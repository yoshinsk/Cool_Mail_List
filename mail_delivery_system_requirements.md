# 独自メール配信システム 要件定義書

作成日: 2026-07-31  
対象: CodexによるPHP + MariaDBフルスクラッチ開発  
前提: Laravel不使用、Composer不使用、Bootstrap使用

## 1. 目的

acMailerが持つメール配信管理機能を踏襲しつつ、自社で公開しているWebサーバおよびMailサーバを利用して、複数配信者が安全にメール配信できる独自システムを構築する。

本システムは、登録済み宛先へのテンプレートメール配信、低速キュー配信、複数送信者アドレスごとのSMTP使い分け、バウンス自動処理、購読停止、AI文面提案を扱う。

## 2. 技術方針

- サーバサイドは素のPHPで実装する。
- Laravelは使用しない。
- Composerは使用しない。
- DBはMySQLまたはMariaDBを使用する。
- UIはBootstrapを使用する。
- JavaScriptは必要最小限にする。
- cronで配信キューとバウンス処理を実行する。
- メール送信はSMTP接続を基本とする。
- SMTP認証情報、APIキー等の秘密情報はDBに平文保存しない。

## 3. 用語定義

| 用語 | 意味 |
| --- | --- |
| 利用者 | 本システムにログインする人間のアカウント。管理者、配信者、編集者など。 |
| 配信者 | メール配信を作成・実行する利用者。複数存在する可能性がある。 |
| 送信者アドレス | メールのFromに使用するメールアドレス。例: `info@example.com` |
| SMTP設定 | 送信者アドレスに紐付くSMTPホスト、認証ID、TLS設定等。 |
| 宛先 | メールを受信する登録メールアドレス。 |
| キャンペーン | 配信者が作成する1回のメール配信単位。 |
| キュー | 宛先ごとに生成される送信待ちレコード。 |
| バウンス | 不達通知。Return-Pathまたは専用メールボックスで受信する。 |
| VERP | 宛先や配信単位を識別できる一意のReturn-Pathを付ける方式。 |

## 4. 想定規模

- 1日あたり配信数: 約1,000通
- 基本配信速度: 1分あたり5通程度
- 送信者アドレス数: 複数
- SMTPサーバ数: 登録済み送信者アドレス数に応じて複数
- 利用者数: 複数
- DB: MariaDB/MySQL

1分5通で送信した場合、1,000通の配信には約200分、すなわち約3時間20分を要する。

## 5. 利用者登録・認証

### 5.1 通常ユーザー登録

- メールアドレスとパスワードによる利用者登録を実装する。
- 登録後はメール確認または管理者承認を経て有効化する。
- 初期設定では、誰でも即時配信可能にしない。
- パスワードは `password_hash()` によりハッシュ化する。
- パスワード再設定機能を実装する。
- ログイン失敗回数制限を実装する。
- セッションIDはログイン成功時に再生成する。

### 5.2 Googleアカウントでサインイン

- Google Identity Servicesを利用したGoogleアカウントサインインを実装する。
- 認証はGoogleアカウントによるサインイン用途に限定し、Google APIアクセス権限の要求は別扱いとする。
- バックエンドでIDトークンを検証する。
- 検証項目:
  - `iss`
  - `aud`
  - `exp`
  - `sub`
  - `email`
  - `email_verified`
- Googleアカウント連携は `google_sub` を主キー的に扱う。
- 既存メールアドレスとGoogleアカウントを自動連携する場合は、メール確認済みかつ管理者承認済みの条件を設ける。
- 自社ドメインのGoogleアカウントのみ許可する設定を持たせる。
- Googleログインだけで即時配信権限を与えず、ロールと承認状態で制御する。

### 5.3 権限

| ロール | 権限 |
| --- | --- |
| システム管理者 | 全機能、SMTP設定、利用者管理、システム設定 |
| 配信管理者 | 宛先管理、テンプレート、キャンペーン、配信実行 |
| 配信者 | 自分のキャンペーン作成、テスト送信、承認依頼 |
| 編集者 | テンプレート作成、AI文面提案、下書き編集 |
| 閲覧者 | 配信履歴、宛先一覧、ログ閲覧のみ |

