-- Store Preschool migration 001: vendors and dark stores
-- Select the existing database first, then import this file.
-- Hostinger: u750208840_storepredb
-- Local XAMPP: storepreschool
-- This does not create a database and does not delete products, customers, or orders.
-- Safe to run again.

SET NAMES utf8mb4;

-- Let users be a vendor or a dark-store login.
SET @role_type = (
    SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);
SET @sql = IF(@role_type LIKE '%vendor%',
    'SELECT 1',
    'ALTER TABLE users MODIFY role ENUM(''customer'',''admin'',''vendor'',''store'') NOT NULL DEFAULT ''customer'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Link a login to one vendor or one dark store.
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'vendor_id') = 0,
    'ALTER TABLE users ADD COLUMN vendor_id INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'dark_store_id') = 0,
    'ALTER TABLE users ADD COLUMN dark_store_id INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Which vendor supplies each product.
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'vendor_id') = 0,
    'ALTER TABLE products ADD COLUMN vendor_id INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Which dark store is packing the order.
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'dark_store_id') = 0,
    'ALTER TABLE orders ADD COLUMN dark_store_id INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS vendors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(160) NOT NULL,
    city VARCHAR(80) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_vendor_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dark_stores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    code VARCHAR(40) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(80) NOT NULL,
    pincode CHAR(6) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_store_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dark_store_pins (
    pincode CHAR(6) PRIMARY KEY,
    dark_store_id INT UNSIGNED NOT NULL,
    KEY idx_store_pin (dark_store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_stock (
    dark_store_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    PRIMARY KEY (dark_store_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_moves (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dark_store_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    vendor_id INT UNSIGNED NULL,
    quantity INT NOT NULL,
    note VARCHAR(180) NOT NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_move_store (dark_store_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO vendors (name, phone, email, city)
SELECT 'Little Hands Supply', '9811111111', 'vendor.toys@storepreschoolapp.in', 'Pune'
WHERE NOT EXISTS (SELECT 1 FROM vendors WHERE email = 'vendor.toys@storepreschoolapp.in');

INSERT INTO vendors (name, phone, email, city)
SELECT 'PaperNest', '9822222222', 'vendor.books@storepreschoolapp.in', 'Pune'
WHERE NOT EXISTS (SELECT 1 FROM vendors WHERE email = 'vendor.books@storepreschoolapp.in');

INSERT INTO vendors (name, phone, email, city)
SELECT 'ClassBench', '9833333333', 'vendor.kits@storepreschoolapp.in', 'Kochi'
WHERE NOT EXISTS (SELECT 1 FROM vendors WHERE email = 'vendor.kits@storepreschoolapp.in');

INSERT INTO dark_stores (name, code, address, city, pincode, phone)
SELECT 'Pune Camp Dark Store', 'PUNE-CAMP', '12 MG Road, Camp', 'Pune', '411001', '9844444444'
WHERE NOT EXISTS (SELECT 1 FROM dark_stores WHERE code = 'PUNE-CAMP');

INSERT INTO dark_stores (name, code, address, city, pincode, phone)
SELECT 'Kochi Lake Dark Store', 'KOCHI-LAKE', '8 Lake View Road', 'Kochi', '682001', '9855555555'
WHERE NOT EXISTS (SELECT 1 FROM dark_stores WHERE code = 'KOCHI-LAKE');

INSERT IGNORE INTO dark_store_pins (pincode, dark_store_id)
SELECT '411001', id FROM dark_stores WHERE code = 'PUNE-CAMP';
INSERT IGNORE INTO dark_store_pins (pincode, dark_store_id)
SELECT '411004', id FROM dark_stores WHERE code = 'PUNE-CAMP';
INSERT IGNORE INTO dark_store_pins (pincode, dark_store_id)
SELECT '682001', id FROM dark_stores WHERE code = 'KOCHI-LAKE';

UPDATE products p
JOIN vendors v ON v.email = 'vendor.toys@storepreschoolapp.in'
SET p.vendor_id = v.id
WHERE p.category_id IN (1, 7) AND p.vendor_id IS NULL;

UPDATE products p
JOIN vendors v ON v.email = 'vendor.books@storepreschoolapp.in'
SET p.vendor_id = v.id
WHERE p.category_id IN (2, 3) AND p.vendor_id IS NULL;

UPDATE products p
JOIN vendors v ON v.email = 'vendor.kits@storepreschoolapp.in'
SET p.vendor_id = v.id
WHERE p.category_id IN (4, 5, 6, 8) AND p.vendor_id IS NULL;

-- Pune keeps the shelf stock. Kochi gets 2 pieces when a product has at least 4.
INSERT INTO store_stock (dark_store_id, product_id, quantity)
SELECT s.id, p.id, p.stock - IF(p.stock >= 4, 2, 0)
FROM products p
JOIN dark_stores s ON s.code = 'PUNE-CAMP'
WHERE NOT EXISTS (
    SELECT 1 FROM store_stock ss WHERE ss.dark_store_id = s.id AND ss.product_id = p.id
);

INSERT INTO store_stock (dark_store_id, product_id, quantity)
SELECT s.id, p.id, IF(p.stock >= 4, 2, 0)
FROM products p
JOIN dark_stores s ON s.code = 'KOCHI-LAKE'
WHERE NOT EXISTS (
    SELECT 1 FROM store_stock ss WHERE ss.dark_store_id = s.id AND ss.product_id = p.id
);

UPDATE orders o
JOIN dark_store_pins p ON p.pincode = o.pincode
SET o.dark_store_id = p.dark_store_id
WHERE o.dark_store_id IS NULL;

-- Vendor@123
INSERT INTO users (name, email, phone, password_hash, role, school_name, city, vendor_id, dark_store_id)
SELECT 'Little Hands Vendor', 'vendor@storepreschoolapp.in', '9811111111',
       '$2y$10$S6JQvFWyw21T1IuztfX3wOwxv9qFftZLUSsilPCcYRDd7jvpbCGIO',
       'vendor', 'Vendor', 'Pune', v.id, NULL
FROM vendors v
WHERE v.email = 'vendor.toys@storepreschoolapp.in'
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'vendor@storepreschoolapp.in');

-- Store@123, Pune Camp
INSERT INTO users (name, email, phone, password_hash, role, school_name, city, vendor_id, dark_store_id)
SELECT 'Pune Dark Store', 'store@storepreschoolapp.in', '9844444444',
       '$2y$10$xhJYuvZ3qlsMxSajwkTDzOixr8w/Sy75csVbcCPbX7Amy2oQ5c0GO',
       'store', 'Dark store', 'Pune', NULL, s.id
FROM dark_stores s
WHERE s.code = 'PUNE-CAMP'
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'store@storepreschoolapp.in');

-- Store@123, Kochi Lake
INSERT INTO users (name, email, phone, password_hash, role, school_name, city, vendor_id, dark_store_id)
SELECT 'Kochi Dark Store', 'store.kochi@storepreschoolapp.in', '9855555555',
       '$2y$10$xhJYuvZ3qlsMxSajwkTDzOixr8w/Sy75csVbcCPbX7Amy2oQ5c0GO',
       'store', 'Dark store', 'Kochi', NULL, s.id
FROM dark_stores s
WHERE s.code = 'KOCHI-LAKE'
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'store.kochi@storepreschoolapp.in');
