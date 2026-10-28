-- JFL Solar website — database update for the 2026-10-28 release (Admin → Page text).
-- For a site that is ALREADY installed: in phpMyAdmin, select the website's database
-- and Import this file once, before or right after uploading the new release.
-- It only ADDS one empty table; no existing data is changed or removed.
-- (New installs do not need it: database-schema.sql already includes the table.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `page_texts` (
  `key` varchar(80) NOT NULL,
  `value` text NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `page_texts_updated_by_foreign` (`updated_by`),
  CONSTRAINT `page_texts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record the migration so a later `php artisan migrate` (if SSH is ever available) skips it.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_28_000100_create_page_texts_table', n.next_batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS next_batch FROM `migrations`) AS n
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_28_000100_create_page_texts_table');
