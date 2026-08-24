<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\ImportService.php
 * CSV/TSV/プレーンテキストから宛先候補をUTF-8で読み取り、DBへ登録する。
 */

final class ImportService
{
    public static function importUploaded(array $file, string $encoding, string $mode): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('アップロードに失敗しました。');
        }

        $raw = file_get_contents((string)$file['tmp_name']);
        if ($raw === false) {
            throw new RuntimeException('ファイルを読み取れません。');
        }

        $text = self::toUtf8($raw, $encoding);
        $delimiter = self::detectDelimiter($text);
        $rows = self::parseRows($text, $delimiter);
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $organizationId = OrganizationService::currentId();
        $headerMap = self::detectHeaderMap($rows[0] ?? []);
        $startLine = $headerMap === null ? 0 : 1;

        foreach ($rows as $lineNumber => $row) {
            if ($lineNumber < $startLine) {
                continue;
            }

            $record = self::recordFromRow($row, $headerMap);
            $email = self::normalizeEmail($record['email']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['errors'][] = ($lineNumber + 1) . '行目: メールアドレス形式が不正です。';
                continue;
            }
            if ($record['status'] !== null && !self::isAllowedStatus($record['status'])) {
                $result['errors'][] = ($lineNumber + 1) . '行目: 状態の値が不正です。';
                continue;
            }

            $existing = Database::fetch(
                'SELECT id FROM recipients WHERE organization_id = ? AND email = ? LIMIT 1',
                [$organizationId, $email]
            );
            if ($existing && $mode === 'skip') {
                $result['skipped']++;
                continue;
            }

            if ($existing) {
                $sql = 'UPDATE recipients SET name = ?, company = ?, tags = ?, updated_at = NOW()';
                $params = [$record['name'], $record['company'], $record['tags']];
                if ($record['status'] !== null) {
                    $sql .= ', status = ?';
                    $params[] = $record['status'];
                }
                $sql .= ' WHERE id = ?';
                $params[] = (int)$existing['id'];
                Database::execute($sql, $params);
                RecipientTagService::syncForRecipient((int)$existing['id'], $record['tags']);
                $result['updated']++;
                continue;
            }

            Database::execute(
                'INSERT INTO recipients (organization_id, email, name, company, tags, source, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $organizationId,
                    $email,
                    $record['name'],
                    $record['company'],
                    $record['tags'],
                    'import',
                    $record['status'] ?? 'active',
                ]
            );
            RecipientTagService::syncForRecipient(Database::lastInsertId(), $record['tags']);
            $result['inserted']++;
        }

        AuditLogger::log('recipients_imported', $result);
        return $result;
    }

    private static function toUtf8(string $raw, string $encoding): string
    {
        $encoding = $encoding === 'auto'
            ? (mb_detect_encoding($raw, ['UTF-8', 'SJIS-win', 'EUC-JP', 'ISO-2022-JP'], true) ?: 'UTF-8')
            : $encoding;

        $text = mb_convert_encoding($raw, 'UTF-8', $encoding);
        return preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
    }

    private static function detectDelimiter(string $text): string
    {
        $firstLine = strtok($text, "\r\n") ?: '';
        $candidates = ["," => substr_count($firstLine, ","), "\t" => substr_count($firstLine, "\t"), ";" => substr_count($firstLine, ";")];
        arsort($candidates);
        $delimiter = (string)array_key_first($candidates);
        return $candidates[$delimiter] > 0 ? $delimiter : "\n";
    }

    private static function parseRows(string $text, string $delimiter): array
    {
        $rows = [];
        if ($delimiter === "\n") {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $rows[] = [$line];
                }
            }
            return $rows;
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $text);
        rewind($handle);
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private static function detectHeaderMap(array $row): ?array
    {
        $map = [];
        foreach ($row as $index => $label) {
            $key = self::headerKey((string)$label);
            if ($key !== null && !isset($map[$key])) {
                $map[$key] = (int)$index;
            }
        }

        return isset($map['email']) ? $map : null;
    }

    private static function headerKey(string $label): ?string
    {
        $label = mb_strtolower(trim($label));
        $label = preg_replace('/[\s　_\\-（）()]/u', '', $label) ?? $label;
        return match ($label) {
            'email', 'mail', 'eメール', 'メール', 'メールアドレス' => 'email',
            'name', '氏名', '名前', 'お名前', '担当者名' => 'name',
            'company', '会社', '会社名', '法人名', '所属会社' => 'company',
            'tag', 'tags', 'タグ', '分類' => 'tags',
            'status', '状態', 'ステータス' => 'status',
            'lastname', '姓', '苗字', '名字' => 'last_name',
            'firstname', '名' => 'first_name',
            default => null,
        };
    }

    private static function recordFromRow(array $row, ?array $headerMap): array
    {
        if ($headerMap === null) {
            return [
                'email' => (string)($row[0] ?? ''),
                'name' => trim((string)($row[1] ?? '')),
                'company' => trim((string)($row[2] ?? '')),
                'tags' => trim((string)($row[3] ?? '')),
                'status' => null,
            ];
        }

        $name = trim((string)($row[$headerMap['name'] ?? -1] ?? ''));
        if ($name === '' && (isset($headerMap['last_name']) || isset($headerMap['first_name']))) {
            $name = trim(
                trim((string)($row[$headerMap['last_name'] ?? -1] ?? '')) .
                ' ' .
                trim((string)($row[$headerMap['first_name'] ?? -1] ?? ''))
            );
        }

        $status = isset($headerMap['status']) ? trim((string)($row[$headerMap['status']] ?? '')) : null;
        return [
            'email' => (string)($row[$headerMap['email']] ?? ''),
            'name' => $name,
            'company' => trim((string)($row[$headerMap['company'] ?? -1] ?? '')),
            'tags' => trim((string)($row[$headerMap['tags'] ?? -1] ?? '')),
            'status' => $status === '' ? null : $status,
        ];
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim(str_replace('\\@', '@', $email)));
    }

    private static function isAllowedStatus(string $status): bool
    {
        return in_array($status, ['active', 'unsubscribed', 'hard_bounced', 'soft_bounced', 'manually_disabled', 'pending_optin'], true);
    }
}
