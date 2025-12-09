<script setup>
import { ref, onMounted, computed } from 'vue'
import { useGameStore } from '../stores/game'
import IdolCard from '../components/IdolCard.vue'

const gameStore = useGameStore()
const packModal = ref(false)
const revealIndex = ref(0)
const showFront = ref(false)
const selectedPackIndex = ref(null)
const activityOpen = ref(false)
const PACK_SIZE = 5
const PACK_BASE_COST = 2500
const selectionIndex = ref(0)
const stage = ref('loading') // 'loading' | 'revealing' | 'selecting'

onMounted(async () => {
  await gameStore.fetchIdols()
})

const packCost = computed(() => gameStore.packDraft?.cost || PACK_BASE_COST)
const packId = computed(() => gameStore.packDraft?.pack_id)
const packIdolsRaw = computed(() => gameStore.packDraft?.idols || [])

function spriteToImage(spriteKey) {
  const match = /idol_(\d+)/.exec(spriteKey || '')
  if (match) {
    const num = Math.min(12, Math.max(1, parseInt(match[1], 10)))
    return `/images/idols/${num}.png`
  }
  return null
}

// map sprite_key to stable images so pack and owned view match
const packIdols = computed(() => {
  return packIdolsRaw.value.map((idol) => {
    const img = spriteToImage(idol.sprite_key)
    return {
      ...idol,
      image_url: idol.image_url || img
    }
  })
})

async function openPack() {
  revealIndex.value = 0
  showFront.value = false
  selectedPackIndex.value = null
  selectionIndex.value = 0
  stage.value = 'loading'
  packModal.value = true
  const result = await gameStore.openIdolPack()
  if (!result.success) {
    packModal.value = false
    stage.value = 'loading'
    return
  }
  stage.value = 'revealing'
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

function handleRevealClick() {
  if (stage.value !== 'revealing') return
  if (!packIdols.value.length) return
  if (!showFront.value) {
    showFront.value = true
    return
  }
  if (revealIndex.value < PACK_SIZE - 1) {
    revealIndex.value++
    showFront.value = false
  } else {
    stage.value = 'selecting'
    selectionIndex.value = 0
    selectedPackIndex.value = 0
  }
}

const inSelectionStage = computed(() => stage.value === 'selecting')
const currentIdol = computed(() => packIdols.value[revealIndex.value] || null)

function normalizeOffset(idx) {
  const total = packIdols.value.length
  if (total === 0) return 0
  let diff = idx - selectionIndex.value
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
    filter: `blur(${blur}px)`
  }
}

function goNext() {
  if (!packIdols.value.length) return
  selectionIndex.value = (selectionIndex.value + 1) % packIdols.value.length
}

