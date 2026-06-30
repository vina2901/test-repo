-- Siguraduhing gamitin ang tamang database base sa iyong screenshot
CREATE DATABASE IF NOT EXISTS `daz_inventory`;
USE `daz_inventory`;

-- 1. Table structure para sa 'users' (Default table para sa system access)
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` ENUM('admin', 'staff') DEFAULT 'staff',
    `reset_token` VARCHAR(255) DEFAULT NULL,
    `token_expiry` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table structure para sa 'categories'
CREATE TABLE IF NOT EXISTS `categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table structure para sa 'master_catalog' (Para sa master_catalog.php)
CREATE TABLE IF NOT EXISTS `master_catalog` (
    `catalog_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `qualification` VARCHAR(150) NOT NULL,
    `unit_type` VARCHAR(50) NOT NULL DEFAULT 'pcs',
    `minimum_stock` INT NOT NULL DEFAULT 2,
    `item_image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qualification_search` (`qualification`, `item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table structure para sa 'inventory_items' (Para sa inventory.php at borrow.php)
CREATE TABLE IF NOT EXISTS `inventory_items` (
    `item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_code` VARCHAR(100) NOT NULL UNIQUE,
    `item_name` VARCHAR(255) NOT NULL,
    `item_type` ENUM('Asset', 'Consumable') NOT NULL,
    `quantity_available` INT NOT NULL DEFAULT 0,
    `minimum_stock_level` INT NOT NULL DEFAULT 3,
    `storage_location` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table structure para sa 'physical_inventory' (Para sa physical_inventory.php at dashboard.php)
CREATE TABLE IF NOT EXISTS `physical_inventory` (
    `inventory_id` INT AUTO_INCREMENT PRIMARY KEY,
    `catalog_id` INT NOT NULL,
    `serial_no` VARCHAR(100) NOT NULL UNIQUE,
    `location` VARCHAR(255) NOT NULL,
    `condition_status` VARCHAR(100) NOT NULL DEFAULT 'Good',
    `current_status` ENUM('In Stock', 'Damaged', 'Pending Purchase') NOT NULL DEFAULT 'In Stock',
    `last_checked` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`catalog_id`) REFERENCES `master_catalog` (`catalog_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table structure para sa 'borrow_records' (Para sa borrow.php)
CREATE TABLE IF NOT EXISTS `borrow_records` (
    `borrow_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `borrower_name` VARCHAR(255) NOT NULL,
    `quantity_borrowed` INT NOT NULL DEFAULT 1,
    `borrow_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expected_return_date` DATE NOT NULL,
    `status` ENUM('Borrowed', 'Returned') NOT NULL DEFAULT 'Borrowed',
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Burahin muna ang lumang table kung gusto mong i-recreate nang buo na walang error
DROP TABLE IF EXISTS `procurement_requests`;

-- Table structure para sa 'procurement_requests' (Na may hiwalay na unit_price at estimated_cost)
CREATE TABLE `procurement_requests` (
    `request_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `reason` TEXT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- DINAGDAG: Dito mase-save ang presyo kada piraso
    `estimated_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- Nananatiling kabuuang total (Qty * Price)
    `requested_by` VARCHAR(150) NOT NULL,
    `request_date` DATETIME NOT NULL, 
    `approval_status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Mag-insert ng isang default user (Username: admin | Password: password123)
INSERT INTO `users` (`username`, `password`, `email`, `role`) 
VALUES ('admin', '$2y$10$EPY9m2vNOn8v8I2Y4V5SXO9X4pM2hZt2m7M2Y7R6v7kCg0vW9yvOi', 'admin@daz.com', 'admin')
ON DUPLICATE KEY UPDATE `username`=`username`;

ALTER TABLE procurement_requests 
MODIFY COLUMN request_date DATETIME NOT NULL;