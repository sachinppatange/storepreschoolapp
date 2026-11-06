-- Store Preschool migration 002: print on demand
-- Select the existing database first, then import this file.
-- Hostinger: u750208840_storepredb
-- Local XAMPP: storepreschool
-- Does not delete products, customers, orders, vendors, or dark stores.
-- Safe to run again.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS print_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    order_id INT UNSIGNED NULL,
    kind ENUM('document', 'photo') NOT NULL,
    file_name VARCHAR(80) NOT NULL,
    original_name VARCHAR(180) NOT NULL,
    pages INT UNSIGNED NOT NULL,
    copies INT UNSIGNED NOT NULL,
    color TINYINT(1) NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_print_session (session_id, order_id),
    KEY idx_print_user (user_id, order_id),
    KEY idx_print_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
