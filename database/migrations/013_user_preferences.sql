-- Migration 013: Create user_preferences table
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `user_id` int(11) NOT NULL,
  `pinned_layanan` json DEFAULT NULL,
  `search_history` json DEFAULT NULL,
  `theme` varchar(10) DEFAULT 'light',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_preferences_user` FOREIGN KEY (`user_id`)
    REFERENCES `admin` (`id_admin`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
