<script setup lang="ts">
import { ref, watch, onMounted, shallowRef, defineAsyncComponent, nextTick } from 'vue'
import Rightbar from '@/components/rightBar.vue'
import { useRightSidebarStore } from '@/stores/sidebar.ts'

const ComponentB = defineAsyncComponent(() => import('@/components/TextEditor.vue'))
const ComponentA = defineAsyncComponent(() => import('@/components/DragAndDrop.vue'))

const sidebar = useRightSidebarStore()
const isComponentToggled = ref(false)
const activeComponent = shallowRef(ComponentA)
const isPulsing = ref(false)

const toggleComponent = () => {
  isComponentToggled.value = !isComponentToggled.value
  activeComponent.value = isComponentToggled.value ? ComponentB : ComponentA
  nextTick(() => updateLayout())
}

const toggleSidebar = () => {
  sidebar.toggle()
  isPulsing.value = true
  setTimeout(() => (isPulsing.value = false), 600)
  nextTick(() => updateLayout())

}

const componentWidth = ref('100%')

const updateLayout = () => {
  const sidebarWidth = sidebar.isOpen
    ? parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width'))
    : 0

  componentWidth.value = `${window.innerWidth - sidebarWidth}px`
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  const sidebarEl = document.querySelector('.rightbar-column')
  if (sidebarEl) {
    const w = window.getComputedStyle(sidebarEl).width
    document.documentElement.style.setProperty('--sidebar-width', w)
  }
  updateLayout()
  window.addEventListener('resize', updateLayout)
  return () => window.removeEventListener('resize', updateLayout)
})

watch(
  () => sidebar.isOpen,
  () => {
    nextTick(() => updateLayout())
  },
)

const handleSidebarToggle = (isOpen: boolean) => {
  sidebar.isOpen = isOpen
}
</script>

<template>
  <div class="main-container w-full h-full flex flex-row bg-gradient-to-br from-blue-50 to-green-50">
    <!-- Column 1: Active Component -->
    <div class="flex-1 h-full flex items-center justify-center overflow-hidden">
      <div class="component-display w-full h-full flex justify-center items-center">
        <component :is="activeComponent" />
      </div>
    </div>

    <!-- Column 2: Buttons -->
    <div class="buttons-column w-10 flex flex-col items-center justify-start px-2 pt-4 space-y-4">
      <!-- Sidebar Toggle Button -->
      <button
        @click="toggleSidebar"
        id="rightToggleSwitch"
        class="sidebar-toggle p-2.5 rounded-md bg-white/90 border z-60 border-[#005188]/20 text-[#005188] transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] hover:bg-white hover:-translate-y-0.5 hover:scale-110 shadow-md hover:shadow-[0_0_15px_rgba(0,81,136,0.2)] active:scale-90 active:shadow-inner active:bg-[#005188]/10"
        :class="{ 'pulse-animation': isPulsing }"
      >
        <div class="relative">
          <svg
            class="w-6 h-6 transform transition-transform duration-300"
            :class="{ 'rotate-90': sidebar.isOpen }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              v-if="!sidebar.isOpen"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 5l7 7-7 7M5 5l7 7-7 7"
            />
          </svg>
          <span class="button-glow absolute inset-0 rounded-lg bg-[#005188]/10"></span>
        </div>
      </button>

      <!-- Vertical Toggle -->
      <div
        class="vertical-toggle relative top-5 w-8 h-24 rounded-lg border-1 border-[#007C52]/30 bg-gradient-to-b from-[#007C52]/05 to-[#005188]/15 flex flex-col justify-center items-center cursor-pointer transition-all duration-300 hover:scale-[1.02] group shadow-[0_0_30px_rgba(0,124,82,0.1)] hover:shadow-[0_0_40px_rgba(0,81,136,0.15)]"
        @click="toggleComponent"
      >
        <div class="absolute inset-0 rounded-lg overflow-hidden">
          <div
            class="absolute inset-0 bg-[conic-gradient(from_var(--angle),transparent_20%,#005188,transparent_80%)] opacity-20 animate-rotate"
          ></div>
        </div>

        <div
          class="toggle-marker absolute w-4 h-5 bg-white/95 rounded-full shadow-md transition-[transform,box-shadow] duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] backdrop-blur-sm flex items-center justify-center border border-[#005188]/30"
          :class="isComponentToggled ? 'translate-y-7 glow-green' : '-translate-y-7 glow-blue'"
        >
          <span
            class="text-xs font-bold transition-colors duration-200"
            :class="isComponentToggled ? 'text-[#007C52]' : 'text-[#005188]'"
          >
            {{ isComponentToggled ? 'B' : 'A' }}
          </span>
          <div
            class="absolute inset-0 rounded-full bg-[radial-gradient(at_65%_15%,#fff_0%,transparent_70%)]"
          ></div>
        </div>

        <div class="absolute top-3 w-full flex justify-center">
          <div class="w-1 h-1 rounded-full bg-[#005188]/40"></div>
        </div>
        <div class="absolute bottom-3 w-full flex justify-center">
          <div class="w-1 h-1 rounded-full bg-[#005188]/40"></div>
        </div>
      </div>
    </div>

    <!-- Column 3: Rightbar -->
    <div class="rightbar-column h-full">
      <Rightbar class="h-full w-full" @toggle="handleSidebarToggle" />
    </div>
  </div>
</template>

<style scoped>
.nginx-editor {
  --cm-foreground: #005188;
  --cm-background: #ffffff;
  --cm-gutter: #00518830;
  --cm-selection: #00518815;
  --cm-keyword: #007C52;
  --cm-string: #005188;
  --cm-comment: #00518880;
  --cm-border: #00518820;
}

.nginx-editor .cm-editor {
  background: var(--cm-background) !important;
  font-family: 'Fira Code', monospace !important;
  font-size: 14px;
  text-align: left !important;
}

.nginx-editor .cm-content {
  color: var(--cm-foreground) !important;
  padding-left: 1rem !important;
}

.nginx-editor .cm-line {
  padding-left: 0 !important;
}

.nginx-editor .cm-gutters {
  background: var(--cm-background) !important;
  border-right: 1px solid var(--cm-border) !important;
  color: var(--cm-gutter) !important;
}

.nginx-editor .cm-activeLine {
  background: var(--cm-selection) !important;
}

.nginx-editor .cm-keyword {
  color: var(--cm-keyword);
}
.nginx-editor .cm-string {
  color: var(--cm-string);
}
.nginx-editor .cm-comment {
  color: var(--cm-comment);
}

.nginx-editor .cm-scroller::-webkit-scrollbar {
  width: 8px;
  background: var(--cm-background);
}

.nginx-editor .cm-scroller::-webkit-scrollbar-thumb {
  background: var(--cm-foreground);
  border-radius: 4px;
}

@keyframes rotate {
  from {
    --angle: 0deg;
  }
  to {
    --angle: 360deg;
  }
}

@property --angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}

.bg-cyber-conic-gradient {
  background: conic-gradient(from var(--angle), transparent 20%, #005188, transparent 80%);
}

.animate-rotate {
  animation: rotate 4s linear infinite;
}

.glow-blue {
  box-shadow: 0 0 10px rgba(0, 81, 136, 0.3);
}

.glow-green {
  box-shadow: 0 0 10px rgba(0, 124, 82, 0.3);
}

.pulse-animation {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(0, 81, 136, 0.4);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(0, 81, 136, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(0, 81, 136, 0);
  }
}
</style>
