<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useGameStore } from '../stores/game'

const gameStore = useGameStore()

const showStartModal = ref(false)
const selectedGroupId = ref(null)
const selectedSongId = ref(null)
const selectedType = ref(null)
const promotionTypes = ref([])

let refreshInterval = null

onMounted(async () => {
  await Promise.all([
    gameStore.fetchPromotions(),
    gameStore.fetchGroups(),
    gameStore.fetchSongs(),
    loadPromotionTypes()
  ])
  
  // Refresh promotions periodically
  refreshInterval = setInterval(() => {
    gameStore.fetchPromotions()
  }, 5000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})

async function loadPromotionTypes() {
  promotionTypes.value = await gameStore.fetchAvailablePromotions()
}

const activePromotions = computed(() => 
  gameStore.promotions.filter(p => p.is_active)
)

const readyPromotions = computed(() => 
  gameStore.promotions.filter(p => p.is_ready)
)

const completedPromotions = computed(() => 
  gameStore.promotions.filter(p => p.is_completed).slice(0, 10)
)

const availableSongs = computed(() => 
  gameStore.completedSongs
)

const songsForGroup = computed(() => {
  if (!selectedGroupId.value) return []
  return availableSongs.value.filter(s => s.group_id === selectedGroupId.value)
})

const canStartPromotion = computed(() => {
  return gameStore.groups.length > 0 && availableSongs.value.length > 0
})

const canSubmitPromotion = computed(() => {
  if (!selectedGroupId.value || !selectedSongId.value || !selectedType.value) return false
  const type = promotionTypes.value.find(t => t.type === selectedType.value)
  return type && gameStore.player?.money >= type.cost
})

function openStartModal() {
  selectedGroupId.value = gameStore.groups[0]?.id || null
  selectedSongId.value = null
  selectedType.value = null
  showStartModal.value = true
  
  // Auto-select first song for group
  if (selectedGroupId.value) {
    const songs = availableSongs.value.filter(s => s.group_id === selectedGroupId.value)
    selectedSongId.value = songs[0]?.id || null
  }
}

function onGroupChange() {
  selectedSongId.value = songsForGroup.value[0]?.id || null
}

async function handleStartPromotion() {
  const result = await gameStore.startPromotion(
    selectedGroupId.value,
    selectedSongId.value,
    selectedType.value
  )
  
  if (result.success) {
    showStartModal.value = false
  }
}

async function handleCompletePromotion(promotionId) {
  await gameStore.completePromotion(promotionId)
}

function getTimeRemaining(endsAt) {
  const now = new Date()
  const end = new Date(endsAt)
  const diff = Math.max(0, end - now)
  
  const minutes = Math.floor(diff / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
}

function getPromoTypeLabel(type) {
  const labels = {
    social_post: '📱 Social Post',
    press_interview: '📰 Press Interview',
    tv_appearance: '📺 TV Appearance',
    showcase: '🎤 Showcase',
    fansign: '✍️ Fansign'
  }
  return labels[type] || type
}

function getGroupById(id) {
  return gameStore.groups.find(g => g.id === id)
}

function getSongById(id) {
  return gameStore.songs.find(s => s.id === id)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold">Promotions</h1>
        <p class="text-gray-400">Run promotions to gain fans and money</p>
      </div>
      
      <button 
        @click="openStartModal"
        class="btn-primary flex items-center gap-2"
        :disabled="!canStartPromotion"
      >
        <span>📢</span>
        Start Promotion
      </button>
    </div>
    
    <!-- Requirements Warning -->
    <div v-if="availableSongs.length === 0" class="game-panel p-4 border-yellow-500/50">
      <p class="text-yellow-400 flex items-center gap-2">
        <span>⚠️</span>
        You need a completed song to run promotions.
        <router-link to="/music" class="underline">Produce music first</router-link>
      </p>
    </div>
    
    <!-- Ready to Collect -->
    <div v-if="readyPromotions.length > 0" class="game-panel p-6 border-kpop-gold-500/50">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span class="animate-bounce">🎁</span> Ready to Collect!
      </h2>
      
      <div class="space-y-3">
        <div 
          v-for="promo in readyPromotions" 
          :key="promo.id"
          class="flex items-center justify-between p-4 bg-kpop-gold-500/10 rounded-xl border border-kpop-gold-500/30"
        >
          <div>
            <p class="font-bold">{{ getPromoTypeLabel(promo.type) }}</p>
            <p class="text-sm text-gray-400">
              {{ getGroupById(promo.group_id)?.name }} - {{ getSongById(promo.song_id)?.title }}
            </p>
            <p class="text-sm text-kpop-gold-400 mt-1">
              +{{ promo.fan_reward.toLocaleString() }} fans, +${{ promo.money_reward.toLocaleString() }}
              <span v-if="promo.went_viral" class="ml-2 text-red-400 font-bold">🔥 VIRAL!</span>
            </p>
          </div>
          
          <button 
            @click="handleCompletePromotion(promo.id)"
            class="btn-gold"
            :disabled="gameStore.loading"
          >
            Collect! 🎉
          </button>
        </div>
      </div>
    </div>
    
    <!-- Active Promotions -->
    <div v-if="activePromotions.length > 0" class="game-panel p-6">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span class="animate-pulse">⏳</span> In Progress
      </h2>
      
      <div class="space-y-3">
        <div 
          v-for="promo in activePromotions" 
          :key="promo.id"
          class="flex items-center justify-between p-4 bg-gray-800/50 rounded-xl"
        >
          <div class="flex items-center gap-4">
            <div class="text-2xl">
              {{ getPromoTypeLabel(promo.type).split(' ')[0] }}
            </div>
            <div>
              <p class="font-bold">{{ getPromoTypeLabel(promo.type).split(' ').slice(1).join(' ') }}</p>
              <p class="text-sm text-gray-400">
                {{ getGroupById(promo.group_id)?.name }} - {{ getSongById(promo.song_id)?.title }}
              </p>
            </div>
          </div>
          
          <div class="text-right">
            <div class="text-kpop-pink-400 font-mono text-lg">
              {{ getTimeRemaining(promo.ends_at) }}
            </div>
            <div class="text-xs text-gray-400">remaining</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Recent Completed -->
    <div v-if="completedPromotions.length > 0" class="game-panel p-6">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span>📋</span> Recent Activity
      </h2>
      
      <div class="space-y-2">
        <div 
          v-for="promo in completedPromotions" 
          :key="promo.id"
          class="flex items-center justify-between p-3 bg-gray-800/30 rounded-lg text-sm"
        >
          <div class="flex items-center gap-3">
            <span>{{ getPromoTypeLabel(promo.type).split(' ')[0] }}</span>
            <span class="text-gray-400">{{ getSongById(promo.song_id)?.title }}</span>
            <span v-if="promo.went_viral" class="text-red-400">🔥</span>
          </div>
          <div class="text-gray-400">
            +{{ promo.fan_reward.toLocaleString() }} fans
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-if="gameStore.promotions.length === 0 && canStartPromotion" class="game-panel p-12 text-center">
      <div class="text-6xl mb-4">📢</div>
      <h2 class="text-xl font-bold mb-2">No Promotions Yet</h2>
      <p class="text-gray-400 mb-6">Run your first promotion to gain fans!</p>
      <button 
        @click="openStartModal"
        class="btn-primary"
      >
        Start Your First Promotion
      </button>
    </div>
    
    <!-- Start Promotion Modal -->
    <Teleport to="body">
      <div v-if="showStartModal" class="modal-backdrop" @click.self="showStartModal = false">
        <div class="game-panel p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-2xl font-bold mb-6 text-center">📢 Start Promotion</h2>
          
          <!-- Group Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Select Group</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="group in gameStore.groups"
                :key="group.id"
                @click="selectedGroupId = group.id; onGroupChange()"
                class="p-3 rounded-xl border-2 transition-all text-left"
                :class="selectedGroupId === group.id 
                  ? 'border-kpop-pink-500 bg-kpop-pink-500/10' 
                  : 'border-gray-600 hover:border-gray-500'"
              >
                <div class="font-bold">{{ group.name }}</div>
              </button>
            </div>
          </div>
          
          <!-- Song Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Select Song</label>
            <div v-if="songsForGroup.length > 0" class="space-y-2">
              <button
                v-for="song in songsForGroup"
                :key="song.id"
                @click="selectedSongId = song.id"
                class="w-full p-3 rounded-xl border-2 transition-all text-left"
                :class="selectedSongId === song.id 
                  ? 'border-kpop-pink-500 bg-kpop-pink-500/10' 
                  : 'border-gray-600 hover:border-gray-500'"
              >
                <div class="font-bold">{{ song.title }}</div>
                <div class="text-sm text-gray-400">
                  Quality: {{ song.quality }} | Hype: {{ song.hype }}
                </div>
              </button>
            </div>
            <p v-else class="text-gray-400 text-center py-4">
              No songs available for this group.
              <router-link to="/music" class="text-kpop-pink-400 underline">Produce a song</router-link>
            </p>
          </div>
          
          <!-- Promotion Type Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Promotion Type</label>
            <div class="space-y-2">
              <button
                v-for="type in promotionTypes"
                :key="type.type"
                @click="selectedType = type.type"
                class="w-full p-4 rounded-xl border-2 transition-all"
                :class="[
                  selectedType === type.type 
                    ? 'border-kpop-pink-500 bg-kpop-pink-500/10' 
                    : 'border-gray-600 hover:border-gray-500',
                  gameStore.player?.money < type.cost ? 'opacity-50' : ''
                ]"
                :disabled="gameStore.player?.money < type.cost"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-bold">{{ type.name }}</div>
                    <div class="text-sm text-gray-400">
                      ~{{ type.base_fans }} fans | {{ type.duration_minutes }} min
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-kpop-gold-400">
                      💰 {{ type.cost.toLocaleString() }}
                    </div>
                  </div>
                </div>
              </button>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="flex gap-3">
            <button 
              @click="showStartModal = false" 
              class="btn-secondary flex-1"
            >
              Cancel
            </button>
            <button 
              @click="handleStartPromotion"
              class="btn-primary flex-1"
              :disabled="!canSubmitPromotion || gameStore.loading"
            >
              <span v-if="gameStore.loading" class="flex items-center justify-center gap-2">
                <span class="spinner"></span>
                Starting...
              </span>
              <span v-else>Start Promotion! 🚀</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

