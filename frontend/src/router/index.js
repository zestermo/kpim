import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guest: true }
  },
  {
    path: '/select-manager',
    name: 'SelectManager',
    component: () => import('../views/SelectManagerView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/onboarding',
    name: 'Onboarding',
    component: () => import('../views/OnboardingView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/idols',
    name: 'Idols',
    component: () => import('../views/IdolsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/groups',
    name: 'Groups',
    component: () => import('../views/GroupsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/music',
    name: 'Music',
    component: () => import('../views/MusicView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/promotions',
    name: 'Promotions',
    component: () => import('../views/PromotionsView.vue'),
    meta: { requiresAuth: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Wait for auth check to complete on first load
  if (!authStore.initialized) {
    await authStore.checkAuth()
  }
  
  // Check if route requires auth
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }
  
  // Redirect away from guest routes if logged in
  if (to.meta.guest && authStore.isAuthenticated) {
    next('/dashboard')
    return
  }
  
  // Allow all navigation for authenticated users - no more manager blocking
  next()
})

export default router
