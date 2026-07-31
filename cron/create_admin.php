<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\cron\create_admin.php
 * 初期管理者をCLIから作成または更新する。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$email = mb_strtolower(trim((string)($argv[1] ?? '')));
$password = (string)($argv[2] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Usage: php cron/create_admin.php admin@example.com 'strong-password-12+'\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = Database::fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);

if ($existing) {
    Database::execute(
        'UPDATE users
         SET password_hash = ?, role = "system_admin", status = "active", email_verified_at = COALESCE(email_verified_at, NOW()), approved_at = COALESCE(approved_at, NOW()), updated_at = NOW()
         WHERE id = ?',
        [$hash, (int)$existing['id']]
    );
    echo "updated admin: {$email}\n";
    exit(0);
}

Database::execute(
    'INSERT INTO users (email, password_hash, role, status, email_verified_at, approved_at, created_at, updated_at)
     VALUES (?, ?, "system_admin", "active", NOW(), NOW(), NOW(), NOW())',
    [$email, $hash]
);
echo "created admin: {$email}\n";
