# 🧺 NomReNom – Near-Expiry & Imperfect Snacks Marketplace

> **Student Prices • Store Pickup • Zero Shipping**

NomReNom connects convenience stores, bakeries, and importers with university students to purchase near-expiry snacks, dented-box biscuits, and melted chocolate at up to 70% off. Students inspect items in-person at the store counter before taking them home, with instant refunds if items don't match photos.

---

## 📁 Project Architecture: Clean Frontend & Backend Separation

This repository is strictly separated into two independent folders:

```
nomrenom/
├── 🌐 frontend/              # 100% Pure Static Web App (HTML, CSS, JS)
│   ├── assets/               # Self-contained CSS, JavaScript, and Images
│   │   ├── css/style.css     # Brand styling & typography
│   │   ├── js/data.js        # Realistic snack catalog & store hubs
│   │   ├── js/app.js         # Interactive cart, filters, modal handlers
│   │   └── images/           # High-resolution snack photos & Teddy mascot
│   ├── index.html            # Homepage with Teddy Mascot & Deal Grid
│   ├── boxes.html            # Browse & filter all available snacks
│   ├── box_details.html      # Detail view with photos & defect notes
│   ├── cart.html             # Shopping cart with counter pickup
│   ├── checkout.html         # Guest checkout & scannable VietQR code
│   ├── track.html            # Digital pickup pass & inspection refund
│   ├── how_it_works.html     # 4-step workflow & quality standards
│   ├── about.html            # Mission, pillars & Team 4 founders
│   ├── reviews.html          # Verified student testimonials
│   └── contact.html          # Campus store onboarding form
│
├── ⚙️ backend/               # Full PHP + MySQL + Admin Architecture
│   ├── admin/                # Staff dashboard & inventory management
│   ├── api/                  # JSON API endpoints
│   ├── assets/               # Backend-specific assets
│   ├── config/               # Database PDO connection & configuration
│   ├── database/             # Schema migrations & seed data
│   ├── docs/                 # API documentation & architecture specs
│   ├── includes/             # Shared PHP templates (header, footer, auth)
│   ├── payments/             # VietQR transaction verification
│   ├── storage/              # Uploaded media & system logs
│   ├── index.php             # PHP home controller
│   ├── boxes.php             # PHP catalog controller
│   ├── box_details.php       # PHP detail controller
│   └── ...                   # Full PHP reservation controllers
│
├── 🚀 index.html             # Root redirect forwarder (for GitHub Pages root)
└── 📄 README.md              # Project documentation & instructions
```

---

## 🌐 1. How to Run or Deploy the Frontend (GitHub Pages / Web)

The `frontend/` folder contains **zero PHP dependencies** and is built with pure HTML5, CSS3, Bootstrap 5, and JavaScript.

### Option A: GitHub Pages (Recommended)
1. Push this repository to GitHub.
2. In your GitHub repository, go to **Settings** → **Pages**.
3. Under **Branch**, select `main` and set the folder to `/ (root)`.
4. Click **Save**.
5. Visiting `https://<your-username>.github.io/<repo>/` will automatically open the frontend marketplace via the root `index.html` forwarder!

### Option B: Local Browser
You can open `frontend/index.html` directly in any web browser, or serve it using VS Code Live Server or Python:
```bash
# From the project root:
cd frontend
python -m http.server 8000
# Open http://localhost:8000 in your browser
```

---

## ⚙️ 2. How to Run the Backend (PHP & MySQL on XAMPP)

The `backend/` folder contains the full server-side PHP application with MySQL persistence and admin tools.

1. Ensure **Apache** and **MySQL** are running in the XAMPP Control Panel.
2. Ensure database `nomrenom_db` is imported from `backend/database/nomrenom_db.sql`.
3. Open your browser and navigate to:
   - **Marketplace:** `http://localhost/nomrenom/backend/` (or `http://localhost/nomrenom/backend/index.php`)
   - **Browse Snacks:** `http://localhost/nomrenom/backend/boxes.php`
   - **Admin Portal:** `http://localhost/nomrenom/backend/admin/`

---

## 🛡️ Core Features & Operating Principles
- **Store Pickup Only:** Zero shipping cost and zero courier delays; students pick up and inspect orders at convenience store counters.
- **Safety First:** Only undamaged primary seals; snacks expiring in 1 month down to 7 days (never expired).
- **Instant Counter Refund:** If an item looks worse than its live photo, student refuses at pickup for an immediate VietQR refund.
- **Interactive Cart & VietQR:** Add items to cart with smooth visual confirmation and generate dynamic, scannable VietQR payment codes.
