# 🚀 Deploying NomReNom on Render.com (Step-by-Step Guide)

Deploying NomReNom on **Render.com** is fast, free, and takes less than **1 minute**. Render provides a free SSL certificate (`https://`), auto-deploy on every `git push`, and persistent server execution.

---

## ⚡ Method 1: Instant Web Service (Recommended - Fastest & Easiest)

### Step 1: Sign Up / Log In to Render
1. Open [https://dashboard.render.com](https://dashboard.render.com)
2. Sign in with your **GitHub** account (`ZinThu559`).

### Step 2: Create a New Web Service
1. Click the blue **"New +"** button in the top right.
2. Select **"Web Service"**.
3. Choose **"Build and deploy from a Git repository"** and click **Next**.
4. Select your repository: `ZinThu559/nomrenom` (or paste `https://github.com/ZinThu559/nomrenom.git`).

### Step 3: Configure Settings (Pre-filled for You)
Fill in or verify the following fields:

| Setting | Value |
| :--- | :--- |
| **Name** | `nomrenom` *(or any name you prefer)* |
| **Region** | `Singapore` *(fastest for Vietnam / Asia)* |
| **Branch** | `main` |
| **Root Directory** | *(leave blank)* |
| **Runtime** | `Node` |
| **Build Command** | `echo "Ready"` *(or leave blank)* |
| **Start Command** | `node server.js` |
| **Instance Type** | **Free** ($0/month) |

### Step 4: Click Deploy!
1. Scroll down and click **"Deploy Web Service"** (or **"Create Web Service"**).
2. Render will spin up the server in **under 30 seconds**.
3. Once live, Render displays your public URL:
   👉 **`https://nomrenom-xxxx.onrender.com`**

---

## ⚡ Method 2: Blueprint Deploy (Automatic with `render.yaml`)

Because we included `render.yaml` in the root of the repository, you can also deploy automatically:
1. Go to [https://dashboard.render.com/blueprints](https://dashboard.render.com/blueprints)
2. Click **"New Blueprint Instance"**
3. Select `ZinThu559/nomrenom`
4. Render will read `render.yaml` and configure everything automatically!
5. Click **"Apply"**.

---

## 🌐 Live URLs & API Endpoints on Render

Once deployed, all the following routes are immediately available on your Render URL:

### 📱 Frontend Pages:
- **Homepage:** `https://your-service.onrender.com/`
- **Snack Boxes:** `https://your-service.onrender.com/boxes`
- **Cart:** `https://your-service.onrender.com/cart`
- **Checkout & VietQR:** `https://your-service.onrender.com/checkout`
- **Counter Order Tracking:** `https://your-service.onrender.com/track`
- **Student Sign In:** `https://your-service.onrender.com/login`
- **New Account Registration:** `https://your-service.onrender.com/register`

### 🔌 Live REST APIs:
- **Health Check:** `GET /api/health`
  ```json
  {
    "status": "online",
    "service": "NomReNom Cloud Backend",
    "runtime": "Node.js Standalone Engine",
    "platform": "Render.com Cloud"
  }
  ```
- **Snacks Catalog:** `GET /api/snacks` (Supports `?grade=`, `?cat=`, `?q=`, `?id=`)
- **Store Locations:** `GET /api/stores`
- **Counter Pickup Orders & VietQR:** `POST /api/orders` and `GET /api/orders?code=NRN-XXXXXX`
- **Authentication & Registration:** `POST /api/auth`

---

## 💡 Benefits of Render.com for NomReNom
1. **Zero Cold Configuration:** `server.js` requires 0 npm dependencies, boots instantly, and handles both static frontend files and backend APIs.
2. **Auto-Deploy:** Every time you push a new commit to `main` on GitHub, Render automatically rebuilds and deploys in seconds.
3. **Free SSL (`https://`):** Fully encrypted and ready for student mobile devices and QR code scanning.
