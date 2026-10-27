-- =========================================================
-- Tickets to Thrill — MySQL schema
-- =========================================================
-- Import this file via phpMyAdmin's "Import" tab (or run
-- `mysql -u root -p < schema.sql`) if you'd rather set up the
-- database by hand instead of letting the app create it on its
-- own on first run.
--
-- This mirrors exactly what db_connect.php creates
-- automatically, including the same seeded admin account, so
-- either path leaves you in the same state. If you already let
-- the app auto-create things, you do NOT need to run this file
-- too — pick one or the other.
-- =========================================================

CREATE DATABASE IF NOT EXISTS `tickets_to_thrill`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `tickets_to_thrill`;

-- ---------------------------------------------------------
-- users
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(120) NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `phone`         VARCHAR(30) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          VARCHAR(20) NOT NULL DEFAULT 'user',
  `created_at`    DATETIME NOT NULL,
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- orders  (one row per checkout/payment attempt)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `total`       DECIMAL(10,2) NOT NULL,
  `status`      VARCHAR(20) NOT NULL DEFAULT 'pending',
  `payment_ref` VARCHAR(60) NULL,
  `visit_date`  DATE NULL,
  `created_at`  DATETIME NOT NULL,
  KEY `idx_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- bookings  (one row per ticket line item within an order)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `order_id`    INT UNSIGNED NULL,
  `ticket_id`   VARCHAR(40) NOT NULL,
  `ticket_name` VARCHAR(160) NOT NULL,
  `category`    VARCHAR(40) NOT NULL,
  `qty`         INT UNSIGNED NOT NULL,
  `price`       DECIMAL(10,2) NOT NULL,
  `status`      VARCHAR(20) NOT NULL DEFAULT 'upcoming',
  `created_at`  DATETIME NOT NULL,
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_order` (`order_id`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- messages  (contact form submissions)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `subject`    VARCHAR(120) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Seed the default admin account so the admin panel is
-- reachable right away.
--   email:    admin@ticketstothrill.example
--   password: Admin@123
-- Change the password (or delete this row) before deploying
-- anywhere real — see README.md.
-- ---------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `phone`, `password_hash`, `role`, `created_at`)
VALUES (
  'Park Admin',
  'admin@ticketstothrill.example',
  NULL,
  '$2b$10$6ew.bD6AVW87ay7QVlNHHudK5Y6HbE2ysQGFp.fZGT/2QlZ7jaOwS',
  'admin',
  NOW()
)
ON DUPLICATE KEY UPDATE `email` = `email`;
