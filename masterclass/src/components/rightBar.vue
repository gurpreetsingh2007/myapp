<template>
  <div class="flex flex-col h-full md:flex-row">
    <!-- Main Content Area -->

    <!-- Backdrop (mobile only) -->
    <Transition name="fade">
      <div
        v-if="sidebar.isOpen && isMobile"
        @click="toggleSidebar"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
      ></div>
    </Transition>

    <!-- Sidebar -->
    <aside
      class="h-full flex flex-col flex-shrink-0 bg-gradient-to-b from-[var(--bg-color)] to-[rgba(0,0,0,0.95)] border-l border-[rgba(0,240,255,0.1)] transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] z-50 shadow-2xl shadow-[rgba(0,240,255,0.05)]"
      :class="{
        'w-80': sidebar.isOpen,
        'w-0': !sidebar.isOpen,
      }"
    >
      <div
        class="relative px-6 py-5 border-b border-[rgba(0,240,255,0.08)] h-16 flex items-center justify-center overflow-hidden flex-shrink-0"
      >
        <!-- Background pulse layer -->
        <div
          class="absolute inset-0 bg-[rgba(0,240,255,0.02)] animate-pulse-slow pointer-events-none z-0"
        ></div>

        <!-- Centered content group -->
        <div class="relative z-10 flex items-center gap-4">
          <!-- Title -->
          <Transition name="slide-fade">
            <h2
              v-if="sidebar.isOpen"
              class="text-xl font-semibold text-[var(--text-color)] tracking-tight"
            >
              <span
                class="bg-gradient-to-r from-[#00f0ff] via-[#a200ff] to-[#d000ff] bg-clip-text text-transparent animate-gradient-shift bg-300%"
              >
                Blocks
              </span>
            </h2>
          </Transition>

          <!-- Add New Block Button -->
          <Transition name="slide-fade">
            <button
              v-if="sidebar.isOpen"
              @click="openNewBlockDialog"
              class="flex items-center justify-center p-2 rounded-full bg-[rgba(0,240,255,0.1)] border border-[rgba(0,240,255,0.2)] text-[#00f0ff] transition-all duration-300 hover:bg-[rgba(0,240,255,0.15)] hover:scale-110"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                />
              </svg>
            </button>
          </Transition>
        </div>
      </div>

      <!-- Content - Scrollable Area -->
      <div
        ref="scrollRef"
        class="flex-grow overflow-y-auto bg-cover bg-center"
        :class="{ 'opacity-100': sidebar.isOpen, 'opacity-0': !sidebar.isOpen }"
        :style="{ backgroundImage: `url(${sidebar.backgroundImageUrl})` }"
      >

        <div class="p-6">
          <!-- Loading state -->
          <div v-if="loading" class="flex justify-center items-center h-40">
            <div
              class="w-12 h-12 rounded-sm border-4 border-t-[#00f0ff] border-r-[#a200ff] border-b-[#d000ff] border-l-transparent animate-spin"
            ></div>
          </div>

          <!-- Error state -->
          <div
            v-else-if="error"
            class="p-4 bg-[rgba(255,0,0,0.1)] rounded-lg border border-[rgba(255,0,0,0.2)] text-red-400"
          >
            {{ error }}
          </div>

          <!-- Config Items -->
          <div v-else class="grid grid-cols-1 gap-4">
            <div
              v-for="row in configRows"
              :key="row.id"
              class="group relative top-1 p-5 rounded-xl bg-[rgba(0,0,0,0.4)] backdrop-blur-sm border border-[rgba(0,240,255,0.1)] hover:border-[rgba(0,240,255,0.3)] transition-all duration-300 cursor-pointer text-[var(--text-color)] hover:-translate-y-1"
            >
              <!-- Background glow effect -->
              <div
                class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-br from-[rgba(0,240,255,0.05)] to-[rgba(208,0,255,0.05)] z-0"
              ></div>

              <!-- Enhanced Three Dots Menu -->
              <div class="absolute top-4 right-3 z-[999]">
                <div class="relative">
                  <!-- Menu Trigger Button -->
                  <button
                    @click.stop="toggleMenu(row.id)"
                    class="p-1.5 rounded-lg transition-all duration-300 hover:bg-[rgba(0,240,255,0.1)] group/trigger"
                    :class="{ 'bg-[rgba(0,240,255,0.1)]': activeMenu === row.id }"
                  >
                    <div class="relative">
                      <!-- Animated background circle -->
                      <div
                        class="absolute inset-0 rounded-lg bg-[#00f0ff] opacity-0 group-hover/trigger:opacity-10 transition-opacity duration-300"
                      ></div>
                      <!-- Dots icon -->

                      <svg
                        class="w-6 h-6 text-[#00f0ff] transform transition-transform duration-300 hover:scale-110"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
                        />
                      </svg>
                    </div>
                  </button>

                  <!-- Enhanced Dropdown Menu -->
                  <transition
                    enter-active-class="transition-all duration-200 ease-out"
                    leave-active-class="transition-all duration-150 ease-in"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                  >
                    <div
                      v-if="activeMenu === row.id"
                      class="absolute right-8 top-3 mt- w-40 origin-top-right z-[9999] overflow: !visible !important"
                    >
                      <div
                        class="relative border border-[rgba(0,240,255,0.3)] rounded-xl bg-[rgba(12,12,12,0.95)] backdrop-blur-lg shadow-2xl shadow-[rgba(0,240,255,0.1)] overflow-hidden"
                      >
                        <!-- Animated border glow -->
                        <div
                          class="absolute inset-0 rounded-xl border-2 border-[rgba(0,240,255,0.1)] pointer-events-none animate-pulse-slow"
                        ></div>

                        <!-- Menu Items -->
                        <div class="space-y-1 p-1.5">
                          <button
                            @click.stop="editBlock(row)"
                            class="flex items-center w-full px-4 py-2.5 rounded-lg text-sm text-[var(--text-color)] hover:bg-[rgba(0,240,255,0.1)] transition-all duration-300 group/edit"
                          >
                            <div class="mr-3 relative">
                              <svg
                                class="w-5 h-5 text-[#00f0ff] group-hover/edit:animate-pulse"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                              >
                                <path
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                              </svg>
                            </div>
                            <span>Edit Block</span>
                          </button>

                          <button
                            @click.stop="deleteBlock(row.id)"
                            class="flex items-center w-full px-4 py-2.5 rounded-lg text-sm text-red-300 hover:bg-[rgba(255,0,0,0.1)] transition-all duration-300 group/delete"
                          >
                            <div class="mr-3 relative">
                              <svg
                                class="w-5 h-5 text-red-400 group-hover/delete:animate-pulse"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                              >
                                <path
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                              </svg>
                            </div>
                            <span class="relative">
                              Delete
                              <span
                                class="absolute bottom-0 left-0 w-full h-px bg-red-400 scale-x-0 group-hover/delete:scale-x-100 transition-transform duration-300"
                              ></span>
                            </span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>
              </div>

              <!-- Button content -->
              <div
                @click="
                  navigateTo(
                    `/dashboard/config?block_id=${path.info.service}&section_id=${encodeURIComponent(path.info.sectionId)}&store_id=${row.title}&store_number=${row.id}`,
                  )
                "
                class="relative z-10 flex flex-col items-center top-0.5 space-y-3"
              >
                <div
                  class="bg-[rgba(0,0,0,0.6)] rounded-md px-3 py-1 border border-[rgba(0,240,255,0.2)]"
                >
                  <span
                    class="text-md font-bold bg-gradient-to-r from-[#00f0ff] to-[#d000ff] bg-clip-text text-transparent"
                  >
                    #{{ row.id }}
                  </span>
                </div>
                <span class="text-lg  font-small tracking-wide text-center">{{ row.title }}</span>
                <div
                  class="h-0.5 w-10 mt-2 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 bg-gradient-to-r from-[#00f0ff] to-[#d000ff]"
                ></div>
              </div>

              <!-- Animated corner decorations -->
              <div
                class="absolute top-0 left-0 w-3 h-3 border-t border-l border-[#00f0ff] rounded-tl-lg animate-pulse-slow"
              ></div>
              <div
                class="absolute top-0 right-0 w-3 h-3 border-t border-r border-[#d000ff] rounded-tr-lg animate-pulse-slow"
              ></div>
              <div
                class="absolute bottom-0 left-0 w-3 h-3 border-b border-l border-[#d000ff] rounded-bl-lg animate-pulse-slow"
              ></div>
              <div
                class="absolute bottom-0 right-0 w-3 h-3 border-b border-r border-[#00f0ff] rounded-br-lg animate-pulse-slow"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <!-- Enhanced New Block Dialog -->
  <Transition name="dialog">
    <div v-if="showNewBlockDialog" class="fixed inset-0 z-[100] flex items-center justify-center">
      <div
        class="absolute inset-0 bg-black/80 backdrop-blur-xsm transition-opacity"
        @click="showNewBlockDialog = false"
      ></div>
      <div
        class="relative z-10 bg-gradient-to-br from-[rgba(12,12,12,0.95)] to-[rgba(20,20,20,0.98)] border border-[rgba(0,240,255,0.3)] rounded-2xl w-full max-w-md p-8 shadow-2xl shadow-[rgba(0,240,255,0.15)] transform transition-all"
      >
        <!-- Animated border gradient -->
        <div class="absolute inset-0 rounded-2xl pointer-events-none">
          <div
            class="absolute -inset-1 bg-gradient-to-r from-[#00f0ff55] to-[#d000ff55] rounded-2xl blur-lg opacity-30 animate-pulse-slow"
          ></div>
        </div>

        <!-- Noise texture overlay -->
        <div class="absolute inset-0 rounded-2xl bg-noise opacity-10 pointer-events-none"></div>

        <h3 class="text-2xl font-bold text-[var(--text-color)] mb-6 relative">
          <span class="bg-gradient-to-r from-[#00f0ff] to-[#d000ff] bg-clip-text text-transparent">
            {{ editMode ? 'Edit Block' : 'Create New Block' }}
          </span>
          <div
            class="h-[2px] mt-1 bg-gradient-to-r from-[#00f0ff] to-[#d000ff] w-20 rounded-full animate-gradient-shift"
          ></div>
        </h3>

        <div class="space-y-6">
          <div>
            <label for="blockTitle" class="block text-sm font-medium text-[#00f0ff] mb-2"
              >Title</label
            >
            <div class="relative">
              <input
                type="text"
                id="blockTitle"
                v-model="newBlockTitle"
                class="w-full bg-[rgba(0,0,0,0.6)] border-2 border-[rgba(0,240,255,0.3)] rounded-xl px-4 py-3 text-[var(--text-color)] focus:outline-none focus:border-[#00f0ff] focus:ring-2 focus:ring-[#00f0ff55] placeholder-gray-500 transition-all duration-300"
                placeholder="Enter block title"
              />
              <div
                class="absolute inset-0 rounded-xl border border-[#00f0ff33] pointer-events-none"
              ></div>
            </div>
          </div>

          <div class="flex justify-end space-x-4 mt-8">
            <button
              @click="showNewBlockDialog = false"
              class="px-5 py-2.5 rounded-xl border-2 border-[rgba(0,240,255,0.3)] bg-[rgba(0,0,0,0.4)] text-[#00f0ff] hover:bg-[rgba(0,240,255,0.1)] hover:border-[#00f0ff] hover:scale-[1.02] transition-all duration-300 flex items-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              Cancel
            </button>
            <button
              @click="saveBlock"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#00f0ff] to-[#d000ff] text-black font-semibold hover:shadow-[0_0_15px_-3px_rgba(0,240,255,0.5)] hover:scale-[1.02] transition-all duration-300 relative overflow-hidden group"
            >
              <div
                class="absolute inset-0 bg-gradient-to-r from-[#00f0ff55] to-[#d000ff55] opacity-0 group-hover:opacity-100 transition-opacity duration-300"
              ></div>
              <span class="relative z-10 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                {{ editMode ? 'Update' : 'Create' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Enhanced Delete Confirmation Dialog -->
  <Transition name="dialog">
    <div v-if="showDeleteDialog" class="fixed inset-0 z-[100] flex items-center justify-center">
      <div
        class="absolute inset-0 bg-black/80 backdrop-blur-xsm transition-opacity"
        @click="showDeleteDialog = false"
      ></div>
      <div
        class="relative z-10 bg-gradient-to-br from-[rgba(20,0,0,0.95)] to-[rgba(30,0,0,0.98)] border border-[rgba(255,0,0,0.3)] rounded-2xl w-full max-w-md p-8 shadow-2xl shadow-[rgba(255,0,0,0.15)] transform transition-all"
      >
        <!-- Danger pulse effect -->
        <div class="absolute inset-0 rounded-2xl pointer-events-none">
          <div
            class="absolute -inset-1 bg-gradient-to-r from-[#ff000055] to-[#ff006655] rounded-2xl blur-lg opacity-30 animate-pulse"
          ></div>
        </div>

        <div class="text-center">
          <div class="mb-5 inline-block p-4 bg-red-900/30 rounded-full">
            <svg
              class="w-12 h-12 text-red-400 animate-pulse"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>

          <h3 class="text-2xl font-bold text-red-400 mb-3">Confirm Deletion</h3>
          <p class="text-red-200/90 mb-6 px-4 leading-relaxed">
            Are you sure you want to delete this block?<br />
            <span class="text-red-300 font-medium"
              >This action can be undone from the history!</span
            >
          </p>

          <div class="flex justify-center space-x-4">
            <button
              @click="showDeleteDialog = false"
              class="px-5 py-2.5 rounded-xl border-2 border-[rgba(255,255,255,0.2)] bg-[rgba(0,0,0,0.4)] text-red-100 hover:bg-[rgba(255,255,255,0.05)] hover:scale-[1.02] transition-all duration-300"
            >
              Cancel
            </button>
            <button
              @click="confirmDelete"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-br from-red-600 to-pink-600 text-white font-semibold hover:shadow-[0_0_15px_-3px_rgba(255,0,0,0.5)] hover:scale-[1.02] transition-all duration-300 relative overflow-hidden group"
            >
              <div
                class="absolute inset-0 bg-gradient-to-br from-red-700 to-pink-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
              ></div>
              <span class="relative z-10 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
                Delete
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onBeforeMount, onBeforeUnmount, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Path } from '@/stores/path'
import { API } from '@/config/index'
import { useRightSidebarStore } from '@/stores/sidebar.ts'


