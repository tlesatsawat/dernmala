-- SQL schema for the POS system

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS `pos_mala` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pos_mala`;

-- Table: tables (restaurant tables)
CREATE TABLE IF NOT EXISTS `tables` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `number` INT NOT NULL UNIQUE,
  `status` ENUM('EMPTY','ORDERING','NEED_STAFF','PAYING','PAID') DEFAULT 'EMPTY'
) ENGINE=InnoDB;

-- Categories of menu items
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- Menu items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `table_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('OPEN','PAID','CANCELLED') DEFAULT 'OPEN',
  `total` DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `menu_item_id` INT UNSIGNED NOT NULL,
  `qty` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `modifications` JSON DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `provider_ref` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('PENDING','SUCCEEDED','FAILED','EXPIRED') DEFAULT 'PENDING',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `paid_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Notifications (for call staff and others)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL,
  `table_id` INT UNSIGNED DEFAULT NULL,
  `message` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_read` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`table_id`) REFERENCES `tables`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Settings table for storing key/value (e.g., Beam API key if desired via admin)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT NOT NULL
) ENGINE=InnoDB;

-- Sample data
INSERT INTO `categories` (`name`) VALUES ('ไม้เสียบ'),('ชุดคอมโบ'),('ของทานเล่น'),('เครื่องดื่ม');

INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `photo`) VALUES
 (1, 'เห็ดออรินจิ', 'อร่อยกรุบ', 15.00, 'mushroom.jpg'),
 (1, 'เบคอนพันเห็ด', 'เบคอนหอมอร่อย', 25.00, 'bacon.jpg'),
 (2, 'ชุด 10 ไม้', 'เลือกไม้เสียบได้ 10 อย่าง', 180.00, 'combo10.jpg');

INSERT INTO `tables` (`number`, `status`) VALUES (1,'EMPTY'),(2,'EMPTY'),(3,'EMPTY'),(4,'EMPTY'),(5,'EMPTY');