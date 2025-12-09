<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useGameStore } from '../stores/game'
import { useAuthStore } from '../stores/auth'

const gameStore = useGameStore()
const authStore = useAuthStore()

const featuredSlots = ref([]) // [{ idolId, lastEventAt }]
const hasSeededFeatured = ref(false)
const lastHandledEventKey = ref(null)

onMounted(async () => {
  await Promise.all([
    gameStore.fetchIdols(),
    gameStore.fetchGroups(),
    gameStore.fetchSongs(),
    gameStore.fetchPromotions(),
    gameStore.fetchAvailablePromotions(),
  ])
})

const player = computed(() => gameStore.player)
const manager = computed(() => authStore.user?.playerProfile?.manager)

const stats = computed(() => ({
  idols: gameStore.idols.length,
  groups: gameStore.groups.length,
  songs: gameStore.songs.length,
  completedSongs: gameStore.completedSongs.length
}))

const heroImage = computed(() => player.value?.background_image || '/images/bgsplash.png')

const activePromotions = computed(() => 
  gameStore.promotions.filter(p => p.is_active || p.is_ready)
)

const recentEvents = computed(() => gameStore.eventLog.slice(0, 4))

const nextPromoUnlock = computed(() => {
  if (!player.value || gameStore.promotionTypes.length === 0) return null
  const fans = player.value.fans || 0
  const rep = player.value.reputation || 0
  const sorted = [...gameStore.promotionTypes].sort((a, b) => {
    const aScore = (a.required_fans || 0) + (a.required_reputation || 0)
    const bScore = (b.required_fans || 0) + (b.required_reputation || 0)
    return aScore - bScore
  })

  return sorted.find(t => fans < (t.required_fans || 0) || rep < (t.required_reputation || 0)) || null
})

const managerPortraits = {
  marble: '/images/managers/marble.png',
  nela: '/images/managers/nela.png',
  spach: '/images/managers/spach.png',
  harris: '/images/managers/harris.png',
}

function getManagerImage(managerData) {
  const key = (managerData?.sprite_key || managerData?.name || '').toLowerCase()
  if (key.includes('marble')) return managerPortraits.marble
  if (key.includes('nela')) return managerPortraits.nela
  if (key.includes('spach')) return managerPortraits.spach
  if (key.includes('harris')) return managerPortraits.harris
  
  const values = Object.values(managerPortraits)
  const index = Math.abs((managerData?.id ?? 0)) % values.length
  return values[index]
}

const idolImages = Array.from({ length: 12 }, (_, i) => `/images/idols/${i + 1}.png`)

function spriteToImage(spriteKey) {
  const match = /idol_(\d+)/.exec(spriteKey || '')
  if (match) {
    const num = Math.min(12, Math.max(1, parseInt(match[1], 10)))
    return `/images/idols/${num}.png`
  }
  return null
}

function hashString(str) {
  return str.split('').reduce((acc, char) => ((acc << 5) - acc) + char.charCodeAt(0), 0)
}

function getIdolImage(idol) {
  if (!idol) return idolImages[0]
  if (idol.image_url) return idol.image_url
  const spriteImg = spriteToImage(idol.sprite_key)
  if (spriteImg) return spriteImg
  const seedBase = idol.id ?? hashString(idol.name || '')
  const index = Math.abs(seedBase) % idolImages.length
  return idolImages[index]
}

const featuredIdols = computed(() => {
  return featuredSlots.value
    .map(slot => {
      const idol = gameStore.idols.find(i => i.id === slot.idolId)
      if (!idol) return null
      return { ...slot, idol, image: getIdolImage(idol) }
    })
    .filter(Boolean)
})

function eventKey(ev) {
  return `${ev.timestamp || ''}-${ev.idol_id || ''}-${ev.message || ''}`
}

function sortByRecency(list) {
  return [...list].sort((a, b) => {
    const aTime = new Date(a.lastEventAt || 0).getTime()
    const bTime = new Date(b.lastEventAt || 0).getTime()
    return bTime - aTime
  })
}

