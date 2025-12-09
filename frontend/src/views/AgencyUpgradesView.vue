<script setup>
import { onMounted, computed } from 'vue'
import { useGameStore } from '../stores/game'

const gameStore = useGameStore()

onMounted(async () => {
  await Promise.all([
    gameStore.fetchUpgrades(),
    gameStore.fetchAvailablePromotions(), // keep promotion types fresh for unlock hints elsewhere
    gameStore.refreshPlayer(),
  ])
})

const canAfford = (upgrade) => {
  if (!upgrade.next_cost) return false
  const fans = gameStore.player?.fans || 0
  const rep = gameStore.player?.reputation || 0
  return fans >= upgrade.next_cost.fans && rep >= upgrade.next_cost.reputation
}

const bonusText = (upgrade) => {
  const pct = Math.round(upgrade.current_bonus * 100)
  switch (upgrade.type) {
    case 'promo_payout':
      return `+${pct}% promo rewards`
    case 'virality':
      return `+${pct}% viral chance`
    case 'production_speed':
      return `-${pct}% production time`
    default:
      return `Bonus: ${pct}%`
  }
}

const nextBonusText = (upgrade) => {
  const pct = Math.round((upgrade.current_bonus + upgrade.bonus_per_level) * 100)
  switch (upgrade.type) {
    case 'promo_payout':
      return `Next: +${pct}% promo rewards`
    case 'virality':
      return `Next: +${pct}% viral chance`
    case 'production_speed':
      return `Next: -${pct}% production time`
    default:
      return `Next bonus: ${pct}%`
  }
}

const remainingToMax = (upgrade) => upgrade.max_level - upgrade.level

const upgrades = computed(() => gameStore.upgrades || [])
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold">Agency Upgrades</h1>
        <p class="text-gray-400">Spend fans or reputation to unlock permanent boosts.</p>
      </div>
      <div class="text-sm text-gray-400">
        Current: 💖 {{ (gameStore.player?.fans || 0).toLocaleString() }} fans • ⭐ {{ gameStore.player?.reputation || 0 }} rep
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div
        v-for="upgrade in upgrades"
        :key="upgrade.type"
        class="game-panel p-5 border border-gray-800"
      >
        <div class="flex items-center justify-between mb-2">
          <div>
            <p class="text-sm text-gray-400 uppercase tracking-wide">{{ upgrade.type.replace('_', ' ') }}</p>
            <h3 class="text-xl font-bold">{{ upgrade.label }}</h3>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-400">Level</p>
            <p class="text-2xl font-bold">{{ upgrade.level }} / {{ upgrade.max_level }}</p>
          </div>
        </div>

        <p class="text-gray-300 text-sm mb-3">{{ upgrade.description }}</p>

        <div class="rounded-lg bg-gray-800/60 p-3 text-sm mb-3">
          <p class="font-semibold">Current Bonus: {{ bonusText(upgrade) }}</p>
          <p class="text-gray-400" v-if="upgrade.level < upgrade.max_level">{{ nextBonusText(upgrade) }}</p>
          <p class="text-green-400" v-else>Maxed out</p>
        </div>

        <div v-if="upgrade.next_cost" class="flex items-center justify-between mb-3">
          <div class="text-sm text-gray-400">
            Cost next level:
          </div>
          <div class="text-right">
            <div>💖 {{ upgrade.next_cost.fans.toLocaleString() }} fans</div>
            <div>⭐ {{ upgrade.next_cost.reputation.toLocaleString() }} rep</div>
          </div>
        </div>
        <div v-else class="text-sm text-green-400 mb-3">You reached the max level.</div>

        <button
          class="btn-primary w-full"
          :disabled="!upgrade.next_cost || !canAfford(upgrade)"
          @click="gameStore.purchaseUpgrade(upgrade.type)"
        >
          <span v-if="!upgrade.next_cost">Maxed</span>
          <span v-else-if="!canAfford(upgrade)">Need more fans/rep</span>
          <span v-else>Upgrade ({{ remainingToMax(upgrade) }} left)</span>
        </button>
      </div>
    </div>
  </div>
</template>

