-- C:\Users\Yoshi\Documents\GitHub\Cool_Mail_List\database\migrations\2026_07_31_remaining_features.sql
-- Googleログイン、DNS診断、ダブルオプトイン、テンプレート版管理、複数組織対応に必要な既存DB移行。

SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE TABLE IF NOT EXISTS organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(128) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_organizations_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO organizations (id, name, slug, is_active, created_at, updated_at)
VALUES (1, 'Default', 'default', 1, NOW(), NOW());

DELIMITER //

DROP PROCEDURE IF EXISTS cml_add_column//
CREATE PROCEDURE cml_add_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @cml_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_sql);
        PREPARE cml_stmt FROM @cml_sql;
        EXECUTE cml_stmt;
        DEALLOCATE PREPARE cml_stmt;
    END IF;
END//

DROP PROCEDURE IF EXISTS cml_drop_index_if_exists//
CREATE PROCEDURE cml_drop_index_if_exists(IN p_table VARCHAR(64), IN p_index VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
    ) THEN
        SET @cml_sql = CONCAT('ALTER TABLE `', p_table, '` DROP INDEX `', p_index, '`');
        PREPARE cml_stmt FROM @cml_sql;
        EXECUTE cml_stmt;
        DEALLOCATE PREPARE cml_stmt;
    END IF;
END//

DROP PROCEDURE IF EXISTS cml_add_index_if_missing//
CREATE PROCEDURE cml_add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
    ) THEN
        SET @cml_sql = CONCAT('ALTER TABLE `', p_table, '` ', p_sql);
        PREPARE cml_stmt FROM @cml_sql;
        EXECUTE cml_stmt;
        DEALLOCATE PREPARE cml_stmt;
    END IF;
END//

DROP PROCEDURE IF EXISTS cml_add_fk_if_missing//
CREATE PROCEDURE cml_add_fk_if_missing(IN p_table VARCHAR(64), IN p_constraint VARCHAR(64), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = p_constraint
    ) THEN
        SET @cml_sql = CONCAT('ALTER TABLE `', p_table, '` ', p_sql);
        PREPARE cml_stmt FROM @cml_sql;
        EXECUTE cml_stmt;
        DEALLOCATE PREPARE cml_stmt;
    END IF;
END//

DELIMITER ;

CALL cml_add_column('users', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('recipients', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('smtp_accounts', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('sender_identities', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('sender_domain_checks', 'details_json', '`details_json` JSON NULL AFTER `ptr_status`');
CALL cml_add_column('mail_templates', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('ai_generation_requests', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('campaigns', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('mail_queue', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL cml_add_column('bounce_messages', 'organization_id', '`organization_id` BIGINT UNSIGNED NULL AFTER `id`');

UPDATE users SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE recipients SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE smtp_accounts SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE sender_identities si
LEFT JOIN smtp_accounts sa ON sa.id = si.smtp_account_id
SET si.organization_id = COALESCE(sa.organization_id, 1)
WHERE si.organization_id IS NULL;
UPDATE mail_templates SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE ai_generation_requests agr
LEFT JOIN users u ON u.id = agr.user_id
SET agr.organization_id = COALESCE(u.organization_id, 1)
WHERE agr.organization_id IS NULL;
UPDATE campaigns c
LEFT JOIN sender_identities si ON si.id = c.sender_identity_id
SET c.organization_id = COALESCE(si.organization_id, 1)
WHERE c.organization_id IS NULL;
UPDATE mail_queue mq
LEFT JOIN campaigns c ON c.id = mq.campaign_id
SET mq.organization_id = COALESCE(c.organization_id, 1)
WHERE mq.organization_id IS NULL;
UPDATE bounce_messages bm
LEFT JOIN mail_queue mq ON mq.return_path_token = bm.return_path_token
SET bm.organization_id = mq.organization_id
WHERE bm.organization_id IS NULL AND mq.organization_id IS NOT NULL;

ALTER TABLE users MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE recipients MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE smtp_accounts MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE sender_identities MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE mail_templates MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ai_generation_requests MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE campaigns MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE mail_queue MODIFY `organization_id` BIGINT UNSIGNED NOT NULL DEFAULT 1;

CALL cml_drop_index_if_exists('recipients', 'email');
CALL cml_drop_index_if_exists('sender_identities', 'from_email');

CALL cml_add_index_if_missing('users', 'idx_users_org', 'ADD INDEX `idx_users_org` (`organization_id`)');
CALL cml_add_index_if_missing('recipients', 'idx_recipients_org', 'ADD INDEX `idx_recipients_org` (`organization_id`)');
CALL cml_add_index_if_missing('recipients', 'uniq_recipients_org_email', 'ADD UNIQUE KEY `uniq_recipients_org_email` (`organization_id`, `email`)');
CALL cml_add_index_if_missing('smtp_accounts', 'idx_smtp_org', 'ADD INDEX `idx_smtp_org` (`organization_id`)');
CALL cml_add_index_if_missing('sender_identities', 'idx_sender_org', 'ADD INDEX `idx_sender_org` (`organization_id`)');
CALL cml_add_index_if_missing('sender_identities', 'uniq_senders_org_email', 'ADD UNIQUE KEY `uniq_senders_org_email` (`organization_id`, `from_email`)');
CALL cml_add_index_if_missing('mail_templates', 'idx_templates_org', 'ADD INDEX `idx_templates_org` (`organization_id`)');
CALL cml_add_index_if_missing('ai_generation_requests', 'idx_ai_requests_org', 'ADD INDEX `idx_ai_requests_org` (`organization_id`)');
CALL cml_add_index_if_missing('campaigns', 'idx_campaign_org', 'ADD INDEX `idx_campaign_org` (`organization_id`)');
CALL cml_add_index_if_missing('mail_queue', 'idx_queue_org', 'ADD INDEX `idx_queue_org` (`organization_id`)');
CALL cml_add_index_if_missing('bounce_messages', 'idx_bounce_org', 'ADD INDEX `idx_bounce_org` (`organization_id`)');

CALL cml_add_fk_if_missing('users', 'fk_users_organization', 'ADD CONSTRAINT `fk_users_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('recipients', 'fk_recipients_organization', 'ADD CONSTRAINT `fk_recipients_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('smtp_accounts', 'fk_smtp_accounts_organization', 'ADD CONSTRAINT `fk_smtp_accounts_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('sender_identities', 'fk_sender_identities_organization', 'ADD CONSTRAINT `fk_sender_identities_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('mail_templates', 'fk_mail_templates_organization', 'ADD CONSTRAINT `fk_mail_templates_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('ai_generation_requests', 'fk_ai_generation_requests_organization', 'ADD CONSTRAINT `fk_ai_generation_requests_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('campaigns', 'fk_campaigns_organization', 'ADD CONSTRAINT `fk_campaigns_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('mail_queue', 'fk_mail_queue_organization', 'ADD CONSTRAINT `fk_mail_queue_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');
CALL cml_add_fk_if_missing('bounce_messages', 'fk_bounce_messages_organization', 'ADD CONSTRAINT `fk_bounce_messages_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)');

DROP PROCEDURE IF EXISTS cml_add_column;
DROP PROCEDURE IF EXISTS cml_drop_index_if_exists;
DROP PROCEDURE IF EXISTS cml_add_index_if_missing;
DROP PROCEDURE IF EXISTS cml_add_fk_if_missing;