import { useJsonDataStore } from '@/stores/block'
const jsonDataStore = useJsonDataStore()
const sidebar = useRightSidebarStore()

const router = useRouter()
const route = useRoute()
const path = Path()
const filePath = computed(() => path.info.sectionId)
const isMobile = ref(false)
interface ConfigRow {
  id: string
  title: string
  // other fields as needed
}
const configRows = ref<ConfigRow[]>([])

const loading = ref(true)
const error = ref<string | null>(null)

// Menu state
const activeMenu = ref<string | null>(null)

// Dialog states
const showNewBlockDialog = ref(false)
const showDeleteDialog = ref(false)
const newBlockTitle = ref('')
const editMode = ref(false)
const currentBlockId = ref<string | null>(null)

const navigateTo = (route: string) => {
  router.push(route)
}

const toggleSidebar = () => {
  sidebar.isOpen = !sidebar.isOpen
  // Emit event to parent component so it can adjust layout if needed
  emit('toggle', sidebar.isOpen)
}

// Toggle menu dropdown
const toggleMenu = (id: string) => {
  if (activeMenu.value === id) {
    activeMenu.value = null
  } else {
    activeMenu.value = id
  }
}

// Close menu when clicking outside
const closeMenuOnOutsideClick = (event: MouseEvent) => {
  if (activeMenu.value !== null) {
    activeMenu.value = null
  }
}

