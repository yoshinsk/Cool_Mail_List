<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\OpenAiService.php
 * OpenAI Responses APIをcURLで呼び出し、メール文面案をJSON下書きとして保存する。
 */

final class OpenAiService
{
    public static function modelOptions(): array
    {
        return [
            [
                'id' => 'gpt-5.6-terra',
                'name' => 'GPT-5.6 Terra',
                'api' => 'Responses API',
                'summary' => '品質とコストのバランスが良い標準候補。通常のメール文面提案に向く。',
                'features' => 'テキスト生成、構造化JSON、長文コンテキスト、画像入力対応',
                'cost_level' => '中',
                'recommended' => true,
            ],
            [
                'id' => 'gpt-5.6-luna',
                'name' => 'GPT-5.6 Luna',
                'api' => 'Responses API',
                'summary' => 'コスト重視・大量生成向け。定型的な短文案やAB案の量産に向く。',
                'features' => 'テキスト生成、構造化JSON、長文コンテキスト、画像入力対応',
                'cost_level' => '低',
                'recommended' => false,
            ],
            [
                'id' => 'gpt-5.6-sol',
                'name' => 'GPT-5.6 Sol',
                'api' => 'Responses API',
                'summary' => '最上位品質。重要な告知、複雑な説明、ブランドトーン調整に向く。',
                'features' => 'テキスト生成、構造化JSON、長文コンテキスト、画像入力対応',
                'cost_level' => '高',
                'recommended' => false,
            ],
            [
                'id' => 'gpt-5.6',
                'name' => 'GPT-5.6 alias',
                'api' => 'Responses API',
                'summary' => 'GPT-5.6 Sol へ向く公式エイリアス。固定IDより将来差し替えを許容する場合に使う。',
                'features' => 'テキスト生成、構造化JSON、長文コンテキスト、画像入力対応',
                'cost_level' => '高',
                'recommended' => false,
            ],
            [
                'id' => 'gpt-5-mini',
                'name' => 'GPT-5 mini',
                'api' => 'Responses API',
                'summary' => '旧世代の低遅延・低コスト候補。明確な指示の文面整形に向く。',
                'features' => 'テキスト入出力、画像入力、推論トークン対応',
                'cost_level' => '低',
                'recommended' => false,
            ],
            [
                'id' => 'gpt-5-nano',
                'name' => 'GPT-5 nano',
                'api' => 'Responses API',
                'summary' => '最小コスト候補。分類、要約、短い件名案など限定用途向け。',
                'features' => 'テキスト入出力、画像入力、推論トークン対応',
                'cost_level' => '最低',
                'recommended' => false,
            ],
        ];
    }

    public static function normalizeModel(string $model): string
    {
        $ids = array_column(self::modelOptions(), 'id');
        return in_array($model, $ids, true) ? $model : 'gpt-5.6-terra';
    }

