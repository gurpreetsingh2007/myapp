<script setup lang="ts">
import { ref, reactive, onMounted, watch, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  HomeIcon,
  UserIcon,
  EnvelopeIcon,
  Cog6ToothIcon,
  ChartBarIcon,
  ServerIcon,
} from '@heroicons/vue/24/outline'
import { Terminal, Code, Database, HardDriveUpload  } from 'lucide-vue-next'
import { useSidebarStore } from '@/stores/sidebar'
import { storeToRefs } from 'pinia'
const sidebarState = useSidebarStore()
const { isOpen } = storeToRefs(sidebarState)

const router = useRouter()
const route = useRoute()
const windowWidth = ref(window.innerWidth)
// Reactive media query detection

// Update window width on resize
const handleResize = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})

// iconMap with strict keys
const iconMap = {
  dashboard: HomeIcon,
  profile: UserIcon,
  messages: EnvelopeIcon,
  settings: Cog6ToothIcon,
  analytics: ChartBarIcon,
  servers: ServerIcon,
  nginx: Terminal,
  php: Code,
  MariDb: Database,
  rsnapshot: HardDriveUpload,
} as const

type IconKey = keyof typeof iconMap

type MenuItem = {
  title: string
  icon: IconKey
  path: string
  active: boolean
  isDropdown?: boolean
  open?: boolean
  children?: MenuItem[]
}

const menuItems = reactive<MenuItem[]>([
  { title: 'Dashboard', icon: 'dashboard', path: '/dashboard', active: true },
  { title: 'Profile', icon: 'profile', path: '/dashboard/profile', active: false },
  { title: 'Settings', icon: 'settings', path: '/dashboard/settings', active: false },
  { title: 'Analytics', icon: 'analytics', path: '/dashboard/analytics', active: false },
  {
    title: 'Services',
    icon: 'servers',
    active: false,
    isDropdown: true,
    open: false,
    children: [
      { title: 'NGINX', path: '/dashboard/nginx', icon: 'nginx', active: false },
      { title: 'RSNAPSHOT', path: '/dashboard/rsnapshot', icon: 'rsnapshot', active: false },
      { title: 'PHP-FPM', path: '/dashboard/phpfpm', icon: 'php', active: false },
      { title: 'MariDb', path: '/dashboard/MariDb', icon: 'MariDb', active: false },
    ],
  },
])

// Set active route on load and on change
watch(
  () => route.path,
  (path) => {
    menuItems.forEach((item) => {
      if (item.children) {
        item.active = item.children.some((child) => child.path === path)
        item.open = item.active // auto-expand on match
      } else {
        item.active = item.path === path
      }
    })
  },
  { immediate: true },
)

