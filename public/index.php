<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\public\index.php
 * Cool Mail List のWebフロントコントローラ。認証、CSRF、各管理画面の処理を集約する。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

Session::start();

$route = (string)($_GET['r'] ?? 'dashboard');
$publicRoutes = ['login', 'google_callback', 'register', 'forgot_password', 'reset_password', 'unsubscribe', 'subscribe', 'confirm_optin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route !== 'unsubscribe') {
    Csrf::requireValid();
}

if (!Auth::check() && !in_array($route, $publicRoutes, true)) {
    redirect_route('login');
}

if (Auth::check() && in_array($route, ['login', 'register'], true)) {
    redirect_route('dashboard');
}

if (Auth::check() && !in_array($route, $publicRoutes, true) && !route_allowed_for_user($route)) {
    http_response_code(403);
    render('error', [
        'title' => '権限がありません',
        'active' => '',
        'message' => 'この機能を利用できる権限がありません。表示されているメニューから操作してください。',
    ]);
    exit;
}

try {
    match ($route) {
        'login' => handle_login(),
        'google_callback' => handle_google_callback(),
        'register' => handle_register(),
        'forgot_password' => handle_forgot_password(),
        'reset_password' => handle_reset_password(),
        'logout' => handle_logout(),
        'recipients' => handle_recipients(),
        'import' => handle_import(),
        'senders' => handle_senders(),
        'dns_checks' => handle_dns_checks(),
        'templates' => handle_templates(),
        'template_edit' => handle_template_edit(),
        'template_compare' => handle_template_compare(),
        'template_delete' => handle_template_delete(),
        'ai' => handle_ai(),
        'test_send' => handle_test_send(),
        'campaigns' => handle_campaigns(),
        'queue_campaign' => handle_queue_campaign(),
        'queue' => handle_queue(),
        'unsubscribes' => handle_unsubscribes(),
        'unsubscribe' => handle_unsubscribe(),
        'subscribe' => handle_subscribe(),
        'confirm_optin' => handle_confirm_optin(),
        'bounces' => handle_bounces(),
        'organizations' => handle_organizations(),
        'users' => handle_users(),
        'audit' => handle_audit(),
        'settings' => handle_settings(),
        default => handle_dashboard(),
    };
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    render('error', [
        'title' => 'エラー',
        'active' => '',
        'message' => Config::get('app.env') === 'production' ? '処理中にエラーが発生しました。' : $e->getMessage(),
    ]);
}

function handle_login(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (Auth::login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            redirect_route('dashboard');
        }
        Session::flash('error', 'ログインできません。メールアドレス、パスワード、承認状態を確認してください。');
    }

    render('auth/login', [
        'title' => 'ログイン',
        'active' => 'login',
        'googleSettings' => GoogleAuthService::settings(),
    ]);
}

function handle_google_callback(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_route('login');
    }

    $credential = (string)($_POST['credential'] ?? '');
    if ($credential === '') {
        Session::flash('error', 'Googleログイン情報を受け取れませんでした。');
        redirect_route('login');
    }

    try {
        $result = GoogleAuthService::handleCredential($credential);
    } catch (Throwable $e) {
        AuditLogger::log('google_login_failed', ['reason' => $e->getMessage()]);
        Session::flash('error', 'Googleログインに失敗しました: ' . $e->getMessage());
        redirect_route('login');
    }

    if (!empty($result['ok'])) {
        redirect_route('dashboard');
    }

    Session::flash('success', (string)$result['email'] . ' を登録しました。管理者承認後にGoogleログインできます。');
    redirect_route('login');
}

function handle_register(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            Session::flash('error', 'メールアドレス形式、またはパスワード長が不足しています。');
        } elseif (Database::fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email])) {
            Session::flash('error', 'このメールアドレスは登録済みです。');
        } else {
            Database::execute(
                'INSERT INTO users (organization_id, email, password_hash, role, status, email_verified_at, created_at, updated_at)
                 VALUES (?, ?, ?, "viewer", "pending_approval", NOW(), NOW(), NOW())',
                [OrganizationService::defaultId(), $email, password_hash($password, PASSWORD_DEFAULT)]
            );
            AuditLogger::log('user_registered', ['email' => $email]);
            Session::flash('success', '登録しました。管理者承認後にログインできます。');
            redirect_route('login');
        }
    }
    render('auth/register', ['title' => '利用者登録', 'active' => 'register']);
}

function handle_forgot_password(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        PasswordResetService::request((string)($_POST['email'] ?? ''));
        Session::flash('success', '登録済みで有効なアカウントの場合、再設定メールを送信しました。');
        redirect_route('login');
    }
    render('auth/forgot_password', ['title' => 'パスワード再設定', 'active' => 'forgot_password']);
}

