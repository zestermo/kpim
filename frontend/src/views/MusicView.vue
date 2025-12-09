<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useGameStore } from '../stores/game'

const gameStore = useGameStore()

const showProduceModal = ref(false)
const selectedGroupId = ref(null)
const selectedGenre = ref('pop')
const songTitle = ref('')

const genres = [
  { value: 'pop', label: '🎤 Pop', description: 'Catchy and mainstream' },
  { value: 'dance', label: '💃 Dance', description: 'Upbeat and energetic' },
  { value: 'ballad', label: '🎹 Ballad', description: 'Emotional and slow' },
  { value: 'hiphop', label: '🎧 Hip-Hop', description: 'Rhythmic and bold' },
  { value: 'rnb', label: '🎷 R&B', description: 'Smooth and soulful' },
  { value: 'edm', label: '🎛️ EDM', description: 'Electronic beats' },
  { value: 'rock', label: '🎸 Rock', description: 'Powerful guitars' },
]

const SONG_COST = 8000

let refreshInterval = null

onMounted(async () => {
  await Promise.all([
    gameStore.fetchSongs(),
    gameStore.fetchGroups()
  ])
  
  // Refresh songs periodically to check for completion
  refreshInterval = setInterval(async () => {
    const inProduction = gameStore.songs.filter(s => s.is_in_production)
    for (const song of inProduction) {
      await gameStore.checkSongCompletion(song.id)
    }
  }, 5000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})

const canProduceSong = computed(() => {
  return gameStore.groups.length > 0 && gameStore.player?.money >= SONG_COST
})

const canSubmitSong = computed(() => {
  return selectedGroupId.value !== null
})

const songsInProduction = computed(() => 
  gameStore.songs.filter(s => s.is_in_production)
)

const completedSongs = computed(() => 
  gameStore.songs.filter(s => s.is_completed)
)

const nowPlaying = computed(() => gameStore.nowPlaying)

function openProduceModal() {
  selectedGroupId.value = gameStore.groups[0]?.id || null
  selectedGenre.value = 'pop'
  songTitle.value = ''
  showProduceModal.value = true
}

async function handleProduceSong() {
  const result = await gameStore.produceSong(
    selectedGroupId.value,
    selectedGenre.value,
    songTitle.value || null
  )
  
  if (result.success) {
    showProduceModal.value = false
  }
}

async function debugProduceInstant() {
  const firstGroup = gameStore.groups[0]
  if (!firstGroup) return
  await gameStore.produceSong(firstGroup.id, 'pop', `Debug Track ${Date.now()}`, true)
  await gameStore.fetchSongs()
}

function getGenreInfo(genreValue) {
  return genres.find(g => g.value === genreValue) || genres[0]
}

function getTimeRemaining(endsAt) {
  const now = new Date()
  const end = new Date(endsAt)
  const diff = Math.max(0, end - now)
  
  const minutes = Math.floor(diff / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
}

function getGroupById(id) {
  return gameStore.groups.find(g => g.id === id)
}

function playSong(song) {
  if (!song.audio_url) return
  gameStore.playSongAudio(song)
}

function pauseSong() {
  gameStore.pauseSongAudio()
}

function restartSong() {
  gameStore.restartSongAudio()
}

function isCurrent(song) {
  return nowPlaying.value?.song?.id === song.id
}

function volumeLabel(vol) {
  return Math.round(vol * 100)
}

function setVolume(evt) {
  const vol = Number(evt.target.value) / 100
  gameStore.setAudioVolume(vol)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold">Music Production</h1>
        <p class="text-gray-400">{{ completedSongs.length }} songs ready for promotion</p>
      </div>
      
      <button 
        @click="openProduceModal"
        class="btn-primary flex items-center gap-2"
        :disabled="!canProduceSong"
      >
        <span>🎵</span>
        Produce New Song
        <span class="text-sm opacity-75">(💰 {{ SONG_COST.toLocaleString() }})</span>
      </button>

      <button
        v-if="gameStore.groups.length > 0"
        @click="debugProduceInstant"
        class="btn-secondary flex items-center gap-2 text-xs"
        title="Debug: instant-complete song"
      >
        ⚡ Debug Song
      </button>
    </div>
    
    <!-- No Groups Warning -->
    <div v-if="gameStore.groups.length === 0" class="game-panel p-4 border-yellow-500/50">
      <p class="text-yellow-400 flex items-center gap-2">
        <span>⚠️</span>
        You need a group to produce music.
        <router-link to="/groups" class="underline">Form a group first</router-link>
      </p>
    </div>
    
    <!-- Songs In Production -->
    <div v-if="songsInProduction.length > 0" class="game-panel p-6">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span class="animate-pulse">⏳</span> In Production
      </h2>
      
      <div class="space-y-3">
        <div 
          v-for="song in songsInProduction" 
          :key="song.id"
          class="flex items-center justify-between p-4 bg-gray-800/50 rounded-xl"
        >
          <div class="flex items-center gap-4">
            <div class="text-2xl">{{ getGenreInfo(song.genre).label.split(' ')[0] }}</div>
            <div>
              <p class="font-bold">{{ song.title }}</p>
              <p class="text-sm text-gray-400">{{ getGroupById(song.group_id)?.name }}</p>
            </div>
          </div>
          
          <div class="text-right">
            <div class="text-kpop-pink-400 font-mono text-lg">
              {{ getTimeRemaining(song.production_ends_at) }}
            </div>
            <div class="text-xs text-gray-400">remaining</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Completed Songs -->
    <div v-if="completedSongs.length > 0">
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span>✅</span> Ready for Promotion
      </h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div 
          v-for="song in completedSongs" 
          :key="song.id"
          class="game-panel p-5"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="text-3xl">{{ getGenreInfo(song.genre).label.split(' ')[0] }}</div>
              <div>
                <h3 class="font-bold text-lg">{{ song.title }}</h3>
                <p class="text-sm text-gray-400">{{ getGroupById(song.group_id)?.name }}</p>
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <div class="text-xs text-gray-400 mb-1">Quality</div>
              <div class="stat-bar">
                <div 
                  class="stat-bar-fill bg-gradient-to-r from-blue-500 to-cyan-500"
                  :style="{ width: `${song.quality}%` }"
                ></div>
              </div>
              <div class="text-right text-sm mt-1">{{ song.quality }}</div>
            </div>
            
            <div>
              <div class="text-xs text-gray-400 mb-1">Hype</div>
              <div class="stat-bar">
                <div 
                  class="stat-bar-fill bg-gradient-to-r from-orange-500 to-red-500"
                  :style="{ width: `${song.hype}%` }"
                ></div>
              </div>
              <div class="text-right text-sm mt-1">{{ song.hype }}</div>
            </div>
          </div>
          
          <div class="flex items-center justify-between pt-3 border-t border-gray-700">
            <div class="text-sm text-gray-400 flex items-center gap-3 flex-wrap">
              <span>Promo Power: <span class="text-kpop-gold-400 font-bold">{{ song.promotion_power }}</span></span>
              <span v-if="song.audio_url" class="inline-flex items-center gap-2 flex-wrap">
                <button 
                  class="btn-secondary text-xs py-1 px-2"
                  @click="isCurrent(song) && nowPlaying?.isPlaying ? pauseSong() : playSong(song)"
                >
                  <span v-if="isCurrent(song) && nowPlaying?.isPlaying">⏸ Pause</span>
                  <span v-else>▶️ Play</span>
                </button>
                <button 
                  class="btn-secondary text-xs py-1 px-2"
                  @click="restartSong"
                  :disabled="!isCurrent(song)"
                >
                  ⏮ Restart
                </button>
                <div class="flex items-center gap-1 text-xs text-gray-300">
                  <span>🔊</span>
                  <input 
                    type="range" 
                    min="0" 
                    max="100" 
                    :value="Math.round(gameStore.audioVolume * 100)" 
                    @input="setVolume"
                  />
                  <span>{{ volumeLabel(gameStore.audioVolume) }}%</span>
                </div>
              </span>
              <span v-else class="text-xs text-gray-500">No audio</span>
            </div>
            <router-link to="/promotions" class="btn-primary text-sm py-2 px-4">
              Promote 📢
            </router-link>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-if="gameStore.songs.length === 0 && gameStore.groups.length > 0" class="game-panel p-12 text-center">
      <div class="text-6xl mb-4">🎵</div>
      <h2 class="text-xl font-bold mb-2">No Songs Yet</h2>
      <p class="text-gray-400 mb-6">Produce your first song to start gaining fans!</p>
      <button 
        @click="openProduceModal"
        class="btn-primary"
        :disabled="!canProduceSong"
      >
        Produce Your First Song
      </button>
    </div>
    
    <!-- Produce Song Modal -->
    <Teleport to="body">
      <div v-if="showProduceModal" class="modal-backdrop" @click.self="showProduceModal = false">
        <div class="game-panel p-6 max-w-lg w-full mx-4">
          <h2 class="text-2xl font-bold mb-6 text-center">🎵 Produce New Song</h2>
          
          <!-- Group Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Select Group</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="group in gameStore.groups"
                :key="group.id"
                @click="selectedGroupId = group.id"
                class="p-3 rounded-xl border-2 transition-all text-left"
                :class="selectedGroupId === group.id 
                  ? 'border-kpop-pink-500 bg-kpop-pink-500/10' 
                  : 'border-gray-600 hover:border-gray-500'"
              >
                <div class="font-bold">{{ group.name }}</div>
                <div class="text-xs text-gray-400">⭐ {{ group.average_star_power }}</div>
              </button>
            </div>
          </div>
          
          <!-- Genre Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Genre</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                v-for="genre in genres"
                :key="genre.value"
                @click="selectedGenre = genre.value"
                class="p-2 rounded-xl border-2 transition-all text-center"
                :class="selectedGenre === genre.value 
                  ? 'border-kpop-pink-500 bg-kpop-pink-500/10' 
                  : 'border-gray-600 hover:border-gray-500'"
              >
                <div class="text-lg">{{ genre.label.split(' ')[0] }}</div>
                <div class="text-xs text-gray-400">{{ genre.label.split(' ').slice(1).join(' ') }}</div>
              </button>
            </div>
          </div>
          
          <!-- Song Title (optional) -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">
              Song Title <span class="text-gray-500">(optional - will auto-generate)</span>
            </label>
            <input
              v-model="songTitle"
              type="text"
              class="input-field"
              placeholder="Enter a song title..."
              maxlength="100"
            />
          </div>
          
          <!-- Actions -->
          <div class="flex gap-3">
            <button 
              @click="showProduceModal = false" 
              class="btn-secondary flex-1"
            >
              Cancel
            </button>
            <button 
              @click="handleProduceSong"
              class="btn-primary flex-1"
              :disabled="!canSubmitSong || gameStore.loading"
            >
              <span v-if="gameStore.loading" class="flex items-center justify-center gap-2">
                <span class="spinner"></span>
                Starting...
              </span>
              <span v-else>Start Production 🎶</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