const toggleSidebar = () => {
  sidebarState.toggle()
}
</script>
<template>
  <div class="sidebar-container">
    <!-- Toggle Button -->
    <button
      @click="toggleSidebar"
      id="togleswitch"
      class="sidebar-toggle fixed top-4 z-[9999] p-2.5 rounded-xl bg-white/90 backdrop-blur-sm border border-slate-200 text-[#005188] transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] hover:bg-white hover:-translate-y-0.5 hover:scale-110 shadow-lg hover:shadow-xl hover:shadow-[#005188]/20"
      :class="isOpen ? 'left-55' : 'left-7'"
    >
      <svg
        class="w-6 h-6 transform transition-transform duration-300"
        :class="{ 'rotate-90': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 6h16M4 12h16M4 18h16"
        />
      </svg>
    </button>

    <!-- Backdrop -->
    <Transition name="fade">
      <div
        v-if="isOpen"
        @click="toggleSidebar"
        class="fixed inset-0 z-50 bg-slate-900/20 backdrop-blur-sm md:hidden"
      ></div>
    </Transition>

    <!-- Sidebar -->
    <aside
      class="sidebar h-screen bg-gradient-to-br from-white via-slate-50/80 to-blue-50/40 border-r border-slate-200/60 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] z-50 overflow-x-hidden shadow-2xl shadow-slate-200/50"
      :class="{ 'sidebar-open': isOpen, 'sidebar-closed': !isOpen }"
    >
      <!-- Header -->
      <div
        class="sidebar-header px-6 py-5 border-b border-slate-200/40 h-16 flex items-center relative overflow-hidden"
      >
        <div class="absolute inset-0 bg-gradient-to-r from-[#005188]/5 to-[#007C52]/5 animate-pulse-slow"></div>
        <Transition name="slide-fade">
          <h2 v-if="isOpen" class="text-3xl font-bold text-slate-800 tracking-tight relative z-10">
            <span
              class="bg-gradient-to-r from-[#005188] via-[#007C52] to-[#005188] bg-clip-text text-transparent animate-gradient-shift bg-300%"
            >
              COOPOLIS
            </span>
          </h2>
        </Transition>
      </div>

      <!-- Navigation Menu -->
      <nav class="sidebar-menu mt-6 relative">
        <ul class="px-2 flex flex-col gap-2">
          <li
            v-for="(item, index) in menuItems"
            :key="index"
            class="group relative cursor-pointer transition-all duration-200"
          >
            <div
              class="flex items-center rounded-xl px-4 py-4 transition-all duration-300 ease-out hover:bg-gradient-to-r hover:from-[#005188]/10 hover:to-[#007C52]/10 hover:translate-x-2 hover:shadow-lg hover:shadow-[#005188]/10"
              :class="[
                item.active
                  ? 'bg-gradient-to-r from-[#005188]/15 to-[#007C52]/15 border-l-4 border-[#005188] shadow-md shadow-[#005188]/20'
                  : '',
                isOpen ? 'justify-start gap-6' : 'justify-center',
              ]"
              @click="item.isDropdown ? (item.open = !item.open) : router.push(item.path)"
            >
              <component
                :is="iconMap[item.icon as keyof typeof iconMap]"
                class="w-6 h-10 transition-all transform hover:scale-125 hover:rotate-12"
                :class="[
                  item.active ? 'text-[#005188]' : 'text-[#007C52]',
                  item.open ? 'animate-pulse text-[#005188]' : '',
                ]"
              />
              <Transition name="slide-fade">
                <span v-if="isOpen" class="text-sm font-semibold text-slate-700">
                  {{ item.title }}
                </span>
              </Transition>
              <svg
                v-if="item.isDropdown && isOpen"
                class="ml-auto w-4 h-4 transition-transform duration-500 text-slate-600"
                :class="{ 'rotate-90 text-[#005188]': item.open }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </div>

            <!-- Dropdown Transition -->
            <Transition
              name="dropdown"
              enter-active-class="dropdown-enter-active"
              leave-active-class="dropdown-leave-active"
            >
              <ul
                v-if="item.isDropdown && item.open"
                class="ml-2 mt-1 relative space-y-3 overflow-hidden"
                :class="{ 'pl-2': isOpen, 'px-1': !isOpen }"
              >
                <li
                  v-for="(child, cIndex) in item.children"
                  :key="cIndex"
                  class="flex items-center rounded-lg transition-all group origin-left"
                  :class="{
                    'px-3 py-2.5': isOpen,
                    'px-2 py-2 justify-center': !isOpen,
                    'bg-gradient-to-r from-[#005188]/10 to-[#007C52]/10 shadow-sm': route.path === child.path,
                  }"
                >
                  <RouterLink
                    :to="child.path"
                    class="w-full flex items-center gap-3 relative"
                    :class="{ 'flex-col text-center': !isOpen, 'flex-row': isOpen }"
                  >
                    <component
                      :is="iconMap[child.icon as IconKey]"
                      class="w-5 h-7 flex-shrink-0 transition-all duration-300"
                      :class="{
                        'text-[#005188] scale-125': route.path === child.path,
                        'text-[#007C52] group-hover:scale-110 group-hover:text-[#005188]': route.path !== child.path,
                      }"
                    />
                    <Transition name="slide-fade">
                      <span
                        v-if="isOpen"
                        class="text-sm text-slate-700 font-medium relative inline-block overflow-hidden"
                      >
                        <span class="inline-block">
                          {{ child.title }}
                          <span
                            class="absolute bottom-0 left-0 w-full h-px transition-all duration-500 origin-left"
                            :class="{
                              'bg-[#005188] scale-x-100': route.path === child.path,
                              'bg-[#007C52] scale-x-0 group-hover:scale-x-100':
                                route.path !== child.path,
                            }"
                          >
                            <span
                              class="absolute inset-0 bg-current transition-all duration-1000 opacity-50 group-hover:animate-underline-pulse"
                            ></span>
                          </span>
                        </span>
                      </span>
                    </Transition>
                    <span
                      v-if="!isOpen"
                      class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-2 text-sm bg-white/95 backdrop-blur-sm text-slate-700 rounded-lg shadow-xl border border-slate-200/60 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap origin-left scale-75 group-hover:scale-100"
                    >
                      {{ child.title }}
                      <span
                        class="absolute -left-px top-1/2 -translate-y-1/2 w-1 h-3/5 bg-gradient-to-b from-[#005188] to-[#007C52] rounded-full opacity-70 animate-pulse"
                      ></span>
                    </span>
                  </RouterLink>
                </li>
              </ul>
            </Transition>
          </li>
        </ul>
      </nav>
    </aside>
  </div>