// New block dialog
const openNewBlockDialog = () => {
  editMode.value = false
  newBlockTitle.value = ''
  showNewBlockDialog.value = true
}

// Edit block
const editBlock = (block: any) => {
  editMode.value = true
  currentBlockId.value = block.id
  newBlockTitle.value = block.title
  showNewBlockDialog.value = true
  activeMenu.value = null
}

// Delete block
const deleteBlock = (id: string) => {
  currentBlockId.value = id
  showDeleteDialog.value = true
  activeMenu.value = null
}
const fetchBlock = async () => {
  const path_name = path.info.sectionId
  const id = path.info.store_number
  try {
    await jsonDataStore.fetchJsonData(path_name, id)
  } catch (err) {
    console.error('Failed to fetch block data:', err)
  }
}

// Confirm deletion
const confirmDelete = async () => {
  try {
    // API call to delete block
    const res = await fetch(
      API +
        `/backend/credentials/delete/block?id=${currentBlockId.value}&path=${encodeURIComponent(filePath.value)}`,
      {
        method: 'DELETE',
      },
    )

    if (!res.ok) {
      throw new Error('Failed to delete block')
    }

    // Remove block from UI
    configRows.value = configRows.value.filter((row) => row.id !== currentBlockId.value)
    showDeleteDialog.value = false
  } catch (err: any) {
    error.value = err?.message || 'Failed to delete block.'
  }
}

