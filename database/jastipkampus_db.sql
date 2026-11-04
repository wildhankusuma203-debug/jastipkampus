-- ============================================================================
-- SKEMA DATABASE MYSQL: JASTIPKAMPUS (LARAVEL 11 + MYSQL)
-- Zona Waktu: Asia/Jakarta (WIB)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `jastipkampus_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jastipkampus_db`;

-- 1. Tabel faculties
DROP TABLE IF EXISTS `faculties`;
CREATE TABLE `faculties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faculties_code_unique` (`code`),
  KEY `faculties_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel study_programs
DROP TABLE IF EXISTS `study_programs`;
CREATE TABLE `study_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `faculty_id` bigint unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `degree` varchar(15) NOT NULL DEFAULT 'S1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `study_programs_faculty_id_foreign` (`faculty_id`),
  KEY `study_programs_is_active_index` (`is_active`),
  CONSTRAINT `study_programs_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel classes
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `study_program_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `cohort_year` varchar(10) NOT NULL DEFAULT '2024',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `classes_study_program_id_foreign` (`study_program_id`),
  KEY `classes_is_active_index` (`is_active`),
  CONSTRAINT `classes_study_program_id_foreign` FOREIGN KEY (`study_program_id`) REFERENCES `study_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `label` varchar(80) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `university` varchar(150) NOT NULL DEFAULT 'Universitas Nusantara',
  `faculty_id` bigint unsigned DEFAULT NULL,
  `study_program_id` bigint unsigned DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `nim` varchar(40) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `wallet_balance` bigint NOT NULL DEFAULT '0',
  `show_class_publicly` tinyint(1) NOT NULL DEFAULT '1',
  `is_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `admin_verification_status` enum('NOT_SUBMITTED','PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'NOT_SUBMITTED',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_faculty_id_foreign` (`faculty_id`),
  KEY `users_study_program_id_foreign` (`study_program_id`),
  KEY `users_class_id_foreign` (`class_id`),
  KEY `users_is_suspended_index` (`is_suspended`),
  KEY `users_admin_verification_status_index` (`admin_verification_status`),
  CONSTRAINT `users_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_study_program_id_foreign` FOREIGN KEY (`study_program_id`) REFERENCES `study_programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel role_user
DROP TABLE IF EXISTS `role_user`;
CREATE TABLE `role_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_user_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel system_configs
DROP TABLE IF EXISTS `system_configs`;
CREATE TABLE `system_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `platform_name` varchar(80) NOT NULL DEFAULT 'JastipKampus',
  `allow_cross_faculty_orders` tinyint(1) NOT NULL DEFAULT '1',
  `require_ktm_for_admin` tinyint(1) NOT NULL DEFAULT '1',
  `max_ktm_size_mb` tinyint unsigned NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabel admin_applications
DROP TABLE IF EXISTS `admin_applications`;
CREATE TABLE `admin_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `university` varchar(150) NOT NULL,
  `faculty_id` bigint unsigned DEFAULT NULL,
  `study_program_id` bigint unsigned DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `nim` varchar(40) NOT NULL,
  `ktm_file_name` varchar(150) NOT NULL,
  `ktm_file_type` varchar(60) NOT NULL,
  `ktm_file_size` int unsigned NOT NULL,
  `ktm_checksum` varchar(64) NOT NULL,
  `ktm_private_path` varchar(255) NOT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `reviewed_by_id` bigint unsigned DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_applications_user_id_foreign` (`user_id`),
  KEY `admin_applications_status_index` (`status`),
  CONSTRAINT `admin_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabel categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categories_created_by_foreign` (`created_by`),
  CONSTRAINT `categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabel services
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_admin_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `price` bigint NOT NULL,
  `requirements` varchar(300) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `faculty_id` bigint unsigned DEFAULT NULL,
  `study_program_id` bigint unsigned DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `show_class_publicly` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `services_owner_admin_id_foreign` (`owner_admin_id`),
  KEY `services_category_id_foreign` (`category_id`),
  KEY `services_is_active_index` (`is_active`),
  KEY `services_is_published_index` (`is_published`),
  CONSTRAINT `services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `services_owner_admin_id_foreign` FOREIGN KEY (`owner_admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Tabel orders
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_code` varchar(40) NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `owner_admin_id` bigint unsigned DEFAULT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `customer_faculty_id` bigint unsigned DEFAULT NULL,
  `customer_program_id` bigint unsigned DEFAULT NULL,
  `customer_class_id` bigint unsigned DEFAULT NULL,
  `is_custom_request` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `items_detail` varchar(300) NOT NULL,
  `pickup_location` varchar(220) NOT NULL,
  `delivery_location` varchar(220) NOT NULL,
  `deadline` datetime NOT NULL,
  `notes` varchar(300) DEFAULT NULL,
  `supporting_info` varchar(300) DEFAULT NULL,
  `customer_budget` bigint NOT NULL DEFAULT '0',
  `offered_price` bigint DEFAULT NULL,
  `offer_note` varchar(255) DEFAULT NULL,
  `agreed_price` bigint DEFAULT NULL,
  `status` enum('WAITING_REVIEW','WAITING_OFFER','WAITING_CUSTOMER_APPROVAL','APPROVED_FOR_PROCESS','IN_PROGRESS','WAITING_CUSTOMER_COMPLETION','COMPLETED','CANCELLED') NOT NULL DEFAULT 'WAITING_OFFER',
  `escrow_status` enum('UNPAID','HELD_IN_ESCROW','RELEASED_TO_ADMIN','REFUNDED') NOT NULL DEFAULT 'UNPAID',
  `escrow_payment_method` varchar(80) DEFAULT NULL,
  `escrow_paid_at` datetime DEFAULT NULL,
  `receipt_proof_path` varchar(255) DEFAULT NULL,
  `delivery_proof_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_code_unique` (`order_code`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_owner_admin_id_foreign` (`owner_admin_id`),
  KEY `orders_status_index` (`status`),
  KEY `orders_escrow_status_index` (`escrow_status`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_owner_admin_id_foreign` FOREIGN KEY (`owner_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Tabel order_histories
DROP TABLE IF EXISTS `order_histories`;
CREATE TABLE `order_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `status` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_histories_order_id_foreign` (`order_id`),
  CONSTRAINT `order_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Tabel order_chats
DROP TABLE IF EXISTS `order_chats`;
CREATE TABLE `order_chats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `sender_name` varchar(120) NOT NULL,
  `sender_role` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_chats_order_id_foreign` (`order_id`),
  CONSTRAINT `order_chats_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Tabel payments
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('ESCROW_DEPOSIT','ESCROW_RELEASE','ESCROW_REFUND','WALLET_WITHDRAWAL') NOT NULL,
  `amount` bigint NOT NULL,
  `payment_method` varchar(80) DEFAULT NULL,
  `bank_or_ewallet` varchar(80) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'SUCCESS',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_foreign` (`order_id`),
  KEY `payments_user_id_foreign` (`user_id`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Tabel reviews
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `owner_admin_id` bigint unsigned DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_order_id_unique` (`order_id`),
  CONSTRAINT `reviews_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Tabel notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `related_order_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(300) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Tabel reports
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reporter_id` bigint unsigned NOT NULL,
  `reporter_name` varchar(120) NOT NULL,
  `target_title` varchar(150) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('OPEN','RESOLVED') NOT NULL DEFAULT 'OPEN',
  `resolution_note` varchar(255) DEFAULT NULL,
  `resolved_by_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reports_reporter_id_foreign` (`reporter_id`),
  CONSTRAINT `reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Tabel audit_logs
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `actor_role` varchar(60) NOT NULL,
  `action` varchar(80) NOT NULL,
  `target_id` varchar(80) DEFAULT NULL,
  `detail` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Tabel sessions Laravel
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATA AWAL (SEEDER): ROLES, SYSTEM CONFIG, FAKULTAS, PRODI, KELAS
-- ============================================================================
INSERT INTO `roles` (`id`, `name`, `label`) VALUES
(1, 'CUSTOMER', 'Mahasiswa Customer'),
(2, 'ADMIN', 'Admin Penyedia Layanan Terverifikasi'),
(3, 'SYSADMIN', 'Administrator Utama Sistem');

INSERT INTO `system_configs` (`id`, `platform_name`, `allow_cross_faculty_orders`, `require_ktm_for_admin`, `max_ktm_size_mb`) VALUES
(1, 'JastipKampus', 1, 1, 2);

INSERT INTO `faculties` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'FIK', 'Fakultas Ilmu Komputer', 1),
(2, 'FTI', 'Fakultas Teknik Industri', 1),
(3, 'FEB', 'Fakultas Ekonomi dan Bisnis', 1);

INSERT INTO `study_programs` (`id`, `faculty_id`, `name`, `degree`, `is_active`) VALUES
(1, 1, 'Informatika', 'S1', 1),
(2, 1, 'Sistem Informasi', 'S1', 1),
(3, 2, 'Teknik Industri', 'S1', 1),
(4, 3, 'Manajemen', 'S1', 1);

INSERT INTO `classes` (`id`, `study_program_id`, `name`, `cohort_year`, `is_active`) VALUES
(1, 1, 'Informatika A', '2024', 1),
(2, 1, 'Informatika B', '2024', 1),
(3, 2, 'Sistem Informasi A', '2024', 1),
(4, 3, 'Teknik Industri A', '2023', 1),
(5, 4, 'Manajemen A', '2024', 1);

SET FOREIGN_KEY_CHECKS = 1;