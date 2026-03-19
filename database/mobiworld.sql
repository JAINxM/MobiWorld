CREATE DATABASE IF NOT EXISTS `mobiworld` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mobiworld`;

-- Users table (matches api/register.php & api/login.php)
DROP DATABASE IF EXISTS `mobiworld`;
CREATE DATABASE IF NOT EXISTS `mobiworld` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mobiworld`;
CREATE TABLE `user_master` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(15),
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Sample Users (passwords hashed with password_hash('password', PASSWORD_BCRYPT))
INSERT INTO `user_master` (`full_name`, `email`, `password_hash`, `mobile`, `is_active`) VALUES
('Test User', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567890', 1),
('Admin User', 'admin@mobiworld.com', '$2y$10$Wq6T1Gf9zY0zL5rK8mN7pO5jH4kG3f2e1d0c9b8a7z6y5x4w3v2u', '+0987654321', 1); -- password: admin123

-- Products table (matches other APIs)
DROP TABLE IF EXISTS `product_master`;
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Insert sample products (from previous data)
INSERT INTO `product_master` (`name`, `brand`, `regular_price`, `discounted_price`, `stock_quantity`, `is_active`, `image_url`, `description`, `specs`) VALUES
('iPhone 15 Pro', 'Apple', 999.00, 949.00, 50, 1, 'https://images.unsplash.com/photo-1696446701796-da61225697cc?auto=format&fit=crop&q=80&w=400', 'Titanium design, A17 Pro chip', '{"Display": "6.1-inch OLED", "Processor": "A17 Pro"}'),
('Samsung Galaxy S24 Ultra', 'Samsung', 1299.00, 1199.00, 30, 1, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf', 'Galaxy AI experience with 200MP camera', '{"Display": "6.8-inch QHD+", "Camera": "200MP"}');

-- Shopping cart tables (matches cart APIs)
DROP TABLE IF EXISTS `shopping_cart`;
CREATE TABLE `shopping_cart` (
  `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `cart_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price_at_time` DECIMAL(10,2) NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_cart_items_cart_id` (`cart_id`),
  KEY `fk_cart_items_product_id` (`product_id`),
  CONSTRAINT `fk_cart_items_cart_id` FOREIGN KEY (`cart_id`) REFERENCES `shopping_cart(cart_id`) ON DELETE CASCADE,
CONSTRAINT `fk_cart_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `product_master(product_id`)
) ENGINE=InnoDB;

-- Orders tables
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `order_date` DATE,
  `subtotal` DECIMAL(10,2),
  `shipping_cost` DECIMAL(10,2) DEFAULT 0,
  `tax_amount` DECIMAL(10,2) DEFAULT 0,
  `total_amount` DECIMAL(10,2),
  `order_status` ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` TEXT,
  `recipient_name` VARCHAR(255),
  `payment_method` VARCHAR(50),
  `transaction_id` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_orders_user_id` (`user_id`),
CONSTRAINT `fk_orders_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_master(user_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `order_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price_at_time` DECIMAL(10,2),
  `subtotal` DECIMAL(10,2),
  KEY `fk_order_items_order_id` (`order_id`),
  KEY `fk_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_order_items_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders(order_id`) ON DELETE CASCADE,
CONSTRAINT `fk_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `product_master(product_id`)
) ENGINE=InnoDB;

-- Indexes
CREATE INDEX `idx_user_email` ON `user_master`(`email`);
CREATE INDEX `idx_cart_user` ON `shopping_cart`(`user_id`, `is_active`);

