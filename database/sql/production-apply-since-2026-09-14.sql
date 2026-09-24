-- Resisquare production schema update
-- Covers database changes that exist locally but are not in git commit ee4555c
-- (14 Sep 2026, "repair issues and edit form fixes that returned 500").
--
-- Run this on the production database AFTER a backup, and BEFORE
-- `php artisan migrate`. The script records these migrations so artisan
-- will not try to apply them again.
--
-- Safe to run more than once. It only adds missing columns, tables,
-- indexes, permissions, and backfills.
--
-- It does NOT copy local test data: staging users, PHPUnit invoices,
-- properties, tenancies, or the resisquare_qa_audit database.
--
-- Data it does change on existing production rows:
--   1. bank_details.account_id, when that column is still empty
--   2. notes.noteable_* and notes.note_type_id, when those are still empty
--   3. two permissions: "manage tenancies" and "view property repair"
--      attached to Super Admin and Landlord

SET NAMES utf8mb4;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- tenant_members: confirmation status
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenant_members' AND COLUMN_NAME = 'details_status'
        ),
        'SELECT ''skip tenant_members.details_status''',
        'ALTER TABLE `tenant_members` ADD COLUMN `details_status` varchar(32) NOT NULL DEFAULT ''pending'''
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenant_members' AND COLUMN_NAME = 'details_confirmed_at'
        ),
        'SELECT ''skip tenant_members.details_confirmed_at''',
        'ALTER TABLE `tenant_members` ADD COLUMN `details_confirmed_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- tenancy_correction_requests
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tenancy_correction_requests` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `account_id` bigint(20) unsigned DEFAULT NULL,
    `tenancy_id` bigint(20) unsigned NOT NULL,
    `tenant_member_id` bigint(20) unsigned DEFAULT NULL,
    `requested_by` bigint(20) unsigned NOT NULL,
    `status` varchar(32) NOT NULL DEFAULT 'pending',
    `message` text NOT NULL,
    `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
    `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot`)),
    `landlord_note` text DEFAULT NULL,
    `reviewed_by` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `applied_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tenancy_correction_requests_reviewed_by_foreign` (`reviewed_by`),
    KEY `tenancy_correction_requests_account_id_index` (`account_id`),
    KEY `tenancy_correction_requests_tenancy_id_index` (`tenancy_id`),
    KEY `tenancy_correction_requests_tenant_member_id_index` (`tenant_member_id`),
    KEY `tenancy_correction_requests_requested_by_index` (`requested_by`),
    KEY `tenancy_correction_requests_status_index` (`status`),
    CONSTRAINT `tenancy_correction_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tenancy_correction_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `tenancy_correction_requests_tenancy_id_foreign` FOREIGN KEY (`tenancy_id`) REFERENCES `tenancies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tenancy_correction_requests_tenant_member_id_foreign` FOREIGN KEY (`tenant_member_id`) REFERENCES `tenant_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- bank_details.account_id and backfill
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_details' AND COLUMN_NAME = 'account_id'
        ),
        'SELECT ''skip bank_details.account_id''',
        'ALTER TABLE `bank_details` ADD COLUMN `account_id` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_details' AND INDEX_NAME = 'bank_details_account_id_index'
        ),
        'SELECT ''skip bank_details_account_id_index''',
        'ALTER TABLE `bank_details` ADD INDEX `bank_details_account_id_index` (`account_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `bank_details` bd
INNER JOIN (
    SELECT user_id, MIN(account_id) AS account_id
    FROM account_users
    WHERE status = 'active'
    GROUP BY user_id
) au ON au.user_id = bd.user_id
SET bd.account_id = au.account_id
WHERE bd.account_id IS NULL;

-- ---------------------------------------------------------------------------
-- notes: polymorphic columns, backfill, nullable legacy columns
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = 'noteable_type'
        ),
        'SELECT ''skip notes.noteable_type''',
        'ALTER TABLE `notes` ADD COLUMN `noteable_type` varchar(191) DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = 'noteable_id'
        ),
        'SELECT ''skip notes.noteable_id''',
        'ALTER TABLE `notes` ADD COLUMN `noteable_id` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = 'note_type_id'
        ),
        'SELECT ''skip notes.note_type_id''',
        'ALTER TABLE `notes` ADD COLUMN `note_type_id` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `notes`
SET `noteable_type` = 'App\\Models\\Property',
    `noteable_id` = `property_id`
WHERE `noteable_type` IS NULL
  AND `property_id` IS NOT NULL;

UPDATE `notes` n
INNER JOIN `note_types` nt ON nt.name = n.type
SET n.note_type_id = nt.id
WHERE n.note_type_id IS NULL
  AND n.type IS NOT NULL;

UPDATE `notes`
SET `note_type_id` = (SELECT id FROM `note_types` ORDER BY id LIMIT 1)
WHERE `note_type_id` IS NULL
  AND EXISTS (SELECT 1 FROM `note_types`);