function handle_reset_password(): void
{
    $token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
    $valid = PasswordResetService::findValid($token) !== null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if ($password !== $confirm || strlen($password) < 12) {
            Session::flash('error', 'パスワードが一致しないか、12文字未満です。');
            redirect_route('reset_password', ['t' => $token]);
        }
        if (PasswordResetService::reset($token, $password)) {
            Session::flash('success', 'パスワードを更新しました。ログインしてください。');
            redirect_route('login');
        }
        Session::flash('error', '再設定URLが無効、または期限切れです。');
        redirect_route('forgot_password');
    }
    render('auth/reset_password', ['title' => 'パスワード再設定', 'active' => 'reset_password', 'token' => $token, 'valid' => $valid]);
}

function handle_logout(): void
{
    Auth::logout();
    redirect_route('login');
}

function handle_dashboard(): void
{
    $orgId = OrganizationService::currentId();
    $canViewAudit = route_allowed_for_user('audit');
    $stats = [
        'recipients' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE organization_id = ?', [$orgId])['c'] ?? 0,
        'active' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE organization_id = ? AND status = "active"', [$orgId])['c'] ?? 0,
        'queued' => Database::fetch('SELECT COUNT(*) AS c FROM mail_queue WHERE organization_id = ? AND status IN ("pending", "temporary_failed")', [$orgId])['c'] ?? 0,
        'sent' => Database::fetch('SELECT COUNT(*) AS c FROM mail_queue WHERE organization_id = ? AND status = "sent"', [$orgId])['c'] ?? 0,
        'bounced' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE organization_id = ? AND status IN ("hard_bounced", "soft_bounced")', [$orgId])['c'] ?? 0,
        'unsubscribed' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE organization_id = ? AND status = "unsubscribed"', [$orgId])['c'] ?? 0,
    ];
    $recentLogs = $canViewAudit ? Database::fetchAll('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 8') : [];
    render('dashboard', [
        'title' => 'ダッシュボード',
        'active' => 'dashboard',
        'stats' => $stats,
        'recentLogs' => $recentLogs,
        'canViewAudit' => $canViewAudit,
    ]);
}

function handle_recipients(): void
{
    $orgId = OrganizationService::currentId();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::requireRole(['system_admin', 'delivery_admin']);
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'メールアドレス形式が不正です。');
        } else {
            Database::execute(
                'INSERT INTO recipients (organization_id, email, name, company, tags, source, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, "manual", ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), company = VALUES(company), tags = VALUES(tags), status = VALUES(status), updated_at = NOW()',
                [$orgId, $email, trim((string)$_POST['name']), trim((string)$_POST['company']), trim((string)$_POST['tags']), (string)$_POST['status']]
            );
            AuditLogger::log('recipient_saved', ['email' => $email, 'organization_id' => $orgId]);
            Session::flash('success', '宛先を保存しました。');
            redirect_route('recipients');
        }
    }

    $where = ['organization_id = ?'];
    $params = [$orgId];
    if (!empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = (string)$_GET['status'];
    }
    if (!empty($_GET['q'])) {
        $where[] = '(email LIKE ? OR name LIKE ? OR company LIKE ? OR tags LIKE ?)';
        $q = '%' . (string)$_GET['q'] . '%';
        array_push($params, $q, $q, $q, $q);
    }
    $sql = 'SELECT * FROM recipients WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 200';
    render('recipients', [
        'title' => '宛先管理',
        'active' => 'recipients',
        'recipients' => Database::fetchAll($sql, $params),
    ]);
}

function handle_import(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin']);
    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = ImportService::importUploaded($_FILES['import_file'] ?? [], (string)$_POST['encoding'], (string)$_POST['mode']);
        Session::flash('success', 'インポート処理を完了しました。');
    }
    render('import', ['title' => 'インポート', 'active' => 'import', 'result' => $result]);
}

function handle_senders(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin']);
    $orgId = OrganizationService::currentId();
    $smtpCheckResult = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? 'create');
        try {
            if ($action === 'create') {
                sender_create($orgId);
                Session::flash('success', '送信者/SMTP設定を保存しました。');
                redirect_route('senders');
            }
            if ($action === 'update') {
                sender_update($orgId, (int)($_POST['sender_id'] ?? 0));
                Session::flash('success', '送信者/SMTP設定を更新しました。');
                redirect_route('senders');
            }
            if ($action === 'delete') {
                $message = sender_delete($orgId, (int)($_POST['sender_id'] ?? 0));
                Session::flash('success', $message);
                redirect_route('senders');
            }
            if ($action === 'check_smtp') {
                $smtpCheckResult = MailerService::checkSenderSmtp((int)($_POST['sender_id'] ?? 0));
            } else {
                Session::flash('error', '不明な操作です。');
                redirect_route('senders');
            }
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            if ($action !== 'check_smtp') {
                redirect_route('senders');
            }
        }
    }

    $senders = Database::fetchAll(
        'SELECT si.*, sa.id AS smtp_account_id, sa.name AS account_name, sa.smtp_host, sa.smtp_port, sa.encryption,
                sa.auth_username, sa.auth_password_ciphertext, sa.per_minute_limit, sa.daily_limit, sa.is_active AS smtp_is_active
         FROM sender_identities si
         JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
         WHERE si.organization_id = ?
         ORDER BY si.id DESC',
        [$orgId]
    );
    render('senders', ['title' => '送信者/SMTP管理', 'active' => 'senders', 'senders' => $senders, 'smtpCheckResult' => $smtpCheckResult]);
}

