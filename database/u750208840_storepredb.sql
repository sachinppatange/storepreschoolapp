-- Store Preschool tables for Hostinger database u750208840_storepredb
-- In phpMyAdmin, select u750208840_storepredb, then Import this file.
-- Do not run CREATE DATABASE. Shared hosting cannot create storepreschool.

USE `u750208840_storepredb`;



SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_status_logs;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    school_name VARCHAR(180) NULL,
    address VARCHAR(500) NULL,
    city VARCHAR(80) NULL,
    pincode VARCHAR(10) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    emoji VARCHAR(16) NOT NULL,
    description VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    short_desc VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    emoji VARCHAR(16) NOT NULL DEFAULT '📦',
    image VARCHAR(255) NULL,
    unit_label VARCHAR(60) NOT NULL DEFAULT '1 pc',
    price DECIMAL(10,2) NOT NULL,
    mrp DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    sku VARCHAR(40) NOT NULL,
    is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    is_new TINYINT(1) NOT NULL DEFAULT 0,
    kit_type ENUM('none', 'starter', 'nursery', 'jrkg', 'srkg') NOT NULL DEFAULT 'none',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_slug (slug),
    UNIQUE KEY uniq_sku (sku),
    KEY idx_category (category_id),
    KEY idx_flags (is_bestseller, is_trending, is_new, kit_type),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id, product_id),
    KEY idx_session (session_id, product_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wishlists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_wish (user_id, product_id),
    CONSTRAINT fk_wish_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_wish_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    description VARCHAR(180) NOT NULL,
    discount_type ENUM('percent', 'flat') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    used_count INT NOT NULL DEFAULT 0,
    valid_from DATE NULL,
    valid_to DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL,
    user_id INT UNSIGNED NULL,
    order_type ENUM('retail', 'bulk') NOT NULL DEFAULT 'retail',
    customer_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    school_name VARCHAR(180) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    address VARCHAR(500) NOT NULL,
    city VARCHAR(80) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    notes VARCHAR(500) NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_code VARCHAR(40) NULL,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    payment_method ENUM('razorpay', 'cod') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    razorpay_order_id VARCHAR(64) NULL,
    razorpay_payment_id VARCHAR(64) NULL,
    delivery_status ENUM('placed', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed',
    stock_deducted TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_order_number (order_number),
    KEY idx_user (user_id),
    KEY idx_status (payment_status, delivery_status),
    KEY idx_created (created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(180) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    KEY idx_order (order_id),
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    note VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_order (order_id),
    CONSTRAINT fk_logs_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo passwords: Admin@123 and Parent@123
INSERT INTO users (id, name, email, phone, password_hash, role, school_name, address, city, pincode) VALUES
(1, 'Store Admin', 'admin@storepreschoolapp.in', '9999999999', '$2y$10$xEvaCt.hhjIf/Qfxykm7WOVmn5mzjV0aLdbxd2hRmcYZJGsWuWcHO', 'admin', 'Store Preschool', 'Pune', 'Pune', '411001'),
(2, 'Ananya Sharma', 'parent@example.com', '9876543210', '$2y$10$DLPaGmtOQn8tI.Cp/oIoxexoLURXZQl84p8mHj.GoBJFnDYmC/A9y', 'customer', 'Little Stars Preschool', '12 MG Road, Camp', 'Pune', '411001'),
(3, 'Rahul Mehta', 'rahul@example.com', '9876501234', '$2y$10$DLPaGmtOQn8tI.Cp/oIoxexoLURXZQl84p8mHj.GoBJFnDYmC/A9y', 'customer', 'Green Valley Preschool', '45 FC Road', 'Pune', '411004'),
(4, 'Priya Nair', 'priya@example.com', '9123456780', '$2y$10$DLPaGmtOQn8tI.Cp/oIoxexoLURXZQl84p8mHj.GoBJFnDYmC/A9y', 'customer', 'Sunshine Kids Preschool', '8 Lake View Road', 'Kochi', '682001');

INSERT INTO categories (id, name, slug, emoji, description, sort_order) VALUES
(1, 'Toys', 'toys', '🧸', 'Play that builds motor skills, language and imagination.', 1),
(2, 'Books', 'books', '📚', 'Picture books and first readers for circle time.', 2),
(3, 'Stationery', 'stationery', '✏️', 'Grips, crayons and notebooks sized for little hands.', 3),
(4, 'Art & Craft', 'art-craft', '🎨', 'Washable colours, paper and dough for art class.', 4),
(5, 'Furniture', 'furniture', '🪑', 'Tables, chairs and storage for preschool classrooms.', 5),
(6, 'Educational Materials', 'educational-materials', '🧩', 'Phonics, counting and Montessori learning tools.', 6),
(7, 'Sports', 'sports', '⚽', 'Soft indoor and outdoor play equipment.', 7),
(8, 'School Essentials', 'school-essentials', '🎒', 'Bags, lunch kits and ready class kits.', 8);

INSERT INTO products
(id, category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type) VALUES
(1, 1, 'Wooden Building Blocks Set', 'wooden-building-blocks', '48 smooth wooden blocks for building and early maths.', 'Smooth wooden blocks sized for little hands. Children stack, sort and count while they play, and the pieces pack back into a classroom tub.', '🧱', '48 pieces', 899, 1199, 40, 'TOY-BLOCKS-48', 1, 0, 0, 'none'),
(2, 1, 'Soft Plush Learning Bear', 'soft-plush-learning-bear', 'A huggable bear with letters and numbers.', 'Numbers and letters are stitched onto a soft bear that works well for circle time, comfort corners and take-home reading.', '🧸', '1 bear', 499, 699, 25, 'TOY-BEAR-01', 0, 1, 0, 'none'),
(3, 1, 'Shape Sorter Cube', 'shape-sorter-cube', 'Chunky shapes that drop into the matching holes.', 'A sturdy cube with easy-grip shapes. It builds hand-eye coordination and the language of circles, squares and triangles.', '🔷', '1 cube', 349, 499, 30, 'TOY-SHAPE-01', 0, 0, 1, 'none'),
(4, 1, 'Mini Kitchen Pretend Play Set', 'mini-kitchen-pretend-play', 'A 12-piece kitchen set for role play.', 'Pots, plates and play food for sharing games. The pieces are chunky enough for Nursery and Jr. KG classrooms.', '🍳', '12 pieces', 1299, 1699, 12, 'TOY-KITCHEN-12', 1, 0, 0, 'none'),
(5, 2, 'My First Alphabet Board Book', 'my-first-alphabet-book', 'Thick pages with one letter, one picture, one word.', 'A board book teachers can leave in the reading corner. Each letter has a familiar preschool picture and a short word.', '📚', '1 book', 199, 299, 50, 'BOK-ABC-01', 1, 0, 0, 'none'),
(6, 2, 'Nursery Rhymes Picture Book', 'nursery-rhymes-picture-book', 'Sing-along rhymes with big illustrations.', 'Twinkle Twinkle, Humpty Dumpty and other class favourites, illustrated so children can point along while the class sings.', '🎵', '1 book', 249, 349, 40, 'BOK-RHYME-01', 0, 1, 0, 'none'),
(7, 2, 'Numbers 1-20 Story Book', 'numbers-1-20-story-book', 'Count animals, fruits and school buses.', 'A short counting story from 1 to 20. Useful for morning circle and for sending home with new counters.', '🔢', '1 book', 179, 249, 35, 'BOK-NUM-20', 0, 0, 0, 'none'),
(8, 2, 'Moral Stories for Preschool', 'moral-stories-for-preschool', 'Short kindness stories for one sitting.', 'Six very short stories about sharing, waiting and helping. Each one finishes inside a single circle-time slot.', '📖', '1 book', 299, 399, 28, 'BOK-MORAL-01', 0, 0, 1, 'none'),
(9, 3, 'Jumbo Crayon Box', 'jumbo-crayon-box', '24 chunky crayons that are hard to break.', 'Thick, bright crayons with a paper wrap small fists can hold. The colours are strong enough for class displays.', '🖍️', '24 colours', 149, 199, 80, 'STA-CRAY-24', 1, 0, 0, 'none'),
(10, 3, 'Triangular Grip Pencils', 'triangular-grip-pencils', '12 fat pencils that guide the writing grip.', 'The triangular barrel helps children place their fingers correctly when tracing patterns and first letters.', '✏️', '12 pack', 99, 149, 100, 'STA-PENCIL-12', 0, 0, 0, 'none'),
(11, 3, 'Preschool Notebook Pack', 'preschool-notebook-pack', 'Four large-square books for patterns and letters.', 'Thick paper and big squares for tracing, patterns and first words. A simple pack to hand out on day one.', '📓', '4 books', 189, 249, 60, 'STA-NOTE-04', 0, 1, 0, 'none'),
(12, 3, 'Safety Scissors and Glue Set', 'safety-scissors-glue-set', 'Round-tip scissors with a washable glue stick.', 'Spring-assist scissors with a round tip, plus a glue stick that washes off tables and hands after craft hour.', '✂️', '1 set', 129, 179, 45, 'STA-CUT-01', 0, 0, 0, 'none'),
(13, 4, 'Finger Paint Set', 'finger-paint-set', 'Six washable, non-toxic colours.', 'Paints made for hands. They dry with a clear colour and wash off skin with soap and water.', '🎨', '6 colours', 349, 449, 30, 'ART-PAINT-06', 1, 0, 0, 'none'),
(14, 4, 'Craft Paper Bundle', 'craft-paper-bundle', '50 bright A4 sheets for cutting and collage.', 'A mix of colours for folding, tearing and collage. The paper is thick enough for glue without soaking through.', '📄', '50 sheets', 199, 279, 40, 'ART-PAPER-50', 0, 0, 0, 'none'),
(15, 4, 'Play Dough 8 Colours', 'play-dough-8-colours', 'Soft dough tubs for rolling and cutting.', 'Eight pots of soft, non-toxic dough. Lids close tightly so the set can live on an open shelf.', '🍥', '8 tubs', 279, 399, 35, 'ART-DOUGH-08', 0, 1, 0, 'none'),
(16, 4, 'Sticker Activity Pack', 'sticker-activity-pack', 'Reward stickers and simple scene sheets.', 'Sheets for fine-motor practice and a sheet of stars teachers use as rewards. The pack is classroom sized.', '🌟', '12 sheets', 159, 229, 50, 'ART-STICK-12', 0, 0, 1, 'none'),
(17, 5, 'Child Study Table', 'child-study-table', 'Rounded corners and an easy-clean top.', 'A low table for Nursery through Sr. KG. Corners are rounded and the top wipes clean after snack and art.', '🪑', '1 table', 3499, 4299, 8, 'FUR-TABLE-01', 1, 0, 0, 'none'),
(18, 5, 'Stackable Preschool Chair', 'stackable-preschool-chair', 'A light chair children can carry themselves.', 'Stackable chair with a stable base. Light enough for a child to move at tidy-up time.', '💺', '1 chair', 1299, 1699, 15, 'FUR-CHAIR-01', 0, 0, 0, 'none'),
(19, 5, 'Book and Toy Storage Rack', 'book-toy-storage-rack', 'Three open shelves for independent tidy-up.', 'Front-facing shelves so children can see the books and toys and put them back without a teacher lifting the bin.', '🗄️', '3 shelves', 2199, 2799, 10, 'FUR-RACK-03', 0, 1, 0, 'none'),
(20, 5, 'Roll-up Nap Mat', 'roll-up-nap-mat', 'A soft rest mat with a carry strap.', 'A thin, washable mat for rest time. It rolls up and has a strap so it can hang by the cubby.', '🛏️', '1 mat', 899, 1199, 20, 'FUR-MAT-01', 0, 0, 0, 'none'),
(21, 6, 'Alphabet Flash Cards', 'alphabet-flash-cards', 'Uppercase, lowercase and a picture on every card.', '52 cards for letter recognition games. The corners are rounded and the set includes a simple storage band.', '🃏', '52 cards', 249, 349, 45, 'EDU-ABC-52', 1, 0, 0, 'none'),
(22, 6, 'Counting Beads Abacus', 'counting-beads-abacus', 'Ten rows of beads for counting and patterns.', 'A colourful abacus for counting to 100, simple adding and copy-the-pattern games.', '🧮', '1 abacus', 399, 549, 30, 'EDU-ABACUS-01', 0, 0, 0, 'none'),
(23, 6, 'Phonics Sound Box', 'phonics-sound-box', 'Picture cards grouped by starting sounds.', 'Cards sorted by first sound so Jr. KG and Sr. KG can play “I spy the sound” in small groups.', '🔊', '1 box', 599, 799, 18, 'EDU-PHONICS-01', 0, 1, 0, 'none'),
(24, 6, 'Montessori Shape Puzzle', 'montessori-shape-puzzle', 'Knobbed wooden shapes with one correct place.', 'Each piece has a knob and fits one space. A quiet shelf activity for the youngest class.', '🧩', '1 puzzle', 449, 599, 22, 'EDU-PUZZLE-01', 0, 0, 1, 'none'),
(25, 7, 'Soft Indoor Ball Set', 'soft-indoor-ball-set', 'Three foam balls safe for the classroom.', 'Light foam balls for indoor throwing, rolling and simple team games without hurting a classmate.', '⚽', '3 balls', 399, 549, 25, 'SPT-BALL-03', 0, 0, 0, 'none'),
(26, 7, 'Kids Skipping Rope', 'kids-skipping-rope', 'Adjustable rope with easy-grip handles.', 'A light rope with short handles. The length can be knotted so it fits Nursery through Sr. KG.', '🪢', '1 rope', 149, 199, 40, 'SPT-ROPE-01', 0, 0, 0, 'none'),
(27, 7, 'Foam Balance Beam', 'foam-balance-beam', 'Six interlocking pieces for balance play.', 'A low foam beam children walk, turn and step over. The pieces join and pull apart for storage.', '🤸', '6 pieces', 799, 999, 12, 'SPT-BEAM-06', 1, 0, 0, 'none'),
(28, 7, 'Cone and Ring Toss Game', 'cone-ring-toss', 'Colour cones and rings for aiming games.', 'A simple outdoor set for aiming, colours and turn-taking. Cones also work as course markers.', '🎯', '1 set', 349, 449, 20, 'SPT-TOSS-01', 0, 1, 0, 'none'),
(29, 8, 'Preschool Backpack', 'preschool-backpack', 'A small bag with a chest strap and bottle pocket.', 'Sized for preschoolers so it does not slip off the shoulders. One main compartment and a side pocket for a bottle.', '🎒', '1 bag', 799, 1099, 30, 'ESS-BAG-01', 1, 0, 0, 'none'),
(30, 8, 'Lunch Box and Bottle Combo', 'lunch-box-bottle-combo', 'A two-compartment box and a leak-resistant bottle.', 'The box holds a snack and lunch. The bottle opens with a button and seals for the school bag.', '🍱', '1 set', 599, 799, 28, 'ESS-LUNCH-01', 0, 1, 0, 'none'),
(31, 8, 'Name Label Sticker Pack', 'name-label-stickers', '40 waterproof labels for bags and bottles.', 'Write the child name once and stick the labels on bottles, bags and pencil boxes. They resist hand washing.', '🏷️', '40 labels', 99, 149, 100, 'ESS-LABEL-40', 0, 0, 0, 'none'),
(32, 8, 'Raincoat and Shoe Bag Set', 'raincoat-shoe-bag', 'A light raincoat with a matching shoe bag.', 'A simple monsoon set for the school commute. The shoe bag also carries a spare pair of indoor shoes.', '☔', '1 set', 699, 899, 15, 'ESS-RAIN-01', 0, 0, 1, 'none'),
(33, 8, 'Preschool Starter Kit', 'preschool-starter-kit', 'Bag, crayons, notebook, alphabet book and flash cards.', 'A day-one kit for a new preschooler: small backpack, jumbo crayons, one notebook, the alphabet board book and flash cards.', '🎁', '1 kit', 2499, 3499, 20, 'KIT-START-01', 1, 1, 0, 'starter'),
(34, 8, 'Nursery Class Kit', 'nursery-class-kit', 'Board book, crayons, dough and a shape sorter.', 'Chosen for Nursery: a board book, jumbo crayons, one play-dough set and a shape sorter for the first term.', '🌱', '1 kit', 1899, 2499, 18, 'KIT-NUR-01', 0, 0, 0, 'nursery'),
(35, 8, 'Jr. KG Class Kit', 'jr-kg-class-kit', 'Pencils, notebooks, phonics cards and a rhyme book.', 'A Jr. KG kit with grip pencils, notebooks, phonics picture cards and the nursery rhyme book.', '🌿', '1 kit', 2199, 2899, 18, 'KIT-JR-01', 0, 0, 0, 'jrkg'),
(36, 8, 'Sr. KG Class Kit', 'sr-kg-class-kit', 'Writing tools, number book, flash cards and craft paper.', 'For the year before Grade 1: grip pencils, notebooks, the numbers book, alphabet cards and a craft paper bundle.', '🌳', '1 kit', 2499, 3299, 16, 'KIT-SR-01', 0, 0, 1, 'srkg');

INSERT INTO coupons (code, description, discount_type, discount_value, min_order, max_discount, usage_limit, used_count, valid_from, valid_to) VALUES
('WELCOME10', '10% off your first school order', 'percent', 10, 499, 300, 1000, 0, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY)),
('SCHOOL15', '15% off class kits and larger orders', 'percent', 15, 1999, 1000, 1000, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY)),
('FLAT100', 'Rs 100 off stationery and essentials', 'flat', 100, 799, NULL, 1000, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY));

