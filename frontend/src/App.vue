<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useGameStore } from './stores/game'
import NavBar from './components/NavBar.vue'
import Toast from './components/Toast.vue'

const route = useRoute()
const authStore = useAuthStore()
const gameStore = useGameStore()

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
          <div class="flex items-center gap-4">
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
