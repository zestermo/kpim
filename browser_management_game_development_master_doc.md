# KPOP IDOL MANAGER 2025 – Development Master Doc

## 1. High-Level Vision
**Game Type:** Browser-based K‑Pop agency management / simulation

**Core Fantasy:** The player becomes the CEO/Head Manager of a K‑Pop agency in 2025—signing trainees, debuting groups, producing music, styling idols, dominating charts, winning awards, and surviving seasonal competition.

**Primary Player Loop:**
1. Scout / Audition members
2. Form & manage a K‑Pop group
3. Produce songs & albums
4. Run promotions (social, press, events)
5. Compete in seasonal charts & award shows
6. Earn currency, fame, fans
7. Reinvest into the next comeback

**Target Session Length:** 2–10 minutes (idle + active hybrid)

**Visual Style:** Pixel-art, 2D, vertical character presentation

---

## 2. Core Game Systems

### 2.1 Resources
- 💰 Money (Primary Currency)
- 💖 Fans / Popularity
- ⭐ Reputation
- 💎 Premium Currency (optional)

### 2.2 Production Systems
- Trainee → Idol → Group → Comeback
- Song → Album → Promotion → Charting
- Tours & Live Events → Fan Growth

### 2.3 Progression
- Player Agency Level
- Group Rank (Rookie → Global)
- Seasonal Resets with Legacy Bonuses

### 2.4 Managers (Playable Characters)
- Multiple selectable managers at start
- Each has **unique passive bonuses**
  - Promotion Boost
  - Training Speed
  - Social Media Virality
  - Award Chance Bonus

### 2.5 Competition & Seasons
- Weekly charts
- Monthly comebacks
- Seasonal award shows
- Limited-time global events

---

## 3. Game Economy (Initial Targets)
| System | Base Value | Upgrade Scaling |
|--------|------------|------------------|
| Income |            |                  |
| Costs  |            |                  |
| Timers |            |                  |

---

## 4. Tech Stack

### 4.1 Frontend
- Framework: Vue 3
- Styling: Tailwind CSS
- State: Pinia
- Animation: CSS Animations + GSAP

### 4.2 Backend
- API: Laravel 11
- Auth: Session-based + token fallback
- Database: MySQL (prod), SQLite (dev)

### 4.3 Account Features
- Email + password login
- Separate player saves
- Cloud saves

---

## 5. Data Models (K‑Pop Focused Draft)

### Player
- id
- username
- agency_name
- money
- reputation
- level

### Manager
- id
- name
- passive_bonus_type
- passive_bonus_value

### Idol
- id
- name
- vocal
- dance
- visual
- charm
- stamina
- popularity

### Group
- id
- name
- concept
- popularity
- members []

### Song
- id
- title
- genre
- hype
- quality

### Album
- id
- title
- era_theme
- songs []

### Event
- id
- type (award, tour, press, festival)
- impact

---

## 6. UI / UX Layout

### 6.1 Core Screens
- Agency Dashboard
- Group Management
- Trainee Auditions
- Music Production
- Promotion & Social Media
- Award Shows & Events

### 6.2 Character Presentation
- Vertical **9:21 / 2:3 sprite layout**
- Dialogue boxes
- Manager + Idols appear during events

### 6.3 Layout Rules
- Mobile-first
- Bottom-tab navigation
- Soft pixel UI panels

---

## 7. Save System
- Server-authoritative saves per account
- Autosave every 30 seconds
- Offline popularity & income simulation

---

## 8. Development Phases

### Phase 1 – Core Playable MVP
- [ ] Login system
- [ ] Choose manager
- [ ] Scout & sign idols
- [ ] Form one group
- [ ] Produce one song
- [ ] Basic popularity system

### Phase 2 – Content & Competition
- [ ] Albums
- [ ] Award shows
- [ ] Seasonal events
- [ ] Social media system

### Phase 3 – Polish & Audio
- [ ] Music system
- [ ] Sound effects
- [ ] Pixel animations

---

## 9. Task Backlog (Living Section)

### Backend
- [ ] Auth setup
- [ ] Player model
- [ ] Resource processing cron

### Frontend
- [ ] Main dashboard
- [ ] Resource display
- [ ] Upgrade buttons

---

## 10. Balancing Notes
- Early game fast growth
- Mid game content unlocks
- Late game prestige scaling
- Awards must never be guaranteed

---

## 11. Live Ops (Future)
- Seasonal comebacks
- Limited banners
- Special idol recruitment events
- World tour competitions

---

## 12. Open Design Questions
- Realistic vs fantasy K‑Pop tone?
- Solo group focus vs multi-group empire?
- Player vs AI agencies or leaderboard only?
- Romance & relationship systems?

---