function sender_create(int $orgId): void
{
    $input = sender_form_input();
    assert_sender_email_unique($orgId, $input['from_email']);
    $passwordCipher = $input['smtp_password'] !== '' ? CryptoService::encrypt($input['smtp_password']) : null;

    Database::execute(
        'INSERT INTO smtp_accounts
            (organization_id, name, smtp_host, smtp_port, encryption, auth_username, auth_password_ciphertext, per_minute_limit, daily_limit, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
        [
            $orgId,
            $input['account_name'],
            $input['smtp_host'],
            $input['smtp_port'],
            $input['encryption'],
            $input['auth_username'],
            $passwordCipher,
            $input['per_minute_limit'],
            $input['daily_limit'],
        ]
    );
    $smtpId = Database::lastInsertId();
    Database::execute(
        'INSERT INTO sender_identities
            (organization_id, smtp_account_id, from_name, from_email, reply_to, dkim_policy, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
        [$orgId, $smtpId, $input['from_name'], $input['from_email'], $input['reply_to'], $input['dkim_policy']]
    );
    AuditLogger::log('sender_identity_created', ['from_email' => $input['from_email'], 'organization_id' => $orgId]);
}

function sender_update(int $orgId, int $senderId): void
{
    $sender = sender_with_smtp($orgId, $senderId);
    $input = sender_form_input();
    $isActive = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;
    assert_sender_email_unique($orgId, $input['from_email'], $senderId);

    $pdo = Database::pdo();
    $pdo->beginTransaction();
    try {
        $smtpParams = [
            $input['account_name'],
            $input['smtp_host'],
            $input['smtp_port'],
            $input['encryption'],
            $input['auth_username'],
            $input['per_minute_limit'],
            $input['daily_limit'],
            $isActive,
        ];
        $passwordSql = '';
        if ($input['smtp_password'] !== '') {
            $passwordSql = ', auth_password_ciphertext = ?';
            $smtpParams[] = CryptoService::encrypt($input['smtp_password']);
        }
        $smtpParams[] = (int)$sender['smtp_account_id'];
        $smtpParams[] = $orgId;
        Database::execute(
            'UPDATE smtp_accounts
             SET name = ?, smtp_host = ?, smtp_port = ?, encryption = ?, auth_username = ?, per_minute_limit = ?, daily_limit = ?, is_active = ?, updated_at = NOW()' . $passwordSql . '
             WHERE id = ? AND organization_id = ?',
            $smtpParams
        );
        Database::execute(
            'UPDATE sender_identities
             SET from_name = ?, from_email = ?, reply_to = ?, dkim_policy = ?, is_active = ?, updated_at = NOW()
             WHERE id = ? AND organization_id = ?',
            [$input['from_name'], $input['from_email'], $input['reply_to'], $input['dkim_policy'], $isActive, $senderId, $orgId]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    AuditLogger::log('sender_identity_updated', ['sender_identity_id' => $senderId, 'from_email' => $input['from_email']]);
}

function sender_delete(int $orgId, int $senderId): string
{
    $sender = sender_with_smtp($orgId, $senderId);
    $usage = sender_usage_counts($senderId, (int)$sender['smtp_account_id']);
    $pdo = Database::pdo();
    $pdo->beginTransaction();
    try {
        if (($usage['campaigns'] + $usage['queue']) > 0) {
            Database::execute('UPDATE sender_identities SET is_active = 0, updated_at = NOW() WHERE id = ? AND organization_id = ?', [$senderId, $orgId]);
            $otherActive = Database::fetch('SELECT COUNT(*) AS c FROM sender_identities WHERE smtp_account_id = ? AND id <> ? AND is_active = 1', [(int)$sender['smtp_account_id'], $senderId]);
            if ((int)($otherActive['c'] ?? 0) === 0) {
                Database::execute('UPDATE smtp_accounts SET is_active = 0, updated_at = NOW() WHERE id = ? AND organization_id = ?', [(int)$sender['smtp_account_id'], $orgId]);
            }
            $pdo->commit();
            AuditLogger::log('sender_identity_disabled', ['sender_identity_id' => $senderId, 'usage' => $usage]);
            return '使用履歴があるため、送信者/SMTP設定を無効化しました。';
        }

        Database::execute('DELETE FROM sender_identities WHERE id = ? AND organization_id = ?', [$senderId, $orgId]);
        $smtpUsers = Database::fetch('SELECT COUNT(*) AS c FROM sender_identities WHERE smtp_account_id = ?', [(int)$sender['smtp_account_id']]);
        if ((int)($smtpUsers['c'] ?? 0) === 0) {
            Database::execute('DELETE FROM smtp_accounts WHERE id = ? AND organization_id = ?', [(int)$sender['smtp_account_id'], $orgId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    AuditLogger::log('sender_identity_deleted', ['sender_identity_id' => $senderId, 'from_email' => $sender['from_email']]);
    return '送信者/SMTP設定を削除しました。';
}

function sender_form_input(): array
{
    $fromEmail = mb_strtolower(trim((string)($_POST['from_email'] ?? '')));
    $replyTo = mb_strtolower(trim((string)($_POST['reply_to'] ?? '')));
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Fromメールの形式が不正です。');
    }
    if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Reply-Toの形式が不正です。');
    }

    $accountName = trim((string)($_POST['account_name'] ?? ''));
    $fromName = trim((string)($_POST['from_name'] ?? ''));
    $smtpHost = trim((string)($_POST['smtp_host'] ?? ''));
    if ($accountName === '' || $fromName === '' || $smtpHost === '') {
        throw new RuntimeException('SMTP設定名、From表示名、SMTPホストは必須です。');
    }

    $encryption = (string)($_POST['encryption'] ?? 'tls');
    if (!in_array($encryption, ['tls', 'ssl', ''], true)) {
        $encryption = 'tls';
    }
    $smtpPort = min(65535, max(1, (int)($_POST['smtp_port'] ?? 587)));
    if ($smtpPort === 465 && $encryption === 'tls') {
        throw new RuntimeException('SMTPポート465を使う場合は、暗号化にSSLを選択してください。TLS(STARTTLS)を使う場合は通常587番です。');
    }
    $dkimPolicy = (string)($_POST['dkim_policy'] ?? 'recommended');
    if (!in_array($dkimPolicy, ['recommended', 'required', 'none'], true)) {
        $dkimPolicy = 'recommended';
    }

    return [
        'account_name' => $accountName,
        'from_name' => $fromName,
        'from_email' => $fromEmail,
        'reply_to' => $replyTo,
        'smtp_host' => $smtpHost,
        'smtp_port' => $smtpPort,
        'encryption' => $encryption,
        'auth_username' => trim((string)($_POST['auth_username'] ?? '')),
        'smtp_password' => trim((string)($_POST['smtp_password'] ?? '')),
        'per_minute_limit' => max(1, (int)($_POST['per_minute_limit'] ?? 5)),
        'daily_limit' => max(1, (int)($_POST['daily_limit'] ?? 1000)),
        'dkim_policy' => $dkimPolicy,
    ];
}

function sender_with_smtp(int $orgId, int $senderId): array
{
    $sender = Database::fetch(
        'SELECT si.*, sa.id AS smtp_account_id
         FROM sender_identities si
         JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
         WHERE si.id = ? AND si.organization_id = ? LIMIT 1',
        [$senderId, $orgId]
    );
    if (!$sender) {
        throw new RuntimeException('送信者/SMTP設定が見つかりません。');
    }
    return $sender;
}

function sender_usage_counts(int $senderId, int $smtpId): array
{
    $campaigns = Database::fetch('SELECT COUNT(*) AS c FROM campaigns WHERE sender_identity_id = ?', [$senderId]);
    $queue = Database::fetch('SELECT COUNT(*) AS c FROM mail_queue WHERE sender_identity_id = ? OR smtp_account_id = ?', [$senderId, $smtpId]);
    return ['campaigns' => (int)($campaigns['c'] ?? 0), 'queue' => (int)($queue['c'] ?? 0)];
}

function assert_sender_email_unique(int $orgId, string $fromEmail, ?int $exceptSenderId = null): void
{
    $params = [$orgId, $fromEmail];
    $sql = 'SELECT id FROM sender_identities WHERE organization_id = ? AND from_email = ?';
    if ($exceptSenderId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $exceptSenderId;
    }
    $sql .= ' LIMIT 1';
    if (Database::fetch($sql, $params)) {
        throw new RuntimeException('同じFromメールの送信者が既に登録されています。');
    }
}

function handle_dns_checks(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin']);
    $orgId = OrganizationService::currentId();
    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $result = DnsDiagnosticsService::run((int)$_POST['sender_identity_id']);
            Session::flash('success', 'DNS診断を実行しました。');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
    }

    render('dns_checks', [
        'title' => 'DNS診断',
        'active' => 'dns_checks',
        'senders' => Database::fetchAll('SELECT * FROM sender_identities WHERE organization_id = ? AND is_active = 1 ORDER BY id DESC', [$orgId]),
        'result' => $result,
        'history' => DnsDiagnosticsService::latest(),
    ]);
}

function handle_templates(): void
{
    $orgId = OrganizationService::currentId();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::requireRole(['system_admin', 'delivery_admin', 'editor']);
        $name = trim((string)$_POST['name']);
        $subject = trim((string)$_POST['subject']);
        $bodyText = (string)$_POST['body_text'];
        $bodyHtml = sanitize_html_email((string)($_POST['body_html'] ?? ''));
        Database::execute(
            'INSERT INTO mail_templates (organization_id, name, subject, body_text, body_html, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$orgId, $name, $subject, $bodyText, $bodyHtml, (int)current_user()['id']]
        );
        TemplateVersionService::saveVersion([
            'id' => Database::lastInsertId(),
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
        ], (int)current_user()['id']);
        AuditLogger::log('template_created', ['name' => $name, 'organization_id' => $orgId]);
        Session::flash('success', 'テンプレートを保存しました。');
        redirect_route('templates');
    }

    render('templates', [
        'title' => 'テンプレート管理',
        'active' => 'templates',
        'templates' => Database::fetchAll('SELECT * FROM mail_templates WHERE organization_id = ? ORDER BY id DESC LIMIT 100', [$orgId]),
        'senders' => Database::fetchAll('SELECT * FROM sender_identities WHERE organization_id = ? AND is_active = 1 ORDER BY id DESC', [$orgId]),
    ]);
}

function handle_template_edit(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin', 'editor']);
    $orgId = OrganizationService::currentId();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $template = Database::fetch('SELECT * FROM mail_templates WHERE id = ? AND organization_id = ? LIMIT 1', [$id, $orgId]);
    if (!$template) {
        throw new RuntimeException('テンプレートが見つかりません。');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        TemplateVersionService::saveVersion($template, (int)current_user()['id']);
        Database::execute(
            'UPDATE mail_templates
             SET name = ?, subject = ?, body_text = ?, body_html = ?, updated_at = NOW()
             WHERE id = ? AND organization_id = ?',
            [
                trim((string)$_POST['name']),
                trim((string)$_POST['subject']),
                (string)$_POST['body_text'],
                sanitize_html_email((string)($_POST['body_html'] ?? '')),
                $id,
                $orgId,
            ]
        );
        AuditLogger::log('template_updated', ['template_id' => $id]);
        Session::flash('success', 'テンプレートを更新しました。');
        redirect_route('template_edit', ['id' => $id]);
    }

    render('template_edit', [
        'title' => 'テンプレート編集',
        'active' => 'templates',
        'template' => $template,
        'versions' => TemplateVersionService::versions($id),
    ]);
}

function handle_template_compare(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin', 'editor']);
    $orgId = OrganizationService::currentId();
    $id = (int)($_GET['id'] ?? 0);
    $template = Database::fetch('SELECT * FROM mail_templates WHERE id = ? AND organization_id = ? LIMIT 1', [$id, $orgId]);
    if (!$template) {
        throw new RuntimeException('テンプレートが見つかりません。');
    }

    $versions = TemplateVersionService::versions($id);
    $leftKey = (string)($_GET['left'] ?? ($versions[0]['id'] ?? 'current'));
    $rightKey = (string)($_GET['right'] ?? 'current');
    $left = template_snapshot($template, $versions, $leftKey);
    $right = template_snapshot($template, $versions, $rightKey);

    render('template_compare', [
        'title' => 'テンプレート差分',
        'active' => 'templates',
        'template' => $template,
        'versions' => $versions,
        'leftKey' => $leftKey,
        'rightKey' => $rightKey,
        'left' => $left,
        'right' => $right,
        'subjectDiff' => TemplateVersionService::diff($left['subject'], $right['subject']),
        'textDiff' => TemplateVersionService::diff($left['body_text'], $right['body_text']),
        'htmlDiff' => TemplateVersionService::diff((string)$left['body_html'], (string)$right['body_html']),
    ]);
}

function handle_template_delete(): void
{
    Auth::requireRole(['system_admin']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_route('templates');
    }

    $orgId = OrganizationService::currentId();
    $id = (int)($_POST['id'] ?? 0);
    $template = Database::fetch('SELECT * FROM mail_templates WHERE id = ? AND organization_id = ? LIMIT 1', [$id, $orgId]);
    if (!$template) {
        Session::flash('error', '削除対象のテンプレートが見つかりません。');
        redirect_route('templates');
    }

    $campaigns = Database::fetch('SELECT COUNT(*) AS c FROM campaigns WHERE template_id = ? AND organization_id = ?', [$id, $orgId]);
    if ((int)($campaigns['c'] ?? 0) > 0) {
        Session::flash('error', 'このテンプレートはキャンペーンで使用中のため削除できません。先に該当キャンペーンを確認してください。');
        redirect_route('templates');
    }

    Database::execute('DELETE FROM mail_templates WHERE id = ? AND organization_id = ?', [$id, $orgId]);
    AuditLogger::log('template_deleted', ['template_id' => $id, 'name' => $template['name']]);
    Session::flash('success', 'テンプレートを削除しました。');
    redirect_route('templates');
}

function handle_ai(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin', 'editor']);
    $latestDraft = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'generate') {
            $generated = OpenAiService::generate($_POST, (int)current_user()['id']);
            $latestDraft = $generated['draft'];
            Session::flash('success', 'AI文面案を生成しました。');
        } elseif ($action === 'adopt') {
            $templateId = OpenAiService::adoptAsTemplate((int)$_POST['result_id'], (int)current_user()['id']);
            Session::flash('success', 'AI文面案をテンプレート #' . $templateId . ' として保存しました。');
            redirect_route('templates');
        }
    }

    render('ai', [
        'title' => 'AI文面提案',
        'active' => 'ai',
        'apiKeyReady' => SettingsService::isSecretSet('openai_api_key', (string)Config::get('openai.api_key', '')),
        'latestDraft' => $latestDraft,
        'results' => OpenAiService::recentResults(),
    ]);
}

function handle_test_send(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin', 'editor']);
    $result = MailerService::sendTest((int)$_POST['sender_identity_id'], (int)$_POST['template_id'], (string)$_POST['test_to']);
    Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'テスト送信しました。' : 'テスト送信に失敗しました: ' . ($result['error'] ?? 'unknown'));
    AuditLogger::log('test_mail_sent', ['ok' => $result['ok'], 'to' => $_POST['test_to']]);
    redirect_route('templates');
}

function handle_campaigns(): void
{
    $orgId = OrganizationService::currentId();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::requireRole(['system_admin', 'delivery_admin', 'sender']);
        $template = Database::fetch('SELECT * FROM mail_templates WHERE id = ? AND organization_id = ?', [(int)$_POST['template_id'], $orgId]);
        $sender = Database::fetch('SELECT * FROM sender_identities WHERE id = ? AND organization_id = ?', [(int)$_POST['sender_identity_id'], $orgId]);
        if (!$template || !$sender) {
            Session::flash('error', '送信者またはテンプレートが見つかりません。');
            redirect_route('campaigns');
        }
        $body = ($template['body_text'] ?? '') . "\n" . ($template['body_html'] ?? '');
        if (!str_contains($body, '{{unsubscribe_url}}')) {
            Session::flash('error', 'テンプレートに {{unsubscribe_url}} が含まれていないため作成できません。');
            redirect_route('campaigns');
        }
        Database::execute(
            'INSERT INTO campaigns (organization_id, name, sender_identity_id, template_id, subject_override, scheduled_at, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, "draft", ?, NOW(), NOW())',
            [
                $orgId,
                trim((string)$_POST['name']),
                (int)$_POST['sender_identity_id'],
                (int)$_POST['template_id'],
                trim((string)$_POST['subject_override']) ?: null,
                normalize_datetime((string)$_POST['scheduled_at']),
                (int)current_user()['id'],
            ]
        );
        AuditLogger::log('campaign_created', ['name' => $_POST['name'], 'organization_id' => $orgId]);
        Session::flash('success', 'キャンペーンを作成しました。');
        redirect_route('campaigns');
    }

    render('campaigns', [
        'title' => 'キャンペーン',
        'active' => 'campaigns',
        'campaigns' => Database::fetchAll(
            'SELECT c.*, si.from_email, mt.name AS template_name,
                    (SELECT COUNT(*) FROM mail_queue mq WHERE mq.campaign_id = c.id) AS queue_count
             FROM campaigns c
             JOIN sender_identities si ON si.id = c.sender_identity_id
             JOIN mail_templates mt ON mt.id = c.template_id
             WHERE c.organization_id = ?
             ORDER BY c.id DESC',
            [$orgId]
        ),
        'senders' => Database::fetchAll('SELECT * FROM sender_identities WHERE organization_id = ? AND is_active = 1 ORDER BY id DESC', [$orgId]),
        'templates' => Database::fetchAll('SELECT * FROM mail_templates WHERE organization_id = ? ORDER BY id DESC', [$orgId]),
    ]);
}

