<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-green-50 flex items-center justify-center p-4">
    <div
      class="container max-w-[1500px] flex flex-col lg:flex-row-reverse items-center justify-between gap-12 animate-fade-in"
    >
      <!-- Right Side - Logo (Large Screens) -->
      <div class="flex-1 xl:flex-[1.2] flex justify-center items-center w-full max-w-[800px]">
        <div class="relative group w-full max-w-[600px]">
          <div
            class="absolute inset-0 bg-[#007C52] rounded-[30%] blur-3xl opacity-20 group-hover:opacity-30 transition-opacity duration-300 animate-pulse"
          ></div>
          <img
            src="@/assets/logo.webp"
            alt="Application Logo"
            class="relative w-full h-auto max-w-[100%] max-h-[100%] rounded-3xl object-contain border-2 border-transparent group-hover:border-[#005188] transition-all duration-300 shadow-xl hover:shadow-[0_0_40px_rgba(0,81,136,0.2)]"
          />
        </div>
      </div>

      <!-- Left Side - Login Form -->
      <div class="flex-1 flex justify-center items-center w-full max-w-[600px]">
        <div
          class="w-full p-8 bg-white/90 backdrop-blur-md rounded-3xl border border-[#005188]/20 shadow-xl transition-all hover:shadow-[0_0_30px_rgba(0,81,136,0.15)] animate-slide-in-left"
          :class="{ 'animate-shake': loginError }"
        >
          <h2
            class="text-4xl xl:text-5xl mb-16 font-bold bg-gradient-to-r from-[#005188] to-[#007C52] bg-clip-text text-transparent"
          >
            Secure Access
          </h2>

          <form @submit.prevent="handleLogin" class="space-y-8">
            <div class="space-y-6">
              <div class="group">
                <label
                  for="username"
                  class="block text-lg xl:text-xl font-medium text-[#005188] mb-3"
                  >Username</label
                >
                <input
                  id="username"
                  name="username"
                  type="text"
                  v-model="username"
                  autocomplete="username"
                  required
                  class="w-full px-6 py-4 text-lg xl:text-xl bg-white rounded-xl border-2 border-[#005188]/20 focus:outline-none focus:border-[#005188] focus:ring-2 focus:ring-[#005188]/30 transition-all placeholder-[#005188]/50 hover:border-[#005188]/40"
                  placeholder="Enter your username"
                  @input="clearError"
                />
              </div>

              <div class="group">
                <label
                  for="password"
                  class="block text-lg xl:text-xl font-medium text-[#007C52] mb-3"
                  >Password</label
                >
                <input
                  id="password"
                  name="password"
                  type="password"
                  v-model="password"
                  required
                  autocomplete="current-password"
                  class="w-full px-6 py-4 text-lg xl:text-xl bg-white rounded-xl border-2 border-[#007C52]/20 focus:outline-none focus:border-[#007C52] focus:ring-2 focus:ring-[#007C52]/30 transition-all placeholder-[#007C52]/50 hover:border-[#007C52]/40"
                  placeholder="••••••••"
                  @input="clearError"
                />
              </div>
            </div>

            <!-- Error Message -->
            <div
              v-if="loginError"
              class="mb-6 p-4 text-center text-red-600 border border-red-300 rounded-xl bg-red-50 backdrop-blur-sm animate-error-glow"
            >
              <span>{{ errorMessage }}</span>
            </div>

            <button
              type="submit"
              id="login-btn"
              class="w-full py-4 text-xl xl:text-2xl font-bold tracking-wider bg-gradient-to-r from-[#005188] to-[#007C52] text-white rounded-xl transition-all hover:shadow-[0_5px_20px_-5px_rgba(0,81,136,0.3)] hover:scale-[1.02] active:scale-100"
              v-on:click="handleLogin"
            >
              AUTHENTICATE
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import forge from 'node-forge'
import { API } from '@/config/index'
import { useAuthStore } from '@/stores/auth.ts'
import { useUserStore } from '@/stores/user'
const userStore = useUserStore()

const authStore = useAuthStore()
const username = ref('')
const password = ref('')
const loginError = ref(false)
const errorMessage = ref('')
const publicKey = ref('')
const router = useRouter()

const clearError = () => {
  if (loginError.value) {
    loginError.value = false
    errorMessage.value = ''
  }
}

// đź”� Fetch public key on mount
onMounted(async () => {
  try {
    const response = await fetch(`${API}/keys/PublicKey`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json', // If you're using any tokens
      },
      credentials: 'include',
    })
    if (!response.ok) {
      throw new Error(`PublicKey error: ${response.status}`)
    }
    const data = await response.json()

    if (!data.message) {
      throw new Error('Invalid public key received from server.')
    }

    publicKey.value = data.message
  } catch (error) {
    console.error('Error fetching public key:', error)
    errorMessage.value = 'Error fetching public key from the server.'
  }
})

const isSubmitting = ref(false)

const handleLogin = async () => {
  if (isSubmitting.value) return // Prevent further submissions if already submitting
  isSubmitting.value = true

  try {
    const Key = forge.pki.publicKeyFromPem(publicKey.value)
    const encryptedPassword = Key.encrypt(password.value, 'RSA-OAEP', {
      md: forge.md.sha256.create(),
    })
    const encryptedUsername = Key.encrypt(username.value, 'RSA-OAEP', {
      md: forge.md.sha256.create(),
    })
    const encryptedPasswordBase64 = forge.util.encode64(encryptedPassword)
    const encryptedUsernameBase64 = forge.util.encode64(encryptedUsername)

    const response = await fetch(API + '/credentials/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json', // If you're using any tokens
      },
      credentials: 'include',
      body: JSON.stringify({
        username: encryptedUsernameBase64,
        password: encryptedPasswordBase64,
      }),
    })

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`)
    }

    const data = await response.json()

    if (data.success) {
      //console.log(sessionStorage.getItem('csrf_token'))
      authStore.setToken(data.csrf_token)
      userStore.setUser(data.username, username.value)
      router.push('/dashboard')
    } else {
      loginError.value = true
      errorMessage.value = data.message || 'Login failed.'
    }
  } catch (err) {
    loginError.value = true
    if (err instanceof Error) {
      errorMessage.value = err.message || 'An unexpected error occurred.'
    } else {
      errorMessage.value = 'An unknown error occurred.'
    }
  } finally {
    isSubmitting.value = false // Reset flag after request completes
  }
}
</script>

<style scoped>
@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  10%,
  30%,
  50%,
  70%,
  90% {
    transform: translateX(-10px);
  }
  20%,
  40%,
  60%,
  80% {
    transform: translateX(10px);
  }
}

@keyframes error-glow {
  0% {
    opacity: 0;
    transform: translateY(-10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-shake {
  animation: shake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

.animate-error-glow {
  animation: error-glow 0.4s ease-out;
}

.animate-fade-in {
  animation: fadeIn 1s ease-in;
}

.animate-slide-in-left {
  animation: slideInLeft 0.8s cubic-bezier(0.23, 1, 0.32, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInLeft {
  from {
    transform: translateX(-5vw);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-pulse {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 0.2;
  }
  50% {
    opacity: 0.3;
  }
}
</style>
