<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGameStore } from '../stores/game'
import IdolCard from '../components/IdolCard.vue'

const gameStore = useGameStore()

const showCreateModal = ref(false)
const groupName = ref('')
const groupConcept = ref('cute')
const selectedIdolIds = ref([])

const concepts = [
  { value: 'cute', label: '💕 Cute', description: 'Sweet and adorable' },
  { value: 'girl_crush', label: '🔥 Girl Crush', description: 'Fierce and confident' },
  { value: 'elegant', label: '✨ Elegant', description: 'Sophisticated and graceful' },
  { value: 'fresh', label: '🌿 Fresh', description: 'Youthful and energetic' },
  { value: 'powerful', label: '💪 Powerful', description: 'Strong and impactful' },
  { value: 'dark', label: '🖤 Dark', description: 'Mysterious and edgy' },
  { value: 'retro', label: '📻 Retro', description: 'Classic and nostalgic' },
]

const GROUP_COST = 10000
const MIN_MEMBERS = 2
const MAX_MEMBERS = 7

onMounted(async () => {
  await Promise.all([
    gameStore.fetchGroups(),
    gameStore.fetchIdols()
  ])
})

const canCreateGroup = computed(() => {
  return gameStore.availableIdols.length >= MIN_MEMBERS &&
         gameStore.player?.money >= GROUP_COST
})

const canSubmitGroup = computed(() => {
  return groupName.value.trim().length > 0 &&
         selectedIdolIds.value.length >= MIN_MEMBERS &&
         selectedIdolIds.value.length <= MAX_MEMBERS
})

function openCreateModal() {
  groupName.value = ''
  groupConcept.value = 'cute'
  selectedIdolIds.value = []
  showCreateModal.value = true
}

function toggleIdolSelection(idol) {
  const index = selectedIdolIds.value.indexOf(idol.id)
  if (index === -1) {
    if (selectedIdolIds.value.length < MAX_MEMBERS) {
      selectedIdolIds.value.push(idol.id)
    }
  } else {
    selectedIdolIds.value.splice(index, 1)
  }
}

async function handleCreateGroup() {
  const result = await gameStore.createGroup(
    groupName.value,
    groupConcept.value,
    selectedIdolIds.value
  )
  
  if (result.success) {
    showCreateModal.value = false
  }
}

