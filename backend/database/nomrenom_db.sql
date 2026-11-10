-- NomReNom Database Schema & Initial Demonstration Seed Data
-- Platform: MySQL 8.0+ / MariaDB 10.4+ (InnoDB, utf8mb4)

CREATE DATABASE IF NOT EXISTS `nomrenom_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nomrenom_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `admin_activity_logs`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `phone_verification_tokens`;
DROP TABLE IF EXISTS `email_verification_tokens`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `reservation_items`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `pickup_slots`;
DROP TABLE IF EXISTS `food_boxes`;
DROP TABLE IF EXISTS `bakeries`;
DROP TABLE IF EXISTS `guest_customers`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS (Registered customers & Admins)
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `is_phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `remember_token` VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. GUEST CUSTOMERS (Unregistered guest reservations)
CREATE TABLE `guest_customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `is_phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_guest_email` (`email`),
    INDEX `idx_guest_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BAKERIES (Partner Bakeries - Sample Demonstration Data)
CREATE TABLE `bakeries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(160) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `address` VARCHAR(255) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) NOT NULL DEFAULT 'Ho Chi Minh City',
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `opening_time` TIME NOT NULL DEFAULT '07:00:00',
    `closing_time` TIME NOT NULL DEFAULT '21:30:00',
    `logo_url` VARCHAR(255) NULL,
    `image_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bakeries_active` (`is_active`),
    INDEX `idx_bakeries_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. FOOD BOXES (Surplus Food Products: Snack Box 40k, Meal Box 70k)
CREATE TABLE `food_boxes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bakery_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(190) NOT NULL UNIQUE,
    `box_type` ENUM('snack_box', 'meal_box') NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `original_value` DECIMAL(12,2) NOT NULL,
    `description` TEXT NOT NULL,
    `contents_sample` TEXT NULL,
    `image_url` VARCHAR(255) NULL,
    `available_qty` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`bakery_id`) REFERENCES `bakeries`(`id`) ON DELETE CASCADE,
    INDEX `idx_food_boxes_type` (`box_type`),
    INDEX `idx_food_boxes_active` (`is_active`),
    INDEX `idx_food_boxes_bakery` (`bakery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PICKUP SLOTS (Designated collection windows per bakery)
CREATE TABLE `pickup_slots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bakery_id` INT UNSIGNED NOT NULL,
    `pickup_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `max_capacity` INT UNSIGNED NOT NULL DEFAULT 20,
    `reserved_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bakery_id`) REFERENCES `bakeries`(`id`) ON DELETE CASCADE,
    INDEX `idx_slots_lookup` (`bakery_id`, `pickup_date`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. RESERVATIONS (Core Order Transactions)
CREATE TABLE `reservations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reservation_code` VARCHAR(32) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NULL,
    `guest_id` INT UNSIGNED NULL,
    `bakery_id` INT UNSIGNED NOT NULL,
    `pickup_slot_id` INT UNSIGNED NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL,
    `reservation_status` ENUM('Pending', 'Confirmed', 'Collected', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `payment_method` ENUM('Online', 'Pickup') NOT NULL,
    `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded', 'Pay at Pickup') NOT NULL DEFAULT 'Pending',
    `special_instructions` VARCHAR(255) NULL,
    `collected_at` DATETIME NULL,
    `cancelled_at` DATETIME NULL,
    `cancellation_reason` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`guest_id`) REFERENCES `guest_customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`bakery_id`) REFERENCES `bakeries`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`pickup_slot_id`) REFERENCES `pickup_slots`(`id`) ON DELETE RESTRICT,
    INDEX `idx_res_code` (`reservation_code`),
    INDEX `idx_res_status` (`reservation_status`),
    INDEX `idx_res_user` (`user_id`),
    INDEX `idx_res_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. RESERVATION ITEMS (Order line items)
