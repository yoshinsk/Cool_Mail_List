<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\public\index.php
 * Cool Mail List のWebフロントコントローラ。認証、CSRF、各管理画面の処理を集約する。
 */

require dirname(__DIR__) . '/app/bootstrap.php';

Session::start();

$route = (string)($_GET['r'] ?? 'dashboard');
$publicRoutes = ['login', 'register', 'forgot_password', 'reset_password', 'unsubscribe'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route !== 'unsubscribe') {
    Csrf::requireValid();
}

if (!Auth::check() && !in_array($route, $publicRoutes, true)) {
    redirect_route('login');
}

if (Auth::check() && in_array($route, ['login', 'register'], true)) {
    redirect_route('dashboard');
}

try {
    match ($route) {
        'login' => handle_login(),
        'register' => handle_register(),
        'forgot_password' => handle_forgot_password(),
        'reset_password' => handle_reset_password(),
        'logout' => handle_logout(),
        'recipients' => handle_recipients(),
        'import' => handle_import(),
        'senders' => handle_senders(),
        'templates' => handle_templates(),
        'ai' => handle_ai(),
        'test_send' => handle_test_send(),
        'campaigns' => handle_campaigns(),
        'queue_campaign' => handle_queue_campaign(),
        'queue' => handle_queue(),
        'unsubscribes' => handle_unsubscribes(),
        'unsubscribe' => handle_unsubscribe(),
        'bounces' => handle_bounces(),
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
    render('auth/login', ['title' => 'ログイン', 'active' => 'login']);
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
                'INSERT INTO users (email, password_hash, role, status, email_verified_at, created_at, updated_at)
                 VALUES (?, ?, "viewer", "pending_approval", NOW(), NOW(), NOW())',
                [$email, password_hash($password, PASSWORD_DEFAULT)]
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
    $stats = [
        'recipients' => Database::fetch('SELECT COUNT(*) AS c FROM recipients')['c'] ?? 0,
        'active' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE status = "active"')['c'] ?? 0,
        'queued' => Database::fetch('SELECT COUNT(*) AS c FROM mail_queue WHERE status IN ("pending", "temporary_failed")')['c'] ?? 0,
        'sent' => Database::fetch('SELECT COUNT(*) AS c FROM mail_queue WHERE status = "sent"')['c'] ?? 0,
        'bounced' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE status IN ("hard_bounced", "soft_bounced")')['c'] ?? 0,
        'unsubscribed' => Database::fetch('SELECT COUNT(*) AS c FROM recipients WHERE status = "unsubscribed"')['c'] ?? 0,
    ];
    $recentLogs = Database::fetchAll('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 8');
    render('dashboard', ['title' => 'ダッシュボード', 'active' => 'dashboard', 'stats' => $stats, 'recentLogs' => $recentLogs]);
}

function handle_recipients(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::requireRole(['system_admin', 'delivery_admin']);
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'メールアドレス形式が不正です。');
        } else {
            Database::execute(
                'INSERT INTO recipients (email, name, company, tags, source, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, "manual", ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), company = VALUES(company), tags = VALUES(tags), status = VALUES(status), updated_at = NOW()',
                [$email, trim((string)$_POST['name']), trim((string)$_POST['company']), trim((string)$_POST['tags']), (string)$_POST['status']]
            );
            AuditLogger::log('recipient_saved', ['email' => $email]);
            Session::flash('success', '宛先を保存しました。');
            redirect_route('recipients');
        }
    }

    $where = [];
    $params = [];
    if (!empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = (string)$_GET['status'];
    }
    if (!empty($_GET['q'])) {
        $where[] = '(email LIKE ? OR name LIKE ? OR company LIKE ? OR tags LIKE ?)';
        $q = '%' . (string)$_GET['q'] . '%';
        array_push($params, $q, $q, $q, $q);
    }
    $sql = 'SELECT * FROM recipients' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 200';
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $passwordCipher = $_POST['smtp_password'] !== '' ? CryptoService::encrypt((string)$_POST['smtp_password']) : null;
        Database::execute(
            'INSERT INTO smtp_accounts
                (name, smtp_host, smtp_port, encryption, auth_username, auth_password_ciphertext, per_minute_limit, daily_limit, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
            [
                trim((string)$_POST['account_name']),
                trim((string)$_POST['smtp_host']),
                (int)$_POST['smtp_port'],
                (string)$_POST['encryption'],
                trim((string)$_POST['auth_username']),
                $passwordCipher,
                max(1, (int)$_POST['per_minute_limit']),
                max(1, (int)$_POST['daily_limit']),
            ]
        );
        $smtpId = Database::lastInsertId();
        Database::execute(
            'INSERT INTO sender_identities
                (smtp_account_id, from_name, from_email, reply_to, dkim_policy, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())',
            [
                $smtpId,
                trim((string)$_POST['from_name']),
                mb_strtolower(trim((string)$_POST['from_email'])),
                mb_strtolower(trim((string)$_POST['reply_to'])),
                (string)$_POST['dkim_policy'],
            ]
        );
        AuditLogger::log('sender_identity_created', ['from_email' => $_POST['from_email']]);
        Session::flash('success', '送信者/SMTP設定を保存しました。');
        redirect_route('senders');
    }

    $senders = Database::fetchAll(
        'SELECT si.*, sa.name AS account_name, sa.smtp_host, sa.smtp_port, sa.encryption, sa.per_minute_limit, sa.daily_limit
         FROM sender_identities si
         JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
         ORDER BY si.id DESC'
    );
    render('senders', ['title' => '送信者/SMTP管理', 'active' => 'senders', 'senders' => $senders]);
}

