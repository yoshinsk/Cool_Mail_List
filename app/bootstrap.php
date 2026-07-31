<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\bootstrap.php
 * アプリ共通の初期化、環境変数読込、基本ヘルパー、クラス読込を行う。
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

require APP_PATH . '/Core/Config.php';
require APP_PATH . '/Core/Database.php';
require APP_PATH . '/Core/Session.php';
require APP_PATH . '/Core/Csrf.php';
require APP_PATH . '/Core/Auth.php';
require APP_PATH . '/Services/AuditLogger.php';
require APP_PATH . '/Services/CryptoService.php';
require APP_PATH . '/Services/ImportService.php';
require APP_PATH . '/Services/MailerService.php';
require APP_PATH . '/Services/QueueService.php';
require APP_PATH . '/Services/BounceParser.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'PHPMailer\\PHPMailer\\';
    if (str_starts_with($class, $prefix)) {
        $name = substr($class, strlen($prefix));
        $path = APP_PATH . '/Vendor/PHPMailer/' . $name . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

Config::load(load_env(BASE_PATH . '/.env'));
date_default_timezone_set(Config::get('app.timezone', 'Asia/Tokyo'));

error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.env') === 'production' ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

function load_env(string $path): array
{
    $env = [];
    if (!is_file($path)) {
        return $env;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $env[trim($key)] = $value;
    }

    return $env;
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function route_url(string $route, array $params = []): string
{
    $params = array_merge(['r' => $route], $params);
    return base_url('index.php?' . http_build_query($params));
}

function base_url(string $path = ''): string
{
    $base = rtrim((string)Config::get('app.url', ''), '/');
    $path = ltrim($path, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $path === '' ? $base : $base . '/' . $path;
}

function asset_url(string $path): string
{
    return base_url('public/assets/' . ltrim($path, '/'));
}

function redirect_to(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}

function redirect_route(string $route, array $params = []): void
{
    redirect_to(route_url($route, $params));
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/Views/' . $view . '.php';
    $content = ob_get_clean();
    require APP_PATH . '/Views/layout.php';
}

function current_user(): ?array
{
    return Auth::user();
}
