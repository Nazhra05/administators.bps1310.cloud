-- Rollback 003: Revert password column
ALTER TABLE `admin`
  MODIFY COLUMN `password` VARCHAR(255) NULL;