    public static function generate(array $input, int $userId): array
    {
        $apiKey = SettingsService::getSecret('openai_api_key', (string)Config::get('openai.api_key', ''));
        if (!$apiKey) {
            throw new RuntimeException('OpenAI APIキーが未設定です。');
        }

        $model = self::normalizeModel(SettingsService::get('openai_model', (string)Config::get('openai.model', 'gpt-5.6-terra')) ?: 'gpt-5.6-terra');
        $prompt = self::buildPrompt($input);
        $requestId = self::saveRequest($userId, $model, $prompt);
        $payload = [
            'model' => $model,
            'instructions' => self::instructions(),
            'input' => $prompt,
        ];

        $response = self::postJson('https://api.openai.com/v1/responses', $payload, $apiKey);
        $rawText = self::extractText($response);
        $draft = self::parseDraft($rawText);

        Database::execute(
            'INSERT INTO ai_generation_results (request_id, result, created_at) VALUES (?, ?, NOW())',
            [$requestId, json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
        AuditLogger::log('ai_content_generated', ['request_id' => $requestId, 'model' => $model], $userId);

        return ['request_id' => $requestId, 'result_id' => Database::lastInsertId(), 'draft' => $draft];
    }

    public static function adoptAsTemplate(int $resultId, int $userId): int
    {
        $row = Database::fetch('SELECT * FROM ai_generation_results WHERE id = ? LIMIT 1', [$resultId]);
        if (!$row) {
            throw new RuntimeException('AI生成結果が見つかりません。');
        }

        $draft = json_decode((string)$row['result'], true);
        if (!is_array($draft)) {
            throw new RuntimeException('AI生成結果の形式が不正です。');
        }

        Database::execute(
            'INSERT INTO mail_templates (name, subject, body_text, body_html, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [
                'AI下書き ' . date('Y-m-d H:i'),
                (string)($draft['subject'] ?? ''),
                (string)($draft['body_text'] ?? ''),
                sanitize_html_email((string)($draft['body_html'] ?? '')),
                $userId,
            ]
        );
        $templateId = Database::lastInsertId();
        Database::execute('UPDATE ai_generation_results SET adopted_at = NOW() WHERE id = ?', [$resultId]);
        AuditLogger::log('ai_content_adopted', ['result_id' => $resultId, 'template_id' => $templateId], $userId);
        return $templateId;
    }

    public static function recentResults(): array
    {
        return Database::fetchAll(
            'SELECT r.*, q.model, q.prompt, u.email AS user_email
             FROM ai_generation_results r
             JOIN ai_generation_requests q ON q.id = r.request_id
             LEFT JOIN users u ON u.id = q.user_id
             ORDER BY r.id DESC LIMIT 20'
        );
    }

    private static function saveRequest(int $userId, string $model, string $prompt): int
    {
        Database::execute(
            'INSERT INTO ai_generation_requests (user_id, prompt, model, created_at) VALUES (?, ?, ?, NOW())',
            [$userId, $prompt, $model]
        );
        return Database::lastInsertId();
    }

    private static function instructions(): string
    {
        return 'あなたは日本語メールマーケティングの編集者です。誇大表現、断定的な不実表示、個人情報の過剰利用を避け、必ず購読停止URL差し込み {{unsubscribe_url}} を本文末尾に含めてください。出力はJSONのみで、subject, body_text, body_html, notes のキーを持たせてください。';
    }

    private static function buildPrompt(array $input): string
    {
        $pairs = [
            '配信目的' => $input['purpose'] ?? '',
            '対象者' => $input['audience'] ?? '',
            'トーン' => $input['tone'] ?? '',
            '商品/サービス概要' => $input['product'] ?? '',
            '伝えたい要点' => $input['points'] ?? '',
            'CTA' => $input['cta'] ?? '',
            '文字数目安' => $input['length'] ?? '',
            'HTMLメール化' => !empty($input['with_html']) ? '必要' : '不要',
        ];

        $lines = [];
        foreach ($pairs as $label => $value) {
            $lines[] = $label . ': ' . trim((string)$value);
        }
        return implode("\n", $lines);
    }

    private static function postJson(string $url, array $payload, string $apiKey): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL拡張が有効ではありません。');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 60,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('OpenAI API呼び出しに失敗しました: HTTP ' . $status . ' ' . $error . ' ' . (string)$body);
        }

        $decoded = json_decode((string)$body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI API応答をJSONとして解析できません。');
        }
        return $decoded;
    }

    private static function extractText(array $response): string
    {
        if (!empty($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        $texts = [];
        self::collectText($response['output'] ?? [], $texts);
        $text = trim(implode("\n", $texts));
        if ($text === '') {
            throw new RuntimeException('OpenAI API応答にテキストが含まれていません。');
        }
        return $text;
    }

    private static function collectText(mixed $node, array &$texts): void
    {
        if (is_array($node)) {
            if (($node['type'] ?? '') === 'output_text' && isset($node['text'])) {
                $texts[] = (string)$node['text'];
            }
            foreach ($node as $child) {
                self::collectText($child, $texts);
            }
        }
    }

    private static function parseDraft(string $rawText): array
    {
        $text = trim($rawText);
        $text = preg_replace('/^```json\s*|\s*```$/u', '', $text) ?? $text;
        $draft = json_decode($text, true);
        if (!is_array($draft)) {
            return [
                'subject' => 'AI文面案',
                'body_text' => $rawText . "\n\n購読停止: {{unsubscribe_url}}",
                'body_html' => '',
                'notes' => ['JSON形式で取得できなかったため、応答全文を本文に保存しました。'],
            ];
        }

        $draft['subject'] = trim((string)($draft['subject'] ?? 'AI文面案'));
        $draft['body_text'] = self::ensureUnsubscribe((string)($draft['body_text'] ?? ''));
        $draft['body_html'] = self::ensureHtmlUnsubscribe((string)($draft['body_html'] ?? ''));
        $draft['notes'] = is_array($draft['notes'] ?? null) ? $draft['notes'] : [];
        return $draft;
    }

    private static function ensureUnsubscribe(string $body): string
    {
        return str_contains($body, '{{unsubscribe_url}}') ? $body : rtrim($body) . "\n\n購読停止: {{unsubscribe_url}}";
    }

    private static function ensureHtmlUnsubscribe(string $html): string
    {
        if ($html === '' || str_contains($html, '{{unsubscribe_url}}')) {
            return $html;
        }
        return rtrim($html) . '<p><a href="{{unsubscribe_url}}">購読停止</a></p>';
    }
}