function seedFeaturedFromExisting() {
  const seen = new Set()
  const seeded = []

  gameStore.eventLog.forEach(ev => {
    if (seen.size >= 4) return
    if (!ev.idol_id) return
    if (seen.has(ev.idol_id)) return
    seen.add(ev.idol_id)
    seeded.push({ idolId: ev.idol_id, lastEventAt: ev.timestamp })
  })

  if (seeded.length < 4) {
    gameStore.idols.forEach(idol => {
      if (seen.size >= 4) return
      seen.add(idol.id)
      seeded.push({ idolId: idol.id, lastEventAt: null })
    })
  }

  featuredSlots.value = sortByRecency(seeded).slice(0, 4)
  if (gameStore.eventLog[0]) {
    lastHandledEventKey.value = eventKey(gameStore.eventLog[0])
  }
}

function applyEventToFeatured(ev) {
  if (!ev?.idol_id) return
  const updatedAt = ev.timestamp || new Date().toISOString()
  const existingIdx = featuredSlots.value.findIndex(slot => slot.idolId === ev.idol_id)

  if (existingIdx !== -1) {
    const updated = { ...featuredSlots.value[existingIdx], lastEventAt: updatedAt }
    const clone = [...featuredSlots.value]
    clone.splice(existingIdx, 1, updated)
    featuredSlots.value = sortByRecency(clone)
    return
  }

  const incoming = { idolId: ev.idol_id, lastEventAt: updatedAt }

  if (featuredSlots.value.length < 4) {
    featuredSlots.value = sortByRecency([...featuredSlots.value, incoming])
    return
  }

  const oldest = [...featuredSlots.value].sort((a, b) => {
    const aTime = new Date(a.lastEventAt || 0).getTime()
    const bTime = new Date(b.lastEventAt || 0).getTime()
    return aTime - bTime
  })[0]

  featuredSlots.value = sortByRecency([
    ...featuredSlots.value.filter(slot => slot.idolId !== oldest.idolId),
    incoming
  ])
}

function handleEventLogChange() {
  const events = gameStore.eventLog
  if (!events.length) return

  const keys = events.map(eventKey)
  const stopIdx = lastHandledEventKey.value ? keys.indexOf(lastHandledEventKey.value) : -1
  const newEvents = stopIdx === -1 ? events : events.slice(0, stopIdx)

  // Process oldest first to keep rotation order consistent
  newEvents.slice().reverse().forEach(ev => applyEventToFeatured(ev))

  if (events[0]) {
    lastHandledEventKey.value = eventKey(events[0])
  }
}

watch(
  () => [gameStore.idols.length, gameStore.eventLog.length],
  () => {
    if (hasSeededFeatured.value) return
    if (!gameStore.idols.length) return
    seedFeaturedFromExisting()
    hasSeededFeatured.value = true
  },
  { immediate: true }
)

watch(
  () => gameStore.eventLog.map(ev => ev.timestamp).join('|'),
  () => {
    if (!hasSeededFeatured.value) return
    handleEventLogChange()
  }
)

function formatTimeAgo(value) {
  const ts = new Date(value || 0).getTime()
  if (!ts) return 'No events yet'

  const diff = Date.now() - ts
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}
</script>