function goPrev() {
  if (!packIdols.value.length) return
  selectionIndex.value = (selectionIndex.value - 1 + packIdols.value.length) % packIdols.value.length
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
      </div>
    </div>
    
    <!-- Insufficient Funds Warning -->
    <div v-if="gameStore.player?.money < packCost" class="game-panel p-4 border-yellow-500/50">
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
      <p class="text-gray-400 mb-6">Open a pack to recruit your first idol.</p>
      <button 
        @click="openPack"
        class="btn-primary"
        :disabled="gameStore.packLoading || gameStore.player?.money < packCost"
      >
        Open an Idol Pack (💰 {{ packCost.toLocaleString() }})
      </button>
    </div>
    
    <!-- New Idol Modal -->
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

          <div v-if="packIdols.length === 0" class="flex items-center justify-center py-12">
            <div class="spinner"></div>
          </div>
          <template v-else>
            <!-- Reveal Flow -->
            <div v-if="!inSelectionStage" class="flex flex-col items-center justify-center py-8 gap-4">
              <div 
                class="w-72 h-[520px] relative"
                @click="handleRevealClick"
              >
                <!-- Back of card (question mark) -->
                <transition name="flip">
                  <div 
                    v-if="!showFront"
                    key="back"
                    class="absolute inset-0 cursor-pointer"
                  >
                    <div class="game-panel w-full h-full flex items-center justify-center text-4xl text-gray-400 animate-pulse">
                      ❓
                    </div>
                  </div>
                </transition>
                <!-- Front of card (idol) -->
                <transition name="flip">
                  <div 
                    v-if="showFront && currentIdol"
                    key="front"
                    class="absolute inset-0"
                  >
                    <IdolCard :idol="currentIdol" />
                  </div>
                </transition>
              </div>
              <p class="text-gray-400 text-sm">Card {{ revealIndex + 1 }} / {{ PACK_SIZE }} — tap to flip, tap again for next.</p>
            </div>

            <!-- Selection carousel -->
            <div v-if="inSelectionStage" class="relative mb-4 min-h-[520px] flex items-center justify-center">
              <div class="absolute top-1/2 -translate-y-1/2 left-0 flex items-center pl-2 z-50">
                <button @click="goPrev" class="btn-secondary px-3 py-2 rounded-full shadow-lg">‹</button>
              </div>
              <div class="absolute top-1/2 -translate-y-1/2 right-0 flex items-center pr-2 z-50">
                <button @click="goNext" class="btn-secondary px-3 py-2 rounded-full shadow-lg">›</button>
              </div>
              
              <div class="overflow-hidden w-full h-[520px] flex items-center justify-center">
                <div class="relative flex items-center justify-center perspective w-full h-full">
                  <div
                    v-for="(idol, idx) in packIdols"
                    :key="idx"
                    class="absolute top-1/2 left-1/2 w-[280px] max-w-sm cursor-pointer transition-all duration-500 ease-out will-change-transform"
                    :style="getCardStyle(idx)"
                    @click="selectedPackIndex = idx"
                    :class="selectedPackIndex === idx ? 'opacity-100 brightness-110 saturate-110 shadow-2xl ring-2 ring-kpop-pink-500' : 'opacity-70 brightness-90'"
                  >
                    <IdolCard :idol="idol" :selectable="true" :selected="selectedPackIndex === idx" />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-4 border-t border-gray-700">
            <p class="text-gray-300">Pack Cost: 💰 {{ packCost.toLocaleString() }}</p>
            <button
              v-if="inSelectionStage"
              class="btn-gold"
              :disabled="selectedPackIndex === null || gameStore.packLoading"
              @click="chooseIdol(selectedPackIndex)"
            >
              <span v-if="gameStore.packLoading" class="spinner"></span>
              <span v-else>Keep Selected Idol</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Activity Log -->
    <div class="fixed bottom-4 right-4 z-40">
      <button 
        class="btn-secondary px-4 py-2 rounded-full shadow-lg flex items-center gap-2"
        @click="activityOpen = !activityOpen"
      >
        📰 Activity
        <span class="text-xs text-gray-200">({{ gameStore.eventLog.length }})</span>
      </button>
      <transition name="fade">
        <div 
          v-if="activityOpen"
          class="game-panel p-4 mt-3 w-80 max-h-80 overflow-y-auto"
        >
          <div class="flex items-center justify-between mb-2">
            <h2 class="text-lg font-bold">Recent Activity</h2>
            <button class="text-gray-400 hover:text-white text-sm" @click="activityOpen = false">✕</button>
          </div>
          <p class="text-gray-400 text-xs mb-2">Last 25 events.</p>
          <div class="space-y-2">
            <p v-if="gameStore.eventLog.length === 0" class="text-gray-500 text-sm">No activity yet.</p>
            <div 
              v-for="(ev, idx) in gameStore.eventLog"
              :key="idx"
              class="bg-gray-800/60 rounded-lg px-3 py-2 text-sm border border-white/5"
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
  </div>
</template>

<style scoped>
.flip-enter-active,
.flip-leave-active {
  transition: all 0.5s ease;
  transform-style: preserve-3d;
}

.flip-enter-from {
  opacity: 0;
  transform: rotateY(90deg) scale(0.95);
}

.flip-leave-to {
  opacity: 0;
  transform: rotateY(-90deg) scale(0.95);
}

.perspective {
  perspective: 1000px;
}
</style>