### 5.4 監査ログ

以下の操作は監査ログに記録する。

- ログイン成功/失敗
- 利用者作成/停止/権限変更
- SMTP設定の作成/更新/削除
- 宛先インポート
- キャンペーン作成/更新/削除
- テスト送信
- 本番配信開始/停止
- 宛先停止/購読停止解除
- AI文面生成

## 6. UI要件

### 6.1 基本デザイン

- Bootstrapを使用する。
- PC表示では左側に縦型メニューを表示する。
- スマートフォン表示ではハンバーガーメニューに切り替える。
- 明るい色調を基本とし、背景・文字・ボタンのコントラストを明確にする。
- 業務画面として読みやすく、表・フォーム・ボタンの視認性を優先する。

### 6.2 画面構成

主要メニュー:

- ダッシュボード
- 宛先管理
- インポート
- 送信者/SMTP管理
- テンプレート管理
- メール作成
- AI文面提案
- 配信予約
- 配信キュー
- 配信履歴
- バウンス管理
- 購読停止一覧
- 利用者管理
- システム設定
- 監査ログ

### 6.3 UI品質

- 操作頻度が高い画面では過剰なアニメーションを避ける。
- ボタン押下時は軽い押下フィードバックを付ける。
- アニメーションは原則150-250ms以内とする。
- `transition: all` は避け、対象プロパティを明示する。
- スマートフォンではテーブルを横スクロールまたはカード表示に切り替える。
- フォーカス状態を明確に表示する。
- `prefers-reduced-motion` に対応する。

## 7. 宛先管理

### 7.1 宛先項目

- メールアドレス
- 氏名
- 会社名
- タグ
- 任意項目
- 登録元
- 登録日時
- 更新日時
- ステータス

### 7.2 宛先ステータス

| ステータス | 意味 |
| --- | --- |
| active | 配信可能 |
| unsubscribed | 購読停止 |
| hard_bounced | ハードバウンスにより停止 |
| soft_bounced | ソフトバウンスにより一時停止 |
| manually_disabled | 管理者による停止 |
| pending_optin | ダブルオプトイン未確認 |

### 7.3 絞り込み

- メールドメイン
- タグ
- 任意項目
- ステータス
- 登録日
- 最終配信日
- バウンス回数

## 8. インポート/エクスポート

### 8.1 インポート形式

- CSV
- TSV
- プレーンテキスト
- 1行1メールアドレス形式
- acMailer由来のCSV/テキスト形式

### 8.2 インポート仕様

- 区切り文字を自動判定する。
- 文字コードを自動判定または手動指定できるようにする。
- ヘッダ行の有無を指定できる。
- 列マッピング画面を用意する。
- メールアドレス形式を検証する。
- 重複を検出する。
- 既存データに対して上書き、スキップ、追記を選択できる。
- 不正行はエラー一覧として表示し、正常行のみ取込可能にする。

### 8.3 エクスポート

- 宛先一覧
- 購読停止一覧
- バウンス一覧
- 配信履歴
- キャンペーン結果

出力形式:

- CSV
- TSV

## 9. 送信者/SMTP管理

### 9.1 送信者アドレス

送信者アドレスごとにSMTP設定を紐付ける。

設定項目:

- 表示名
- Fromメールアドレス
- Reply-To
- SMTPホスト
- SMTPポート
- TLS/SSL
- SMTP認証方式
- SMTP認証ID
- SMTPパスワード
- 1分あたり送信上限
- 1日あたり送信上限
- バウンス受信用アドレス
- DKIM署名方針
- 有効/無効

### 9.2 DMARC対応方針

- FromドメインとSMTP/DKIM/SPFの整合性を重視する。
- 送信者アドレスごとに利用できるSMTP設定を固定する。
- 送信者アドレスと異なるドメインのSMTPを誤って使わないようにする。
- 送信前にSPF、DKIM、DMARC、MX、PTRの確認結果を表示する。

重要:

