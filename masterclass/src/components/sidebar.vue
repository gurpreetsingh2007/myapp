<template>
  <div>
    <!-- Toggle Button -->
    <button
      @click="toggleSidebar"
      class="sidebar-toggle fixed top-6 z-[1001] p-2.5 rounded-lg bg-[rgba(0,240,255,0.1)] border border-[rgba(0,240,255,0.2)] text-[#00f0ff] transition-all hover:bg-[rgba(0,240,255,0.15)] hover:-translate-y-0.5 duration-300 ease-in-out"
      :class="isOpen ? 'left-55' : 'left-7'"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 6h16M4 12h16M4 18h16"
        />
      </svg>
    </button>

    <!-- Backdrop -->
    <div
      v-if="isOpen"
      @click="toggleSidebar"
      class="fixed inset-0 z-[999] bg-black/50 backdrop-blur-sm md:hidden"
    ></div>

    <!-- Sidebar -->
    <aside
      class="sidebar fixed left-0 top-0 h-screen w-20 bg-[var(--bg-color)] border-r border-[rgba(0,240,255,0.1)] transition-all duration-300 z-[1000] overflow-x-hidden"
      :class="{ open: isOpen, 'w-64 shadow-xl': isOpen }"
    >
      <div
        class="sidebar-header px-6 py-5 border-b border-[rgba(0,240,255,0.08)] h-16 flex items-center"
      >
        <h2 v-show="isOpen" class="text-xl font-semibold text-[var(--text-color)] tracking-tight">
          <span class="bg-gradient-to-r from-[#00f0ff] to-[#d000ff] bg-clip-text text-transparent"
            >GURPREET SINGH</span
          >
        </h2>
      </div>

      <nav class="sidebar-menu mt-6">
        <ul class="px-2 flex flex-col gap-3">
          <li
            v-for="(item, index) in menuItems"
            :key="index"
            class="group relative cursor-pointer transition-all duration-200"
          >
            <RouterLink
              :to="item.path"
              class="flex items-center rounded-md px-4 py-4 transition-all duration-200 hover:bg-[rgba(0,240,255,0.05)] hover:translate-x-1"
              :class="[
                item.active
                  ? 'bg-gradient-to-r from-[rgba(0,240,255,0.1)] to-[rgba(208,0,255,0.1)] border-l-4 border-[#00f0ff]'
                  : '',
                isOpen ? 'justify-start gap-3' : 'justify-center',
              ]"
            >
              <component
                :is="iconMap[item.icon as IconKey]"
                class="w-6 h-6 transition-all"
                :class="item.active ? 'text-[#d000ff]' : 'text-[#00f0ff]'"
              />
              <span
                v-if="isOpen"
                class="text-sm font-medium text-[var(--text-color)] transition-opacity duration-300 opacity-100"
              >
                {{ item.title }}
              </span>
            </RouterLink>
          </li>
        </ul>
      </nav>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  HomeIcon,
  UserIcon,
  EnvelopeIcon,
  Cog6ToothIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()

const iconMap = {
  dashboard: HomeIcon,
  profile: UserIcon,
  messages: EnvelopeIcon,
  settings: Cog6ToothIcon,
  analytics: ChartBarIcon,
} as const

const isOpen = ref(false)

const menuItems = reactive([
  { title: 'Dashboard', icon: 'dashboard', path: '/dashboard/dashboard', active: false },
  { title: 'Profile', icon: 'profile', path: '/dashboard/profile', active: false },
  { title: 'Messages', icon: 'messages', path: '/dashboard/messages', active: false },
  { title: 'Settings', icon: 'settings', path: '/dashboard/settings', active: false },
  { title: 'Analytics', icon: 'analytics', path: '/dashboard/analytics', active: false },
])
type IconKey = keyof typeof iconMap

// Set active route on load and on change
watch(
  () => route.path,
  (path) => {
    menuItems.forEach((item) => (item.active = item.path === path))
  },
  { immediate: true },
)

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
}
</script>

<style scoped>
.sidebar {
  background: var(--bg-color);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1000;
  overflow-x: hidden;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar.open {
  width: 260px;
}

.sidebar-toggle {
  transition: all 0.2s ease;
}

.sidebar-toggle:hover {
  background: rgba(0, 240, 255, 0.15);
  transform: translateY(-1px);
}

.sidebar-menu li.active {
  font-weight: 600;
}
</style>
