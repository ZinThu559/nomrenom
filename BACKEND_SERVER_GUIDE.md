# 🚀 NomReNom Backend Server Deployment Guide

This repository contains the full backend server for **NomReNom** (PHP 8.2 + Apache + MariaDB/MySQL).

---

## 🌟 1-Click Free Cloud Deployment (Connect to GitHub)

You can launch a live backend server with a public HTTPS URL (e.g. `https://nomrenom-backend.onrender.com`) in **2 minutes for free** using **Render.com** directly from your GitHub repository.

### Option A: Deploy on Render.com (Recommended & Free)

1. **Sign in to Render**:
   - Go to [https://render.com](https://render.com) and click **Get Started** / **Sign In with GitHub**.
2. **Create New Web Service**:
   - In your Render Dashboard, click **New +** > **Web Service**.
   - Select **Build and deploy from a Git repository**.
   - Connect your GitHub account and select **`ZinThu559/nomrenom`**.
3. **Configure Settings**:
   - **Name**: `nomrenom-backend`
   - **Region**: Singapore (or nearest to your users)
   - **Branch**: `main`
   - **Runtime**: `Docker` (Render will automatically detect the root `Dockerfile`)
   - **Instance Type**: `Free`
4. **Add Database**:
   - Click **New +** > **PostgreSQL** or connect a free MySQL database (e.g. from Aiven, Clever Cloud, or Railway).
   - Set environment variables under **Environment**:
     - `DB_HOST`: your database host
     - `DB_NAME`: `nomrenom_db`
     - `DB_USER`: your database username
     - `DB_PASS`: your database password
5. **Click "Create Web Service"**:
   - Render will build the container from GitHub and give you a public URL like:
     ```
     https://nomrenom-backend.onrender.com
     ```
   - Whenever you push code to `main` on GitHub, Render will **automatically redeploy** your backend!

---

### Option B: Deploy with Docker Compose (Local or Cloud VPS)

Run the backend and database anywhere Docker is installed:

```bash
# Clone the repository
git clone https://github.com/ZinThu559/nomrenom.git
cd nomrenom

# Start the PHP backend and MariaDB database
docker compose up -d

# Open the backend
# http://localhost:8080
```

---

## 📡 Live API Endpoints

Once your server is running, the following endpoints are available:

| Endpoint | Method | Description |
| :--- | :---: | :--- |
| `/api/index.php` | `GET` | Server health status & database connectivity check |
| `/api/cart.php` | `POST / GET` | Add, update, remove, and get shopping cart items |
| `/admin/login.php` | `GET / POST` | Store Admin Portal for inventory, orders & refunds |
| `/boxes.php` | `GET` | PHP Dynamic Marketplace with server-rendered filters |
| `/frontend/index.html` | `GET` | Static frontend mirror |

---

## 🔒 Default Administrator Credentials (Pre-seeded)

1. **Alex Nguyen (Lead Operations Admin)**
   - **Email**: `admin.alex@nomrenom.vn`
   - **Password**: `NomAdmin2026!Alex`
2. **Linh Tran (Store Inventory Admin)**
   - **Email**: `admin.linh@nomrenom.vn`
   - **Password**: `NomAdmin2026!Linh`
3. **Minh Vo (Quality & Refunds Admin)**
   - **Email**: `admin.minh@nomrenom.vn`
   - **Password**: `NomAdmin2026!Minh`
