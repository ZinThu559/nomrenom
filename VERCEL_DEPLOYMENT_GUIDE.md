# ⚡ Deploying NomReNom on Vercel

This repository is **100% pre-configured for Vercel**!

It includes:
1. **Frontend Static Routing**: Clean URLs (`/boxes`, `/cart`, `/checkout`, `/login`, `/register`, `/track`) without redirects.
2. **Serverless Backend API** in `/api/`: Native Vercel serverless microservices for health, snacks, orders, and authentication.
3. **CORS Enabled**: Complete cross-origin resource sharing support for mobile and web.

---

## 🚀 How to Deploy on Vercel (Step-by-Step)

### Step 1: Import Your Repository on Vercel
1. Go to [https://vercel.com](https://vercel.com) and log in with your **GitHub account**.
2. Click **Add New...** > **Project**.
3. Under **Import Git Repository**, find **`ZinThu559/nomrenom`** and click **Import**.

### Step 2: Configure Project Settings
- **Project Name**: `nomrenom` (or your preferred name)
- **Framework Preset**: `Other` *(Vercel will automatically read `vercel.json`)*
- **Root Directory**: `./` (leave default)
- **Build and Output Settings**: Leave default

### Step 3: Click "Deploy"
Vercel will build and deploy your project in under 30 seconds!
You will get a live production URL like:
```
https://nomrenom.vercel.app
```

---

## 📡 Live Serverless API Endpoints on Vercel

Your live Vercel deployment automatically powers these backend endpoints:

| Endpoint | Method | Description |
| :--- | :---: | :--- |
| `https://<your-project>.vercel.app/api/health` | `GET` | API health check, region & status |
| `https://<your-project>.vercel.app/api/snacks` | `GET` | Get all snacks, filter by `?grade=`, `?cat=`, `?q=`, or `?id=` |
| `https://<your-project>.vercel.app/api/stores` | `GET` | Get all 3 university campus hub store details |
| `https://<your-project>.vercel.app/api/orders` | `POST / GET` | Create snack reservation & track by pickup code |
| `https://<your-project>.vercel.app/api/auth` | `POST` | Customer & admin login / registration |

---

## 🌐 Clean Website Pages on Vercel

With `vercel.json`, your web pages load with clean URLs:

- **Homepage**: `https://<your-project>.vercel.app/`
- **Browse Snacks**: `https://<your-project>.vercel.app/boxes`
- **Cart**: `https://<your-project>.vercel.app/cart`
- **Checkout**: `https://<your-project>.vercel.app/checkout`
- **Sign In**: `https://<your-project>.vercel.app/login`
- **Create Account**: `https://<your-project>.vercel.app/register`
- **Track Pickup**: `https://<your-project>.vercel.app/track`

---

## 🔄 Automatic Continuous Deployment

Every time you push a commit to `main` on GitHub, Vercel will **automatically build and deploy** your updates within seconds!
