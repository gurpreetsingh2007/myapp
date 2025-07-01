<template>
  <div class="fixed inset-0 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
      <!-- Header con gradient -->
      <div class="bg-gradient-to-r from-[#005188] to-[#007C52] p-6 flex justify-between items-center">
        <h3 class="text-2xl font-bold text-white">Server Management</h3>
        <button @click="$emit('close')" class="text-white hover:text-gray-200">
          <XMarkIcon class="h-8 w-8" />
        </button>
      </div>

      <div class="p-6 space-y-4">
        <!-- Search Bar -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
          </div>
          <input
            v-model="searchQuery"
            type="text"
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005188] focus:border-transparent shadow-sm"
            placeholder="Search servers..."
          />
        </div>

        <!-- Server List Container -->
        <div class="border border-gray-300 rounded-lg overflow-hidden">
          <div
            class="overflow-y-auto transition-all duration-200"
            :style="{
              'max-height': showAllServers ? 'none' : '308px',
              'overflow-y': filteredServers.length > 7 ? 'auto' : 'hidden'
            }"
          >
            <!-- Server Items -->
            <div
              v-for="server in filteredServers"
              :key="server.server_id"
              class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 group"
              :class="{ 'bg-blue-50': selectedServers.includes(server.server_id) }"
              @click="toggleServerSelection(server.server_id)"
            >
              <div class="flex items-center flex-1 min-w-0">
                <!-- Server Icon with status indicator -->
                <div class="relative mr-3">
                  <ServerIcon class="h-5 w-5 text-gray-500" />
                  <span
                    v-if="server.ssl_enabled"
                    class="absolute -bottom-1 -right-1 w-2 h-2 bg-green-500 rounded-full border border-white"
                  ></span>
                </div>

                <!-- Server Info -->
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-700 truncate">{{ server.server_title }}</p>
                  <div class="flex items-center mt-1 space-x-2">
                    <span class="text-xs text-gray-500">Port: {{ server.port }}</span>
                    <span
                      v-if="server.is_http2"
                      class="text-xs px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded-full"
                    >
                      HTTP/2
                    </span>
                  </div>
                </div>
              </div>

              <!-- Selection Indicator -->
              <div v-if="selectedServers.includes(server.server_id)" class="ml-2">
                <CheckIcon class="h-5 w-5 text-[#007C52]" />
              </div>
            </div>

            <!-- Empty State -->
            <div
              v-if="filteredServers.length === 0"
              class="p-4 text-center text-gray-500 text-sm"
            >
              No servers found matching your search
            </div>
          </div>
        </div>

        <!-- Selection Status -->
        <div class="flex justify-between items-center pt-2">
          <span class="text-xs text-gray-500">
            {{ selectedServers.length }} server{{ selectedServers.length !== 1 ? 's' : '' }} selected
          </span>
          <button
            @click="toggleSelectAll"
            type="button"
            class="text-xs text-[#005188] hover:text-[#003d6a] font-medium"
          >
            {{ selectedServers.length === filteredServers.length ? 'Deselect all' : 'Select all' }}
          </button>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
          <button
            @click="$emit('close')"
            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 shadow-sm"
          >
            Cancel
          </button>
          <button
            @click="executeAction"
            :disabled="selectedServers.length === 0"
            :class="[
              'px-6 py-2.5 bg-gradient-to-r from-[#005188] to-[#007C52] text-white rounded-lg shadow-sm transition-all duration-200',
              { 'opacity-70 cursor-not-allowed': selectedServers.length === 0 },
              { 'hover:from-[#003d6a] hover:to-[#006044] hover:shadow-md': selectedServers.length > 0 }
            ]"
          >
            Execute
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'
import {
  ServerIcon,
  XMarkIcon,
  MagnifyingGlassIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['close', 'execute'])

const nginxStore = useNginxStore()
const selectedServers = ref<number[]>([])
const searchQuery = ref('')
const showAllServers = ref(false)

// Load servers if not already loaded
onMounted(async () => {
  if (nginxStore.servers.length === 0) {
    await nginxStore.fetchServers()
  }
})

// Filter servers based on title only
const filteredServers = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return nginxStore.servers.filter(server =>
    server.server_title.toLowerCase().includes(query)
  )
})

// Toggle server selection
const toggleServerSelection = (serverId: number) => {
  const index = selectedServers.value.indexOf(serverId)
  if (index === -1) {
    selectedServers.value.push(serverId)
  } else {
    selectedServers.value.splice(index, 1)
  }
  showAllServers.value = false
}

// Toggle select all/deselect all
const toggleSelectAll = () => {
  if (selectedServers.value.length === filteredServers.value.length) {
    selectedServers.value = []
    showAllServers.value = false
  } else {
    selectedServers.value = filteredServers.value.map(server => server.server_id)
    showAllServers.value = true
  }
}

// Execute action
const executeAction = () => {
  if (selectedServers.value.length === 0) return
  emit('execute', selectedServers.value)
}
</script>

<style scoped>
/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Smooth transitions */
.border-b {
  transition: border-color 0.2s ease;
}

.bg-blue-50 {
  transition: background-color 0.2s ease;
}
</style>