function handle_queue_campaign(): void
{
    Auth::requireRole(['system_admin', 'delivery_admin']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect_route('campaigns');
    }
    $created = QueueService::queueCampaign((int)$_POST['id']);
    Session::flash('success', $created . '件の配信キューを作成しました。');
    redirect_route('campaigns');
}

function handle_queue(): void
{
    $orgId = OrganizationService::currentId();
    $rows = Database::fetchAll(
        'SELECT mq.*, c.name AS campaign_name, r.email AS recipient_email
         FROM mail_queue mq
         JOIN campaigns c ON c.id = mq.campaign_id
         JOIN recipients r ON r.id = mq.recipient_id
         WHERE mq.organization_id = ?
         ORDER BY mq.id DESC LIMIT 200',
        [$orgId]
    );
    render('queue', ['title' => '配信キュー', 'active' => 'queue', 'rows' => $rows]);
}

function handle_unsubscribes(): void
{
    $orgId = OrganizationService::currentId();
    render('unsubscribes', [
        'title' => '購読停止一覧',
        'active' => 'unsubscribes',
        'rows' => Database::fetchAll(
            'SELECT u.*, r.email
             FROM unsubscribes u
             JOIN recipients r ON r.id = u.recipient_id
             WHERE r.organization_id = ?
             ORDER BY u.id DESC LIMIT 200',
            [$orgId]
        ),
    ]);
}

