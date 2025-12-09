<script setup>
import { ref, onMounted, computed } from 'vue'
import { useGameStore } from '../stores/game'
import IdolCard from '../components/IdolCard.vue'

const gameStore = useGameStore()
const scouting = ref(false)
const newIdol = ref(null)
const packModal = ref(false)
const revealIndex = ref(0)
const selectedPackIndex = ref(null)
const PACK_SIZE = 5

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

const packCost = computed(() => gameStore.packDraft?.cost || SCOUT_COST * PACK_SIZE)
const packId = computed(() => gameStore.packDraft?.pack_id)
const packIdols = computed(() => gameStore.packDraft?.idols || [])

async function openPack() {
  revealIndex.value = 0
  selectedPackIndex.value = null
  packModal.value = true
  const result = await gameStore.openIdolPack()
  if (!result.success) {
    packModal.value = false
  }
}

function revealNext() {
  if (revealIndex.value < PACK_SIZE) {
    revealIndex.value++
  }
}

async function chooseIdol(idx) {
  if (!packId.value) return
  selectedPackIndex.value = idx
  const result = await gameStore.chooseIdolFromPack(packId.value, idx)
  if (result.success) {
    packModal.value = false
    revealIndex.value = 0
    selectedPackIndex.value = null
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold">Your Idols</h1>
        <p class="text-gray-400">{{ gameStore.idols.length }} trainees in your agency</p>
      </div>
      
      <div class="flex flex-wrap gap-3">
        <button 
          @click="openPack"
          class="btn-primary flex items-center gap-2"
          :disabled="gameStore.packLoading || gameStore.player?.money < packCost"
        >
          <span v-if="gameStore.packLoading" class="spinner"></span>
          <span v-else>🎴</span>
          Open Idol Pack
          <span class="text-sm opacity-75">(💰 {{ packCost.toLocaleString() }})</span>
        </button>
        <button 
          @click="handleScout"
          class="btn-secondary flex items-center gap-2"
          :disabled="scouting || gameStore.player?.money < SCOUT_COST"
        >
          <span v-if="scouting" class="spinner"></span>
          <span v-else>🔍</span>
          Scout Single
          <span class="text-sm opacity-75">(💰 {{ SCOUT_COST.toLocaleString() }})</span>
        </button>
      </div>
    </div>
    
    <!-- Insufficient Funds Warning -->
    <div v-if="gameStore.player?.money < Math.min(SCOUT_COST, packCost)" class="game-panel p-4 border-yellow-500/50">
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

    <!-- Pack Modal -->
    <Teleport to="body">
      <div v-if="packModal" class="modal-backdrop" @click.self="packModal = false">
        <div class="game-panel p-6 w-full max-w-5xl mx-4 max-h-[90vh] overflow-hidden flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold flex items-center gap-2">🎴 Idol Pack</h2>
              <p class="text-gray-400 text-sm">Reveal 5 idols, keep one.</p>
            </div>
            <button class="text-gray-400 hover:text-white" @click="packModal = false">✕</button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="(idol, idx) in packIdols"
                :key="idx"
                class="relative"
              >
                <transition name="fade" mode="out-in">
                  <div v-if="idx < revealIndex" class="cursor-pointer" @click="selectedPackIndex = idx">
                    <IdolCard :idol="idol" :selectable="true" :selected="selectedPackIndex === idx" />
                  </div>
                  <div v-else class="game-panel h-[500px] flex items-center justify-center text-4xl text-gray-500 bg-gray-800/80">
                    ❓
                  </div>
                </transition>
              </div>
            </div>

            <div class="space-y-3">
              <p class="text-gray-300">Pack Cost: 💰 {{ packCost.toLocaleString() }}</p>
              <p class="text-gray-400 text-sm">Reveal each card, then choose one idol to keep.</p>
              
              <button
                v-if="revealIndex < PACK_SIZE"
                class="btn-primary w-full"
                :disabled="gameStore.packLoading"
                @click="revealNext"
              >
                <span v-if="gameStore.packLoading" class="spinner"></span>
                <span v-else>Reveal Card {{ revealIndex + 1 }} / {{ PACK_SIZE }}</span>
              </button>

              <button
                v-else
                class="btn-gold w-full"
                :disabled="selectedPackIndex === null || gameStore.packLoading"
                @click="chooseIdol(selectedPackIndex)"
              >
                <span v-if="gameStore.packLoading" class="spinner"></span>
                <span v-else>Keep Selected Idol</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

