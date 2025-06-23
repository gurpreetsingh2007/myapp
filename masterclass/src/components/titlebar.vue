<template>
  <header
    class="titlebar relative z-40 flex flex-col border-b border-[rgba(0,240,255,0.1)] bg-[var(--bg-color)] transition-all duration-300 ease-in-out"
    :class="{ 'pl-[260px]': isOpen, 'pl-[80px]': !isOpen }"
  >
    <!-- Top Row: Title & Actions -->
    <div class="flex w-full items-center justify-between px-6 py-2.5">
      <!-- Left: Title & Breadcrumbs -->
      <div class="flex flex-col max-w-[60%] sm:max-w-[70%] truncate">
        <h1
          class="text-2xl sm:text-3xl md:text-4xl font-semibold tracking-tight bg-gradient-to-r from-[#00f0ff] to-[#d000ff] bg-clip-text text-transparent transition-all duration-300 ease-in-out hover:scale-[1.02] truncate"
        >
          {{ currentPageTitle }}
        </h1>

        <!-- Breadcrumbs - Collapsible -->
        <div
          class="text-xs text-gray-400 flex items-center mt-1 opacity-70 overflow-hidden"
          v-if="breadcrumbs.length > 1"
        >
          <!-- Compact/Mobile view: Show only first and last with ellipsis dropdown -->
          <div class="flex items-center relative" v-if="isCompactView && breadcrumbs.length > 2">
            <!-- First crumb -->
            <RouterLink
              :to="breadcrumbs[0].path"
              class="hover:text-[#00f0ff] transition-colors duration-200 truncate max-w-[60px] sm:max-w-[100px]"
            >
              {{ breadcrumbs[0].name }}
            </RouterLink>

            <!-- Ellipsis dropdown -->
            <div class="relative inline-block mx-1 group">
              <button class="hover:text-[#00f0ff] focus:outline-none px-1">...</button>

              <!-- Dropdown content -->
              <div
                class="absolute left-0 top-full mt-1 bg-[#121212] border border-[rgba(0,240,255,0.2)] rounded-lg shadow-lg py-1 px-2 w-48 z-50 hidden group-hover:block"
              >
                <div v-for="(crumb, idx) in breadcrumbs.slice(1, -1)" :key="idx" class="py-1">
                  <RouterLink
                    :to="crumb.path"
                    class="block hover:text-[#00f0ff] transition-colors duration-200 truncate"
                  >
                    {{ crumb.name }}
                  </RouterLink>
                </div>
              </div>
            </div>

            <!-- Arrow separator -->
            <svg
              class="h-3 w-3 mx-1 opacity-50 flex-shrink-0"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>

            <!-- Last crumb -->
            <span class="truncate max-w-[60px] sm:max-w-[100px]">
              {{ breadcrumbs[breadcrumbs.length - 1].name }}
            </span>
          </div>

          <!-- Full view: Show all breadcrumbs -->
          <div class="flex items-center flex-wrap gap-y-1" v-else>
            <div class="flex items-center" v-for="(crumb, index) in breadcrumbs" :key="index">
              <RouterLink
                v-if="index < breadcrumbs.length - 1"
                :to="crumb.path"
                class="hover:text-[#00f0ff] transition-colors duration-200 truncate max-w-[100px] md:max-w-[150px]"
              >
                {{ crumb.name }}
              </RouterLink>
              <span v-else class="truncate max-w-[100px] md:max-w-[150px]">{{ crumb.name }}</span>

              <svg
                v-if="index < breadcrumbs.length - 1"
                class="h-3 w-3 mx-1 opacity-50 flex-shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Actions -->
      <div class="flex items-center gap-2 sm:gap-3 md:gap-4 ml-auto flex-shrink-0">
        <!-- Search with transition - Hide on smallest screens -->


        <!-- Mobile search toggle -->


        <!-- Actions menu (collapses when space is limited) -->
        <div class="relative group">
          <!-- Hamburger menu for small screens -->
          <button
            @click="toggleActionMenu"
            class="p-2 sm:p-2.5 rounded-lg bg-[rgba(0,240,255,0.1)] border border-[rgba(0,240,255,0.2)] text-[#00f0ff] transition-all hover:bg-[rgba(0,240,255,0.15)] hover:-translate-y-0.5 duration-300 ease-in-out sm:hidden"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Actions dropdown for small screens -->
          <div
            v-if="showActionMenu"
            ref="actionMenuContainer"
            class="absolute right-0 mt-2 w-48 bg-[#121212] border border-[rgba(0,240,255,0.2)] rounded-lg shadow-lg overflow-hidden z-50 sm:hidden"
          >
            <div class="py-2">
              <button
                @click="(goBack(), toggleActionMenu())"
                class="flex items-center w-full px-4 py-2 text-sm text-white hover:bg-[rgba(0,240,255,0.1)] transition-colors"
              >
                <ArrowLeftIcon class="w-4 h-4 mr-2" />
                Back
              </button>
              <RouterLink
                to="/dashboard"
                @click="toggleActionMenu"
                class="flex items-center px-4 py-2 text-sm text-white hover:bg-[rgba(0,240,255,0.1)] transition-colors"
              >
                <HomeIcon class="w-4 h-4 mr-2" />
                Dashboard
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- Regular buttons for larger screens -->
        <button
          @click="goBack"
          class="hidden sm:flex p-2 sm:p-2.5 rounded-lg bg-[rgba(0,240,255,0.1)] border border-[rgba(0,240,255,0.2)] text-[#00f0ff] transition-all hover:bg-[rgba(0,240,255,0.15)] hover:-translate-y-0.5 duration-300 ease-in-out relative group"
        >
          <ArrowLeftIcon class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
          <span class="sr-only">Go Back</span>
          <!-- Tooltip -->
          <span
            class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap"
          >
            Back
          </span>
        </button>

        <!-- Home button -->
        <RouterLink
          to="/dashboard"
          class="hidden sm:flex p-2 sm:p-2.5 rounded-lg bg-[rgba(0,240,255,0.1)] border border-[rgba(0,240,255,0.2)] text-[#00f0ff] transition-all hover:bg-[rgba(0,240,255,0.15)] hover:-translate-y-0.5 duration-300 ease-in-out relative group"
        >
          <HomeIcon class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
          <span class="sr-only">Dashboard</span>
          <!-- Tooltip -->
          <span
            class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap"
          >
            Dashboard
          </span>
        </RouterLink>

        <!-- User profile with dropdown -->
        <div class="relative group">
          <div
            class="flex items-center gap-2 cursor-pointer p-1 rounded-full hover:bg-[rgba(0,240,255,0.05)] transition-all duration-200"
          >
            <img
              src="https://i.pravatar.cc/100"
              alt="User Avatar"
              class="w-7 h-7 sm:w-8 sm:h-8 rounded-full border-2 border-[rgba(0,240,255,0.3)] transition-all duration-300 group-hover:border-[#00f0ff]"
            />
            <span
              class="text-sm text-[#eeeeee] hidden md:block group-hover:text-[#00f0ff] transition-colors duration-200 truncate max-w-[100px]"
              >{{ useUserStore().name }}</span
            >
            <svg
              class="w-4 h-4 text-gray-400 hidden md:block transition-transform duration-300 group-hover:rotate-180"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>

          <!-- Dropdown menu -->
          <div
            class="absolute right-0 mt-2 w-48 bg-[#121212] border border-[rgba(0,240,255,0.2)] rounded-lg shadow-lg overflow-hidden transform scale-95 opacity-0 invisible group-hover:scale-100 group-hover:opacity-100 group-hover:visible transition-all duration-200 origin-top-right z-50"
          >
            <div class="py-2">
              <RouterLink
                to="/dashboard/profile"
                class="block px-4 py-2 text-sm text-white hover:bg-[rgba(0,240,255,0.1)] transition-colors"
              >
                Profile
              </RouterLink>
              <RouterLink
                to="/dashboard/settings"
                class="block px-4 py-2 text-sm text-white hover:bg-[rgba(0,240,255,0.1)] transition-colors"
              >
                Settings
              </RouterLink>
              <div class="border-t border-[rgba(255,255,255,0.1)] my-1"></div>
              <button
                @click="logout"
                class="w-full text-left px-4 py-2 text-sm text-[#ff6b6b] hover:bg-[rgba(255,107,107,0.1)] transition-colors"
              >
                Sign out
              </button>
            </div>
          </div>
        </div>

        <!-- Slot for custom actions -->
        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Mobile search bar (conditional) -->
    <div v-if="showMobileSearch" class="px-6 py-2 md:hidden transition-all duration-300">
      <div class="relative">
      </div>
    </div>

    <!-- Bottom border glow -->
    <div
      class="absolute bottom-0 left-0 h-[1px] w-full bg-[linear-gradient(90deg,transparent_0%,rgba(0,240,255,0.4)_50%,transparent_100%)] bg-[length:200%_100%] animate-borderFlow"
    ></div>
  </header>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useSidebarStore } from '@/stores/sidebar'
