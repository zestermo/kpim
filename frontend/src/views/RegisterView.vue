<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const agency_name = ref('')
const error = ref('')
const audioRef = ref(null)
const isMuted = ref(false)
const hasInteracted = ref(false)

// Start music after user interaction (browser autoplay policy)
function startMusic() {
  if (hasInteracted.value || !audioRef.value) return
  hasInteracted.value = true
  
  audioRef.value.volume = 0
  audioRef.value.play().then(() => {
    fadeAudio(audioRef.value, 0, 0.5, 2000)
  }).catch(e => {
    console.log('Audio autoplay blocked:', e)
  })
}

function fadeAudio(audio, from, to, duration) {
  const steps = 50
  const stepTime = duration / steps
  const volumeStep = (to - from) / steps
  let currentStep = 0
  
  const interval = setInterval(() => {
    currentStep++
    audio.volume = Math.max(0, Math.min(1, from + (volumeStep * currentStep)))
    
    if (currentStep >= steps) {
      clearInterval(interval)
    }
  }, stepTime)
}

function toggleMute() {
  if (!audioRef.value) return
  isMuted.value = !isMuted.value
  
  if (isMuted.value) {
    fadeAudio(audioRef.value, audioRef.value.volume, 0, 500)
  } else {
    fadeAudio(audioRef.value, audioRef.value.volume, 0.5, 500)
  }
}

onMounted(() => {
  document.addEventListener('click', startMusic, { once: true })
  document.addEventListener('keydown', startMusic, { once: true })
})

onUnmounted(() => {
  if (audioRef.value && audioRef.value.volume > 0) {
    fadeAudio(audioRef.value, audioRef.value.volume, 0, 500)
    setTimeout(() => {
      if (audioRef.value) audioRef.value.pause()
    }, 500)
  }
  document.removeEventListener('click', startMusic)
  document.removeEventListener('keydown', startMusic)
})