</template>

<style scoped>
/* Base Styles */
.sidebar-container {
  height: 100%;
}

.sidebar {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-open {
  width: 260px;
}

.sidebar-closed {
  width: 80px;
}

/* Animation Keyframes */
@keyframes underline-pulse {
  0% {
    transform: translateX(-100%);
    opacity: 1;
  }
  50% {
    transform: translateX(100%);
    opacity: 0.5;
  }
  100% {
    transform: translateX(100%);
    opacity: 0;
  }
}

@keyframes gradient-shift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

@keyframes pulse-slow {
  0%,
  100% {
    opacity: 0.1;
  }
  50% {
    opacity: 0.2;
  }
}

@keyframes submenu-bounce {
  0% {
    transform: translateX(-10px);
    opacity: 0;
  }
  60% {
    transform: translateX(5px);
    opacity: 1;
  }
  100% {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Transition Effects */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

.dropdown-enter-active {
  transition:
    opacity 0.3s ease-out,
    max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-leave-active {
  transition:
    opacity 0.3s ease-in,
    max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  max-height: 0;
}

.dropdown-enter-to,
.dropdown-leave-from {
  opacity: 1;
  max-height: 500px;
}

/* Animation Utilities */
.animate-underline-pulse {
  animation: underline-pulse 1.2s ease-out infinite;
}

.animate-gradient-shift {
  animation: gradient-shift 6s ease infinite;
  background-size: 300% 300%;
}

.animate-pulse-slow {
  animation: pulse-slow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Mobile Adjustments */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    z-index: 50;
    width: 260px !important;
    transform: translateX(-100%);
    transition:
      transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      box-shadow 0.3s ease;
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
    box-shadow: 0 20px 40px rgba(0, 81, 136, 0.15);
  }

  .sidebar.sidebar-closed {
    transform: translateX(-100%);
  }

  .sidebar-toggle {
    left: 0.5rem !important;
  }

  .sidebar-toggle.left-55 {
    left: 13.5rem !important;
  }
}

/* Light Theme Enhancements */
.sidebar:hover {
  box-shadow: 0 25px 50px rgba(0, 81, 136, 0.08);
}

/* Scrollbar Styling for Light Theme */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgb(248, 250, 252);
}

.sidebar::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #005188, #007C52);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #007C52, #005188);
}
</style>
