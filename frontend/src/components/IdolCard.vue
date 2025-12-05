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
    class="idol-card relative"
    :class="{ 
      'ring-2 ring-kpop-pink-500': selected,
      'cursor-pointer': selectable 
    }"
    @click="handleClick"
  >
    <!-- Rarity indicator -->
    <div 
      class="absolute top-0 left-0 right-0 h-1 rounded-t-xl bg-gradient-to-r"
      :class="rarityClass"
    ></div>
    
    <!-- Selection checkbox -->
    <div v-if="selectable" class="absolute top-3 right-3">
      <div 
        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
        :class="selected ? 'bg-kpop-pink-500 border-kpop-pink-500' : 'border-gray-500'"
      >
        <span v-if="selected" class="text-white text-sm">✓</span>
      </div>
    </div>
    
    <!-- Idol avatar placeholder -->
    <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-kpop-pink-500/20 to-kpop-purple-500/20 flex items-center justify-center text-3xl">
      ⭐
    </div>
    
    <!-- Name & Rarity -->
    <h3 class="font-bold text-lg text-center mb-1">{{ idol.name }}</h3>
    <p class="text-center text-sm capitalize mb-3" :class="`rarity-${idol.rarity}`">
      {{ idol.rarity }}
    </p>
    
    <!-- Stats -->
    <div class="space-y-2">
      <div class="flex items-center gap-2">
        <span class="text-xs w-14 text-gray-400">Vocal</span>
        <div class="stat-bar flex-1">
          <div 
            class="stat-bar-fill bg-gradient-to-r from-pink-500 to-rose-500" 
            :style="{ width: `${idol.vocal}%` }"
          ></div>
        </div>
        <span class="text-xs w-8 text-right">{{ idol.vocal }}</span>
      </div>
      
      <div class="flex items-center gap-2">
        <span class="text-xs w-14 text-gray-400">Dance</span>
        <div class="stat-bar flex-1">
          <div 
            class="stat-bar-fill bg-gradient-to-r from-blue-500 to-cyan-500" 
            :style="{ width: `${idol.dance}%` }"
          ></div>
        </div>
        <span class="text-xs w-8 text-right">{{ idol.dance }}</span>
      </div>
      
      <div class="flex items-center gap-2">
        <span class="text-xs w-14 text-gray-400">Visual</span>
        <div class="stat-bar flex-1">
          <div 
            class="stat-bar-fill bg-gradient-to-r from-purple-500 to-violet-500" 
            :style="{ width: `${idol.visual}%` }"
          ></div>
        </div>
        <span class="text-xs w-8 text-right">{{ idol.visual }}</span>
      </div>
      
      <div class="flex items-center gap-2">
        <span class="text-xs w-14 text-gray-400">Charm</span>
        <div class="stat-bar flex-1">
          <div 
            class="stat-bar-fill bg-gradient-to-r from-yellow-500 to-orange-500" 
            :style="{ width: `${idol.charm}%` }"
          ></div>
        </div>
        <span class="text-xs w-8 text-right">{{ idol.charm }}</span>
      </div>
      
      <div class="flex items-center gap-2">
        <span class="text-xs w-14 text-gray-400">Stamina</span>
        <div class="stat-bar flex-1">
          <div 
            class="stat-bar-fill bg-gradient-to-r from-green-500 to-emerald-500" 
            :style="{ width: `${idol.stamina}%` }"
          ></div>
        </div>
        <span class="text-xs w-8 text-right">{{ idol.stamina }}</span>
      </div>
    </div>
    
    <!-- Star Power -->
    <div class="mt-4 pt-3 border-t border-gray-700 flex items-center justify-between">
      <span class="text-sm text-gray-400">Star Power</span>
      <span class="font-bold text-kpop-gold-400">⭐ {{ starPower }}</span>
    </div>
  </div>
</template>

