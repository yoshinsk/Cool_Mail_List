<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\dns_checks.php
 * 送信者ドメインのMX/SPF/DKIM/DMARC/PTR診断と履歴を表示する画面。
 */
?>
<section class="panel">
    <div class="panel-title">診断実行</div>
    <form method="post" class="row g-3">
        <?= Csrf::field() ?>
        <div class="col-md-8">
            <select class="form-select" name="sender_identity_id" required>
                <option value="">送信者を選択</option>
                <?php foreach ($senders as $sender): ?>
                    <option value="<?= h((string)$sender['id']) ?>"><?= h($sender['from_name'] . ' <' . $sender['from_email'] . '>') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><button class="btn btn-primary w-100" type="submit">DNS診断</button></div>
    </form>
</section>

<?php if ($result): ?>
    <section class="panel">
        <div class="panel-title"><?= h($result['domain']) ?> の診断結果</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>項目</th><th>状態</th><th>要約</th><th>レコード</th></tr></thead>
                <tbody>
                <?php foreach ($result['checks'] as $name => $check): ?>
                    <tr>
                        <td><?= h(strtoupper($name)) ?></td>
                        <td><span class="status-badge"><?= h($check['status']) ?></span></td>
                        <td><?= h($check['summary']) ?></td>
                        <td class="text-break">
                            <?php foreach ($check['records'] as $record): ?>
                                <div><?= h($record) ?></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-title">診断履歴</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>From</th><th>MX</th><th>SPF</th><th>DKIM</th><th>DMARC</th><th>PTR</th><th>日時</th></tr></thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= h((string)$row['id']) ?></td>
                    <td><?= h($row['from_email']) ?></td>
                    <td><span class="status-badge"><?= h($row['mx_status']) ?></span></td>
                    <td><span class="status-badge"><?= h($row['spf_status']) ?></span></td>
                    <td><span class="status-badge"><?= h($row['dkim_status']) ?></span></td>
                    <td><span class="status-badge"><?= h($row['dmarc_status']) ?></span></td>
                    <td><span class="status-badge"><?= h($row['ptr_status']) ?></span></td>
                    <td><?= h($row['checked_at']) ?></td>
                </tr>
                <?php if (!empty($row['details_json'])): ?>
                    <?php $details = json_decode((string)$row['details_json'], true) ?: []; ?>
                    <tr class="table-light">
                        <td></td>
                        <td colspan="7" class="small text-break">
                            <?php foreach ($details as $name => $detail): ?>
                                <strong><?= h(strtoupper((string)$name)) ?>:</strong>
                                <?= h((string)($detail['summary'] ?? '')) ?>
                                <?php if (!empty($detail['records'])): ?>
                                    <span class="text-muted"><?= h(implode(' / ', $detail['records'])) ?></span>
                                <?php endif; ?>
                                <br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