// Save block (create or update)
const saveBlock = async () => {
  if (!newBlockTitle.value.trim()) {
    return
  }

  try {
    const endpoint = editMode.value
      ? `/backend/credentials/update/block?id=${currentBlockId.value}&path=${encodeURIComponent(filePath.value)}`
      : `/backend/credentials/create/block?path=${encodeURIComponent(filePath.value)}`

    const method = editMode.value ? 'PUT' : 'POST'

    const res = await fetch(API + endpoint, {
      method,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        title: newBlockTitle.value,
        // You can add more data here if needed
        // json_data: {} // Optional: Add custom JSON data for the block
      }),
    })

    if (!res.ok) {
      throw new Error(`Failed to ${editMode.value ? 'update' : 'create'} block`)
    }

    const data = await res.json()

    if (editMode.value) {
      // Update existing block
      const index = configRows.value.findIndex((row) => row.id === currentBlockId.value)
      if (index !== -1) {
        configRows.value[index].title = newBlockTitle.value
      }
    } else {
      // Add new block to the list
      configRows.value.push({
        id: data.id,
        title: newBlockTitle.value,
      })
    }

    showNewBlockDialog.value = false
  } catch (err: any) {
    error.value = err?.message || `Failed to ${editMode.value ? 'update' : 'create'} block.`
  }
}

