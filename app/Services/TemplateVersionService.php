<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\TemplateVersionService.php
 * テンプレートの版保存と行単位差分を提供する。
 */

final class TemplateVersionService
{
    public static function saveVersion(array $template, ?int $userId): void
    {
        Database::execute(
            'INSERT INTO mail_template_versions (mail_template_id, subject, body_text, body_html, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [
                (int)$template['id'],
                (string)$template['subject'],
                (string)$template['body_text'],
                (string)($template['body_html'] ?? ''),
                $userId,
            ]
        );
    }

    public static function versions(int $templateId): array
    {
        return Database::fetchAll('SELECT * FROM mail_template_versions WHERE mail_template_id = ? ORDER BY id DESC', [$templateId]);
    }

    public static function diff(string $old, string $new): array
    {
        $oldLines = preg_split('/\R/u', $old) ?: [];
        $newLines = preg_split('/\R/u', $new) ?: [];
        $max = max(count($oldLines), count($newLines));
        $rows = [];
        for ($i = 0; $i < $max; $i++) {
            $left = $oldLines[$i] ?? '';
            $right = $newLines[$i] ?? '';
            $rows[] = ['line' => $i + 1, 'old' => $left, 'new' => $right, 'changed' => $left !== $right];
        }
        return $rows;
    }
}