function getConceptInfo(conceptValue) {
  return concepts.find(c => c.value === conceptValue) || concepts[0]
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold">Your Groups</h1>
        <p class="text-gray-400">{{ gameStore.groups.length }} active groups</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="btn-primary flex items-center gap-2"
        :disabled="!canCreateGroup"
      >
        <span>👥</span>
        Form New Group
        <span class="text-sm opacity-75">(💰 {{ GROUP_COST.toLocaleString() }})</span>
      </button>
    </div>
    
    <!-- Requirements Warning -->
    <div v-if="gameStore.availableIdols.length < MIN_MEMBERS" class="game-panel p-4 border-yellow-500/50">
      <p class="text-yellow-400 flex items-center gap-2">
        <span>⚠️</span>
        You need at least {{ MIN_MEMBERS }} available idols to form a group.
        <router-link to="/idols" class="underline">Scout more idols</router-link>
      </p>
    </div>
    
    <!-- Groups Grid -->
    <div v-if="gameStore.groups.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div 
        v-for="group in gameStore.groups" 
        :key="group.id"
        class="game-panel p-6"
      >
        <!-- Group Header -->
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-xl font-bold">{{ group.name }}</h3>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-sm">{{ getConceptInfo(group.concept).label }}</span>
              <span class="text-gray-500">•</span>
              <span class="text-sm text-gray-400">{{ group.member_count }} members</span>
            </div>
          </div>
          
          <div class="text-right">
            <div class="text-kpop-gold-400 font-bold">⭐ {{ group.average_star_power }}</div>
            <div class="text-xs text-gray-400">Avg. Star Power</div>
          </div>
        </div>
        
        <!-- Members Preview -->
        <div class="flex flex-wrap gap-2 mb-4">
          <div 
            v-for="member in group.members" 
            :key="member.id"
            class="px-3 py-1 bg-gray-800 rounded-full text-sm"
            :class="`rarity-${member.rarity}`"
          >
            {{ member.name }}
          </div>
        </div>
        
        <!-- Group Stats -->
        <div class="grid grid-cols-3 gap-2 pt-4 border-t border-gray-700">
          <div class="text-center">
            <div class="text-lg font-bold">{{ Math.round(group.average_vocal || 0) }}</div>
            <div class="text-xs text-gray-400">Vocal</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-bold">{{ Math.round(group.average_dance || 0) }}</div>
            <div class="text-xs text-gray-400">Dance</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-bold">{{ Math.round(group.average_visual || 0) }}</div>
            <div class="text-xs text-gray-400">Visual</div>
          </div>
        </div>
        
        <!-- Popularity -->
        <div class="mt-4 flex items-center gap-2">
          <span class="text-sm text-gray-400">Popularity</span>
          <div class="stat-bar flex-1">
            <div 
              class="stat-bar-fill bg-gradient-to-r from-kpop-pink-500 to-kpop-purple-500"
              :style="{ width: `${Math.min(100, group.popularity / 100)}%` }"
            ></div>
          </div>
          <span class="text-sm font-medium">{{ group.popularity?.toLocaleString() || 0 }}</span>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="game-panel p-12 text-center">
      <div class="text-6xl mb-4">👥</div>
      <h2 class="text-xl font-bold mb-2">No Groups Yet</h2>
      <p class="text-gray-400 mb-6">Form your first group to start producing music!</p>
      <button 
        @click="openCreateModal"
        class="btn-primary"
        :disabled="!canCreateGroup"
      >
        Form Your First Group
      </button>
    </div>
    
    <!-- Create Group Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
        <div class="game-panel p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-2xl font-bold mb-6 text-center">Form New Group</h2>
          
          <!-- Group Name -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Group Name</label>
            <input
              v-model="groupName"
              type="text"
              class="input-field"
              placeholder="Enter a catchy group name..."
              maxlength="50"
            />
          </div>
          
          <!-- Concept Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">Concept</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                v-for="concept in concepts"
                :key="concept.value"
                @click="groupConcept = concept.value"
                class="p-3 rounded-xl border-2 transition-all text-center"
                :class="groupConcept === concept.value 
                  ? 'border-kpop-pink-500 bg-kpop-pink-500/10' 
                  : 'border-gray-600 hover:border-gray-500'"
              >
                <div class="text-lg">{{ concept.label.split(' ')[0] }}</div>
                <div class="text-xs text-gray-400">{{ concept.label.split(' ').slice(1).join(' ') }}</div>
              </button>
            </div>
          </div>
          
          <!-- Member Selection -->
          <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-2">
              Select Members ({{ selectedIdolIds.length }}/{{ MAX_MEMBERS }})
              <span class="text-kpop-pink-400">- Min {{ MIN_MEMBERS }} required</span>
            </label>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto p-2">
              <IdolCard
                v-for="idol in gameStore.availableIdols"
                :key="idol.id"
                :idol="idol"
                :selectable="true"
                :selected="selectedIdolIds.includes(idol.id)"
                @select="toggleIdolSelection"
              />
            </div>
            
            <p v-if="gameStore.availableIdols.length === 0" class="text-center text-gray-400 py-8">
              No available idols. Scout more or remove idols from existing groups.
            </p>
          </div>
          
          <!-- Actions -->
          <div class="flex gap-3">
            <button 
              @click="showCreateModal = false" 
              class="btn-secondary flex-1"
            >
              Cancel
            </button>
            <button 
              @click="handleCreateGroup"
              class="btn-primary flex-1"
              :disabled="!canSubmitGroup || gameStore.loading"
            >
              <span v-if="gameStore.loading" class="flex items-center justify-center gap-2">
                <span class="spinner"></span>
                Creating...
              </span>
              <span v-else>Debut Group! 🎉</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