- 中央のシステムドメインだけをReturn-Pathに使う場合、SPFアラインメントが崩れる可能性がある。
- FromドメインとReturn-Pathドメインが異なる場合、DKIMアラインメントでDMARCを通す設計が必要。
- 最良構成は、送信者ドメインごとに `bounce.送信者ドメイン` のMXまたは転送先を本システムに向ける方式。

## 10. メールテンプレート

### 10.1 対応形式

- テキストメール
- HTMLメール
- multipart/alternative

### 10.2 テンプレート機能

- 件名テンプレート
- 本文テンプレート
- HTML本文テンプレート
- 差し込み項目
- テスト送信
- プレビュー
- 複製
- 下書き保存
- バージョン管理

### 10.3 差し込み項目

例:

- `{{email}}`
- `{{name}}`
- `{{company}}`
- `{{unsubscribe_url}}`
- `{{custom.field_name}}`

### 10.4 HTMLメール安全対策

- JavaScriptを除去する。
- 危険なイベント属性を除去する。
- 外部画像利用時に警告を表示する。
- HTMLとテキスト本文の両方を保持する。

## 11. AI文面提案

### 11.1 基本方針

- OpenAI API等の外部APIを利用する。
- AIはメール本文を直接送信しない。
- AI生成結果は下書きとして保存し、配信者または管理者が確認してから配信する。

### 11.2 入力項目

- 配信目的
- 対象者
- トーン
- 商品/サービス概要
- 伝えたい要点
- CTA
- 文字数
- HTMLメール化の有無

### 11.3 保存情報

- 入力プロンプト
- 生成結果
- 生成日時
- 実行利用者
- 使用モデル
- 採用/不採用

### 11.4 注意事項

- 個人情報や機密情報を外部APIに送る可能性がある場合は警告を表示する。
- APIキーは環境変数または暗号化設定として管理する。
- AI生成文面には誤情報が含まれる可能性があるため、人間承認を必須とする。

## 12. キャンペーン管理

### 12.1 作成項目

- キャンペーン名
- 配信者
- 送信者アドレス
- テンプレート
- 配信対象条件
- 配信日時
- テスト送信先
- 送信速度
- 承認状態

### 12.2 配信状態

| 状態 | 意味 |
| --- | --- |
| draft | 下書き |
| pending_approval | 承認待ち |
| approved | 承認済み |
| queued | キュー作成済み |
| sending | 送信中 |
| paused | 一時停止 |
| completed | 完了 |
| cancelled | 中止 |

### 12.3 配信前チェック

- 送信者アドレスが有効か。
- SMTP設定が有効か。
- テンプレートに購読停止URLが含まれているか。
- `List-Unsubscribe` ヘッダが生成可能か。
- 宛先数が想定範囲内か。
- 停止済み宛先が除外されているか。
- テスト送信が完了しているか。

## 13. 配信キュー

### 13.1 cron仕様

- cronで毎分起動する。
- 1回の実行で最大5通程度を送信する。
- SMTPアカウントごとの送信上限を守る。
- cronの多重起動を防止する。
- 実行中に異常終了しても次回再開できる。

### 13.2 キュー状態

| 状態 | 意味 |
| --- | --- |
| pending | 未送信 |
| sending | 送信中 |
| sent | 送信済み |
| temporary_failed | 一時失敗 |
| permanent_failed | 恒久失敗 |
| skipped | 停止済み等で除外 |
| cancelled | キャンセル |

### 13.3 保存情報

- campaign_id
- recipient_id
- sender_identity_id
- smtp_account_id
- scheduled_at
- sent_at
- status
- smtp_response_code
- enhanced_status_code
- error_message
- retry_count
- return_path_token

## 14. バウンス処理

### 14.1 基本方式

- VERP方式を採用する。
- 宛先ごとに一意なReturn-Pathを生成する。
- 例: `bounce+campaign123.recipient456.token@bounce.example.com`

### 14.2 受信方式

第一候補:

- MTA直結処理
- 専用メールボックスまたは専用アドレス宛のメールをPHP CLIにpipeする。
- 受信直後にDBへ記録する。

第二候補:

- IMAP/POPによる定期取得
- 専用メールボックスをcronで読み取り、バウンス判定する。

### 14.3 解析対象

