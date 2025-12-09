<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const navItems = [
  { path: '/dashboard', icon: '🏠', label: 'Agency' },
  { path: '/idols', icon: '⭐', label: 'Idols' },
  { path: '/groups', icon: '👥', label: 'Groups' },
  { path: '/music', icon: '🎵', label: 'Music' },
  { path: '/promotions', icon: '📢', label: 'Promo' },
  { path: '/upgrades', icon: '⬆️', label: 'Upgrades' },
]

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-gray-900/95 backdrop-blur-sm border-t border-gray-800 z-50">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-around py-2">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="nav-tab"
          :class="{ active: route.path === item.path }"
        >
          <span class="text-2xl mb-1">{{ item.icon }}</span>
          <span class="text-xs font-medium">{{ item.label }}</span>
        </router-link>
        
        <button @click="handleLogout" class="nav-tab hover:text-red-400">
          <span class="text-2xl mb-1">🚪</span>
          <span class="text-xs font-medium">Exit</span>
        </button>
      </div>
    </div>
  </nav>
  
  <!-- Spacer for fixed nav -->
  <div class="h-20"></div>
</template>

