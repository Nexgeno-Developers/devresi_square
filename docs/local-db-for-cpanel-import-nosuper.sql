-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: resisquare_laravel_webdeveloper
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `resisquare_laravel_webdeveloper`
--



--
-- Table structure for table `account_headers`
--

DROP TABLE IF EXISTS `account_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_headers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `charge_on` enum('property','tenancy','landlord','contractor','applicants','all') NOT NULL,
  `who_can_view` enum('tenant','owner','contractor','everyone') NOT NULL DEFAULT 'everyone',
  `reminders` tinyint(1) NOT NULL DEFAULT 0,
  `agent_fees` tinyint(1) NOT NULL DEFAULT 0,
  `require_bank_details` tinyint(1) NOT NULL DEFAULT 0,
  `charge_in` enum('arrears','advance','anytime') NOT NULL DEFAULT 'anytime',
  `can_have_duration` tinyint(1) NOT NULL DEFAULT 0,
  `settle_through` enum('credit_note','debit_note','refund','all') NOT NULL DEFAULT 'all',
  `duration_parameter_required` tinyint(1) NOT NULL DEFAULT 0,
  `penalty_type` enum('percentage','flat_rate') DEFAULT NULL,
  `tax_included` tinyint(1) NOT NULL DEFAULT 0,
  `tax_type` enum('percentage','flat_rate') DEFAULT NULL,
  `transaction_between` enum('tenant_landlord','landlord_agent','agent_contractor','internal_staff_agent','landlord_contractor','landlord_management','all') NOT NULL DEFAULT 'all',
  `accrue` tinyint(1) NOT NULL DEFAULT 0,
  `bank_id` bigint(20) unsigned DEFAULT NULL,
  `penalty_frequency` enum('annual','monthly') DEFAULT NULL,
  `penalty_due_type` enum('instant','days_after_invoice','custom') DEFAULT NULL,
  `penalty_due_days` int(11) DEFAULT NULL,
  `include_tenancy_period` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_headers_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_headers`
--

