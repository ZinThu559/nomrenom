<?php
/**
 * NomReNom - Database Connection Handler (PDO)
 * Seamless Hybrid: Connects to MySQL/MariaDB when available,
 * and automatically falls back to an embedded SQLite database if running in
 * Docker, Render, or a standalone cloud container without a live MySQL instance.
 */

require_once __DIR__ . '/config.php';

// Parse DATABASE_URL or cloud environment variables (Render, Railway, Docker, Heroku)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'nomrenom_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$databaseUrl = getenv('DATABASE_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('JAWSDB_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts) {
        $dbHost = $parts['host'] ?? $dbHost;
        $dbPort = $parts['port'] ?? $dbPort;
        $dbUser = $parts['user'] ?? $dbUser;
        $dbPass = $parts['pass'] ?? $dbPass;
        $dbName = ltrim($parts['path'] ?? '', '/') ?: $dbName;
    }
}

defined('DB_HOST') or define('DB_HOST', $dbHost);
defined('DB_PORT') or define('DB_PORT', $dbPort);
defined('DB_NAME') or define('DB_NAME', $dbName);
defined('DB_USER') or define('DB_USER', $dbUser);
defined('DB_PASS') or define('DB_PASS', $dbPass);
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance (MySQL or Automatic SQLite Fallback)
 * @return PDO
 */
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // 1. First, attempt connection to MySQL / MariaDB (Local XAMPP or Cloud DB)
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // 2. MySQL is not running or unreachable (e.g. Render / Docker container without external MySQL).
            // Gracefully fall back to SQLite so the application loads immediately without crashing!
            try {
                $dbDir = __DIR__ . '/../database';
                if (!is_dir($dbDir) || !is_writable($dbDir)) {
                    @mkdir($dbDir, 0777, true);
                }
                if (!is_writable($dbDir)) {
                    $dbDir = sys_get_temp_dir();
                }

                $sqlitePath = $dbDir . '/nomrenom_auto.sqlite';
                $isNew = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

                $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                // Register standard MySQL functions in SQLite for complete query compatibility
                $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'));
                $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));

                if ($isNew) {
                    initSqliteFallbackDatabase($pdo);
                }

                return $pdo;
            } catch (Exception $sqliteErr) {
                // If even SQLite fails, show clean error
                $isApi = defined('IS_API') || 
                         (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api') !== false) ||
                         (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/api') !== false);

                if ($isApi) {
                    http_response_code(503);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Database Fallback Error',
                        'message' => $sqliteErr->getMessage()
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    exit;
                }

                die('<div style="font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;background:#fef2f2;border:1px solid #f87171;border-radius:8px;">' .
                    '<h2 style="color:#991b1b;margin-top:0;">Database Connection Error</h2>' .
                    '<p style="color:#7f1d1d;">Could not connect to MySQL MariaDB, and SQLite fallback failed.</p>' .
                    '<p><strong>MySQL:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>' .
                    '<p><strong>SQLite:</strong> ' . htmlspecialchars($sqliteErr->getMessage()) . '</p>' .
                    '</div>');
            }
        }
    }

    return $pdo;
}

/**
 * Initializes and populates the embedded SQLite database with complete commercial snack catalog
 */
