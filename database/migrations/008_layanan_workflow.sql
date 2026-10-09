-- Migration 008: Add workflow and metadata columns to layanan
ALTER TABLE `layanan`
  ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','published','archived') NOT NULL DEFAULT 'published' AFTER `keyword`,
  ADD COLUMN IF NOT EXISTS `unit_id` INT(11) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `search_aliases` TEXT NULL AFTER `unit_id`,
  ADD COLUMN IF NOT EXISTS `updated_by` INT(11) NULL AFTER `search_aliases`,
  ADD COLUMN IF NOT EXISTS `reject_reason` VARCHAR(255) NULL AFTER `updated_by`;

ALTER TABLE `layanan`
  ADD CONSTRAINT `fk_layanan_unit` FOREIGN KEY (`unit_id`)
    REFERENCES `units` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `layanan`
  ADD CONSTRAINT `fk_layanan_updater` FOREIGN KEY (`updated_by`)
    REFERENCES `admin` (`id_admin`)
    ON DELETE SET NULL ON UPDATE CASCADE;
