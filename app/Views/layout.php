<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\layout.php
 * Bootstrapベースの共通レイアウト。PC左メニューとモバイルメニューを提供する。
 */

$user = current_user();
$nav = [
    ['dashboard', 'ダッシュボード'],
    ['recipients', '宛先管理'],
    ['import', 'インポート'],
    ['senders', '送信者/SMTP管理'],
    ['dns_checks', 'DNS診断'],
    ['templates', 'テンプレート管理'],
    ['ai', 'AI文面提案'],
    ['campaigns', 'メール作成/配信予約'],
    ['queue', '配信キュー'],
    ['unsubscribes', '購読停止一覧'],
    ['bounces', 'バウンス管理'],
    ['organizations', '組織管理'],
    ['users', '利用者管理'],
    ['settings', 'システム設定'],
    ['audit', '監査ログ'],
];
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? Config::get('app.name')) ?> - <?= h(Config::get('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(asset_url('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom d-lg-none">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="<?= h(route_url('dashboard')) ?>">Cool Mail List</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-expanded="false" aria-label="メニュー">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mobileNav">
            <div class="navbar-nav">
                <?php foreach ($nav as [$key, $label]): ?>
                    <a class="nav-link <?= ($active ?? '') === $key ? 'active' : '' ?>" href="<?= h(route_url($key)) ?>"><?= h($label) ?></a>
                <?php endforeach; ?>
                <?php if ($user): ?>
                    <a class="nav-link" href="<?= h(route_url('logout')) ?>">ログアウト</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar d-none d-lg-flex">
            <div class="brand">Cool Mail List</div>
            <div class="userbox">
                <div class="small text-muted">ログイン中</div>
                <div class="fw-semibold text-truncate"><?= h($user['email'] ?? '') ?></div>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($nav as [$key, $label]): ?>
                    <a class="<?= ($active ?? '') === $key ? 'active' : '' ?>" href="<?= h(route_url($key)) ?>"><?= h($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="logout" href="<?= h(route_url('logout')) ?>">ログアウト</a>
        </aside>
    <?php endif; ?>
    <main class="main-content <?= $user ? '' : 'auth-content' ?>">
        <?php if ($message = Session::flash('success')): ?>
            <div class="alert alert-success"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($message = Session::flash('error')): ?>
            <div class="alert alert-danger"><?= h($message) ?></div>
        <?php endif; ?>
        <header class="page-header">
            <h1><?= h($title ?? '') ?></h1>
        </header>
        <?= $content ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= h(asset_url('js/app.js')) ?>"></script>
</body>
</html>