function initSqliteFallbackDatabase(PDO $pdo): void {
    // 1. Create Core Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            icon TEXT,
            sort_order INT DEFAULT 0,
            is_active INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS bakeries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            address TEXT NOT NULL,
            district TEXT NOT NULL,
            city TEXT DEFAULT 'Ho Chi Minh City',
            phone TEXT NOT NULL,
            email TEXT NOT NULL,
            opening_time TEXT DEFAULT '07:00:00',
            closing_time TEXT DEFAULT '22:00:00',
            logo_url TEXT,
            image_url TEXT,
            is_active INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS food_boxes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bakery_id INT NOT NULL,
            category_id INT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            box_type TEXT NOT NULL DEFAULT 'snack_box',
            condition_grade TEXT DEFAULT 'dented_box',
            price REAL NOT NULL,
            original_value REAL NOT NULL,
            discount_percent INT DEFAULT 50,
            expiry_date TEXT,
            description TEXT,
            contents_sample TEXT,
            image_url TEXT,
            available_qty INT DEFAULT 10,
            is_active INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'customer',
            is_email_verified INT DEFAULT 1,
            is_phone_verified INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS guest_customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            is_email_verified INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS pickup_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bakery_id INT NOT NULL,
            pickup_date TEXT NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            max_capacity INT DEFAULT 20,
            reserved_count INT DEFAULT 0,
            is_active INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_code TEXT NOT NULL UNIQUE,
            user_id INT,
            guest_id INT,
            bakery_id INT NOT NULL,
            pickup_slot_id INT,
            total_amount REAL NOT NULL,
            reservation_status TEXT DEFAULT 'Pending',
            payment_method TEXT DEFAULT 'Online',
            payment_status TEXT DEFAULT 'Pending',
            special_instructions TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reservation_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_id INT NOT NULL,
            food_box_id INT NOT NULL,
            box_name TEXT NOT NULL,
            unit_price REAL NOT NULL,
            quantity INT DEFAULT 1,
            subtotal REAL NOT NULL
        );

        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_id INT NOT NULL,
            payment_method TEXT DEFAULT 'Online',
            gateway_provider TEXT DEFAULT 'vietqr',
            transaction_reference TEXT,
            amount REAL NOT NULL,
            payment_status TEXT DEFAULT 'Paid',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_id INT,
            user_id INT,
            food_box_id INT,
            customer_name TEXT NOT NULL,
            rating INT DEFAULT 5,
            comment TEXT NOT NULL,
            is_public INT DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 2. Seed Categories
    $pdo->exec("
        INSERT INTO categories (id, name, slug, description, icon, sort_order) VALUES
        (1, 'Biscuits & Cookies', 'biscuits', 'Crispy biscuits, cookies and sandwich wafers', 'cookie', 1),
        (2, 'Chocolates & Sweets', 'chocolates', 'Rich milk chocolates, truffles and candy bars', 'box2-heart', 2),
        (3, 'Chips & Savory Snacks', 'chips', 'Savory potato crisps and crackers', 'fire', 3),
        (4, 'Ramyun & Instant Noodles', 'ramyun', 'Korean & Japanese spicy ramyun noodle snacks', 'cup-hot', 4),
        (5, 'Packaged Breads & Buns', 'breads', 'Marshmallow cakes, choco pies and brioche rolls', 'bag', 5),
        (6, 'Imported Asian Snacks', 'asian-snacks', 'Popular regional snack imports', 'stars', 6);
    ");

    // 3. Seed Partner Stores
    $pdo->exec("
        INSERT INTO bakeries (id, name, slug, description, address, district, city, phone, email, opening_time, closing_time, logo_url, image_url) VALUES
        (1, 'Circle K – University Campus Hub', 'circle-k-campus', 'Campus partner store with counter inspection table for near-expiry deals.', 'Khu pho 6, Linh Trung Ward, Thu Duc City (Opposite Dorm A)', 'Thu Duc City', 'Ho Chi Minh City', '028 3724 5566', 'circlek.campus@nomrenom.vn', '07:00:00', '23:00:00', 'assets/img/bakeries/bakery-logo-1.svg', 'assets/img/bakeries/bakery-store-1.jpg'),
        (2, 'FamilyMart – Student District 1', 'familymart-d1', 'Convenient city center pickup counter with VietQR fast check-in.', '88 Nguyen Thi Minh Khai, Ben Nghe Ward, District 1', 'District 1', 'Ho Chi Minh City', '028 3822 1199', 'familymart.d1@nomrenom.vn', '06:30:00', '23:30:00', 'assets/img/bakeries/bakery-logo-2.svg', 'assets/img/bakeries/bakery-store-2.jpg'),
        (3, 'Tokyo Snack Corner – Binh Thanh Hub', 'tokyo-snack-binh-thanh', 'Imported treats and temperature-controlled chocolate recovery counter.', '420 Dien Bien Phu, Ward 25, Binh Thanh District', 'Binh Thanh District', 'Ho Chi Minh City', '028 3512 8877', 'tokyosnack.bt@nomrenom.vn', '08:00:00', '22:00:00', 'assets/img/bakeries/bakery-logo-3.svg', 'assets/img/bakeries/bakery-store-3.jpg');
    ");

    // 4. Seed 10 Snack Products
    $pdo->exec("
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
    ");

    // 5. Seed Users & Admins
    $admin1 = password_hash('NomAdmin2026!Alex', PASSWORD_DEFAULT);
    $admin2 = password_hash('NomAdmin2026!Linh', PASSWORD_DEFAULT);
    $admin3 = password_hash('NomAdmin2026!Minh', PASSWORD_DEFAULT);
    $adminMaster = password_hash('Admin@12345', PASSWORD_DEFAULT);
    $studentPass = password_hash('Student@12345', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Alex Nguyen (Lead Administrator)', 'admin.alex@nomrenom.vn', '0901234561', $admin1, 'admin']);
    $stmt->execute([2, 'Linh Tran (Store Inventory Admin)', 'admin.linh@nomrenom.vn', '0901234562', $admin2, 'admin']);
    $stmt->execute([3, 'Minh Vo (Quality & Refunds Admin)', 'admin.minh@nomrenom.vn', '0901234563', $admin3, 'admin']);
    $stmt->execute([4, 'NomReNom Superadmin', 'admin@nomrenom.vn', '0901234567', $adminMaster, 'admin']);
    $stmt->execute([5, 'Alex Nguyen (Student Account)', 'student@university.edu.vn', '0912345678', $studentPass, 'customer']);

    // 6. Seed Sample Pickup Slots
    $today = date('Y-m-d');
    $pdo->exec("
        INSERT INTO pickup_slots (bakery_id, pickup_date, start_time, end_time, max_capacity, reserved_count) VALUES
        (1, '$today', '17:00:00', '19:00:00', 20, 3),
        (1, '$today', '19:00:00', '21:00:00', 20, 1),
        (2, '$today', '17:30:00', '19:30:00', 25, 4),
        (3, '$today', '18:00:00', '20:00:00', 15, 2);
    ");
}
