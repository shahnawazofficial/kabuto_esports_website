-- KABUTO ESPORTS — User Auth & PayU Updates
-- Run this in phpMyAdmin on database: u702149217_WUS1U

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `mobile`        VARCHAR(15) DEFAULT NULL,
  `bgmi_uid`      VARCHAR(50) DEFAULT NULL,
  `bgmi_ign`      VARCHAR(50) DEFAULT NULL,
  `is_verified`   TINYINT(1) NOT NULL DEFAULT 1,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `last_login`    DATETIME DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_email` (`email`),
  INDEX `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Add user_id column to registrations (link registrations to user accounts)
-- ============================================================
ALTER TABLE `registrations`
  ADD COLUMN `user_id` INT UNSIGNED DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_user_id` (`user_id`),
  ADD FOREIGN KEY `fk_reg_user` (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