import { ArrowLeftIcon, HomeIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth.ts'
import { useUserStore } from '@/stores/user'
import { useBreadcrumbStore, Title } from '@/stores/path'
import { API } from '@/config/index'

const BREAD = useBreadcrumbStore()
const title = Title()

useUserStore().loadUser()
const authStore = useAuthStore()
const router = useRouter()
const sidebarState = useSidebarStore()
const { isOpen } = storeToRefs(sidebarState)


const showMobileSearch = ref(false)
const showActionMenu = ref(false)
const windowWidth = ref(window.innerWidth)
const isCompactView = computed(() => windowWidth.value < 640)

const currentPageTitle = computed(() => title.t)
const breadcrumbs = computed(() => BREAD.breadcrumbs)

function goBack() {
  router.back()
}

async function logout() {
  // Handle logout logic
  const { token } = storeToRefs(authStore)
  try {
    const res = await fetch(`${API}/credentials/exit`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ csrf_token: token.value }),
    })

    //const data = awa
  } catch (err) {
    console.error('Session check failed:', err)
  }

  authStore.clearToken()
  useUserStore().clearUser()

  router.push('/')
}

function toggleActionMenu() {
  showActionMenu.value = !showActionMenu.value
}
const actionMenuContainer = ref<HTMLElement | null>(null)
// Click outside handler
const handleClickOutside = (event: MouseEvent) => {
  if (actionMenuContainer.value && !actionMenuContainer.value.contains(event.target as Node)) {
    showActionMenu.value = false
  }
}

// Watch for menu state changes
watch(showActionMenu, (newValue) => {
  if (newValue) {
    setTimeout(() => {
      document.addEventListener('click', handleClickOutside)
    }, 0)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})

// Cleanup
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Window resize handler
const handleResize = () => {
  windowWidth.value = window.innerWidth

  // Auto-close mobile menus on resize to larger screens
  if (windowWidth.value >= 640) {
    showActionMenu.value = false
  }
}

// Lifecycle hooks
onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.titlebar {
  height: auto;
  min-height: 4rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes borderFlow {
  0% {
    background-position-x: -100%;
  }
  100% {
    background-position-x: 200%;
  }
}

.animate-borderFlow {
  animation: borderFlow 8s linear infinite;
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .titlebar {
    padding-left: 4rem !important;
  }
}
</style>
