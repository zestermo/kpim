<script setup>
defineProps({
  size: {
    type: String,
    default: 'md'
  }
})

const sizeClasses = {
  sm: 'w-4 h-4 border-2',
  md: 'w-8 h-8 border-3',
  lg: 'w-12 h-12 border-4'
}
</script>

<template>
  <div 
    class="rounded-full border-gray-600 border-t-kpop-pink-500 animate-spin"
    :class="sizeClasses[size]"
  ></div>
</template>