LOCK TABLES `account_headers` WRITE;
/*!40000 ALTER TABLE `account_headers` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_headers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_subscription_addons`
--

DROP TABLE IF EXISTS `account_subscription_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_subscription_addons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_subscription_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `addon_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `billing_cycle` enum('monthly','annual') NOT NULL,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `stripe_subscription_item_id` varchar(191) DEFAULT NULL,
  `stripe_price_id` varchar(191) DEFAULT NULL,
  `price_at_purchase_minor` int(11) DEFAULT NULL,
  `addon_name_at_purchase` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_subscription_addons_account_id_status_index` (`account_id`,`status`),
  KEY `account_subscription_addons_account_subscription_id_index` (`account_subscription_id`),
  KEY `account_subscription_addons_addon_id_index` (`addon_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_subscription_addons`
--

LOCK TABLES `account_subscription_addons` WRITE;
/*!40000 ALTER TABLE `account_subscription_addons` DISABLE KEYS */;
INSERT INTO `account_subscription_addons` VALUES (1,1,1,4,1,'monthly','active',NULL,NULL,1500,'Property Manager','2026-09-10 11:54:39','2026-09-10 11:54:39'),(2,2,2,1,1,'annual','cancelled',NULL,NULL,5000,'Extra Property','2026-09-10 11:54:39','2026-09-10 11:54:39');
/*!40000 ALTER TABLE `account_subscription_addons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_subscriptions`
--

DROP TABLE IF EXISTS `account_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle` enum('monthly','annual') NOT NULL,
  `status` enum('trialing','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trialing',
  `trial_started_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `current_period_start` timestamp NULL DEFAULT NULL,
  `current_period_end` timestamp NULL DEFAULT NULL,
  `stripe_subscription_id` varchar(191) DEFAULT NULL,
  `stripe_price_id` varchar(191) DEFAULT NULL,
  `price_at_signup_minor` int(11) DEFAULT NULL,
  `currency_at_signup` varchar(191) NOT NULL DEFAULT 'GBP',
  `plan_name_at_signup` varchar(191) DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_subscriptions_account_id_status_index` (`account_id`,`status`),
  KEY `account_subscriptions_plan_id_index` (`plan_id`),
  KEY `account_subscriptions_stripe_subscription_id_index` (`stripe_subscription_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_subscriptions`
--

LOCK TABLES `account_subscriptions` WRITE;
/*!40000 ALTER TABLE `account_subscriptions` DISABLE KEYS */;
INSERT INTO `account_subscriptions` VALUES (1,1,1,'monthly','trialing','2026-09-10 11:54:39','2026-09-17 11:54:39','2026-09-10 11:54:39','2026-10-10 11:54:39',NULL,NULL,2900,'GBP','Landlord Basic',0,NULL,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(2,2,2,'annual','trialing','2026-09-10 11:54:39','2026-09-17 11:54:39','2026-09-10 11:54:39','2027-09-10 11:54:39',NULL,NULL,149000,'GBP','Estate Agent Company',0,NULL,'2026-09-10 11:54:39','2026-09-10 11:54:39');
/*!40000 ALTER TABLE `account_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_users`
--

DROP TABLE IF EXISTS `account_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `member_type` enum('owner','admin','staff','contact','landlord','owner_contact','tenant','contractor','property_manager') NOT NULL,
  `access_level` enum('full','edit','view','no_login') NOT NULL DEFAULT 'view',
  `can_login` tinyint(1) NOT NULL DEFAULT 0,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','invited','disabled') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_users_account_id_user_id_unique` (`account_id`,`user_id`),
  KEY `account_users_account_id_member_type_index` (`account_id`,`member_type`),
  KEY `account_users_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_users`
--

LOCK TABLES `account_users` WRITE;
/*!40000 ALTER TABLE `account_users` DISABLE KEYS */;
INSERT INTO `account_users` VALUES (1,1,6,'owner','full',1,NULL,NULL,'active',5,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(2,1,9,'landlord','view',1,NULL,NULL,'active',6,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(3,1,10,'tenant','view',1,NULL,NULL,'active',6,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(4,1,11,'contractor','view',1,NULL,NULL,'active',6,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(5,1,12,'property_manager','full',1,NULL,NULL,'active',6,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(6,2,7,'owner','full',1,NULL,NULL,'active',5,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(7,2,8,'staff','edit',1,NULL,NULL,'active',7,'2026-09-10 11:54:39','2026-09-10 11:54:39'),(8,1,14,'tenant','view',1,NULL,NULL,'active',6,'2026-09-14 09:59:29','2026-09-14 09:59:29');
/*!40000 ALTER TABLE `account_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint(20) unsigned NOT NULL,
  `account_type` enum('landlord','estate_agent_freelance','estate_agent_company') NOT NULL,
  `account_name` varchar(191) DEFAULT NULL,
  `billing_email` varchar(191) DEFAULT NULL,
  `billing_phone` varchar(191) DEFAULT NULL,
  `currency` varchar(191) NOT NULL DEFAULT 'GBP',
  `timezone` varchar(64) NOT NULL DEFAULT 'Europe/London',
  `status` enum('trialing','active','past_due','suspended','cancelled') NOT NULL DEFAULT 'trialing',
  `trial_started_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `stripe_customer_id` varchar(191) DEFAULT NULL,
  `registration_welcome_email_sent_at` timestamp NULL DEFAULT NULL,
  `subscription_activation_email_sent_at` timestamp NULL DEFAULT NULL,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `onboarding_step` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `onboarding_property_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status_reason` text DEFAULT NULL,
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `status_changed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_owner_user_id_index` (`owner_user_id`),
  KEY `accounts_account_type_index` (`account_type`),
  KEY `accounts_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,6,'landlord','Staging Landlord Account','landlord.owner@resisquare.test','+440000000000','GBP','Europe/London','trialing','2026-09-10 11:54:39','2026-09-17 11:54:39',NULL,NULL,NULL,'2026-09-14 10:03:12',1,4,'2026-09-10 11:54:39','2026-09-14 10:03:50',NULL,NULL,NULL),(2,7,'estate_agent_company','Staging Estate Agent Account','estate.owner@resisquare.test','+440000000000','GBP','Europe/London','trialing','2026-09-10 11:54:39','2026-09-17 11:54:39',NULL,NULL,NULL,NULL,1,NULL,'2026-09-10 11:54:39','2026-09-10 11:54:39',NULL,NULL,NULL);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addons`
--

DROP TABLE IF EXISTS `addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `addon_type` enum('property','branch','staff','property_manager') NOT NULL,
  `monthly_price_minor` int(11) NOT NULL DEFAULT 0,
  `annual_price_minor` int(11) NOT NULL DEFAULT 0,
  `currency` varchar(191) NOT NULL DEFAULT 'GBP',
  `stripe_monthly_price_id` varchar(191) DEFAULT NULL,
  `stripe_annual_price_id` varchar(191) DEFAULT NULL,
  `grant_quantity` int(11) NOT NULL DEFAULT 1,
  `is_stackable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `addons_code_unique` (`code`),
  KEY `addons_addon_type_index` (`addon_type`),
  KEY `addons_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addons`
--

LOCK TABLES `addons` WRITE;
/*!40000 ALTER TABLE `addons` DISABLE KEYS */;
INSERT INTO `addons` VALUES (1,'extra_property','Extra Property','property',500,5000,'GBP',NULL,NULL,1,1,1,'2026-09-10 11:54:31','2026-09-10 11:54:31'),(2,'extra_branch','Extra Branch','branch',2000,20000,'GBP',NULL,NULL,1,1,1,'2026-09-10 11:54:31','2026-09-10 11:54:31'),(3,'extra_staff','Extra Staff','staff',1000,10000,'GBP',NULL,NULL,1,1,1,'2026-09-10 11:54:31','2026-09-10 11:54:31'),(4,'property_manager','Property Manager','property_manager',1500,15000,'GBP',NULL,NULL,1,1,1,'2026-09-10 11:54:31','2026-09-10 11:54:31');
/*!40000 ALTER TABLE `addons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `all_note_refunds`
--

DROP TABLE IF EXISTS `all_note_refunds`;
/*!50001 DROP VIEW IF EXISTS `all_note_refunds`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `all_note_refunds` AS SELECT
 1 AS `unified_id`,
  1 AS `note_kind`,
  1 AS `refund_id`,
  1 AS `note_id`,
  1 AS `note_number`,
  1 AS `amount`,
  1 AS `refund_date`,
  1 AS `status`,
  1 AS `transaction_number`,
  1 AS `reference`,
  1 AS `notes`,
  1 AS `processed_by`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `audits`
--

DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(191) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(191) NOT NULL,
  `auditable_type` varchar(191) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(1023) DEFAULT NULL,
  `tags` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audits_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audits_user_id_user_type_index` (`user_id`,`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audits`
--

LOCK TABLES `audits` WRITE;
/*!40000 ALTER TABLE `audits` DISABLE KEYS */;
INSERT INTO `audits` VALUES (1,'App\\Models\\User',6,'updated','App\\Models\\Account',1,'{\"onboarding_completed_at\":null,\"onboarding_step\":1}','{\"onboarding_completed_at\":\"2026-09-10 17:25:38\",\"onboarding_step\":3}','http://127.0.0.1:8001/admin/dashboard','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/3.18.9 Chrome/144.0.7559.236 Electron/40.10.3 Safari/537.36',NULL,'2026-09-10 11:55:38','2026-09-10 11:55:38'),(2,'App\\Models\\User',6,'updated','App\\Models\\Account',1,'{\"onboarding_step\":3}','{\"onboarding_step\":1}','http://127.0.0.1:8000/admin/onboarding/landlord/step','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 09:36:17','2026-09-14 09:36:17'),(3,'App\\Models\\User',6,'created','App\\Models\\AccountUser',8,'[]','{\"account_id\":1,\"user_id\":14,\"member_type\":\"tenant\",\"access_level\":\"view\",\"can_login\":true,\"status\":\"active\",\"created_by\":6,\"id\":8}','http://127.0.0.1:8000/admin/users/quick-store-user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 09:59:30','2026-09-14 09:59:30'),(4,'App\\Models\\User',6,'updated','App\\Models\\Account',1,'{\"onboarding_step\":1,\"onboarding_property_id\":null}','{\"onboarding_step\":2,\"onboarding_property_id\":4}','http://127.0.0.1:8000/admin/onboarding/landlord/property','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 10:01:59','2026-09-14 10:01:59'),(5,'App\\Models\\User',6,'updated','App\\Models\\Account',1,'{\"onboarding_step\":2}','{\"onboarding_step\":3}','http://127.0.0.1:8000/admin/onboarding/landlord/owners','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 10:02:08','2026-09-14 10:02:08'),(6,'App\\Models\\User',6,'created','App\\Models\\PropertyParticipant',5,'[]','{\"account_id\":1,\"property_id\":4,\"user_id\":14,\"participant_type\":\"tenant\",\"access_level\":\"view\",\"can_view_finance\":false,\"can_view_documents\":true,\"can_upload_documents\":false,\"status\":\"active\",\"created_by\":6,\"id\":5}','http://127.0.0.1:8000/admin/onboarding/landlord/tenancy','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 10:02:54','2026-09-14 10:02:54'),(7,'App\\Models\\User',6,'created','App\\Models\\Event',1,'[]','{\"title\":\"Move-in \\u2014 Flat 108, 1 Baltimore Wharf, London, E14 9RU\",\"type_id\":9,\"sub_type_id\":29,\"status\":\"Scheduled\",\"diary_owner\":6,\"on_behalf_of\":6,\"location\":\"Move-in \\u2014 Flat 108, 1 Baltimore Wharf, London, E14 9RU\",\"description\":\"Created when the tenancy was set up.\",\"start_datetime\":\"2026-09-09 10:00:00\",\"end_datetime\":\"2026-09-09 11:00:00\"}','http://127.0.0.1:8000/admin/onboarding/landlord/tenancy','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 10:02:54','2026-09-14 10:02:54'),(8,'App\\Models\\User',6,'updated','App\\Models\\Account',1,'{\"onboarding_completed_at\":\"2026-09-10 17:25:38\"}','{\"onboarding_completed_at\":\"2026-09-14 15:33:12\"}','http://127.0.0.1:8000/admin/onboarding/landlord/complete','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 10:03:12','2026-09-14 10:03:12'),(9,'App\\Models\\User',6,'updated','App\\Models\\Account',1,'{\"onboarding_step\":3}','{\"onboarding_step\":1}','http://127.0.0.1:8000/admin/onboarding/landlord/step','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',NULL,'2026-09-14 10:03:50','2026-09-14 10:03:50');
/*!40000 ALTER TABLE `audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `account_name` varchar(191) DEFAULT NULL,
  `account_no` varchar(191) DEFAULT NULL,
  `sort_code` varchar(191) DEFAULT NULL,
  `bank_name` varchar(191) DEFAULT NULL,
  `swift_code` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `branch` varchar(191) DEFAULT NULL,
  `ifsc_code` varchar(191) DEFAULT NULL,
  `account_type` varchar(191) DEFAULT NULL,
  `purpose` varchar(191) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_type` enum('savings','current','overdraft') NOT NULL DEFAULT 'savings',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `bank_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_details`
--

DROP TABLE IF EXISTS `bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `account_name` varchar(191) NOT NULL,
  `account_no` varchar(191) NOT NULL,
  `sort_code` varchar(191) NOT NULL,
  `bank_name` varchar(191) NOT NULL,
  `swift_code` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_details_user_id_foreign` (`user_id`),
  CONSTRAINT `bank_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_details`
--

LOCK TABLES `bank_details` WRITE;
/*!40000 ALTER TABLE `bank_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliation_lines`
--

DROP TABLE IF EXISTS `bank_reconciliation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_reconciliation_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_reconciliation_id` bigint(20) unsigned NOT NULL,
  `gl_journal_line_id` bigint(20) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_matched` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_reconciliation_lines_bank_reconciliation_id_foreign` (`bank_reconciliation_id`),
  KEY `bank_reconciliation_lines_gl_journal_line_id_foreign` (`gl_journal_line_id`),
  KEY `bank_rec_lines_account_idx` (`account_id`),
  CONSTRAINT `bank_reconciliation_lines_bank_reconciliation_id_foreign` FOREIGN KEY (`bank_reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_reconciliation_lines_gl_journal_line_id_foreign` FOREIGN KEY (`gl_journal_line_id`) REFERENCES `gl_journal_lines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliation_lines`
--

LOCK TABLES `bank_reconciliation_lines` WRITE;
/*!40000 ALTER TABLE `bank_reconciliation_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliation_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sys_bank_account_id` bigint(20) unsigned NOT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gl_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','reconciled') NOT NULL DEFAULT 'draft',
  `reconciled_by` bigint(20) unsigned DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_reconciliations_sys_bank_account_id_foreign` (`sys_bank_account_id`),
  KEY `bank_reconciliations_reconciled_by_foreign` (`reconciled_by`),
  KEY `bank_recs_account_idx` (`account_id`),
  CONSTRAINT `bank_reconciliations_reconciled_by_foreign` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_reconciliations_sys_bank_account_id_foreign` FOREIGN KEY (`sys_bank_account_id`) REFERENCES `sys_bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliations`
--

LOCK TABLES `bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `bank_reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `is_main_head_office` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(191) DEFAULT NULL,
  `address_line_1` varchar(191) DEFAULT NULL,
  `address_line_2` varchar(191) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `county` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `country` varchar(191) NOT NULL DEFAULT 'UK',
  `user_email` varchar(191) DEFAULT NULL,
  `user_phone` varchar(191) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `alternate_phone` varchar(191) DEFAULT NULL,
  `alternate_email` varchar(191) DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media`)),
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `branches_created_by_foreign` (`created_by`),
  KEY `branches_account_id_idx` (`account_id`),
  CONSTRAINT `branches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,2,1,'Staging Head Office','20 Staging Branch Road',NULL,'20 Staging Branch Road','London',NULL,'ST3 3AA','UK','estate.office@resisquare.test','+440000000001',7,'2026-09-10 11:54:40','2026-09-10 11:54:40',NULL,NULL,NULL,2,'active');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_settings`
--

DROP TABLE IF EXISTS `business_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  `lang` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_settings_type_index` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_settings`
--

LOCK TABLES `business_settings` WRITE;
/*!40000 ALTER TABLE `business_settings` DISABLE KEYS */;
INSERT INTO `business_settings` VALUES (1,'default_ar_account_id',NULL,NULL,'2026-09-10 11:52:18','2026-09-10 11:52:18'),(2,'default_ap_account_id',NULL,NULL,'2026-09-10 11:52:18','2026-09-10 11:52:18'),(3,'default_revenue_account_id',NULL,NULL,'2026-09-10 11:52:18','2026-09-10 11:52:18'),(4,'default_expense_account_id',NULL,NULL,'2026-09-10 11:52:18','2026-09-10 11:52:18');
/*!40000 ALTER TABLE `business_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('all_permissions','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:10:{i:0;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:1;s:4:\"name\";s:23:\"view accounting reports\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:1;s:4:\"name\";s:23:\"view accounting reports\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:1;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:2;s:4:\"name\";s:18:\"manage gl accounts\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:2;s:4:\"name\";s:18:\"manage gl accounts\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:2;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:3;s:4:\"name\";s:18:\"manage gl journals\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:3;s:4:\"name\";s:18:\"manage gl journals\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:3;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:4;s:4:\"name\";s:15:\"manage receipts\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:4;s:4:\"name\";s:15:\"manage receipts\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:4;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:5;s:4:\"name\";s:26:\"manage bank reconciliation\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:5;s:4:\"name\";s:26:\"manage bank reconciliation\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:5;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:6;s:4:\"name\";s:19:\"manage fixed assets\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:6;s:4:\"name\";s:19:\"manage fixed assets\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:6;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:7;s:4:\"name\";s:24:\"close accounting periods\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:7;s:4:\"name\";s:24:\"close accounting periods\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:19\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:19\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:7;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:8;s:4:\"name\";s:18:\"manage own company\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:20\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:20\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:8;s:4:\"name\";s:18:\"manage own company\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:20\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:20\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:8;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:9;s:4:\"name\";s:22:\"transfer company owner\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:20\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:20\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:9;s:4:\"name\";s:22:\"transfer company owner\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:20\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:20\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}i:9;O:35:\"Spatie\\Permission\\Models\\Permission\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:11:\"permissions\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:5:{s:2:\"id\";i:10;s:4:\"name\";s:26:\"download property brochure\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:20\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:20\";}s:11:\"\0*\0original\";a:5:{s:2:\"id\";i:10;s:4:\"name\";s:26:\"download property brochure\";s:10:\"guard_name\";s:3:\"web\";s:10:\"created_at\";s:19:\"2026-09-10 17:22:20\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:20\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:2:\"id\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',2104401142),('business_settings','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:26:\"App\\Models\\BusinessSetting\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"business_settings\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:4:\"type\";s:21:\"default_ar_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:1;s:4:\"type\";s:21:\"default_ar_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:26:\"App\\Models\\BusinessSetting\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"business_settings\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:2;s:4:\"type\";s:21:\"default_ap_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:2;s:4:\"type\";s:21:\"default_ap_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:26:\"App\\Models\\BusinessSetting\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"business_settings\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:3;s:4:\"type\";s:26:\"default_revenue_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:3;s:4:\"type\";s:26:\"default_revenue_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:26:\"App\\Models\\BusinessSetting\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"business_settings\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:4;s:4:\"type\";s:26:\"default_expense_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:4;s:4:\"type\";s:26:\"default_expense_account_id\";s:5:\"value\";N;s:4:\"lang\";N;s:10:\"created_at\";s:19:\"2026-09-10 17:22:18\";s:10:\"updated_at\";s:19:\"2026-09-10 17:22:18\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1789464859),('c1dfd96eea8cc2b62785275bca38ac261256e278','i:1;',1789380152),('c1dfd96eea8cc2b62785275bca38ac261256e278:timer','i:1789380152;',1789380152),('spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:68:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:23:\"view accounting reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:18:\"manage gl accounts\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:18:\"manage gl journals\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"manage receipts\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:26:\"manage bank reconciliation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:19:\"manage fixed assets\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:24:\"close accounting periods\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:18:\"manage own company\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:22:\"transfer company owner\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:26:\"download property brochure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:20:\"manage registrations\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:14:\"view dashboard\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:15:\"view properties\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:17:\"create properties\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:15:\"edit properties\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:17:\"delete properties\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:20:\"view property owners\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:21:\"view property tenancy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:23:\"view property documents\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:12:\"view tenants\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:6;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:14:\"create tenants\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:12:\"edit tenants\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:14:\"view documents\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;i:4;i:6;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:18:\"view rent payments\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:25:\"view maintenance requests\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;i:4;i:6;i:5;i:8;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:22:\"view communication log\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;i:4;i:6;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:19:\"view own lease info\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:5;i:3;i:8;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:15:\"access settings\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:26:\"manage roles & permissions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:12:\"manage users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:4;i:2;i:6;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:16:\"view own profile\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:5;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:20:\"view office profiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:17:\"view all profiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:29:\"assign properties to landlord\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:14:\"delete tenants\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:26:\"assign tenants to property\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:11:\"end tenancy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:27:\"create maintenance requests\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:4;i:2;i:5;i:3;i:6;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:24:\"assign maintenance tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:25:\"update maintenance status\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:4;i:2;i:8;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:26:\"complete maintenance tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:4;i:2;i:8;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:10:\"view users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:12:\"create users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:10:\"edit users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:12:\"delete users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:22:\"manage user categories\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:16:\"upload documents\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:4;i:2;i:5;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:18:\"download documents\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:16:\"delete documents\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:13:\"view invoices\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:15:\"create invoices\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:13:\"edit invoices\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:15:\"delete invoices\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:17:\"mark invoice paid\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:12:\"view reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:18:\"send notifications\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:13:\"view calendar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:6;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:15:\"view all staffs\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:9:\"add staff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:10:\"edit staff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:12:\"delete staff\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:16:\"view staff roles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:14:\"add staff role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:15:\"edit staff role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:17:\"delete staff role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:13:\"view contacts\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:5;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:20:\"view property repair\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:5;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:22:\"create property repair\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:5;}}}s:5:\"roles\";a:7:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:11:\"Super Admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:8:\"Landlord\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:5:\"Owner\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:16:\"Property Manager\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:6:\"Tenant\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"Estate Agent\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:10:\"Contractor\";s:1:\"c\";s:3:\"web\";}}}',1789464925),('uploaded_asset_','N;',2104740334);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `registration_number` varchar(191) DEFAULT NULL,
  `registered_address` text DEFAULT NULL,
  `communication_address` text DEFAULT NULL,
  `emails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emails`)),
  `phones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phones`)),
  `logo_path` varchar(191) DEFAULT NULL,
  `stamp_path` varchar(191) DEFAULT NULL,
  `vat_number` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media`)),
  `services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_type` enum('agency_company','freelance_profile') DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `companies_created_by_foreign` (`created_by`),
  KEY `companies_updated_by_foreign` (`updated_by`),
  KEY `companies_owner_user_id_foreign` (`owner_user_id`),
  KEY `companies_account_id_idx` (`account_id`),
  CONSTRAINT `companies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,NULL,'Hodkiewicz-Swaniawski',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-10 11:54:29','2026-09-10 11:54:29',NULL,NULL,'active'),(2,7,'Staging Estate Agent Ltd',NULL,NULL,NULL,'[\"estate.owner@resisquare.test\"]','[\"+440000000000\"]',NULL,NULL,NULL,NULL,NULL,NULL,7,7,'2026-09-10 11:54:39','2026-09-10 11:54:39',2,'agency_company','active');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_owner_transfers`
--

DROP TABLE IF EXISTS `company_owner_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_owner_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `old_owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `new_owner_user_id` bigint(20) unsigned NOT NULL,
  `transferred_by` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_owner_transfers_company_id_foreign` (`company_id`),
  KEY `company_owner_transfers_old_owner_user_id_foreign` (`old_owner_user_id`),
  KEY `company_owner_transfers_new_owner_user_id_foreign` (`new_owner_user_id`),
  KEY `company_owner_transfers_transferred_by_foreign` (`transferred_by`),
  CONSTRAINT `company_owner_transfers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_owner_transfers_new_owner_user_id_foreign` FOREIGN KEY (`new_owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_owner_transfers_old_owner_user_id_foreign` FOREIGN KEY (`old_owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_owner_transfers_transferred_by_foreign` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_owner_transfers`
--

LOCK TABLES `company_owner_transfers` WRITE;
/*!40000 ALTER TABLE `company_owner_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_owner_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_details`
--

DROP TABLE IF EXISTS `compliance_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `compliance_record_id` bigint(20) unsigned NOT NULL,
  `key` varchar(191) NOT NULL,
  `value` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_details`
--

LOCK TABLES `compliance_details` WRITE;
/*!40000 ALTER TABLE `compliance_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_records`
--

DROP TABLE IF EXISTS `compliance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `compliance_type_id` bigint(20) unsigned DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `photos` varchar(2000) DEFAULT NULL,
  `status` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `responsible_user_id` bigint(20) unsigned DEFAULT NULL,
  `remediation_due_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_records_property_id_foreign` (`property_id`),
  KEY `compliance_records_compliance_type_id_foreign` (`compliance_type_id`),
  CONSTRAINT `compliance_records_compliance_type_id_foreign` FOREIGN KEY (`compliance_type_id`) REFERENCES `compliance_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compliance_records_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_records`
--

LOCK TABLES `compliance_records` WRITE;
/*!40000 ALTER TABLE `compliance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_types`
--

DROP TABLE IF EXISTS `compliance_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `alias` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_types_name_unique` (`name`),
  UNIQUE KEY `compliance_types_alias_unique` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_types`
--

LOCK TABLES `compliance_types` WRITE;
/*!40000 ALTER TABLE `compliance_types` DISABLE KEYS */;
INSERT INTO `compliance_types` VALUES (1,'Energy Performance Certificate (EPC)','epc','Indicates the energy efficiency of a property.','2026-09-10 11:54:21','2026-09-10 11:54:21',NULL),(2,'Gas Safety Certificate','gas','Required annually for properties with gas appliances.','2026-09-10 11:54:21','2026-09-10 11:54:21',NULL),(3,'Electrical Installation Condition Report (EICR)','eicr','Ensures electrical safety every 5 years.','2026-09-10 11:54:21','2026-09-10 11:54:21',NULL),(4,'Landlord Registration','landlord_registration','Required in some areas for landlords to register.','2026-09-10 11:54:21','2026-09-10 11:54:21',NULL);
/*!40000 ALTER TABLE `compliance_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(2) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `zone_id` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` varchar(50) NOT NULL DEFAULT '',
  `updated_by` varchar(50) NOT NULL DEFAULT '',
  `deleted_by` varchar(50) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'UK','United Kingdom',0,1,'','','','2026-09-10 11:54:24','2026-09-10 11:54:24',NULL),(2,'US','United States',0,1,'','','','2026-09-10 11:54:24','2026-09-10 11:54:24',NULL),(3,'IN','India',0,1,'','','','2026-09-10 11:54:24','2026-09-10 11:54:24',NULL),(4,'AU','Australia',0,1,'','','','2026-09-10 11:54:24','2026-09-10 11:54:24',NULL),(5,'CA','Canada',0,1,'','','','2026-09-10 11:54:24','2026-09-10 11:54:24',NULL);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_note_refunds`
--

DROP TABLE IF EXISTS `credit_note_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_note_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint(20) unsigned NOT NULL,
  `transaction_number` varchar(191) DEFAULT NULL,
  `refund_date` date NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `reference` varchar(191) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_note_refunds_transaction_number_unique` (`transaction_number`),
  KEY `credit_note_refunds_credit_note_id_index` (`credit_note_id`),
  KEY `credit_note_refunds_payment_method_id_index` (`payment_method_id`),
  KEY `credit_note_refunds_bank_account_id_index` (`bank_account_id`),
  KEY `credit_note_refunds_status_index` (`status`),
  KEY `credit_note_refunds_processed_by_index` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_note_refunds`
--

LOCK TABLES `credit_note_refunds` WRITE;
/*!40000 ALTER TABLE `credit_note_refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_note_refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_number` varchar(191) NOT NULL,
  `note_date` date NOT NULL,
  `party_id` bigint(20) unsigned NOT NULL,
  `party_role` enum('client','vendor') DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL,
  `currency` varchar(12) NOT NULL DEFAULT 'GBP',
  `status` enum('draft','applied','refunded','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_notes_note_number_unique` (`note_number`),
  KEY `credit_notes_party_id_index` (`party_id`),
  KEY `credit_notes_status_index` (`status`),
  KEY `credit_notes_account_idx` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_notes`
--

LOCK TABLES `credit_notes` WRITE;
/*!40000 ALTER TABLE `credit_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(3) NOT NULL,
  `symbol` varchar(191) NOT NULL,
  `symbol_position` enum('before','after') NOT NULL DEFAULT 'before',
  `exchange_rate` decimal(10,6) NOT NULL DEFAULT 1.000000,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES (1,'British Pound','GBP','£','before',1.000000,1,1,'2026-09-10 11:54:20','2026-09-10 11:54:20');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `debit_note_refunds`
--

DROP TABLE IF EXISTS `debit_note_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `debit_note_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `debit_note_id` bigint(20) unsigned NOT NULL,
  `transaction_number` varchar(191) DEFAULT NULL,
  `refund_date` date NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `reference` varchar(191) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `debit_note_refunds_transaction_number_unique` (`transaction_number`),
  KEY `debit_note_refunds_debit_note_id_index` (`debit_note_id`),
  KEY `debit_note_refunds_payment_method_id_index` (`payment_method_id`),
  KEY `debit_note_refunds_bank_account_id_index` (`bank_account_id`),
  KEY `debit_note_refunds_status_index` (`status`),
  KEY `debit_note_refunds_processed_by_index` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debit_note_refunds`
--

LOCK TABLES `debit_note_refunds` WRITE;
/*!40000 ALTER TABLE `debit_note_refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `debit_note_refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `debit_notes`
--

DROP TABLE IF EXISTS `debit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `debit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_number` varchar(191) NOT NULL,
  `note_date` date NOT NULL,
  `party_id` bigint(20) unsigned NOT NULL,
  `party_role` enum('client','vendor') DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL,
  `currency` varchar(12) NOT NULL DEFAULT 'GBP',
  `status` enum('draft','applied','refunded','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `debit_notes_note_number_unique` (`note_number`),
  KEY `debit_notes_party_id_index` (`party_id`),
  KEY `debit_notes_status_index` (`status`),
  KEY `debit_notes_account_idx` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debit_notes`
--

LOCK TABLES `debit_notes` WRITE;
/*!40000 ALTER TABLE `debit_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `debit_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `designation_has_permissions`
--

DROP TABLE IF EXISTS `designation_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designation_has_permissions` (
  `designation_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`designation_id`,`permission_id`),
  KEY `designation_has_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `designation_has_permissions_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `designation_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designation_has_permissions`
--

LOCK TABLES `designation_has_permissions` WRITE;
/*!40000 ALTER TABLE `designation_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `designation_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `designations_title_unique` (`title`),
  KEY `designations_account_id_idx` (`account_id`),
  KEY `designations_company_id_idx` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
INSERT INTO `designations` VALUES (1,'Staff','2026-09-10 11:52:20','2026-09-10 11:52:20',NULL,NULL,'active'),(2,'Manager','2026-09-10 11:53:26','2026-09-10 11:53:26',NULL,NULL,'active'),(3,'Negotiator','2026-09-10 11:53:26','2026-09-10 11:53:26',NULL,NULL,'active'),(4,'Lister','2026-09-10 11:53:26','2026-09-10 11:53:26',NULL,NULL,'active'),(5,'Assistant','2026-09-10 11:53:26','2026-09-10 11:53:26',NULL,NULL,'active');
/*!40000 ALTER TABLE `designations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_sequences`
--

DROP TABLE IF EXISTS `document_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(191) NOT NULL,
  `prefix` varchar(20) DEFAULT NULL,
  `next_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_sequences_document_type_branch_id_unique` (`document_type`,`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_sequences`
--

LOCK TABLES `document_sequences` WRITE;
/*!40000 ALTER TABLE `document_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_types`
--

DROP TABLE IF EXISTS `document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_types`
--

LOCK TABLES `document_types` WRITE;
/*!40000 ALTER TABLE `document_types` DISABLE KEYS */;
INSERT INTO `document_types` VALUES (1,'Photo ID','Passport, driving licence, or national identity card.','2026-09-10 11:52:24','2026-09-10 11:52:24'),(2,'Proof of Address','Utility bill, bank statement, or council tax letter dated within 3 months.','2026-09-10 11:52:24','2026-09-10 11:52:24');
/*!40000 ALTER TABLE `document_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `documentable_id` bigint(20) unsigned NOT NULL,
  `documentable_type` varchar(191) NOT NULL,
  `upload_ids` varchar(2000) DEFAULT NULL,
  `document_type_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `visibility` enum('private','shared','portal') NOT NULL DEFAULT 'private',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_documentable_type_documentable_id_index` (`documentable_type`,`documentable_id`),
  KEY `documents_document_type_id_foreign` (`document_type_id`),
  KEY `documents_account_id_idx` (`account_id`),
  CONSTRAINT `documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `receiver` varchar(191) DEFAULT NULL,
  `identifier` varchar(191) NOT NULL,
  `email_type` varchar(191) DEFAULT NULL,
  `subject` text DEFAULT NULL,
  `default_text` longtext DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_status_changeable` tinyint(1) NOT NULL DEFAULT 1,
  `is_dafault_text_editable` tinyint(1) NOT NULL DEFAULT 1,
  `addon` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_templates_account_identifier_idx` (`account_id`,`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES (1,NULL,'all','appointment.invited','Appointment Invited','Appointment invitation: [[appointment_title]]','<p>You have been invited to [[appointment_title]] on [[appointment_at]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(2,NULL,'all','appointment.reminder','Appointment Reminder','Appointment reminder: [[appointment_title]]','<p>[[appointment_title]] starts on [[appointment_at]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(3,NULL,'all','appointment.rescheduled','Appointment Rescheduled','Appointment rescheduled: [[appointment_title]]','<p>[[appointment_title]] has been rescheduled to [[appointment_at]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(4,NULL,'all','appointment.cancelled','Appointment Cancelled','Appointment cancelled: [[appointment_title]]','<p>[[appointment_title]] scheduled for [[appointment_at]] has been cancelled.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(5,NULL,'all','offer.submitted','Offer Submitted','New offer for [[property_address]]','<p>A new offer of [[offer_amount]] has been submitted for [[property_address]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(6,NULL,'all','offer.accepted','Offer Accepted','Offer accepted for [[property_address]]','<p>The offer for [[property_address]] has been accepted.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(7,NULL,'all','offer.rejected','Offer Rejected','Offer update for [[property_address]]','<p>The offer for [[property_address]] was not successful.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(8,NULL,'all','offer.withdrawn','Offer Withdrawn','Offer withdrawn for [[property_address]]','<p>The offer for [[property_address]] has been withdrawn.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(9,NULL,'all','tenancy.activated','Tenancy Activated','Your tenancy at [[property_address]]','<p>Your tenancy at [[property_address]] starts on [[move_in_date]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(10,NULL,'all','tenancy.updated','Tenancy Updated','Tenancy updated: [[property_address]]','<p>Your tenancy details for [[property_address]] have been updated.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(11,NULL,'all','tenancy.move_in_due','Tenancy Move In Due','Move-in approaching: [[property_address]]','<p>The tenancy at [[property_address]] starts in [[days_text]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(12,NULL,'all','tenancy.deposit_due','Tenancy Deposit Due','Deposit protection deadline: [[property_address]]','<p>Deposit protection and prescribed information are [[due_text]] for [[property_address]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(13,NULL,'all','tenancy.right_to_rent_due','Tenancy Right To Rent Due','Right to rent follow-up due','<p>A recorded right to rent follow-up check is [[due_text]]. Open the tenancy record for details.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(14,NULL,'all','tenancy.notice_served','Tenancy Notice Served','Tenancy notice: [[notice_type]]','<p>A [[notice_type]] notice has been recorded for [[property_address]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(15,NULL,'all','compliance.expiring','Compliance Expiring','[[compliance_type]] expires soon','<p>[[compliance_type]] for [[property_address]] expires on [[due_date]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(16,NULL,'all','compliance.expired','Compliance Expired','Expired: [[compliance_type]]','<p>[[compliance_type]] for [[property_address]] expired on [[due_date]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(17,NULL,'all','compliance.remediation_due','Compliance Remediation Due','Compliance remediation [[due_text]]','<p>Remediation for [[compliance_type]] at [[property_address]] is [[due_text]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(18,NULL,'all','compliance.renewed','Compliance Renewed','[[compliance_type]] renewed','<p>A renewed [[compliance_type]] has been recorded for [[property_address]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(19,NULL,'all','repair.reported','Repair Reported','Repair reported: [[repair_reference]]','<p>A [[repair_priority]] priority repair has been reported at [[property_address]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(20,NULL,'all','repair.escalated','Repair Escalated','Unacknowledged repair: [[repair_reference]]','<p>Repair [[repair_reference]] remains unacknowledged and requires attention.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(21,NULL,'all','repair.manager_assigned','Repair Manager Assigned','Repair acknowledged: [[repair_reference]]','<p>A property manager has been assigned to repair [[repair_reference]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(22,NULL,'all','repair.quote_requested','Repair Quote Requested','Quote requested: [[repair_reference]]','<p>You have been invited to quote for repair [[repair_reference]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(23,NULL,'all','repair.quote_submitted','Repair Quote Submitted','Quote received: [[repair_reference]]','<p>A contractor quote has been submitted for repair [[repair_reference]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(24,NULL,'all','repair.contractor_assigned','Repair Contractor Assigned','Repair work assigned: [[repair_reference]]','<p>Repair [[repair_reference]] has been assigned to you.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(25,NULL,'all','repair.visit_scheduled','Repair Visit Scheduled','Repair visit scheduled: [[repair_reference]]','<p>A repair visit for [[repair_reference]] is scheduled for [[appointment_at]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(26,NULL,'all','repair.status_changed','Repair Status Changed','Repair update: [[repair_reference]]','<p>Repair [[repair_reference]] changed from [[old_status]] to [[new_status]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(27,NULL,'all','finance.invoice_issued','Finance Invoice Issued','Invoice [[invoice_number]]','<p>Invoice [[invoice_number]] for [[invoice_amount]] is due on [[due_date]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(28,NULL,'all','finance.invoice_due','Finance Invoice Due','Invoice [[invoice_number]] is due soon','<p>Invoice [[invoice_number]] for [[invoice_amount]] is due on [[due_date]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(29,NULL,'all','finance.invoice_overdue','Finance Invoice Overdue','Invoice [[invoice_number]] is overdue','<p>Invoice [[invoice_number]] is overdue with [[invoice_amount]] outstanding.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(30,NULL,'all','finance.payment_received','Finance Payment Received','Payment received: [[payment_amount]]','<p>We received [[payment_amount]] for [[invoice_number]].</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(31,NULL,'all','finance.invoice_voided','Finance Invoice Voided','Invoice [[invoice_number]] voided','<p>Invoice [[invoice_number]] has been voided.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(32,NULL,'all','finance.statement_ready','Finance Statement Ready','Your property statement is ready','<p>A new statement for [[property_address]] is ready to view.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30'),(33,NULL,'all','system.delivery_failed','System Delivery Failed','Notification delivery failed','<p>A notification could not be delivered after all retry attempts.</p><p><a href=\"[[action_url]]\">Open in ResiSquare</a></p>',1,1,1,NULL,'2026-09-10 11:54:30','2026-09-10 11:54:30');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estate_charges`
--

DROP TABLE IF EXISTS `estate_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estate_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ref_no` int(11) NOT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `ownergroup_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(550) NOT NULL,
  `paid_by_landlord` tinyint(4) NOT NULL DEFAULT 0,
  `managed_by_property` tinyint(4) NOT NULL DEFAULT 0,
  `charge_landlord` int(11) DEFAULT NULL,
  `tax` int(11) NOT NULL DEFAULT 0,
  `schedule_charge` decimal(10,0) DEFAULT NULL,
  `attachment` longtext NOT NULL,
  `type` varchar(191) NOT NULL,
  `due_date` date NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `frequency` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL,
  `added_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estate_charges_property_id_foreign` (`property_id`),
  KEY `estate_charges_ownergroup_id_foreign` (`ownergroup_id`),
  KEY `estate_charges_added_by_foreign` (`added_by`),
  CONSTRAINT `estate_charges_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estate_charges_ownergroup_id_foreign` FOREIGN KEY (`ownergroup_id`) REFERENCES `owner_group` (`id`) ON DELETE SET NULL,
  CONSTRAINT `estate_charges_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estate_charges`
--

LOCK TABLES `estate_charges` WRITE;
/*!40000 ALTER TABLE `estate_charges` DISABLE KEYS */;
/*!40000 ALTER TABLE `estate_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estate_charges_items`
--

DROP TABLE IF EXISTS `estate_charges_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estate_charges_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `charge_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(65,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `tax_amount` decimal(65,2) NOT NULL,
  `charge_attachment` longtext DEFAULT NULL,
  `status` varchar(155) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estate_charges_items_charge_id_foreign` (`charge_id`),
  CONSTRAINT `estate_charges_items_charge_id_foreign` FOREIGN KEY (`charge_id`) REFERENCES `estate_charges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estate_charges_items`
--

LOCK TABLES `estate_charges_items` WRITE;
/*!40000 ALTER TABLE `estate_charges_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `estate_charges_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_instance_changes`
--

DROP TABLE IF EXISTS `event_instance_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_instance_changes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `changed_field` varchar(191) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `comment` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_instance_changes_event_id_foreign` (`event_id`),
  KEY `event_instance_changes_changed_by_foreign` (`changed_by`),
  CONSTRAINT `event_instance_changes_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_instance_changes_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_instance_changes`
--

LOCK TABLES `event_instance_changes` WRITE;
/*!40000 ALTER TABLE `event_instance_changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_instance_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_instances`
--

DROP TABLE IF EXISTS `event_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `instance_status` enum('Scheduled','Cancelled','Rescheduled') NOT NULL DEFAULT 'Scheduled',
  `notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_instances_event_id_foreign` (`event_id`),
  CONSTRAINT `event_instances_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_instances`
--

LOCK TABLES `event_instances` WRITE;
/*!40000 ALTER TABLE `event_instances` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_reminders`
--

DROP TABLE IF EXISTS `event_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `minutes_before` int(11) NOT NULL DEFAULT 0,
  `channel` enum('email','in_app','sms','push') NOT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_reminders_event_id_foreign` (`event_id`),
  CONSTRAINT `event_reminders_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_reminders`
--

LOCK TABLES `event_reminders` WRITE;
/*!40000 ALTER TABLE `event_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_sub_types`
--

DROP TABLE IF EXISTS `event_sub_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_sub_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_sub_types_event_type_id_name_unique` (`event_type_id`,`name`),
  UNIQUE KEY `event_sub_types_slug_unique` (`slug`),
  CONSTRAINT `event_sub_types_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_sub_types`
--

LOCK TABLES `event_sub_types` WRITE;
/*!40000 ALTER TABLE `event_sub_types` DISABLE KEYS */;
INSERT INTO `event_sub_types` VALUES (1,1,'Buyer Viewing','buyer-viewing','Buyer Viewing event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(2,1,'Tenant Viewing','tenant-viewing','Tenant Viewing event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(3,1,'Virtual Viewing','virtual-viewing','Virtual Viewing event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(4,1,'Second Viewing','second-viewing','Second Viewing event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(5,2,'Sales Valuation','sales-valuation','Sales Valuation event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(6,2,'Lettings Valuation','lettings-valuation','Lettings Valuation event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(7,2,'Revaluation','revaluation','Revaluation event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(8,2,'Virtual Valuation','virtual-valuation','Virtual Valuation event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(9,3,'Buyer Registration Call','buyer-registration-call','Buyer Registration Call event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(10,3,'Vendor Feedback Call','vendor-feedback-call','Vendor Feedback Call event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(11,3,'Lettings Follow-up Call','lettings-follow-up-call','Lettings Follow-up Call event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(12,3,'Mortgage Follow-up','mortgage-follow-up','Mortgage Follow-up event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(13,4,'In-Branch Client Meeting','in-branch-client-meeting','In-Branch Client Meeting event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(14,4,'Vendor Meeting','vendor-meeting','Vendor Meeting event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(15,4,'Landlord Meeting','landlord-meeting','Landlord Meeting event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(16,4,'Investor Meeting','investor-meeting','Investor Meeting event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(17,5,'Post-Viewing Follow-Up','post-viewing-follow-up','Post-Viewing Follow-Up event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(18,5,'Lead Nurture Follow-Up','lead-nurture-follow-up','Lead Nurture Follow-Up event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(19,5,'Inactive Landlord Follow-Up','inactive-landlord-follow-up','Inactive Landlord Follow-Up event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(20,6,'Property Inspection','property-inspection','Property Inspection event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(21,6,'Mid-Term Inspection','mid-term-inspection','Mid-Term Inspection event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(22,6,'Check-Out Inspection','check-out-inspection','Check-Out Inspection event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(23,7,'Sales Agreement Signing','sales-agreement-signing','Sales Agreement Signing event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(24,7,'Tenancy Agreement Signing','tenancy-agreement-signing','Tenancy Agreement Signing event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(25,7,'Renewal Discussion','renewal-discussion','Renewal Discussion event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(26,8,'Maintenance Request Logged','maintenance-request-logged','Maintenance Request Logged event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(27,8,'Contractor Visit','contractor-visit','Contractor Visit event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(28,8,'Repair Completion','repair-completion','Repair Completion event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(29,9,'Move-In Scheduled','move-in-scheduled','Move-In Scheduled event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(30,9,'Key Collection','key-collection','Key Collection event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(31,9,'Move-Out Inspection','move-out-inspection','Move-Out Inspection event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(32,10,'Gas Safety Renewal','gas-safety-renewal','Gas Safety Renewal event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(33,10,'License Expiry','license-expiry','License Expiry event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(34,10,'Rent Review Due','rent-review-due','Rent Review Due event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(35,11,'Offer Received','offer-received','Offer Received event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(36,11,'Offer Accepted','offer-accepted','Offer Accepted event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(37,11,'Offer Withdrawn','offer-withdrawn','Offer Withdrawn event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(38,12,'Right to Rent Check','right-to-rent-check','Right to Rent Check event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(39,12,'Tenancy Renewal Check','tenancy-renewal-check','Tenancy Renewal Check event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(40,12,'Deposit Scheme Check','deposit-scheme-check','Deposit Scheme Check event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(41,13,'Mortgage Advisor Meeting','mortgage-advisor-meeting','Mortgage Advisor Meeting event','2026-09-10 11:54:25','2026-09-10 11:54:25'),(42,13,'Mortgage Application Follow-Up','mortgage-application-follow-up','Mortgage Application Follow-Up event','2026-09-10 11:54:25','2026-09-10 11:54:25');
/*!40000 ALTER TABLE `event_sub_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_types`
--

DROP TABLE IF EXISTS `event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_types_name_unique` (`name`),
  UNIQUE KEY `event_types_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_types`
--

LOCK TABLES `event_types` WRITE;
/*!40000 ALTER TABLE `event_types` DISABLE KEYS */;
INSERT INTO `event_types` VALUES (1,'Viewing','viewing','Viewing related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(2,'Valuation','valuation','Valuation related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(3,'Call','call','Call related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(4,'Meeting','meeting','Meeting related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(5,'Follow-Up','follow-up','Follow-Up related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(6,'Inspection','inspection','Inspection related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(7,'Contract','contract','Contract related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(8,'Maintenance','maintenance','Maintenance related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(9,'Move-In/Move-Out','move-inmove-out','Move-In/Move-Out related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(10,'Reminder','reminder','Reminder related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(11,'Offer','offer','Offer related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(12,'Tenancy Check','tenancy-check','Tenancy Check related events','2026-09-10 11:54:25','2026-09-10 11:54:25'),(13,'Mortgage Appointment','mortgage-appointment','Mortgage Appointment related events','2026-09-10 11:54:25','2026-09-10 11:54:25');
/*!40000 ALTER TABLE `event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventables`
--

DROP TABLE IF EXISTS `eventables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `eventable_id` bigint(20) unsigned NOT NULL,
  `eventable_type` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evtbl_unique` (`event_id`,`eventable_id`,`eventable_type`),
  CONSTRAINT `eventables_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventables`
--

LOCK TABLES `eventables` WRITE;
/*!40000 ALTER TABLE `eventables` DISABLE KEYS */;
INSERT INTO `eventables` VALUES (1,1,4,'Property',NULL,NULL),(2,1,14,'App\\Models\\User',NULL,NULL);
/*!40000 ALTER TABLE `eventables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `type_id` bigint(20) unsigned DEFAULT NULL,
  `sub_type_id` bigint(20) unsigned DEFAULT NULL,
  `office` varchar(191) DEFAULT NULL,
  `status` enum('Confirmed','Pending','Cancelled','Rescheduled','Scheduled') NOT NULL DEFAULT 'Pending',
  `diary_owner` varchar(191) DEFAULT NULL,
  `on_behalf_of` varchar(191) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reminder` varchar(191) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `rrule` varchar(191) DEFAULT NULL,
  `exdates` text DEFAULT NULL,
  `is_exception` tinyint(1) NOT NULL DEFAULT 0,
  `instance_status` enum('Scheduled','Cancelled','Completed','Rescheduled') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_start_datetime_index` (`start_datetime`),
  KEY `events_parent_id_index` (`parent_id`),
  KEY `events_account_id_idx` (`account_id`),
  CONSTRAINT `events_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `events` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,NULL,'Move-in — Flat 108, 1 Baltimore Wharf, London, E14 9RU',9,29,NULL,'Scheduled','6','6','Move-in — Flat 108, 1 Baltimore Wharf, London, E14 9RU','Created when the tenancy was set up.',NULL,'2026-09-09 10:00:00','2026-09-09 11:00:00',NULL,NULL,0,NULL,'2026-09-14 10:02:54','2026-09-14 10:02:54',1,NULL,NULL);
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fixed_assets`
--

DROP TABLE IF EXISTS `fixed_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fixed_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `asset_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(15,2) NOT NULL,
  `salvage_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `useful_life_months` int(11) NOT NULL DEFAULT 60,
  `depreciation_method` varchar(30) NOT NULL DEFAULT 'straight_line',
  `accumulated_depreciation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_book_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','disposed','fully_depreciated') NOT NULL DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_amount` decimal(15,2) DEFAULT NULL,
  `gl_asset_account_id` bigint(20) unsigned DEFAULT NULL,
  `gl_depreciation_account_id` bigint(20) unsigned DEFAULT NULL,
  `gl_expense_account_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fixed_assets_asset_code_unique` (`asset_code`),
  KEY `fixed_assets_gl_asset_account_id_foreign` (`gl_asset_account_id`),
  KEY `fixed_assets_gl_depreciation_account_id_foreign` (`gl_depreciation_account_id`),
  KEY `fixed_assets_gl_expense_account_id_foreign` (`gl_expense_account_id`),
  CONSTRAINT `fixed_assets_gl_asset_account_id_foreign` FOREIGN KEY (`gl_asset_account_id`) REFERENCES `gl_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_assets_gl_depreciation_account_id_foreign` FOREIGN KEY (`gl_depreciation_account_id`) REFERENCES `gl_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_assets_gl_expense_account_id_foreign` FOREIGN KEY (`gl_expense_account_id`) REFERENCES `gl_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixed_assets`
--

LOCK TABLES `fixed_assets` WRITE;
/*!40000 ALTER TABLE `fixed_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `fixed_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_type` varchar(191) NOT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `demo_date` date DEFAULT NULL,
  `demo_time` time DEFAULT NULL,
  `hear_about` varchar(191) DEFAULT NULL,
  `subscribe` tinyint(1) NOT NULL DEFAULT 0,
  `attachment` varchar(191) DEFAULT NULL,
  `ip` varchar(191) DEFAULT NULL,
  `ip_data` varchar(191) DEFAULT NULL,
  `ref_url` varchar(191) DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `w_countrycode` varchar(10) DEFAULT NULL,
  `w_phone` varchar(10) DEFAULT NULL,
  `wati_response` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submissions`
--

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_account_balances`
--

DROP TABLE IF EXISTS `gl_account_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gl_account_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gl_account_id` bigint(20) unsigned NOT NULL,
  `period` varchar(7) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gl_account_balances_gl_account_id_period_unique` (`gl_account_id`,`period`),
  KEY `gl_balances_account_idx` (`account_id`),
  CONSTRAINT `gl_account_balances_gl_account_id_foreign` FOREIGN KEY (`gl_account_id`) REFERENCES `gl_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_account_balances`
--

LOCK TABLES `gl_account_balances` WRITE;
/*!40000 ALTER TABLE `gl_account_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_account_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_accounts`
--

DROP TABLE IF EXISTS `gl_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gl_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` enum('asset','liability','income','expense','equity') NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `group` varchar(100) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gl_accounts_code_unique` (`code`),
  KEY `gl_accounts_parent_id_foreign` (`parent_id`),
  KEY `gl_accounts_group_index` (`group`),
  KEY `gl_accounts_account_idx` (`account_id`),
  CONSTRAINT `gl_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `gl_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_accounts`
--

LOCK TABLES `gl_accounts` WRITE;
/*!40000 ALTER TABLE `gl_accounts` DISABLE KEYS */;
INSERT INTO `gl_accounts` VALUES (1,'1000','Cash in Hand','asset',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(2,'1010','Bank Account','asset',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(3,'1100','Accounts Receivable (Tenants)','asset',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(4,'1200','Security Deposit Receivable','asset',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(5,'1300','Advance to Contractors','asset',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(6,'2000','Accounts Payable (Contractors)','liability',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(7,'2100','Owner Payable','liability',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(8,'2200','Tenant Security Deposit Payable','liability',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(9,'2300','Advance Rent Received','liability',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(10,'4000','Rental Income','income',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(11,'4100','Commission Income','income',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(12,'4200','Late Fee Income','income',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(13,'4300','Other Property Income','income',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(14,'5000','Maintenance Expense','expense',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(15,'5100','Contractor Expense','expense',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(16,'5200','Utilities Expense','expense',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(17,'5300','Management Expense','expense',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(18,'3000','Owner Capital','equity',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL),(19,'3100','Retained Earnings','equity',NULL,NULL,0,1,'2026-09-10 11:54:27','2026-09-10 11:54:27',NULL);
/*!40000 ALTER TABLE `gl_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_audit_logs`
--

DROP TABLE IF EXISTS `gl_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gl_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `auditable_type` varchar(191) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `action` varchar(20) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `gl_audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `gl_audit_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_audit_logs`
--

LOCK TABLES `gl_audit_logs` WRITE;
/*!40000 ALTER TABLE `gl_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_journal_lines`
--

DROP TABLE IF EXISTS `gl_journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gl_journal_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gl_journal_id` bigint(20) unsigned NOT NULL,
  `gl_account_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gl_journal_lines_gl_journal_id_foreign` (`gl_journal_id`),
  KEY `gl_journal_lines_gl_account_id_foreign` (`gl_account_id`),
  KEY `gl_journal_lines_comp_user_acc_created_idx` (`company_id`,`user_id`,`gl_account_id`,`created_at`),
  KEY `gl_journal_lines_account_idx` (`account_id`),
  CONSTRAINT `gl_journal_lines_gl_account_id_foreign` FOREIGN KEY (`gl_account_id`) REFERENCES `gl_accounts` (`id`),
  CONSTRAINT `gl_journal_lines_gl_journal_id_foreign` FOREIGN KEY (`gl_journal_id`) REFERENCES `gl_journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_journal_lines`
--

LOCK TABLES `gl_journal_lines` WRITE;
/*!40000 ALTER TABLE `gl_journal_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_journal_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_journals`
--

DROP TABLE IF EXISTS `gl_journals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gl_journals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reversal_of_id` bigint(20) unsigned DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `date` date NOT NULL,
  `memo` varchar(191) DEFAULT NULL,
  `source_type` varchar(191) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gl_journals_reversal_of_id_idx` (`reversal_of_id`),
  KEY `gl_journals_date_idx` (`date`),
  KEY `gl_journals_account_idx` (`account_id`),
  KEY `gl_journals_company_idx` (`company_id`),
  KEY `gl_journals_branch_idx` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_journals`
--

LOCK TABLES `gl_journals` WRITE;
/*!40000 ALTER TABLE `gl_journals` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_journals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_period_closes`
--

DROP TABLE IF EXISTS `gl_period_closes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gl_period_closes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(7) NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gl_period_closes_period_unique` (`period`),
  KEY `gl_period_closes_closed_by_foreign` (`closed_by`),
  CONSTRAINT `gl_period_closes_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_period_closes`
--

LOCK TABLES `gl_period_closes` WRITE;
/*!40000 ALTER TABLE `gl_period_closes` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_period_closes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT NULL,
  `tax_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_statuses`
--

DROP TABLE IF EXISTS `invoice_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_statuses_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_statuses`
--

LOCK TABLES `invoice_statuses` WRITE;
/*!40000 ALTER TABLE `invoice_statuses` DISABLE KEYS */;
INSERT INTO `invoice_statuses` VALUES (1,'Pending',NULL,NULL),(2,'Paid',NULL,NULL),(3,'Overdue',NULL,NULL),(4,'Cancelled',NULL,NULL);
/*!40000 ALTER TABLE `invoice_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(191) NOT NULL,
  `work_order_id` bigint(20) unsigned DEFAULT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `invoiced_date_time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_work_order_id_foreign` (`work_order_id`),
  KEY `invoices_property_id_foreign` (`property_id`),
  KEY `invoices_user_id_foreign` (`user_id`),
  CONSTRAINT `invoices_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
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

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_types`
--

DROP TABLE IF EXISTS `job_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `order_level` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_types_name_unique` (`name`),
  KEY `job_types_parent_id_foreign` (`parent_id`),
  CONSTRAINT `job_types_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `job_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_types`
--

LOCK TABLES `job_types` WRITE;
/*!40000 ALTER TABLE `job_types` DISABLE KEYS */;
INSERT INTO `job_types` VALUES (1,'Check In',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(2,'Check Out',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(3,'Professional Clean',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(4,'Deep Cleaning',3,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(5,'Carpet Cleaning',3,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(6,'Window Cleaning',3,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(7,'Upholstery Cleaning',3,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(8,'Kitchen/Bathroom Sanitization',3,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(9,'End of Tenancy Cleaning',3,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(10,'Repair Work',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(11,'Plumbing',10,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(12,'Handyman Work',10,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(13,'Electrical Works',10,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(14,'Gas Safety Certification',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(15,'Boiler Inspection',14,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(16,'Gas Leak Testing',14,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(17,'Gas Appliance Servicing',14,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(18,'Flue & Ventilation Check',14,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(19,'Gas Pipework Check',14,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(20,'CO Detector Installation',14,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(21,'EICR',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(22,'Circuit Testing',21,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(23,'Fuse Box Inspection',21,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(24,'Wiring Condition Report',21,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(25,'PAT Testing',21,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(26,'Emergency Lighting Inspection',21,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(27,'EPC',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(28,'Thermal Imaging Inspection',27,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(29,'Loft Insulation Check',27,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(30,'Boiler Efficiency Testing',27,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(31,'Heating System Assessment',27,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(32,'Window/Door Insulation Check',27,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(33,'Renewable Energy Evaluation',27,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(34,'PAT',NULL,1,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(35,'Visual Inspection',34,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(36,'Earth Continuity Test',34,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(37,'Insulation Resistance Test',34,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(38,'Load & Functional Testing',34,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22'),(39,'Labeling & Certification',34,2,0,'2026-09-10 11:54:22','2026-09-10 11:54:22');
/*!40000 ALTER TABLE `job_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `local_authorities`
--

DROP TABLE IF EXISTS `local_authorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `local_authorities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `local_authority_group_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local_authorities_local_authority_group_id_foreign` (`local_authority_group_id`),
  CONSTRAINT `local_authorities_local_authority_group_id_foreign` FOREIGN KEY (`local_authority_group_id`) REFERENCES `local_authority_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `local_authorities`
--

LOCK TABLES `local_authorities` WRITE;
/*!40000 ALTER TABLE `local_authorities` DISABLE KEYS */;
INSERT INTO `local_authorities` VALUES (1,1,'Barking and Dagenham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(2,1,'Barnet','2026-09-10 11:54:23','2026-09-10 11:54:23'),(3,1,'Bexley','2026-09-10 11:54:23','2026-09-10 11:54:23'),(4,1,'Brent','2026-09-10 11:54:23','2026-09-10 11:54:23'),(5,1,'Bromley','2026-09-10 11:54:23','2026-09-10 11:54:23'),(6,1,'Camden','2026-09-10 11:54:23','2026-09-10 11:54:23'),(7,1,'Croydon','2026-09-10 11:54:23','2026-09-10 11:54:23'),(8,1,'Ealing','2026-09-10 11:54:23','2026-09-10 11:54:23'),(9,1,'Enfield','2026-09-10 11:54:23','2026-09-10 11:54:23'),(10,1,'Greenwich','2026-09-10 11:54:23','2026-09-10 11:54:23'),(11,1,'Hackney','2026-09-10 11:54:23','2026-09-10 11:54:23'),(12,1,'Hammersmith and Fulham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(13,1,'Haringey','2026-09-10 11:54:23','2026-09-10 11:54:23'),(14,1,'Harrow','2026-09-10 11:54:23','2026-09-10 11:54:23'),(15,1,'Havering','2026-09-10 11:54:23','2026-09-10 11:54:23'),(16,1,'Hillingdon','2026-09-10 11:54:23','2026-09-10 11:54:23'),(17,1,'Hounslow','2026-09-10 11:54:23','2026-09-10 11:54:23'),(18,1,'Islington','2026-09-10 11:54:23','2026-09-10 11:54:23'),(19,1,'Kensington and Chelsea','2026-09-10 11:54:23','2026-09-10 11:54:23'),(20,1,'Kingston upon Thames','2026-09-10 11:54:23','2026-09-10 11:54:23'),(21,1,'Lambeth','2026-09-10 11:54:23','2026-09-10 11:54:23'),(22,1,'Lewisham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(23,1,'Merton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(24,1,'Newham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(25,1,'Redbridge','2026-09-10 11:54:23','2026-09-10 11:54:23'),(26,1,'Richmond upon Thames','2026-09-10 11:54:23','2026-09-10 11:54:23'),(27,1,'Southwark','2026-09-10 11:54:23','2026-09-10 11:54:23'),(28,1,'Sutton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(29,1,'Tower Hamlets','2026-09-10 11:54:23','2026-09-10 11:54:23'),(30,1,'Waltham Forest','2026-09-10 11:54:23','2026-09-10 11:54:23'),(31,1,'Wandsworth','2026-09-10 11:54:23','2026-09-10 11:54:23'),(32,1,'Westminster','2026-09-10 11:54:23','2026-09-10 11:54:23'),(33,1,'City of London','2026-09-10 11:54:23','2026-09-10 11:54:23'),(34,2,'Barnsley','2026-09-10 11:54:23','2026-09-10 11:54:23'),(35,2,'Birmingham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(36,2,'Bolton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(37,2,'Bradford','2026-09-10 11:54:23','2026-09-10 11:54:23'),(38,2,'Bury','2026-09-10 11:54:23','2026-09-10 11:54:23'),(39,2,'Calderdale','2026-09-10 11:54:23','2026-09-10 11:54:23'),(40,2,'Coventry','2026-09-10 11:54:23','2026-09-10 11:54:23'),(41,2,'Doncaster','2026-09-10 11:54:23','2026-09-10 11:54:23'),(42,2,'Dudley','2026-09-10 11:54:23','2026-09-10 11:54:23'),(43,2,'Gateshead','2026-09-10 11:54:23','2026-09-10 11:54:23'),(44,2,'Kirklees','2026-09-10 11:54:23','2026-09-10 11:54:23'),(45,2,'Knowsley','2026-09-10 11:54:23','2026-09-10 11:54:23'),(46,2,'Leeds','2026-09-10 11:54:23','2026-09-10 11:54:23'),(47,2,'Liverpool','2026-09-10 11:54:23','2026-09-10 11:54:23'),(48,2,'Manchester','2026-09-10 11:54:23','2026-09-10 11:54:23'),(49,2,'Newcastle upon Tyne','2026-09-10 11:54:23','2026-09-10 11:54:23'),(50,2,'North Tyneside','2026-09-10 11:54:23','2026-09-10 11:54:23'),(51,2,'Oldham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(52,2,'Rochdale','2026-09-10 11:54:23','2026-09-10 11:54:23'),(53,2,'Rotherham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(54,2,'Salford','2026-09-10 11:54:23','2026-09-10 11:54:23'),(55,2,'Sandwell','2026-09-10 11:54:23','2026-09-10 11:54:23'),(56,2,'Sefton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(57,2,'Sheffield','2026-09-10 11:54:23','2026-09-10 11:54:23'),(58,2,'Solihull','2026-09-10 11:54:23','2026-09-10 11:54:23'),(59,2,'South Tyneside','2026-09-10 11:54:23','2026-09-10 11:54:23'),(60,2,'St Helens','2026-09-10 11:54:23','2026-09-10 11:54:23'),(61,2,'Stockport','2026-09-10 11:54:23','2026-09-10 11:54:23'),(62,2,'Sunderland','2026-09-10 11:54:23','2026-09-10 11:54:23'),(63,2,'Tameside','2026-09-10 11:54:23','2026-09-10 11:54:23'),(64,2,'Trafford','2026-09-10 11:54:23','2026-09-10 11:54:23'),(65,2,'Wakefield','2026-09-10 11:54:23','2026-09-10 11:54:23'),(66,2,'Walsall','2026-09-10 11:54:23','2026-09-10 11:54:23'),(67,2,'Wigan','2026-09-10 11:54:23','2026-09-10 11:54:23'),(68,2,'Wolverhampton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(69,2,'Wirral','2026-09-10 11:54:23','2026-09-10 11:54:23'),(70,3,'Bath and North East Somerset','2026-09-10 11:54:23','2026-09-10 11:54:23'),(71,3,'Bedford Borough','2026-09-10 11:54:23','2026-09-10 11:54:23'),(72,3,'Blackburn with Darwen','2026-09-10 11:54:23','2026-09-10 11:54:23'),(73,3,'Blackpool','2026-09-10 11:54:23','2026-09-10 11:54:23'),(74,3,'Bournemouth, Christchurch and Poole','2026-09-10 11:54:23','2026-09-10 11:54:23'),(75,3,'Bracknell Forest','2026-09-10 11:54:23','2026-09-10 11:54:23'),(76,3,'Brighton and Hove','2026-09-10 11:54:23','2026-09-10 11:54:23'),(77,3,'Bristol','2026-09-10 11:54:23','2026-09-10 11:54:23'),(78,3,'Buckinghamshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(79,3,'Central Bedfordshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(80,3,'Cheshire East','2026-09-10 11:54:23','2026-09-10 11:54:23'),(81,3,'Cheshire West and Chester','2026-09-10 11:54:23','2026-09-10 11:54:23'),(82,3,'Cornwall','2026-09-10 11:54:23','2026-09-10 11:54:23'),(83,3,'County Durham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(84,3,'Darlington','2026-09-10 11:54:23','2026-09-10 11:54:23'),(85,3,'Derby','2026-09-10 11:54:23','2026-09-10 11:54:23'),(86,3,'Dorset','2026-09-10 11:54:23','2026-09-10 11:54:23'),(87,3,'East Riding of Yorkshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(88,3,'Halton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(89,3,'Hartlepool','2026-09-10 11:54:23','2026-09-10 11:54:23'),(90,3,'Herefordshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(91,3,'Isle of Wight','2026-09-10 11:54:23','2026-09-10 11:54:23'),(92,3,'Isles of Scilly','2026-09-10 11:54:23','2026-09-10 11:54:23'),(93,3,'Kingston upon Hull','2026-09-10 11:54:23','2026-09-10 11:54:23'),(94,3,'Leicester','2026-09-10 11:54:23','2026-09-10 11:54:23'),(95,3,'Luton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(96,3,'Medway','2026-09-10 11:54:23','2026-09-10 11:54:23'),(97,3,'Middlesbrough','2026-09-10 11:54:23','2026-09-10 11:54:23'),(98,3,'Milton Keynes','2026-09-10 11:54:23','2026-09-10 11:54:23'),(99,3,'North East Lincolnshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(100,3,'North Lincolnshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(101,3,'North Northamptonshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(102,3,'North Somerset','2026-09-10 11:54:23','2026-09-10 11:54:23'),(103,3,'Nottingham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(104,3,'Peterborough','2026-09-10 11:54:23','2026-09-10 11:54:23'),(105,3,'Plymouth','2026-09-10 11:54:23','2026-09-10 11:54:23'),(106,3,'Portsmouth','2026-09-10 11:54:23','2026-09-10 11:54:23'),(107,3,'Reading','2026-09-10 11:54:23','2026-09-10 11:54:23'),(108,3,'Redcar and Cleveland','2026-09-10 11:54:23','2026-09-10 11:54:23'),(109,3,'Rutland','2026-09-10 11:54:23','2026-09-10 11:54:23'),(110,3,'Shropshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(111,3,'Slough','2026-09-10 11:54:23','2026-09-10 11:54:23'),(112,3,'South Gloucestershire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(113,3,'Southampton','2026-09-10 11:54:23','2026-09-10 11:54:23'),(114,3,'Southend-on-Sea','2026-09-10 11:54:23','2026-09-10 11:54:23'),(115,3,'Stockton-on-Tees','2026-09-10 11:54:23','2026-09-10 11:54:23'),(116,3,'Stoke-on-Trent','2026-09-10 11:54:23','2026-09-10 11:54:23'),(117,3,'Swindon','2026-09-10 11:54:23','2026-09-10 11:54:23'),(118,3,'Telford and Wrekin','2026-09-10 11:54:23','2026-09-10 11:54:23'),(119,3,'Thurrock','2026-09-10 11:54:23','2026-09-10 11:54:23'),(120,3,'Torbay','2026-09-10 11:54:23','2026-09-10 11:54:23'),(121,3,'Warrington','2026-09-10 11:54:23','2026-09-10 11:54:23'),(122,3,'West Berkshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(123,3,'West Northamptonshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(124,3,'Wiltshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(125,3,'Windsor and Maidenhead','2026-09-10 11:54:23','2026-09-10 11:54:23'),(126,3,'Wokingham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(127,3,'York','2026-09-10 11:54:23','2026-09-10 11:54:23'),(128,4,'Bedfordshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(129,4,'Cambridgeshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(130,4,'Derbyshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(131,4,'Devon','2026-09-10 11:54:23','2026-09-10 11:54:23'),(132,4,'Dorset','2026-09-10 11:54:23','2026-09-10 11:54:23'),(133,4,'Durham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(134,4,'East Sussex','2026-09-10 11:54:23','2026-09-10 11:54:23'),(135,4,'Essex','2026-09-10 11:54:23','2026-09-10 11:54:23'),(136,4,'Gloucestershire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(137,4,'Hampshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(138,4,'Hertfordshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(139,4,'Kent','2026-09-10 11:54:23','2026-09-10 11:54:23'),(140,4,'Lancashire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(141,4,'Leicestershire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(142,4,'Lincolnshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(143,4,'Norfolk','2026-09-10 11:54:23','2026-09-10 11:54:23'),(144,4,'Northamptonshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(145,4,'Nottinghamshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(146,4,'Oxfordshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(147,4,'Somerset','2026-09-10 11:54:23','2026-09-10 11:54:23'),(148,4,'Staffordshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(149,4,'Suffolk','2026-09-10 11:54:23','2026-09-10 11:54:23'),(150,4,'Surrey','2026-09-10 11:54:23','2026-09-10 11:54:23'),(151,4,'Warwickshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(152,4,'West Sussex','2026-09-10 11:54:23','2026-09-10 11:54:23'),(153,5,'Aberdeen City','2026-09-10 11:54:23','2026-09-10 11:54:23'),(154,5,'Aberdeenshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(155,5,'Angus','2026-09-10 11:54:23','2026-09-10 11:54:23'),(156,5,'Argyll and Bute','2026-09-10 11:54:23','2026-09-10 11:54:23'),(157,5,'Clackmannanshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(158,5,'Dumfries and Galloway','2026-09-10 11:54:23','2026-09-10 11:54:23'),(159,5,'Dundee City','2026-09-10 11:54:23','2026-09-10 11:54:23'),(160,5,'East Ayrshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(161,5,'East Dunbartonshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(162,5,'East Lothian','2026-09-10 11:54:23','2026-09-10 11:54:23'),(163,5,'East Renfrewshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(164,5,'Edinburgh City','2026-09-10 11:54:23','2026-09-10 11:54:23'),(165,5,'Falkirk','2026-09-10 11:54:23','2026-09-10 11:54:23'),(166,5,'Fife','2026-09-10 11:54:23','2026-09-10 11:54:23'),(167,5,'Glasgow City','2026-09-10 11:54:23','2026-09-10 11:54:23'),(168,5,'Highland','2026-09-10 11:54:23','2026-09-10 11:54:23'),(169,5,'Inverclyde','2026-09-10 11:54:23','2026-09-10 11:54:23'),(170,5,'Midlothian','2026-09-10 11:54:23','2026-09-10 11:54:23'),(171,5,'Moray','2026-09-10 11:54:23','2026-09-10 11:54:23'),(172,5,'North Ayrshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(173,5,'North Lanarkshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(174,5,'Orkney Islands','2026-09-10 11:54:23','2026-09-10 11:54:23'),(175,5,'Perth and Kinross','2026-09-10 11:54:23','2026-09-10 11:54:23'),(176,5,'Renfrewshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(177,5,'Scottish Borders','2026-09-10 11:54:23','2026-09-10 11:54:23'),(178,5,'Shetland Islands','2026-09-10 11:54:23','2026-09-10 11:54:23'),(179,5,'South Ayrshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(180,5,'South Lanarkshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(181,5,'Stirling','2026-09-10 11:54:23','2026-09-10 11:54:23'),(182,5,'West Dunbartonshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(183,5,'West Lothian','2026-09-10 11:54:23','2026-09-10 11:54:23'),(184,5,'Western Isles (Eilean Siar)','2026-09-10 11:54:23','2026-09-10 11:54:23'),(185,6,'Blaenau Gwent','2026-09-10 11:54:23','2026-09-10 11:54:23'),(186,6,'Bridgend','2026-09-10 11:54:23','2026-09-10 11:54:23'),(187,6,'Caerphilly','2026-09-10 11:54:23','2026-09-10 11:54:23'),(188,6,'Cardiff','2026-09-10 11:54:23','2026-09-10 11:54:23'),(189,6,'Carmarthenshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(190,6,'Ceredigion','2026-09-10 11:54:23','2026-09-10 11:54:23'),(191,6,'Conwy','2026-09-10 11:54:23','2026-09-10 11:54:23'),(192,6,'Denbighshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(193,6,'Flintshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(194,6,'Gwynedd','2026-09-10 11:54:23','2026-09-10 11:54:23'),(195,6,'Isle of Anglesey','2026-09-10 11:54:23','2026-09-10 11:54:23'),(196,6,'Merthyr Tydfil','2026-09-10 11:54:23','2026-09-10 11:54:23'),(197,6,'Monmouthshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(198,6,'Neath Port Talbot','2026-09-10 11:54:23','2026-09-10 11:54:23'),(199,6,'Newport','2026-09-10 11:54:23','2026-09-10 11:54:23'),(200,6,'Pembrokeshire','2026-09-10 11:54:23','2026-09-10 11:54:23'),(201,6,'Powys','2026-09-10 11:54:23','2026-09-10 11:54:23'),(202,6,'Rhondda Cynon Taf','2026-09-10 11:54:23','2026-09-10 11:54:23'),(203,6,'Swansea','2026-09-10 11:54:23','2026-09-10 11:54:23'),(204,6,'Torfaen','2026-09-10 11:54:23','2026-09-10 11:54:23'),(205,6,'Vale of Glamorgan','2026-09-10 11:54:23','2026-09-10 11:54:23'),(206,6,'Wrexham','2026-09-10 11:54:23','2026-09-10 11:54:23'),(207,7,'Antrim and Newtownabbey','2026-09-10 11:54:23','2026-09-10 11:54:23'),(208,7,'Ards and North Down','2026-09-10 11:54:23','2026-09-10 11:54:23'),(209,7,'Armagh City, Banbridge, and Craigavon','2026-09-10 11:54:23','2026-09-10 11:54:23'),(210,7,'Belfast City','2026-09-10 11:54:23','2026-09-10 11:54:23'),(211,7,'Causeway Coast and Glens','2026-09-10 11:54:23','2026-09-10 11:54:23'),(212,7,'Derry City and Strabane','2026-09-10 11:54:23','2026-09-10 11:54:23'),(213,7,'Fermanagh and Omagh','2026-09-10 11:54:23','2026-09-10 11:54:23'),(214,7,'Lisburn and Castlereagh City','2026-09-10 11:54:23','2026-09-10 11:54:23'),(215,7,'Mid and East Antrim','2026-09-10 11:54:23','2026-09-10 11:54:23'),(216,7,'Mid Ulster','2026-09-10 11:54:23','2026-09-10 11:54:23'),(217,7,'Newry, Mourne, and Down','2026-09-10 11:54:23','2026-09-10 11:54:23');
/*!40000 ALTER TABLE `local_authorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `local_authority_groups`
--

DROP TABLE IF EXISTS `local_authority_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `local_authority_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `display_prefix` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `local_authority_groups`
--

LOCK TABLES `local_authority_groups` WRITE;
/*!40000 ALTER TABLE `local_authority_groups` DISABLE KEYS */;
INSERT INTO `local_authority_groups` VALUES (1,'London Boroughs','London Boroughs','2026-09-10 11:54:23','2026-09-10 11:54:23'),(2,'Metropolitan Borough Councils','Metropolitan Borough Councils','2026-09-10 11:54:23','2026-09-10 11:54:23'),(3,'Unitary Authorities','Unitary Authorities','2026-09-10 11:54:23','2026-09-10 11:54:23'),(4,'County Councils','County Councils','2026-09-10 11:54:23','2026-09-10 11:54:23'),(5,'Scotland','Scotland','2026-09-10 11:54:23','2026-09-10 11:54:23'),(6,'Wales','Wales','2026-09-10 11:54:23','2026-09-10 11:54:23'),(7,'Northern Ireland','Northern Ireland','2026-09-10 11:54:23','2026-09-10 11:54:23');
/*!40000 ALTER TABLE `local_authority_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_10_24_115126_create_cache_table',1),(2,'2024_10_24_115127_create_jobs_table',1),(3,'2024_10_24_115128_create_roles_table',1),(4,'2024_10_24_115129_create_users_table',1),(5,'2024_10_24_115130_add_role_id_to_users_table',1),(6,'2024_10_24_115131_create_properties_table',1),(7,'2024_10_24_115132_create_currencies_table',1),(8,'2024_10_24_115133_create_uploads_table',1),(9,'2024_11_26_120416_create_contacts_categories_table',1),(10,'2024_11_26_135612_create_owner_group_table',1),(11,'2024_11_26_135613_create_charges_table',1),(12,'2024_11_26_135614_create_charges_items_table',2),(13,'2024_11_29_121253_create_offers_table',2),(14,'2024_12_03_120637_create_station_names_table',2),(15,'2024_12_03_120721_create_school_names_table',2),(16,'2024_12_03_120739_create_religious_places_table',2),(17,'2024_12_10_110222_create_branches_table',2),(18,'2024_12_10_121943_create_designations_table',2),(19,'2024_12_10_130739_create_property_responsibilities_table',2),(20,'2024_12_19_165539_create_tenancies_table',3),(21,'2024_12_19_165540_create_tenant_members_table',3),(22,'2024_12_24_184831_create_owner_group_contacts_table',3),(23,'2025_01_07_180912_create_tenancy_types_table',3),(24,'2025_01_07_182335_create_tenancy_sub_statuses_table',3),(25,'2025_01_08_180220_create_contact_details_table',3),(26,'2025_01_08_190920_create_property_manager_tenancy_table',3),(27,'2025_01_13_171532_create_compliance_types_table',3),(28,'2025_01_13_171533_create_compliance_details_table',4),(29,'2025_01_13_171533_create_compliance_records_table',4),(30,'2025_01_22_190537_create_repair_categories_table',4),(31,'2025_01_22_190549_create_repair_issues_table',4),(32,'2025_01_22_190602_create_repair_photos_table',4),(33,'2025_01_22_190610_create_repair_assignments_table',4),(34,'2025_01_22_190620_create_repair_histories_table',4),(35,'2025_01_22_190627_create_repair_issue_contacts_table',4),(36,'2025_02_06_185804_create_repair_issue_contractor_assignments_table',4),(37,'2025_02_06_185804_create_repair_issue_property_managers_table',4),(38,'2025_02_25_175531_create_work_orders_table',4),(39,'2025_02_25_194407_create_job_types_table',4),(40,'2025_03_08_170302_create_invoices_table',5),(41,'2025_03_08_170303_create_tax_rates_table',5),(42,'2025_03_08_170304_create_invoice_items_table',5),(43,'2025_03_08_170305_create_invoice_statuses_table',5),(44,'2025_03_08_171102_create_transaction_categories_table',5),(45,'2025_03_08_171103_create_transactions_table',5),(46,'2025_03_21_151155_create_work_order_items_table',5),(47,'2025_04_23_170740_create_events_table',5),(48,'2025_04_23_175258_create_event_types_table',5),(49,'2025_04_23_175308_create_event_sub_types_table',5),(50,'2025_04_24_201047_create_local_authority_groups_table',5),(51,'2025_04_24_201057_create_local_authorities_table',5),(52,'2025_04_30_163745_create_countries_table',5),(53,'2025_05_02_194626_create_notes_table',5),(54,'2025_05_15_165111_create_bank_details_table',5),(55,'2025_05_15_194809_create_nationalities_table',5),(56,'2025_05_21_173658_create_note_types_table',5),(57,'2025_05_26_013430_create_document_types_table',5),(58,'2025_05_26_013615_create_documents_table',5),(59,'2025_05_28_182248_create_form_submissions_table',5),(60,'2025_05_31_144638_create_event_instances_table',5),(61,'2025_05_31_163625_create_event_instance_changes_table',5),(62,'2025_06_13_180045_create_event_reminders_table',5),(63,'2025_06_17_175122_create_audits_table',5),(64,'2025_06_21_171058_create_eventables_table',5),(65,'2025_07_05_180135_create_permission_tables',5),(66,'2025_07_15_000000_create_companies_table',5),(67,'2025_07_15_000005_create_staff_table',5),(68,'2025_07_17_170036_merge_contacts_into_users',5),(69,'2025_07_29_161040_add_created_by_updated_by_to_work_orders_table',5),(70,'2025_07_29_161816_add_created_by_updated_by_to_repair_issues_table',5),(71,'2025_08_25_163251_create_accounting_headers_table',5),(72,'2025_08_29_000001_bank_accounts_table',5),(73,'2025_09_02_184408_create_payment_methods_table',5),(74,'2025_09_15_000010_create_credit_note_refunds_table',5),(75,'2025_09_15_000011_create_debit_note_refunds_table',5),(76,'2025_09_15_000020_create_document_sequences_table',5),(77,'2025_09_15_000021_create_purchase_invoices_table',5),(78,'2025_09_15_000022_create_purchase_invoice_items_table',5),(79,'2025_09_15_000023_create_credit_notes_table',5),(80,'2025_09_15_000024_create_debit_notes_table',5),(81,'2025_09_15_000025_create_note_applications_table',5),(82,'2025_09_15_000026_create_all_note_refunds_view',5),(83,'2026_02_10_000001_create_sys_accounting_tables',5),(84,'2026_02_11_000002_create_sys_bank_accounts_table',5),(85,'2026_02_11_000003_update_sys_receipts_bank_account_fk_to_sys_bank_accounts',5),(86,'2026_02_11_000004_remove_user_id_from_sys_bank_accounts_if_exists',5),(87,'2026_02_11_000005_rename_bank_account_id_to_sys_bank_account_id_in_sys_tables',5),(88,'2026_02_11_000006_remove_owner_columns_from_sys_bank_accounts',5),(89,'2026_02_17_000007_add_status_and_applied_amount_to_sys_receipts',5),(90,'2026_02_17_100000_create_gl_accounts_table',5),(91,'2026_02_17_100100_create_gl_journals_table',5),(92,'2026_02_17_100200_create_gl_journal_lines_table',5),(93,'2026_02_17_100300_create_gl_account_balances_table',5),(94,'2026_02_17_100400_add_gl_columns_to_bank_payments_receipts',5),(95,'2026_02_19_120000_add_reversal_links_and_payment_void_columns',5),(96,'2026_02_19_130500_add_gl_account_id_to_sys_bank_accounts',5),(97,'2026_02_24_150000_add_source_receipt_fk_to_sys_payments',5),(98,'2026_02_24_160000_add_indexes_to_gl_tables',5),(99,'2026_02_24_171000_add_default_gl_setting_keys',6),(100,'2026_02_24_171500_seed_default_gl_settings_values',6),(101,'2026_02_27_180000_add_payment_meta_to_receipts_and_payments',6),(102,'2026_03_02_100000_create_gl_period_closes_table',6),(103,'2026_03_02_100100_create_bank_reconciliations_table',6),(104,'2026_03_02_100200_add_group_and_parent_to_gl_accounts',6),(105,'2026_03_02_100300_create_gl_audit_logs_table',6),(106,'2026_03_02_100400_create_fixed_assets_table',6),(107,'2026_03_02_100500_add_attachments_to_gl_journals',6),(108,'2026_03_02_100600_add_accounting_permissions',6),(109,'2026_03_20_000000_create_sys_invoice_headers_table',6),(110,'2026_03_20_000100_add_invoice_header_id_to_sys_sale_invoices',6),(111,'2026_03_20_000200_add_linking_fields_to_sys_sale_invoices',6),(112,'2026_03_23_000300_add_recurring_fields_to_sys_sale_invoices',6),(113,'2026_03_23_000301_add_penalty_fields_to_sys_sale_invoices',6),(114,'2026_04_21_183250_create_sms_templates_table',6),(115,'2026_04_21_200000_create_notification_logs_table',6),(116,'2026_04_27_000100_add_scheduling_fields_to_sys_sale_invoices',6),(117,'2026_04_27_000120_drop_send_on_from_sys_sale_invoices',6),(118,'2026_05_12_124134_create_sessions_table',6),(119,'2026_05_14_000002_cleanup_form_submissions_and_add_staff_contacts',6),(120,'2026_05_15_000001_create_registrations_table',6),(121,'2026_05_15_000002_add_verify_via_to_registrations',6),(122,'2026_05_15_000003_add_estate_agent_to_registrations_type',6),(123,'2026_05_15_000004_drop_unique_email_from_registrations',6),(124,'2026_05_15_000010_create_otp_configurations_table',6),(125,'2026_05_15_000011_create_sms_templates_table',6),(126,'2026_05_15_000012_create_designation_has_permissions_and_drop_staff_role_id',6),(127,'2026_05_15_000013_add_permissions_customized_to_staff_table',6),(128,'2026_05_18_000001_extend_companies_branches_and_staff_for_agent_ownership',6),(129,'2026_05_18_000002_create_company_owner_transfers_table',6),(130,'2026_05_18_000003_add_missing_company_id_to_branches_table',6),(131,'2026_05_19_000001_add_head_office_and_social_fields_to_branches',6),(132,'2026_05_19_000003_add_responsibility_type_to_property_responsibilities',6),(133,'2026_05_19_000004_add_primary_contact_fields_to_user_details',6),(134,'2026_05_19_000005_make_property_responsibility_extra_fields_nullable',6),(135,'2026_05_26_000100_add_quote_fields_to_repair_issue_contractor_assignments',6),(136,'2026_07_01_000001_create_saas_subscription_tables',6),(137,'2026_07_01_000002_add_saas_columns_to_existing_tables',6),(138,'2026_07_02_000001_add_signup_snapshots_to_account_subscriptions',6),(139,'2026_07_03_000001_add_purchase_snapshots_to_account_subscription_addons',6),(140,'2026_07_06_000001_add_saas_hardening_indexes',6),(141,'2026_07_13_000001_add_manage_registrations_permission',6),(142,'2026_07_15_000001_add_password_hash_to_registrations',6),(143,'2026_07_22_000001_add_subscription_activation_email_sent_at_to_accounts',6),(144,'2026_07_22_000002_add_registration_welcome_email_sent_at_to_accounts',6),(145,'2026_07_27_000001_rebuild_property_identity_hashes',6),(146,'2026_08_13_000001_create_notifications_table',6),(147,'2026_08_13_000010_build_crm_notification_foundation',6),(148,'2026_08_13_000020_add_notification_deadline_fields',6),(149,'2026_08_28_100000_align_properties_ownership_columns',6),(150,'2026_09_01_150000_add_landlord_onboarding_to_accounts',6),(151,'2026_09_01_163000_grant_landlord_property_view_permissions',6),(152,'2026_09_09_153800_create_rent_invoices_and_payments_tables',6),(153,'2026_09_10_161000_add_stripe_fields_to_rent_payments',6),(154,'2026_09_10_181000_add_status_reason_to_accounts',7),(155,'2026_09_10_190000_add_missing_property_detail_columns',8);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(1,'App\\Models\\User',6),(1,'App\\Models\\User',9),(2,'App\\Models\\User',2),(2,'App\\Models\\User',5),(4,'App\\Models\\User',3),(4,'App\\Models\\User',12),(5,'App\\Models\\User',10),(5,'App\\Models\\User',14),(6,'App\\Models\\User',7),(8,'App\\Models\\User',11),(16,'App\\Models\\User',4),(16,'App\\Models\\User',8);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nationalities`
--

DROP TABLE IF EXISTS `nationalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nationalities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nationalities_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nationalities`
--

LOCK TABLES `nationalities` WRITE;
/*!40000 ALTER TABLE `nationalities` DISABLE KEYS */;
INSERT INTO `nationalities` VALUES (1,'Afghan','2026-09-10 11:54:24','2026-09-10 11:54:24'),(2,'Albanian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(3,'Algerian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(4,'Andorran','2026-09-10 11:54:24','2026-09-10 11:54:24'),(5,'Angolan','2026-09-10 11:54:24','2026-09-10 11:54:24'),(6,'Antiguans','2026-09-10 11:54:24','2026-09-10 11:54:24'),(7,'Argentine','2026-09-10 11:54:24','2026-09-10 11:54:24'),(8,'Armenian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(9,'Australian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(10,'Austrian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(11,'Azerbaijani','2026-09-10 11:54:24','2026-09-10 11:54:24'),(12,'Bahamian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(13,'Bahraini','2026-09-10 11:54:24','2026-09-10 11:54:24'),(14,'Bangladeshi','2026-09-10 11:54:24','2026-09-10 11:54:24'),(15,'Barbadian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(16,'Belarusian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(17,'Belgian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(18,'Belizean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(19,'Beninese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(20,'Bhutanese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(21,'Bolivian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(22,'Bosnian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(23,'Brazilian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(24,'British','2026-09-10 11:54:24','2026-09-10 11:54:24'),(25,'Bruneian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(26,'Bulgarian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(27,'Burkinabe','2026-09-10 11:54:24','2026-09-10 11:54:24'),(28,'Burmese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(29,'Burundian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(30,'Cambodian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(31,'Cameroonian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(32,'Canadian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(33,'Cape Verdean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(34,'Central African','2026-09-10 11:54:24','2026-09-10 11:54:24'),(35,'Chadian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(36,'Chilean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(37,'Chinese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(38,'Colombian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(39,'Comoran','2026-09-10 11:54:24','2026-09-10 11:54:24'),(40,'Congolese (Congo-Brazzaville)','2026-09-10 11:54:24','2026-09-10 11:54:24'),(41,'Congolese (Congo-Kinshasa)','2026-09-10 11:54:24','2026-09-10 11:54:24'),(42,'Costa Rican','2026-09-10 11:54:24','2026-09-10 11:54:24'),(43,'Croatian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(44,'Cuban','2026-09-10 11:54:24','2026-09-10 11:54:24'),(45,'Cypriot','2026-09-10 11:54:24','2026-09-10 11:54:24'),(46,'Czech','2026-09-10 11:54:24','2026-09-10 11:54:24'),(47,'Danish','2026-09-10 11:54:24','2026-09-10 11:54:24'),(48,'Djiboutian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(49,'Dominican (Dominica)','2026-09-10 11:54:24','2026-09-10 11:54:24'),(50,'Dominican (Dominican Republic)','2026-09-10 11:54:24','2026-09-10 11:54:24'),(51,'Ecuadorean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(52,'Egyptian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(53,'Emirati','2026-09-10 11:54:24','2026-09-10 11:54:24'),(54,'Equatorial Guinean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(55,'Eritrean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(56,'Estonian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(57,'Ethiopian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(58,'Fijian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(59,'Finnish','2026-09-10 11:54:24','2026-09-10 11:54:24'),(60,'French','2026-09-10 11:54:24','2026-09-10 11:54:24'),(61,'Gabonese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(62,'Gambian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(63,'Georgian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(64,'German','2026-09-10 11:54:24','2026-09-10 11:54:24'),(65,'Ghanaian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(66,'Greek','2026-09-10 11:54:24','2026-09-10 11:54:24'),(67,'Grenadian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(68,'Guatemalan','2026-09-10 11:54:24','2026-09-10 11:54:24'),(69,'Guinea-Bissauan','2026-09-10 11:54:24','2026-09-10 11:54:24'),(70,'Guinean','2026-09-10 11:54:24','2026-09-10 11:54:24'),(71,'Guyanese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(72,'Haitian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(73,'Herzegovinian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(74,'Honduran','2026-09-10 11:54:24','2026-09-10 11:54:24'),(75,'Hungarian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(76,'I-Kiribati','2026-09-10 11:54:24','2026-09-10 11:54:24'),(77,'Icelander','2026-09-10 11:54:24','2026-09-10 11:54:24'),(78,'Indian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(79,'Indonesian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(80,'Iranian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(81,'Iraqi','2026-09-10 11:54:24','2026-09-10 11:54:24'),(82,'Irish','2026-09-10 11:54:24','2026-09-10 11:54:24'),(83,'Israeli','2026-09-10 11:54:24','2026-09-10 11:54:24'),(84,'Italian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(85,'Ivorian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(86,'Jamaican','2026-09-10 11:54:24','2026-09-10 11:54:24'),(87,'Japanese','2026-09-10 11:54:24','2026-09-10 11:54:24'),(88,'Jordanian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(89,'Kazakhstani','2026-09-10 11:54:24','2026-09-10 11:54:24'),(90,'Kenyan','2026-09-10 11:54:24','2026-09-10 11:54:24'),(91,'Kittian and Nevisian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(92,'Kuwaiti','2026-09-10 11:54:24','2026-09-10 11:54:24'),(93,'Kyrgyz','2026-09-10 11:54:24','2026-09-10 11:54:24'),(94,'Laotian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(95,'Latvian','2026-09-10 11:54:24','2026-09-10 11:54:24'),(96,'Lebanese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(97,'Liberian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(98,'Libyan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(99,'Liechtensteiner','2026-09-10 11:54:25','2026-09-10 11:54:25'),(100,'Lithuanian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(101,'Luxembourger','2026-09-10 11:54:25','2026-09-10 11:54:25'),(102,'Macedonian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(103,'Malagasy','2026-09-10 11:54:25','2026-09-10 11:54:25'),(104,'Malawian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(105,'Malaysian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(106,'Maldivian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(107,'Malian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(108,'Maltese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(109,'Marshallese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(110,'Mauritanian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(111,'Mauritian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(112,'Mexican','2026-09-10 11:54:25','2026-09-10 11:54:25'),(113,'Micronesian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(114,'Moldovan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(115,'Monacan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(116,'Mongolian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(117,'Montenegrin','2026-09-10 11:54:25','2026-09-10 11:54:25'),(118,'Moroccan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(119,'Mosotho','2026-09-10 11:54:25','2026-09-10 11:54:25'),(120,'Motswana','2026-09-10 11:54:25','2026-09-10 11:54:25'),(121,'Mozambican','2026-09-10 11:54:25','2026-09-10 11:54:25'),(122,'Namibian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(123,'Nauruan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(124,'Nepalese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(125,'New Zealander','2026-09-10 11:54:25','2026-09-10 11:54:25'),(126,'Nicaraguan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(127,'Nigerian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(128,'Nigerien','2026-09-10 11:54:25','2026-09-10 11:54:25'),(129,'North Korean','2026-09-10 11:54:25','2026-09-10 11:54:25'),(130,'Northern Irish','2026-09-10 11:54:25','2026-09-10 11:54:25'),(131,'Norwegian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(132,'Omani','2026-09-10 11:54:25','2026-09-10 11:54:25'),(133,'Pakistani','2026-09-10 11:54:25','2026-09-10 11:54:25'),(134,'Palauan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(135,'Panamanian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(136,'Papua New Guinean','2026-09-10 11:54:25','2026-09-10 11:54:25'),(137,'Paraguayan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(138,'Peruvian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(139,'Polish','2026-09-10 11:54:25','2026-09-10 11:54:25'),(140,'Portuguese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(141,'Qatari','2026-09-10 11:54:25','2026-09-10 11:54:25'),(142,'Romanian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(143,'Russian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(144,'Rwandan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(145,'Saint Lucian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(146,'Salvadoran','2026-09-10 11:54:25','2026-09-10 11:54:25'),(147,'Samoan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(148,'San Marinese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(149,'Sao Tomean','2026-09-10 11:54:25','2026-09-10 11:54:25'),(150,'Saudi','2026-09-10 11:54:25','2026-09-10 11:54:25'),(151,'Scottish','2026-09-10 11:54:25','2026-09-10 11:54:25'),(152,'Senegalese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(153,'Serbian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(154,'Seychellois','2026-09-10 11:54:25','2026-09-10 11:54:25'),(155,'Sierra Leonean','2026-09-10 11:54:25','2026-09-10 11:54:25'),(156,'Singaporean','2026-09-10 11:54:25','2026-09-10 11:54:25'),(157,'Slovakian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(158,'Slovenian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(159,'Solomon Islander','2026-09-10 11:54:25','2026-09-10 11:54:25'),(160,'Somali','2026-09-10 11:54:25','2026-09-10 11:54:25'),(161,'South African','2026-09-10 11:54:25','2026-09-10 11:54:25'),(162,'South Korean','2026-09-10 11:54:25','2026-09-10 11:54:25'),(163,'South Sudanese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(164,'Spanish','2026-09-10 11:54:25','2026-09-10 11:54:25'),(165,'Sri Lankan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(166,'Sudanese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(167,'Surinamer','2026-09-10 11:54:25','2026-09-10 11:54:25'),(168,'Swazi','2026-09-10 11:54:25','2026-09-10 11:54:25'),(169,'Swedish','2026-09-10 11:54:25','2026-09-10 11:54:25'),(170,'Swiss','2026-09-10 11:54:25','2026-09-10 11:54:25'),(171,'Syrian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(172,'Taiwanese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(173,'Tajikistani','2026-09-10 11:54:25','2026-09-10 11:54:25'),(174,'Tanzanian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(175,'Thai','2026-09-10 11:54:25','2026-09-10 11:54:25'),(176,'Togolese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(177,'Tongan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(178,'Trinidadian/Tobagonian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(179,'Tunisian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(180,'Turkish','2026-09-10 11:54:25','2026-09-10 11:54:25'),(181,'Tuvaluan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(182,'Ugandan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(183,'Ukrainian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(184,'Uruguayan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(185,'Uzbekistani','2026-09-10 11:54:25','2026-09-10 11:54:25'),(186,'Venezuelan','2026-09-10 11:54:25','2026-09-10 11:54:25'),(187,'Vietnamese','2026-09-10 11:54:25','2026-09-10 11:54:25'),(188,'Yemeni','2026-09-10 11:54:25','2026-09-10 11:54:25'),(189,'Zambian','2026-09-10 11:54:25','2026-09-10 11:54:25'),(190,'Zimbabwean','2026-09-10 11:54:25','2026-09-10 11:54:25');
/*!40000 ALTER TABLE `nationalities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `note_applications`
--

DROP TABLE IF EXISTS `note_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_type` varchar(191) DEFAULT NULL,
  `note_id` bigint(20) unsigned DEFAULT NULL,
  `applied_to_type` varchar(191) DEFAULT NULL,
  `applied_to_id` bigint(20) unsigned DEFAULT NULL,
  `applied_amount` decimal(14,2) NOT NULL,
  `applied_by` bigint(20) unsigned DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `note_applications_note_type_note_id_index` (`note_type`,`note_id`),
  KEY `note_applications_applied_to_type_applied_to_id_index` (`applied_to_type`,`applied_to_id`),
  KEY `note_applications_note_id_note_type_index` (`note_id`,`note_type`),
  KEY `note_applications_applied_to_id_applied_to_type_index` (`applied_to_id`,`applied_to_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note_applications`
--

LOCK TABLES `note_applications` WRITE;
/*!40000 ALTER TABLE `note_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `note_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `note_types`
--

DROP TABLE IF EXISTS `note_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `note_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note_types`
--

LOCK TABLES `note_types` WRITE;
/*!40000 ALTER TABLE `note_types` DISABLE KEYS */;
INSERT INTO `note_types` VALUES (1,'Email',NULL,NULL),(2,'Call',NULL,NULL),(3,'Text',NULL,NULL),(4,'General',NULL,NULL),(5,'MIS',NULL,NULL);
/*!40000 ALTER TABLE `note_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `type` enum('Email','Call','Text','General','MIS') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `visibility` enum('private','shared','portal') NOT NULL DEFAULT 'private',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notes_property_id_foreign` (`property_id`),
  KEY `notes_user_id_foreign` (`user_id`),
  KEY `notes_account_id_idx` (`account_id`),
  CONSTRAINT `notes_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_logs`
--

DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `identifier` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `subject_type` varchar(191) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `notification_uuid` char(36) DEFAULT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `channel` varchar(191) NOT NULL,
  `recipient` varchar(191) DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `message` text NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempt` int(10) unsigned NOT NULL DEFAULT 0,
  `max_attempts` int(10) unsigned NOT NULL DEFAULT 3,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_logs_idempotency_unique` (`idempotency_key`),
  KEY `notification_logs_identifier_channel_index` (`identifier`,`channel`),
  KEY `notification_logs_status_attempt_index` (`status`,`attempt`),
  KEY `notification_logs_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notification_logs_account_status_idx` (`account_id`,`status`,`created_at`),
  KEY `notification_logs_subject_idx` (`subject_type`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_logs`
--

LOCK TABLES `notification_logs` WRITE;
/*!40000 ALTER TABLE `notification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event_key` varchar(191) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT NULL,
  `in_app_enabled` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_preferences_unique` (`account_id`,`user_id`,`event_key`),
  KEY `notification_preferences_account_id_event_key_index` (`account_id`,`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) NOT NULL,
  `event_key` varchar(191) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `action_url` text DEFAULT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_account_unread_idx` (`notifiable_type`,`notifiable_id`,`account_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) NOT NULL,
  `term` varchar(255) NOT NULL,
  `move_in_date` date NOT NULL,
  `tenant_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tenant_details`)),
  `status` enum('Pending','Accepted','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offers_property_id_foreign` (`property_id`),
  CONSTRAINT `offers_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offers`
--

LOCK TABLES `offers` WRITE;
/*!40000 ALTER TABLE `offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_configurations`
--

DROP TABLE IF EXISTS `otp_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) NOT NULL,
  `value` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_configurations`
--

LOCK TABLES `otp_configurations` WRITE;
/*!40000 ALTER TABLE `otp_configurations` DISABLE KEYS */;
INSERT INTO `otp_configurations` VALUES (1,'fast2sms',1,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(2,'twillo',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(3,'nexmo',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(4,'mimsms',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(5,'mimo',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(6,'msegat',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(7,'smsgatewayhub',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(8,'sparrow',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(9,'ssl_wireless',0,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(10,'zender',0,'2026-09-10 11:52:20','2026-09-10 11:52:20');
/*!40000 ALTER TABLE `otp_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_group`
--

DROP TABLE IF EXISTS `owner_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_group` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `purchased_date` date NOT NULL,
  `sold_date` date DEFAULT NULL,
  `archived_date` date DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_group_property_id_foreign` (`property_id`),
  KEY `owner_group_deleted_by_foreign` (`deleted_by`),
  KEY `owner_group_added_by_foreign` (`added_by`),
  KEY `owner_group_updated_by_foreign` (`updated_by`),
  CONSTRAINT `owner_group_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `owner_group_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `owner_group_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `owner_group_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_group`
--

LOCK TABLES `owner_group` WRITE;
/*!40000 ALTER TABLE `owner_group` DISABLE KEYS */;
INSERT INTO `owner_group` VALUES (1,4,'2026-09-14',NULL,NULL,'active','2026-09-14 10:01:59','2026-09-14 10:01:59',NULL,6,NULL,NULL);
/*!40000 ALTER TABLE `owner_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_group_users`
--

DROP TABLE IF EXISTS `owner_group_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_group_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_group_users_owner_group_id_foreign` (`owner_group_id`),
  KEY `owner_group_users_user_id_foreign` (`user_id`),
  KEY `owner_group_users_added_by_foreign` (`added_by`),
  KEY `owner_group_users_updated_by_foreign` (`updated_by`),
  KEY `owner_group_users_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `owner_group_users_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `owner_group_users_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `owner_group_users_owner_group_id_foreign` FOREIGN KEY (`owner_group_id`) REFERENCES `owner_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `owner_group_users_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `owner_group_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_group_users`
--

LOCK TABLES `owner_group_users` WRITE;
/*!40000 ALTER TABLE `owner_group_users` DISABLE KEYS */;
INSERT INTO `owner_group_users` VALUES (1,1,6,1,'2026-09-14 10:01:59','2026-09-14 10:01:59',NULL,6,NULL,NULL);
/*!40000 ALTER TABLE `owner_group_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Cash','CASH',1,'2026-09-10 11:54:29','2026-09-10 11:54:29'),(2,'Bank Transfer','BANK',1,'2026-09-10 11:54:29','2026-09-10 11:54:29'),(3,'Card','CARD',1,'2026-09-10 11:54:29','2026-09-10 11:54:29');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view accounting reports','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(2,'manage gl accounts','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(3,'manage gl journals','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(4,'manage receipts','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(5,'manage bank reconciliation','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(6,'manage fixed assets','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(7,'close accounting periods','web','2026-09-10 11:52:19','2026-09-10 11:52:19'),(8,'manage own company','web','2026-09-10 11:52:20','2026-09-10 11:52:20'),(9,'transfer company owner','web','2026-09-10 11:52:20','2026-09-10 11:52:20'),(10,'download property brochure','web','2026-09-10 11:52:20','2026-09-10 11:52:20'),(11,'manage registrations','web','2026-09-10 11:52:23','2026-09-10 11:52:23'),(12,'view dashboard','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(13,'view properties','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(14,'create properties','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(15,'edit properties','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(16,'delete properties','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(17,'view property owners','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(18,'view property tenancy','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(19,'view property documents','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(20,'view tenants','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(21,'create tenants','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(22,'edit tenants','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(23,'view documents','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(24,'view rent payments','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(25,'view maintenance requests','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(26,'view communication log','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(27,'view own lease info','web','2026-09-10 11:52:24','2026-09-10 11:52:24'),(28,'access settings','web','2026-09-10 11:52:31','2026-09-10 11:52:31'),(29,'manage roles & permissions','web','2026-09-10 11:52:32','2026-09-10 11:52:32'),(30,'manage users','web','2026-09-10 11:52:32','2026-09-10 11:52:32'),(31,'view own profile','web','2026-09-10 11:52:32','2026-09-10 11:52:32'),(32,'view office profiles','web','2026-09-10 11:52:32','2026-09-10 11:52:32'),(33,'view all profiles','web','2026-09-10 11:52:32','2026-09-10 11:52:32'),(34,'assign properties to landlord','web','2026-09-10 11:52:32','2026-09-10 11:52:32'),(35,'delete tenants','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(36,'assign tenants to property','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(37,'end tenancy','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(38,'create maintenance requests','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(39,'assign maintenance tasks','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(40,'update maintenance status','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(41,'complete maintenance tasks','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(42,'view users','web','2026-09-10 11:52:33','2026-09-10 11:52:33'),(43,'create users','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(44,'edit users','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(45,'delete users','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(46,'manage user categories','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(47,'upload documents','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(48,'download documents','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(49,'delete documents','web','2026-09-10 11:52:34','2026-09-10 11:52:34'),(50,'view invoices','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(51,'create invoices','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(52,'edit invoices','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(53,'delete invoices','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(54,'mark invoice paid','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(55,'view reports','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(56,'send notifications','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(57,'view calendar','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(58,'view all staffs','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(59,'add staff','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(60,'edit staff','web','2026-09-10 11:52:35','2026-09-10 11:52:35'),(61,'delete staff','web','2026-09-10 11:52:36','2026-09-10 11:52:36'),(62,'view staff roles','web','2026-09-10 11:52:36','2026-09-10 11:52:36'),(63,'add staff role','web','2026-09-10 11:52:36','2026-09-10 11:52:36'),(64,'edit staff role','web','2026-09-10 11:52:36','2026-09-10 11:52:36'),(65,'delete staff role','web','2026-09-10 11:52:36','2026-09-10 11:52:36'),(66,'view contacts','web','2026-09-10 11:52:37','2026-09-10 11:52:37'),(67,'view property repair','web','2026-09-10 11:52:37','2026-09-10 11:52:37'),(68,'create property repair','web','2026-09-10 11:52:37','2026-09-10 11:52:37');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `target_account_type` enum('landlord','estate_agent_freelance','estate_agent_company') NOT NULL,
  `description` text DEFAULT NULL,
  `monthly_price_minor` int(11) NOT NULL DEFAULT 0,
  `annual_price_minor` int(11) NOT NULL DEFAULT 0,
  `currency` varchar(191) NOT NULL DEFAULT 'GBP',
  `stripe_monthly_price_id` varchar(191) DEFAULT NULL,
  `stripe_annual_price_id` varchar(191) DEFAULT NULL,
  `trial_days` int(11) NOT NULL DEFAULT 7,
  `property_limit` int(11) NOT NULL DEFAULT 0,
  `branch_limit` int(11) NOT NULL DEFAULT 0,
  `staff_limit` int(11) NOT NULL DEFAULT 0,
  `property_manager_limit` int(11) NOT NULL DEFAULT 0,
  `allow_company_profile` tinyint(1) NOT NULL DEFAULT 0,
  `allow_invoice_branding` tinyint(1) NOT NULL DEFAULT 0,
  `allow_roles_permissions` tinyint(1) NOT NULL DEFAULT 0,
  `allow_contact_login` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_code_unique` (`code`),
  KEY `plans_target_account_type_index` (`target_account_type`),
  KEY `plans_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (1,'landlord_basic','Landlord Basic','landlord','Staging landlord plan with one property and no branch or staff access.',2900,29000,'GBP',NULL,NULL,7,5,0,0,0,0,0,0,1,1,10,'2026-09-10 11:54:31','2026-09-10 11:54:38'),(2,'estate_agent_company','Estate Agent Company','estate_agent_company','Staging estate-agent company plan with two properties, one branch, and one staff user.',14900,149000,'GBP',NULL,NULL,7,2,1,1,1,1,1,1,1,1,20,'2026-09-10 11:54:31','2026-09-10 11:54:39');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prop_ref_no` varchar(191) DEFAULT NULL,
  `prop_name` varchar(191) DEFAULT NULL,
  `line_1` varchar(191) DEFAULT NULL,
  `line_2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `country` bigint(20) unsigned DEFAULT NULL,
  `county` varchar(155) DEFAULT NULL,
  `currency` varchar(155) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `frunishing_type` varchar(191) DEFAULT NULL,
  `property_type` varchar(191) DEFAULT NULL,
  `transaction_type` varchar(191) DEFAULT NULL,
  `specific_property_type` varchar(191) DEFAULT NULL,
  `bedroom` varchar(191) DEFAULT NULL,
  `bathroom` varchar(191) DEFAULT NULL,
  `reception` varchar(191) DEFAULT NULL,
  `parking` varchar(191) DEFAULT NULL,
  `parking_location` varchar(50) DEFAULT NULL,
  `balcony` varchar(191) DEFAULT NULL,
  `garden` varchar(191) DEFAULT NULL,
  `service` varchar(191) DEFAULT NULL,
  `management` varchar(191) DEFAULT NULL,
  `collecting_rent` varchar(191) DEFAULT NULL,
  `floor` varchar(191) DEFAULT NULL,
  `square_feet` decimal(10,4) DEFAULT NULL,
  `square_meter` decimal(10,4) DEFAULT NULL,
  `aspects` varchar(191) DEFAULT NULL,
  `sales_current_status` varchar(155) DEFAULT NULL,
  `letting_current_status` varchar(155) DEFAULT NULL,
  `sales_status_description` longtext DEFAULT NULL,
  `letting_status_description` longtext DEFAULT NULL,
  `available_from` date DEFAULT NULL,
  `pets_allow` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 for yes, 0 for no',
  `market_on` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`market_on`)),
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `furniture` varchar(555) DEFAULT NULL,
  `kitchen` varchar(555) DEFAULT NULL,
  `heating_cooling` varchar(555) DEFAULT NULL,
  `safety` varchar(555) DEFAULT NULL,
  `other` varchar(555) DEFAULT NULL,
  `access_arrangement` varchar(255) DEFAULT NULL,
  `key_highlights` varchar(255) DEFAULT NULL,
  `nearest_station` varchar(191) DEFAULT NULL,
  `nearest_school` varchar(191) DEFAULT NULL,
  `nearest_religious_places` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nearest_religious_places`)),
  `useful_information` varchar(255) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `ground_rent` decimal(12,2) DEFAULT NULL,
  `service_charge` decimal(12,2) DEFAULT NULL,
  `estate_charge` decimal(12,2) DEFAULT NULL,
  `miscellaneous_charge` decimal(12,2) DEFAULT NULL,
  `estate_charges_id` bigint(20) unsigned DEFAULT NULL,
  `annual_council_tax` decimal(10,2) DEFAULT NULL,
  `council_tax_band` varchar(191) DEFAULT NULL,
  `local_authority` varchar(255) DEFAULT NULL,
  `letting_price` decimal(10,2) DEFAULT NULL,
  `tenure` varchar(191) DEFAULT NULL,
  `length_of_lease` int(11) DEFAULT NULL,
  `epc_rating` varchar(191) DEFAULT NULL,
  `is_gas` tinyint(1) DEFAULT NULL,
  `gas_safe_acknowledged` tinyint(1) DEFAULT NULL,
  `photos` varchar(2000) DEFAULT NULL,
  `floor_plan` varchar(2000) DEFAULT NULL,
  `view_360` varchar(2000) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `designation` varchar(191) DEFAULT NULL,
  `branch` varchar(191) DEFAULT NULL,
  `commission_percentage` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `imp_notes` longtext DEFAULT NULL,
  `step` int(11) DEFAULT NULL,
  `quick_step` int(11) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `property_identity_hash` varchar(64) DEFAULT NULL,
  `uprn` varchar(32) DEFAULT NULL,
  `epc_required` tinyint(1) DEFAULT NULL,
  `youtube_url` text DEFAULT NULL,
  `instagram_url` text DEFAULT NULL,
  `nearest_places` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nearest_places`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `properties_account_identity_unique` (`account_id`,`property_identity_hash`),
  KEY `properties_added_by_foreign` (`added_by`),
  KEY `properties_deleted_by_foreign` (`deleted_by`),
  KEY `properties_account_id_idx` (`account_id`),
  KEY `properties_company_id_idx` (`company_id`),
  KEY `properties_branch_id_idx` (`branch_id`),
  KEY `properties_created_by_foreign` (`created_by`),
  KEY `properties_updated_by_foreign` (`updated_by`),
  CONSTRAINT `properties_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `properties`
--

LOCK TABLES `properties` WRITE;
/*!40000 ALTER TABLE `properties` DISABLE KEYS */;
INSERT INTO `properties` VALUES (1,'STG-LAND-001','Staging Landlord Property','1 Staging Landlord Street',NULL,'London',NULL,NULL,'GBP','ST1 1AA',NULL,'Residential','Lettings','Flat','2','1','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Available',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1250.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1250.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,6,6,6,'2026-09-14 10:04:14','2026-09-10 11:54:40','2026-09-14 10:04:14',1,NULL,NULL,'07b4ceaad478350c59e6a591c00f877924b358cba977e66446a6e36dab257e64',NULL,NULL,NULL,NULL,NULL),(2,'STG-EST-001','Staging Estate Property','10 Staging Estate Avenue',NULL,'London',NULL,NULL,'GBP','ST2 2AA',NULL,'Residential','Lettings','Flat','2','1','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Available',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1250.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1250.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,7,NULL,7,NULL,NULL,'2026-09-10 11:54:40','2026-09-10 11:54:40',2,2,1,'a1074b30cc852acfee7cb00ab0e264530ecf5c56d7b11cfa937f6c9764af7c5e',NULL,NULL,NULL,NULL,NULL),(3,'STG-EST-002','Staging Estate Unassigned Property','11 Staging Estate Avenue',NULL,'London',NULL,NULL,'GBP','ST2 2AB',NULL,'Residential','Lettings','Flat','2','1','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Available',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1250.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1250.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,7,NULL,7,NULL,NULL,'2026-09-10 11:54:40','2026-09-10 11:54:40',2,2,1,'f21c055110c2fe15adfa6f0afb548f9ae6ad2c9c361af8f9a82daaeb262965af',NULL,NULL,NULL,NULL,NULL),(4,'RESISQP0000001','Flat 108, 1 Baltimore Wharf','Flat 108, 1 Baltimore Wharf',NULL,'London',1,'Tower Hamlets','GBP','E14 9RU',NULL,'lettings',NULL,'flat','3','2','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'22',1033.0000,96.0000,NULL,NULL,'let agreed',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Sources: test, postcodes.io, findthatpostcode',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'F','Tower Hamlets',NULL,'Leasehold',NULL,'C',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,6,6,NULL,6,'2026-09-14 10:04:14','2026-09-14 10:01:59','2026-09-14 10:04:14',1,NULL,NULL,'6b993df69a7ea18312076523bf959180bbfdee1049acfea869fc205535c15cef','295684980300',1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_manager_tenancy`
--

DROP TABLE IF EXISTS `property_manager_tenancy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_manager_tenancy` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenancy_id` bigint(20) unsigned NOT NULL,
  `property_manager_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_manager_tenancy_tenancy_id_foreign` (`tenancy_id`),
  KEY `property_manager_tenancy_property_manager_id_foreign` (`property_manager_id`),
  KEY `property_manager_tenancy_property_id_foreign` (`property_id`),
  CONSTRAINT `property_manager_tenancy_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_manager_tenancy_property_manager_id_foreign` FOREIGN KEY (`property_manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_manager_tenancy_tenancy_id_foreign` FOREIGN KEY (`tenancy_id`) REFERENCES `tenancies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_manager_tenancy`
--

LOCK TABLES `property_manager_tenancy` WRITE;
/*!40000 ALTER TABLE `property_manager_tenancy` DISABLE KEYS */;
INSERT INTO `property_manager_tenancy` VALUES (1,1,12,1,'2026-09-10 11:54:40','2026-09-10 11:54:40');
/*!40000 ALTER TABLE `property_manager_tenancy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_participants`
--

DROP TABLE IF EXISTS `property_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `participant_type` enum('landlord','owner','tenant','contractor','property_manager','staff') NOT NULL,
  `access_level` enum('view','edit','full') NOT NULL DEFAULT 'view',
  `can_view_finance` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_documents` tinyint(1) NOT NULL DEFAULT 0,
  `can_upload_documents` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_participants_account_property_user_type_unique` (`account_id`,`property_id`,`user_id`,`participant_type`),
  KEY `property_participants_account_id_user_id_index` (`account_id`,`user_id`),
  KEY `property_participants_property_id_index` (`property_id`),
  KEY `property_participants_participant_type_index` (`participant_type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_participants`
--

LOCK TABLES `property_participants` WRITE;
/*!40000 ALTER TABLE `property_participants` DISABLE KEYS */;
INSERT INTO `property_participants` VALUES (1,1,1,9,'landlord','view',1,1,1,'active',6,'2026-09-10 11:54:40','2026-09-10 11:54:40'),(2,1,1,10,'tenant','view',0,1,0,'active',6,'2026-09-10 11:54:40','2026-09-10 11:54:40'),(3,1,1,11,'contractor','view',0,0,0,'active',6,'2026-09-10 11:54:40','2026-09-10 11:54:40'),(4,1,1,12,'property_manager','full',1,1,1,'active',6,'2026-09-10 11:54:40','2026-09-10 11:54:40'),(5,1,4,14,'tenant','view',0,1,0,'active',6,'2026-09-14 10:02:54','2026-09-14 10:02:54');
/*!40000 ALTER TABLE `property_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_responsibilities`
--

DROP TABLE IF EXISTS `property_responsibilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_responsibilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `responsibility_type` varchar(191) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `commission_percentage` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_responsibilities_property_id_foreign` (`property_id`),
  KEY `property_responsibilities_user_id_foreign` (`user_id`),
  KEY `property_responsibilities_branch_id_foreign` (`branch_id`),
  KEY `property_responsibilities_designation_id_foreign` (`designation_id`),
  KEY `property_responsibilities_added_by_foreign` (`added_by`),
  KEY `property_responsibilities_deleted_by_foreign` (`deleted_by`),
  KEY `property_responsibilities_account_idx` (`account_id`),
  CONSTRAINT `property_responsibilities_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_responsibilities_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `property_responsibilities_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_responsibilities_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`),
  CONSTRAINT `property_responsibilities_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  CONSTRAINT `property_responsibilities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_responsibilities`
--

LOCK TABLES `property_responsibilities` WRITE;
/*!40000 ALTER TABLE `property_responsibilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_responsibilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoice_items`
--

DROP TABLE IF EXISTS `purchase_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `total_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_invoice_items_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  CONSTRAINT `purchase_invoice_items_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoice_items`
--

LOCK TABLES `purchase_invoice_items` WRITE;
/*!40000 ALTER TABLE `purchase_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(191) NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_invoices_invoice_number_unique` (`invoice_number`),
  KEY `purchase_invoices_supplier_id_index` (`supplier_id`),
  KEY `purchase_invoices_property_id_index` (`property_id`),
  KEY `purchase_invoices_status_id_index` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoices`
--

LOCK TABLES `purchase_invoices` WRITE;
/*!40000 ALTER TABLE `purchase_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(191) NOT NULL,
  `last_name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `type` enum('landlord','owner','freelancing_agent','contractor','estate_agent') NOT NULL,
  `verify_via` enum('email','phone') DEFAULT NULL,
  `password_hash` varchar(191) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `otp_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_token` varchar(100) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_otp` varchar(10) DEFAULT NULL,
  `phone_otp_expires_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','email_verified','phone_verified','verified','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `ref_url` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_type` enum('landlord','estate_agent_freelance','estate_agent_company') DEFAULT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `billing_cycle` enum('monthly','annual') DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registrations_email_verification_token_unique` (`email_verification_token`),
  KEY `registrations_account_id_idx` (`account_id`),
  KEY `registrations_plan_id_idx` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `religious_places`
--

DROP TABLE IF EXISTS `religious_places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `religious_places` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `religious_places_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `religious_places`
--

LOCK TABLES `religious_places` WRITE;
/*!40000 ALTER TABLE `religious_places` DISABLE KEYS */;
INSERT INTO `religious_places` VALUES (1,'Masjid al-Haram','2026-09-10 11:53:26','2026-09-10 11:53:26'),(2,'St. Mary’s Church','2026-09-10 11:53:26','2026-09-10 11:53:26'),(3,'ISKCON Temple','2026-09-10 11:53:26','2026-09-10 11:53:26'),(4,'Buddhist Temple','2026-09-10 11:53:26','2026-09-10 11:53:26'),(5,'Synagogue of London','2026-09-10 11:53:26','2026-09-10 11:53:26');
/*!40000 ALTER TABLE `religious_places` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rent_invoices`
--

DROP TABLE IF EXISTS `rent_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rent_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned NOT NULL,
  `tenancy_id` bigint(20) unsigned NOT NULL,
  `tenant_user_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(32) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance` decimal(12,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'issued',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rent_invoices_account_id_invoice_no_unique` (`account_id`,`invoice_no`),
  KEY `rent_invoices_account_id_tenant_user_id_index` (`account_id`,`tenant_user_id`),
  KEY `rent_invoices_account_id_tenancy_id_index` (`account_id`,`tenancy_id`),
  KEY `rent_invoices_account_id_index` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rent_invoices`
--

LOCK TABLES `rent_invoices` WRITE;
/*!40000 ALTER TABLE `rent_invoices` DISABLE KEYS */;
INSERT INTO `rent_invoices` VALUES (1,1,1,1,10,'RENT-0001','2026-09-14','2026-09-28',NULL,NULL,1200.00,0.00,'paid','helo','2026-09-14 09:38:22','2026-09-14 09:40:46');
/*!40000 ALTER TABLE `rent_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rent_payments`
--

DROP TABLE IF EXISTS `rent_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rent_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `rent_invoice_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `fee_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_at` date NOT NULL,
  `method` varchar(32) NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `stripe_checkout_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `recorded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rent_payments_stripe_checkout_session_id_unique` (`stripe_checkout_session_id`),
  KEY `rent_payments_account_id_rent_invoice_id_index` (`account_id`,`rent_invoice_id`),
  KEY `rent_payments_account_id_index` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rent_payments`
--

LOCK TABLES `rent_payments` WRITE;
/*!40000 ALTER TABLE `rent_payments` DISABLE KEYS */;
INSERT INTO `rent_payments` VALUES (1,1,1,1200.00,0.00,'2026-09-14','bank_transfer','wewewe',NULL,NULL,6,'2026-09-14 09:40:46','2026-09-14 09:40:46');
/*!40000 ALTER TABLE `rent_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_assignments`
--

DROP TABLE IF EXISTS `repair_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('assigned','in-progress','completed','closed') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_assignments_repair_issue_id_foreign` (`repair_issue_id`),
  KEY `repair_assignments_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `repair_assignments_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_assignments_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_assignments`
--

LOCK TABLES `repair_assignments` WRITE;
/*!40000 ALTER TABLE `repair_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `repair_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_categories`
--

DROP TABLE IF EXISTS `repair_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `level` int(11) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_categories_name_parent_id_level_unique` (`name`,`parent_id`,`level`),
  KEY `repair_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `repair_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `repair_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_categories`
--

LOCK TABLES `repair_categories` WRITE;
/*!40000 ALTER TABLE `repair_categories` DISABLE KEYS */;
INSERT INTO `repair_categories` VALUES (1,'Staging General Repair',NULL,1,'General staging repair category.','placeholder.svg',999,1,'2026-09-10 11:54:40','2026-09-10 11:54:40');
/*!40000 ALTER TABLE `repair_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_histories`
--

DROP TABLE IF EXISTS `repair_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `action` text NOT NULL,
  `previous_status` varchar(191) DEFAULT NULL,
  `new_status` varchar(191) NOT NULL,
  `note` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_histories_repair_issue_id_foreign` (`repair_issue_id`),
  CONSTRAINT `repair_histories_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_histories`
--

LOCK TABLES `repair_histories` WRITE;
/*!40000 ALTER TABLE `repair_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `repair_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_issue_contractor_assignments`
--

DROP TABLE IF EXISTS `repair_issue_contractor_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_issue_contractor_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `contractor_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `quote_attachment` varchar(191) DEFAULT NULL,
  `contractor_preferred_availability` timestamp NULL DEFAULT NULL,
  `contractor_availability_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contractor_availability_options`)),
  `consultant_name` varchar(191) DEFAULT NULL,
  `consultant_phone` varchar(191) DEFAULT NULL,
  `tentative_start_date` date DEFAULT NULL,
  `tentative_end_date` date DEFAULT NULL,
  `quote_notes` text DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'Proposed',
  `quote_token` varchar(80) DEFAULT NULL,
  `quote_requested_at` timestamp NULL DEFAULT NULL,
  `quote_submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_issue_contractor_assignments_quote_token_unique` (`quote_token`),
  KEY `repair_issue_contractor_assignments_repair_issue_id_foreign` (`repair_issue_id`),
  KEY `repair_issue_contractor_assignments_contractor_id_foreign` (`contractor_id`),
  KEY `repair_issue_contractor_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `rica_account_id_idx` (`account_id`),
  CONSTRAINT `repair_issue_contractor_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_issue_contractor_assignments_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_issue_contractor_assignments_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_issue_contractor_assignments`
--

LOCK TABLES `repair_issue_contractor_assignments` WRITE;
/*!40000 ALTER TABLE `repair_issue_contractor_assignments` DISABLE KEYS */;
INSERT INTO `repair_issue_contractor_assignments` VALUES (1,1,11,12,150.00,NULL,'2026-09-13 11:54:40',NULL,NULL,NULL,NULL,NULL,NULL,'Assigned','staging-quote-token-001','2026-09-10 11:54:40',NULL,'2026-09-10 11:54:40','2026-09-10 11:54:40',1);
/*!40000 ALTER TABLE `repair_issue_contractor_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_issue_property_managers`
--

DROP TABLE IF EXISTS `repair_issue_property_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_issue_property_managers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `property_manager_id` bigint(20) unsigned NOT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_issue_property_managers_repair_issue_id_foreign` (`repair_issue_id`),
  KEY `repair_issue_property_managers_property_manager_id_foreign` (`property_manager_id`),
  KEY `ripm_account_id_idx` (`account_id`),
  CONSTRAINT `repair_issue_property_managers_property_manager_id_foreign` FOREIGN KEY (`property_manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_issue_property_managers_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_issue_property_managers`
--

LOCK TABLES `repair_issue_property_managers` WRITE;
/*!40000 ALTER TABLE `repair_issue_property_managers` DISABLE KEYS */;
INSERT INTO `repair_issue_property_managers` VALUES (1,1,12,'2026-09-10 11:54:40',6,'Staging property manager repair assignment.','2026-09-10 11:54:40','2026-09-10 11:54:40',1);
/*!40000 ALTER TABLE `repair_issue_property_managers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_issue_users`
--

DROP TABLE IF EXISTS `repair_issue_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_issue_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_category_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_issue_users_repair_issue_id_foreign` (`repair_issue_id`),
  KEY `repair_issue_users_user_id_foreign` (`user_id`),
  CONSTRAINT `repair_issue_users_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_issue_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_issue_users`
--

LOCK TABLES `repair_issue_users` WRITE;
/*!40000 ALTER TABLE `repair_issue_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `repair_issue_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_issues`
--

DROP TABLE IF EXISTS `repair_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `repair_category_id` bigint(20) unsigned NOT NULL,
  `repair_navigation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`repair_navigation`)),
  `description` longtext NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `tenant_availability` timestamp NULL DEFAULT NULL,
  `access_details` text DEFAULT NULL,
  `estimated_price` decimal(10,2) DEFAULT NULL,
  `vat_type` enum('inclusive','exclusive') DEFAULT NULL,
  `priority` enum('low','medium','high','critical') NOT NULL,
  `sub_status` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL,
  `final_contractor_id` bigint(20) unsigned NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_issues_property_id_foreign` (`property_id`),
  KEY `repair_issues_repair_category_id_foreign` (`repair_category_id`),
  KEY `repair_issues_tenant_id_foreign` (`tenant_id`),
  KEY `repair_issues_final_contractor_id_foreign` (`final_contractor_id`),
  KEY `repair_issues_created_by_foreign` (`created_by`),
  KEY `repair_issues_updated_by_foreign` (`updated_by`),
  KEY `repair_issues_account_id_idx` (`account_id`),
  CONSTRAINT `repair_issues_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repair_issues_final_contractor_id_foreign` FOREIGN KEY (`final_contractor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `repair_issues_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  CONSTRAINT `repair_issues_repair_category_id_foreign` FOREIGN KEY (`repair_category_id`) REFERENCES `repair_categories` (`id`),
  CONSTRAINT `repair_issues_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`),
  CONSTRAINT `repair_issues_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_issues`
--

LOCK TABLES `repair_issues` WRITE;
/*!40000 ALTER TABLE `repair_issues` DISABLE KEYS */;
INSERT INTO `repair_issues` VALUES (1,1,1,'[\"Staging General Repair\"]','Staging repair for contractor portal testing.',10,'2026-09-12 11:54:40','Use staging lockbox code 0000.',150.00,'exclusive','medium','Assigned','Open',11,'STG-REP-001',6,6,'2026-09-10 11:54:40','2026-09-10 11:54:40',1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `repair_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_photos`
--

DROP TABLE IF EXISTS `repair_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `photos` varchar(2000) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_photos_repair_issue_id_foreign` (`repair_issue_id`),
  CONSTRAINT `repair_photos_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_photos`
--

LOCK TABLES `repair_photos` WRITE;
/*!40000 ALTER TABLE `repair_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `repair_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,1),(12,2),(12,3),(12,4),(12,5),(12,6),(13,1),(13,2),(13,3),(13,4),(13,5),(13,6),(14,1),(14,2),(14,4),(14,6),(15,1),(15,2),(15,4),(15,6),(16,1),(16,2),(16,4),(16,6),(17,1),(17,2),(17,5),(18,1),(18,2),(18,5),(19,1),(19,2),(20,1),(20,2),(20,3),(20,4),(20,6),(21,1),(21,2),(21,4),(21,6),(22,1),(22,2),(22,4),(22,6),(23,1),(23,2),(23,4),(23,5),(23,6),(24,1),(24,2),(24,3),(24,4),(25,1),(25,2),(25,4),(25,5),(25,6),(25,8),(26,1),(26,2),(26,4),(26,5),(26,6),(27,1),(27,2),(27,5),(27,8),(28,2),(29,2),(30,2),(30,4),(30,6),(31,2),(31,3),(31,5),(32,2),(32,6),(33,2),(33,4),(34,1),(34,2),(34,4),(34,6),(35,1),(35,2),(35,4),(35,6),(36,1),(36,2),(36,4),(36,6),(37,2),(38,2),(38,4),(38,5),(38,6),(39,2),(39,4),(40,2),(40,4),(40,8),(41,2),(41,4),(41,8),(42,2),(42,4),(43,2),(44,2),(45,2),(46,2),(46,4),(47,2),(47,4),(47,5),(48,2),(48,4),(49,2),(49,4),(50,2),(51,2),(51,4),(52,2),(52,4),(53,2),(54,2),(54,4),(55,2),(55,3),(55,4),(56,2),(56,4),(57,1),(57,2),(57,4),(57,6),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(65,2),(66,2),(66,5),(67,2),(67,5),(68,2),(68,5);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `guard_name` varchar(191) NOT NULL DEFAULT 'web',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Landlord','2026-09-10 11:52:24','2026-09-10 11:52:24','web'),(2,'Super Admin','2026-09-10 11:52:36','2026-09-10 11:52:36','web'),(3,'Owner','2026-09-10 11:52:36','2026-09-10 11:52:36','web'),(4,'Property Manager','2026-09-10 11:52:36','2026-09-10 11:52:36','web'),(5,'Tenant','2026-09-10 11:52:36','2026-09-10 11:52:36','web'),(6,'Estate Agent','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(7,'Agent','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(8,'Contractor','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(9,'Maintenance','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(10,'Service Provider','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(11,'User','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(12,'Letting Applicant','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(13,'Sales Applicant','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(14,'Solicitor','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(15,'Other','2026-09-10 11:52:37','2026-09-10 11:52:37','web'),(16,'Staff','2026-09-10 11:52:37','2026-09-10 11:52:37','web');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_names`
--

DROP TABLE IF EXISTS `school_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_names` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_names_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_names`
--

LOCK TABLES `school_names` WRITE;
/*!40000 ALTER TABLE `school_names` DISABLE KEYS */;
INSERT INTO `school_names` VALUES (1,'Hampton High','2026-09-10 11:53:26','2026-09-10 11:53:26'),(2,'Tower House School','2026-09-10 11:53:26','2026-09-10 11:53:26'),(3,'Greenwich School','2026-09-10 11:53:26','2026-09-10 11:53:26'),(4,'St. Peter’s Academy','2026-09-10 11:53:26','2026-09-10 11:53:26'),(5,'East London Academy','2026-09-10 11:53:26','2026-09-10 11:53:26');
/*!40000 ALTER TABLE `school_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
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

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('NJMLEp3zlciXPNPOOOCiNd9XXpC1YpzLb9xLDjtf',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.137.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQURHWHp6RXN2cVNLM3JpYnlzZE4zeGFkdWpRVWh6dm5Tejg0UDFsWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1789378460),('Ug8B7VOotgy8bPyzcmMp4rggzKIBmtM64l4dH6lg',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoicDdpT1BobUJNQmltNWxrT3Q2NGpXMUUzUDFaWVdZdjVyUHVhQUJ0cSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi90ZW5hbmNpZXMvMiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjY7czoxODoiY3VycmVudF9hY2NvdW50X2lkIjtpOjE7czoyODoibGFuZGxvcmRfb25ib2FyZGluZ19kZWZlcnJlZCI7YjoxO30=',1789380357);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(100) NOT NULL,
  `sms_body` longtext NOT NULL,
  `template_id` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_templates`
--

LOCK TABLES `sms_templates` WRITE;
/*!40000 ALTER TABLE `sms_templates` DISABLE KEYS */;
INSERT INTO `sms_templates` VALUES (1,'phone_number_verification','Your [[site_name]] verification code is [[code]]. Valid for 2 minutes. Do not share.',NULL,1,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(2,'password_reset','Your [[site_name]] password reset code is [[code]]. Valid for 10 minutes.',NULL,1,'2026-09-10 11:52:20','2026-09-10 11:52:20'),(3,'account_opening','Welcome to [[site_name]]! Your account has been created. Login: [[code]]',NULL,1,'2026-09-10 11:52:20','2026-09-10 11:52:20');
/*!40000 ALTER TABLE `sms_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `permissions_customized` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `staff_user_id_foreign` (`user_id`),
  KEY `staff_parent_id_foreign` (`parent_id`),
  KEY `staff_branch_id_foreign` (`branch_id`),
  KEY `staff_account_id_idx` (`account_id`),
  CONSTRAINT `staff_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,8,7,0,'2026-09-10 11:54:40','2026-09-10 11:54:40',1,2,'active');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_contacts`
--

DROP TABLE IF EXISTS `staff_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `type` enum('email','phone') NOT NULL,
  `value` varchar(191) NOT NULL,
  `label` varchar(191) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_contacts_staff_id_index` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_contacts`
--

LOCK TABLES `staff_contacts` WRITE;
/*!40000 ALTER TABLE `staff_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `station_names`
--

DROP TABLE IF EXISTS `station_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_names` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `station_names_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `station_names`
--

LOCK TABLES `station_names` WRITE;
/*!40000 ALTER TABLE `station_names` DISABLE KEYS */;
INSERT INTO `station_names` VALUES (1,'Bakerloo','2026-09-10 11:53:26','2026-09-10 11:53:26'),(2,'Jubilee','2026-09-10 11:53:26','2026-09-10 11:53:26'),(3,'Northern','2026-09-10 11:53:26','2026-09-10 11:53:26'),(4,'Central','2026-09-10 11:53:26','2026-09-10 11:53:26'),(5,'Victoria','2026-09-10 11:53:26','2026-09-10 11:53:26');
/*!40000 ALTER TABLE `station_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_adjustment_notes`
--

DROP TABLE IF EXISTS `sys_adjustment_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_adjustment_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_type` enum('credit','debit') NOT NULL,
  `adjustment_reason` enum('return','refund','writeoff') DEFAULT NULL,
  `reference_type` enum('sale_invoice','purchase_invoice') NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `note_no` varchar(50) NOT NULL,
  `note_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `balance_amount` decimal(15,2) NOT NULL,
  `is_refunded` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_adjustment_notes_note_no_unique` (`note_no`),
  KEY `sys_adjustment_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `sys_adjustment_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_adjustment_notes`
--

LOCK TABLES `sys_adjustment_notes` WRITE;
/*!40000 ALTER TABLE `sys_adjustment_notes` DISABLE KEYS */;
INSERT INTO `sys_adjustment_notes` VALUES (1,'credit','writeoff','sale_invoice',10,1,'AN-888168','1981-11-28',2040.04,685.03,1,'Odio debitis amet expedita voluptatem non id tempora.','2026-09-10 11:54:29'),(2,'debit','return','purchase_invoice',18,2,'AN-992785','2017-04-03',148.65,953.80,1,NULL,'2026-09-10 11:54:29'),(3,'credit','return','sale_invoice',7,1,'AN-088437','1971-08-30',2188.30,995.39,1,NULL,'2026-09-10 11:54:29'),(4,'debit','refund','sale_invoice',20,2,'AN-574326','2018-02-06',300.76,11.40,0,NULL,'2026-09-10 11:54:29'),(5,'debit','refund','purchase_invoice',18,4,'AN-395128','1981-08-23',2648.61,37.35,1,NULL,'2026-09-10 11:54:29'),(6,'debit','refund','purchase_invoice',10,3,'AN-449369','1987-08-30',1657.75,1449.26,0,'Dicta rerum labore voluptatem maiores.','2026-09-10 11:54:29'),(7,'debit','refund','sale_invoice',11,2,'AN-375997','2009-04-13',2937.60,1418.48,0,NULL,'2026-09-10 11:54:29'),(8,'credit','writeoff','sale_invoice',10,2,'AN-123594','2000-04-01',1136.76,131.96,1,NULL,'2026-09-10 11:54:29'),(9,'debit','return','purchase_invoice',14,1,'AN-524733','2022-03-16',2984.58,628.55,0,NULL,'2026-09-10 11:54:29'),(10,'debit','return','sale_invoice',19,1,'AN-183317','2025-06-08',2433.73,511.47,0,'Debitis a est ab.','2026-09-10 11:54:29'),(11,'debit','return','purchase_invoice',20,3,'AN-470489','1998-04-17',2576.80,1004.68,0,NULL,'2026-09-10 11:54:29'),(12,'credit','refund','sale_invoice',13,1,'AN-472940','1994-06-10',2998.10,218.23,1,'Dignissimos minima saepe dolore.','2026-09-10 11:54:29'),(13,'credit','writeoff','sale_invoice',16,1,'AN-854877','2018-04-08',2449.39,582.62,0,'Consequatur perspiciatis ea ad rerum iure.','2026-09-10 11:54:29'),(14,'credit','writeoff','sale_invoice',17,2,'AN-885935','1997-11-17',2340.97,1292.64,0,NULL,'2026-09-10 11:54:29'),(15,'credit','refund','purchase_invoice',9,4,'AN-773904','1995-05-01',1421.69,407.09,0,NULL,'2026-09-10 11:54:29');
/*!40000 ALTER TABLE `sys_adjustment_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_bank_accounts`
--

DROP TABLE IF EXISTS `sys_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_name` varchar(191) DEFAULT NULL,
  `account_no` varchar(191) DEFAULT NULL,
  `sort_code` varchar(191) DEFAULT NULL,
  `bank_name` varchar(191) DEFAULT NULL,
  `swift_code` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `branch` varchar(191) DEFAULT NULL,
  `ifsc_code` varchar(191) DEFAULT NULL,
  `account_type` varchar(191) DEFAULT NULL,
  `purpose` varchar(191) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_type` enum('savings','current','overdraft') NOT NULL DEFAULT 'savings',
  `gl_account_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_bank_accounts_account_idx` (`account_id`),
  KEY `sys_bank_accounts_company_idx` (`company_id`),
  KEY `sys_bank_accounts_branch_idx` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_bank_accounts`
--

LOCK TABLES `sys_bank_accounts` WRITE;
/*!40000 ALTER TABLE `sys_bank_accounts` DISABLE KEYS */;
INSERT INTO `sys_bank_accounts` VALUES (1,'Caleb Medhurst','2176032049','62-77-47','Ledner Inc','CTKHYP08',1,1,'Lake Edaland','KWVY095192','business','general',16161.90,'savings',NULL,'2026-09-10 11:54:29','2026-09-10 11:54:29',NULL,NULL,NULL),(2,'Stella Hagenes','8750757989','53-70-35','Hyatt, Schumm and Wintheiser','HAWPCE65',1,1,'Rodrickville','CCYQ009512','business','general',14942.01,'overdraft',NULL,'2026-09-10 11:54:29','2026-09-10 11:54:29',NULL,NULL,NULL),(3,'Julien Osinski','6343507601','22-28-79','Stracke Inc','JXIXOI57',1,1,'Reinaborough','DSUN012661','business','general',2603.70,'savings',NULL,'2026-09-10 11:54:29','2026-09-10 11:54:29',NULL,NULL,NULL),(4,'Keely Kassulke III','4523936303','61-49-62','Dickens PLC','ZQPFHA87',1,1,'Amiestad','VJQU020228','business','general',19820.41,'savings',NULL,'2026-09-10 11:54:29','2026-09-10 11:54:29',NULL,NULL,NULL);
/*!40000 ALTER TABLE `sys_bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_expense_categories`
--

DROP TABLE IF EXISTS `sys_expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_expense_categories`
--

LOCK TABLES `sys_expense_categories` WRITE;
/*!40000 ALTER TABLE `sys_expense_categories` DISABLE KEYS */;
INSERT INTO `sys_expense_categories` VALUES (1,'Maintenance RP48',1,NULL,'2026-09-10 11:54:29'),(2,'Repairs DE18',1,'Doloremque et et nemo est deserunt.','2026-09-10 11:54:29'),(3,'Repairs HZ54',0,NULL,'2026-09-10 11:54:29'),(4,'Admin EF20',1,NULL,'2026-09-10 11:54:29'),(5,'Maintenance FY79',1,'Eligendi quidem corrupti ratione alias harum temporibus.','2026-09-10 11:54:29'),(6,'Utilities SG42',1,'Ullam aut eius dolore.','2026-09-10 11:54:29');
/*!40000 ALTER TABLE `sys_expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_income_categories`
--

DROP TABLE IF EXISTS `sys_income_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_income_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_income_categories`
--

LOCK TABLES `sys_income_categories` WRITE;
/*!40000 ALTER TABLE `sys_income_categories` DISABLE KEYS */;
INSERT INTO `sys_income_categories` VALUES (1,'Rent EN73',1,NULL,'2026-09-10 11:54:29'),(2,'Rent ES83',1,'Voluptas et voluptatum perferendis voluptatem atque.','2026-09-10 11:54:29'),(3,'Other Income RP75',1,'Reprehenderit ea repudiandae enim.','2026-09-10 11:54:29'),(4,'Service Charges NW22',1,'Et unde rem fuga facilis enim qui expedita.','2026-09-10 11:54:29'),(5,'Other Income UM17',1,NULL,'2026-09-10 11:54:29'),(6,'Other Income EJ25',1,NULL,'2026-09-10 11:54:29');
/*!40000 ALTER TABLE `sys_income_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_invoice_headers`
--

DROP TABLE IF EXISTS `sys_invoice_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_invoice_headers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `header_name` varchar(191) NOT NULL,
  `header_description` text DEFAULT NULL,
  `unique_reference_number` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_invoice_headers_unique_reference_number_unique` (`unique_reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_invoice_headers`
--

LOCK TABLES `sys_invoice_headers` WRITE;
/*!40000 ALTER TABLE `sys_invoice_headers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_invoice_headers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_payments`
--

DROP TABLE IF EXISTS `sys_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sys_bank_account_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `payment_type` enum('income','expense','general') NOT NULL,
  `reference_type` enum('sale_invoice','purchase_invoice') DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `payment_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_meta`)),
  `gl_journal_id` bigint(20) unsigned DEFAULT NULL,
  `is_voided` tinyint(1) NOT NULL DEFAULT 0,
  `voided_at` timestamp NULL DEFAULT NULL,
  `source_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_payments_user_id_foreign` (`user_id`),
  KEY `sys_payments_source_receipt_id_foreign` (`source_receipt_id`),
  KEY `sys_payments_account_idx` (`account_id`),
  CONSTRAINT `sys_payments_source_receipt_id_foreign` FOREIGN KEY (`source_receipt_id`) REFERENCES `sys_receipts` (`id`),
  CONSTRAINT `sys_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_payments`
--

LOCK TABLES `sys_payments` WRITE;
/*!40000 ALTER TABLE `sys_payments` DISABLE KEYS */;
INSERT INTO `sys_payments` VALUES (1,1,4,2,'income','purchase_invoice',NULL,'2008-02-16',2765.05,'Officia nihil officia vero illum aut.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(2,1,4,1,'income','sale_invoice',3082,'2014-04-08',648.31,'Ut commodi aut ea earum quo tempora voluptates.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(3,1,4,1,'expense','sale_invoice',8043,'1998-01-25',2606.90,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(4,1,3,1,'general','sale_invoice',8142,'1973-06-20',2776.67,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(5,1,3,3,'income','sale_invoice',3523,'1992-01-09',1071.63,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(6,1,2,2,'general',NULL,NULL,'1987-05-21',942.78,'Et saepe et est et voluptas.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(7,1,3,3,'income','sale_invoice',4771,'2009-09-24',1519.98,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(8,1,4,2,'expense','sale_invoice',4883,'2007-11-04',2156.33,'Occaecati qui quod omnis magnam exercitationem quis harum.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(9,1,4,2,'income','purchase_invoice',NULL,'2022-01-15',779.72,'Itaque nobis est amet vitae.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(10,1,4,3,'general','purchase_invoice',9596,'1996-05-22',4716.36,'Nemo nulla assumenda et ad magnam possimus quod magni.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(11,1,2,3,'general',NULL,NULL,'2009-09-17',884.91,'Nulla molestias quisquam fuga fugiat.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(12,1,1,1,'general','purchase_invoice',7308,'2009-07-03',3837.14,'Aut omnis quia harum et nesciunt.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(13,1,1,3,'general','purchase_invoice',2668,'1982-10-23',2908.02,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(14,1,3,2,'expense','purchase_invoice',6172,'2017-07-24',1426.73,'In qui quis corporis debitis.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(15,1,3,1,'expense','purchase_invoice',5304,'2012-07-11',152.14,'Ullam velit perferendis molestiae ipsam.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(16,1,4,2,'income','sale_invoice',6920,'1996-01-20',4442.78,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(17,1,3,3,'expense','sale_invoice',NULL,'1981-10-08',4204.46,'Voluptatem facere voluptates reiciendis quis.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(18,1,1,3,'expense','purchase_invoice',NULL,'2007-12-27',4245.86,'Eum facilis saepe atque voluptatem.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(19,1,1,3,'expense',NULL,5359,'2020-07-09',3024.04,'Consequatur velit qui et.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(20,1,2,3,'income','sale_invoice',NULL,'1983-06-18',2564.08,'Iusto quasi quia inventore est officia.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(21,1,1,2,'general','purchase_invoice',NULL,'2021-03-06',3466.63,'Alias tenetur rerum commodi perspiciatis asperiores nulla.',NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(22,1,3,2,'income','purchase_invoice',1361,'1970-10-17',228.31,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(23,1,1,2,'expense',NULL,5371,'1987-07-28',919.56,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(24,1,1,1,'general','sale_invoice',9693,'1997-06-09',2264.32,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL),(25,1,3,3,'expense','purchase_invoice',NULL,'2019-02-18',2167.64,NULL,NULL,NULL,0,NULL,NULL,'2026-09-10 11:54:29',NULL);
/*!40000 ALTER TABLE `sys_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_purchase_invoice_items`
--

DROP TABLE IF EXISTS `sys_purchase_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_purchase_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_purchase_invoice_items_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  KEY `sys_purchase_invoice_items_tax_id_foreign` (`tax_id`),
  KEY `sys_purchase_items_account_idx` (`account_id`),
  CONSTRAINT `sys_purchase_invoice_items_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `sys_purchase_invoices` (`id`),
  CONSTRAINT `sys_purchase_invoice_items_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `sys_taxes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_purchase_invoice_items`
--

LOCK TABLES `sys_purchase_invoice_items` WRITE;
/*!40000 ALTER TABLE `sys_purchase_invoice_items` DISABLE KEYS */;
INSERT INTO `sys_purchase_invoice_items` VALUES (1,1,'ipsam in iste','Eveniet itaque similique omnis incidunt repudiandae.',14.30,476.46,63.80,39,1.20,NULL,3236.49,NULL,'2026-09-10 11:54:29',NULL),(2,1,'tenetur accusantium accusantium','Qui odit ab ab quas.',19.90,192.39,79.26,40,20.00,91.28,1843.44,NULL,'2026-09-10 11:54:29',NULL),(3,1,'ut qui provident',NULL,2.63,458.88,43.93,41,21.73,98.44,1558.98,'Est alias sint totam voluptas dolores.','2026-09-10 11:54:29',NULL),(4,2,'repellendus porro reprehenderit','Et rerum sapiente autem provident tempore enim.',16.51,251.08,30.29,NULL,9.01,18.55,3966.15,'Dolorem sed eum minus iusto voluptate voluptatum in dolor.','2026-09-10 11:54:29',NULL),(5,2,'aut aut aliquam',NULL,7.66,491.20,37.60,42,5.94,325.73,4463.68,NULL,'2026-09-10 11:54:29',NULL),(6,2,'sit rerum et',NULL,6.68,163.38,97.97,43,0.23,160.54,2237.10,'Qui corporis iusto ut reiciendis quis rem.','2026-09-10 11:54:29',NULL),(7,3,'cum quam at',NULL,19.67,697.04,38.24,NULL,15.38,92.60,2488.94,'Placeat quaerat praesentium accusantium et nobis porro ipsum quis.','2026-09-10 11:54:29',NULL),(8,3,'eaque nam rerum',NULL,10.84,493.72,77.70,44,13.00,121.90,1719.52,'Voluptate reiciendis quia officia corporis officia.','2026-09-10 11:54:29',NULL),(9,4,'nam repudiandae vel',NULL,7.92,542.60,80.59,45,3.67,310.49,1173.69,'Et esse accusamus labore ex.','2026-09-10 11:54:29',NULL),(10,4,'eligendi neque quibusdam','Fugiat alias voluptas sed illo.',9.01,95.58,41.13,46,6.06,469.80,4575.23,'Amet et et expedita omnis.','2026-09-10 11:54:29',NULL),(11,5,'blanditiis natus ut','Modi culpa et non consequatur molestiae inventore animi.',6.47,119.88,93.42,47,9.00,82.35,959.80,'Inventore laboriosam consectetur iste dolor qui voluptatum magni delectus.','2026-09-10 11:54:29',NULL),(12,6,'id libero explicabo',NULL,6.27,292.22,46.66,48,17.08,283.40,398.39,'Sed unde dolor ad officia odit.','2026-09-10 11:54:29',NULL),(13,6,'voluptatem quas corporis',NULL,6.03,211.82,43.65,49,21.62,383.70,442.76,'A aliquid est id excepturi qui maiores.','2026-09-10 11:54:29',NULL),(14,6,'et voluptates et','Aliquid occaecati dolor dolorum beatae quia.',18.52,119.89,74.42,NULL,0.36,270.26,3734.74,'Rerum ut aliquam id qui consequatur.','2026-09-10 11:54:29',NULL),(15,7,'voluptatibus quo eveniet','Eaque rem sint ipsam explicabo et aut.',16.79,90.17,75.96,NULL,0.83,265.07,3479.22,'Eveniet voluptas eos praesentium et mollitia.','2026-09-10 11:54:29',NULL),(16,8,'et facilis accusantium','Consequatur voluptates officiis iure totam.',11.76,722.89,19.44,50,NULL,NULL,3219.73,NULL,'2026-09-10 11:54:29',NULL),(17,8,'quae sit temporibus',NULL,9.14,677.43,35.52,51,24.79,495.95,4081.60,NULL,'2026-09-10 11:54:29',NULL),(18,9,'magni fugit et','Autem ducimus id officia eligendi in quia architecto.',9.95,240.02,51.14,52,12.11,117.83,111.26,NULL,'2026-09-10 11:54:29',NULL),(19,9,'consequatur blanditiis beatae','Dolor omnis odio repudiandae voluptate saepe.',4.61,486.94,46.53,53,24.00,NULL,2003.50,NULL,'2026-09-10 11:54:29',NULL),(20,9,'porro perspiciatis esse','Cum alias accusantium et neque voluptas.',16.42,560.28,6.82,54,23.70,37.58,1495.71,'Et velit beatae consequuntur nobis fugiat.','2026-09-10 11:54:29',NULL),(21,9,'ipsum deserunt dolorum','Ipsa molestiae eligendi dicta ipsam.',2.70,117.52,48.08,55,11.41,140.05,2824.29,NULL,'2026-09-10 11:54:29',NULL),(22,10,'tempore est animi','Deleniti perferendis dignissimos aut ipsum quisquam.',7.88,456.02,79.51,56,16.29,326.19,1386.99,NULL,'2026-09-10 11:54:29',NULL),(23,10,'est nemo sapiente','Quisquam aut vel et ea.',9.92,489.75,74.00,57,12.74,NULL,4521.84,NULL,'2026-09-10 11:54:29',NULL),(24,10,'voluptas dolorem similique',NULL,7.59,76.04,71.63,58,17.00,442.10,69.77,'A molestias sunt voluptate ipsum.','2026-09-10 11:54:29',NULL),(25,11,'ut vitae dolore',NULL,13.25,452.60,87.65,59,6.46,78.91,1241.24,NULL,'2026-09-10 11:54:29',NULL),(26,11,'enim id sed','Exercitationem nobis cupiditate similique qui fugit corporis tempora.',8.19,468.02,1.13,60,11.43,341.80,4984.79,'Repudiandae accusantium et nulla saepe sint enim natus.','2026-09-10 11:54:29',NULL),(27,11,'velit veritatis deserunt',NULL,13.01,70.01,76.94,61,4.51,127.19,138.24,'Sit repellendus recusandae eveniet numquam.','2026-09-10 11:54:29',NULL),(28,12,'sapiente dolore qui',NULL,4.97,392.07,54.41,NULL,20.68,3.41,1839.63,'Iusto eum voluptatum debitis et aut temporibus.','2026-09-10 11:54:29',NULL),(29,12,'neque earum tempore','Incidunt officia ducimus rerum vitae vero.',13.50,699.23,44.92,62,21.59,258.39,3099.89,NULL,'2026-09-10 11:54:29',NULL),(30,12,'eaque inventore placeat','Aspernatur ducimus quo dolorem ipsa.',9.17,639.53,85.04,63,NULL,12.13,918.75,'Velit voluptatem sint sapiente voluptatem sint.','2026-09-10 11:54:29',NULL),(31,13,'adipisci et quisquam','Amet corporis ipsum qui minus placeat.',4.04,753.36,37.69,64,8.07,388.07,1448.38,NULL,'2026-09-10 11:54:29',NULL),(32,13,'ut voluptatibus quidem','Blanditiis voluptatem dolores neque ab debitis.',2.73,463.59,5.15,65,21.25,408.71,1139.22,NULL,'2026-09-10 11:54:29',NULL),(33,14,'dolorem quo vel','Quaerat repudiandae aut voluptatem hic cumque.',11.05,776.80,68.90,66,11.64,289.23,3615.38,'Eum est natus quas nihil.','2026-09-10 11:54:29',NULL),(34,14,'illum veritatis ipsa',NULL,3.35,603.46,21.42,NULL,0.01,300.99,3769.93,NULL,'2026-09-10 11:54:29',NULL),(35,14,'id explicabo rem','Voluptatibus dolor pariatur voluptas.',5.71,5.82,89.00,67,12.80,32.53,507.34,NULL,'2026-09-10 11:54:29',NULL),(36,15,'dolores rem corporis',NULL,17.30,9.09,56.11,68,18.79,NULL,3575.37,'Dolorem cumque consequatur magni expedita porro.','2026-09-10 11:54:29',NULL),(37,15,'quo corrupti velit','Quos consequatur voluptatem sed sit et reiciendis.',4.56,496.68,30.44,NULL,14.05,255.30,3833.59,'Qui ad quia voluptatem ut.','2026-09-10 11:54:29',NULL),(38,15,'quo beatae ut',NULL,17.97,280.43,73.11,NULL,9.03,153.74,126.25,NULL,'2026-09-10 11:54:29',NULL),(39,16,'iure ut et',NULL,4.39,12.10,95.32,NULL,10.06,35.74,4850.34,'Voluptas et fugiat laudantium totam hic.','2026-09-10 11:54:29',NULL),(40,17,'cumque itaque quam',NULL,11.33,120.47,44.60,69,6.25,21.20,2243.66,'Tempore ducimus optio tenetur fugiat nisi modi ipsum non.','2026-09-10 11:54:29',NULL),(41,17,'quo atque omnis',NULL,12.38,77.95,91.68,70,3.94,NULL,3988.34,NULL,'2026-09-10 11:54:29',NULL),(42,18,'magni consequatur beatae',NULL,10.83,313.89,46.04,71,1.33,NULL,2738.44,'Voluptatem iste temporibus ea dolores.','2026-09-10 11:54:29',NULL),(43,18,'quis eum et',NULL,2.94,486.89,66.60,72,21.90,64.63,707.55,'Voluptatem alias labore vel et fuga voluptatem.','2026-09-10 11:54:29',NULL),(44,18,'illo delectus et','Sint sed eum recusandae commodi.',14.73,497.52,75.82,NULL,4.36,207.37,2544.75,'Ducimus earum iure fugiat omnis dolore.','2026-09-10 11:54:29',NULL),(45,19,'aliquam quae est',NULL,18.43,540.06,60.31,73,21.66,30.45,1740.38,NULL,'2026-09-10 11:54:29',NULL),(46,20,'quod earum a',NULL,12.88,358.34,91.67,NULL,21.84,70.29,2815.48,'Esse enim aut quasi voluptatem voluptate.','2026-09-10 11:54:29',NULL),(47,20,'et excepturi aut','Tenetur modi ipsam laudantium nostrum.',2.92,221.89,52.47,74,4.30,237.86,499.22,NULL,'2026-09-10 11:54:29',NULL),(48,20,'qui atque facilis',NULL,11.92,721.09,14.36,NULL,14.73,68.41,4867.94,NULL,'2026-09-10 11:54:29',NULL),(49,20,'itaque id excepturi',NULL,14.12,709.32,81.65,75,0.25,NULL,2859.33,NULL,'2026-09-10 11:54:29',NULL);
/*!40000 ALTER TABLE `sys_purchase_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_purchase_invoices`
--

DROP TABLE IF EXISTS `sys_purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_purchase_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `balance_amount` decimal(15,2) NOT NULL,
  `status` enum('draft','received','paid','partial','cancelled') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_purchase_invoices_invoice_no_unique` (`invoice_no`),
  KEY `sys_purchase_invoices_user_id_foreign` (`user_id`),
  KEY `sys_purchase_inv_account_idx` (`account_id`),
  KEY `sys_purchase_inv_company_idx` (`company_id`),
  KEY `sys_purchase_inv_branch_idx` (`branch_id`),
  CONSTRAINT `sys_purchase_invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_purchase_invoices`
--

LOCK TABLES `sys_purchase_invoices` WRITE;
/*!40000 ALTER TABLE `sys_purchase_invoices` DISABLE KEYS */;
INSERT INTO `sys_purchase_invoices` VALUES (1,2,'PI-373979','2026-07-07','2026-07-18',4310.56,236.79,'partial',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(2,3,'PI-264114','2026-08-15','2026-09-18',463.98,1882.80,'draft',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(3,1,'PI-783524','2026-09-07','2026-09-22',4628.07,1723.39,'cancelled',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(4,4,'PI-482912','2026-07-14','2026-09-02',389.70,1332.00,'partial','Eos illum non dolore reiciendis quis voluptas necessitatibus.','2026-09-10 11:54:29',NULL,NULL,NULL),(5,1,'PI-500494','2026-06-26','2026-06-30',430.05,1799.46,'paid',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(6,1,'PI-960181','2026-07-21','2026-08-04',4171.23,346.28,'paid',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(7,3,'PI-488818','2026-08-18',NULL,3781.21,1660.82,'partial',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(8,4,'PI-034384','2026-09-07','2026-10-08',2770.04,988.38,'paid',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(9,3,'PI-950332','2026-08-25','2026-10-06',490.51,1909.29,'cancelled',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(10,4,'PI-435438','2026-08-17','2026-09-24',579.75,998.70,'draft','Debitis voluptates asperiores et necessitatibus pariatur suscipit.','2026-09-10 11:54:29',NULL,NULL,NULL),(11,2,'PI-497619','2026-08-22','2026-09-04',2078.26,1769.57,'draft',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(12,4,'PI-260995','2026-09-05','2026-09-13',4058.71,1014.09,'paid','Voluptatem necessitatibus consequatur ipsum ipsa voluptatem blanditiis dolor.','2026-09-10 11:54:29',NULL,NULL,NULL),(13,4,'PI-730667','2026-09-09','2026-09-27',3747.83,1339.63,'received','Recusandae corporis ut hic nam dolor.','2026-09-10 11:54:29',NULL,NULL,NULL),(14,3,'PI-411757','2026-08-04','2026-08-12',1947.15,1436.15,'draft',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(15,1,'PI-828329','2026-06-17','2026-09-05',177.21,235.21,'partial','A est eligendi voluptas laboriosam recusandae corrupti quos.','2026-09-10 11:54:29',NULL,NULL,NULL),(16,4,'PI-029780','2026-07-30',NULL,490.14,89.34,'partial',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(17,4,'PI-753498','2026-06-22',NULL,4282.95,789.78,'partial','Eaque veniam accusamus repudiandae porro et architecto vero.','2026-09-10 11:54:29',NULL,NULL,NULL),(18,4,'PI-202605','2026-08-14','2026-09-02',384.41,1845.39,'cancelled',NULL,'2026-09-10 11:54:29',NULL,NULL,NULL),(19,2,'PI-314285','2026-08-02','2026-09-10',1713.64,1680.51,'paid','Nostrum asperiores id sed error.','2026-09-10 11:54:29',NULL,NULL,NULL),(20,3,'PI-599814','2026-06-28','2026-07-08',1206.61,75.29,'received','Architecto aut est eaque dolorum nemo.','2026-09-10 11:54:29',NULL,NULL,NULL);
/*!40000 ALTER TABLE `sys_purchase_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_receipts`
--

DROP TABLE IF EXISTS `sys_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `receiptable_type` varchar(100) NOT NULL,
  `receiptable_id` bigint(20) unsigned NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `receipt_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sys_bank_account_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_meta`)),
  `gl_journal_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('unapplied','partially_applied','applied') NOT NULL DEFAULT 'unapplied',
  `applied_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_receipts_receipt_no_unique` (`receipt_no`),
  KEY `sys_receipts_user_id_foreign` (`user_id`),
  KEY `sys_receipts_payment_method_id_foreign` (`payment_method_id`),
  KEY `sys_receipts_receiptable_type_receiptable_id_index` (`receiptable_type`,`receiptable_id`),
  KEY `sys_receipts_sys_bank_account_id_foreign` (`sys_bank_account_id`),
  KEY `sys_receipts_account_idx` (`account_id`),
  CONSTRAINT `sys_receipts_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  CONSTRAINT `sys_receipts_sys_bank_account_id_foreign` FOREIGN KEY (`sys_bank_account_id`) REFERENCES `sys_bank_accounts` (`id`),
  CONSTRAINT `sys_receipts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_receipts`
--

LOCK TABLES `sys_receipts` WRITE;
/*!40000 ALTER TABLE `sys_receipts` DISABLE KEYS */;
INSERT INTO `sys_receipts` VALUES (1,1,4,'App\\Models\\SysSaleInvoice',1,'RC-106540','1985-09-25',4907.93,2,1,'REF-6810','Qui animi voluptas distinctio illo.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(2,1,1,'App\\Models\\SysSaleInvoice',2,'RC-393885','2005-05-04',4688.40,2,1,'REF-2784','Quis consequuntur nesciunt aliquid adipisci et rerum eaque in.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(3,1,3,'App\\Models\\SysSaleInvoice',3,'RC-201455','2017-12-25',626.25,2,3,'REF-2732',NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(4,1,2,'App\\Models\\SysSaleInvoice',4,'RC-744210','1972-08-18',2524.15,3,3,'REF-7038','Odit corporis provident architecto facere similique.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(5,1,1,'App\\Models\\SysSaleInvoice',5,'RC-426175','1982-06-17',210.29,3,3,'REF-0614','Molestiae repellendus corrupti voluptatum facere enim cupiditate.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(6,1,1,'App\\Models\\SysSaleInvoice',6,'RC-995365','2019-05-21',750.63,3,3,'REF-8049','Et dolor qui quo alias soluta molestiae et accusamus.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(7,1,3,'App\\Models\\SysSaleInvoice',7,'RC-039038','2024-08-23',1565.70,2,3,'REF-6531',NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(8,1,3,'App\\Models\\SysSaleInvoice',8,'RC-432122','1990-04-22',3093.12,3,2,NULL,'Accusantium hic repudiandae assumenda inventore.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(9,1,4,'App\\Models\\SysSaleInvoice',9,'RC-970814','2014-11-22',496.43,2,2,'REF-4312','Omnis deserunt ipsum hic iure.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(10,1,2,'App\\Models\\SysSaleInvoice',10,'RC-222865','1981-08-21',4809.39,4,1,'REF-7179',NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(11,1,2,'App\\Models\\SysPurchaseInvoice',1,'RC-012617','1977-03-03',588.83,2,2,'REF-0016','Rerum dolorem vitae corporis minus.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(12,1,3,'App\\Models\\SysPurchaseInvoice',2,'RC-867798','1991-01-28',4368.81,3,1,'REF-8754',NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(13,1,1,'App\\Models\\SysPurchaseInvoice',3,'RC-498869','2025-02-06',3371.94,4,3,'REF-0360','Nobis ut ratione repellendus quasi laboriosam.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(14,1,4,'App\\Models\\SysPurchaseInvoice',4,'RC-213387','2008-09-06',2260.22,3,3,'REF-1302',NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(15,1,1,'App\\Models\\SysPurchaseInvoice',5,'RC-658452','1996-02-07',2090.54,2,3,'REF-7614','Debitis magnam molestias harum autem quisquam.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(16,1,1,'App\\Models\\SysPurchaseInvoice',6,'RC-846375','2017-10-15',3818.20,2,2,'REF-8072','Iusto ea enim amet distinctio sed asperiores inventore.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:29',NULL),(17,1,3,'App\\Models\\SysPurchaseInvoice',7,'RC-726170','2014-05-19',55.71,2,2,NULL,'Aliquam fuga eveniet voluptas fugiat numquam.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:30',NULL),(18,1,4,'App\\Models\\SysPurchaseInvoice',8,'RC-686172','1996-09-06',1792.66,3,3,'REF-1028',NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:30',NULL),(19,1,3,'App\\Models\\SysPurchaseInvoice',9,'RC-160737','1975-01-15',3247.79,4,2,'REF-9275','Quae ullam vel quis velit.',NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:30',NULL),(20,1,4,'App\\Models\\SysPurchaseInvoice',10,'RC-772278','2000-03-27',3299.97,2,3,NULL,NULL,NULL,NULL,'unapplied',0.00,'2026-09-10 11:54:30',NULL);
/*!40000 ALTER TABLE `sys_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_refunds`
--

DROP TABLE IF EXISTS `sys_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_note_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `sys_bank_account_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `refund_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sys_refunds_adjustment_note_id_foreign` (`adjustment_note_id`),
  KEY `sys_refunds_user_id_foreign` (`user_id`),
  CONSTRAINT `sys_refunds_adjustment_note_id_foreign` FOREIGN KEY (`adjustment_note_id`) REFERENCES `sys_adjustment_notes` (`id`),
  CONSTRAINT `sys_refunds_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_refunds`
--

LOCK TABLES `sys_refunds` WRITE;
/*!40000 ALTER TABLE `sys_refunds` DISABLE KEYS */;
INSERT INTO `sys_refunds` VALUES (1,1,1,4,1,'2026-03-21',650.70,NULL,NULL,'2026-09-10 11:54:29'),(2,2,2,1,2,'1989-06-13',1049.24,'RF-3785','Ratione quibusdam praesentium aliquid consequatur dignissimos.','2026-09-10 11:54:29'),(3,3,1,4,2,'2003-03-02',167.53,'RF-6331',NULL,'2026-09-10 11:54:29'),(4,4,2,3,1,'1978-09-11',673.49,'RF-0214',NULL,'2026-09-10 11:54:29'),(5,5,4,1,2,'2015-10-05',1361.17,NULL,NULL,'2026-09-10 11:54:29'),(6,6,3,2,2,'1971-03-15',1481.11,'RF-7186',NULL,'2026-09-10 11:54:29'),(7,7,2,3,2,'1970-05-28',748.00,'RF-2075',NULL,'2026-09-10 11:54:29'),(8,8,2,4,1,'2003-10-29',2998.14,'RF-6050',NULL,'2026-09-10 11:54:29');
/*!40000 ALTER TABLE `sys_refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_sale_invoice_items`
--

DROP TABLE IF EXISTS `sys_sale_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_sale_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_invoice_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_sale_invoice_items_sale_invoice_id_foreign` (`sale_invoice_id`),
  KEY `sys_sale_invoice_items_tax_id_foreign` (`tax_id`),
  KEY `sys_sale_items_account_idx` (`account_id`),
  CONSTRAINT `sys_sale_invoice_items_sale_invoice_id_foreign` FOREIGN KEY (`sale_invoice_id`) REFERENCES `sys_sale_invoices` (`id`),
  CONSTRAINT `sys_sale_invoice_items_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `sys_taxes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_sale_invoice_items`
--

LOCK TABLES `sys_sale_invoice_items` WRITE;
/*!40000 ALTER TABLE `sys_sale_invoice_items` DISABLE KEYS */;
INSERT INTO `sys_sale_invoice_items` VALUES (1,1,'molestiae laborum non',NULL,2.08,625.48,98.58,9,12.17,288.79,2988.36,'At eum et recusandae velit omnis occaecati assumenda.','2026-09-10 11:54:29',NULL),(2,1,'eos ullam vel',NULL,5.56,346.73,72.42,NULL,18.97,38.31,3853.85,NULL,'2026-09-10 11:54:29',NULL),(3,1,'accusantium ipsam aliquam','Et ut nihil animi itaque facere harum.',4.24,739.81,67.96,10,1.82,173.42,4914.73,NULL,'2026-09-10 11:54:29',NULL),(4,2,'accusantium in itaque',NULL,14.95,403.86,93.48,11,NULL,NULL,1627.43,NULL,'2026-09-10 11:54:29',NULL),(5,2,'nihil labore consequatur',NULL,9.50,129.03,84.33,12,16.48,NULL,2256.76,'Et velit tempora quibusdam eligendi.','2026-09-10 11:54:29',NULL),(6,3,'officiis magnam qui','Perspiciatis culpa quisquam fugiat debitis.',12.74,472.84,19.60,NULL,9.62,211.82,788.33,'Aspernatur non in quis sint omnis temporibus.','2026-09-10 11:54:29',NULL),(7,4,'nobis nisi alias',NULL,15.14,177.92,5.41,13,2.64,436.01,1193.04,NULL,'2026-09-10 11:54:29',NULL),(8,5,'laboriosam sint dolor','Veniam quia eos deserunt rerum ex.',4.48,119.14,98.99,14,12.20,22.50,2408.51,'Beatae eos ipsum rem suscipit necessitatibus.','2026-09-10 11:54:29',NULL),(9,6,'inventore tenetur voluptates','Cumque blanditiis et natus dolorum assumenda sit animi.',4.10,357.32,59.05,15,10.31,213.37,4469.01,NULL,'2026-09-10 11:54:29',NULL),(10,6,'qui temporibus nobis','Rerum velit quo perferendis in iste.',3.48,199.65,95.31,16,13.46,10.58,4133.42,'Et facere esse laudantium eum ipsam praesentium.','2026-09-10 11:54:29',NULL),(11,6,'dolorum corrupti molestiae',NULL,10.88,347.09,71.93,17,24.30,41.92,657.74,'Porro sint et enim velit aut vero illum.','2026-09-10 11:54:29',NULL),(12,7,'eaque et itaque','Sint fugiat et ea.',16.56,590.54,49.67,18,8.28,69.30,851.14,'Et ab ipsam velit et.','2026-09-10 11:54:29',NULL),(13,8,'magni a saepe',NULL,19.88,585.67,64.33,NULL,10.82,NULL,3613.85,'Est quis sapiente quasi aliquam beatae.','2026-09-10 11:54:29',NULL),(14,9,'qui saepe impedit',NULL,16.78,705.16,43.86,NULL,23.36,144.88,929.17,'At placeat non ea repudiandae ut quis.','2026-09-10 11:54:29',NULL),(15,9,'labore enim voluptatem',NULL,3.43,298.03,70.15,19,20.47,157.16,3712.55,'Quia accusamus tempore ratione vel.','2026-09-10 11:54:29',NULL),(16,9,'quia expedita animi','Est voluptatibus dolor doloribus velit id.',13.21,456.00,39.12,20,NULL,120.20,3781.77,'Non soluta maxime autem perspiciatis ea ipsum soluta consequuntur.','2026-09-10 11:54:29',NULL),(17,9,'voluptatem deleniti odit','Est praesentium ut harum quia.',6.35,38.05,47.50,21,17.36,NULL,2133.11,'Odit ut cum ut nesciunt.','2026-09-10 11:54:29',NULL),(18,10,'et sit aut','Aperiam quibusdam voluptatem maxime quisquam numquam.',6.15,385.76,99.35,22,20.51,145.10,957.57,'Non expedita consequatur eos.','2026-09-10 11:54:29',NULL),(19,11,'illum veniam voluptas',NULL,8.94,659.37,84.03,23,18.38,294.50,902.51,'Fuga blanditiis nam facilis molestiae eveniet animi repellat.','2026-09-10 11:54:29',NULL),(20,11,'et vel distinctio','Expedita sed dolorem dolore minima illo dignissimos aut totam.',17.88,420.27,56.52,NULL,NULL,326.85,3061.92,'Assumenda illum sed consequatur consequatur quia vel.','2026-09-10 11:54:29',NULL),(21,12,'et rerum ipsa','Modi consequatur est ipsam nemo.',6.62,151.15,58.53,24,4.69,471.37,1493.54,NULL,'2026-09-10 11:54:29',NULL),(22,12,'rerum velit amet',NULL,15.22,529.50,8.14,25,NULL,101.47,325.89,NULL,'2026-09-10 11:54:29',NULL),(23,13,'dolor natus mollitia','Adipisci cumque facere hic voluptate ut nisi aut totam.',2.11,215.59,76.54,26,19.37,407.72,604.98,NULL,'2026-09-10 11:54:29',NULL),(24,14,'quisquam qui numquam',NULL,19.89,409.73,70.85,27,0.79,292.11,4565.33,NULL,'2026-09-10 11:54:29',NULL),(25,15,'quo ratione reprehenderit','Est nostrum inventore tempore.',3.63,187.40,11.70,28,NULL,NULL,4430.07,'At quidem sunt facere omnis ipsa qui.','2026-09-10 11:54:29',NULL),(26,16,'sequi dolor unde','Sit dolore neque molestias natus ipsam.',7.49,434.95,14.65,NULL,9.02,178.29,2384.91,NULL,'2026-09-10 11:54:29',NULL),(27,16,'excepturi maiores harum',NULL,14.00,450.58,79.71,29,24.36,NULL,3013.76,'Ratione excepturi soluta dignissimos omnis dolor beatae provident.','2026-09-10 11:54:29',NULL),(28,16,'quae deleniti expedita','Veniam exercitationem rerum ut et dolores repudiandae.',2.57,710.37,62.69,30,11.01,276.90,4893.82,'Praesentium ad voluptas dolorem unde.','2026-09-10 11:54:29',NULL),(29,16,'magni quisquam porro',NULL,7.75,762.18,56.11,31,NULL,NULL,3902.82,NULL,'2026-09-10 11:54:29',NULL),(30,17,'ducimus magnam exercitationem','Non id ex unde ut quasi sed.',13.72,700.57,8.38,32,10.66,139.81,4674.85,'Occaecati cumque tempore natus ducimus deserunt neque.','2026-09-10 11:54:29',NULL),(31,17,'fuga voluptatem nam','Cumque incidunt harum ad culpa dolorem.',2.52,767.16,65.55,NULL,17.49,463.66,1468.76,'Laudantium tempora aspernatur soluta.','2026-09-10 11:54:29',NULL),(32,18,'fugiat aliquam suscipit','Nesciunt aliquid voluptatem voluptas voluptas omnis.',19.09,791.44,7.73,33,NULL,101.81,587.93,NULL,'2026-09-10 11:54:29',NULL),(33,18,'numquam ut atque',NULL,15.19,355.70,24.42,34,21.99,124.21,4785.38,'Modi fuga error optio voluptas dolorum facere.','2026-09-10 11:54:29',NULL),(34,19,'dolores nobis et',NULL,16.24,379.40,50.51,NULL,18.56,493.46,674.63,'Voluptatem porro eum nihil nisi quidem.','2026-09-10 11:54:29',NULL),(35,19,'iusto ex doloribus','Fuga voluptatem repellendus dolor.',18.46,79.91,53.07,35,NULL,NULL,4154.70,NULL,'2026-09-10 11:54:29',NULL),(36,19,'blanditiis provident aliquam','Optio expedita quibusdam accusantium nostrum sed minus dolorem.',17.78,351.80,78.82,36,14.59,46.10,4921.71,NULL,'2026-09-10 11:54:29',NULL),(37,19,'qui beatae fugiat',NULL,12.61,303.91,24.03,37,23.98,114.33,4820.93,NULL,'2026-09-10 11:54:29',NULL),(38,20,'odio et ipsam',NULL,13.47,476.67,62.91,38,18.20,413.77,329.11,NULL,'2026-09-10 11:54:29',NULL);
/*!40000 ALTER TABLE `sys_sale_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_sale_invoices`
--

DROP TABLE IF EXISTS `sys_sale_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_sale_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `invoice_header_id` bigint(20) unsigned DEFAULT NULL,
  `link_to_type` enum('Property','Tenancy','Contractor') DEFAULT NULL,
  `link_to_id` bigint(20) unsigned DEFAULT NULL,
  `charge_to_type` enum('Owner','Tenant','Contractor') DEFAULT NULL,
  `charge_to_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `reminder_days_before_due` smallint(5) unsigned DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `balance_amount` decimal(15,2) NOT NULL,
  `status` enum('draft','issued','paid','partial','cancelled') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `penalty_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `penalty_type` enum('percentage','flat_rate') DEFAULT NULL,
  `penalty_fixed_rate` decimal(15,2) DEFAULT NULL,
  `penalty_amount_input` decimal(15,2) DEFAULT NULL,
  `penalty_gl_account_id` bigint(20) unsigned DEFAULT NULL,
  `penalty_grace_days` int(11) NOT NULL DEFAULT 0,
  `penalty_max_amount` decimal(15,2) DEFAULT NULL,
  `penalty_applied_at` timestamp NULL DEFAULT NULL,
  `penalty_amount_applied` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recurring_master_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `recurring_sequence` int(10) unsigned DEFAULT NULL,
  `recurring_month_interval` tinyint(3) unsigned DEFAULT NULL,
  `recurring_custom_interval` int(10) unsigned DEFAULT NULL,
  `recurring_custom_unit` enum('day','week','month','year') DEFAULT NULL,
  `unlimited_cycles` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_cycles` int(10) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_sale_invoices_invoice_no_unique` (`invoice_no`),
  KEY `sys_sale_invoices_user_id_foreign` (`user_id`),
  KEY `sys_sale_invoices_invoice_header_id_foreign` (`invoice_header_id`),
  KEY `sys_sale_invoices_bank_account_id_foreign` (`bank_account_id`),
  KEY `sys_sale_invoices_link_to_idx` (`link_to_type`,`link_to_id`),
  KEY `sys_sale_invoices_charge_to_idx` (`charge_to_type`,`charge_to_id`),
  KEY `sys_sale_invoices_recurring_idx` (`recurring_master_invoice_id`,`recurring_sequence`),
  KEY `sys_sale_invoices_penalty_gl_account_id_foreign` (`penalty_gl_account_id`),
  KEY `sys_sale_invoices_penalty_idx` (`penalty_enabled`,`penalty_applied_at`),
  KEY `sys_sale_invoices_due_date_idx` (`due_date`),
  KEY `sys_sale_inv_account_idx` (`account_id`),
  KEY `sys_sale_inv_company_idx` (`company_id`),
  KEY `sys_sale_inv_branch_idx` (`branch_id`),
  CONSTRAINT `sys_sale_invoices_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sys_sale_invoices_invoice_header_id_foreign` FOREIGN KEY (`invoice_header_id`) REFERENCES `sys_invoice_headers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sys_sale_invoices_penalty_gl_account_id_foreign` FOREIGN KEY (`penalty_gl_account_id`) REFERENCES `gl_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sys_sale_invoices_recurring_master_invoice_id_foreign` FOREIGN KEY (`recurring_master_invoice_id`) REFERENCES `sys_sale_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sys_sale_invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_sale_invoices`
--

LOCK TABLES `sys_sale_invoices` WRITE;
/*!40000 ALTER TABLE `sys_sale_invoices` DISABLE KEYS */;
INSERT INTO `sys_sale_invoices` VALUES (1,4,NULL,NULL,NULL,NULL,NULL,NULL,'SI-045455','2026-08-23','2026-09-03',NULL,4258.75,1371.80,'draft','Quaerat molestiae maxime hic error quisquam enim.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(2,1,NULL,NULL,NULL,NULL,NULL,NULL,'SI-799367','2026-07-10',NULL,NULL,351.79,41.34,'issued',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(3,3,NULL,NULL,NULL,NULL,NULL,NULL,'SI-607459','2026-08-21',NULL,NULL,1401.73,1639.45,'partial','Nobis quae quas excepturi suscipit ullam.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(4,2,NULL,NULL,NULL,NULL,NULL,NULL,'SI-936645','2026-07-08','2026-07-13',22,648.13,1911.88,'partial','Quia saepe quo accusantium.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(5,1,NULL,NULL,NULL,NULL,NULL,NULL,'SI-710143','2026-06-22','2026-09-09',NULL,3778.21,1211.98,'cancelled',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(6,1,NULL,NULL,NULL,NULL,NULL,NULL,'SI-410271','2026-07-06','2026-07-31',NULL,4359.79,17.59,'paid',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(7,3,NULL,NULL,NULL,NULL,NULL,NULL,'SI-973471','2026-06-27','2026-07-15',NULL,3129.37,390.98,'cancelled','Distinctio sunt deserunt quo molestias dolores non vitae.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(8,3,NULL,NULL,NULL,NULL,NULL,NULL,'SI-629419','2026-08-16','2026-08-19',24,1033.68,866.94,'issued','At est iure rerum facilis nisi vitae.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(9,4,NULL,NULL,NULL,NULL,NULL,NULL,'SI-187236','2026-07-15','2026-10-03',3,1042.24,470.77,'cancelled',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(10,2,NULL,NULL,NULL,NULL,NULL,NULL,'SI-371267','2026-07-20','2026-07-29',NULL,4613.74,1517.34,'issued','Est quasi error et eveniet perspiciatis.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(11,4,NULL,NULL,NULL,NULL,NULL,NULL,'SI-985737','2026-08-27','2026-09-02',NULL,2815.04,506.72,'paid','Inventore tempore impedit voluptatibus distinctio sed quis enim explicabo.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(12,4,NULL,NULL,NULL,NULL,NULL,NULL,'SI-197150','2026-06-15','2026-09-12',NULL,3345.52,1583.13,'draft','Sunt est impedit facilis architecto delectus ut.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(13,2,NULL,NULL,NULL,NULL,NULL,NULL,'SI-838243','2026-07-07','2026-08-28',26,975.51,1625.27,'paid',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(14,4,NULL,NULL,NULL,NULL,NULL,NULL,'SI-511619','2026-07-08','2026-08-05',NULL,3642.27,1017.20,'issued','Itaque repudiandae vero maxime omnis.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(15,1,NULL,NULL,NULL,NULL,NULL,NULL,'SI-051564','2026-06-11','2026-09-30',NULL,68.86,1445.82,'draft','Tempore non doloribus beatae amet sed aliquid.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(16,2,NULL,NULL,NULL,NULL,NULL,NULL,'SI-995243','2026-06-11','2026-08-18',NULL,114.79,639.12,'draft','Nesciunt optio occaecati delectus delectus.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(17,1,NULL,NULL,NULL,NULL,NULL,NULL,'SI-016667','2026-08-15','2026-09-04',NULL,4023.30,541.54,'draft','Dolore saepe et quasi laudantium.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(18,3,NULL,NULL,NULL,NULL,NULL,NULL,'SI-771108','2026-07-13','2026-08-07',NULL,3489.25,1662.46,'issued',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(19,3,NULL,NULL,NULL,NULL,NULL,NULL,'SI-567445','2026-08-06','2026-09-22',NULL,4187.74,1935.10,'cancelled',NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL),(20,3,NULL,NULL,NULL,NULL,NULL,NULL,'SI-430697','2026-08-10','2026-09-21',NULL,3085.16,1459.25,'draft','Voluptas sed et necessitatibus ipsum et.',0,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-09-10 11:54:29',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `sys_sale_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_taxes`
--

DROP TABLE IF EXISTS `sys_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `rate` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_taxes`
--

LOCK TABLES `sys_taxes` WRITE;
/*!40000 ALTER TABLE `sys_taxes` DISABLE KEYS */;
INSERT INTO `sys_taxes` VALUES (1,'VAT DB59',6.28,1,NULL,'2026-09-10 11:54:29'),(2,'Service Tax SD71',21.42,1,'Molestias possimus hic amet quasi ea sit tenetur voluptatem.','2026-09-10 11:54:29'),(3,'GST TV07',17.48,0,NULL,'2026-09-10 11:54:29'),(4,'VAT JO88',5.74,1,NULL,'2026-09-10 11:54:29'),(5,'Service Tax AB14',18.81,1,'Dolores veritatis provident nisi quod temporibus.','2026-09-10 11:54:29'),(6,'Sales Tax PW83',18.80,1,'Rerum quod qui eaque dolores quia et exercitationem.','2026-09-10 11:54:29'),(7,'VAT QI03',11.32,0,'Quia voluptates optio dicta nobis sed odio.','2026-09-10 11:54:29'),(8,'Service Tax RP87',11.43,1,'Excepturi ipsum id blanditiis eveniet nobis earum ratione.','2026-09-10 11:54:29'),(9,'Service Tax VH79',4.96,0,'Est vel ipsam ea nemo odio.','2026-09-10 11:54:29'),(10,'Sales Tax GU92',0.56,1,NULL,'2026-09-10 11:54:29'),(11,'Service Tax NU61',14.14,1,'Illum voluptas debitis molestiae repellat nisi dolorum.','2026-09-10 11:54:29'),(12,'VAT EZ79',11.49,1,'Est voluptas eligendi non et.','2026-09-10 11:54:29'),(13,'Service Tax GA20',15.32,0,NULL,'2026-09-10 11:54:29'),(14,'Service Tax JP01',17.22,1,NULL,'2026-09-10 11:54:29'),(15,'GST LG34',12.72,1,NULL,'2026-09-10 11:54:29'),(16,'GST RK30',10.13,1,'Quae consequatur culpa libero delectus sapiente deleniti ut debitis.','2026-09-10 11:54:29'),(17,'Sales Tax QJ42',9.44,1,NULL,'2026-09-10 11:54:29'),(18,'VAT TE67',14.40,1,NULL,'2026-09-10 11:54:29'),(19,'Sales Tax KV66',19.03,1,NULL,'2026-09-10 11:54:29'),(20,'VAT LB79',15.09,1,NULL,'2026-09-10 11:54:29'),(21,'Service Tax QT39',21.26,1,'Vel odio corrupti rerum neque.','2026-09-10 11:54:29'),(22,'Service Tax ID93',16.58,1,'Sunt consequatur molestias ut voluptatem possimus necessitatibus id.','2026-09-10 11:54:29'),(23,'GST DP97',12.79,1,NULL,'2026-09-10 11:54:29'),(24,'GST EF72',9.75,0,NULL,'2026-09-10 11:54:29'),(25,'Sales Tax KR64',1.00,1,'Qui incidunt excepturi quod voluptas incidunt.','2026-09-10 11:54:29'),(26,'GST YP57',3.99,0,NULL,'2026-09-10 11:54:29'),(27,'GST CH28',6.18,0,'Esse ipsa qui sit exercitationem dolor.','2026-09-10 11:54:29'),(28,'GST VI49',9.70,1,'Reprehenderit architecto qui sit rerum amet dolorem ut.','2026-09-10 11:54:29'),(29,'GST LD02',3.38,1,NULL,'2026-09-10 11:54:29'),(30,'GST LQ55',20.21,1,NULL,'2026-09-10 11:54:29'),(31,'VAT TT60',22.36,0,'Laboriosam repellendus corporis sit.','2026-09-10 11:54:29'),(32,'VAT MW43',1.71,1,NULL,'2026-09-10 11:54:29'),(33,'GST IN35',18.41,1,NULL,'2026-09-10 11:54:29'),(34,'VAT UM06',23.45,1,NULL,'2026-09-10 11:54:29'),(35,'GST WM67',19.76,1,'Vel consequatur vel laboriosam aliquid.','2026-09-10 11:54:29'),(36,'VAT YK13',20.72,1,NULL,'2026-09-10 11:54:29'),(37,'GST HQ08',16.25,1,'Ut ut inventore fugiat veniam in odio.','2026-09-10 11:54:29'),(38,'Sales Tax FU04',9.02,1,'Fugit necessitatibus amet deleniti.','2026-09-10 11:54:29'),(39,'VAT CB39',9.83,1,'Qui saepe alias consequatur reprehenderit et quae eos expedita.','2026-09-10 11:54:29'),(40,'Service Tax PM89',17.94,1,'Consectetur aut dolores sunt.','2026-09-10 11:54:29'),(41,'VAT EY14',24.52,1,NULL,'2026-09-10 11:54:29'),(42,'GST GA82',23.09,1,'Rem vel cupiditate nesciunt voluptas eaque sint.','2026-09-10 11:54:29'),(43,'Sales Tax PQ67',12.50,1,'Expedita sint id cumque cupiditate.','2026-09-10 11:54:29'),(44,'Sales Tax VV47',4.29,1,'Veniam voluptatem nulla ut quia tempora.','2026-09-10 11:54:29'),(45,'Service Tax WT22',19.54,1,'Voluptatem aut deleniti non dignissimos quasi aperiam vitae.','2026-09-10 11:54:29'),(46,'GST ZB41',15.13,1,NULL,'2026-09-10 11:54:29'),(47,'VAT NH78',18.99,0,NULL,'2026-09-10 11:54:29'),(48,'Sales Tax FW74',12.71,1,'Voluptates ex quia ea optio perferendis aut temporibus soluta.','2026-09-10 11:54:29'),(49,'GST IS27',6.05,1,NULL,'2026-09-10 11:54:29'),(50,'Service Tax LZ79',11.92,1,'Eos deserunt debitis voluptatem rem.','2026-09-10 11:54:29'),(51,'GST OG41',0.37,1,'Ab voluptatem aut quaerat quam vel ratione amet.','2026-09-10 11:54:29'),(52,'VAT MQ93',11.02,1,'Culpa accusamus sed et adipisci.','2026-09-10 11:54:29'),(53,'GST HT81',19.28,1,NULL,'2026-09-10 11:54:29'),(54,'Service Tax UG44',8.35,1,NULL,'2026-09-10 11:54:29'),(55,'VAT WD53',20.53,0,'Quos totam in et reiciendis.','2026-09-10 11:54:29'),(56,'Sales Tax AP17',21.68,0,'Ut at dolor cupiditate optio aut possimus.','2026-09-10 11:54:29'),(57,'Service Tax PY36',17.44,1,NULL,'2026-09-10 11:54:29'),(58,'VAT VE23',12.73,1,'Aliquam explicabo tempore corrupti sunt sit.','2026-09-10 11:54:29'),(59,'Sales Tax NX67',24.87,1,'Ratione veniam nisi enim explicabo dolorum iste.','2026-09-10 11:54:29'),(60,'Service Tax KP04',5.86,1,NULL,'2026-09-10 11:54:29'),(61,'VAT SB41',24.55,0,'Sit dicta autem vitae dolor reiciendis earum sit.','2026-09-10 11:54:29'),(62,'Sales Tax TZ95',2.72,1,'Debitis dignissimos perferendis exercitationem quidem.','2026-09-10 11:54:29'),(63,'Sales Tax RP08',3.78,1,NULL,'2026-09-10 11:54:29'),(64,'VAT KJ04',24.15,1,'Voluptatem quam natus eveniet.','2026-09-10 11:54:29'),(65,'GST CI26',1.87,1,NULL,'2026-09-10 11:54:29'),(66,'Sales Tax QM47',6.60,0,NULL,'2026-09-10 11:54:29'),(67,'Sales Tax XG88',10.16,1,NULL,'2026-09-10 11:54:29'),(68,'GST BN96',12.43,1,NULL,'2026-09-10 11:54:29'),(69,'Sales Tax DT14',13.97,1,'Nihil aliquam unde reiciendis reiciendis voluptatem sequi eius totam.','2026-09-10 11:54:29'),(70,'Sales Tax JU07',1.67,0,'Et debitis nobis error rerum.','2026-09-10 11:54:29'),(71,'VAT KW16',24.38,1,'Quis omnis earum quis sapiente.','2026-09-10 11:54:29'),(72,'VAT DQ82',14.52,1,'Error quod modi dolore quaerat et alias accusamus.','2026-09-10 11:54:29'),(73,'Sales Tax TR80',8.38,1,'Dolorum in doloremque aut suscipit.','2026-09-10 11:54:29'),(74,'Service Tax QF64',9.35,1,'Perferendis et culpa voluptatem optio voluptatibus.','2026-09-10 11:54:29'),(75,'VAT PV53',16.72,1,NULL,'2026-09-10 11:54:29');
/*!40000 ALTER TABLE `sys_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rates`
--

LOCK TABLES `tax_rates` WRITE;
/*!40000 ALTER TABLE `tax_rates` DISABLE KEYS */;
INSERT INTO `tax_rates` VALUES (1,'Standard VAT',20.00,NULL,NULL),(2,'Reduced VAT',5.00,NULL,NULL),(3,'Zero VAT',0.00,NULL,NULL);
/*!40000 ALTER TABLE `tax_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenancies`
--

DROP TABLE IF EXISTS `tenancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenancies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `offer_id` bigint(20) unsigned DEFAULT NULL,
  `tenancy_sub_status_id` bigint(20) unsigned DEFAULT NULL,
  `tenancy_type_id` bigint(20) unsigned DEFAULT NULL,
  `move_in` date DEFAULT NULL,
  `move_out` date DEFAULT NULL,
  `tenancy_renewal_confirm_date` date DEFAULT NULL,
  `extension_date` date DEFAULT NULL,
  `rent` decimal(10,2) DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  `deposit_type` varchar(191) DEFAULT NULL,
  `deposit_number` int(11) DEFAULT NULL,
  `deposit_held_by` varchar(191) DEFAULT NULL,
  `deposit_service` varchar(191) DEFAULT NULL,
  `tds_dps_number` varchar(191) DEFAULT NULL,
  `reference_number` varchar(191) DEFAULT NULL,
  `deposit_scheme` varchar(191) DEFAULT NULL,
  `periodic` tinyint(1) NOT NULL DEFAULT 0,
  `rolling_contract` tinyint(1) NOT NULL DEFAULT 0,
  `renewal_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `term_months` int(11) DEFAULT NULL,
  `term_days` int(11) DEFAULT NULL,
  `frequency` enum('Monthly','Weekly') DEFAULT NULL,
  `status` enum('Active','Inactive','Terminated','Archived') NOT NULL DEFAULT 'Active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `deposit_received_at` datetime DEFAULT NULL,
  `deposit_protected_at` datetime DEFAULT NULL,
  `prescribed_information_sent_at` datetime DEFAULT NULL,
  `written_terms_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenancies_property_id_foreign` (`property_id`),
  KEY `tenancies_offer_id_foreign` (`offer_id`),
  KEY `tenancies_account_id_idx` (`account_id`),
  CONSTRAINT `tenancies_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenancies_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenancies`
--

LOCK TABLES `tenancies` WRITE;
/*!40000 ALTER TABLE `tenancies` DISABLE KEYS */;
INSERT INTO `tenancies` VALUES (1,1,NULL,14,5,'2026-01-01','2026-12-31',NULL,NULL,1250.00,1442.31,'weeks_deposit',5,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,'Monthly','Active',10,NULL,NULL,'2026-09-10 11:54:40','2026-09-10 11:54:40',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL),(2,4,NULL,NULL,NULL,'2026-09-09',NULL,NULL,NULL,233.00,1232.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,12,NULL,'Monthly','Active',NULL,NULL,NULL,'2026-09-14 10:02:54','2026-09-14 10:02:54',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `tenancies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenancy_notices`
--

DROP TABLE IF EXISTS `tenancy_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenancy_notices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `tenancy_id` bigint(20) unsigned NOT NULL,
  `recipient_user_id` bigint(20) unsigned DEFAULT NULL,
  `notice_type` varchar(191) NOT NULL,
  `served_at` datetime NOT NULL,
  `effective_at` datetime DEFAULT NULL,
  `document_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'served',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenancy_notices_account_id_tenancy_id_index` (`account_id`,`tenancy_id`),
  KEY `tenancy_notices_effective_at_status_index` (`effective_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenancy_notices`
--

LOCK TABLES `tenancy_notices` WRITE;
/*!40000 ALTER TABLE `tenancy_notices` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenancy_notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenancy_sub_statuses`
--

DROP TABLE IF EXISTS `tenancy_sub_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenancy_sub_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenancy_sub_statuses_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenancy_sub_statuses`
--

LOCK TABLES `tenancy_sub_statuses` WRITE;
/*!40000 ALTER TABLE `tenancy_sub_statuses` DISABLE KEYS */;
INSERT INTO `tenancy_sub_statuses` VALUES (1,'Under Negotiation','2026-09-10 11:54:27','2026-09-10 11:54:27'),(2,'Offer Accepted','2026-09-10 11:54:27','2026-09-10 11:54:27'),(3,'Admin To Approve','2026-09-10 11:54:27','2026-09-10 11:54:27'),(4,'Accounts To Process','2026-09-10 11:54:27','2026-09-10 11:54:27'),(5,'Current Tenancy','2026-09-10 11:54:27','2026-09-10 11:54:27'),(6,'Current Tenancy (On Notice)','2026-09-10 11:54:27','2026-09-10 11:54:27'),(7,'Aborted','2026-09-10 11:54:27','2026-09-10 11:54:27'),(8,'Offer Rejected','2026-09-10 11:54:27','2026-09-10 11:54:27'),(9,'Offer Rejected – Refund Request','2026-09-10 11:54:27','2026-09-10 11:54:27'),(10,'Checked Out','2026-09-10 11:54:27','2026-09-10 11:54:27'),(11,'Checked Out – Deposit Dispute','2026-09-10 11:54:27','2026-09-10 11:54:27'),(12,'Checked Out – Deposit Settled','2026-09-10 11:54:27','2026-09-10 11:54:27'),(13,'Archive','2026-09-10 11:54:27','2026-09-10 11:54:27'),(14,'Active','2026-09-10 11:54:40','2026-09-10 11:54:40');
/*!40000 ALTER TABLE `tenancy_sub_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenancy_types`
--

DROP TABLE IF EXISTS `tenancy_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenancy_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenancy_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenancy_types`
--

LOCK TABLES `tenancy_types` WRITE;
/*!40000 ALTER TABLE `tenancy_types` DISABLE KEYS */;
INSERT INTO `tenancy_types` VALUES (1,'APT','2026-09-10 11:54:26','2026-09-10 11:54:26'),(2,'Common Law','2026-09-10 11:54:26','2026-09-10 11:54:26'),(3,'Company','2026-09-10 11:54:26','2026-09-10 11:54:26'),(4,'Short Let - AST','2026-09-10 11:54:26','2026-09-10 11:54:26'),(5,'Assured Shorthold Tenancy','2026-09-10 11:54:26','2026-09-10 11:54:26');
/*!40000 ALTER TABLE `tenancy_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_members`
--

DROP TABLE IF EXISTS `tenant_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenancy_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_main_person` tinyint(1) NOT NULL DEFAULT 0,
  `group_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `access_level` enum('view','edit') NOT NULL DEFAULT 'view',
  `can_login` tinyint(1) NOT NULL DEFAULT 0,
  `right_to_rent_required` tinyint(1) NOT NULL DEFAULT 0,
  `right_to_rent_checked_at` datetime DEFAULT NULL,
  `right_to_rent_follow_up_due_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_members_tenancy_id_foreign` (`tenancy_id`),
  KEY `tenant_members_user_id_foreign` (`user_id`),
  KEY `tenant_members_account_id_idx` (`account_id`),
  CONSTRAINT `tenant_members_tenancy_id_foreign` FOREIGN KEY (`tenancy_id`) REFERENCES `tenancies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_members`
--

LOCK TABLES `tenant_members` WRITE;
/*!40000 ALTER TABLE `tenant_members` DISABLE KEYS */;
INSERT INTO `tenant_members` VALUES (1,1,10,1,'STG-TENANCY-1','2026-09-10 11:54:40','2026-09-10 11:54:40',1,'view',1,0,NULL,NULL),(2,2,14,1,'GROUP_2','2026-09-14 10:02:54','2026-09-14 10:02:54',1,'view',1,0,NULL,NULL);
/*!40000 ALTER TABLE `tenant_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_categories`
--

DROP TABLE IF EXISTS `transaction_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaction_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) DEFAULT NULL,
  `is_income` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_categories`
--

LOCK TABLES `transaction_categories` WRITE;
/*!40000 ALTER TABLE `transaction_categories` DISABLE KEYS */;
INSERT INTO `transaction_categories` VALUES (1,'Salary','SALARY',1,1,1,NULL,NULL),(2,'Advance Payment','ADVANCE',1,1,1,NULL,NULL),(3,'Utility','UTILITY',0,1,1,NULL,NULL),(4,'Electricity Bill','ELECTRICITY',0,1,1,NULL,NULL),(5,'Travel','TRAVEL',0,1,1,NULL,NULL),(6,'Other','OTHER',0,1,1,NULL,NULL),(7,'Rent','RENT',1,1,1,NULL,NULL),(8,'Deposit','DEPOSIT',1,1,1,NULL,NULL),(9,'Gas Bill','GAS',0,1,1,NULL,NULL),(10,'EPC Certificate','EPC',0,1,1,NULL,NULL),(11,'Maintenance','MAINT',0,1,1,NULL,NULL);
/*!40000 ALTER TABLE `transaction_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_number` varchar(191) NOT NULL,
  `transaction_type` varchar(191) DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_category_id` bigint(20) unsigned DEFAULT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `payer_id` bigint(20) unsigned DEFAULT NULL,
  `payee_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `transaction_reference` varchar(191) DEFAULT NULL,
  `credit` decimal(10,2) DEFAULT NULL,
  `debit` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `status` varchar(191) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_transaction_number_unique` (`transaction_number`),
  KEY `transactions_invoice_id_foreign` (`invoice_id`),
  KEY `transactions_transaction_category_id_foreign` (`transaction_category_id`),
  KEY `transactions_property_id_foreign` (`property_id`),
  KEY `transactions_payer_id_foreign` (`payer_id`),
  KEY `transactions_payee_id_foreign` (`payee_id`),
  CONSTRAINT `transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_payee_id_foreign` FOREIGN KEY (`payee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_payer_id_foreign` FOREIGN KEY (`payer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_transaction_category_id_foreign` FOREIGN KEY (`transaction_category_id`) REFERENCES `transaction_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploads`
--

DROP TABLE IF EXISTS `uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_original_name` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `type` varchar(15) DEFAULT NULL,
  `external_link` varchar(500) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `visibility` enum('private','shared','portal') NOT NULL DEFAULT 'private',
  PRIMARY KEY (`id`),
  KEY `uploads_account_id_idx` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploads`
--

LOCK TABLES `uploads` WRITE;
/*!40000 ALTER TABLE `uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_details`
--

DROP TABLE IF EXISTS `user_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `employment_status` varchar(191) DEFAULT NULL,
  `business_name` varchar(191) DEFAULT NULL,
  `registered_address` varchar(191) DEFAULT NULL,
  `guarantee` tinyint(1) DEFAULT NULL,
  `previously_rented` tinyint(1) DEFAULT NULL,
  `poor_credit` tinyint(1) DEFAULT NULL,
  `correspondence_address` varchar(191) DEFAULT NULL,
  `occupation` varchar(191) DEFAULT NULL,
  `vat_number` varchar(191) DEFAULT NULL,
  `allow_email` tinyint(1) NOT NULL DEFAULT 0,
  `allow_post` tinyint(1) NOT NULL DEFAULT 0,
  `allow_text` tinyint(1) NOT NULL DEFAULT 0,
  `allow_call` tinyint(1) NOT NULL DEFAULT 0,
  `emails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emails`)),
  `primary_email` varchar(191) DEFAULT NULL,
  `phones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phones`)),
  `primary_phone` varchar(191) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `area` varchar(191) DEFAULT NULL,
  `tentative_move_in` date DEFAULT NULL,
  `no_of_beds` tinyint(3) unsigned DEFAULT NULL,
  `no_of_tenants` tinyint(3) unsigned DEFAULT NULL,
  `specialisations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialisations`)),
  `cover_areas` varchar(191) DEFAULT NULL,
  `pi_insurance` tinyint(1) NOT NULL DEFAULT 0,
  `pi_reference_number` varchar(191) DEFAULT NULL,
  `pi_certificate` varchar(191) DEFAULT NULL,
  `nationality_id` bigint(20) unsigned DEFAULT NULL,
  `visa_expiry` date DEFAULT NULL,
  `passport_no` varchar(191) DEFAULT NULL,
  `nrl_number` varchar(191) DEFAULT NULL,
  `right_to_rent_check` tinyint(1) NOT NULL DEFAULT 0,
  `checked_by_user` bigint(20) unsigned DEFAULT NULL,
  `checked_by_external` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_details_user_id_foreign` (`user_id`),
  KEY `user_details_checked_by_user_foreign` (`checked_by_user`),
  CONSTRAINT `user_details_checked_by_user_foreign` FOREIGN KEY (`checked_by_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_details`
--

LOCK TABLES `user_details` WRITE;
/*!40000 ALTER TABLE `user_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `selected_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_properties`)),
  `first_name` varchar(55) DEFAULT NULL,
  `middle_name` varchar(55) DEFAULT NULL,
  `last_name` varchar(55) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(191) DEFAULT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `postcode` varchar(15) DEFAULT NULL,
  `city` varchar(55) DEFAULT NULL,
  `country` varchar(55) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 for active, 0 for inactive',
  `can_login` tinyint(1) NOT NULL DEFAULT 0,
  `user_type` varchar(50) DEFAULT NULL,
  `quick_step` int(11) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `last_active_account_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_branch_id_foreign` (`branch_id`),
  KEY `users_designation_id_foreign` (`designation_id`),
  KEY `users_category_id_foreign` (`category_id`),
  KEY `users_last_active_account_idx` (`last_active_account_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `users_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Rai','rai@resisqaure.co.uk','2026-09-10 11:53:01','$2y$12$GE/q9Scrmk9/fSgGTe53meSRhAhKxDjwZ1YyYVReeWY1hEGcaixOG','ZGqllqpOaH','2026-09-10 11:53:01','2026-09-10 11:53:01',NULL,NULL,NULL,NULL,NULL,'Rai','','','1234567890',NULL,'123 Main Street','Apt 4B','12345','New York','',1,1,'landlord',NULL,NULL,NULL,NULL),(2,NULL,'Tanveer','tanveer@resisqaure.co.uk','2026-09-10 11:53:25','$2y$12$VQgXinKZQVyhaBmDt05GZuYJf3Nvfh6kpYwMduuMGAmAQ1YmhvDQq','M2zHLfew2G','2026-09-10 11:53:25','2026-09-10 11:53:25',NULL,NULL,NULL,NULL,NULL,'Tanveer','','','1234567890',NULL,'123 Main Street','Apt 4B','12345','New York','',1,1,'super_admin',NULL,NULL,NULL,NULL),(3,NULL,'Jatinder','Jatinder@resisqaure.co.uk','2026-09-10 11:53:25','$2y$12$EJl4DuVfJJzZm8IyhpNrnusoJaIMDoIngmR3nDoOsUg5J091fxnRu','K1SibP9qVm','2026-09-10 11:53:26','2026-09-10 11:53:26',NULL,NULL,NULL,NULL,NULL,'Jatinder','','','1234567890',NULL,'123 Main Street','Apt 4B','12345','New York','',1,1,'property_manager',NULL,NULL,NULL,NULL),(4,NULL,'Umair','umair@resisqaure.co.uk','2026-09-10 11:53:26','$2y$12$.XhgEppFUfvsiQJFiX9UXuA3ytZxbQqXBNp0aHIhxjd42iyq6LqeG','3x3E3TUqR6','2026-09-10 11:53:26','2026-09-10 11:53:26',NULL,NULL,NULL,NULL,NULL,'Umair','','','1234567890',NULL,'123 Main Street','Apt 4B','12345','New York','',1,1,'staff',NULL,NULL,NULL,NULL),(5,NULL,'Staging Super Admin','admin@resisquare.test','2026-09-10 11:54:36','$2y$12$0kv6r.xkbqG3LQXzYNOcSe3BiBkLEQG.Y5dZCXTlRcHYZJPVgnF7e',NULL,'2026-09-10 11:54:36','2026-09-10 12:18:25',NULL,NULL,NULL,NULL,NULL,'Staging',NULL,'Super Admin','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'super_admin',NULL,NULL,NULL,1),(6,NULL,'Lara Landlord','landlord.owner@resisquare.test','2026-09-10 11:54:37','$2y$12$YFd0OBQe4MBlco8nJ3gsyuSpFq6ZaiKrN8MqxCLHnJKyeczHzChQ2',NULL,'2026-09-10 11:54:37','2026-09-10 11:54:39',NULL,NULL,NULL,NULL,NULL,'Lara',NULL,'Landlord','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'landlord',NULL,NULL,NULL,1),(7,NULL,'Evan Estate','estate.owner@resisquare.test','2026-09-10 11:54:37','$2y$12$yaR99Jy8wGpMQTviyEmB0ur/cfwdiwrfJlJ7o5h8ifTWGZ8qmdfES',NULL,'2026-09-10 11:54:37','2026-09-10 11:54:39',NULL,NULL,NULL,NULL,NULL,'Evan',NULL,'Estate','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'estate_agent',NULL,NULL,NULL,2),(8,NULL,'Sara Staff','estate.staff@resisquare.test','2026-09-10 11:54:37','$2y$12$ZFbEEZNT/IxFbhI8IGGYBuTSW4wTgija8hDEKJ8soTrXsGO9jUeQ2',NULL,'2026-09-10 11:54:37','2026-09-10 11:54:40',2,1,NULL,NULL,NULL,'Sara',NULL,'Staff','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'staff',NULL,NULL,NULL,2),(9,NULL,'Chris Contact','landlord.contact@resisquare.test','2026-09-10 11:54:37','$2y$12$EgU/YR7eGAMrSITCPCkK0.LYD7rhNAAt1PRejZhiH2OJaJ/HexgJq',NULL,'2026-09-10 11:54:38','2026-09-10 11:54:39',NULL,NULL,NULL,NULL,NULL,'Chris',NULL,'Contact','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'landlord',NULL,NULL,NULL,1),(10,NULL,'Tina Tenant','tenant@resisquare.test','2026-09-10 11:54:38','$2y$12$s6msE46sQNTm.pRFR2yoruj5tblEfxbYHclfuDouRuilPon9nH3Jm',NULL,'2026-09-10 11:54:38','2026-09-10 11:54:39',NULL,NULL,NULL,NULL,NULL,'Tina',NULL,'Tenant','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'tenant',NULL,NULL,NULL,1),(11,NULL,'Carl Contractor','contractor@resisquare.test','2026-09-10 11:54:38','$2y$12$VQFQgGRRI2rDbCHbVQFYv./gpsLJDY.G5OT8O1v9WUt.cK8pn4dxm',NULL,'2026-09-10 11:54:38','2026-09-10 11:54:39',NULL,NULL,NULL,NULL,NULL,'Carl',NULL,'Contractor','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'contractor',NULL,NULL,NULL,1),(12,NULL,'Priya Manager','property.manager@resisquare.test','2026-09-10 11:54:38','$2y$12$MyOHwZ67ysWXvLVOW3FW6.5Fz/UxlTZu2uJ/U8UcxQaFJVgGYowgK',NULL,'2026-09-10 11:54:38','2026-09-10 11:54:39',NULL,NULL,NULL,NULL,NULL,'Priya',NULL,'Manager','+440000000000',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'property_manager',NULL,NULL,NULL,1),(14,NULL,'Sabir Sayyed','sabir.nexgeno@gmail.com',NULL,'$2y$12$mheqZ.MFthsB/pQKPY59HuyYjs1wBzIpiZIGGvdodgm.pJXQ2vbd2',NULL,'2026-09-14 09:59:28','2026-09-14 10:02:54',NULL,NULL,NULL,NULL,'[4]',NULL,NULL,NULL,'7666705662',NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,6,6,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_categories`
--

DROP TABLE IF EXISTS `users_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(155) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 for active, 0 for inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_categories`
--

LOCK TABLES `users_categories` WRITE;
/*!40000 ALTER TABLE `users_categories` DISABLE KEYS */;
INSERT INTO `users_categories` VALUES (1,'Owner',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(2,'Property Manager',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(3,'Tenant',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(4,'Landlord',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(5,'Agent',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(6,'Contractor',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(7,'Maintenance',1,'2026-09-10 11:54:20','2026-09-10 11:54:20'),(8,'Service Provider',1,'2026-09-10 11:54:20','2026-09-10 11:54:20');
/*!40000 ALTER TABLE `users_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_order_items`
--

DROP TABLE IF EXISTS `work_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(8,2) DEFAULT NULL,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_order_items_work_order_id_foreign` (`work_order_id`),
  KEY `work_order_items_tax_rate_id_foreign` (`tax_rate_id`),
  CONSTRAINT `work_order_items_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_order_items_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_order_items`
--

LOCK TABLES `work_order_items` WRITE;
/*!40000 ALTER TABLE `work_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `works_order_no` varchar(191) NOT NULL,
  `repair_issue_id` bigint(20) unsigned NOT NULL,
  `job_type_id` bigint(20) unsigned DEFAULT NULL,
  `job_sub_type_id` bigint(20) unsigned DEFAULT NULL,
  `job_status` varchar(191) DEFAULT NULL,
  `job_scope` text DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `invoice_to` varchar(191) DEFAULT NULL,
  `invoice_to_id` bigint(20) unsigned DEFAULT NULL,
  `tentative_start_date` date DEFAULT NULL,
  `tentative_end_date` date DEFAULT NULL,
  `booked_date` date DEFAULT NULL,
  `quote_attachment` varchar(191) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `charge_to_landlord` decimal(10,2) DEFAULT NULL,
  `payment_by` varchar(191) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `extra_notes` text DEFAULT NULL,
  `status` varchar(191) DEFAULT NULL,
  `invoices` bigint(20) unsigned DEFAULT NULL,
  `invoiced_date` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_orders_works_order_no_unique` (`works_order_no`),
  KEY `work_orders_repair_issue_id_foreign` (`repair_issue_id`),
  KEY `work_orders_invoice_to_id_foreign` (`invoice_to_id`),
  KEY `work_orders_created_by_foreign` (`created_by`),
  KEY `work_orders_updated_by_foreign` (`updated_by`),
  KEY `work_orders_account_id_idx` (`account_id`),
  CONSTRAINT `work_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_orders_invoice_to_id_foreign` FOREIGN KEY (`invoice_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_orders_repair_issue_id_foreign` FOREIGN KEY (`repair_issue_id`) REFERENCES `repair_issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_orders_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
INSERT INTO `work_orders` VALUES (1,'STG-WO-001',1,NULL,NULL,'Assigned','Staging work order for contractor portal testing.',NULL,'landlord',6,'2026-09-13','2026-09-15',NULL,NULL,NULL,NULL,NULL,150.00,NULL,'Open',NULL,NULL,6,6,'2026-09-10 11:54:40','2026-09-10 11:54:40',1,NULL,NULL);
/*!40000 ALTER TABLE `work_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'resisquare_laravel_webdeveloper'
--

--
-- Current Database: `resisquare_laravel_webdeveloper`
--


--
-- Final view structure for view `all_note_refunds`
--

/*!50001 DROP VIEW IF EXISTS `all_note_refunds`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `all_note_refunds` AS select concat('credit_',`cr`.`id`) AS `unified_id`,'credit' AS `note_kind`,`cr`.`id` AS `refund_id`,`cr`.`credit_note_id` AS `note_id`,`cn`.`note_number` AS `note_number`,`cr`.`amount` AS `amount`,`cr`.`refund_date` AS `refund_date`,`cr`.`status` AS `status`,`cr`.`transaction_number` AS `transaction_number`,`cr`.`reference` AS `reference`,`cr`.`notes` AS `notes`,`cr`.`processed_by` AS `processed_by`,`cr`.`created_at` AS `created_at` from (`credit_note_refunds` `cr` left join `credit_notes` `cn` on(`cr`.`credit_note_id` = `cn`.`id`)) union all select concat('debit_',`dr`.`id`) AS `unified_id`,'debit' AS `note_kind`,`dr`.`id` AS `refund_id`,`dr`.`debit_note_id` AS `note_id`,`dn`.`note_number` AS `note_number`,`dr`.`amount` AS `amount`,`dr`.`refund_date` AS `refund_date`,`dr`.`status` AS `status`,`dr`.`transaction_number` AS `transaction_number`,`dr`.`reference` AS `reference`,`dr`.`notes` AS `notes`,`dr`.`processed_by` AS `processed_by`,`dr`.`created_at` AS `created_at` from (`debit_note_refunds` `dr` left join `debit_notes` `dn` on(`dr`.`debit_note_id` = `dn`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 15:38:53
