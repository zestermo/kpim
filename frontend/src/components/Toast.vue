<script setup>
import { useToastStore } from '../stores/toast'

const toastStore = useToastStore()

const typeStyles = {
  success: 'border-green-500 bg-green-500/10',
  error: 'border-red-500 bg-red-500/10',
  warning: 'border-yellow-500 bg-yellow-500/10',
  info: 'border-kpop-purple-500 bg-kpop-purple-500/10'
}

const typeIcons = {
  success: '✓',
  error: '✕',
  warning: '⚠',
  info: 'ℹ'
}
</script>

<template>
  <TransitionGroup
    name="toast"
    tag="div"
    class="fixed top-4 right-4 z-50 flex flex-col gap-2"
  >
    <div
      v-for="toast in toastStore.toasts"
      :key="toast.id"
      class="px-5 py-3 rounded-xl border-2 backdrop-blur-sm shadow-xl
             flex items-center gap-3 min-w-[280px] max-w-[400px]"
      :class="typeStyles[toast.type]"
    >
      <span class="text-xl">{{ typeIcons[toast.type] }}</span>
      <span class="flex-1 font-medium">{{ toast.message }}</span>
      <button
        @click="toastStore.removeToast(toast.id)"
        class="text-gray-400 hover:text-white transition-colors"
      >
        ✕
      </button>
    </div>
  </TransitionGroup>
</template>

<style scoped>
.toast-enter-active {
  animation: slideIn 0.3s ease-out;
}

.toast-leave-active {
  animation: slideOut 0.2s ease-in;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideOut {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(100px);
  }
}
</style>

