<script setup>
import { computed, onMounted, watch, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useGameStore } from './stores/game'
import NavBar from './components/NavBar.vue'
import Toast from './components/Toast.vue'

const route = useRoute()
const authStore = useAuthStore()
const gameStore = useGameStore()
const activityOpen = ref(false)
const showMiniPlayer = computed(() => gameStore.nowPlaying?.song && route.name !== 'Music')
const miniTitle = computed(() => gameStore.nowPlaying?.song?.title || '')

// Hide nav on special pages
const showNav = computed(() => {
  if (!authStore.isAuthenticated) return false
  if (route.name === 'SelectManager') return false
  if (route.name === 'Onboarding') return false
  return true
})

const showHeader = computed(() => {
  if (!authStore.isAuthenticated) return false
  if (route.name === 'SelectManager') return false
  if (route.name === 'Onboarding') return false
  return true
})

onMounted(() => {
  watch(
    () => authStore.isAuthenticated,
    (authed) => {
      if (authed) {
        gameStore.startEventPolling()
      }
    },
    { immediate: true }
  )
})

function toggleActivity() {
  activityOpen.value = !activityOpen.value
  if (activityOpen.value) {
    gameStore.markEventsRead()
  }
}

function miniTogglePlay() {
  if (!gameStore.nowPlaying?.song) return
  if (gameStore.nowPlaying.isPlaying) {
    gameStore.pauseSongAudio()
  } else {
    gameStore.playSongAudio(gameStore.nowPlaying.song)
  }
}

function miniRestart() {
  gameStore.restartSongAudio()
}

function miniSetVolume(e) {
  gameStore.setAudioVolume(Number(e.target.value) / 100)
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header with resources (only on main game screens) -->
    <header v-if="showHeader" class="sticky top-0 z-40 bg-gray-900/95 backdrop-blur-sm border-b border-gray-800">
      <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <div class="flex items-center gap-3">
            <span class="text-2xl">⭐</span>
            <h1 class="font-display text-xl text-transparent bg-clip-text bg-gradient-to-r from-kpop-pink-400 to-kpop-purple-400">
              KPOP IDOL MANAGER
            </h1>
          </div>
          
          <!-- Resources -->
          <div class="flex items-center gap-4 relative">
            <!-- Activity dropdown trigger -->
            <div class="relative">
              <button 
                class="px-3 py-2 rounded-lg bg-gray-800/80 border border-gray-700 hover:border-gray-600 flex items-center gap-2 shadow"
                @click="toggleActivity"
              >
                📰
                <span class="text-sm font-semibold">Activity</span>
                <span 
                  v-if="gameStore.unreadEvents > 0" 
                  class="ml-1 inline-flex items-center justify-center text-xs font-bold text-white bg-kpop-pink-500 rounded-full px-2 py-0.5"
                >
                  {{ gameStore.unreadEvents }}
                </span>
              </button>
              
              <transition name="fade">
                <div 
                  v-if="activityOpen"
                  class="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto game-panel p-3 border border-gray-700 shadow-xl"
                >
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-sm">Recent Activity</h3>
                    <button class="text-gray-400 hover:text-white text-xs" @click="activityOpen = false">✕</button>
                  </div>
                  <p class="text-gray-400 text-xs mb-2">Last 25 events.</p>
                  <div class="space-y-2">
                    <p v-if="gameStore.eventLog.length === 0" class="text-gray-500 text-sm">No activity yet.</p>
                    <div
                      v-for="(ev, idx) in gameStore.eventLog"
                      :key="idx"
                      class="bg-gray-800/70 rounded-lg px-3 py-2 text-sm border border-white/5"
                    >
                      <div class="font-semibold">{{ ev.message }}</div>
                      <div class="text-gray-400 text-xs mt-1">
                        +${{ ev.money?.toLocaleString?.() || 0 }} / +{{ ev.fans?.toLocaleString?.() || 0 }} fans
                      </div>
                      <div class="text-gray-500 text-[11px] mt-1">{{ new Date(ev.timestamp).toLocaleTimeString() }}</div>
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <div class="resource-badge">
              <span class="text-kpop-gold-400">💰</span>
              <span class="font-bold">{{ gameStore.formattedMoney }}</span>
            </div>
            <div class="resource-badge">
              <span class="text-kpop-pink-400">💖</span>
              <span class="font-bold">{{ gameStore.formattedFans }}</span>
            </div>
            <div class="resource-badge">
              <span class="text-kpop-purple-400">⭐</span>
              <span class="font-bold">{{ gameStore.player?.reputation || 0 }}</span>
            </div>
          </div>
          <!-- Mini player -->
          <transition name="fade">
            <div 
              v-if="showMiniPlayer" 
              class="mt-2 flex items-center gap-3 bg-gray-800/80 border border-gray-700 rounded-xl px-3 py-2 text-sm"
            >
              <span class="text-lg">🎵</span>
              <div class="flex-1 min-w-0">
                <p class="font-semibold truncate">{{ miniTitle }}</p>
                <div class="flex items-center gap-2 text-xs text-gray-300 flex-wrap">
                  <button class="btn-secondary text-xs py-1 px-2" @click="miniTogglePlay">
                    <span v-if="gameStore.nowPlaying?.isPlaying">⏸ Pause</span>
                    <span v-else>▶️ Play</span>
                  </button>
                  <button class="btn-secondary text-xs py-1 px-2" @click="miniRestart">⏮ Restart</button>
                  <div class="flex items-center gap-1">
                    <span class="text-gray-400">🔊</span>
                    <input 
                      type="range" 
                      min="0" max="100" 
                      :value="Math.round(gameStore.audioVolume * 100)" 
                      @input="miniSetVolume"
                    />
                    <span class="text-gray-400">{{ Math.round(gameStore.audioVolume * 100) }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1" :class="{ 'container mx-auto px-4 py-6': showHeader }">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Bottom navigation (only on main game screens) -->
    <NavBar v-if="showNav" />

    <!-- Toast notifications -->
    <Toast />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
