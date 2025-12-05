import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor for CSRF token
api.interceptors.request.use(async (config) => {
  // For non-GET requests, ensure we have CSRF token
  if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
    // Get CSRF cookie if not present
    const csrfToken = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1]
    
    if (!csrfToken) {
      try {
        // Request CSRF cookie
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
      } catch (e) {
        // Ignore CSRF fetch errors
      }
    }
  }
  
  return config
})

// Response interceptor - DON'T redirect on 401, let the app handle it
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Just reject the error, don't redirect
    // The auth store and router will handle auth state
    return Promise.reject(error)
  }
)

export default api
