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
        
        <!-- Managers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
          <div
            v-for="manager in gameStore.managers"
            :key="manager.id"
            class="game-panel p-6 cursor-pointer transition-all duration-300"
            :class="{
              'ring-2 ring-kpop-pink-500 scale-105': selectedManager?.id === manager.id,
              'hover:border-kpop-purple-400': selectedManager?.id !== manager.id
            }"
            @click="selectedManager = manager"
          >
            <!-- Avatar -->
            <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gradient-to-br from-kpop-pink-500/20 to-kpop-purple-500/20 flex items-center justify-center text-4xl">
              👔
            </div>
            
            <!-- Name -->
            <h3 class="text-xl font-bold text-center mb-2">{{ manager.name }}</h3>
            
            <!-- Bonus -->
            <div class="bg-kpop-purple-500/20 rounded-lg px-4 py-2 mb-3 text-center">
              <span class="text-sm text-gray-300">{{ bonusLabels[manager.bonus_type] }}</span>
              <span class="ml-2 font-bold text-kpop-gold-400">{{ formatBonus(manager) }}</span>
            </div>
            
            <!-- Flavor text -->
            <p class="text-sm text-gray-400 text-center italic">
              "{{ manager.flavor_text }}"
            </p>
            
            <!-- Selection indicator -->
            <div v-if="selectedManager?.id === manager.id" class="mt-4 text-center">
              <span class="text-kpop-pink-400 font-medium">✓ Selected</span>
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
