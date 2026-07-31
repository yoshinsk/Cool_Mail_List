# Cool Mail List

Cool Mail List は、PHP + MariaDB で構築する独自メール配信管理システムです。Laravel と Composer は使わず、PHPMailer を同梱して SMTP 送信を行います。

## 現在の実装範囲

- メールアドレス/パスワード認証
- 管理者承認前提の利用者登録
- ロール管理の基礎
- Bootstrap レスポンシブ管理画面
- 宛先の登録、検索、ステータス管理
- CSV/TSV/テキストの宛先インポート
- 送信者アドレスと SMTP 設定の固定紐付け
- SMTP パスワードの AES-256-GCM 暗号化保存
- テキスト/HTMLメールテンプレート
- PHPMailer によるテスト送信
- キャンペーン作成
- 宛先別キュー生成
- cron による低速キュー配信
- VERP 形式 Return-Path
- List-Unsubscribe / One-Click List-Unsubscribe ヘッダ
- ログイン不要の購読停止
- DSN バウンス解析の基礎
- IMAP によるバウンスメール定期取得
- パスワード再設定メール
- OpenAI API キーの暗号化保存
- AI 文面提案とテンプレート採用
- 監査ログ

## 未実装または第2段階

- Google Identity Services の ID トークン検証
- DNS 診断の詳細表示
- ダブルオプトイン
- テンプレート差分比較
- 複数組織対応

## 技術構成

- PHP 8.3 系推奨
- MariaDB 10.5 系以上
- Bootstrap 5
- PHPMailer 6.10.0
- Composer 不使用

PHPMailer は公式 GitHub リリース `v6.10.0` から以下を `app/Vendor/PHPMailer/` に同梱しています。

- `PHPMailer.php`
- `SMTP.php`
- `Exception.php`
- `LICENSE`

## ディレクトリ構成

```text
/
  index.php
  unsubscribe.php
  .env.example
  app/
    Core/
    Services/
    Views/
    Vendor/PHPMailer/
  public/
    index.php
    assets/
  cron/
    send_queue.php
    process_bounce_pipe.php
    fetch_bounces.php
    create_admin.php
  database/
    schema.sql
  storage/
```

## セットアップ

`.env.example` を `.env` にコピーし、環境値を設定します。

```env
APP_URL=https://mail.example.com
APP_KEY=32文字以上のランダム文字列
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mailerdb
DB_USER=mailerdb
DB_PASS=********
QUEUE_BATCH_LIMIT=5
BOUNCE_DOMAIN=example.com
BOUNCE_BASE_EMAIL=bounce@example.com
SYSTEM_MAIL_FROM=no-reply@example.com
SYSTEM_SMTP_HOST=smtp.example.com
SYSTEM_SMTP_PORT=587
SYSTEM_SMTP_ENCRYPTION=tls
SYSTEM_SMTP_USER=no-reply@example.com
SYSTEM_SMTP_PASS=********
BOUNCE_IMAP_HOST=imap.example.com
BOUNCE_IMAP_PORT=993
BOUNCE_IMAP_ENCRYPTION=ssl
BOUNCE_IMAP_USER=bounce@example.com
BOUNCE_IMAP_PASS=********
OPENAI_MODEL=gpt-5.6-terra
```

DB スキーマを適用します。

```bash
mysql -u mailerdb -p mailerdb < database/schema.sql
```

初期管理者を作成します。

```bash
/opt/plesk/php/8.3/bin/php cron/create_admin.php admin@example.com 'strong-password-12+'
```

## cron

Plesk 環境では Web と CLI の PHP バージョン差を避けるため、PHP 8.3 の実行ファイルを明示します。

```cron
* * * * * /opt/plesk/php/8.3/bin/php /var/www/vhosts/mxnew.fieltrust.jp/httpdocs/cron/send_queue.php >/dev/null 2>&1
*/5 * * * * /opt/plesk/php/8.3/bin/php /var/www/vhosts/mxnew.fieltrust.jp/httpdocs/cron/fetch_bounces.php >/dev/null 2>&1
```

1回の実行で送信する件数は `.env` の `QUEUE_BATCH_LIMIT` で制御します。初期値は5件です。

バウンス取得は管理画面の「システム設定 > メール設定」で保存したIMAP設定を優先し、未設定時は `.env` の `BOUNCE_IMAP_*` を使います。既定では `UNSEEN` のメールだけを処理して既読化します。

Return-Path は送信者ごとに分けず、常に「固定バウンス基準アドレス」を基準にします。例として `bounce+rp_xxxxx@example.com` の形式で生成します。Plesk/Postfix 側で plus addressing が有効な環境では、これらは `bounce@example.com` の同一メールボックスに配送されます。

## メール設定

管理画面の「システム設定 > メール設定」で以下を保存できます。DB保存値がある場合は `.env` より優先されます。SMTP/IMAPパスワードは `settings` テーブルに平文保存せず、`APP_KEY` を使って暗号化します。

- 固定バウンス基準アドレス
- パスワード再設定などに使うシステムメールFrom
- システムメール送信用SMTPホスト、ポート、暗号化、ユーザー、パスワード
- バウンス取得用IMAPホスト、ポート、暗号化、ユーザー、パスワード、メールボックス、検索条件

## AI 文面提案

システム設定画面で OpenAI API キーとモデルを保存します。モデルは Responses API 対応候補を特徴付きリストから選択します。API キーは `settings` テーブルに平文保存せず、`APP_KEY` を使って暗号化します。

文面提案画面では、配信目的、対象者、トーン、商品/サービス概要、要点、CTA、文字数目安を入力し、生成結果を `ai_generation_requests` と `ai_generation_results` に保存します。生成結果は直接送信せず、テンプレートとして採用してからキャンペーンで使用します。

Google ログインは現時点では後回しです。

## 公開ディレクトリ配置

この初期構成は、リポジトリ全体を Plesk の公開ディレクトリに配置しても動作するように、`.htaccess` で以下への直接アクセスを拒否します。

- `.env`
- `app/`
- `cron/`
- `database/`
- `storage/`

より厳密な本番構成では、`app/`、`cron/`、`database/`、`storage/` を公開ディレクトリ外へ置き、`public/` のみを公開する構成へ移行してください。

## 配信前の確認事項

- From ドメインと SMTP サーバの整合性
- SPF、DKIM、DMARC の整合性
- `{{unsubscribe_url}}` を本文またはHTML本文に含めること
- SMTP パスワードを `.env` や Git に保存しないこと
- OpenAI API キーとメールボックスパスワードを Git に保存しないこと
- テスト送信完了後に本番キューを作成すること

## 参照

- PHPMailer: https://github.com/PHPMailer/PHPMailer
- PHPMailer 6.10.0: https://github.com/PHPMailer/PHPMailer/releases/tag/v6.10.0
- OpenAI Text generation: https://developers.openai.com/api/docs/guides/text
- One-Click List-Unsubscribe, RFC 8058: https://datatracker.ietf.org/doc/html/rfc8058
- List-Unsubscribe Header, RFC 2369: https://www.ietf.org/rfc/rfc2369.txt
