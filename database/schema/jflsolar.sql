-- JFL Solar website — database structure (generated from the Laravel migrations).
-- First install: in phpMyAdmin, select the empty database and Import this file.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
/*M!999999\- enable the sandbox mode */ 
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_blocks` (
  `key` varchar(60) NOT NULL,
  `section` varchar(20) NOT NULL,
  `title` varchar(160) NOT NULL,
  `subtitle` varchar(200) DEFAULT NULL,
  `body` text NOT NULL,
  `claim_status` varchar(20) NOT NULL DEFAULT 'needs_confirmation',
  `review_note` text DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `lock_version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(254) DEFAULT NULL,
  `inquiry_type` varchar(40) NOT NULL,
  `location` varchar(120) DEFAULT NULL,
  `bill_range` varchar(20) DEFAULT NULL,
  `item_type` varchar(10) DEFAULT NULL,
  `item_slug` varchar(80) DEFAULT NULL,
  `item_label` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'new',
  `staff_notes` text DEFAULT NULL,
  `emailed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inquiries_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_assets` (
  `id` char(36) NOT NULL,
  `kind` varchar(10) NOT NULL,
  `storage_key` varchar(100) NOT NULL,
  `package_id` char(36) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `project_id` char(36) DEFAULT NULL,
  `content_block_key` varchar(60) DEFAULT NULL,
  `alt` varchar(300) NOT NULL DEFAULT '',
  `caption` varchar(300) DEFAULT NULL,
  `file_name` varchar(200) DEFAULT NULL,
  `widths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widths`)),
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `bytes` int(10) unsigned DEFAULT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_assets_storage_key_unique` (`storage_key`),
  KEY `media_assets_package_id_foreign` (`package_id`),
  KEY `media_assets_product_id_foreign` (`product_id`),
  KEY `media_assets_project_id_foreign` (`project_id`),
  KEY `media_assets_content_block_key_foreign` (`content_block_key`),
  CONSTRAINT `media_assets_content_block_key_foreign` FOREIGN KEY (`content_block_key`) REFERENCES `content_blocks` (`key`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `media_assets_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_assets_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_assets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `packages` (
  `id` char(36) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `summary` varchar(300) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `package_type` varchar(20) NOT NULL,
  `system_type` varchar(10) DEFAULT NULL,
  `inverter_kw` decimal(8,2) DEFAULT NULL,
  `array_kwp` decimal(8,2) DEFAULT NULL,
  `battery_kwh` decimal(8,2) DEFAULT NULL,
  `components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`components`)),
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`inclusions`)),
  `exclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`exclusions`)),
  `pricing_mode` varchar(10) NOT NULL DEFAULT 'quote',
  `price_php` decimal(12,2) DEFAULT NULL,
  `price_basis` varchar(200) DEFAULT NULL,
  `price_is_sample` tinyint(1) NOT NULL DEFAULT 0,
  `installation_terms` text DEFAULT NULL,
  `fulfillment` varchar(20) NOT NULL DEFAULT 'to_be_confirmed',
  `fulfillment_notes` varchar(600) DEFAULT NULL,
  `promo_label` varchar(80) DEFAULT NULL,
  `promo_start` date DEFAULT NULL,
  `promo_end` date DEFAULT NULL,
  `hide_when_expired` tinyint(1) NOT NULL DEFAULT 0,
  `warranty_terms` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_sample` tinyint(1) NOT NULL DEFAULT 0,
  `lock_version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packages_slug_unique` (`slug`),
  UNIQUE KEY `packages_sku_unique` (`sku`),
  KEY `packages_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_texts` (
  `key` varchar(80) NOT NULL,
  `value` text NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `page_texts_updated_by_foreign` (`updated_by`),
  CONSTRAINT `page_texts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` char(36) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'published',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `lock_version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` char(36) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `name` varchar(140) NOT NULL,
  `brand` varchar(60) DEFAULT NULL,
  `model` varchar(80) DEFAULT NULL,
  `category_id` char(36) NOT NULL,
  `summary` varchar(300) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`specs`)),
  `pricing_mode` varchar(10) NOT NULL DEFAULT 'quote',
  `price_php` decimal(12,2) DEFAULT NULL,
  `price_basis` varchar(200) DEFAULT NULL,
  `price_is_sample` tinyint(1) NOT NULL DEFAULT 0,
  `availability` varchar(20) NOT NULL DEFAULT 'on_request',
  `warranty_terms` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_sample` tinyint(1) NOT NULL DEFAULT 0,
  `lock_version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_status_category_id_sort_order_index` (`status`,`category_id`,`sort_order`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` char(36) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `title` varchar(140) NOT NULL,
  `location` varchar(120) DEFAULT NULL,
  `project_type` varchar(20) NOT NULL,
  `system_size_kwp` decimal(8,2) DEFAULT NULL,
  `system_size_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `scope` varchar(600) DEFAULT NULL,
  `summary` varchar(300) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `video_urls` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_sample` tinyint(1) NOT NULL DEFAULT 0,
  `lock_version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_slug_unique` (`slug`),
  KEY `projects_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` tinyint(3) unsigned NOT NULL,
  `phone_display` varchar(40) NOT NULL,
  `phone_e164` varchar(16) NOT NULL,
  `messaging_display` varchar(40) DEFAULT NULL,
  `whatsapp_e164` varchar(16) DEFAULT NULL,
  `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `viber_e164` varchar(16) DEFAULT NULL,
  `viber_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `facebook_url` varchar(300) DEFAULT NULL,
  `facebook_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `messenger_url` varchar(300) DEFAULT NULL,
  `messenger_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `public_email` varchar(254) DEFAULT NULL,
  `office_address` varchar(300) NOT NULL,
  `warehouse_address` varchar(300) DEFAULT NULL,
  `service_area` varchar(300) NOT NULL,
  `service_area_note` text DEFAULT NULL,
  `business_hours` varchar(300) DEFAULT NULL,
  `general_warranty` text DEFAULT NULL,
  `lock_version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'editor',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
set autocommit=0;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_10_17_000100_create_site_tables',1),
(5,'2026_10_28_000100_create_page_texts_table',1),
(6,'2026_10_29_000100_add_video_urls_to_projects',1);
commit;
SET FOREIGN_KEY_CHECKS = 1;
