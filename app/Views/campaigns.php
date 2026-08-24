<?php
/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Views\campaigns.php
 * キャンペーン作成、予約日時設定、宛先キュー生成画面。
 */
$canQueueCampaign = route_allowed_for_user('queue_campaign');
?>
<section class="panel campaign-guide-entry">
    <div class="campaign-guide-entry-text">
        <div>
            <div class="panel-title">送信手順ガイド</div>
            <p class="section-help mb-0">詳しい手順は別ウィンドウで確認できます。作成フォームでは、入力や作成時に必要な補足だけをバルーンで表示します。</p>
        </div>
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#campaignGuideModal">
            ガイドを別ウィンドウで開く
        </button>
    </div>
</section>

<div class="modal fade campaign-guide-modal" id="campaignGuideModal" tabindex="-1" aria-labelledby="campaignGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="campaignGuideModalLabel">送信手順ガイド</h2>
                    <p class="modal-guide-intro mb-0">本配信は「キャンペーン作成」だけでは始まりません。送信者、テンプレート、宛先を確認した上で「キュー生成」を実行し、予約日時以降にcronが順番に送信します。</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <div class="guide-steps">
                    <div class="guide-step">
                        <span class="guide-number">1</span>
                        <div>
                            <h3>宛先を準備する</h3>
                            <p><a href="<?= h(route_url('recipients')) ?>">宛先管理</a> または <a href="<?= h(route_url('import')) ?>">インポート</a> で配信先を登録します。配信対象になるのは状態が <strong>active</strong> の宛先だけです。購読停止、バウンス停止、確認待ちの宛先には送りません。</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-number">2</span>
                        <div>
                            <h3>送信者/SMTPを確認する</h3>
                            <p><a href="<?= h(route_url('senders')) ?>">送信者/SMTP管理</a> で送信者を登録し、<strong>SMTPチェック</strong> が成功することを確認します。一般的には587番はTLS、465番はSSLです。続けて <a href="<?= h(route_url('dns_checks')) ?>">DNS診断</a> でSPF、DKIM、DMARC、PTRを確認します。</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-number">3</span>
                        <div>
                            <h3>テンプレートを用意する</h3>
                            <p><a href="<?= h(route_url('templates')) ?>">テンプレート管理</a> で件名と本文を作成します。本文には必ず <code>{{unsubscribe_url}}</code> を入れてください。差し込みは <code>{{name}}</code>、<code>{{company}}</code>、<code>{{email}}</code> が使えます。本配信前にテスト送信で表示を確認します。</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-number">4</span>
                        <div>
                            <h3>キャンペーンを作成する</h3>
                            <p>この画面でキャンペーン名、送信者、テンプレート、予約日時を選んで「作成」を押します。この時点ではまだ宛先別キューは作成されず、メールも送信されません。件名上書きは今回だけ件名を変えたい場合に使います。</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-number">5</span>
                        <div>
                            <h3>キュー生成で送信対象を確定する</h3>
                            <p>キャンペーン一覧の「キュー生成」を押すと、その時点のactive宛先から宛先別の送信キューを作ります。キュー数が0のキャンペーンだけ生成できます。送信対象を変えたい場合は、キュー生成前に宛先やテンプレートを確認してください。</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-number">6</span>
                        <div>
                            <h3>送信状況を確認する</h3>
                            <p>予約日時を過ぎるとcronが毎分 <code>send_queue.php</code> を実行し、設定件数ずつ送信します。進行状況は <a href="<?= h(route_url('queue')) ?>">配信キュー</a>、到達後の停止は <a href="<?= h(route_url('bounces')) ?>">バウンス管理</a> と <a href="<?= h(route_url('unsubscribes')) ?>">購読停止一覧</a> で確認します。</p>
                        </div>
                    </div>
                </div>
                <div class="guide-checklist">
                    <div><strong>送信前チェック</strong><span>SMTPチェック成功、DNS診断確認、テスト送信確認、本文内の購読停止URL確認。</span></div>
                    <div><strong>すぐ送る場合</strong><span>予約日時を現在時刻のまま作成し、キュー生成後、次回cron実行から送信されます。</span></div>
                    <div><strong>送信を止めたい場合</strong><span>キュー生成前なら作成し直し、キュー生成後は配信キューとキャンペーン状態を確認してください。</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<section class="panel">
    <div class="panel-title-row">
        <div>
            <div class="panel-title">キャンペーン作成</div>
            <p class="section-help mb-0">送信者、テンプレート、予約日時を組み合わせて配信予定を作ります。迷った項目は入力欄を選ぶと補足が表示されます。</p>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#campaignGuideModal">手順を見る</button>
    </div>
    <form method="post" class="row g-3" id="campaignCreateForm">
        <?= Csrf::field() ?>
        <div class="col-md-4">
            <label class="form-label" for="campaign_name">キャンペーン名</label>
            <input class="form-control" id="campaign_name" name="name" placeholder="例: 8月ニュースレター" required data-guide-title="キャンペーン名" data-guide-message="管理画面で見分けるための名前です。受信者には表示されません。あとから配信対象を見返しやすい名前にしてください。">
            <div class="form-help">管理画面で見分けるための名前です。受信者には表示されません。</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="campaign_sender">送信者</label>
            <select class="form-select" id="campaign_sender" name="sender_identity_id" required data-guide-title="送信者/SMTP" data-guide-message="Fromに使う送信者です。SMTPチェックが成功し、DNS診断でSPF、DKIM、DMARC、PTRを確認済みの送信者を選んでください。">
                <option value="">送信者</option>
                <?php foreach ($senders as $sender): ?>
                    <option value="<?= h((string)$sender['id']) ?>"><?= h($sender['from_email']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-help">Fromに使う送信者です。先にテスト送信とDNS診断を済ませてください。</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="campaign_template">テンプレート</label>
            <select class="form-select" id="campaign_template" name="template_id" required data-guide-title="テンプレート" data-guide-message="本文に {{unsubscribe_url}} が入っているテンプレートを選びます。本配信前にテスト送信で差し込みと表示を確認してください。">
                <option value="">テンプレート</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?= h((string)$template['id']) ?>"><?= h($template['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-help">本文に <code>{{unsubscribe_url}}</code> がないテンプレートは配信作成できません。</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="subject_override">件名上書き</label>
            <input class="form-control" id="subject_override" name="subject_override" placeholder="任意" data-guide-title="件名上書き" data-guide-message="空欄ならテンプレートの件名を使います。今回の配信だけ件名を変えたい場合に入力してください。">
            <div class="form-help">空欄ならテンプレートの件名を使います。今回だけ件名を変えたい時に入力します。</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="scheduled_at">予約日時</label>
            <input class="form-control" id="scheduled_at" name="scheduled_at" type="datetime-local" value="<?= h(date('Y-m-d\TH:i')) ?>" required data-guide-title="予約日時" data-guide-message="この日時以降にcronが順番に送信します。すぐ送る場合は現在時刻のままで作成し、一覧からキュー生成してください。">
            <div class="form-help">この日時以降にcronが順番に送信します。すぐ送る場合は現在時刻のままで構いません。</div>
        </div>
        <div class="col-md-2 campaign-submit-column"><button class="btn btn-primary w-100" type="submit">作成</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-title">キャンペーン一覧</div>
    <p class="section-help">キュー生成後は宛先ごとの送信予定が作成されます。キュー数が0の間だけキュー生成できます。</p>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>名称</th><th>From</th><th>テンプレート</th><th>状態</th><th>予約日時</th><th>キュー</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><?= h((string)$campaign['id']) ?></td>
                    <td><?= h($campaign['name']) ?></td>
                    <td><?= h($campaign['from_email']) ?></td>
                    <td><?= h($campaign['template_name']) ?></td>
                    <td><span class="status-badge"><?= h($campaign['status']) ?></span></td>
                    <td><?= h($campaign['scheduled_at']) ?></td>
                    <td><?= h((string)$campaign['queue_count']) ?></td>
                    <td>
                        <?php if ($canQueueCampaign && (int)$campaign['queue_count'] === 0): ?>
                            <form method="post" action="<?= h(route_url('queue_campaign')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= h((string)$campaign['id']) ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">キュー生成</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
