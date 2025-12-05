<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useGameStore } from '../stores/game'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const gameStore = useGameStore()
const authStore = useAuthStore()

const currentStep = ref(0)
const agencyName = ref('')
const isTyping = ref(false)
const displayedText = ref('')

const manager = computed(() => authStore.user?.playerProfile?.manager)

const steps = [
  {
    id: 'welcome',
    title: 'Welcome, CEO!',
    managerSays: `Welcome to the K-Pop industry! I'm {manager}, and I'll be your right hand as we build the next global sensation. Let's get your agency set up!`,
    action: null
  },
  {
    id: 'agency-name',
    title: 'Name Your Agency',
    managerSays: `Every legendary agency needs a memorable name. What should we call your entertainment company?`,
    action: 'name-agency'
  },
  {
    id: 'tour-dashboard',
    title: 'Agency Dashboard',
    managerSays: `This is your Agency Dashboard - your command center. Here you'll see your resources: 💰 Money for operations, 💖 Fans who love your artists, and ⭐ Reputation in the industry.`,
    action: null,
    highlight: 'dashboard'
  },
  {
    id: 'tour-idols',
    title: 'Idol Scouting',
    managerSays: `In the Idols section, you'll scout talented trainees. Each idol has unique stats - Vocal, Dance, Visual, Charm, and Stamina. Rarer idols have higher potential! 🌟`,
    action: null,
    highlight: 'idols'
  },
  {
    id: 'tour-groups',
    title: 'Group Formation',
    managerSays: `Once you have enough idols, form them into Groups! Choose a concept that matches their strengths. A well-balanced group performs better in promotions.`,
    action: null,
    highlight: 'groups'
  },
  {
    id: 'tour-music',
    title: 'Music Production',
    managerSays: `Create songs for your groups in the Music studio. Different genres appeal to different audiences. Quality songs lead to successful promotions! 🎵`,
    action: null,
    highlight: 'music'
  },
  {
    id: 'tour-promotions',
    title: 'Promotions',
    managerSays: `Finally, run Promotions to gain fans and money! From social media posts to TV appearances - each has different costs and rewards. Sometimes you might even go viral! 🔥`,
    action: null,
    highlight: 'promotions'
  },
  {
    id: 'ready',
    title: 'Ready to Begin!',
    managerSays: `Your first mission: Scout at least 2 idols and form your debut group! I've given you 💰50,000 to start. Use it wisely, and let's make K-Pop history together!`,
    action: 'complete'
  }
]

const currentStepData = computed(() => steps[currentStep.value])

const processedManagerText = computed(() => {
  if (!currentStepData.value) return ''
  return currentStepData.value.managerSays.replace('{manager}', manager.value?.name || 'your manager')
})

onMounted(() => {
  agencyName.value = authStore.user?.playerProfile?.agency_name || ''
  typeText()
})

async function typeText() {
  isTyping.value = true
  displayedText.value = ''
  const text = processedManagerText.value
  
  for (let i = 0; i < text.length; i++) {
    displayedText.value += text[i]
    await new Promise(r => setTimeout(r, 20))
  }
  
  isTyping.value = false
}

function nextStep() {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++
    typeText()
  }
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--
    typeText()
  }
}

async function saveAgencyName() {
  if (!agencyName.value.trim()) return
  
  // Update locally
  if (authStore.user?.playerProfile) {
    authStore.user.playerProfile.agency_name = agencyName.value
  }
  
  // TODO: API call to save agency name
  nextStep()
}

function skipToEnd() {
  router.push('/idols')
}

function completeOnboarding() {
  router.push('/idols')
}

