<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\OrganizationService.php
 * 複数組織対応のため、既定組織の作成、現在組織ID、組織一覧を提供する。
 */

final class OrganizationService
{
    public static function defaultId(): int
    {
        $org = Database::fetch('SELECT id FROM organizations ORDER BY id LIMIT 1');
        if ($org) {
            return (int)$org['id'];
        }

        Database::execute(
            'INSERT INTO organizations (name, slug, is_active, created_at, updated_at) VALUES ("Default", "default", 1, NOW(), NOW())'
        );
        return Database::lastInsertId();
    }

    public static function currentId(): int
    {
        $user = current_user();
        if ($user && !empty($user['organization_id'])) {
            return (int)$user['organization_id'];
        }
        return self::defaultId();
    }

    public static function publicId(?string $slug = null): int
    {
        $slug = trim((string)$slug);
        if ($slug !== '') {
            $org = Database::fetch('SELECT id FROM organizations WHERE slug = ? AND is_active = 1 LIMIT 1', [$slug]);
            if ($org) {
                return (int)$org['id'];
            }
        }
        return self::defaultId();
    }

    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM organizations ORDER BY id');
    }

    public static function create(string $name, string $slug): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('組織名を入力してください。');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', trim($slug)) ?? '');
        if ($slug === '') {
            throw new RuntimeException('組織スラッグが不正です。');
        }

        Database::execute(
            'INSERT INTO organizations (name, slug, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())',
            [$name, $slug]
        );
    }
}