ALTER TABLE `notes` MODIFY `property_id` bigint(20) unsigned DEFAULT NULL;
ALTER TABLE `notes` MODIFY `user_id` bigint(20) unsigned DEFAULT NULL;
ALTER TABLE `notes` MODIFY `type` varchar(50) DEFAULT NULL;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND INDEX_NAME = 'notes_noteable_index'
        ),
        'SELECT ''skip notes_noteable_index''',
        'ALTER TABLE `notes` ADD INDEX `notes_noteable_index` (`noteable_type`, `noteable_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND INDEX_NAME = 'notes_note_type_id_index'
        ),
        'SELECT ''skip notes_note_type_id_index''',
        'ALTER TABLE `notes` ADD INDEX `notes_note_type_id_index` (`note_type_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- privacy_breach_logs
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `privacy_breach_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `account_id` bigint(20) unsigned DEFAULT NULL,
    `severity` varchar(32) NOT NULL DEFAULT 'low',
    `status` varchar(32) NOT NULL DEFAULT 'open',
    `summary` varchar(255) NOT NULL,
    `details` text DEFAULT NULL,
    `discovered_at` timestamp NULL DEFAULT NULL,
    `contained_at` timestamp NULL DEFAULT NULL,
    `notified_at` timestamp NULL DEFAULT NULL,
    `recorded_by` bigint(20) unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `privacy_breach_logs_account_id_index` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tenancies: rent due day and automatic invoices
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancies' AND COLUMN_NAME = 'rent_due_day'
        ),
        'SELECT ''skip tenancies.rent_due_day''',
        'ALTER TABLE `tenancies` ADD COLUMN `rent_due_day` tinyint(3) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancies' AND COLUMN_NAME = 'rent_auto_invoice'
        ),
        'SELECT ''skip tenancies.rent_auto_invoice''',
        'ALTER TABLE `tenancies` ADD COLUMN `rent_auto_invoice` tinyint(1) NOT NULL DEFAULT 0'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancies' AND COLUMN_NAME = 'rent_auto_invoice_tenant_user_id'
        ),
        'SELECT ''skip tenancies.rent_auto_invoice_tenant_user_id''',
        'ALTER TABLE `tenancies` ADD COLUMN `rent_auto_invoice_tenant_user_id` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancies' AND COLUMN_NAME = 'rent_next_period_start'
        ),
        'SELECT ''skip tenancies.rent_next_period_start''',
        'ALTER TABLE `tenancies` ADD COLUMN `rent_next_period_start` date DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- documents.title
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'title'
        ),
        'SELECT ''skip documents.title''',
        'ALTER TABLE `documents` ADD COLUMN `title` varchar(255) DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- compliance_records: served to tenant
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'compliance_records' AND COLUMN_NAME = 'served_to_tenant_at'
        ),
        'SELECT ''skip compliance_records.served_to_tenant_at''',
        'ALTER TABLE `compliance_records` ADD COLUMN `served_to_tenant_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'compliance_records' AND COLUMN_NAME = 'served_notes'
        ),
        'SELECT ''skip compliance_records.served_notes''',
        'ALTER TABLE `compliance_records` ADD COLUMN `served_notes` varchar(1000) DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- repair_issues: landlord note, complaint, SLA
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'landlord_note'
        ),
        'SELECT ''skip repair_issues.landlord_note''',
        'ALTER TABLE `repair_issues` ADD COLUMN `landlord_note` varchar(500) DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'complaint_code'
        ),
        'SELECT ''skip repair_issues.complaint_code''',
        'ALTER TABLE `repair_issues` ADD COLUMN `complaint_code` varchar(191) DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND INDEX_NAME = 'repair_issues_complaint_code_index'
        ),
        'SELECT ''skip repair_issues_complaint_code_index''',
        'ALTER TABLE `repair_issues` ADD INDEX `repair_issues_complaint_code_index` (`complaint_code`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'classification_snapshot'
        ),
        'SELECT ''skip repair_issues.classification_snapshot''',
        'ALTER TABLE `repair_issues` ADD COLUMN `classification_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`classification_snapshot`))'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'sla_due_at'
        ),
        'SELECT ''skip repair_issues.sla_due_at''',
        'ALTER TABLE `repair_issues` ADD COLUMN `sla_due_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND INDEX_NAME = 'repair_issues_sla_due_at_index'
        ),
        'SELECT ''skip repair_issues_sla_due_at_index''',
        'ALTER TABLE `repair_issues` ADD INDEX `repair_issues_sla_due_at_index` (`sla_due_at`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'make_safe_due_at'
        ),
        'SELECT ''skip repair_issues.make_safe_due_at''',
        'ALTER TABLE `repair_issues` ADD COLUMN `make_safe_due_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'emergency_access'
        ),
        'SELECT ''skip repair_issues.emergency_access''',
        'ALTER TABLE `repair_issues` ADD COLUMN `emergency_access` tinyint(1) NOT NULL DEFAULT 0'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'reported_at'
        ),
        'SELECT ''skip repair_issues.reported_at''',
        'ALTER TABLE `repair_issues` ADD COLUMN `reported_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'dispatched_at'
        ),
        'SELECT ''skip repair_issues.dispatched_at''',
        'ALTER TABLE `repair_issues` ADD COLUMN `dispatched_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'dispatched_by'
        ),
        'SELECT ''skip repair_issues.dispatched_by''',
        'ALTER TABLE `repair_issues` ADD COLUMN `dispatched_by` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'make_safe_at'
        ),
        'SELECT ''skip repair_issues.make_safe_at''',
        'ALTER TABLE `repair_issues` ADD COLUMN `make_safe_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'make_safe_by'
        ),
        'SELECT ''skip repair_issues.make_safe_by''',
        'ALTER TABLE `repair_issues` ADD COLUMN `make_safe_by` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'resolved_at'
        ),
        'SELECT ''skip repair_issues.resolved_at''',
        'ALTER TABLE `repair_issues` ADD COLUMN `resolved_at` timestamp NULL DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'resolved_by'
        ),
        'SELECT ''skip repair_issues.resolved_by''',
        'ALTER TABLE `repair_issues` ADD COLUMN `resolved_by` bigint(20) unsigned DEFAULT NULL'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `repair_sla_events` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `repair_issue_id` bigint(20) unsigned NOT NULL,
    `event` varchar(191) NOT NULL,
    `occurred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `actor_id` bigint(20) unsigned DEFAULT NULL,
    `note` text DEFAULT NULL,
    `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `repair_sla_events_repair_issue_id_event_index` (`repair_issue_id`, `event`),
    CONSTRAINT `repair_sla_events_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- events.visible_to_tenant
-- ---------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'visible_to_tenant'
        ),
        'SELECT ''skip events.visible_to_tenant''',
        'ALTER TABLE `events` ADD COLUMN `visible_to_tenant` tinyint(1) NOT NULL DEFAULT 0'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- Permissions added in RoleAndPermissionSeeder since ee4555c.
-- Super Admin and Landlord receive both. Other roles are left unchanged.
-- ---------------------------------------------------------------------------
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'manage tenancies', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'manage tenancies' AND `guard_name` = 'web'
);

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'view property repair', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'view property repair' AND `guard_name` = 'web'
);

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
INNER JOIN `roles` r ON r.name IN ('Super Admin', 'Landlord') AND r.guard_name = 'web'
WHERE p.name IN ('manage tenancies', 'view property repair')
  AND p.guard_name = 'web'
  AND NOT EXISTS (
      SELECT 1 FROM `role_has_permissions` rhp
      WHERE rhp.permission_id = p.id AND rhp.role_id = r.id
  );

-- ---------------------------------------------------------------------------
-- Mark the migrations applied so `php artisan migrate` skips them.
-- ---------------------------------------------------------------------------
SET @rs_batch := (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_18_150000_add_tenancy_detail_confirmation', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_18_150000_add_tenancy_detail_confirmation');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_160000_add_account_id_to_bank_details', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_160000_add_account_id_to_bank_details');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_160000_align_notes_polymorphic_columns', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_160000_align_notes_polymorphic_columns');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_170000_create_privacy_breach_logs_table', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_170000_create_privacy_breach_logs_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_180000_add_rent_auto_invoice_to_tenancies', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_180000_add_rent_auto_invoice_to_tenancies');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_190000_add_title_to_documents', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_190000_add_title_to_documents');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_200000_add_served_to_tenant_to_compliance_records', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_200000_add_served_to_tenant_to_compliance_records');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_23_170000_add_priority_complaint_sla_to_repair_issues', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_23_170000_add_priority_complaint_sla_to_repair_issues');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_24_160000_add_rent_due_day_to_tenancies', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_24_160000_add_rent_due_day_to_tenancies');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_24_170000_add_landlord_note_to_repair_issues', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_24_170000_add_landlord_note_to_repair_issues');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_24_180000_add_visible_to_tenant_to_events', @rs_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_24_180000_add_visible_to_tenant_to_events');

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- Quick check. Each of these should return 1.
SELECT 'tenant_members.details_status' AS item, COUNT(*) AS present
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenant_members' AND COLUMN_NAME = 'details_status'
UNION ALL
SELECT 'tenancy_correction_requests', COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancy_correction_requests'
UNION ALL
SELECT 'bank_details.account_id', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_details' AND COLUMN_NAME = 'account_id'
UNION ALL
SELECT 'notes.noteable_type', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = 'noteable_type'
UNION ALL
SELECT 'privacy_breach_logs', COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'privacy_breach_logs'
UNION ALL
SELECT 'tenancies.rent_due_day', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancies' AND COLUMN_NAME = 'rent_due_day'
UNION ALL
SELECT 'tenancies.rent_auto_invoice', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenancies' AND COLUMN_NAME = 'rent_auto_invoice'
UNION ALL
SELECT 'documents.title', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'title'
UNION ALL
SELECT 'compliance_records.served_to_tenant_at', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'compliance_records' AND COLUMN_NAME = 'served_to_tenant_at'
UNION ALL
SELECT 'repair_issues.landlord_note', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_issues' AND COLUMN_NAME = 'landlord_note'
UNION ALL
SELECT 'repair_sla_events', COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repair_sla_events'
UNION ALL
SELECT 'events.visible_to_tenant', COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'visible_to_tenant';
