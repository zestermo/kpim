<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useGameStore } from '../stores/game'
import { useAuthStore } from '../stores/auth'
import api from '../api'

const router = useRouter()
const gameStore = useGameStore()
const authStore = useAuthStore()

const selectedManager = ref(null)
const isSelecting = ref(false)
const checking = ref(true)
const error = ref('')
const activeIndex = ref(0)

const managerPortraits = {
  marble: '/images/managers/marble.png',
  nela: '/images/managers/nela.png',
  spach: '/images/managers/spach.png',
  harris: '/images/managers/harris.png',
}

onMounted(async () => {
  // Force refresh user data from server
  try {
    const response = await api.get('/me')
    const userData = response.data.data.user
    authStore.user = userData
    gameStore.setPlayer(userData.playerProfile)
    
    // If manager already selected, go to dashboard
    if (userData?.playerProfile?.manager_id) {
      console.log('Manager already selected, redirecting to dashboard')
      window.location.href = '/dashboard'
      return
    }
  } catch (e) {
    console.error('Failed to check user:', e)
    error.value = 'Failed to load user data'
  }
  
  checking.value = false
  await gameStore.fetchManagers()
  if (gameStore.managers.length > 0) {
    activeIndex.value = 0
    selectedManager.value = gameStore.managers[0]
  }
})

async function confirmSelection() {
  if (!selectedManager.value || isSelecting.value) return
  
  isSelecting.value = true
  error.value = ''
  
  try {
    const result = await gameStore.selectManager(selectedManager.value.id)
    
    if (result.success) {
      // Update the auth store user data
      if (authStore.user?.playerProfile) {
        authStore.user.playerProfile.manager_id = selectedManager.value.id
        authStore.user.playerProfile.manager = selectedManager.value
      }
      
      // Force navigation with window.location
      window.location.href = '/dashboard'
    } else {
      error.value = result.error || 'Failed to select manager'
    }
  } catch (e) {
    error.value = e.message || 'An error occurred'
  }
  
  isSelecting.value = false
}

function forceLogout() {
  authStore.logout()
  window.location.href = '/login'
}

function skipToDashboard() {
  window.location.href = '/dashboard'
}

const bonusLabels = {
  promotion_boost: '📢 Promotion Power',
  training_speed: '⚡ Training Speed',
  virality_chance: '🔥 Viral Chance',
  award_chance: '🏆 Award Luck',
  scouting_quality: '🔍 Scouting Quality'
}

function formatBonus(manager) {
  const percentage = Math.round(manager.bonus_value * 100)
  return `+${percentage}%`
}

function getManagerImage(manager) {
  const key = (manager?.sprite_key || manager?.name || '').toLowerCase()
  if (key.includes('marble')) return managerPortraits.marble
  if (key.includes('nela')) return managerPortraits.nela
  if (key.includes('spach')) return managerPortraits.spach
  if (key.includes('harris')) return managerPortraits.harris
  
  // Fallback deterministic pick
  const values = Object.values(managerPortraits)
  const index = Math.abs((manager?.id ?? 0)) % values.length
  return values[index]
}

function isActive(idx) {
  return idx === activeIndex.value
}

function getManagerCardStyle(manager) {
  const img = getManagerImage(manager)
  return {
    backgroundImage: `linear-gradient(180deg, rgba(12,12,20,0) 0%, rgba(12,12,20,0) 60%, rgba(12,12,20,0.75) 100%), url(${img})`,
    backgroundSize: '125%',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat',
  }
}

function normalizeOffset(idx) {
  const total = gameStore.managers.length
  if (total === 0) return 0
  let diff = idx - activeIndex.value
  const half = Math.floor(total / 2)
  if (diff > half) diff -= total
  if (diff < -half) diff += total
  return diff
}

function getCardStyle(idx) {
  const diff = normalizeOffset(idx)
  const translate = diff * 260
  const scale = Math.max(0.75, 1 - Math.abs(diff) * 0.08)
  const rotate = diff * -6
  const opacity = Math.max(0.35, 1 - Math.abs(diff) * 0.2)
  const blur = Math.max(0, Math.abs(diff) - 1) * 2
  return {
    transform: `translate(-50%, -50%) translateX(${translate}px) scale(${scale}) rotateY(${rotate}deg)`,
    zIndex: 100 - Math.abs(diff),
    opacity,
    filter: `blur(${blur}px)`,
  }
}

function goNext() {
  if (!gameStore.managers.length) return
  activeIndex.value = (activeIndex.value + 1) % gameStore.managers.length
  selectedManager.value = gameStore.managers[activeIndex.value]
}

function goPrev() {
  if (!gameStore.managers.length) return
  activeIndex.value = (activeIndex.value - 1 + gameStore.managers.length) % gameStore.managers.length
  selectedManager.value = gameStore.managers[activeIndex.value]
}