<template>
  <div class="space-y-6">
    <div class="relative overflow-hidden rounded-3xl border border-gray-800 bg-gray-900 shadow-2xl min-h-[380px] lg:min-h-[460px]">
      <div 
        class="absolute inset-0 bg-cover bg-center"
        :style="{ backgroundImage: `url(${heroImage})` }"
      ></div>
      <div class="absolute inset-0 bg-gradient-to-r from-gray-900/90 via-gray-900/75 to-gray-900/35"></div>

      <div class="relative p-6 md:p-8 flex flex-col lg:flex-row gap-6 items-stretch">
        <div class="w-full lg:w-72 flex flex-col h-full">
          <!-- Tall manager portrait foreground -->
          <div class="relative flex-1 overflow-hidden rounded-2xl min-h-[300px]">
            <div 
              class="absolute inset-0 bg-cover bg-center"
              :style="{ backgroundImage: `linear-gradient(180deg, rgba(8,8,12,0.05) 0%, rgba(8,8,12,0.9) 70%), url(${getManagerImage(manager)})` }"
            ></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-kpop-pink-400 to-kpop-purple-400"></div>
            <div class="relative h-full flex flex-col justify-end p-4 space-y-2">
              <p class="text-xs uppercase tracking-wide text-gray-300">Manager</p>
              <h3 class="text-2xl font-bold">{{ manager?.name || 'Unknown' }}</h3>
              <p class="text-sm text-kpop-gold-400">
                +{{ Math.round((manager?.bonus_value || 0) * 100) }}% {{ manager?.bonus_type?.replace('_', ' ') }}
              </p>
              <div class="pt-3 border-t border-white/10 flex items-center justify-between text-sm">
                <span class="font-semibold text-transparent bg-clip-text bg-gradient-to-r from-kpop-pink-400 to-kpop-purple-400">
                  {{ player?.agency_name || 'My Agency' }}
                </span>
                <span class="px-3 py-1 rounded-lg bg-black/50 border border-white/10 text-xs">
                  Lv {{ player?.level || 1 }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex-1 space-y-4">
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-300">Spotlight</p>
              <h2 class="text-xl font-bold">Featured Idols</h2>
              <p class="text-sm text-gray-400">Auto-rotates as events happen</p>
            </div>
            <router-link to="/idols" class="btn-secondary text-sm py-2 px-4">
              View All Idols
            </router-link>
          </div>

          <TransitionGroup
            name="fade-fast"
            tag="div"
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 auto-rows-[minmax(260px,1fr)]"
          >
            <div
              v-for="slot in featuredIdols"
              :key="slot.idol.id"
              class="relative overflow-hidden rounded-2xl min-h-[260px]"
            >
              <div
                class="absolute inset-0 bg-cover bg-center"
                :style="{ backgroundImage: `linear-gradient(180deg, rgba(6,6,10,0.35) 0%, rgba(6,6,10,0.9) 70%), url(${slot.image})` }"
              ></div>
              <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-kpop-pink-400 to-kpop-purple-400"></div>
              <div class="relative p-4 flex flex-col justify-end h-full space-y-2">
                <div class="flex items-center justify-between gap-2">
                  <h3 class="font-bold text-lg drop-shadow">{{ slot.idol.name }}</h3>
                  <span class="text-[11px] px-2 py-1 rounded-full bg-black/50 border border-white/10 capitalize">
                    {{ slot.idol.rarity }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-300">
                  <span>⭐ {{ Math.round((slot.idol.vocal * 0.25) + (slot.idol.dance * 0.25) + (slot.idol.visual * 0.20) + (slot.idol.charm * 0.20) + (slot.idol.stamina * 0.10)) }}</span>
                  <span class="text-gray-400">{{ formatTimeAgo(slot.lastEventAt) }}</span>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div class="game-panel p-3 text-center">
        <div class="text-2xl mb-1">⭐</div>
        <div class="text-xl font-bold">{{ stats.idols }}</div>
        <div class="text-xs text-gray-400">Idols</div>
      </div>
      <div class="game-panel p-3 text-center">
        <div class="text-2xl mb-1">👥</div>
        <div class="text-xl font-bold">{{ stats.groups }}</div>
        <div class="text-xs text-gray-400">Groups</div>
      </div>
      <div class="game-panel p-3 text-center">
        <div class="text-2xl mb-1">🎵</div>
        <div class="text-xl font-bold">{{ stats.completedSongs }}</div>
        <div class="text-xs text-gray-400">Songs</div>
      </div>
      <div class="game-panel p-3 text-center">
        <div class="text-2xl mb-1">💖</div>
        <div class="text-xl font-bold">{{ gameStore.formattedFans }}</div>
        <div class="text-xs text-gray-400">Total Fans</div>
      </div>
    </div>

    <div class="game-panel p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <p class="text-sm text-gray-400">Next promotion unlock</p>
        <p class="font-bold text-lg">
          <span v-if="nextPromoUnlock">
            {{ nextPromoUnlock.name }} requires
            {{ (nextPromoUnlock.required_fans || 0).toLocaleString() }} fans /
            {{ (nextPromoUnlock.required_reputation || 0).toLocaleString() }} rep
          </span>
          <span v-else>All promotions unlocked! 🚀</span>
        </p>
      </div>
      <div class="text-sm text-gray-400">
        Keep growing fans and reputation to access higher-tier promos.
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div v-if="activePromotions.length > 0" class="game-panel p-6">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-bold flex items-center gap-2">
            <span>📢</span> Active Promotions
          </h2>
          <router-link to="/promotions" class="text-sm text-kpop-pink-300 hover:underline">Go to promos</router-link>
        </div>
        <div class="space-y-3">
          <div 
            v-for="promo in activePromotions" 
            :key="promo.id"
            class="flex items-center justify-between p-3 bg-gray-800/50 rounded-xl border border-white/5"
          >
            <div>
              <p class="font-medium">{{ promo.type.replace('_', ' ').toUpperCase() }}</p>
              <p class="text-sm text-gray-400">{{ promo.group?.name }} — {{ promo.song?.title }}</p>
            </div>
            
            <router-link 
              to="/promotions" 
              class="btn-primary text-sm py-2 px-4"
            >
              {{ promo.is_ready ? 'Collect!' : 'View' }}
            </router-link>
          </div>
        </div>
      </div>

      <div class="game-panel p-6">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-bold flex items-center gap-2">
            <span>📰</span> Recent Events
          </h2>
          <span class="text-xs text-gray-400">Auto-refreshes every pulse</span>
        </div>
        <div class="space-y-2">
          <p v-if="recentEvents.length === 0" class="text-gray-500 text-sm">No activity yet.</p>
          <div
            v-for="(ev, idx) in recentEvents"
            :key="idx"
            class="flex items-start gap-3 rounded-xl bg-gray-800/50 border border-white/5 p-3"
          >
            <div class="text-xl">✨</div>
            <div class="flex-1">
              <p class="font-semibold">{{ ev.message }}</p>
              <p class="text-xs text-gray-400">+${{ ev.money?.toLocaleString?.() || 0 }} / +{{ ev.fans?.toLocaleString?.() || 0 }} fans</p>
              <p class="text-[11px] text-gray-500 mt-1">{{ formatTimeAgo(ev.timestamp) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="game-panel p-6">
      <h2 class="text-lg font-bold mb-4">Quick Actions</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <router-link to="/idols" class="btn-secondary text-center py-4">
          <div class="text-2xl mb-1">🔍</div>
          <div class="text-sm">Scout Idol</div>
        </router-link>
        
        <router-link to="/groups" class="btn-secondary text-center py-4">
          <div class="text-2xl mb-1">👥</div>
          <div class="text-sm">Form Group</div>
        </router-link>
        
        <router-link to="/music" class="btn-secondary text-center py-4">
          <div class="text-2xl mb-1">🎵</div>
          <div class="text-sm">Produce Song</div>
        </router-link>
        
        <router-link to="/promotions" class="btn-secondary text-center py-4">
          <div class="text-2xl mb-1">📢</div>
          <div class="text-sm">Run Promo</div>
        </router-link>
        <router-link to="/upgrades" class="btn-secondary text-center py-4">
          <div class="text-2xl mb-1">⬆️</div>
          <div class="text-sm">Agency Upgrades</div>
        </router-link>
      </div>
    </div>

    <div v-if="stats.idols === 0" class="game-panel p-6 border-kpop-gold-500/50">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span>📚</span> Getting Started
      </h2>
      
      <div class="space-y-4">
        <div class="flex items-start gap-4">
          <div class="w-8 h-8 rounded-full bg-kpop-pink-500/20 flex items-center justify-center font-bold">1</div>
          <div>
            <p class="font-medium">Scout Your First Idols</p>
            <p class="text-sm text-gray-400">Visit the Idols page to scout talented trainees for your agency.</p>
          </div>
        </div>
        
        <div class="flex items-start gap-4">
          <div class="w-8 h-8 rounded-full bg-kpop-pink-500/20 flex items-center justify-center font-bold">2</div>
          <div>
            <p class="font-medium">Form a Group</p>
            <p class="text-sm text-gray-400">Once you have at least 2 idols, form your debut group!</p>
          </div>
        </div>
        
        <div class="flex items-start gap-4">
          <div class="w-8 h-8 rounded-full bg-kpop-pink-500/20 flex items-center justify-center font-bold">3</div>
          <div>
            <p class="font-medium">Produce Music & Promote</p>
            <p class="text-sm text-gray-400">Create songs and run promotions to gain fans and make money!</p>
          </div>
        </div>
      </div>
      
      <router-link to="/idols" class="btn-primary mt-6 inline-block">
        Start Scouting ⭐
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.fade-fast-enter-active,
.fade-fast-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.fade-fast-enter-from,
.fade-fast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>