async function handleSubmit() {
  error.value = ''
  
  if (password.value !== password_confirmation.value) {
    error.value = 'Passwords do not match'
    return
  }
  
  const result = await authStore.register({
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: password_confirmation.value,
    agency_name: agency_name.value || undefined
  })
  
  if (result.success) {
    if (audioRef.value) {
      fadeAudio(audioRef.value, audioRef.value.volume, 0, 800)
      setTimeout(() => {
        router.push('/select-manager')
      }, 800)
    } else {
      router.push('/select-manager')
    }
  } else {
    error.value = result.error
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Background Music -->
    <audio 
      ref="audioRef" 
      loop 
      preload="auto"
      :muted="isMuted"
    >
      <source src="/music/menu.mp3" type="audio/mpeg">
    </audio>

    <div class="flex flex-1">
    <!-- Left Side - Splash Art -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
      <!-- Layer 1: Background Image (bgsplash.png) -->
      <div class="absolute inset-0 z-0">
        <img 
          src="/images/bgsplash.png" 
          alt="" 
          class="w-full h-full object-cover object-center"
        />
        <!-- Overlay gradient for depth -->
        <div class="absolute inset-0 bg-gradient-to-r from-transparent to-gray-900/50"></div>
      </div>
      
      <!-- Layer 2: Girls Splash (girlssplash.png) -->
      <div class="absolute inset-0 z-10 flex items-center justify-center">
        <img 
          src="/images/girlssplash.png" 
          alt="K-Pop Idols" 
          class="max-h-full max-w-full object-contain drop-shadow-2xl animate-pulse-scale"
          style="filter: drop-shadow(0 0 30px rgba(0,0,0,0.5));"
        />
      </div>
      
      <!-- Layer 3: Logo (logo.png) -->
      <div class="absolute inset-0 z-20 flex items-end justify-center pb-12">
        <img 
          src="/images/logo.png" 
          alt="KPOP IDOL MANAGER 2025" 
          class="w-auto max-w-[95%] h-auto max-h-[320px] object-contain animate-float drop-shadow-lg pixel-art"
          style="filter: drop-shadow(0 4px 20px rgba(236, 72, 153, 0.5));"
        />
      </div>
      
      <!-- Floating particles overlay -->
      <div class="absolute inset-0 z-30 pointer-events-none overflow-hidden">
        <div class="star star-1">✦</div>
        <div class="star star-2">★</div>
        <div class="star star-3">✧</div>
        <div class="star star-4">⭐</div>
        <div class="star star-5">✦</div>
      </div>
      
      <!-- Bottom gradient fade to black -->
      <div class="absolute bottom-0 left-0 right-0 h-32 z-25 bg-gradient-to-t from-gray-900 to-transparent"></div>
    </div>
    
    <!-- Right Side - Register Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-gray-900 overflow-y-auto">
      <div class="w-full max-w-md py-8">
        <!-- Mobile Logo -->
        <div class="lg:hidden text-center mb-6">
          <img 
            src="/images/logo.png" 
            alt="KPOP IDOL MANAGER 2025" 
            class="w-auto max-w-[240px] h-auto mx-auto mb-4 pixel-art"
          />
        </div>
        
        <!-- Register Form -->
        <div class="game-panel p-8">
          <h2 class="text-2xl font-bold mb-2 text-center">Create Your Agency</h2>
          <p class="text-gray-400 text-center mb-6">Start your K-Pop empire today</p>
          
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label class="block text-sm text-gray-400 mb-2">Your Name</label>
              <input
                v-model="name"
                type="text"
                class="input-field"
                placeholder="Park Jinyoung"
                required
                @focus="startMusic"
              />
            </div>
            
            <div>
              <label class="block text-sm text-gray-400 mb-2">
                Agency Name <span class="text-gray-500">(optional)</span>
              </label>
              <input
                v-model="agency_name"
                type="text"
                class="input-field"
                placeholder="Star Entertainment"
              />
            </div>
            
            <div>
              <label class="block text-sm text-gray-400 mb-2">Email</label>
              <input
                v-model="email"
                type="email"
                class="input-field"
                placeholder="ceo@starent.com"
                required
              />
            </div>
            
            <div>
              <label class="block text-sm text-gray-400 mb-2">Password</label>
              <input
                v-model="password"
                type="password"
                class="input-field"
                placeholder="••••••••"
                required
                minlength="6"
              />
            </div>
            
            <div>
              <label class="block text-sm text-gray-400 mb-2">Confirm Password</label>
              <input
                v-model="password_confirmation"
                type="password"
                class="input-field"
                placeholder="••••••••"
                required
              />
            </div>
            
            <div v-if="error" class="text-red-400 text-sm text-center py-2 px-4 bg-red-400/10 rounded-lg">
              {{ error }}
            </div>
            
            <button 
              type="submit" 
              class="btn-primary w-full text-lg py-4"
              :disabled="authStore.loading"
            >
              <span v-if="authStore.loading" class="flex items-center justify-center gap-2">
                <span class="spinner"></span>
                Creating...
              </span>
              <span v-else>Start Your Journey 🚀</span>
            </button>
          </form>
          
          <div class="mt-6 text-center">
            <p class="text-gray-400">
              Already have an agency?
            </p>
            <router-link 
              to="/login" 
              class="inline-block mt-2 text-kpop-pink-400 hover:text-kpop-pink-300 font-bold transition-colors"
            >
              Sign In →
            </router-link>
          </div>
        </div>
        
        <!-- Music Control -->
        <div class="mt-6 text-center">
          <button 
            @click="toggleMute"
            class="text-gray-500 hover:text-white transition-colors text-sm flex items-center justify-center gap-2 mx-auto"
          >
            <span v-if="isMuted">🔇 Music Off</span>
            <span v-else>🎵 Music On</span>
          </button>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<style scoped>
/* Pixel art rendering */
.pixel-art {
  image-rendering: pixelated;
  image-rendering: crisp-edges;
}

/* Floating stars animation */
.star {
  position: absolute;
  font-size: 1.5rem;
  color: rgba(255, 255, 255, 0.7);
  animation: floatStar 12s ease-in-out infinite;
  text-shadow: 0 0 10px rgba(236, 72, 153, 0.8);
}

.star-1 { top: 15%; left: 10%; animation-delay: 0s; font-size: 1.2rem; }
.star-2 { top: 25%; left: 85%; animation-delay: 2s; font-size: 1.5rem; }
.star-3 { top: 70%; left: 12%; animation-delay: 4s; font-size: 1rem; }
.star-4 { top: 60%; left: 75%; animation-delay: 1s; font-size: 1.8rem; }
.star-5 { top: 45%; left: 5%; animation-delay: 3s; font-size: 1.3rem; }

@keyframes floatStar {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
    opacity: 0.5;
  }
  25% {
    transform: translateY(-15px) rotate(10deg);
    opacity: 1;
  }
  50% {
    transform: translateY(-8px) rotate(-5deg);
    opacity: 0.7;
  }
  75% {
    transform: translateY(-20px) rotate(5deg);
    opacity: 0.9;
  }
}

/* Float animation for logo */
.animate-float {
  animation: float 4s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

/* Subtle pulse scale animation for girls splash */
.animate-pulse-scale {
  animation: pulseScale 4s ease-in-out infinite;
}

@keyframes pulseScale {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

/* Custom z-index for gradient */
.z-25 {
  z-index: 25;
}
</style>
