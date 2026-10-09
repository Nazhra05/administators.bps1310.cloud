-- Migration 003: Ensure password column is VARCHAR(255) for modern hash algorithms
ALTER TABLE `admin`
  MODIFY COLUMN `password` VARCHAR(255) NULL DEFAULT NULL;
