-- Skrip pembuatan database dan user untuk lingkungan pengembangan lokal (XAMPP MariaDB/MySQL)
-- Jalankan via phpMyAdmin (tab SQL) atau terminal mysql: C:\xampp\mysql\bin\mysql.exe -u root

CREATE DATABASE IF NOT EXISTS bps_solsel_dev  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS bps_solsel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'bps_app'@'localhost'      IDENTIFIED BY 'change_me_dev';
CREATE USER IF NOT EXISTS 'bps_migrator'@'localhost' IDENTIFIED BY 'change_me_migrator_dev';

GRANT SELECT, INSERT, UPDATE, DELETE ON bps_solsel_dev.*  TO 'bps_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON bps_solsel_test.* TO 'bps_app'@'localhost';
GRANT ALL PRIVILEGES ON bps_solsel_dev.*  TO 'bps_migrator'@'localhost';
GRANT ALL PRIVILEGES ON bps_solsel_test.* TO 'bps_migrator'@'localhost';
FLUSH PRIVILEGES;
