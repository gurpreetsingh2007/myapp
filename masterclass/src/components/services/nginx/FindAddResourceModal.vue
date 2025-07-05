<template>
  <div class="fixed inset-0 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden min-h-0">
      <!-- Header con gradient -->
      <div class="bg-gradient-to-r from-[#005188] to-[#007C52] p-6 flex justify-between items-center">
        <h3 class="text-2xl font-bold text-white">Server Management</h3>
        <button @click="handleClose" class="text-white hover:text-gray-200">
          <XMarkIcon class="h-8 w-8" />
        </button>
      </div>

      <div class="p-6 space-y-4">
        <!-- Status indicators -->
        <div v-if="loadingChanges" class="p-3 bg-blue-50 text-blue-700 rounded-lg flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Applying changes to {{ selectedServers.length }} server(s)...</span>
        </div>

        <div v-if="showSuccess" class="p-3 bg-green-50 text-green-700 rounded-lg flex items-center">
          <CheckCircleIcon class="h-5 w-5 mr-3" />
          <span>{{ successMessage }}</span>
          <button @click="showSuccess = false" class="ml-auto text-green-700 hover:text-green-900">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div v-if="loadingFailed" class="p-3 bg-red-50 text-red-700 rounded-lg flex items-center">
          <ExclamationTriangleIcon class="h-5 w-5 mr-3" />
          <span>{{ errorMessage || 'Failed to apply changes. Please try again.' }}</span>
          <button @click="loadingFailed = false" class="ml-auto text-red-700 hover:text-red-900">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <!-- Search Bar and Add Name Field -->
        <div class="flex items-center gap-4">
          <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005188] focus:border-transparent shadow-sm"
              placeholder="Search by server names only..."
              :disabled="loadingChanges"
            />
          </div>

          <ArrowRightIcon class="h-5 w-5 text-gray-400 flex-shrink-0" />

          <div class="relative flex-1">
            <input
              v-model="newServerName"
              type="text"
              class="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005188] focus:border-transparent shadow-sm"
              :class="{ 'border-red-300': newServerName.trim() && !isValidDomain(newServerName.trim()) }"
              placeholder="Name to add (e.g., example.com)"
              @keyup.enter="confirmExecute"
              :disabled="loadingChanges"
            />
            <p v-if="newServerName.trim() && !isValidDomain(newServerName.trim())" class="text-xs text-red-500 mt-1">
              Please enter a valid domain name
            </p>
          </div>
        </div>

        <!-- Server List Container -->
        <div class="border border-gray-300 rounded-lg overflow-hidden">
          <div class="overflow-y-auto max-h-[308px]">
            <!-- Server Items -->
            <div
              v-for="server in filteredServers"
              :key="server.server_id"
              class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 group transition-colors duration-150"
              :class="{
                'bg-blue-50': selectedServers.includes(server.server_id),
                'opacity-50 cursor-not-allowed': loadingChanges
              }"
              @click="!loadingChanges && toggleServerSelection(server.server_id)"
            >
              <div class="flex items-center flex-1 min-w-0">
                <ServerIcon class="h-5 w-5 text-gray-500 mr-3 flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-700 truncate">{{ server.server_title }}</p>
                  <div class="mt-1 flex flex-wrap gap-1">
                    <span
                      v-for="(name, index) in getHighlightedNames(server)"
                      :key="index"
                      class="text-xs px-1 py-0.5 rounded"
                      :class="{
                        'bg-yellow-100': isMatchingName(name.original),
                        'bg-gray-100': !isMatchingName(name.original)
                      }"
                      v-html="name.highlighted"
                    />
                  </div>
                </div>
              </div>
              <CheckIcon
                v-if="selectedServers.includes(server.server_id)"
                class="h-5 w-5 text-[#007C52] ml-2 flex-shrink-0"
              />
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
            class="text-xs text-[#005188] hover:text-[#003d6a] font-medium disabled:opacity-50"
            :disabled="loadingChanges || filteredServers.length === 0"
          >
            {{ selectedServers.length === filteredServers.length ? 'Deselect all' : 'Select all' }}
          </button>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
          <button
            @click="handleClose"
            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 shadow-sm disabled:opacity-50"
            :disabled="loadingChanges"
          >
            Cancel
          </button>
          <button
            @click="confirmExecute"
            :disabled="!canExecute"
            :class="[
              'px-6 py-2.5 bg-gradient-to-r from-[#005188] to-[#007C52] text-white rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center min-w-[120px]',
              { 'opacity-70 cursor-not-allowed': !canExecute },
              { 'hover:from-[#003d6a] hover:to-[#006044] hover:shadow-md': canExecute && !loadingChanges },
              { 'from-[#003d6a] to-[#006044]': loadingChanges }
            ]"
          >
            <template v-if="!loadingChanges">Execute</template>
            <template v-else>
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </template>
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
  CheckIcon,
  ArrowRightIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

interface Server {
  server_id: number
  server_title: string
  server_name: string | string[]
  server_names?: string[]
  port: number
  ssl_enabled: boolean
  is_mtls: boolean
  is_http2: boolean
  ssl_certificate?: string
  ssl_certificate_key?: string
  ssl_client_certificate?: string
  ssl_verify_client?: string
  locations: Array<{
    path: string
    proxy_pass: string
    directives: Array<{
      param_name: string
      param_value: string
      is_common: boolean
    }>
  }>
  directives: Array<{
    param_name: string
    param_value: string
    is_common: boolean
  }>
}

const emit = defineEmits(['close', 'execute'])
const nginxStore = useNginxStore()

const selectedServers = ref<number[]>([])
const searchQuery = ref('')
const newServerName = ref('')
const loadingChanges = ref(false)
const loadingFailed = ref(false)
const showSuccess = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

