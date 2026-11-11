# 🧺 NomReNom – Near-Expiry & Imperfect Snacks Marketplace

> **Student Prices • Store Pickup • Zero Shipping • 100% Serverless Frontend**

NomReNom connects convenience stores, bakeries, and importers with university students to purchase near-expiry snacks, dented-box biscuits, and melted chocolate at up to 70% off. Students inspect items in-person at the store counter before taking them home, with instant refunds if items don't match photos.

---

## 🚀 100% Static Frontend Architecture (Deployable on Vercel)

This application is built with clean HTML5, CSS3, modern Vanilla JavaScript, and Bootstrap 5. It runs entirely in the browser with **zero server dependencies** and connects directly to **Supabase Cloud Database (PostgreSQL)** for user registration and authentication.

```
nomrenom/
├── index.html            # Main Landing Page (Hero showcase, Categories, Mascot)
├── boxes.html            # Marketplace Catalog with live filters & search
├── box_details.html      # Individual Snack Details & Defect Photos
├── cart.html             # Counter Pickup Shopping Cart
├── checkout.html         # Pickup Slot Selector & Confirmation
├── track.html            # Digital Pickup Pass & Order Lookup
├── login.html            # Customer Sign In (Connected to Supabase)
├── register.html         # Customer Registration (Connected to Supabase)
├── how_it_works.html     # 4-step workflow & quality standards
├── about.html            # Mission, Pillars & University Pilot
├── reviews.html          # Verified student testimonials
├── contact.html          # Store partner onboarding form
├── vercel.json           # Vercel Clean URL routing configuration
└── assets/               # CSS, JavaScript & Snack Images
    ├── css/style.css     # Brand styling & typography
    ├── js/data.js        # Snack catalog & store hubs data
    ├── js/app.js         # Reactive cart, search & Supabase Auth client
    └── img/              # High-resolution snack photos & Teddy mascot
```

---

## ⚡ Live Cloud Database (Supabase)

All customer accounts and activity are stored in real-time in Supabase:
- **Project Ref:** `mguszjsjiqkpoqebzivv`
- **Users Table:** `public.users` (stores `full_name`, `email`, `phone`, `password_hash`, `role`, `created_at`, `last_login_at`)
- **Snack Inventory:** `public.food_boxes` (10 curated snack deals)
- **Reservations:** `public.reservations` (Store pickup reservations)

When a customer signs up or signs in on Vercel, their profile and login timestamp (`last_login_at`) are saved directly to your Supabase PostgreSQL database.
