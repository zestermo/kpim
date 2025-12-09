import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'
import { useGameStore } from './game'
import { useToastStore } from './toast'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)
  const initialized = ref(false)
  
  const isAuthenticated = computed(() => !!user.value)
  
  async function checkAuth() {
    if (initialized.value) return
    
    try {
      const response = await api.get('/me')
      user.value = response.data.data.user
      console.log('[auth] checkAuth user raw', response.data.data.user)
      
      // Load game data
      const gameStore = useGameStore()
      gameStore.setPlayer(user.value.playerProfile)
    } catch (error) {
      // Not authenticated - this is fine for new users
      user.value = null
    } finally {
      initialized.value = true
    }
  }
  
  async function register(data) {
    loading.value = true
    const toast = useToastStore()
    
    try {
      const response = await api.post('/auth/register', data)
      user.value = response.data.data.user
      console.log('[auth] register user raw', response.data.data.user)
      
      const gameStore = useGameStore()
      gameStore.setPlayer(user.value.playerProfile)
      
      toast.success('Welcome to KPOP IDOL MANAGER! 🌟')
      return { success: true }
    } catch (error) {
      const message = error.response?.data?.message || 'Registration failed'
      toast.error(message)
      return { success: false, error: message }
    } finally {
      loading.value = false
    }
  }
  
  async function login(email, password) {
    loading.value = true
    const toast = useToastStore()
    
    try {
      const response = await api.post('/auth/login', { email, password })
      user.value = response.data.data.user
      console.log('[auth] login user raw', response.data.data.user)
      
      const gameStore = useGameStore()
      gameStore.setPlayer(user.value.playerProfile)
      
      toast.success('Welcome back! 💫')
      return { success: true }
    } catch (error) {
      const message = error.response?.data?.message || 'Invalid credentials'
      toast.error(message)
      return { success: false, error: message }
    } finally {
      loading.value = false
    }
  }
  
  async function logout() {
    const toast = useToastStore()
    
    try {
      await api.post('/auth/logout')
    } catch (error) {
      // Continue with logout even if request fails
    }
    
    user.value = null
    const gameStore = useGameStore()
    gameStore.reset()
    
    toast.info('See you next time! 👋')
  }
  
  function updateUser(userData) {
    user.value = userData
  }
  
  return {
    user,
    loading,
    initialized,
    isAuthenticated,
    checkAuth,
    register,
    login,
    logout,
    updateUser
  }
})
