import { defineStore } from 'pinia'

interface UserState {
  name: string | null
  email: string | null
}

export const useUserStore = defineStore('user', {
  state: (): UserState => ({
    name: null,
    email: null,
  }),

  actions: {
    setUser(name: string, email: string) {
      this.name = name
      this.email = email

      sessionStorage.setItem('userName', name)
      sessionStorage.setItem('userEmail', email)
    },

    clearUser() {
      this.name = null
      this.email = null
      
    },

    loadUser() {
      this.name = sessionStorage.getItem('userName')
      this.email = sessionStorage.getItem('userEmail')
    },
  },
})
