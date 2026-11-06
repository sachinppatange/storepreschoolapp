-- School referral links and balances.
-- Run once. If a column already exists, skip this file.
-- The website adds the same columns the first time a page loads.
-- Do not import storepreschool.sql again. It drops the tables.
-- The website also adds these columns the first time a page loads.
-- A parent order through /index.php?ref=CODE adds 5% of the product total
-- to that school's balance. The balance is taken off the school's next order.
-- A cancelled, failed, or refunded order reverses the amount.

ALTER TABLE users
    ADD COLUMN referral_code VARCHAR(16) NULL,
    ADD COLUMN referral_balance DECIMAL(10,2) NOT NULL DEFAULT 0;

ALTER TABLE users
    ADD UNIQUE KEY uniq_referral_code (referral_code);

ALTER TABLE orders
    ADD COLUMN referral_school_id INT UNSIGNED NULL,
    ADD COLUMN referral_earn DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN referral_spent DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN referral_void TINYINT(1) NOT NULL DEFAULT 0;
