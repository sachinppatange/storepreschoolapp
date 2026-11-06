-- School logins and the B2B price.
-- Safe to run more than once on u750208840_storepredb.
-- Do not import storepreschool.sql again. It drops the tables.
-- The website also adds this role the first time a page loads.
-- Demo school, created only when the email is new:
--   school@storepreschoolapp.in / School@123

ALTER TABLE users
    MODIFY role ENUM('customer','admin','vendor','store','school') NOT NULL DEFAULT 'customer';

INSERT INTO users (name, email, phone, password_hash, role, school_name, city)
SELECT 'Little Stars Preschool', 'school@storepreschoolapp.in', '9800000001',
       '$2y$10$zfiW1GPqZMp1IqvmF74ExOV3B9yT/OTlnNsmIdWe1OOGep3USGc3C',
       'school', 'Little Stars Preschool', 'Pune'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'school@storepreschoolapp.in'
);
