<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\MailerService.php
 * PHPMailerを使い、SMTP設定ごとにメール本文差し込み・解除ヘッダ・VERP Return-Pathを付けて送信する。
 */

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

final class MailerService
{
    public static function checkSenderSmtp(int $senderId): array
    {
        $sender = Database::fetch(
            'SELECT si.id AS sender_identity_id, si.from_email,
                    sa.smtp_host, sa.smtp_port, sa.encryption, sa.auth_username, sa.auth_password_ciphertext
             FROM sender_identities si
             JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
             WHERE si.id = ? AND si.organization_id = ?',
            [$senderId, OrganizationService::currentId()]
        );
        if (!$sender) {
            throw new RuntimeException('送信者/SMTP設定が見つかりません。');
        }

        $result = [
            'ok' => false,
            'sender_id' => (int)$sender['sender_identity_id'],
            'from_email' => (string)$sender['from_email'],
            'smtp_host' => (string)$sender['smtp_host'],
            'smtp_port' => (int)$sender['smtp_port'],
            'encryption' => (string)$sender['encryption'],
            'auth_user_set' => (string)($sender['auth_username'] ?? '') !== '',
            'message' => '',
        ];

        if ((int)$sender['smtp_port'] === 465 && (string)$sender['encryption'] === 'tls') {
            $result['message'] = 'SMTPポート465では暗号化にSSLを選択してください。TLS(STARTTLS)を使う場合は通常587番です。';
            return $result;
        }

        try {
            $mail = self::buildMailer($sender);
            $mail->Timeout = 15;
            $mail->Timelimit = 15;
            $connected = $mail->smtpConnect();
            if ($connected && $mail->getSMTPInstance()) {
                $mail->getSMTPInstance()->quit();
                $mail->getSMTPInstance()->close();
            }

            $result['ok'] = $connected;
            $result['message'] = $connected ? 'SMTP接続と認証に成功しました。' : ($mail->ErrorInfo ?: 'SMTP接続に失敗しました。');
        } catch (Throwable $e) {
            $result['message'] = $e->getMessage();
        }

        AuditLogger::log('sender_smtp_checked', [
            'sender_identity_id' => $senderId,
            'ok' => $result['ok'],
            'smtp_host' => $result['smtp_host'],
        ]);
        return $result;
    }

    public static function sendQueueItem(array $item): array
    {
        $mail = self::buildMailer($item);
        $recipient = [
            'email' => $item['recipient_email'],
            'name' => $item['recipient_name'],
            'company' => $item['recipient_company'],
        ];
        $unsubscribeUrl = base_url('unsubscribe.php?t=' . urlencode($item['unsubscribe_token']));
        $subject = self::renderText($item['subject'], $recipient, $unsubscribeUrl);
        $textBody = self::renderText($item['body_text'], $recipient, $unsubscribeUrl);
        $htmlBody = $item['body_html'] !== null && $item['body_html'] !== ''
            ? self::renderText($item['body_html'], $recipient, $unsubscribeUrl)
            : '';

        try {
            $mail->setFrom($item['from_email'], $item['from_name']);
            if (!empty($item['reply_to'])) {
                $mail->addReplyTo($item['reply_to']);
            }
            $mail->addAddress($item['recipient_email'], $item['recipient_name'] ?? '');
            $mail->Subject = $subject;
            $mail->Body = $htmlBody !== '' ? $htmlBody : nl2br(h($textBody));
            $mail->AltBody = $textBody;
            $mail->isHTML($htmlBody !== '');
            $mail->Sender = self::returnPath($item);
            $mail->addCustomHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');
            $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            $mail->send();

            return [
                'ok' => true,
                'message_id' => $mail->getLastMessageID(),
                'smtp_response' => $mail->ErrorInfo,
            ];
        } catch (MailException $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'smtp_response' => $mail->ErrorInfo,
                'permanent' => self::looksPermanent($mail->ErrorInfo . ' ' . $e->getMessage()),
            ];
        }
    }

    public static function sendTest(int $senderId, int $templateId, string $to): array
    {
        $organizationId = OrganizationService::currentId();
        $item = Database::fetch(
            'SELECT si.from_name, si.from_email, si.reply_to,
                    sa.smtp_host, sa.smtp_port, sa.encryption, sa.auth_username, sa.auth_password_ciphertext,
                    mt.subject, mt.body_text, mt.body_html,
                    ? AS recipient_email, ? AS recipient_name, "" AS recipient_company,
                    "test" AS return_path_token, "test" AS unsubscribe_token
             FROM sender_identities si
             JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
             JOIN mail_templates mt ON mt.id = ?
             WHERE si.id = ?
               AND si.organization_id = ?
               AND mt.organization_id = si.organization_id
               AND si.is_active = 1
               AND sa.is_active = 1',
            [$to, 'テスト宛先', $templateId, $senderId, $organizationId]
        );
        if (!$item) {
            throw new RuntimeException('送信者またはテンプレートが見つかりません。');
        }
        return self::sendQueueItem($item);
    }

    public static function sendSystemMail(string $to, string $subject, string $textBody, ?string $htmlBody = null): array
    {
        $mail = self::buildSystemMailer();
        $smtp = MailSettingsService::systemSmtp();
        try {
            $mail->setFrom($smtp['from'], $smtp['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody ?: nl2br(h($textBody));
            $mail->AltBody = $textBody;
            $mail->isHTML($htmlBody !== null && $htmlBody !== '');
            $mail->send();

            return [
                'ok' => true,
                'message_id' => $mail->getLastMessageID(),
                'smtp_response' => $mail->ErrorInfo,
            ];
        } catch (MailException $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'smtp_response' => $mail->ErrorInfo,
            ];
        }
    }

    private static function buildMailer(array $item): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host = $item['smtp_host'];
        $mail->Port = (int)$item['smtp_port'];
        $mail->SMTPAuth = $item['auth_username'] !== null && $item['auth_username'] !== '';
        $mail->Username = $item['auth_username'] ?? '';
        $mail->Password = CryptoService::decrypt($item['auth_password_ciphertext'] ?? '');
        if ($item['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($item['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        return $mail;
    }

    private static function buildSystemMailer(): PHPMailer
    {
        $smtp = MailSettingsService::systemSmtp();
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'];
        $mail->SMTPAuth = $smtp['user'] !== '';
        $mail->Username = $smtp['user'];
        $mail->Password = $smtp['pass'];

        if ($smtp['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        return $mail;
    }

    private static function renderText(string $template, array $recipient, string $unsubscribeUrl): string
    {
        return strtr($template, [
            '{{email}}' => (string)($recipient['email'] ?? ''),
            '{{name}}' => (string)($recipient['name'] ?? ''),
            '{{company}}' => (string)($recipient['company'] ?? ''),
            '{{unsubscribe_url}}' => $unsubscribeUrl,
        ]);
    }

    private static function returnPath(array $item): string
    {
        $baseEmail = MailSettingsService::bounceBaseEmail();
        if (!str_contains($baseEmail, '@')) {
            throw new RuntimeException('固定バウンス基準アドレスが未設定、または不正です。');
        }
        return self::plusAddress($baseEmail, $item['return_path_token']);
    }

    private static function plusAddress(string $email, string $token): string
    {
        [$local, $domain] = explode('@', $email, 2);
        return $local . '+' . $token . '@' . $domain;
    }

    private static function looksPermanent(string $message): bool
    {
        return (bool)preg_match('/\b5\.\d+\.\d+\b|\b55\d\b|user unknown|no such user|mailbox unavailable/i', $message);
    }
}
