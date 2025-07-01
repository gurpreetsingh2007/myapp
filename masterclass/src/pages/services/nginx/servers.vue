<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-emerald-50 flex flex-col">
    <AddProxyBlockModal v-if="showProxyModal" @close="showProxyModal = false" />
    <UpdateProxyBlock
  v-if="showUpdateModal"
  :server-id="selectedServerId"
  @close="showUpdateModal = false"
/>
    <div class="container mx-auto px-4 py-6 flex-1 flex flex-col">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-1">Nginx Servers</h1>
        <p class="text-gray-600">Manage your server configurations</p>
      </div>

      <!-- Action Bar -->
      <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow border border-white/20 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 items-stretch">
          <!-- Action Buttons -->
          <div class="flex flex-wrap gap-2 flex-1">
            <button
              @click="addServer"
              class="flex items-center px-4 py-2 bg-gradient-to-r from-[#005188] to-[#0066aa] text-white font-medium rounded-lg shadow hover:shadow-md transition-all"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              Add Server
            </button>

            <button
              @click="deleteSelected"
              :disabled="selectedServers.length === 0"
              class="flex items-center px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white font-medium rounded-lg shadow hover:shadow-md transition-all disabled:opacity-50"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Delete
            </button>

            <button
              @click="openGeneralParameters"
              class="flex items-center px-4 py-2 bg-gradient-to-r from-[#007C52] to-[#009966] text-white font-medium rounded-lg shadow hover:shadow-md transition-all"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Settings
            </button>
          </div>

          <!-- Search and Refresh -->
          <div class="flex flex-col sm:flex-row gap-2 flex-1 sm:max-w-md">
            <div class="relative flex-1">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 bg-white/90 transition-all"
              />
            </div>

            <button
              @click="loadServers"
              class="flex items-center justify-center px-4 py-2 bg-white/90 text-gray-700 font-medium rounded-lg shadow border border-gray-200 hover:border-gray-300 transition-all"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="nginxStore.isLoading" class="flex-1 flex items-center justify-center">
        <div class="text-center space-y-4">
          <div class="inline-flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-[#005188]/10 border-t-[#005188]"></div>
          </div>
          <p class="text-gray-600">Loading server configurations...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="nginxStore.error" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          <div>
            <p class="text-red-700 font-medium">Error loading servers</p>
            <p class="text-red-600 text-sm mt-1">{{ nginxStore.error }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <div v-if="!nginxStore.isLoading && !nginxStore.error" class="flex-1 flex flex-col">
        <!-- No Data State -->
        <div v-if="filteredServers.length === 0" class="flex-1 flex flex-col items-center justify-center text-center space-y-4">
          <div class="w-16 h-16 bg-gradient-to-r from-[#005188]/10 to-[#007C52]/10 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <p class="text-gray-600 font-medium">No servers found</p>
          <button
            @click="addServer"
            class="flex items-center px-6 py-2 bg-gradient-to-r from-[#005188] to-[#0066aa] text-white font-medium rounded-lg shadow hover:shadow-md transition-all"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Your First Server
          </button>
        </div>

        <!-- Table Container -->
        <div v-else class="flex-1 flex flex-col bg-white/80 backdrop-blur-sm rounded-xl shadow border border-white/20 overflow-hidden">
          <!-- Table Wrapper with horizontal scroll -->
          <div class="flex-1 overflow-x-auto">
            <table class="w-full table-auto">
              <thead class="bg-gradient-to-r from-[#005188]/5 to-[#007C52]/5 sticky top-0">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input
                      type="checkbox"
                      @change="toggleSelectAll"
                      :checked="selectedServers.length === filteredServers.length && filteredServers.length > 0"
                      class="w-4 h-4 text-[#005188] bg-white border-gray-300 rounded focus:ring-[#005188] focus:ring-2"
                    />
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Port</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SSL</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locations</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="server in filteredServers"
                  :key="server.server_id"
                  class="hover:bg-gradient-to-r hover:from-blue-50/30 hover:to-emerald-50/30 transition-colors"
                  :class="{ 'bg-blue-50/20': selectedServers.includes(server.server_id) }"
                >
                  <td class="px-4 py-3">
                    <input
                      type="checkbox"
                      :value="server.server_id"
                      v-model="selectedServers"
                      class="w-4 h-4 text-[#005188] bg-white border-gray-300 rounded focus:ring-[#005188] focus:ring-2"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-8 w-8 bg-gradient-to-r from-[#005188]/10 to-[#007C52]/10 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-4 w-4 text-[#005188]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900" :title="server.server_title">
                          {{ server.server_title }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-sm text-gray-700 font-mono" :title="server.server_name">
                      {{ server.server_name }}
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#005188]/10 text-[#005188]">
                      {{ server.port }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span v-if="server.cert_name" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#007C52]/10 text-[#007C52]">
                      <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                      </svg>
                      <span :title="server.cert_name">{{ server.cert_name }}</span>
                    </span>
                    <span v-else class="text-gray-400 text-sm">No SSL</span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-sm text-gray-700">
                      {{ server.location_count }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex justify-end space-x-1">

                      <button
                        @click="editServer(server.server_id)"
                        class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-[#007C52] hover:bg-[#006641] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#007C52]/50 transition-all"
                      >
                        Edit
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import AddProxyBlockModal from '@/components/services/nginx/AddProxyBlockModal.vue';
import UpdateProxyBlock from '@/components/services/nginx/UpdateProxyBlock.vue';
import { useNginxStore } from '@/stores/services/nginx/nginx'

// Initialize the store
const nginxStore = useNginxStore()
const selectedServerId = ref(1)
const showProxyModal = ref(false)
const   showUpdateModal = ref(false)

// Reactive data
const searchQuery = ref('')
const selectedServers = ref<number[]>([])

// Computed properties
const filteredServers = computed(() => {
  if (!searchQuery.value) {
    return nginxStore.servers
  }
  const query = searchQuery.value.toLowerCase()
  return nginxStore.servers.filter(server =>
    server.server_title?.toLowerCase().includes(query) ||
    server.server_name?.toLowerCase().includes(query) ||
    server.port?.toString().includes(query) ||
    server.cert_name?.toLowerCase().includes(query)
  )
})

// Function to load servers
const loadServers = async () => {
  try {
    await nginxStore.fetchServers()
  } catch (error) {
    console.error('Failed to load servers:', error)
  }
}

// Load servers when component mounts
onMounted(() => {
  loadServers()
})

// Selection handlers
const toggleSelectAll = () => {
  if (selectedServers.value.length === filteredServers.value.length) {
    selectedServers.value = []
  } else {
    selectedServers.value = filteredServers.value.map(server => server.server_id)
  }
}

// Action handlers
const addServer = () => {
  showProxyModal.value = true;
}

const deleteSelected = async () => {
  if (selectedServers.value.length === 0) return

  const confirmed = confirm(`Are you sure you want to delete ${selectedServers.value.length} server(s)?`)
  if (confirmed) {
    try {
      for (const serverId of selectedServers.value) {
        await nginxStore.deleteServer(serverId)
      }
      selectedServers.value = []
    } catch (error) {
      console.error('Failed to delete servers:', error)
    }
  }
}

const openGeneralParameters = () => {
  console.log('Open general parameters')
}

// Server action handlers


const editServer = (serverId: number) => {
  showUpdateModal.value = true
  selectedServerId.value = serverId

}

const deleteServerHandler = async (serverId: number) => {
  if (confirm('Are you sure you want to delete this server?')) {
    try {
      await nginxStore.deleteServer(serverId)
      selectedServers.value = selectedServers.value.filter(id => id !== serverId)
    } catch (error) {
      console.error('Failed to delete server:', error)
    }
  }
}
</script>

<style>
/* Custom scrollbar for better appearance */
::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
