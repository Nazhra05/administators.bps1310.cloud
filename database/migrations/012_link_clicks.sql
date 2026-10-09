-- Migration 012: Create link_clicks table
CREATE TABLE IF NOT EXISTS `link_clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `layanan_id` int(11) NOT NULL,
  `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clicks_user` (`user_id`, `clicked_at`),
  KEY `idx_clicks_layanan` (`layanan_id`),
  CONSTRAINT `fk_clicks_user` FOREIGN KEY (`user_id`)
    REFERENCES `admin` (`id_admin`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_clicks_layanan` FOREIGN KEY (`layanan_id`)
    REFERENCES `layanan` (`id_layanan`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
