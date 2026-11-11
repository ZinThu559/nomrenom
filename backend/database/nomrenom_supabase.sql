-- ==============================================================================
-- NomReNom PostgreSQL Schema & Seed Data for Supabase
-- Paste this entire file into Supabase -> SQL Editor -> Click 'Run'
-- ==============================================================================

-- 1. DROP EXISTING TABLES
DROP TABLE IF EXISTS feedback CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS reservation_items CASCADE;
DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS pickup_slots CASCADE;
DROP TABLE IF EXISTS food_boxes CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS bakeries CASCADE;
DROP TABLE IF EXISTS guest_customers CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS pilot_surveys CASCADE;

-- 2. CREATE TABLES

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    is_email_verified INT DEFAULT 1,
    is_phone_verified INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(60),
    sort_order INT DEFAULT 0,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bakeries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    description TEXT,
    address VARCHAR(255) NOT NULL,
    district VARCHAR(100) NOT NULL,
    city VARCHAR(100) DEFAULT 'Ho Chi Minh City',
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(191) NOT NULL,
    opening_time TIME DEFAULT '07:00:00',
    closing_time TIME DEFAULT '22:00:00',
    logo_url VARCHAR(255),
    image_url VARCHAR(255),
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE food_boxes (
    id SERIAL PRIMARY KEY,
    bakery_id INT NOT NULL REFERENCES bakeries(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    box_type VARCHAR(50) DEFAULT 'snack_box',
    condition_grade VARCHAR(50) DEFAULT 'dented_box',
    price NUMERIC(12,2) NOT NULL,
    original_value NUMERIC(12,2) NOT NULL,
    discount_percent INT DEFAULT 50,
    expiry_date DATE,
    description TEXT,
    contents_sample TEXT,
    image_url VARCHAR(255),
    available_qty INT DEFAULT 10,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE guest_customers (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_email_verified INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pickup_slots (
    id SERIAL PRIMARY KEY,
    bakery_id INT NOT NULL REFERENCES bakeries(id) ON DELETE CASCADE,
    pickup_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_capacity INT DEFAULT 20,
    reserved_count INT DEFAULT 0,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    reservation_code VARCHAR(32) NOT NULL UNIQUE,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    guest_id INT REFERENCES guest_customers(id) ON DELETE SET NULL,
    bakery_id INT NOT NULL REFERENCES bakeries(id),
    pickup_slot_id INT REFERENCES pickup_slots(id) ON DELETE SET NULL,
    total_amount NUMERIC(12,2) NOT NULL,
    reservation_status VARCHAR(30) DEFAULT 'Pending',
    payment_method VARCHAR(30) DEFAULT 'Online',
    payment_status VARCHAR(30) DEFAULT 'Pending',
    special_instructions VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservation_items (
    id SERIAL PRIMARY KEY,
    reservation_id INT NOT NULL REFERENCES reservations(id) ON DELETE CASCADE,
    food_box_id INT NOT NULL REFERENCES food_boxes(id),
    box_name VARCHAR(180) NOT NULL,
    unit_price NUMERIC(12,2) NOT NULL,
    quantity INT DEFAULT 1,
    subtotal NUMERIC(12,2) NOT NULL
);

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    reservation_id INT NOT NULL REFERENCES reservations(id) ON DELETE CASCADE,
    payment_method VARCHAR(30) DEFAULT 'Online',
    gateway_provider VARCHAR(30) DEFAULT 'vietqr',
    transaction_reference VARCHAR(100),
    amount NUMERIC(12,2) NOT NULL,
    payment_status VARCHAR(30) DEFAULT 'Paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE feedback (
    id SERIAL PRIMARY KEY,
    reservation_id INT REFERENCES reservations(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    food_box_id INT REFERENCES food_boxes(id) ON DELETE SET NULL,
    customer_name VARCHAR(120) NOT NULL,
    rating INT DEFAULT 5,
    comment TEXT NOT NULL,
    is_public INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pilot_surveys (
    id SERIAL PRIMARY KEY,
    student_name VARCHAR(120) NOT NULL,
    student_email VARCHAR(191) NOT NULL,
    student_role VARCHAR(80) DEFAULT 'Undergraduate Student',
    snack_box_price_rating VARCHAR(50) DEFAULT 'Great Value',
    meal_box_price_rating VARCHAR(50) DEFAULT 'Fair Price',
    pickup_convenience VARCHAR(50) DEFAULT 'Very Convenient',
    would_recommend INT DEFAULT 1,
    feedback_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. SEED CATEGORIES
INSERT INTO categories (id, name, slug, description, icon, sort_order) VALUES
(1, 'Biscuits & Cookies', 'biscuits', 'Crispy biscuits, cookies and sandwich wafers', 'cookie', 1),
(2, 'Chocolates & Sweets', 'chocolates', 'Rich milk chocolates, truffles and candy bars', 'box2-heart', 2),
(3, 'Chips & Savory Snacks', 'chips', 'Savory potato crisps and crackers', 'fire', 3),
(4, 'Ramyun & Instant Noodles', 'ramyun', 'Korean & Japanese spicy ramyun noodle snacks', 'cup-hot', 4),
(5, 'Packaged Breads & Buns', 'breads', 'Marshmallow cakes, choco pies and brioche rolls', 'bag', 5),
(6, 'Imported Asian Snacks', 'asian-snacks', 'Popular regional snack imports', 'stars', 6);

-- 4. SEED PARTNER STORES
INSERT INTO bakeries (id, name, slug, description, address, district, city, phone, email, opening_time, closing_time, logo_url, image_url) VALUES
(1, 'Circle K – University Campus Hub', 'circle-k-campus', 'Campus partner store with counter inspection table for near-expiry deals.', 'Khu pho 6, Linh Trung Ward, Thu Duc City (Opposite Dorm A)', 'Thu Duc City', 'Ho Chi Minh City', '028 3724 5566', 'circlek.campus@nomrenom.vn', '07:00:00', '23:00:00', 'assets/img/bakeries/bakery-logo-1.svg', 'assets/img/bakeries/bakery-store-1.jpg'),
(2, 'FamilyMart – Student District 1', 'familymart-d1', 'Convenient city center pickup counter with VietQR fast check-in.', '88 Nguyen Thi Minh Khai, Ben Nghe Ward, District 1', 'District 1', 'Ho Chi Minh City', '028 3822 1199', 'familymart.d1@nomrenom.vn', '06:30:00', '23:30:00', 'assets/img/bakeries/bakery-logo-2.svg', 'assets/img/bakeries/bakery-store-2.jpg'),
(3, 'Tokyo Snack Corner – Binh Thanh Hub', 'tokyo-snack-binh-thanh', 'Imported treats and temperature-controlled chocolate recovery counter.', '420 Dien Bien Phu, Ward 25, Binh Thanh District', 'Binh Thanh District', 'Ho Chi Minh City', '028 3512 8877', 'tokyosnack.bt@nomrenom.vn', '08:00:00', '22:00:00', 'assets/img/bakeries/bakery-logo-3.svg', 'assets/img/bakeries/bakery-store-3.jpg');

-- 5. SEED 10 RESCUED SNACK PRODUCTS
INSERT INTO food_boxes (id, bakery_id, category_id, name, slug, condition_grade, price, original_value, discount_percent, expiry_date, available_qty, image_url, description) VALUES
(1, 1, 1, 'Glico Pocky Strawberry Biscuit Sticks (Family Pack)', 'pocky-strawberry-family', 'dented_box', 14000, 38000, 63, '2026-11-27', 6, 'assets/img/snacks/pocky_strawberry_dented.jpg', 'Crispy biscuit sticks coated in sweet strawberry cream. Outer carton box has a squished top-right corner from pallet transport.'),
(2, 2, 1, 'Oreo Vanilla Creme Cookies (Pack of 3)', 'oreo-vanilla-creme-pack3', 'crumbled_biscuits', 12000, 32000, 63, '2026-12-04', 12, 'assets/img/snacks/oreo_crumbled_cookies.jpg', 'Classic chocolate sandwich cookies with smooth vanilla creme. Several cookies were cracked into pieces during shipping.'),
(3, 3, 2, 'Nestle KitKat Chunky Milk Chocolate (Box of 4)', 'kitkat-chunky-milk-chocolate', 'melted_reset', 18000, 48000, 63, '2026-12-08', 6, 'assets/img/snacks/kitkat_melted_set.jpg', 'Crispy wafer finger covered in thick milk chocolate. Melted and re-solidified in air-conditioned storage.'),
(4, 1, 3, 'Lay''s Magic Masala Potato Chips (Buddy Pack)', 'lays-magic-masala-chips', 'dented_box', 15000, 35000, 57, '2026-11-23', 5, 'assets/img/snacks/lays_magic_masala.jpg', 'Crunchy potato crisps with savory seasoning. Outer foil bag has cosmetic creases on the upper seam.'),
(5, 3, 2, 'Meiji Meltykiss Creamy Milk Chocolate (Box of 5)', 'meiji-meltykiss-chocolate', 'melted_reset', 25000, 65000, 62, '2026-11-30', 8, 'assets/img/snacks/meltykiss_box.jpg', 'Premium Japanese cocoa-dusted melt-in-mouth truffles. Misshapen cubes from temperature fluctuation.'),
(6, 2, 5, 'Lotte Choco Pie Original Marshmallow (Rich Pack)', 'lotte-choco-pie-original', 'dented_box', 19000, 45000, 58, '2026-11-25', 7, 'assets/img/snacks/choco_pie_dented.jpg', 'Two soft cake layers filled with fluffy marshmallow, dipped in chocolate. Exterior carton has squished side.'),
(7, 1, 6, 'Lotte Pepero Almond Crunch (Special Box)', 'lotte-pepero-almond-crunch', 'near_expiry', 16000, 42000, 62, '2026-11-19', 9, 'assets/img/snacks/pepero_almond_dented.jpg', 'Crunchy biscuit sticks dipped in rich chocolate and crushed roasted almonds. Approaching best-before date.'),
(8, 3, 1, 'Hokkaido Cream Cheese Wafer Sticks (Party Box)', 'hokkaido-cream-cheese-wafers', 'crumbled_biscuits', 22000, 62000, 65, '2026-12-01', 4, 'assets/img/snacks/hokkaido_cheese_wafers.jpg', 'Fragrant wafer sticks filled with rich Hokkaido cheese cream. Ends of wafer sticks cracked in transit.'),
(9, 2, 3, 'TaoKaeNoi Crispy Seaweed Rolls (Bulgogi BBQ, 6s)', 'taokaenoi-crispy-seaweed-bulgogi', 'near_expiry', 18000, 50000, 64, '2026-11-18', 11, 'assets/img/snacks/seaweed_bulgogi_pack.jpg', 'Super crispy roasted seaweed sheets seasoned with savory sweet Korean bulgogi glaze. 8 days before date.'),
(10, 1, 3, 'Nongshim Crispy Ramen Noodle Snack (Family Size)', 'nongshim-crispy-ramen-snack', 'crumbled_biscuits', 11000, 28000, 61, '2026-11-26', 15, 'assets/img/snacks/nongshim_crispy_noodles.jpg', 'Crunchy crushed dry ramen noodle snack with BBQ flavor powder. Crushed into small bits, ideal for snacking.');

-- 6. SEED ADMINS & DEMO USERS
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
(1, 'Alex Nguyen (Lead Administrator)', 'admin.alex@nomrenom.vn', '0901234561', '$2y$10$EWFB9c6Ib5ZUwMgYeLiqgOYKDPNAt/dS9x/Hjg4li1PnSk499o8hK', 'admin'),
(2, 'Linh Tran (Store Inventory Admin)', 'admin.linh@nomrenom.vn', '0901234562', '$2y$10$UqkHVc7dWGzQp8C5fFzHfevZZO.WIQfwEXSLnMEgHsUTdWR0TF1oS', 'admin'),
(3, 'Minh Vo (Quality & Refunds Admin)', 'admin.minh@nomrenom.vn', '0901234563', '$2y$10$ryiJiVX9s5MLX/GrJp8q0.k5gTf4G7sz.YFtSzDa/kuwj.MLuSo6u', 'admin'),
(4, 'NomReNom Superadmin', 'admin@nomrenom.vn', '0901234567', '$2y$10$nfpzuSZyUVtZk6SH6jXYdOpcW.s0Z1L6Y6XEC2icsGwflSd7pSTPm', 'admin'),
(5, 'Alex Nguyen (Student Account)', 'student@university.edu.vn', '0912345678', '$2y$10$CXW.gac6zn4nWEJbL2f2COyrA13aPwJJDhISqxL0jiAiuqMyBfMyy', 'customer');

-- 7. SEED PICKUP SLOTS (Today & Tomorrow)
INSERT INTO pickup_slots (bakery_id, pickup_date, start_time, end_time, max_capacity, reserved_count) VALUES
(1, CURRENT_DATE, '17:00:00', '19:00:00', 20, 3),
(1, CURRENT_DATE, '19:00:00', '21:00:00', 20, 1),
(2, CURRENT_DATE, '17:30:00', '19:30:00', 25, 4),
(3, CURRENT_DATE, '18:00:00', '20:00:00', 15, 2);

-- Reset Sequences to max ID
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('categories_id_seq', (SELECT MAX(id) FROM categories));
SELECT setval('bakeries_id_seq', (SELECT MAX(id) FROM bakeries));
SELECT setval('food_boxes_id_seq', (SELECT MAX(id) FROM food_boxes));
SELECT setval('pickup_slots_id_seq', (SELECT MAX(id) FROM pickup_slots));
