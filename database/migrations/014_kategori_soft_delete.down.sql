-- Rollback 014: Drop is_active column and index from kategori
DROP INDEX IF EXISTS `idx_kategori_is_active` ON `kategori`;
ALTER TABLE `kategori` DROP COLUMN IF EXISTS `is_active`;
