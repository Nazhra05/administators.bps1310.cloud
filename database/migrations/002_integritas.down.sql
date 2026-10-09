-- Rollback 002: Integrity constraints, timestamps, and indexes
DROP INDEX IF EXISTS `idx_layanan_akses` ON `layanan`;
DROP INDEX IF EXISTS `idx_layanan_nama` ON `layanan`;

ALTER TABLE `layanan`
  DROP COLUMN IF EXISTS `updated_at`,
  DROP COLUMN IF EXISTS `created_at`;

ALTER TABLE `kategori`
  DROP COLUMN IF EXISTS `updated_at`,
  DROP COLUMN IF EXISTS `created_at`;
