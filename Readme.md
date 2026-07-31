# Cool Mail List

Cool Mail List は、PHP + MariaDB で構築する独自メール配信管理システムです。Laravel と Composer は使わず、PHPMailer を同梱して SMTP 送信を行います。

## 現在の実装範囲

- メールアドレス/パスワード認証
- 管理者承認前提の利用者登録
- ロール別メニュー表示とURL直アクセス制限
- Bootstrap レスポンシブ管理画面
- 宛先の登録、検索、ステータス管理
- CSV/TSV/テキストの宛先インポート
- 送信者アドレスと SMTP 設定の固定紐付け
- SMTP パスワードの AES-256-GCM 暗号化保存
- テキスト/HTMLメールテンプレート
- system_admin 限定のテンプレート削除
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
- Google Identity Services の ID トークン検証
- Googleログインによる既存承認済みユーザー連携
- Googleログイン新規ユーザーの承認待ち登録
- OpenAI API キーの暗号化保存
- AI 文面提案とテンプレート採用
- DNS 診断の詳細表示
- 公開フォームとダブルオプトイン
- テンプレート版保存と差分比較
- 複数組織対応
- 監査ログ

## 今後の拡張候補

- 組織ごとの詳細な権限分離
- キャンペーン承認ワークフロー
- 配信レポート、クリック計測、ABテスト
- Google OAuth の追加スコープ連携

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
GOOGLE_CLIENT_ID=
GOOGLE_ALLOWED_DOMAIN=
OPENAI_MODEL=gpt-5.6-terra
```

DB スキーマを適用します。

```bash
mysql -u mailerdb -p mailerdb < database/schema.sql
```

既存DBへ第2段階機能を追加する場合は、移行SQLを適用します。

```bash
mysql -u mailerdb -p mailerdb < database/migrations/2026_07_31_remaining_features.sql
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

## Google ログイン

システム設定画面で `Google Client ID` を保存すると、ログイン画面に Sign in with Google ボタンが表示されます。サーバ側では Google の公開証明書で ID トークン署名、`aud`、`iss`、`exp`、`sub`、`email_verified` を検証します。

`許可Workspaceドメイン(任意)` を設定した場合は、メールアドレスのドメインではなく ID トークンの `hd` クレームで制限します。既存の承認済みユーザーはメールアドレス一致でGoogleアカウントを連携し、未登録ユーザーは `pending_approval` として登録します。

## ダブルオプトイン

公開登録フォームは次のURLで利用できます。

```text
https://mail.example.com/index.php?r=subscribe
https://mail.example.com/index.php?r=subscribe&org=default
```

登録時は `pending_optin` の宛先を作成し、システムSMTPから7日有効の確認URLを送信します。確認URLを開くと宛先ステータスが `active` になります。

## DNS 診断

「DNS診断」画面で送信者を選択すると、FromドメインとSMTPホストに対して以下を確認し、`sender_domain_checks` に詳細JSON付きで保存します。

- MX
- SPF
- DKIM
- DMARC
- PTR

## テンプレート版管理

テンプレート作成時と更新前に `mail_template_versions` へ保存版を残します。「差分」画面では保存版同士、または保存版と現在版を件名、テキスト本文、HTML本文ごとに比較できます。

保存済みテンプレートは `system_admin` のみ削除できます。キャンペーンで使用中のテンプレートは、参照整合性を守るため削除できません。

## ロール別表示

ログイン後の機能メニューは、利用者ロールで閲覧または操作できる機能だけを表示します。URLを直接指定した場合も同じ権限判定を行い、権限外の機能は403で拒否します。

## 複数組織

既定組織 `Default` を自動作成し、ユーザー、宛先、SMTPアカウント、送信者、テンプレート、AI生成履歴、キャンペーン、キューを `organization_id` で分離します。`system_admin` は「組織管理」で組織を追加し、「利用者管理」で所属組織を割り当てます。

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
- OpenAI Models: https://developers.openai.com/api/docs/models
- Google ID token verification: https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
- Google Sign in JavaScript API: https://developers.google.com/identity/gsi/web/reference/js-reference
- One-Click List-Unsubscribe, RFC 8058: https://datatracker.ietf.org/doc/html/rfc8058
- List-Unsubscribe Header, RFC 2369: https://www.ietf.org/rfc/rfc2369.txt