function handle_unsubscribe(): void
{
    $token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
    $queue = $token !== '' ? Database::fetch(
        'SELECT mq.*, r.email FROM mail_queue mq JOIN recipients r ON r.id = mq.recipient_id WHERE mq.unsubscribe_token = ? LIMIT 1',
        [$token]
    ) : null;

    if (!$queue) {
        render('unsubscribe', ['title' => '購読停止', 'active' => '', 'queue' => null]);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Database::execute(
            'INSERT INTO unsubscribes (recipient_id, mail_queue_id, reason, token, created_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), created_at = NOW()',
            [(int)$queue['recipient_id'], (int)$queue['id'], 'one-click unsubscribe', $token]
        );
        Database::execute('UPDATE recipients SET status = "unsubscribed", updated_at = NOW() WHERE id = ?', [(int)$queue['recipient_id']]);
        AuditLogger::log('recipient_unsubscribed', ['recipient_id' => $queue['recipient_id']]);
        render('unsubscribe_done', ['title' => '購読停止完了', 'active' => '', 'email' => $queue['email']]);
        return;
    }

    render('unsubscribe', ['title' => '購読停止', 'active' => '', 'queue' => $queue]);
}

function handle_subscribe(): void
{
    $slug = (string)($_GET['org'] ?? $_POST['org'] ?? '');
    $done = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            OptInService::request(
                (string)($_POST['email'] ?? ''),
                (string)($_POST['name'] ?? ''),
                (string)($_POST['company'] ?? ''),
                OrganizationService::publicId($slug)
            );
            $done = true;
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
    }

    render('subscribe', [
        'title' => '配信登録',
        'active' => '',
        'orgSlug' => $slug,
        'done' => $done,
    ]);
}

