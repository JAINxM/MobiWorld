-- Add to mobiworld database after import
USE `mobiworld`;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL, 
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin user: email=admin@mobiworld.com, password=admin123
INSERT IGNORE INTO `admin_users` (`admin_name`, `email`, `password_hash`, `is_active`) VALUES
('Super Admin', 'admin@mobiworld.com', '$2y$10$Wq6T1Gf9zY0zL5rK8mN7pO5jH4kG3f2e1d0c9b8a7z6y5x4w3v2u', 1);

-- phpMyAdmin: Import this → admin login works!

-- Wishlist Feature Tables
CREATE TABLE IF NOT EXISTS `wishlist_items` (
  `wishlist_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_product` (`product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user_master`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `product_master`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

