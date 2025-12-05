# 🚀 Deploy KPOP IDOL MANAGER to Railway

## Prerequisites
1. Create a [Railway account](https://railway.app)
2. Install Railway CLI: `npm install -g @railway/cli`
3. Login: `railway login`

## Step 1: Create Railway Project

```bash
# In your terminal
railway init
```

Choose "Empty Project" when prompted.

## Step 2: Add MySQL Database

1. Go to your Railway dashboard
2. Click "+ New" → "Database" → "MySQL"
3. Wait for it to provision

## Step 3: Set Environment Variables

In Railway dashboard, go to your service → Variables tab, add:

```
APP_NAME=KPOP Idol Manager
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY_HERE
APP_URL=https://your-app.railway.app

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_DATABASE=${{MySQL.MYSQL_DATABASE}}
DB_USERNAME=${{MySQL.MYSQL_USER}}
DB_PASSWORD=${{MySQL.MYSQL_PASSWORD}}

SESSION_DRIVER=cookie
SESSION_DOMAIN=.railway.app
SANCTUM_STATEFUL_DOMAINS=your-app.railway.app
```

Generate APP_KEY locally:
```bash
cd backend
php artisan key:generate --show
```

## Step 4: Build & Deploy

### Option A: Deploy from GitHub (Recommended)

1. Push your code to GitHub
2. In Railway, click "+ New" → "GitHub Repo"
3. Select your repo and the `backend` folder as root
4. Railway will auto-deploy on every push!

### Option B: Deploy via CLI

```bash
# Build frontend first
cd frontend
npm install
npm run build

# Copy to Laravel public
cp -r dist/* ../backend/public/

# Deploy backend
cd ../backend
railway up
```

## Step 5: Run Migrations

In Railway dashboard → your service → Settings → click "Railway Shell":

```bash
php artisan migrate --force
php artisan db:seed --force
```

Or add this to your start command (already configured in railway.toml).

## 🎉 Done!

Your app will be live at: `https://your-project.up.railway.app`

---

## Troubleshooting

### "Session domain mismatch"
Make sure `SESSION_DOMAIN` is set to `.railway.app`

### "CORS errors"
Update `SANCTUM_STATEFUL_DOMAINS` to include your Railway URL

### "Database connection failed"
Double-check the MySQL reference variables are correct

### Build fails
Try deploying backend separately first, then add frontend build step