const navHighlights = computed(() => ({
  dashboard: currentStepData.value?.highlight === 'dashboard',
  idols: currentStepData.value?.highlight === 'idols',
  groups: currentStepData.value?.highlight === 'groups',
  music: currentStepData.value?.highlight === 'music',
  promotions: currentStepData.value?.highlight === 'promotions',
}))
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Progress Bar -->
    <div class="bg-gray-800 h-2">
      <div 
        class="h-full bg-gradient-to-r from-kpop-pink-500 to-kpop-purple-500 transition-all duration-500"
        :style="{ width: `${((currentStep + 1) / steps.length) * 100}%` }"
      ></div>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col items-center justify-center p-6">
      <div class="max-w-2xl w-full">
        
        <!-- Manager Avatar & Speech -->
        <div class="game-panel p-6 mb-6">
          <div class="flex items-start gap-4">
            <!-- Manager Avatar -->
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-kpop-pink-500/30 to-kpop-purple-500/30 flex items-center justify-center text-4xl flex-shrink-0 animate-float">
              👔
            </div>
            
            <!-- Speech Bubble -->
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-bold text-kpop-pink-400">{{ manager?.name || 'Manager' }}</span>
                <span class="text-xs text-gray-500">says:</span>
              </div>
              <p class="text-lg leading-relaxed">
                {{ displayedText }}
                <span v-if="isTyping" class="animate-pulse">▊</span>
              </p>
            </div>
          </div>
        </div>
        
        <!-- Step Title -->
        <h2 class="text-2xl font-bold text-center mb-6">
          {{ currentStepData.title }}
        </h2>
        
        <!-- Action Area -->
        <div v-if="currentStepData.action === 'name-agency'" class="game-panel p-6 mb-6">
          <label class="block text-sm text-gray-400 mb-2">Agency Name</label>
          <input
            v-model="agencyName"
            type="text"
            class="input-field text-xl text-center"
            placeholder="Enter your agency name..."
            maxlength="50"
            @keyup.enter="saveAgencyName"
          />
          <p class="text-center text-sm text-gray-500 mt-2">This will be displayed to other players</p>
        </div>
        
        <!-- Navigation Highlight Preview -->
        <div v-if="currentStepData.highlight" class="game-panel p-4 mb-6">
          <div class="flex justify-center gap-6">
            <div 
              class="flex flex-col items-center p-3 rounded-xl transition-all"
              :class="navHighlights.dashboard ? 'bg-kpop-pink-500/20 ring-2 ring-kpop-pink-500' : 'opacity-50'"
            >
              <span class="text-2xl mb-1">🏠</span>
              <span class="text-xs">Agency</span>
            </div>
            <div 
              class="flex flex-col items-center p-3 rounded-xl transition-all"
              :class="navHighlights.idols ? 'bg-kpop-pink-500/20 ring-2 ring-kpop-pink-500' : 'opacity-50'"
            >
              <span class="text-2xl mb-1">⭐</span>
              <span class="text-xs">Idols</span>
            </div>
            <div 
              class="flex flex-col items-center p-3 rounded-xl transition-all"
              :class="navHighlights.groups ? 'bg-kpop-pink-500/20 ring-2 ring-kpop-pink-500' : 'opacity-50'"
            >
              <span class="text-2xl mb-1">👥</span>
              <span class="text-xs">Groups</span>
            </div>
            <div 
              class="flex flex-col items-center p-3 rounded-xl transition-all"
              :class="navHighlights.music ? 'bg-kpop-pink-500/20 ring-2 ring-kpop-pink-500' : 'opacity-50'"
            >
              <span class="text-2xl mb-1">🎵</span>
              <span class="text-xs">Music</span>
            </div>
            <div 
              class="flex flex-col items-center p-3 rounded-xl transition-all"
              :class="navHighlights.promotions ? 'bg-kpop-pink-500/20 ring-2 ring-kpop-pink-500' : 'opacity-50'"
            >
              <span class="text-2xl mb-1">📢</span>
              <span class="text-xs">Promo</span>
            </div>
          </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between gap-4">
          <button 
            v-if="currentStep > 0"
            @click="prevStep"
            class="btn-secondary"
          >
            ← Back
          </button>
          <div v-else></div>
          
          <button
            @click="skipToEnd"
            class="text-gray-400 hover:text-white text-sm"
          >
            Skip Tutorial →
          </button>
          
          <button 
            v-if="currentStepData.action === 'name-agency'"
            @click="saveAgencyName"
            class="btn-primary"
            :disabled="!agencyName.trim()"
          >
            Continue →
          </button>
          <button 
            v-else-if="currentStepData.action === 'complete'"
            @click="completeOnboarding"
            class="btn-gold text-lg"
          >
            🌟 Start Scouting!
          </button>
          <button 
            v-else
            @click="nextStep"
            class="btn-primary"
            :disabled="isTyping"
          >
            {{ isTyping ? 'Reading...' : 'Continue →' }}
          </button>
        </div>
        
      </div>
    </div>
    
    <!-- Step Indicator -->
    <div class="pb-6 flex justify-center gap-2">
      <div 
        v-for="(step, index) in steps" 
        :key="step.id"
        class="w-2 h-2 rounded-full transition-all"
        :class="index <= currentStep ? 'bg-kpop-pink-500' : 'bg-gray-600'"
      ></div>
    </div>
  </div>
</template>

