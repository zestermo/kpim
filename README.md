# KPOP IDOL MANAGER 2025

A browser-based K-Pop agency management simulation game where you become the CEO of your own entertainment company!

## 🌟 Features

- **Scout Idols**: Find talented trainees with randomized stats and rarities
- **Form Groups**: Create K-Pop groups with unique concepts
- **Produce Music**: Create songs across multiple genres
- **Run Promotions**: Execute marketing campaigns to gain fans and money
- **Manager System**: Choose a manager with unique passive bonuses
- **Real-time Progression**: Watch your promotions complete in real-time

## 🛠️ Tech Stack

- **Frontend**: Vue 3, Vite, Pinia, Tailwind CSS
- **Backend**: Laravel 11, SQLite
- **Auth**: Laravel Sanctum (session-based)

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed the database (adds default managers)
php artisan db:seed

# Start the server
php artisan serve
```

The backend will run at `http://localhost:8000`

### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start dev server
npm run dev
```

The frontend will run at `http://localhost:5173`

## 📱 How to Play

1. **Register** - Create your agency account
2. **Select Manager** - Choose a manager with a bonus that fits your strategy
3. **Scout Idols** - Spend money to discover new talent (different rarities!)
4. **Form a Group** - Combine at least 2 idols into a group
5. **Produce Songs** - Create music for your group
6. **Promote** - Run campaigns to gain fans, money, and reputation
7. **Grow** - Reinvest earnings to expand your empire!

## 🎮 Game Mechanics

### Resources
- 💰 **Money** - Used to scout, produce, and promote
- 💖 **Fans** - Your total fanbase
- ⭐ **Reputation** - Your agency's standing

### Idol Rarities
- ⚪ Common (50%)
- 🟢 Uncommon (30%)
- 🔵 Rare (14%)
- 🟣 Epic (5%)
- 🌟 Legendary (1%)

### Promotion Types
- 📱 Social Post - Quick, cheap, low reward
- 📰 Press Interview - Medium effort, medium reward
- 📺 TV Appearance - High visibility
- 🎤 Showcase - Maximum exposure
- ✍️ Fansign - Fan engagement focus

### Viral Chance
Promotions have a chance to go viral, multiplying your rewards!

## 📁 Project Structure

```
kpim/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   └── Models/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
│
├── frontend/               # Vue SPA
│   ├── src/
│   │   ├── api/
│   │   ├── components/
│   │   ├── router/
│   │   ├── stores/
│   │   └── views/
│   └── index.html
│
└── browser_management_game_development_master_doc.md
```

## 🔧 API Endpoints

### Auth
- `POST /api/v1/auth/register` - Create account
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/me` - Get current user

### Game
- `GET /api/v1/managers` - List managers
- `POST /api/v1/managers/select` - Select manager
- `GET /api/v1/idols` - List your idols
- `POST /api/v1/idols/scout` - Scout new idol
- `GET /api/v1/groups` - List your groups
- `POST /api/v1/groups` - Create group
- `GET /api/v1/songs` - List your songs
- `POST /api/v1/songs` - Produce song
- `GET /api/v1/promotions` - List promotions
- `POST /api/v1/promotions` - Start promotion
- `POST /api/v1/promotions/{id}/complete` - Complete promotion

## 🎨 Design Philosophy

- **Mobile-first**: Designed for touch interfaces
- **K-Pop Aesthetic**: Vibrant pinks, purples, and golds
- **Pixel UI Inspiration**: Soft, rounded game panels
- **Real-time Feedback**: Toasts and animations for all actions

## 📝 License

MIT License - Feel free to use this as a learning project!

---

Built with 💖 for K-Pop fans and game dev enthusiasts

