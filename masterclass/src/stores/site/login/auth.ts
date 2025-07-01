import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: sessionStorage.getItem('csrf_token') || '',
  }),
  actions: {
    setToken(newToken: string) {
      this.token = newToken
      sessionStorage.setItem('csrf_token', newToken)
    },
    clearToken() {
      this.token = ''
      sessionStorage.removeItem('csrf_token')
    },
  },
})
