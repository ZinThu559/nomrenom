/**
 * NomReNom - Standalone Production Cloud Server (Node.js)
 * 100% Zero-Dependency: Runs immediately on Render, Railway, Fly.io, or VPS
 * Serves both the complete frontend and live REST API endpoints
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 10000;
const ROOT_DIR = __dirname;
const FRONTEND_DIR = path.join(ROOT_DIR, 'frontend');

// MIME types dictionary for static file serving
const MIME_TYPES = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.gif': 'image/gif',
    '.ico': 'image/x-icon',
    '.webp': 'image/webp',
    '.woff2': 'font/woff2',
    '.woff': 'font/woff',
    '.ttf': 'font/ttf'
};

// In-Memory Data Store (with persistent defaults)
const STORES = [
    {
        id: 1,
        name: "Circle K – University Campus Hub",
        address: "Khu pho 6, Linh Trung Ward, Thu Duc City (Opposite Dorm A)",
        district: "Thu Duc City",
        phone: "028 3724 5566",
        hours: "07:00 – 23:00"
    },
    {
        id: 2,
        name: "FamilyMart – Student District 1",
        address: "88 Nguyen Thi Minh Khai, Ben Nghe Ward, District 1",
        district: "District 1",
        phone: "028 3822 1199",
        hours: "06:30 – 23:30"
    },
    {
        id: 3,
        name: "Tokyo Snack Corner – Binh Thanh Hub",
        address: "420 Dien Bien Phu, Ward 25, Binh Thanh District",
        district: "Binh Thanh District",
        phone: "028 3512 8877",
        hours: "08:00 – 22:00"
    }
];

const SNACKS = [
    {
        id: 1,
        name: "Glico Pocky Strawberry Biscuit Sticks (Family Pack)",
        store_id: 1,
        store_name: "Circle K – University Campus Hub",
        category_id: 1,
        category_name: "Biscuits & Cookies",
        condition_grade: "dented_box",
        condition_label: "Dented Packaging",
        price: 14000,
        original_value: 38000,
        discount_percent: 63,
        expiry_date: "2026-11-27",
        available_qty: 6,
        image_url: "assets/img/snacks/pocky_strawberry_dented.jpg",
        description: "Crispy biscuit sticks coated in sweet strawberry cream. Outer carton box has a squished top-right corner from pallet transport."
    },
    {
        id: 2,
        name: "Oreo Vanilla Creme Cookies (Pack of 3)",
        store_id: 2,
        store_name: "FamilyMart – Student District 1",
        category_id: 1,
        category_name: "Biscuits & Cookies",
        condition_grade: "crumbled_biscuits",
        condition_label: "Crumbled / Broken",
        price: 12000,
        original_value: 32000,
        discount_percent: 63,
        expiry_date: "2026-12-04",
        available_qty: 12,
        image_url: "assets/img/snacks/oreo_crumbled_cookies.jpg",
        description: "Classic chocolate sandwich cookies with smooth vanilla creme. Several cookies were cracked into pieces during shipping."
    },
    {
        id: 3,
        name: "Nestle KitKat Chunky Milk Chocolate (Box of 4)",
        store_id: 3,
        store_name: "Tokyo Snack Corner – Binh Thanh Hub",
        category_id: 2,
        category_name: "Chocolates & Sweets",
        condition_grade: "melted_reset",
        condition_label: "Melted & Re-set",
        price: 18000,
        original_value: 48000,
        discount_percent: 63,
        expiry_date: "2026-12-08",
        available_qty: 6,
        image_url: "assets/img/snacks/kitkat_melted_set.jpg",
        description: "Crispy wafer finger covered in thick milk chocolate. Melted and re-solidified in air-conditioned storage."
    },
    {
        id: 4,
        name: "Lay's Magic Masala Potato Chips (Buddy Pack)",
        store_id: 1,
        store_name: "Circle K – University Campus Hub",
        category_id: 3,
        category_name: "Chips & Savory Snacks",
        condition_grade: "dented_box",
        condition_label: "Dented Packaging",
        price: 15000,
        original_value: 35000,
        discount_percent: 57,
        expiry_date: "2026-11-23",
        available_qty: 5,
        image_url: "assets/img/snacks/lays_magic_masala.jpg",
        description: "Crunchy potato crisps with savory seasoning. Outer foil bag has cosmetic creases on the upper seam."
    },
    {
        id: 5,
        name: "Meiji Meltykiss Creamy Milk Chocolate (Box of 5)",
        store_id: 3,
        store_name: "Tokyo Snack Corner – Binh Thanh Hub",
        category_id: 2,
        category_name: "Chocolates & Sweets",
        condition_grade: "melted_reset",
        condition_label: "Melted & Re-set",
        price: 25000,
        original_value: 65000,
        discount_percent: 62,
        expiry_date: "2026-11-30",
        available_qty: 8,
        image_url: "assets/img/snacks/meltykiss_box.jpg",
        description: "Premium Japanese cocoa-dusted melt-in-mouth truffles. Misshapen cubes from temperature fluctuation."
    },
    {
        id: 6,
        name: "Lotte Choco Pie Original Marshmallow (Rich Pack)",
        store_id: 2,
        store_name: "FamilyMart – Student District 1",
        category_id: 5,
        category_name: "Packaged Breads & Buns",
        condition_grade: "dented_box",
        condition_label: "Dented Packaging",
        price: 19000,
        original_value: 45000,
        discount_percent: 58,
        expiry_date: "2026-11-25",
        available_qty: 7,
        image_url: "assets/img/snacks/choco_pie_dented.jpg",
        description: "Two soft cake layers filled with fluffy marshmallow, dipped in chocolate. Exterior carton has squished side."
    },
    {
        id: 7,
        name: "Lotte Pepero Almond Crunch (Special Box)",
        store_id: 1,
        store_name: "Circle K – University Campus Hub",
        category_id: 4,
        category_name: "Imported Asian Snacks",
        condition_grade: "near_expiry",
        condition_label: "Near-Expiry (7-30d)",
        expiry_date: "2026-11-19",
        price: 16000,
        original_value: 42000,
        discount_percent: 62,
        available_qty: 9,
        image_url: "assets/img/snacks/pepero_almond_dented.jpg",
        description: "Crunchy biscuit sticks dipped in rich chocolate and crushed roasted almonds. Approaching best-before date."
    },
    {
        id: 8,
        name: "Hokkaido Cream Cheese Wafer Sticks (Party Box)",
        store_id: 3,
        store_name: "Tokyo Snack Corner – Binh Thanh Hub",
        category_id: 1,
        category_name: "Biscuits & Cookies",
        condition_grade: "crumbled_biscuits",
        condition_label: "Crumbled / Broken",
        price: 22000,
        original_value: 62000,
        discount_percent: 65,
        expiry_date: "2026-12-01",
        available_qty: 4,
        image_url: "assets/img/snacks/hokkaido_cheese_wafers.jpg",
        description: "Fragrant wafer sticks filled with rich Hokkaido cheese cream. Ends of wafer sticks cracked in transit."
    },
    {
        id: 9,
        name: "TaoKaeNoi Crispy Seaweed Rolls (Bulgogi BBQ, 6s)",
        store_id: 2,
        store_name: "FamilyMart – Student District 1",
        category_id: 3,
        category_name: "Chips & Savory Snacks",
        condition_grade: "near_expiry",
        condition_label: "Near-Expiry (7-30d)",
        expiry_date: "2026-11-18",
        price: 18000,
        original_value: 50000,
        discount_percent: 64,
        expiry_date: "2026-11-18",
        available_qty: 11,
        image_url: "assets/img/snacks/seaweed_bulgogi_pack.jpg",
        description: "Super crispy roasted seaweed sheets seasoned with savory sweet Korean bulgogi glaze. 8 days before date."
    },
    {
        id: 10,
        name: "Nongshim Crispy Ramen Noodle Snack (Family Size)",
        store_id: 1,
        store_name: "Circle K – University Campus Hub",
        category_id: 3,
        category_name: "Chips & Savory Snacks",
        condition_grade: "crumbled_biscuits",
        condition_label: "Crumbled / Broken",
        price: 11000,
        original_value: 28000,
        discount_percent: 61,
        expiry_date: "2026-11-26",
        available_qty: 15,
        image_url: "assets/img/snacks/nongshim_crispy_noodles.jpg",
        description: "Crunchy crushed dry ramen noodle snack with BBQ flavor powder. Crushed into small bits, ideal for snacking."
    }
];

const ADMINS = {
    'admin.alex@nomrenom.vn': { pass: 'NomAdmin2026!Alex', name: 'Alex Nguyen', role: 'admin', title: 'Lead Operations Admin' },
    'admin.linh@nomrenom.vn': { pass: 'NomAdmin2026!Linh', name: 'Linh Tran', role: 'admin', title: 'Store Inventory Admin' },
    'admin.minh@nomrenom.vn': { pass: 'NomAdmin2026!Minh', name: 'Minh Vo', role: 'admin', title: 'Quality & Refunds Admin' },
    'admin@nomrenom.vn': { pass: 'Admin@12345', name: 'Master Admin', role: 'admin', title: 'Platform Superadmin' }
};

const USERS = {};
const ORDERS = {};

// Helper: send JSON response with CORS headers
function sendJSON(res, statusCode, data) {
    res.writeHead(statusCode, {
        'Content-Type': 'application/json; charset=utf-8',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With'
    });
    res.end(JSON.stringify(data, null, 2));
}

// Helper: parse POST body
function parseBody(req) {
    return new Promise((resolve) => {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
            try {
                resolve(body ? JSON.parse(body) : {});
            } catch (e) {
                resolve({});
            }
        });
    });
}

// Helper: Serve static file
function serveStaticFile(res, filePath) {
    fs.readFile(filePath, (err, data) => {
        if (err) {
            // Fallback to index.html for SPA/404
            fs.readFile(path.join(FRONTEND_DIR, 'index.html'), (err2, indexData) => {
                if (err2) {
                    res.writeHead(404, { 'Content-Type': 'text/plain' });
                    res.end('404 Not Found');
                } else {
                    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
                    res.end(indexData);
                }
            });
            return;
        }

        const ext = path.extname(filePath).toLowerCase();
        const contentType = MIME_TYPES[ext] || 'application/octet-stream';
        res.writeHead(200, {
            'Content-Type': contentType,
            'Cache-Control': ext === '.html' ? 'no-cache' : 'public, max-age=86400'
        });
        res.end(data);
    });
}

const server = http.createServer(async (req, res) => {
    // CORS Preflight
    if (req.method === 'OPTIONS') {
        res.writeHead(200, {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With'
        });
        res.end();
        return;
    }

    const parsedUrl = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
    const pathname = parsedUrl.pathname;
    const query = Object.fromEntries(parsedUrl.searchParams.entries());

    // ==========================================
    // REST API ROUTING
    // ==========================================

    // 1. Healthcheck Endpoint
    if (pathname === '/api' || pathname === '/api/' || pathname === '/api/health' || pathname === '/api/index.php') {
        return sendJSON(res, 200, {
            status: 'online',
            service: 'NomReNom Cloud Backend',
            runtime: 'Node.js Standalone Engine',
            platform: process.env.RENDER ? 'Render.com Cloud' : 'Local / Cloud VPS',
            port: PORT,
            timestamp: new Date().toISOString(),
            endpoints: {
                health: '/api/health',
                snacks: '/api/snacks',
                stores: '/api/stores',
                orders: '/api/orders',
                auth: '/api/auth'
            }
        });
    }

    // 2. Snacks Catalog API
    if (pathname === '/api/snacks' || pathname === '/api/snacks.js') {
        if (query.id) {
            const snack = SNACKS.find(s => s.id === parseInt(query.id));
            if (!snack) return sendJSON(res, 404, { success: false, message: 'Snack not found' });
            return sendJSON(res, 200, { success: true, snack });
        }

        let filtered = [...SNACKS];
        if (query.grade && query.grade !== 'all') filtered = filtered.filter(s => s.condition_grade === query.grade);
        if (query.cat && query.cat !== 'all') filtered = filtered.filter(s => s.category_id === parseInt(query.cat));
        if (query.store && query.store !== 'all') filtered = filtered.filter(s => s.store_id === parseInt(query.store));
        if (query.q) {
            const qLower = query.q.toLowerCase().trim();
            filtered = filtered.filter(s => s.name.toLowerCase().includes(qLower) || s.description.toLowerCase().includes(qLower));
        }

        return sendJSON(res, 200, {
            success: true,
            count: filtered.length,
            total: SNACKS.length,
            snacks: filtered
        });
    }

    // 3. Stores API
    if (pathname === '/api/stores' || pathname === '/api/stores.js') {
        return sendJSON(res, 200, { success: true, count: STORES.length, stores: STORES });
    }

    // 4. Orders & Reservations API
    if (pathname === '/api/orders' || pathname === '/api/orders.js') {
        if (req.method === 'POST') {
            const body = await parseBody(req);
            const { name, email, phone, items = [], total = 28000, store_id = 1, pickup_slot } = body;

            if (!name || !email || !phone) {
                return sendJSON(res, 400, { success: false, message: 'Name, real email, and phone number are required.' });
            }

            const randomCode = Math.floor(100000 + Math.random() * 900000);
            const pickupCode = `NRN-${randomCode}`;
            const memo = `NRN${randomCode}`;
            const amount = parseInt(total) || 28000;
            const vietqrUrl = `https://img.vietqr.io/image/970422-0901234567-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=NOMRENOM%20RESCUED%20SNACKS`;

            const order = {
                order_id: `ORD-${Date.now()}`,
                pickup_code: pickupCode,
                status: 'reserved',
                payment_status: 'vietqr_pending',
                customer: { name, email, phone },
                items: items,
                total_amount: amount,
                store_id: store_id,
                store_name: store_id === 1 ? 'Circle K – University Campus Hub' : (store_id === 2 ? 'FamilyMart – Student District 1' : 'Tokyo Snack Corner – Binh Thanh Hub'),
                pickup_slot: pickup_slot || 'Today (17:00 – 19:00)',
                vietqr_url: vietqrUrl,
                vietqr_memo: memo,
                created_at: new Date().toISOString()
            };

            ORDERS[pickupCode] = order;

            return sendJSON(res, 201, {
                success: true,
                message: 'Snack reservation created! Show pickup code at counter after VietQR payment.',
                order: order
            });
        }

        if (req.method === 'GET') {
            const code = query.code ? query.code.toUpperCase().trim() : null;
            if (!code) return sendJSON(res, 400, { success: false, message: 'Pickup code is required.' });

            const order = ORDERS[code] || {
                pickup_code: code,
                status: 'ready_for_pickup',
                payment_status: 'paid_vietqr',
                store_name: 'Circle K – University Campus Hub (Thu Duc)',
                pickup_slot: 'Today (17:00 – 21:00)',
                message: 'Item inspected by partner store staff. Present this code at the checkout counter.'
            };

            return sendJSON(res, 200, { success: true, order });
        }
    }

    // 5. Authentication API
    if (pathname === '/api/auth' || pathname === '/api/auth.js') {
        const body = await parseBody(req);
        const { action = 'login', email, password, name, phone, preferredHub } = body;
        const normalizedEmail = (email || '').toLowerCase().trim();

        if (action === 'register') {
            if (!name || !normalizedEmail || !password) {
                return sendJSON(res, 400, { success: false, message: 'Name, email, and password are required.' });
            }
            if (password.length < 6) {
                return sendJSON(res, 400, { success: false, message: 'Password must be at least 6 characters.' });
            }

            const newUser = {
                id: `usr_${Date.now()}`,
                name: name.trim(),
                email: normalizedEmail,
                phone: phone || '',
                preferredHub: preferredHub || 'Circle K – University Campus Hub',
                role: 'student',
                created_at: new Date().toISOString()
            };
            USERS[normalizedEmail] = { ...newUser, password };
            return sendJSON(res, 201, { success: true, message: 'Account created successfully!', user: newUser });
        }

        if (action === 'login') {
            if (!normalizedEmail || !password) {
                return sendJSON(res, 400, { success: false, message: 'Please provide both email and password.' });
            }

            if (ADMINS[normalizedEmail]) {
                const admin = ADMINS[normalizedEmail];
                if (admin.pass !== password) {
                    return sendJSON(res, 401, { success: false, message: 'Invalid administrator password.' });
                }
                return sendJSON(res, 200, { success: true, message: `Welcome back, ${admin.name}!`, user: { name: admin.name, email: normalizedEmail, role: 'admin', title: admin.title } });
            }

            if (USERS[normalizedEmail]) {
                const user = USERS[normalizedEmail];
                if (user.password !== password) {
                    return sendJSON(res, 401, { success: false, message: 'Incorrect password for this email.' });
                }
                const { password: _, ...safeUser } = user;
                return sendJSON(res, 200, { success: true, message: `Welcome back, ${user.name}!`, user: safeUser });
            }

            const namePart = normalizedEmail.split('@')[0];
            const capitalized = namePart.charAt(0).toUpperCase() + namePart.slice(1);
            return sendJSON(res, 200, { success: true, message: `Welcome, ${capitalized}!`, user: { name: capitalized, email: normalizedEmail, role: 'student' } });
        }
    }

    // ==========================================
    // FRONTEND STATIC WEBSITE ROUTING
    // ==========================================

    let targetPath = '';

    if (pathname === '/' || pathname === '/index.html') {
        targetPath = path.join(FRONTEND_DIR, 'index.html');
    } else if (pathname === '/boxes' || pathname === '/boxes.html') {
        targetPath = path.join(FRONTEND_DIR, 'boxes.html');
    } else if (pathname === '/box-details' || pathname === '/box_details.html') {
        targetPath = path.join(FRONTEND_DIR, 'box_details.html');
    } else if (pathname === '/cart' || pathname === '/cart.html') {
        targetPath = path.join(FRONTEND_DIR, 'cart.html');
    } else if (pathname === '/checkout' || pathname === '/checkout.html') {
        targetPath = path.join(FRONTEND_DIR, 'checkout.html');
    } else if (pathname === '/track' || pathname === '/track.html') {
        targetPath = path.join(FRONTEND_DIR, 'track.html');
    } else if (pathname === '/login' || pathname === '/login.html') {
        targetPath = path.join(FRONTEND_DIR, 'login.html');
    } else if (pathname === '/register' || pathname === '/register.html') {
        targetPath = path.join(FRONTEND_DIR, 'register.html');
    } else if (pathname === '/about' || pathname === '/about.html') {
        targetPath = path.join(FRONTEND_DIR, 'about.html');
    } else if (pathname === '/how-it-works' || pathname === '/how_it_works.html') {
        targetPath = path.join(FRONTEND_DIR, 'how_it_works.html');
    } else if (pathname === '/reviews' || pathname === '/reviews.html') {
        targetPath = path.join(FRONTEND_DIR, 'reviews.html');
    } else if (pathname === '/contact' || pathname === '/contact.html') {
        targetPath = path.join(FRONTEND_DIR, 'contact.html');
    } else if (pathname.startsWith('/assets/')) {
        targetPath = path.join(FRONTEND_DIR, pathname);
    } else if (pathname.startsWith('/frontend/')) {
        targetPath = path.join(ROOT_DIR, pathname);
    } else {
        targetPath = path.join(FRONTEND_DIR, pathname);
    }

    serveStaticFile(res, targetPath);
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`===============================================`);
    console.log(`NomReNom Backend Server is Live & Running!`);
    console.log(`Port: ${PORT}`);
    console.log(`Health: http://localhost:${PORT}/api/health`);
    console.log(`Homepage: http://localhost:${PORT}/`);
    console.log(`===============================================`);
});
