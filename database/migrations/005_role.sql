-- Migration 005: Add role columns, unit_id FK, and account security fields to admin
ALTER TABLE `admin`
  ADD COLUMN IF NOT EXISTS `unit_id` INT(11) NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `unit_id`,
  ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `failed_attempts` INT NOT NULL DEFAULT 0 AFTER `must_change_password`,
  ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL AFTER `failed_attempts`,
  ADD COLUMN IF NOT EXISTS `last_login_at` DATETIME NULL AFTER `locked_until`;

ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_unit` FOREIGN KEY (`unit_id`)
    REFERENCES `units` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;
