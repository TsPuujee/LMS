-- Separate database for the automated test suite (dev only).
CREATE DATABASE IF NOT EXISTS `lms_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `lms_test`.* TO 'lms'@'%';
FLUSH PRIVILEGES;