INSERT INTO wishlists (user_id, product_id) VALUES
(2, 2),
(2, 33);

INSERT INTO orders
(id, order_number, user_id, order_type, customer_name, email, school_name, mobile, address, city, pincode, notes, subtotal, discount, coupon_code, delivery_fee, total, payment_method, payment_status, razorpay_order_id, razorpay_payment_id, delivery_status, stock_deducted, created_at) VALUES
(1, 'SP260001', 2, 'retail', 'Ananya Sharma', 'parent@example.com', 'Little Stars Preschool', '9876543210', '12 MG Road, Camp', 'Pune', '411001', 'Please deliver to the school office.', 1297.00, 0.00, NULL, 0.00, 1297.00, 'razorpay', 'paid', 'order_seed_1', 'pay_seed_1', 'delivered', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2, 'SP260002', 2, 'retail', 'Ananya Sharma', 'parent@example.com', 'Little Stars Preschool', '9876543210', '12 MG Road, Camp', 'Pune', '411001', NULL, 2499.00, 374.85, 'SCHOOL15', 0.00, 2124.15, 'razorpay', 'paid', 'order_seed_2', 'pay_seed_2', 'delivered', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 'SP260003', 2, 'retail', 'Ananya Sharma', 'parent@example.com', 'Little Stars Preschool', '9876543210', '12 MG Road, Camp', 'Pune', '411001', NULL, 596.00, 0.00, NULL, 0.00, 596.00, 'cod', 'paid', NULL, NULL, 'delivered', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'SP260004', 2, 'retail', 'Ananya Sharma', 'parent@example.com', 'Little Stars Preschool', '9876543210', '12 MG Road, Camp', 'Pune', '411001', 'Leave bags at reception.', 1598.00, 100.00, 'FLAT100', 0.00, 1498.00, 'razorpay', 'paid', 'order_seed_4', 'pay_seed_4', 'shipped', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 'SP260005', 2, 'retail', 'Ananya Sharma', 'parent@example.com', 'Little Stars Preschool', '9876543210', '12 MG Road, Camp', 'Pune', '411001', 'Payment not completed.', 279.00, 0.00, NULL, 40.00, 319.00, 'razorpay', 'pending', NULL, NULL, 'placed', 0, NOW()),
(6, 'SP260006', 3, 'retail', 'Rahul Mehta', 'rahul@example.com', 'Green Valley Preschool', '9876501234', '45 FC Road', 'Pune', '411004', 'Classroom 2.', 3499.00, 0.00, NULL, 0.00, 3499.00, 'razorpay', 'paid', 'order_seed_6', 'pay_seed_6', 'confirmed', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 'SP260007', 4, 'bulk', 'Priya Nair', 'priya@example.com', 'Sunshine Kids Preschool', '9123456780', '8 Lake View Road', 'Kochi', '682001', 'Class strength - Nursery: 24, Jr. KG: 0, Sr. KG: 0', 3798.00, 569.70, 'SCHOOL15', 0.00, 3228.30, 'cod', 'paid', NULL, NULL, 'packed', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 'SP260008', 3, 'retail', 'Rahul Mehta', 'rahul@example.com', 'Green Valley Preschool', '9876501234', '45 FC Road', 'Pune', '411004', NULL, 198.00, 0.00, NULL, 40.00, 238.00, 'cod', 'paid', NULL, NULL, 'delivered', 1, NOW());

INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES
(1, 1, 'Wooden Building Blocks Set', 899.00, 1, 899.00),
(1, 5, 'My First Alphabet Board Book', 199.00, 2, 398.00),
(2, 33, 'Preschool Starter Kit', 2499.00, 1, 2499.00),
(3, 9, 'Jumbo Crayon Box', 149.00, 4, 596.00),
(4, 29, 'Preschool Backpack', 799.00, 2, 1598.00),
(5, 15, 'Play Dough 8 Colours', 279.00, 1, 279.00),
(6, 17, 'Child Study Table', 3499.00, 1, 3499.00),
(7, 34, 'Nursery Class Kit', 1899.00, 2, 3798.00),
(8, 10, 'Triangular Grip Pencils', 99.00, 2, 198.00);

INSERT INTO order_status_logs (order_id, status, note, created_at) VALUES
(1, 'placed', 'Order placed', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 'confirmed', 'Payment received', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 'packed', 'Packed at the store', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'shipped', 'Handed to the delivery partner', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'out_for_delivery', 'Out for school delivery', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'delivered', 'Delivered to the school office', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'placed', 'Order placed', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'delivered', 'Delivered to the school office', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'placed', 'Cash on delivery order placed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'delivered', 'Delivered and payment collected', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'placed', 'Order placed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'packed', 'Packed at the store', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'shipped', 'On the way to school', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5, 'placed', 'Waiting for online payment', NOW()),
(6, 'placed', 'Order placed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 'confirmed', 'Payment received', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 'placed', 'Bulk order placed', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(7, 'confirmed', 'School order confirmed', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(7, 'packed', 'Kits packed for dispatch', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(8, 'placed', 'Cash on delivery order placed', NOW()),
(8, 'delivered', 'Delivered and payment collected', NOW());