## 13. Cursor Instructions (Agent Behavior)
- Prioritize playable game loop first
- Pixel art & vertical layout are mandatory
- All idols/groups must be fully data-driven
- Avoid real-world celebrity references
- Systems must support seasonal resets

---

## 14. Change Log
- v0.1 – Initial document created
- v0.2 – KPOP IDOL MANAGER 2025 pivot
- v0.3 – Technical Specification added

---

# 15. Technical Specification (Cursor Kickoff)

## 15.1 Architecture Overview
**Pattern:** API-first with SPA frontend
- Frontend: Vue 3 + Pinia + Tailwind
- Backend: Laravel 11 REST API
- Auth: Session cookies + CSRF (SPA mode)
- Realtime (optional later): Laravel WebSockets / Pusher protocol

**Environments**
- Local: SQLite + Vite
- Staging/Prod: MySQL + Nginx

---

## 15.2 API Conventions
- Base URL: /api/v1
- Auth required via middleware: auth:sanctum (or session guards)
- JSON only
- Versioned responses

**Response Shape**
{
  "success": true,
  "data": {},
  "meta": {}
}

**Error Shape**
{
  "success": false,
  "code": "VALIDATION_ERROR",
  "message": "Human readable"
}

---

## 15.3 Authentication Flow
1. Register (email, password)
2. Login
3. Session stored via cookies
4. Player profile auto-created
5. Logout flushes save lock

Endpoints:
- POST /auth/register
- POST /auth/login
- POST /auth/logout
- GET /me

---

## 15.4 Core Simulation Loop
**Tick Rate:**
- Server cron every 60 seconds

Each tick updates:
- Group popularity decay/growth
- Passive fan gain
- Ongoing events
- Song/album production timers

Formula Rules:
- All growth = base * (1 + bonuses)
- Bonuses stack multiplicatively
- No hard caps

---

## 15.5 Manager System
**Managers are stat modifiers only.**

Schema:
- id
- name
- bonus_type
- bonus_value
- flavor_text

Examples:
- promotion_boost +15%
- training_speed +20%
- virality_chance +5%

Only one active manager per player.

---

## 15.6 Idol Generation System
When scouting:
- Randomized stats within tier bounds
- Stat rarity curves

Stats:
- Vocal (1–100)
- Dance (1–100)
- Visual (1–100)
- Charm (1–100)
- Stamina (1–100)

Derived:
- Star Power = weighted sum

---

## 15.7 Group System
Group Properties:
- Name
- Concept
- Members (2–7)
- Popularity

Group Bonuses:
- Concept synergy
- Member stat synergy

---

## 15.8 Song & Album System

### Song
- genre
- quality
- hype
- production_time

### Album
- 1–6 songs
- era_theme
- comeback_power = sum(song.hype * group.popularity)

---

## 15.9 Promotion System
Promotion Types:
- Social post
- Press interview
- TV appearance
- Showcase event

Each promotion:
- Cost
- Duration
- Fan impact
- Virality roll

---

## 15.10 Seasonal Systems
- Weekly charts
- Monthly rankings
- Quarterly award shows

Awards grant:
- Permanent rep bonuses
- Unique cosmetics

---

## 15.11 Economy Rules
- Early income from promotions
- Mid income from albums
- Late income from tours + awards

Anti-inflation systems:
- Increasing production costs
- Soft diminishing returns

---

## 15.12 Pixel Art & Rendering Rules
- All characters vertical portrait format (9:21 / 2:3)
- Assets loaded via sprite atlases
- Animations:
  - Idle
  - Talk
  - Celebration

---

## 15.13 Audio System
- Global mute
- Music channel
- SFX channel
- One background track per screen

---

## 15.14 Security Rules
- All economy mutation server-side
- Anti-duplicate reward checks
- Event completion idempotent

---

## 15.15 Backend Models (Concrete)
- User
- PlayerProfile
- Manager
- Idol
- Group
- GroupMember
- Song
- Album
- Event
- Promotion
- Season

---

## 15.16 First API Endpoints To Build (Order)
1. Auth
2. Player Profile
3. Manager Selection
4. Idol Scouting
5. Group Creation
6. Song Production
7. Promotion Execution

---

## 15.17 Frontend App Structure
/src
- /views
- /components
- /stores
- /api
- /assets/sprites
- /assets/audio

---

## 15.18 Performance Rules
- No tick-based polling more than 1s
- Web animations only; no canvas in MVP
- All sprites preloaded per screen

---

## 15.19 Cursor Dev Rules (Hard Constraints)
- Do not generate placeholder logic
- All numbers must be configuration-driven
- No business logic in Vue components
- All mutation flows must be reversible
- Never bypass server validation

---

## 15.20 MVP Completion Definition
MVP is complete when:
- Player can login
- Select manager
- Scout idol
- Create group
- Produce song
- Promote song
- Gain fans & money
- Log out and resume progress