function handle_templates(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::requireRole(['system_admin', 'delivery_admin', 'editor']);
        Database::execute(
            'INSERT INTO mail_templates (name, subject, body_text, body_html, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [
                trim((string)$_POST['name']),
                trim((string)$_POST['subject']),
                (string)$_POST['body_text'],
                sanitize_html_email((string)($_POST['body_html'] ?? '')),
                (int)current_user()['id'],
            ]
        );
        AuditLogger::log('template_created', ['name' => $_POST['name']]);
        Session::flash('success', 'テンプレートを保存しました。');
        redirect_route('templates');
    }

    render('templates', [
        'title' => 'テンプレート管理',
        'active' => 'templates',
        'templates' => Database::fetchAll('SELECT * FROM mail_templates ORDER BY id DESC LIMIT 100'),
        'senders' => Database::fetchAll('SELECT * FROM sender_identities WHERE is_active = 1 ORDER BY id DESC'),
    ]);
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::requireRole(['system_admin', 'delivery_admin', 'sender']);
        $template = Database::fetch('SELECT * FROM mail_templates WHERE id = ?', [(int)$_POST['template_id']]);
        $body = ($template['body_text'] ?? '') . "\n" . ($template['body_html'] ?? '');
        if (!str_contains($body, '{{unsubscribe_url}}')) {
            Session::flash('error', 'テンプレートに {{unsubscribe_url}} が含まれていないため作成できません。');
            redirect_route('campaigns');
        }
        Database::execute(
            'INSERT INTO campaigns (name, sender_identity_id, template_id, subject_override, scheduled_at, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, "draft", ?, NOW(), NOW())',
            [
                trim((string)$_POST['name']),
                (int)$_POST['sender_identity_id'],
                (int)$_POST['template_id'],
                trim((string)$_POST['subject_override']) ?: null,
                normalize_datetime((string)$_POST['scheduled_at']),
                (int)current_user()['id'],
            ]
        );
        AuditLogger::log('campaign_created', ['name' => $_POST['name']]);
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
             ORDER BY c.id DESC'
        ),
        'senders' => Database::fetchAll('SELECT * FROM sender_identities WHERE is_active = 1 ORDER BY id DESC'),
        'templates' => Database::fetchAll('SELECT * FROM mail_templates ORDER BY id DESC'),
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
    $rows = Database::fetchAll(
        'SELECT mq.*, c.name AS campaign_name, r.email AS recipient_email
         FROM mail_queue mq
         JOIN campaigns c ON c.id = mq.campaign_id
         JOIN recipients r ON r.id = mq.recipient_id
         ORDER BY mq.id DESC LIMIT 200'
    );
    render('queue', ['title' => '配信キュー', 'active' => 'queue', 'rows' => $rows]);
}

function handle_unsubscribes(): void
{
    render('unsubscribes', [
        'title' => '購読停止一覧',
        'active' => 'unsubscribes',
        'rows' => Database::fetchAll(
            'SELECT u.*, r.email FROM unsubscribes u JOIN recipients r ON r.id = u.recipient_id ORDER BY u.id DESC LIMIT 200'
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

function handle_bounces(): void
{
    render('bounces', [
        'title' => 'バウンス管理',
        'active' => 'bounces',
        'rows' => Database::fetchAll('SELECT * FROM bounce_messages ORDER BY id DESC LIMIT 200'),
    ]);
}

function handle_users(): void
{
    Auth::requireRole(['system_admin']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Database::execute(
            'UPDATE users SET role = ?, status = ?, approved_at = CASE WHEN ? = "active" THEN COALESCE(approved_at, NOW()) ELSE approved_at END, updated_at = NOW() WHERE id = ?',
            [(string)$_POST['role'], (string)$_POST['status'], (string)$_POST['status'], (int)$_POST['user_id']]
        );
        AuditLogger::log('user_updated', ['user_id' => (int)$_POST['user_id']]);
        Session::flash('success', '利用者を更新しました。');
        redirect_route('users');
    }
    render('users', ['title' => '利用者管理', 'active' => 'users', 'users' => Database::fetchAll('SELECT * FROM users ORDER BY id DESC')]);
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
        $model = trim((string)($_POST['openai_model'] ?? ''));
        if ($model !== '') {
            SettingsService::set('openai_model', OpenAiService::normalizeModel($model));
        }
        $apiKey = trim((string)($_POST['openai_api_key'] ?? ''));
        if ($apiKey !== '') {
            SettingsService::setSecret('openai_api_key', $apiKey);
        }
        AuditLogger::log('settings_updated', ['openai_model' => $model]);
        Session::flash('success', 'システム設定を保存しました。');
        redirect_route('settings');
    }

    render('settings', [
        'title' => 'システム設定',
        'active' => 'settings',
        'openaiModel' => OpenAiService::normalizeModel(SettingsService::get('openai_model', (string)Config::get('openai.model', 'gpt-5.6-terra')) ?: 'gpt-5.6-terra'),
        'openaiModelOptions' => OpenAiService::modelOptions(),
        'openaiKeySet' => SettingsService::isSecretSet('openai_api_key', (string)Config::get('openai.api_key', '')),
    ]);
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
