-- Rollback 005: Drop role columns and unit_id FK from admin
ALTER TABLE `admin`
  DROP FOREIGN KEY `fk_admin_unit`,
  DROP COLUMN IF EXISTS `last_login_at`,
  DROP COLUMN IF EXISTS `locked_until`,
  DROP COLUMN IF EXISTS `failed_attempts`,
  DROP COLUMN IF EXISTS `must_change_password`,
  DROP COLUMN IF EXISTS `is_active`,
  DROP COLUMN IF EXISTS `unit_id`;
