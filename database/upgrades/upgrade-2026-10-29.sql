-- JFL Solar website — database update for the 2026-10-29 release (project video links).
-- For a site that is ALREADY installed: in phpMyAdmin, select the website's database
-- and Import this file once. It only ADDS one empty column to `projects`;
-- no existing data is changed or removed. Safe to import twice.
-- (New installs do not need it: database-schema.sql already includes the column.)
SET NAMES utf8mb4;

ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `video_urls` text DEFAULT NULL AFTER `description`;

-- Record the migration so a later `php artisan migrate` (if SSH is ever available) skips it.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_29_000100_add_video_urls_to_projects', n.next_batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS next_batch FROM `migrations`) AS n
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_29_000100_add_video_urls_to_projects');