function handle_confirm_optin(): void
{
    $email = OptInService::confirm((string)($_GET['t'] ?? ''));
    render('confirm_optin', ['title' => '配信登録確認', 'active' => '', 'email' => $email]);
}

function handle_bounces(): void
{
    $orgId = OrganizationService::currentId();
    render('bounces', [
        'title' => 'バウンス管理',
        'active' => 'bounces',
        'rows' => Database::fetchAll(
            'SELECT * FROM bounce_messages
             WHERE organization_id = ? OR organization_id IS NULL
             ORDER BY id DESC LIMIT 200',
            [$orgId]
        ),
    ]);
}

function handle_organizations(): void
{
    Auth::requireRole(['system_admin']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            OrganizationService::create((string)($_POST['name'] ?? ''), (string)($_POST['slug'] ?? ''));
            AuditLogger::log('organization_created', ['name' => $_POST['name'] ?? '', 'slug' => $_POST['slug'] ?? '']);
            Session::flash('success', '組織を作成しました。');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect_route('organizations');
    }

    render('organizations', [
        'title' => '組織管理',
        'active' => 'organizations',
        'organizations' => OrganizationService::all(),
    ]);
}

function handle_users(): void
{
    Auth::requireRole(['system_admin']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Database::execute(
            'UPDATE users
             SET organization_id = ?, role = ?, status = ?, approved_at = CASE WHEN ? = "active" THEN COALESCE(approved_at, NOW()) ELSE approved_at END, updated_at = NOW()
             WHERE id = ?',
            [(int)$_POST['organization_id'], (string)$_POST['role'], (string)$_POST['status'], (string)$_POST['status'], (int)$_POST['user_id']]
        );
        AuditLogger::log('user_updated', ['user_id' => (int)$_POST['user_id']]);
        Session::flash('success', '利用者を更新しました。');
        redirect_route('users');
    }
    render('users', [
        'title' => '利用者管理',
        'active' => 'users',
        'users' => Database::fetchAll(
            'SELECT u.*, o.name AS organization_name
             FROM users u
             LEFT JOIN organizations o ON o.id = u.organization_id
             ORDER BY u.id DESC'
        ),
        'organizations' => OrganizationService::all(),
    ]);
}