function handleCardClick(idx) {
  activeIndex.value = idx
  selectedManager.value = gameStore.managers[idx]
}

const debugInfo = computed(() => ({
  hasUser: !!authStore.user,
  hasProfile: !!authStore.user?.playerProfile,
  managerId: authStore.user?.playerProfile?.manager_id,
  managerName: authStore.user?.playerProfile?.manager?.name
}))
</script>

<template>
  <div class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
      
      <!-- Debug/Escape Panel -->
      <div class="game-panel p-4 mb-6 border-yellow-500/50">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="text-sm text-gray-400">
            <p>Manager ID: {{ debugInfo.managerId || 'None' }}</p>
            <p v-if="debugInfo.managerName">Manager: {{ debugInfo.managerName }}</p>
          </div>
          <div class="flex gap-2">
            <button @click="skipToDashboard" class="btn-secondary text-sm py-2 px-4">
              Force → Dashboard
            </button>
            <button @click="forceLogout" class="text-red-400 hover:text-red-300 text-sm py-2 px-4">
              Logout & Reset
            </button>
          </div>
        </div>
      </div>
      
      <!-- Error Message -->
      <div v-if="error" class="game-panel p-4 mb-6 border-red-500/50">
        <p class="text-red-400">{{ error }}</p>
      </div>
      
      <!-- Loading -->
      <div v-if="checking" class="text-center py-12">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">Checking your profile...</p>
      </div>
      
      <template v-else>
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="font-display text-3xl text-transparent bg-clip-text bg-gradient-to-r from-kpop-pink-400 to-kpop-purple-400 mb-2">
            Choose Your Manager
          </h1>
          <p class="text-gray-400">Your manager will guide your agency with unique bonuses</p>
        </div>
        
      <!-- Managers Carousel -->
      <div class="relative mb-10 min-h-[70vh] flex items-center justify-center">
        <!-- Decorative scroll hints -->
        <div class="absolute top-1/2 -translate-y-1/2 left-3 text-3xl text-white/40 pointer-events-none select-none">‹</div>
        <div class="absolute top-1/2 -translate-y-1/2 right-3 text-3xl text-white/40 pointer-events-none select-none">›</div>
        
        <div class="overflow-hidden h-[520px] w-full flex items-center justify-center">
          <div class="relative flex items-center justify-center perspective">
            <div
              v-for="(manager, idx) in gameStore.managers"
              :key="manager.id"
              class="manager-card absolute top-1/2 left-1/2 w-[300px] max-w-sm cursor-pointer transition-all duration-500 ease-out will-change-transform"
              :class="isActive(idx) ? 'opacity-100 brightness-110 saturate-110 shadow-2xl' : 'opacity-60 brightness-75 saturate-75'"
              :style="getCardStyle(idx)"
              @click="handleCardClick(idx)"
            >
              <div 
                class="game-panel min-h-[500px] p-0 flex flex-col h-full justify-end text-center border-transparent bg-gray-900/60 overflow-hidden"
                :style="getManagerCardStyle(manager)"
              >
                <div class="flex-1"></div>
                <div class="p-6 bg-gradient-to-t from-black/80 via-black/60 to-transparent">
                  <h3 class="text-2xl font-bold mb-2 drop-shadow-lg">{{ manager.name }}</h3>
                  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-black/35 border border-white/20 text-sm backdrop-blur-sm">
                    <span>{{ bonusLabels[manager.bonus_type] }}</span>
                    <span class="text-kpop-gold-300 font-bold">{{ formatBonus(manager) }}</span>
                  </div>
                  
                  <p class="text-sm text-gray-200/90 italic drop-shadow mt-4 mb-3">
                    "{{ manager.flavor_text }}"
                  </p>
                  
                  <div class="text-center">
                    <span 
                      class="px-4 py-2 rounded-full text-sm font-semibold backdrop-blur-sm"
                      :class="selectedManager?.id === manager.id ? 'bg-kpop-pink-500/30 text-white border border-kpop-pink-200/60' : 'bg-black/35 text-gray-100 border border-white/20'"
                    >
                      {{ selectedManager?.id === manager.id ? 'Selected' : 'Tap to select' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
        
        <!-- Confirm Button -->
        <div class="text-center">
          <button
            @click="confirmSelection"
            class="btn-primary text-lg px-12"
            :disabled="!selectedManager || isSelecting"
          >
            <span v-if="isSelecting" class="flex items-center gap-2">
              <span class="spinner"></span>
              Selecting...
            </span>
            <span v-else>
              {{ selectedManager ? `Start with ${selectedManager.name}` : 'Select a Manager' }}
            </span>
          </button>
        </div>
      </template>
    </div>
  </div>
</template>
