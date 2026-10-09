-- Migration 009: Create settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `skey` varchar(50) NOT NULL,
  `svalue` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