- Return-Path
- To
- Original-Recipient
- Final-Recipient
- Action
- Status
- Diagnostic-Code
- message/delivery-status
- 元メールのMessage-ID

### 14.4 判定ルール

| 条件 | 処理 |
| --- | --- |
| `5.x.x` | ハードバウンス候補 |
| `5.1.1` 宛先不存在 | 即時停止 |
| ドメイン不存在 | 即時停止 |
| `4.x.x` | ソフトバウンス |
| mailbox full | 複数回失敗後に一時停止 |
| policy reject / spam reject | 宛先停止ではなく送信設定・本文・認証を警告 |
| 連続ソフトバウンス3回 | 一時停止 |
| 一定期間失敗継続 | 一時停止 |

### 14.5 バウンス停止

- ハードバウンスは次回配信から自動除外する。
- ソフトバウンスは閾値到達まで再試行する。
- バウンス理由は宛先ごとに履歴保存する。

## 15. 購読停止

### 15.1 本文内URL

- すべての配信メールに購読停止URLを挿入する。
- 購読停止URLは推測困難なトークンを含める。
- ログインなしで購読停止できる。

### 15.2 List-Unsubscribe

- `List-Unsubscribe` ヘッダを付与する。
- `List-Unsubscribe-Post: List-Unsubscribe=One-Click` を付与する。
- GETアクセスでは確認画面を表示する。
- POSTアクセスでは即時購読停止する。

### 15.3 停止理由

- ユーザー操作
- one-click unsubscribe
- 管理者停止
- バウンス停止
- 苦情フィードバック

## 16. 苦情フィードバック

初期MVPでは任意機能とするが、将来拡張できるDB構造にする。

対象:

- Yahoo Complaint Feedback Loop
- Microsoft SNDS/JMRP
- Gmail Postmaster Toolsによるドメイン単位監視

迷惑メール報告を受けた宛先は、原則として即時購読停止扱いにする。

## 17. 配信履歴

### 17.1 記録対象

- キャンペーン
- 宛先
- 送信者アドレス
- SMTPアカウント
- 送信日時
- 送信結果
- SMTP応答
- バウンス結果
- 購読停止結果

### 17.2 不要な計測

- 開封計測は不要。
- クリック計測は不要。

開封/クリック計測を実装しないことで、プライバシー面と実装負荷を抑える。

## 18. acMailer踏襲機能

- メールアドレス管理
- 一括登録
- 一括削除
- CSV取込
- テキスト取込
- 自由項目
- 絞り込み配信
- 差し込み送信
- 配信メールテンプレート
- HTMLメール
- テスト配信
- 予約配信
- 配信履歴
- 登録フォーム
- 解除フォーム
- ダブルオプトイン
- バウンス処理
- 設定バックアップ

## 19. DBテーブル案

### 19.1 認証・利用者

- users
- user_google_accounts
- user_sessions
- password_resets
- roles
- user_roles
- audit_logs

### 19.2 宛先

- recipients
- recipient_custom_fields
- recipient_custom_values
- recipient_tags
- tags
- unsubscribes
- optin_tokens

### 19.3 送信者・SMTP

- sender_identities
- smtp_accounts
- sender_domain_checks
- dkim_settings

### 19.4 テンプレート・AI

- mail_templates
- mail_template_versions
- ai_generation_requests
- ai_generation_results

### 19.5 配信

- campaigns
- campaign_segments
- campaign_approvals
- mail_queue
- mail_send_logs

### 19.6 バウンス

- bounce_messages
- bounce_events
- bounce_rules

### 19.7 システム

- settings
- cron_locks
- system_logs

## 20. ディレクトリ構成案

Composerを使わないため、簡潔な独自構成とする。

```text
/public
  /index.php
  /login.php
  /google-callback.php
  /unsubscribe.php
  /assets
    /css
    /js
/app
  /Config
  /Controllers
  /Models
  /Services
  /Mail
  /Auth
  /Bounce
  /Import
  /AI
  /Views
/cron
  /send_queue.php
  /fetch_bounces.php
  /process_bounce_pipe.php
/storage
  /logs
  /imports
  /mail_raw
/database
  /schema.sql
  /migrations
```

## 21. セキュリティ要件

