<script setup>
defineProps({
  label: String,
  value: Number,
  max: {
    type: Number,
    default: 100
  },
  color: {
    type: String,
    default: 'from-kpop-pink-500 to-kpop-purple-500'
  }
})
</script>

<template>
  <div class="flex items-center gap-2">
    <span v-if="label" class="text-xs w-16 text-gray-400">{{ label }}</span>
    <div class="stat-bar flex-1">
      <div 
        class="stat-bar-fill bg-gradient-to-r" 
        :class="color"
        :style="{ width: `${(value / max) * 100}%` }"
      ></div>
    </div>
    <span class="text-xs w-8 text-right font-medium">{{ value }}</span>
  </div>
</template>

