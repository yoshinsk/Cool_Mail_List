<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\organizations.php
 * 複数組織の作成、状態確認、公開登録URLを表示する管理画面。
 */
?>
<section class="panel">
    <div class="panel-title">組織追加</div>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-5"><input class="form-control" name="name" placeholder="組織名" required></div>
        <div class="col-md-5"><input class="form-control" name="slug" placeholder="public-slug" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">作成</button></div>
    </form>
</section>

<section class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>組織名</th><th>スラッグ</th><th>状態</th><th>公開登録URL</th></tr></thead>
            <tbody>
            <?php foreach ($organizations as $organization): ?>
                <tr>
                    <td><?= h((string)$organization['id']) ?></td>
                    <td><?= h($organization['name']) ?></td>
                    <td><?= h($organization['slug']) ?></td>
                    <td><span class="status-badge"><?= (int)$organization['is_active'] === 1 ? 'active' : 'disabled' ?></span></td>
                    <td><a href="<?= h(route_url('subscribe', ['org' => $organization['slug']])) ?>" target="_blank" rel="noopener"><?= h(route_url('subscribe', ['org' => $organization['slug']])) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