onMounted(async () => {
  if (nginxStore.servers.length === 0) {
    try {
      await nginxStore.fetchServers()
      await nginxStore.fetchCertificates()
    } catch (error) {
      loadingFailed.value = true
      errorMessage.value = 'Failed to load servers'
      console.error('Error loading servers:', error)
    }
  }
})

const handleClose = () => {
  if (!loadingChanges.value) {
    emit('close')
  }
}

const canExecute = computed(() => {
  return selectedServers.value.length > 0 &&
         newServerName.value.trim() !== '' &&
         isValidDomain(newServerName.value.trim()) &&
         !loadingChanges.value
})

const getServerNames = (server: Server): string[] => {
  if (server.server_names) {
    return Array.isArray(server.server_names)
      ? server.server_names
      : [server.server_names]
  }
  if (server.server_name) {
    return Array.isArray(server.server_name)
      ? server.server_name
      : server.server_name.split(' ').filter(n => n)
  }
  return []
}

const getHighlightedNames = (server: Server): { original: string, highlighted: string }[] => {
  const names = getServerNames(server)
  return names.map(name => ({
    original: name,
    highlighted: highlightMatch(name)
  }))
}

const isMatchingName = (name: string): boolean => {
  const query = searchQuery.value.trim().toLowerCase()
  return query && name.toLowerCase().includes(query)
}

const highlightMatch = (name: string): string => {
  const query = searchQuery.value.trim()
  if (!query) return name

  const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi')
  return name.replace(regex, '<span class="underline decoration-yellow-500 font-bold">$1</span>')
}

const escapeRegExp = (string: string) => {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

const filteredServers = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return nginxStore.servers

  return nginxStore.servers.filter(server =>
    getServerNames(server).some(name =>
      name.toLowerCase().includes(query)
    ))
})

const toggleServerSelection = (serverId: number) => {
  if (loadingChanges.value) return

  const index = selectedServers.value.indexOf(serverId)
  if (index === -1) {
    selectedServers.value.push(serverId)
  } else {
    selectedServers.value.splice(index, 1)
  }
}

const toggleSelectAll = () => {
  if (loadingChanges.value) return

  if (selectedServers.value.length === filteredServers.value.length) {
    selectedServers.value = []
  } else {
    selectedServers.value = filteredServers.value.map(server => server.server_id)
  }
}

const isValidDomain = (name: string): boolean => {
  return /^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i.test(name)
}

const resetForm = () => {
  selectedServers.value = []
  newServerName.value = ''
  searchQuery.value = ''
}

const confirmExecute = () => {
  if (!canExecute.value) return

  if (confirm(`Are you sure you want to add "${newServerName.value.trim()}" to ${selectedServers.value.length} server(s)?`)) {
    executeAction()
  }
}

const executeAction = async () => {
  if (!canExecute.value) return

  loadingChanges.value = true
  loadingFailed.value = false
  showSuccess.value = false
  errorMessage.value = null
  successMessage.value = null

  try {
    const nameToAdd = newServerName.value.trim()

    if (!nameToAdd) throw new Error('Server name is required')
    if (!isValidDomain(nameToAdd)) throw new Error('Please enter a valid domain name')

    // Per ogni server selezionato, prepara l'update completo
    const updatePromises = selectedServers.value.map(serverId => {
      const server = nginxStore.servers.find(s => s.server_id === serverId)
      if (!server) throw new Error(`Server ${serverId} not found`)

      // Prepara l'oggetto di update con TUTTI i campi necessari
      const updateData = {
        server_id: serverId,
        // Modifica solo il server_name
        server_name: [...getServerNames(server), nameToAdd].join(' ').trim(),
        // Mantieni tutti gli altri campi invariati
        server_title: server.server_title,
        port: server.port,
        ssl_enabled: server.ssl_enabled,
        is_mtls: server.is_mtls,
        is_http2: server.is_http2,
        locations: server.locations.map(loc => ({
          path: loc.path,
          proxy_pass: loc.proxy_pass,
          directives: loc.directives.map(d => ({
            param_name: d.param_name,
            param_value: d.param_value,
            is_common: d.is_common
          }))
        })),
        directives: server.directives.map(d => ({
          param_name: d.param_name,
          param_value: d.param_value,
          is_common: d.is_common
        })),
        ssl_certificate: server.ssl_certificate || null,
        ssl_certificate_key: server.ssl_certificate_key || null,
        ssl_client_certificate: server.ssl_client_certificate || null,
        ssl_verify_client: server.ssl_verify_client || 'off'
      }

      console.log('Updating server:', serverId, 'with data:', updateData)
      return nginxStore.updateServer(serverId, updateData)
    })

    const results = await Promise.all(updatePromises)

    // Aggiorna lo stato locale
    results.forEach((result, index) => {
      const serverId = selectedServers.value[index]
      const server = nginxStore.servers.find(s => s.server_id === serverId)
      if (server) {
        server.server_name = [...getServerNames(server), nameToAdd].join(' ').trim()
      }
    })

    successMessage.value = `Added "${nameToAdd}" to ${selectedServers.value.length} server(s) successfully`
    showSuccess.value = true
    resetForm()

  } catch (err: any) {
    loadingFailed.value = true
    errorMessage.value = err.response?.data?.message ||
                       err.message ||
                       'Failed to update servers'
    console.error('Update error:', err)
  } finally {
    loadingChanges.value = false
  }
}
</script>

<style scoped>
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

.border-b {
  transition: border-color 0.2s ease;
}

.bg-blue-50 {
  transition: background-color 0.2s ease;
}

.bg-yellow-100 {
  transition: background-color 0.1s ease;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
