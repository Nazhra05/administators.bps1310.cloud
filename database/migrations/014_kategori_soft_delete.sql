-- Migration 014: Add is_active column to kategori for soft delete
ALTER TABLE `kategori`
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `deskripsi`;

CREATE INDEX IF NOT EXISTS `idx_kategori_is_active` ON `kategori` (`is_active`);