function handle_audit(): void
{
    Auth::requireRole(['system_admin']);
    render('audit', ['title' => '監査ログ', 'active' => 'audit', 'rows' => Database::fetchAll('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 300')]);
}

function handle_settings(): void
{
    Auth::requireRole(['system_admin']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? 'openai');
        if ($action === 'mail_settings') {
            MailSettingsService::update($_POST);
            AuditLogger::log('mail_settings_updated', ['bounce_base_email' => $_POST['bounce_base_email'] ?? '']);
            Session::flash('success', 'メール設定を保存しました。');
            redirect_route('settings');
        }

        if ($action === 'google_settings') {
            GoogleAuthService::updateSettings($_POST);
            AuditLogger::log('google_settings_updated', ['allowed_domain' => $_POST['google_allowed_domain'] ?? '']);
            Session::flash('success', 'Googleログイン設定を保存しました。');
            redirect_route('settings');
        }

        $model = trim((string)($_POST['openai_model'] ?? ''));
        if ($model !== '') {
            SettingsService::set('openai_model', OpenAiService::normalizeModel($model));
        }
        $apiKey = trim((string)($_POST['openai_api_key'] ?? ''));
        if ($apiKey !== '') {
            SettingsService::setSecret('openai_api_key', $apiKey);
        }
        AuditLogger::log('openai_settings_updated', ['openai_model' => $model]);
        Session::flash('success', 'OpenAI API設定を保存しました。');
        redirect_route('settings');
    }

    $mailSettings = MailSettingsService::formValues();
    $googleSettings = GoogleAuthService::settings();
    render('settings', [
        'title' => 'システム設定',
        'active' => 'settings',
        'mailSettings' => $mailSettings,
        'googleSettings' => $googleSettings,
        'openaiModel' => OpenAiService::normalizeModel(SettingsService::get('openai_model', (string)Config::get('openai.model', 'gpt-5.6-terra')) ?: 'gpt-5.6-terra'),
        'openaiModelOptions' => OpenAiService::modelOptions(),
        'openaiKeySet' => SettingsService::isSecretSet('openai_api_key', (string)Config::get('openai.api_key', '')),
    ]);
}

