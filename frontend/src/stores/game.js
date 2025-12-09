import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'
import { useToastStore } from './toast'

export const useGameStore = defineStore('game', () => {
  const player = ref(null)
  const managers = ref([])
  const idols = ref([])
  const groups = ref([])
  const songs = ref([])
  const promotions = ref([])
  const promotionTypes = ref([])
  const upgrades = ref([])
  const loading = ref(false)
  const packLoading = ref(false)
  const packDraft = ref(null)
  const eventLog = ref([])
  const unreadEvents = ref(0)
  const nowPlaying = ref(null) // { song, isPlaying, progress? }
  const audioVolume = ref(0.7)
  let audioEl = null
  let eventsInterval = null
  
  // Computed
  const formattedMoney = computed(() => {
    if (!player.value) return '0'
    return player.value.money.toLocaleString()
  })
  
  const formattedFans = computed(() => {
    if (!player.value) return '0'
    return player.value.fans.toLocaleString()
  })
  
  const availableIdols = computed(() => {
    return idols.value.filter(idol => !idol.groups || idol.groups.length === 0)
  })
  
  const completedSongs = computed(() => {
    return songs.value.filter(song => song.is_completed)
  })
  
  // Actions
  function setPlayer(playerData) {
    player.value = playerData
  }
  
  function reset() {
    player.value = null
    managers.value = []
    idols.value = []
    groups.value = []
    songs.value = []
    promotions.value = []
    promotionTypes.value = []
    upgrades.value = []
    packDraft.value = null
    eventLog.value = []
    unreadEvents.value = 0
    nowPlaying.value = null
    if (audioEl) {
      audioEl.pause()
      audioEl = null
    }
    if (eventsInterval) {
      clearInterval(eventsInterval)
      eventsInterval = null
    }
  }
  
  async function fetchManagers() {
    try {
      const response = await api.get('/managers')
      managers.value = response.data.data.managers
    } catch (error) {
      console.error('Failed to fetch managers:', error)
    }
  }
  
  async function selectManager(managerId) {
    const toast = useToastStore()
    loading.value = true
    
    try {
      const response = await api.post('/managers/select', { manager_id: managerId })
      player.value = response.data.data.player
      
      // Find the selected manager from our list
      const selectedManager = managers.value.find(m => m.id === managerId)
      if (player.value && selectedManager) {
        player.value.manager = selectedManager
      }
      
      toast.success('Manager selected! Let\'s build your empire! 🚀')
      return { success: true, player: player.value }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to select manager'
      toast.error(message)
      return { success: false, error: message }
    } finally {
      loading.value = false
    }
  }
  
  async function fetchIdols() {
    try {
      const response = await api.get('/idols')
      idols.value = response.data.data.idols
    } catch (error) {
      console.error('Failed to fetch idols:', error)
    }
  }
  
  async function scoutIdol() {
    const toast = useToastStore()
    loading.value = true
    
    try {
      const response = await api.post('/idols/scout')
      const newIdol = response.data.data.idol
      idols.value.push(newIdol)
      player.value = response.data.data.player
      
      const rarityEmoji = {
        common: '⚪',
        uncommon: '🟢',
        rare: '🔵',
        epic: '🟣',
        legendary: '🌟'
      }
      
      toast.success(`Scouted ${newIdol.name}! ${rarityEmoji[newIdol.rarity] || '⭐'}`)
      return { success: true, idol: newIdol }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to scout idol'
      toast.error(message)
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  async function openIdolPack() {
    const toast = useToastStore()
    packLoading.value = true
    try {
      const response = await api.post('/idol-packs')
      packDraft.value = response.data.data
      return { success: true, pack: packDraft.value }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to open pack'
      toast.error(message)
      return { success: false, error: message }
    } finally {
      packLoading.value = false
    }
  }

  async function chooseIdolFromPack(packId, index) {
    const toast = useToastStore()
    packLoading.value = true
    try {
      const response = await api.post(`/idol-packs/${packId}/choose`, { index })
      const idol = response.data.data.idol
      idols.value.push(idol)
      player.value = response.data.data.player
      packDraft.value = null
      toast.success(response.data.data.message || 'Idol recruited!')
      return { success: true, idol }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to choose idol'
      toast.error(message)
      return { success: false, error: message }
    } finally {
      packLoading.value = false
    }
  }

  async function pulseEvents() {
    if (!player.value) return
    try {
      const response = await api.post('/events/pulse')
      const events = response.data.data.events || []
      if (events.length) {
        player.value = response.data.data.player
        const toast = useToastStore()
        events.forEach(ev => {
          eventLog.value.unshift({ ...ev, unread: true })
          if (eventLog.value.length > 25) {
            eventLog.value.pop()
          }
          unreadEvents.value += 1
          toast.info(`${ev.message} +$${ev.money.toLocaleString()} / +${ev.fans.toLocaleString()} fans`)
        })
      }
    } catch (error) {
      // silent
    }
  }

  function startEventPolling() {
    if (eventsInterval) return
    pulseEvents()
    eventsInterval = setInterval(pulseEvents, 45000)
  }

  function markEventsRead() {
    unreadEvents.value = 0
    eventLog.value = eventLog.value.map(ev => ({ ...ev, unread: false }))
  }

  function ensureAudio() {
    if (!audioEl) {
      audioEl = new Audio()
      audioEl.volume = audioVolume.value
    }
  }

  function playSongAudio(song) {
    if (!song?.audio_url) return
    ensureAudio()
    if (audioEl.src !== song.audio_url) {
      audioEl.src = song.audio_url
      audioEl.currentTime = 0
    }
    audioEl.volume = audioVolume.value
    audioEl.play()
    nowPlaying.value = {
      song,
      isPlaying: true
    }
  }

  function pauseSongAudio() {
    if (!audioEl) return
    audioEl.pause()
    if (nowPlaying.value) {
      nowPlaying.value = { ...nowPlaying.value, isPlaying: false }
    }
  }

  function restartSongAudio() {
    if (!audioEl) return
    audioEl.currentTime = 0
    audioEl.play()
    if (nowPlaying.value) {
      nowPlaying.value = { ...nowPlaying.value, isPlaying: true }
    }
  }

  function setAudioVolume(vol) {
    audioVolume.value = Math.min(1, Math.max(0, vol))
    if (audioEl) {
      audioEl.volume = audioVolume.value
    }
  }

  async function pulseEvents() {
    if (!player.value) return
    try {
      const response = await api.post('/events/pulse')
      const events = response.data.data.events || []
      if (events.length) {
        player.value = response.data.data.player
        const toast = useToastStore()
        events.forEach(ev => {
          eventLog.value.unshift(ev)
          if (eventLog.value.length > 25) {
            eventLog.value.pop()
          }
          toast.info(`${ev.message} +$${ev.money.toLocaleString()} / +${ev.fans.toLocaleString()} fans`)
        })
      }
    } catch (error) {
      // silent
    }
  }

  function startEventPolling() {
    if (eventsInterval) return
    pulseEvents()
    eventsInterval = setInterval(pulseEvents, 45000)
  }
  
  async function fetchGroups() {
    try {
      const response = await api.get('/groups')
      groups.value = response.data.data.groups
    } catch (error) {
      console.error('Failed to fetch groups:', error)
    }
  }
  
  async function createGroup(name, concept, memberIds) {
    const toast = useToastStore()
    loading.value = true
    
    try {
      const response = await api.post('/groups', {
        name,
        concept,
        member_ids: memberIds
      })
      
      groups.value.push(response.data.data.group)
      player.value = response.data.data.player
      
      // Refresh idols to update their group status
      await fetchIdols()
      
      toast.success(`${name} has debuted! 🎉`)
      return { success: true, group: response.data.data.group }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to create group'
      toast.error(message)
      return { success: false }
    } finally {
      loading.value = false
    }
  }
  
  async function fetchSongs() {
    try {
      const response = await api.get('/songs')
      songs.value = response.data.data.songs
    } catch (error) {
      console.error('Failed to fetch songs:', error)
    }
  }
  
  async function produceSong(groupId, genre, title = null, debugComplete = false) {
    const toast = useToastStore()
    loading.value = true
    
    try {
      const response = await api.post('/songs', {
        group_id: groupId,
        genre,
        title,
        debug_complete: debugComplete
      })
      
      const newSong = response.data.data.song
      songs.value.push(newSong)
      player.value = response.data.data.player
      
      const msg = newSong.is_completed ? `Created "${newSong.title}" instantly (debug)` : `Started producing "${newSong.title}"! 🎵`
      toast.success(msg)
      return { success: true, song: newSong }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to produce song'
      toast.error(message)
      return { success: false }
    } finally {
      loading.value = false
    }
  }
  
  async function checkSongCompletion(songId) {
    try {
      const response = await api.get(`/songs/${songId}`)
      const updatedSong = response.data.data.song
      
      // Update in local state
      const index = songs.value.findIndex(s => s.id === songId)
      if (index !== -1) {
        songs.value[index] = updatedSong
      }
      
      return updatedSong
    } catch (error) {
      console.error('Failed to check song:', error)
      return null
    }
  }
  
  async function fetchPromotions() {
    try {
      const response = await api.get('/promotions')
      promotions.value = response.data.data.promotions
    } catch (error) {
      console.error('Failed to fetch promotions:', error)
    }
  }
  
  async function fetchAvailablePromotions() {
    try {
      const response = await api.get('/promotions/available')
      promotionTypes.value = response.data.data.promotion_types
      return promotionTypes.value
    } catch (error) {
      console.error('Failed to fetch promotion types:', error)
      return []
    }
  }
  
  async function startPromotion(groupId, songId, type) {
    const toast = useToastStore()
    loading.value = true
    
    try {
      const response = await api.post('/promotions', {
        group_id: groupId,
        song_id: songId,
        type
      })
      
      promotions.value.unshift(response.data.data.promotion)
      player.value = response.data.data.player
      
      toast.success(response.data.data.message)
      return { success: true, promotion: response.data.data.promotion }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to start promotion'
      toast.error(message)
      return { success: false }
    } finally {
      loading.value = false
    }
  }
  
  async function completePromotion(promotionId) {
    const toast = useToastStore()
    loading.value = true
    
    try {
      const response = await api.post(`/promotions/${promotionId}/complete`)
      
      // Update promotion in state
      const index = promotions.value.findIndex(p => p.id === promotionId)
      if (index !== -1) {
        promotions.value[index] = response.data.data.promotion
      }
      
      player.value = response.data.data.player
      
      const rewards = response.data.data.rewards
      let message = `+${rewards.fans.toLocaleString()} fans, +$${rewards.money.toLocaleString()}`
      
      if (rewards.went_viral) {
        toast.success(`🔥 IT WENT VIRAL! ${message}`)
      } else {
        toast.success(`Promotion complete! ${message}`)
      }
      
      return { success: true, rewards }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to complete promotion'
      toast.error(message)
      return { success: false }
    } finally {
      loading.value = false
    }
  }
  
  async function refreshPlayer() {
    try {
      const response = await api.get('/player')
      player.value = response.data.data.player
    } catch (error) {
      console.error('Failed to refresh player:', error)
    }
  }

  async function fetchUpgrades() {
    try {
      const response = await api.get('/agency-upgrades')
      upgrades.value = response.data.data.upgrades
      player.value = response.data.data.player
      return upgrades.value
    } catch (error) {
      console.error('Failed to fetch upgrades:', error)
      return []
    }
  }

  async function purchaseUpgrade(type) {
    const toast = useToastStore()
    try {
      const response = await api.post('/agency-upgrades/purchase', { type })
      upgrades.value = response.data.data.upgrades
      player.value = response.data.data.player
      toast.success(response.data.data.message || 'Upgrade purchased!')
      return { success: true }
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to purchase upgrade'
      toast.error(message)
      return { success: false, error: message }
    }
  }
  
  return {
    // State
    player,
    managers,
    idols,
    groups,
    songs,
    promotions,
    promotionTypes,
    upgrades,
    loading,
    packLoading,
    packDraft,
    eventLog,
    unreadEvents,
    nowPlaying,
    audioVolume,
    
    // Computed
    formattedMoney,
    formattedFans,
    availableIdols,
    completedSongs,
    
    // Actions
    setPlayer,
    reset,
    fetchManagers,
    selectManager,
    fetchIdols,
    scoutIdol,
    openIdolPack,
    chooseIdolFromPack,
    fetchGroups,
    createGroup,
    fetchSongs,
    produceSong,
    checkSongCompletion,
    fetchPromotions,
    fetchAvailablePromotions,
    startPromotion,
    completePromotion,
    refreshPlayer,
    fetchUpgrades,
    purchaseUpgrade,
    pulseEvents,
    startEventPolling,
    markEventsRead,
    playSongAudio,
    pauseSongAudio,
    restartSongAudio,
    setAudioVolume
  }
})

