<script setup>
import { computed } from 'vue'

const props = defineProps({
  idol: {
    type: Object,
    required: true
  },
  selectable: {
    type: Boolean,
    default: false
  },
  selected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select'])

const rarityColors = {
  common: 'from-gray-400 to-gray-500',
  uncommon: 'from-green-400 to-green-500',
  rare: 'from-blue-400 to-blue-500',
  epic: 'from-purple-400 to-purple-500',
  legendary: 'from-yellow-400 to-orange-500'
}

const rarityClass = computed(() => rarityColors[props.idol.rarity] || rarityColors.common)

const idolImages = Array.from({ length: 12 }, (_, i) => `/images/idols/${i + 1}.png`)

function hashString(str) {
  return str.split('').reduce((acc, char) => ((acc << 5) - acc) + char.charCodeAt(0), 0)
}

function spriteToImage(spriteKey) {
  const match = /idol_(\d+)/.exec(spriteKey || '')
  if (match) {
    const num = Math.min(12, Math.max(1, parseInt(match[1], 10)))
    return `/images/idols/${num}.png`
  }
  return null
}

const idolImage = computed(() => {
  if (props.idol.image_url) return props.idol.image_url
  const spriteImg = spriteToImage(props.idol.sprite_key)
  if (spriteImg) return spriteImg
  
  const seedBase = props.idol.id ?? hashString(props.idol.name || '')
  const index = Math.abs(seedBase) % idolImages.length
  return idolImages[index]
})

const starPower = computed(() => {
  const { vocal, dance, visual, charm, stamina } = props.idol
  return Math.round(
    (vocal * 0.25) +
    (dance * 0.25) +
    (visual * 0.20) +
    (charm * 0.20) +
    (stamina * 0.10)
  )
})

function handleClick() {
  if (props.selectable) {
    emit('select', props.idol)
  }
}
</script>

<template>
  <div 
    class="idol-card relative overflow-hidden min-h-[500px] w-[280px] max-w-sm mx-auto flex flex-col justify-end"
    :class="{ 
      'ring-2 ring-kpop-pink-500': selected,
      'cursor-pointer': selectable 
    }"
    @click="handleClick"
  >
    <div 
      class="absolute inset-0 bg-cover bg-center"
      :style="{ backgroundImage: `linear-gradient(180deg, rgba(12,12,20,0) 0%, rgba(12,12,20,0) 60%, rgba(12,12,20,0.8) 100%), url(${idolImage})`, backgroundSize: 'contain', backgroundRepeat: 'no-repeat' }"
    ></div>
    
    <!-- Rarity indicator -->
    <div 
      class="absolute top-0 left-0 right-0 h-1 rounded-t-xl bg-gradient-to-r mix-blend-screen"
      :class="rarityClass"
    ></div>
    
    <!-- Selection checkbox -->
    <div v-if="selectable" class="absolute top-3 right-3 z-10">
      <div 
        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors bg-black/50 backdrop-blur"
        :class="selected ? 'bg-kpop-pink-500 border-kpop-pink-500' : 'border-gray-500'"
      >
        <span v-if="selected" class="text-white text-sm">✓</span>
      </div>
    </div>
    
    <div class="relative z-10 p-5 pt-12 space-y-3">
      <div class="text-center">
        <h3 class="font-bold text-xl mb-1 drop-shadow">{{ idol.name }}</h3>
        <p class="text-sm capitalize inline-flex items-center gap-2 px-3 py-1 rounded-full bg-black/40 border border-white/10">
          <span :class="`rarity-${idol.rarity}`">{{ idol.rarity }}</span>
        </p>
      </div>
      
      <!-- Stats -->
      <div class="space-y-2 bg-black/35 backdrop-blur-sm p-4 rounded-xl border border-white/10">
        <div class="flex items-center gap-2">
          <span class="text-xs w-14 text-gray-300">Vocal</span>
          <div class="stat-bar flex-1">
            <div 
              class="stat-bar-fill bg-gradient-to-r from-pink-500 to-rose-500" 
              :style="{ width: `${idol.vocal}%` }"
            ></div>
          </div>
          <span class="text-xs w-8 text-right text-gray-200">{{ idol.vocal }}</span>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-xs w-14 text-gray-300">Dance</span>
          <div class="stat-bar flex-1">
            <div 
              class="stat-bar-fill bg-gradient-to-r from-blue-500 to-cyan-500" 
              :style="{ width: `${idol.dance}%` }"
            ></div>
          </div>
          <span class="text-xs w-8 text-right text-gray-200">{{ idol.dance }}</span>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-xs w-14 text-gray-300">Visual</span>
          <div class="stat-bar flex-1">
            <div 
              class="stat-bar-fill bg-gradient-to-r from-purple-500 to-violet-500" 
              :style="{ width: `${idol.visual}%` }"
            ></div>
          </div>
          <span class="text-xs w-8 text-right text-gray-200">{{ idol.visual }}</span>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-xs w-14 text-gray-300">Charm</span>
          <div class="stat-bar flex-1">
            <div 
              class="stat-bar-fill bg-gradient-to-r from-yellow-500 to-orange-500" 
              :style="{ width: `${idol.charm}%` }"
            ></div>
          </div>
          <span class="text-xs w-8 text-right text-gray-200">{{ idol.charm }}</span>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-xs w-14 text-gray-300">Stamina</span>
          <div class="stat-bar flex-1">
            <div 
              class="stat-bar-fill bg-gradient-to-r from-green-500 to-emerald-500" 
              :style="{ width: `${idol.stamina}%` }"
            ></div>
          </div>
          <span class="text-xs w-8 text-right text-gray-200">{{ idol.stamina }}</span>
        </div>
      </div>
      
      <!-- Star Power -->
      <div class="pt-3 border-t border-white/10 flex items-center justify-between text-white drop-shadow">
        <span class="text-sm text-gray-200">Star Power</span>
        <span class="font-bold text-kpop-gold-400">⭐ {{ starPower }}</span>
      </div>
    </div>
  </div>
</template>

