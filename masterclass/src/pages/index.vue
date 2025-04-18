<template>
  <div class="min-h-screen bg-[var(--bg-color)] flex items-center justify-center p-4">
    <div
      class="container max-w-[1500px] flex flex-col lg:flex-row-reverse items-center justify-between gap-12 animate-fade-in"
    >
      <!-- Right Side - Logo (Large Screens) -->
      <div class="flex-1 xl:flex-[1.2] flex justify-center items-center w-full max-w-[800px]">
        <div class="relative group w-full max-w-[600px]">
          <div
            class="absolute inset-0 bg-[var(--primary-glow)] rounded-[30%] blur-3xl opacity-40 group-hover:opacity-60 transition-opacity duration-300 animate-pulse"
          ></div>
          <img
            src="@/assets/logo.webp"
            alt="Application Logo"
            class="relative w-full h-auto max-w-[100%] max-h-[100%] rounded-3xl object-contain border-2 border-transparent group-hover:border-[var(--primary-glow)] transition-all duration-300 shadow-2xl hover:shadow-[0_0_80px_var(--primary-glow)]"
          />
        </div>
      </div>

      <!-- Left Side - Login Form -->
      <div class="flex-1 flex justify-center items-center w-full max-w-[600px]">
        <div
          class="w-full p-8 bg-black/30 backdrop-blur-xl rounded-3xl border border-white/10 shadow-2xl transition-all hover:shadow-[0_0_60px_var(--primary-glow)] animate-slide-in-left"
          :class="{ 'animate-shake': loginError }"
        >
          <h2
            class="text-4xl xl:text-5xl mb-16 font-bold bg-gradient-to-r from-[#00f0ff] to-[#d000ff] bg-clip-text text-transparent"
          >
            Secure Access
          </h2>

          <form @submit.prevent="handleLogin" class="space-y-8">
            <div class="space-y-6">
              <div class="group">
                <label
                  for="username"
                  class="block text-lg xl:text-xl font-medium text-[#00f0ff] mb-3"
                  >Username</label
                >
                <input
                  id="username"
                  name="username"
                  type="text"
                  v-model="username"
                  autocomplete="username"
                  required
                  class="w-full px-6 py-4 text-lg xl:text-xl bg-black/40 rounded-xl border-2 border-white/10 focus:outline-none focus:border-[#00f0ff] focus:ring-2 focus:ring-[#00f0ff] transition-all placeholder-gray-400 hover:border-white/30"
                  placeholder="Enter your username"
                  @input="clearError"
                />
              </div>

              <div class="group">
                <label
                  for="password"
                  class="block text-lg xl:text-xl font-medium text-[#d000ff] mb-3"
                  >Password</label
                >
                <input
                  id="password"
                  name="password"
                  type="password"
                  v-model="password"
                  required
                  autocomplete="current-password"
                  class="w-full px-6 py-4 text-lg xl:text-xl bg-black/40 rounded-xl border-2 border-white/10 focus:outline-none focus:border-[#d000ff] focus:ring-2 focus:ring-[#d000ff] transition-all placeholder-gray-400 hover:border-white/30"
                  placeholder="••••••••"
                  @input="clearError"
                />
              </div>
            </div>

            <!-- Error Message -->
            <div
              v-if="loginError"
              class="mb-6 p-4 text-center text-red-400 border border-red-400/30 rounded-xl bg-red-900/10 backdrop-blur-sm animate-error-glow"
            >
              <span class="text-shadow-red-glow">{{ errorMessage }}</span>
            </div>

            <button
              type="submit"
              id="login-btn"
              class="button w-full py-4 text-xl xl:text-2xl font-bold tracking-wider transition-transform hover:scale-[1.02] hover:shadow-[0_0_40px_#00f0ff]"
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

// 🔘 Fetch public key on mount
onMounted(async () => {
  try {
    const response = await fetch(`${API}/keys/PublicKey`)
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

.text-shadow-red-glow {
  text-shadow:
    0 0 8px rgba(255, 50, 50, 0.6),
    0 0 16px rgba(255, 0, 0, 0.3);
}

/* Using imported fonts directly without @font-face */
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
    opacity: 0.4;
  }
  50% {
    opacity: 0.6;
  }
}

@media (min-width: 1920px) {
  .container {
    gap: 15vw;
    padding: 4rem;
  }

  .rounded-3xl {
    border-radius: 2.5rem;
  }

  .text-5xl {
    font-size: 3.5rem;
  }

  .custom-max-w {
    max-width: 600px;
  }
}

@media (min-width: 2560px) {
  .container {
    max-width: 2000px;
  }

  .text-5xl {
    font-size: 4rem;
  }

  input {
    padding: 1.5rem;
  }
}
</style>
