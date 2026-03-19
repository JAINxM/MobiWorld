DROP DATABASE IF EXISTS `mobiworld`;
CREATE DATABASE `mobiworld` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mobiworld`;

-- Users table (core for login/register)
CREATE TABLE `user_master` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(15),
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample users
INSERT INTO `user_master` (`full_name`, `email`, `password_hash`, `mobile`) VALUES
('Test User', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567890'),
('Admin User', 'admin@mobiworld.com', '$2y$10$Wq6T1Gf9zY0zL5rK8mN7pO5jH4kG3f2e1d0c9b8a7z6y5x4w3v2u', '+0987654321');

-- Products (for API)
CREATE TABLE `product_master` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `regular_price` DECIMAL(10,2) NOT NULL,
  `discounted_price` DECIMAL(10,2),
  `stock_quantity` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `image_url` VARCHAR(500),
  `description` TEXT,
  `specs` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `product_master` (`name`, `brand`, `regular_price`, `discounted_price`, `stock_quantity`, `image_url`, `description`) VALUES
('iPhone 15 Pro', 'Apple', 999.00, 949.00, 50, 'https://images.unsplash.com/photo-1696446701796-da61225697cc?auto=format&fit=crop&q=80&w=400', 'Titanium design'),
('Samsung Galaxy S24 Ultra', 'Samsung', 1299.00, 1199.00, 30, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf', 'Galaxy AI');

-- Basic cart/orders (no FK to avoid syntax issues)
CREATE TABLE `shopping_cart` (
  `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `cart_items` (
  `cart_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT DEFAULT 1,
  `price_at_time` DECIMAL(10,2) NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `orders` (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(10,2),
  `order_status` ENUM('pending','confirmed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `order_items` (
  `order_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price_at_time` DECIMAL(10,2)
) ENGINE=InnoDB;

-- Indexes
CREATE INDEX `idx_user_master_email` ON `user_master` (`email`);
CREATE INDEX `idx_shopping_cart_user` ON `shopping_cart` (`user_id`, `is_active`);
