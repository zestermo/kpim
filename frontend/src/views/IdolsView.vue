<script setup>
import { ref, onMounted } from 'vue'
import { useGameStore } from '../stores/game'
import IdolCard from '../components/IdolCard.vue'

const gameStore = useGameStore()
const scouting = ref(false)
const newIdol = ref(null)

onMounted(async () => {
  await gameStore.fetchIdols()
})

async function handleScout() {
  scouting.value = true
  newIdol.value = null
  
  const result = await gameStore.scoutIdol()
  
  if (result.success) {
    newIdol.value = result.idol
  }
  
  scouting.value = false
}

function closeNewIdolModal() {
  newIdol.value = null
}

const SCOUT_COST = 5000
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold">Your Idols</h1>
        <p class="text-gray-400">{{ gameStore.idols.length }} trainees in your agency</p>
      </div>
      
      <button 
        @click="handleScout"
        class="btn-primary flex items-center gap-2"
        :disabled="scouting || gameStore.player?.money < SCOUT_COST"
      >
        <span v-if="scouting" class="spinner"></span>
        <span v-else>🔍</span>
        Scout New Idol
        <span class="text-sm opacity-75">(💰 {{ SCOUT_COST.toLocaleString() }})</span>
      </button>
    </div>
    
    <!-- Insufficient Funds Warning -->
    <div v-if="gameStore.player?.money < SCOUT_COST" class="game-panel p-4 border-yellow-500/50">
      <p class="text-yellow-400 flex items-center gap-2">
        <span>⚠️</span>
        Not enough money to scout. Run promotions to earn more!
      </p>
    </div>
    
    <!-- Idols Grid -->
    <div v-if="gameStore.idols.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <IdolCard
        v-for="idol in gameStore.idols"
        :key="idol.id"
        :idol="idol"
      />
    </div>
    
    <!-- Empty State -->
    <div v-else class="game-panel p-12 text-center">
      <div class="text-6xl mb-4">🌟</div>
      <h2 class="text-xl font-bold mb-2">No Idols Yet</h2>
      <p class="text-gray-400 mb-6">Scout your first idol to begin building your K-Pop empire!</p>
      <button 
        @click="handleScout"
        class="btn-primary"
        :disabled="scouting || gameStore.player?.money < SCOUT_COST"
      >
        Scout Your First Idol
      </button>
    </div>
    
    <!-- New Idol Modal -->
    <Teleport to="body">
      <div v-if="newIdol" class="modal-backdrop" @click.self="closeNewIdolModal">
        <div class="game-panel p-8 max-w-sm w-full mx-4 animate-float">
          <div class="text-center mb-6">
            <div class="text-5xl mb-2">🎉</div>
            <h2 class="text-2xl font-bold">New Idol Found!</h2>
          </div>
          
          <IdolCard :idol="newIdol" />
          
          <button @click="closeNewIdolModal" class="btn-primary w-full mt-6">
            Welcome to the Agency!
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