// Define emits
const emit = defineEmits(['toggle'])

// Check if device is mobile
const checkIfMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onBeforeMount(() => {
  checkIfMobile()
  window.addEventListener('resize', checkIfMobile)
  document.addEventListener('click', closeMenuOnOutsideClick)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkIfMobile)
  document.removeEventListener('click', closeMenuOnOutsideClick)
})

onMounted(async () => {
  try {
    const res = await fetch(
      API + `/backend/credentials/get/block?path=${encodeURIComponent(filePath.value)}`,
    )

    if (!res.ok) {
      throw new Error('Failed to fetch config')
    }

    const data = await res.json()
    configRows.value = data.rows
  } catch (err: any) {
    error.value = err?.message || 'Failed to load config.'
  } finally {
    loading.value = false
  }
  fetchBlock()
})
watch(
  () => route.fullPath,
  async () => {
    fetchBlock()
  },
  { immediate: true },
)
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.dialog-enter-active,
.dialog-leave-active {
  transition: all 0.3s ease;
}
.dialog-enter-from,
.dialog-leave-to {
  transform: scale(0.9);
  opacity: 0;
}

@keyframes pulse-slow {
  0% {
    opacity: 0.3;
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0.3;
  }
}

.animate-pulse-slow {
  animation: pulse-slow 3s infinite;
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

.animate-gradient-shift {
  animation: gradient-shift 3s infinite;
}

.bg-300\% {
  background-size: 300% 300%;
}

:root {
  --bg-color: rgba(0, 0, 0, 0.8);
  --text-color: #ffffff;
}

/* Custom scrollbar for the sidebar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.3);
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #00f0ff, #d000ff);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #00f0ff, #a200ff);
}
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-slow {
  0%,
  100% {
    opacity: 0.3;
  }
  50% {
    opacity: 0.15;
  }
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}
</style>