function template_snapshot(array $template, array $versions, string $key): array
{
    if ($key === 'current') {
        return [
            'id' => 'current',
            'label' => '現在版',
            'subject' => (string)$template['subject'],
            'body_text' => (string)$template['body_text'],
            'body_html' => (string)($template['body_html'] ?? ''),
        ];
    }

    foreach ($versions as $version) {
        if ((string)$version['id'] === $key) {
            return [
                'id' => (string)$version['id'],
                'label' => '版 #' . $version['id'] . ' / ' . $version['created_at'],
                'subject' => (string)$version['subject'],
                'body_text' => (string)$version['body_text'],
                'body_html' => (string)($version['body_html'] ?? ''),
            ];
        }
    }

    return template_snapshot($template, $versions, 'current');
}

function sanitize_html_email(string $html): string
{
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? '';
    $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';
    return $html;
}

function normalize_datetime(string $value): string
{
    $value = str_replace('T', ' ', trim($value));
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
        return $value . ':00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
        return $value;
    }
    return date('Y-m-d H:i:s');
}

function route_roles(): array
{
    $all = ['system_admin', 'delivery_admin', 'sender', 'editor', 'viewer'];
    return [
        'dashboard' => $all,
        'logout' => $all,
        'recipients' => ['system_admin', 'delivery_admin'],
        'import' => ['system_admin', 'delivery_admin'],
        'senders' => ['system_admin', 'delivery_admin'],
        'dns_checks' => ['system_admin', 'delivery_admin'],
        'templates' => ['system_admin', 'delivery_admin', 'editor'],
        'template_edit' => ['system_admin', 'delivery_admin', 'editor'],
        'template_compare' => ['system_admin', 'delivery_admin', 'editor'],
        'template_delete' => ['system_admin'],
        'ai' => ['system_admin', 'delivery_admin', 'editor'],
        'test_send' => ['system_admin', 'delivery_admin', 'editor'],
        'campaigns' => ['system_admin', 'delivery_admin', 'sender'],
        'queue_campaign' => ['system_admin', 'delivery_admin'],
        'queue' => ['system_admin', 'delivery_admin', 'sender'],
        'unsubscribes' => ['system_admin', 'delivery_admin'],
        'bounces' => ['system_admin', 'delivery_admin'],
        'organizations' => ['system_admin'],
        'users' => ['system_admin'],
        'settings' => ['system_admin'],
        'audit' => ['system_admin'],
    ];
}

function route_allowed_for_role(string $route, string $role): bool
{
    $roles = route_roles()[$route] ?? [];
    return in_array($role, $roles, true);
}

function route_allowed_for_user(string $route): bool
{
    $user = current_user();
    return $user !== null && route_allowed_for_role($route, (string)($user['role'] ?? ''));
}
