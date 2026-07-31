<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\DnsDiagnosticsService.php
 * FromドメインとSMTPホストに対してMX/SPF/DMARC/DKIM/PTRの診断を行う。
 */

final class DnsDiagnosticsService
{
    public static function run(int $senderId): array
    {
        $sender = Database::fetch(
            'SELECT si.*, sa.smtp_host
             FROM sender_identities si
             JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
             WHERE si.id = ? AND si.organization_id = ?',
            [$senderId, OrganizationService::currentId()]
        );
        if (!$sender) {
            throw new RuntimeException('送信者が見つかりません。');
        }

        $domain = substr(strrchr((string)$sender['from_email'], '@') ?: '', 1);
        if ($domain === '') {
            throw new RuntimeException('Fromメールのドメインを取得できません。');
        }

        $checks = [
            'mx' => self::mx($domain),
            'spf' => self::spf($domain),
            'dmarc' => self::dmarc($domain),
            'dkim' => self::dkim($domain, (int)$senderId),
            'ptr' => self::ptr((string)$sender['smtp_host']),
        ];

        Database::execute(
            'INSERT INTO sender_domain_checks
                (sender_identity_id, spf_status, dkim_status, dmarc_status, mx_status, ptr_status, details_json, checked_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $senderId,
                $checks['spf']['status'],
                $checks['dkim']['status'],
                $checks['dmarc']['status'],
                $checks['mx']['status'],
                $checks['ptr']['status'],
                json_encode($checks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );

        AuditLogger::log('dns_diagnostics_run', ['sender_identity_id' => $senderId, 'domain' => $domain]);
        return ['sender' => $sender, 'domain' => $domain, 'checks' => $checks];
    }

    public static function latest(?int $senderId = null): array
    {
        if ($senderId) {
            return Database::fetchAll(
                'SELECT sdc.*, si.from_email
                 FROM sender_domain_checks sdc
                 JOIN sender_identities si ON si.id = sdc.sender_identity_id
                 WHERE sdc.sender_identity_id = ? AND si.organization_id = ?
                 ORDER BY sdc.id DESC LIMIT 20',
                [$senderId, OrganizationService::currentId()]
            );
        }

        return Database::fetchAll(
            'SELECT sdc.*, si.from_email
             FROM sender_domain_checks sdc
             JOIN sender_identities si ON si.id = sdc.sender_identity_id
             WHERE si.organization_id = ?
             ORDER BY sdc.id DESC LIMIT 100',
            [OrganizationService::currentId()]
        );
    }

    private static function mx(string $domain): array
    {
        $records = dns_get_record($domain, DNS_MX) ?: [];
        return [
            'status' => $records ? 'ok' : 'missing',
            'summary' => $records ? 'MXあり' : 'MXなし',
            'records' => array_map(static fn($r) => ($r['target'] ?? '') . ' priority=' . ($r['pri'] ?? ''), $records),
        ];
    }

    private static function spf(string $domain): array
    {
        $records = self::txt($domain);
        $spf = array_values(array_filter($records, static fn($r) => str_starts_with(strtolower($r), 'v=spf1')));
        return [
            'status' => count($spf) === 1 ? 'ok' : (count($spf) > 1 ? 'warning' : 'missing'),
            'summary' => count($spf) === 1 ? 'SPFあり' : (count($spf) > 1 ? 'SPFが複数あります' : 'SPFなし'),
            'records' => $spf,
        ];
    }

    private static function dmarc(string $domain): array
    {
        $records = self::txt('_dmarc.' . $domain);
        $dmarc = array_values(array_filter($records, static fn($r) => str_starts_with(strtolower($r), 'v=dmarc1')));
        return [
            'status' => count($dmarc) === 1 ? 'ok' : (count($dmarc) > 1 ? 'warning' : 'missing'),
            'summary' => count($dmarc) === 1 ? 'DMARCあり' : (count($dmarc) > 1 ? 'DMARCが複数あります' : 'DMARCなし'),
            'records' => $dmarc,
        ];
    }

    private static function dkim(string $domain, int $senderId): array
    {
        $selectors = Database::fetchAll('SELECT selector FROM dkim_settings WHERE sender_identity_id = ?', [$senderId]);
        $selectorNames = array_values(array_unique(array_filter(array_map(static fn($r) => (string)$r['selector'], $selectors))));
        if (!$selectorNames) {
            $selectorNames = ['default', 'selector1', 'google', 'mail'];
        }

        $hits = [];
        foreach ($selectorNames as $selector) {
            $txt = self::txt($selector . '._domainkey.' . $domain);
            foreach ($txt as $record) {
                if (str_contains(strtolower($record), 'v=dkim1')) {
                    $hits[] = $selector . ': ' . $record;
                }
            }
        }

        return [
            'status' => $hits ? 'ok' : 'missing',
            'summary' => $hits ? 'DKIM候補あり' : '既定セレクタではDKIMなし',
            'records' => $hits,
        ];
    }

    private static function ptr(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];
        $ptrs = [];
        foreach ($ips as $ip) {
            $ptr = gethostbyaddr($ip);
            $ptrs[] = $ip . ' => ' . ($ptr ?: 'なし');
        }
        return [
            'status' => $ptrs ? 'ok' : 'missing',
            'summary' => $ptrs ? 'PTR確認済み' : 'SMTPホストのIP解決不可',
            'records' => $ptrs,
        ];
    }

    private static function txt(string $name): array
    {
        $records = dns_get_record($name, DNS_TXT) ?: [];
        return array_values(array_filter(array_map(static fn($r) => (string)($r['txt'] ?? ''), $records)));
    }
}