CREATE TABLE `reservation_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT UNSIGNED NOT NULL,
    `food_box_id` INT UNSIGNED NOT NULL,
    `box_name` VARCHAR(180) NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`food_box_id`) REFERENCES `food_boxes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PAYMENTS (Gateway and In-Person Payment Records)
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT UNSIGNED NOT NULL,
    `payment_method` ENUM('Online', 'Pickup') NOT NULL,
    `gateway_provider` ENUM('vnpay', 'momo', 'pickup') NOT NULL,
    `transaction_reference` VARCHAR(100) NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded', 'Pay at Pickup') NOT NULL DEFAULT 'Pending',
    `gateway_response` TEXT NULL,
    `payment_date` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
    INDEX `idx_payments_ref` (`transaction_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. EMAIL VERIFICATION TOKENS
CREATE TABLE `email_verification_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `guest_id` INT UNSIGNED NULL,
    `email` VARCHAR(191) NOT NULL,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`guest_id`) REFERENCES `guest_customers`(`id`) ON DELETE CASCADE,
    INDEX `idx_email_token` (`token`, `is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PHONE VERIFICATION TOKENS (SMS OTP)
CREATE TABLE `phone_verification_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL,
    `otp_code` VARCHAR(8) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone_otp` (`phone`, `otp_code`, `is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PASSWORD RESET TOKENS
CREATE TABLE `password_reset_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pwd_reset_token` (`token`, `is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. FEEDBACK (Customer reviews and ratings for food boxes)
CREATE TABLE `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `guest_id` INT UNSIGNED NULL,
    `food_box_id` INT UNSIGNED NULL,
    `customer_name` VARCHAR(120) NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT NOT NULL,
    `is_public` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`guest_id`) REFERENCES `guest_customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`food_box_id`) REFERENCES `food_boxes`(`id`) ON DELETE SET NULL,
    INDEX `idx_feedback_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. ADMIN ACTIVITY LOGS (Audit trail for administrative operations)
CREATE TABLE `admin_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(80) NOT NULL,
    `entity_type` VARCHAR(60) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_admin_logs_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. PILOT SURVEYS (Team 4 MVP Success Criterion 5: Price Acceptance & Student Demand Validation)
CREATE TABLE `pilot_surveys` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_name` VARCHAR(120) NOT NULL,
    `student_email` VARCHAR(191) NOT NULL,
    `student_role` VARCHAR(80) NOT NULL DEFAULT 'Undergraduate Student',
    `snack_box_price_rating` ENUM('Great Value', 'Fair Price', 'A Bit High') NOT NULL DEFAULT 'Great Value',
    `meal_box_price_rating` ENUM('Great Value', 'Fair Price', 'A Bit High') NOT NULL DEFAULT 'Fair Price',
    `pickup_convenience` ENUM('Very Convenient', 'Acceptable', 'Inconvenient') NOT NULL DEFAULT 'Very Convenient',
    `would_recommend` TINYINT(1) NOT NULL DEFAULT 1,
    `feedback_notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_survey_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- DEMONSTRATION SEED DATA
-- Clearly labelled demonstration records for initial testing and university evaluation
-- ==============================================================================

-- 1. SEED USERS (Admins & Verified Test Customer)
-- Password for admin.alex: NomAdmin2026!Alex
-- Password for admin.linh: NomAdmin2026!Linh
-- Password for admin.minh: NomAdmin2026!Minh
-- Password for admin (master): Admin@12345
-- Password for student: Student@12345
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `is_email_verified`, `is_phone_verified`) VALUES
(1, 'Alex Nguyen (Lead Administrator)', 'admin.alex@nomrenom.vn', '0901234561', '$2y$10$EWFB9c6Ib5ZUwMgYeLiqgOYKDPNAt/dS9x/Hjg4li1PnSk499o8hK', 'admin', 1, 1),
(2, 'Linh Tran (Store & Inventory Admin)', 'admin.linh@nomrenom.vn', '0901234562', '$2y$10$UqkHVc7dWGzQp8C5fFzHfevZZO.WIQfwEXSLnMEgHsUTdWR0TF1oS', 'admin', 1, 1),
(3, 'Minh Vo (Quality & Refunds Admin)', 'admin.minh@nomrenom.vn', '0901234563', '$2y$10$ryiJiVX9s5MLX/GrJp8q0.k5gTf4G7sz.YFtSzDa/kuwj.MLuSo6u', 'admin', 1, 1),
(4, 'NomReNom System Administrator', 'admin@nomrenom.vn', '0901234567', '$2y$10$nfpzuSZyUVtZk6SH6jXYdOpcW.s0Z1L6Y6XEC2icsGwflSd7pSTPm', 'admin', 1, 1),
(5, 'Alex Nguyen (Student Account)', 'student@university.edu.vn', '0912345678', '$2y$10$CXW.gac6zn4nWEJbL2f2COyrA13aPwJJDhISqxL0jiAiuqMyBfMyy', 'customer', 1, 1);

-- 1B. SEED GUEST CUSTOMERS
INSERT INTO `guest_customers` (`id`, `full_name`, `email`, `phone`, `is_email_verified`) VALUES
(1, 'Jessica Taylor (Guest)', 'guest@university.edu.vn', '0987654321', 1);

-- 2. SEED SAMPLE BAKERIES (Demonstration Partners)
INSERT INTO `bakeries` (`id`, `name`, `slug`, `description`, `address`, `district`, `city`, `phone`, `email`, `opening_time`, `closing_time`, `logo_url`, `image_url`, `is_active`) VALUES
(1, 'Dreamy Bakes Bakery', 'dreamy-bakes', '[Sample Bakery 01] Specializes in French sweet pastries, authentic butter croissants, and organic sourdough bread baked fresh every morning.', '128 Nguyen Thi Minh Khai Street, Ben Thanh Ward', 'District 1', 'Ho Chi Minh City', '02838123456', 'contact@dreamybakes.sample.vn', '07:30:00', '21:30:00', 'assets/img/bakeries/bakery-logo-1.svg', 'assets/img/bakeries/bakery-store-1.jpg', 1),
(2, 'Saigon Artisan Bakery', 'saigon-artisan-bakery', '[Sample Bakery 02] Renowned craft bakery near the University Campus, famous for hearty sandwiches, savory tarts, and brioche pastries.', '45 Vo Van Ngan Street, Linh Chieu Ward', 'Thu Duc City', 'Ho Chi Minh City', '02838765432', 'hello@sgartisan.sample.vn', '07:00:00', '21:00:00', 'assets/img/bakeries/bakery-logo-2.svg', 'assets/img/bakeries/bakery-store-2.jpg', 1),
(3, 'Le Petit Croissant & Pastry', 'le-petit-croissant', '[Sample Bakery 03] Classical European bakery committed to zero food waste by offering delicious afternoon pastry surplus combos for students.', '89 Su Van Hanh Street, Ward 12', 'District 10', 'Ho Chi Minh City', '02839998877', 'orders@lepetit.sample.vn', '08:00:00', '22:00:00', 'assets/img/bakeries/bakery-logo-3.svg', 'assets/img/bakeries/bakery-store-3.jpg', 1);

-- 3. SEED FOOD BOXES (Fixed prices: Snack Box 40,000 VND | Meal Box 70,000 VND)
INSERT INTO `food_boxes` (`id`, `bakery_id`, `name`, `slug`, `box_type`, `price`, `original_value`, `description`, `contents_sample`, `image_url`, `available_qty`, `is_active`) VALUES
(1, 1, 'Afternoon Sweet Pastry Box (Snack Box)', 'afternoon-sweet-pastry-box', 'snack_box', 40000.00, 95000.00, 'Delightful afternoon snack box containing delicious fresh pastries baked this morning from Dreamy Bakes. Save over 55% compared to normal retail price!', 'Usually includes 2-3 items: French butter croissant, pain au chocolat, or blueberry danish.', 'assets/img/food_boxes/snack-box-1.jpg', 15, 1),
(2, 1, 'Hearty Campus Dinner Box (Meal Box)', 'hearty-campus-dinner-box', 'meal_box', 70000.00, 160000.00, 'Nutritious dinner meal box ideal for students, containing a savory deli sandwich, warm quiche tart, and a sweet fresh pastry.', 'Usually includes: 1 Smoked ham & cheddar baguette, 1 warm savory quiche, 1 vanilla cream puff.', 'assets/img/food_boxes/meal-box-1.jpg', 10, 1),
(3, 2, 'Energy Morning Baguette (Snack Box)', 'energy-morning-baguette', 'snack_box', 40000.00, 90000.00, 'Perfect breakfast or quick snack box featuring golden brioche rolls, cinnamon swirls, and crispy cheese sticks.', 'Usually includes: 1 Double chocolate muffin, 1 mini garlic cheese brioche, 1 butter cashew cookie.', 'assets/img/food_boxes/snack-box-2.jpg', 20, 1),
(4, 2, 'University Sourdough Feast (Meal Box)', 'university-sourdough-feast', 'meal_box', 70000.00, 155000.00, 'Substantial evening meal box featuring artisan sourdough sandwiches, roasted ham, melted cheese, and fresh greens.', 'Usually includes: 1 Black pepper chicken sandwich, 1 mushroom cheese quiche, 1 almond croissant.', 'assets/img/food_boxes/meal-box-2.jpg', 12, 1),
(5, 3, 'Relaxing Sweet Tea Box (Snack Box)', 'relaxing-sweet-tea-box', 'snack_box', 40000.00, 100000.00, 'Assortment of fresh fruit tarts, chocolate twists, and flaky almond pastries from Le Petit Croissant.', 'Usually includes: 2 Crispy caneles, 1 custard roll croissant, 1 fruit tartlet.', 'assets/img/food_boxes/snack-box-3.jpg', 18, 1),
(6, 3, 'European Artisan Dinner Box (Meal Box)', 'european-artisan-dinner-box', 'meal_box', 70000.00, 170000.00, 'Satisfying savory meal box with artisan bread, savory chicken puff pastry, and seasonal bakery desserts.', 'Usually includes: 1 Honey mustard grilled panini, 1 slice rosemary focaccia, 1 fresh fruit tart.', 'assets/img/food_boxes/meal-box-3.jpg', 8, 1);

-- 4. SEED PICKUP SLOTS (Today and upcoming days)
INSERT INTO `pickup_slots` (`bakery_id`, `pickup_date`, `start_time`, `end_time`, `max_capacity`, `reserved_count`, `is_active`) VALUES
(1, CURDATE(), '17:30:00', '18:30:00', 15, 2, 1),
(1, CURDATE(), '19:00:00', '20:30:00', 20, 3, 1),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '17:30:00', '18:30:00', 15, 0, 1),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', '20:30:00', 20, 0, 1),
(2, CURDATE(), '18:00:00', '19:30:00', 25, 4, 1),
(2, CURDATE(), '20:00:00', '21:00:00', 20, 1, 1),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', '19:30:00', 25, 0, 1),
(3, CURDATE(), '17:00:00', '18:30:00', 15, 1, 1),
(3, CURDATE(), '19:30:00', '21:00:00', 15, 2, 1),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:30:00', '21:00:00', 15, 0, 1);

-- 5. SEED INITIAL RESERVATIONS (Demonstrating different statuses)
INSERT INTO `reservations` (`id`, `reservation_code`, `user_id`, `guest_id`, `bakery_id`, `pickup_slot_id`, `total_amount`, `reservation_status`, `payment_method`, `payment_status`, `special_instructions`, `created_at`) VALUES
(1, 'NRN-DEMO-001', 2, NULL, 1, 1, 40000.00, 'Collected', 'Online', 'Paid', 'No scallions or onions please.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'NRN-DEMO-002', 2, NULL, 1, 2, 70000.00, 'Confirmed', 'Online', 'Paid', 'Collecting after university evening lecture.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'NRN-DEMO-003', NULL, 1, 2, 5, 80000.00, 'Pending', 'Pickup', 'Pay at Pickup', 'Two Snack Boxes for roommate group study.', NOW());

-- 6. SEED RESERVATION ITEMS
INSERT INTO `reservation_items` (`reservation_id`, `food_box_id`, `box_name`, `unit_price`, `quantity`, `subtotal`) VALUES
(1, 1, 'Afternoon Sweet Pastry Box (Snack Box)', 40000.00, 1, 40000.00),
(2, 2, 'Hearty Campus Dinner Box (Meal Box)', 70000.00, 1, 70000.00),
(3, 3, 'Energy Morning Baguette (Snack Box)', 40000.00, 2, 80000.00);

-- 7. SEED PAYMENTS
INSERT INTO `payments` (`reservation_id`, `payment_method`, `gateway_provider`, `transaction_reference`, `amount`, `payment_status`, `gateway_response`, `payment_date`) VALUES
(1, 'Online', 'vnpay', 'VNP2026110301982', 40000.00, 'Paid', '{"vnp_ResponseCode":"00","vnp_TransactionStatus":"00"}', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Online', 'momo', 'MOM2026110408192', 70000.00, 'Paid', '{"resultCode":0,"message":"Successful."}', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'Pickup', 'pickup', 'PAY-PICKUP-DEMO-003', 80000.00, 'Pay at Pickup', 'Payment due upon collection at store', NULL);

-- 8. SEED FEEDBACK
INSERT INTO `feedback` (`reservation_id`, `user_id`, `food_box_id`, `customer_name`, `rating`, `comment`, `is_public`, `created_at`) VALUES
(1, 2, 1, 'Alex Nguyen', 5, 'The pastries were delightfully fresh and flaky! For only 40,000 VND, I received 3 premium treats. This surplus food rescue initiative is a lifesaver for students!', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 9. SEED ADMIN ACTIVITY LOG
INSERT INTO `admin_activity_logs` (`admin_user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES
(1, 'SYSTEM_INIT', 'SYSTEM', 1, 'NomReNom core system initialized successfully with initial baseline configuration.', '127.0.0.1');

-- 10. SEED PILOT SURVEYS (Team 4 Target 5: At least 20 student price acceptance evaluations)
INSERT INTO `pilot_surveys` (`student_name`, `student_email`, `snack_box_price_rating`, `meal_box_price_rating`, `pickup_convenience`, `would_recommend`, `feedback_notes`, `created_at`) VALUES
('Alex Nguyen', 'alex.nguyen@university.edu.vn', 'Great Value', 'Fair Price', 'Very Convenient', 1, '40k for 3 French pastries is way cheaper than campus cafeterias! Love this!', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Bao Tran', 'bao.tran@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, '70k meal box with savory sandwich and quiche was so filling for dinner.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('Camille Duong', 'camille.d@university.edu.vn', 'Fair Price', 'Fair Price', 'Very Convenient', 1, 'Pickup at Dreamy Bakes near campus took less than 2 minutes.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('David Le', 'david.le@university.edu.vn', 'Great Value', 'Fair Price', 'Acceptable', 1, 'Helps save money every week as an international student.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Emma Pham', 'emma.pham@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, 'Food waste reduction is a cause I strongly support.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Felix Hoang', 'felix.h@university.edu.vn', 'Fair Price', 'Fair Price', 'Very Convenient', 1, 'The pastries were still fresh and warm.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Grace Vu', 'grace.vu@university.edu.vn', 'Great Value', 'Fair Price', 'Very Convenient', 1, 'Very easy reservation process on my phone.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Henry Do', 'henry.do@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, 'Saved over 50% compared to buying at retail price.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Ivy Vo', 'ivy.vo@university.edu.vn', 'Fair Price', 'Fair Price', 'Acceptable', 1, 'Would love more evening pickup slots after 8 PM.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Jack Nguyen', 'jack.nguyen@university.edu.vn', 'Great Value', 'Fair Price', 'Very Convenient', 1, 'High quality bakery food at street food prices.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Kelly Trinh', 'kelly.t@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, 'My roommates and I ordered 3 boxes together!', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Lucas Dinh', 'lucas.d@university.edu.vn', 'Fair Price', 'Fair Price', 'Very Convenient', 1, 'Simple and smooth checkout experience.', NOW()),
('Mia Huynh', 'mia.huynh@university.edu.vn', 'Great Value', 'Fair Price', 'Very Convenient', 1, 'Really delicious croissant and cinnamon rolls.', NOW()),
('Noah Phan', 'noah.p@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, 'Great meal box portion size for active students.', NOW()),
('Olivia Mai', 'olivia.mai@university.edu.vn', 'Fair Price', 'Fair Price', 'Very Convenient', 1, 'Affordable and sustainable.', NOW()),
('Peter Ngo', 'peter.ngo@university.edu.vn', 'Great Value', 'Fair Price', 'Acceptable', 1, 'Will definitely reserve again next week.', NOW()),
('Quinn Dang', 'quinn.d@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, 'Super convenient pickup after my evening lab.', NOW()),
('Ryan Tran', 'ryan.tran@university.edu.vn', 'Fair Price', 'Fair Price', 'Very Convenient', 1, 'Clear pickup instructions on the voucher.', NOW()),
('Sophia Ly', 'sophia.ly@university.edu.vn', 'Great Value', 'Fair Price', 'Very Convenient', 1, 'Best food initiative on campus this semester.', NOW()),
('Thomas Bui', 'thomas.bui@university.edu.vn', 'Great Value', 'Great Value', 'Very Convenient', 1, 'Affordable price point for university students.', NOW()),
('Uyen Nguyen', 'uyen.ng@university.edu.vn', 'Fair Price', 'Fair Price', 'Very Convenient', 1, 'Great bakery selection and polite staff.', NOW()),
('Victor Doan', 'victor.d@university.edu.vn', 'Great Value', 'Fair Price', 'Very Convenient', 1, 'Zero food waste concept is practical and meaningful.', NOW());
