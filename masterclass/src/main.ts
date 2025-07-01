import './assets/main.css'

import { createApp } from 'vue'
import { createPinia, storeToRefs } from 'pinia'

import App from './App.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from 'vue-router/auto-routes'
import { API } from '@/config/index'
import { useAuthStore } from '@/stores/site/login/auth' // Use the correct path for highlight.js

const app = createApp(App)
const router = createRouter({
  history: createWebHistory(),
  routes,
})

for (const route of router.getRoutes()) {
  // Check if the route path starts with '/dashboard/'
  if (route.path !== '/' && route.path !== '[..catchAll]') {
    //route.meta.requiresAuth = true
  }
}

router.beforeEach(async (to, from, next) => {
  //console.log('Route guard triggered')

  const auth = useAuthStore()
  const { token } = storeToRefs(auth)

  // If route doesn't require auth, allow navigation
  if (!to.meta.requiresAuth) {
    //console.log('Does not require login')s
    return next()
  }

  // If no token in store, redirect to login
  if (!token.value) {
    console.log('No token in store')
    return to.path === '/' ? next(false) : next('/')
  }

  try {
    const res = await fetch(`${API}/credentials/logincheack`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ csrf_token: token.value }),
    })

    const data = await res.json()

    if (data.success) {
      //
      //should be data.success
      //console.log('Token valid')
      next()
    } else {
    }
  } catch (err) {


    auth.clearToken()
    console.error('Session check failed:', err)
    return to.path === '/' ? next(false) : next('/')
  }
})


app.use(createPinia())
app.use(router)
app.mount('#app')
