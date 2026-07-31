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

        foreach ($rows as $lineNumber => $row) {
            $email = mb_strtolower(trim((string)($row[0] ?? '')));
            $name = trim((string)($row[1] ?? ''));
            $company = trim((string)($row[2] ?? ''));
            $tags = trim((string)($row[3] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['errors'][] = ($lineNumber + 1) . '行目: メールアドレス形式が不正です。';
                continue;
            }

            $existing = Database::fetch('SELECT id FROM recipients WHERE email = ? LIMIT 1', [$email]);
            if ($existing && $mode === 'skip') {
                $result['skipped']++;
                continue;
            }

            if ($existing) {
                Database::execute(
                    'UPDATE recipients SET name = ?, company = ?, tags = ?, updated_at = NOW() WHERE id = ?',
                    [$name, $company, $tags, (int)$existing['id']]
                );
                $result['updated']++;
                continue;
            }

            Database::execute(
                'INSERT INTO recipients (email, name, company, tags, source, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, "active", NOW(), NOW())',
                [$email, $name, $company, $tags, 'import']
            );
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
}
