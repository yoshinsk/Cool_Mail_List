<?php
declare(strict_types=1);

/*
 * C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\app\Services\RecipientTagService.php
 * 宛先タグの正規化、recipient_tags同期、キャンペーン配信先タグ条件の保存と表示を扱う。
 */

final class RecipientTagService
{
    /**
     * 画面入力やCSV文字列からタグ名を取り出し、空白除去と重複排除を行う。
     */
    public static function normalizeList(string|array|null $input): array
    {
        $items = is_array($input)
            ? $input
            : preg_split('/[,、;；\t\r\n]+/u', (string)$input);
        $tags = [];

        foreach ($items ?: [] as $item) {
            $tag = trim((string)$item);
            if ($tag === '') {
                continue;
            }
            $tags[$tag] = $tag;
        }

        return array_values($tags);
    }

    /**
     * recipients.tags の表示用文字列を正とし、検索用の中間テーブルを同じ内容に揃える。
     */
    public static function syncForRecipient(int $recipientId, string|array|null $tags): void
    {
        $names = self::normalizeList($tags);
        Database::execute('DELETE FROM recipient_tags WHERE recipient_id = ?', [$recipientId]);
        if ($names === []) {
            return;
        }

        foreach (self::ensureTagIds($names) as $tagId) {
            Database::execute(
                'INSERT IGNORE INTO recipient_tags (recipient_id, tag_id) VALUES (?, ?)',
                [$recipientId, $tagId]
            );
        }
    }

    /**
     * 組織内で使われているタグを、作成画面で選びやすい件数付きで返す。
     */
    public static function allForOrganization(int $organizationId): array
    {
        return Database::fetchAll(
            'SELECT
                t.name,
                COUNT(DISTINCT r.id) AS total_count,
                SUM(CASE WHEN r.status = "active" THEN 1 ELSE 0 END) AS active_count
             FROM tags t
             JOIN recipient_tags rt ON rt.tag_id = t.id
             JOIN recipients r ON r.id = rt.recipient_id
             WHERE r.organization_id = ?
             GROUP BY t.id, t.name
             ORDER BY t.name',
            [$organizationId]
        );
    }

    /**
     * キャンペーンのタグ条件を保存する。未選択の場合は全active宛先扱いにする。
     */
    public static function storeCampaignFilter(int $campaignId, string|array|null $tags, string $match = 'any'): void
    {
        $names = self::normalizeList($tags);
        Database::execute('DELETE FROM campaign_segments WHERE campaign_id = ?', [$campaignId]);
        if ($names === []) {
            return;
        }

        $filter = [
            'tags' => $names,
            'tag_match' => $match === 'all' ? 'all' : 'any',
        ];
        Database::execute(
            'INSERT INTO campaign_segments (campaign_id, filter_json, created_at) VALUES (?, ?, NOW())',
            [$campaignId, json_encode($filter, JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * 1キャンペーン分のタグ条件を取得する。条件なしなら空配列を返す。
     */
    public static function campaignFilter(int $campaignId): array
    {
        $row = Database::fetch(
            'SELECT filter_json FROM campaign_segments WHERE campaign_id = ? ORDER BY id DESC LIMIT 1',
            [$campaignId]
        );
        if (!$row || empty($row['filter_json'])) {
            return ['tags' => [], 'tag_match' => 'any'];
        }

        return self::normalizeFilter(json_decode((string)$row['filter_json'], true) ?: []);
    }

    /**
     * 一覧表示用に複数キャンペーンのタグ条件をまとめて読む。
     */
    public static function filtersForCampaignIds(array $campaignIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $campaignIds)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            'SELECT campaign_id, filter_json FROM campaign_segments WHERE campaign_id IN (' . $placeholders . ') ORDER BY id DESC',
            $ids
        );
        $filters = [];
        foreach ($rows as $row) {
            $campaignId = (int)$row['campaign_id'];
            if (isset($filters[$campaignId])) {
                continue;
            }
            $filters[$campaignId] = self::normalizeFilter(json_decode((string)$row['filter_json'], true) ?: []);
        }

        return $filters;
    }

    /**
     * 保存済み条件を管理画面で読める短い説明へ変換する。
     */
    public static function describeFilter(array $filter): string
    {
        $normalized = self::normalizeFilter($filter);
        if ($normalized['tags'] === []) {
            return '全active宛先';
        }

        $prefix = $normalized['tag_match'] === 'all' && count($normalized['tags']) > 1
            ? 'タグすべて: '
            : 'タグ: ';
        return $prefix . implode(' / ', $normalized['tags']);
    }

    /**
     * タグ名をtagsテーブルに存在させ、名前順ではなく入力順でIDを返す。
     */
    private static function ensureTagIds(array $names): array
    {
        foreach ($names as $name) {
            Database::execute(
                'INSERT INTO tags (name, created_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)',
                [$name]
            );
        }

        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $rows = Database::fetchAll('SELECT id, name FROM tags WHERE name IN (' . $placeholders . ')', $names);
        $idsByName = [];
        foreach ($rows as $row) {
            $idsByName[(string)$row['name']] = (int)$row['id'];
        }

        $ids = [];
        foreach ($names as $name) {
            if (isset($idsByName[$name])) {
                $ids[] = $idsByName[$name];
            }
        }
        return $ids;
    }

    /**
     * 古い形式や壊れたJSONを受けても、以後の処理が同じ形で扱えるようにする。
     */
    private static function normalizeFilter(array $filter): array
    {
        return [
            'tags' => self::normalizeList($filter['tags'] ?? []),
            'tag_match' => ($filter['tag_match'] ?? 'any') === 'all' ? 'all' : 'any',
        ];
    }
}
