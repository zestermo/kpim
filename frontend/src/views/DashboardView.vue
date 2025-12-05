<script setup>
import { computed, onMounted } from 'vue'
import { useGameStore } from '../stores/game'
import { useAuthStore } from '../stores/auth'

const gameStore = useGameStore()
const authStore = useAuthStore()

onMounted(async () => {
  await Promise.all([
    gameStore.fetchIdols(),
    gameStore.fetchGroups(),
    gameStore.fetchSongs(),
    gameStore.fetchPromotions()
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

const activePromotions = computed(() => 
  gameStore.promotions.filter(p => p.is_active || p.is_ready)
)

const recentActivity = computed(() => {
  const activities = []
  
  // Recent promotions
  gameStore.promotions.slice(0, 3).forEach(p => {
    if (p.is_completed) {
      activities.push({
        icon: p.went_viral ? '🔥' : '📢',
        text: `Completed ${p.type.replace('_', ' ')}`,
        detail: p.went_viral ? 'Went viral!' : `+${p.fan_reward} fans`
      })
    }
  })
  
  return activities
})
</script>

<template>
  <div class="space-y-6">
    <!-- Agency Header -->
    <div class="game-panel p-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-kpop-pink-400 to-kpop-purple-400">
            {{ player?.agency_name || 'My Agency' }}
          </h1>
          <p class="text-gray-400 mt-1">
            Level {{ player?.level || 1 }} • Managed by {{ manager?.name || 'Unknown' }}
          </p>
        </div>
        
        <div class="flex items-center gap-2 px-4 py-2 bg-kpop-purple-500/20 rounded-xl">
          <span class="text-lg">👔</span>
          <div>
            <p class="text-sm text-gray-300">{{ manager?.name }}</p>
            <p class="text-xs text-kpop-gold-400">
              +{{ Math.round((manager?.bonus_value || 0) * 100) }}% {{ manager?.bonus_type?.replace('_', ' ') }}
            </p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="game-panel p-4 text-center">
        <div class="text-3xl mb-2">⭐</div>
        <div class="text-2xl font-bold">{{ stats.idols }}</div>
        <div class="text-sm text-gray-400">Idols</div>
      </div>
      
      <div class="game-panel p-4 text-center">
        <div class="text-3xl mb-2">👥</div>
        <div class="text-2xl font-bold">{{ stats.groups }}</div>
        <div class="text-sm text-gray-400">Groups</div>
      </div>
      
      <div class="game-panel p-4 text-center">
        <div class="text-3xl mb-2">🎵</div>
        <div class="text-2xl font-bold">{{ stats.completedSongs }}</div>
        <div class="text-sm text-gray-400">Songs</div>
      </div>
      
      <div class="game-panel p-4 text-center">
        <div class="text-3xl mb-2">💖</div>
        <div class="text-2xl font-bold">{{ gameStore.formattedFans }}</div>
        <div class="text-sm text-gray-400">Total Fans</div>
      </div>
    </div>
    
    <!-- Active Promotions -->
    <div v-if="activePromotions.length > 0" class="game-panel p-6">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span>📢</span> Active Promotions
      </h2>
      
      <div class="space-y-3">
        <div 
          v-for="promo in activePromotions" 
          :key="promo.id"
          class="flex items-center justify-between p-3 bg-gray-800/50 rounded-xl"
        >
          <div>
            <p class="font-medium">{{ promo.type.replace('_', ' ').toUpperCase() }}</p>
            <p class="text-sm text-gray-400">{{ promo.group?.name }} - {{ promo.song?.title }}</p>
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
    
    <!-- Quick Actions -->
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
      </div>
    </div>
    
    <!-- Getting Started Guide (if new) -->
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

