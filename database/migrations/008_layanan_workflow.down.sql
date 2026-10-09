-- Rollback 008: Drop workflow and metadata columns from layanan
ALTER TABLE `layanan`
  DROP FOREIGN KEY `fk_layanan_updater`,
  DROP FOREIGN KEY `fk_layanan_unit`,
  DROP COLUMN IF EXISTS `reject_reason`,
  DROP COLUMN IF EXISTS `updated_by`,
  DROP COLUMN IF EXISTS `search_aliases`,
  DROP COLUMN IF EXISTS `unit_id`,
  DROP COLUMN IF EXISTS `status`;
