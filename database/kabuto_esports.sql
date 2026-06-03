-- ============================================================
-- KABUTO ESPORTS TOURNAMENT REGISTRATION PLATFORM
-- Database: MySQL 8.0+
-- Charset: utf8mb4
-- ============================================================

-- Import into your selected database (u702149217_WUS1U on Hostinger)
-- No CREATE DATABASE needed — select the database in phpMyAdmin first


-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'admin', 'moderator') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tournaments
-- ============================================================
CREATE TABLE `tournaments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `rules` LONGTEXT DEFAULT NULL,
  `schedule` TEXT DEFAULT NULL,
  `banner` VARCHAR(500) DEFAULT NULL,
  `game` VARCHAR(100) NOT NULL DEFAULT 'BGMI',
  `mode` ENUM('solo', 'duo', 'squad') NOT NULL DEFAULT 'squad',
  `entry_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `prize_pool` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `prize_distribution` TEXT DEFAULT NULL,
  `total_slots` INT UNSIGNED NOT NULL DEFAULT 100,
  `registered_slots` INT UNSIGNED NOT NULL DEFAULT 0,
  `registration_deadline` DATETIME NOT NULL,
  `tournament_start` DATETIME DEFAULT NULL,
  `status` ENUM('upcoming', 'active', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `registration_open` TINYINT(1) NOT NULL DEFAULT 1,
  `contact_info` VARCHAR(500) DEFAULT NULL,
  `discord_link` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_mode` (`mode`),
  INDEX `idx_deadline` (`registration_deadline`),
  INDEX `idx_entry_fee` (`entry_fee`),
  FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: registrations
-- ============================================================
CREATE TABLE `registrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `registration_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: KAB-YYYY-00001',
  `tournament_id` INT UNSIGNED NOT NULL,
  `team_name` VARCHAR(100) NOT NULL,
  `leader_name` VARCHAR(100) NOT NULL,
  `leader_uid` VARCHAR(50) NOT NULL,
  `leader_ign` VARCHAR(50) NOT NULL,
  `mobile` VARCHAR(15) NOT NULL,
  `whatsapp` VARCHAR(15) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `player2_name` VARCHAR(100) DEFAULT NULL,
  `player2_uid` VARCHAR(50) DEFAULT NULL,
  `player3_name` VARCHAR(100) DEFAULT NULL,
  `player3_uid` VARCHAR(50) DEFAULT NULL,
  `player4_name` VARCHAR(100) DEFAULT NULL,
  `player4_uid` VARCHAR(50) DEFAULT NULL,
  `sub_name` VARCHAR(100) DEFAULT NULL,
  `sub_uid` VARCHAR(50) DEFAULT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded', 'free') NOT NULL DEFAULT 'pending',
  `transaction_id` VARCHAR(200) DEFAULT NULL,
  `payment_reference` VARCHAR(200) DEFAULT NULL,
  `amount_paid` DECIMAL(10,2) DEFAULT 0.00,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `confirmation_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_registration_id` (`registration_id`),
  INDEX `idx_tournament_id` (`tournament_id`),
  INDEX `idx_payment_status` (`payment_status`),
  INDEX `idx_email` (`email`),
  INDEX `idx_mobile` (`mobile`),
  INDEX `idx_leader_uid` (`leader_uid`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `registration_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(200) NOT NULL,
  `payu_txn_id` VARCHAR(200) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `payment_gateway` VARCHAR(50) NOT NULL DEFAULT 'PayU',
  `payment_mode` VARCHAR(50) DEFAULT NULL,
  `bank_ref_num` VARCHAR(200) DEFAULT NULL,
  `status` ENUM('initiated', 'success', 'failure', 'pending', 'refunded', 'cancelled') NOT NULL DEFAULT 'initiated',
  `gateway_response` LONGTEXT DEFAULT NULL COMMENT 'Full JSON response from gateway',
  `hash_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_registration_id` (`registration_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_payu_txn_id` (`payu_txn_id`),
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_sessions (for session management)
-- ============================================================
CREATE TABLE `admin_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `session_token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_admin_id` (`admin_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: csrf_tokens
-- ============================================================
CREATE TABLE `csrf_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `session_id` VARCHAR(255) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT ADMIN (password: Admin@Kabuto2026 — CHANGE IMMEDIATELY)
-- ============================================================
INSERT INTO `admins` (`name`, `email`, `password_hash`, `role`) VALUES
('Super Admin', 'admin@kabutoesports.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Default password: password  — CHANGE IN PRODUCTION!

-- ============================================================
-- SAMPLE TOURNAMENTS (for testing)
-- ============================================================
INSERT INTO `tournaments` (
  `name`, `slug`, `description`, `rules`, `schedule`,
  `game`, `mode`, `entry_fee`, `prize_pool`, `prize_distribution`,
  `total_slots`, `registration_deadline`, `tournament_start`,
  `status`, `contact_info`, `discord_link`, `created_by`
) VALUES
(
  'Kabuto Pro League Season 1',
  'kabuto-pro-league-season-1',
  'The inaugural season of Kabuto Pro League! Show your skills and compete with the best BGMI players across India. Massive prize pool up for grabs.',
  '1. All players must have a valid BGMI account.\n2. Players must join the lobby 15 minutes before the match.\n3. Any form of cheating/hacking will result in immediate disqualification.\n4. Team leader is responsible for the entire team.\n5. Decisions by the tournament admin are final.\n6. No refunds after successful payment.\n7. Players must be 16+ years of age.\n8. One player can only register with one team.',
  'Day 1 (Qualifiers): July 10, 2026 - 6:00 PM IST\nDay 2 (Semi-Finals): July 12, 2026 - 6:00 PM IST\nDay 3 (Grand Finals): July 14, 2026 - 6:00 PM IST',
  'BGMI', 'squad', 200.00, 50000.00,
  '1st Place: ₹25,000\n2nd Place: ₹12,000\n3rd Place: ₹7,000\n4th Place: ₹3,500\n5th Place: ₹2,500',
  100, '2026-07-08 23:59:59', '2026-07-10 18:00:00',
  'active', '+91 98765 43210', 'https://discord.gg/kabutoesports', 1
),
(
  'Kabuto Free Fire Friday',
  'kabuto-free-fire-friday',
  'Weekly free tournament for all skill levels. Win exciting prizes every Friday!',
  '1. Free entry for all players.\n2. Solo mode - no teams.\n3. Standard BGMI rules apply.\n4. Results announced within 24 hours.',
  'Every Friday at 8:00 PM IST',
  'BGMI', 'solo', 0.00, 5000.00,
  '1st Place: ₹2,500\n2nd Place: ₹1,500\n3rd Place: ₹1,000',
  50, '2026-07-04 20:00:00', '2026-07-05 20:00:00',
  'active', '+91 98765 43210', 'https://discord.gg/kabutoesports', 1
),
(
  'Kabuto Duo Domination',
  'kabuto-duo-domination',
  'Team up with your best partner and dominate the battlefield. 2v2 tournament with amazing prizes!',
  '1. Duo teams only.\n2. Both players must register together.\n3. No substitutions allowed mid-tournament.\n4. Standard rules apply.',
  'Tournament Date: July 20, 2026\nCheck-in: 5:00 PM IST\nStart: 6:00 PM IST',
  'BGMI', 'duo', 100.00, 20000.00,
  '1st Place: ₹10,000\n2nd Place: ₹5,000\n3rd Place: ₹3,000\n4th Place: ₹2,000',
  80, '2026-07-18 23:59:59', '2026-07-20 18:00:00',
  'upcoming', '+91 98765 43210', 'https://discord.gg/kabutoesports', 1
);