- HTTPS前提。
- PDOプリペアドステートメントを使用する。
- CSRFトークンを実装する。
- XSS対策として出力エスケープを徹底する。
- セッションCookieに `HttpOnly`、`Secure`、`SameSite` を設定する。
- ログイン成功時にセッションIDを再生成する。
- SMTPパスワード、OpenAI APIキー、Google Client Secretは暗号化または環境変数で管理する。
- 管理画面へのIP制限を任意設定できるようにする。
- ファイルアップロードはCSV/TSV/テキストに限定する。
- インポートファイルは一定期間後に削除する。

## 22. MVP範囲

初期開発で実装する範囲:

- 通常ユーザー登録
- Googleアカウントサインイン
- ロール管理
- 管理者ログイン
- BootstrapレスポンシブUI
- 宛先管理
- CSV/TSV/テキストインポート
- 送信者/SMTP管理
- テンプレート管理
- テスト送信
- キャンペーン作成
- cronキュー配信
- 毎分5通制御
- 配信履歴
- VERP形式Return-Path
- バウンス受信処理
- 購読停止URL
- List-Unsubscribeヘッダ
- 監査ログ

第2段階:

- AI文面提案
- 詳細なDNS診断
- 苦情フィードバック連携
- ダブルオプトイン
- テンプレートバージョン比較
- 複数組織対応

## 23. 受入基準

### 23.1 認証

- 通常登録した利用者がメール確認または管理者承認後にログインできる。
- Googleアカウントでサインインできる。
- 未承認ユーザーは配信できない。
- ロールによりメニューと操作権限が制御される。

### 23.2 配信

- 送信者アドレスごとにSMTP設定を固定できる。
- キャンペーンから宛先ごとのキューが生成される。
- cron実行1回あたり最大5通程度のみ送信される。
- SMTP応答が履歴に保存される。
- 途中停止、再開ができる。

### 23.3 バウンス

- Return-Pathトークンから宛先を特定できる。
- DSNの `Status` を解析できる。
- ハードバウンスが次回配信から除外される。
- ソフトバウンスが閾値に基づき一時停止される。

### 23.4 購読停止

- 本文内URLからログインなしで購読停止できる。
- `List-Unsubscribe` ヘッダが付与される。
- POSTによるワンクリック購読停止が動作する。
- 停止済み宛先は次回配信から除外される。

### 23.5 UI

- PCでは左サイドメニューが表示される。
- スマートフォンではハンバーガーメニューになる。
- 明るくコントラストのある画面で、主要操作が視認しやすい。
- テーブル、フォーム、ボタンがスマートフォンでも破綻しない。

## 24. 実装時の注意点

- 配信者アカウントと送信者メールアドレスを混同しない。
- Reply-ToとReturn-Pathを混同しない。
- FromドメインとSMTP/DKIM/SPFの整合性を崩さない。
- policy rejectやspam rejectを単純な宛先不明として処理しない。
- AI生成文面は必ず人間承認を通す。
- 購読停止はユーザーにとって最短導線にする。
- 開封/クリック計測は実装しない。
- cron多重起動を必ず防ぐ。

## 25. 参照規格・参考資料

- Google Identity Services: https://developers.google.com/identity/gsi/web/guides/overview
- OAuth 2.0 Security Best Current Practice, RFC 9700: https://datatracker.ietf.org/doc/rfc9700/
- OWASP Authentication Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html
- OWASP Session Management Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
- SMTP, RFC 5321: https://datatracker.ietf.org/doc/html/rfc5321
- Delivery Status Notifications, RFC 3464: https://datatracker.ietf.org/doc/html/rfc3464
- Enhanced Mail System Status Codes, RFC 3463: https://datatracker.ietf.org/doc/html/rfc3463
- One-Click List-Unsubscribe, RFC 8058: https://datatracker.ietf.org/doc/html/rfc8058
- List-Unsubscribe Header, RFC 2369: https://www.ietf.org/rfc/rfc2369.txt
- Gmail Email Sender Guidelines: https://support.google.com/mail/answer/81126
- Yahoo Sender Hub Best Practices: https://senders.yahooinc.com/best-practices/
