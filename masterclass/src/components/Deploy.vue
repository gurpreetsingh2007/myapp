<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useModifiedFilesStore } from '@/stores/modified'
import { storeToRefs } from 'pinia'
import { API } from '@/config/index'

// Props
const props = defineProps<{
  message?: string
}>()

// Types
interface Client {
  clientId: string
  remoteAddress: string
}

interface ServerListResponse {
  clients: Client[]
  total: number
  timestamp: string
}

// Store
const modifiedFilesStore = useModifiedFilesStore()
const { files } = storeToRefs(modifiedFilesStore)

// Extract service from message prop
const currentService = computed(() => {
  return props.message?.trim() || null
})

// Filter files by service
const filteredModifiedFiles = computed(() => {
  if (!currentService.value) {
    return files.value
  }
  return files.value.filter(file => file.service === currentService.value)
})

// Reactive state
const loading = ref(false)
const sending = ref(false)
const error = ref('')
const successMessage = ref('')
const serverList = ref<ServerListResponse>({ clients: [], total: 0, timestamp: '' })
const selectedServers = ref<string[]>([])
const selectedFiles = ref<string[]>([])
const resultData = ref<any>(null)

const formattedResponse = computed(() => {
  if (!resultData.value?.response) return ''
  try {
    const parsed = JSON.parse(resultData.value.response)
    if (!parsed.success || !parsed.responses) return 'No valid response.'
    return Object.entries(parsed.responses)
      .map(([clientId, data]: any) => {
        return `> ${clientId}\nCMD: ${data.command}\nSTATUS: ${data.status}\nOUTPUT:\n${data.output.trim()}\n`
      })
      .join('\n')
  } catch (e) {
    return '[ERROR] Malformed response data.'
  }
})

// Computed properties
const allServersSelected = computed(
  () => selectedServers.value.length === serverList.value.total && serverList.value.total > 0,
)

const canSendFiles = computed(
  () => selectedServers.value.length > 0 && selectedFiles.value.length > 0,
)

// Methods
const fetchServerList = async () => {
  loading.value = true
  error.value = ''

  try {

    const response = await fetch(`${API}/credentials/get/server_list`)

    if (!response.ok) {
      throw new Error(`NETWORK ERROR: STATUS ${response.status}`)
    }

    const data: ServerListResponse = await response.json()
    serverList.value = data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'NEURAL NETWORK CONNECTION FAILED'
    console.error('Error fetching server list:', err)
  } finally {
    loading.value = false
  }
}

const selectAllServers = () => {
  selectedServers.value = serverList.value.clients.map((client) => client.clientId)
}

const deselectAllServers = () => {
  selectedServers.value = []
}

const selectAllFiles = () => {
  selectedFiles.value = filteredModifiedFiles.value.map(file => file.path)
}

const deselectAllFiles = () => {
  selectedFiles.value = []
}

const toggleServer = (clientId: string) => {
  const index = selectedServers.value.indexOf(clientId)
  if (index > -1) {
    selectedServers.value.splice(index, 1)
  } else {
    selectedServers.value.push(clientId)
  }
}

const toggleFile = (filePath: string) => {
  const index = selectedFiles.value.indexOf(filePath)
  if (index > -1) {
    selectedFiles.value.splice(index, 1)
  } else {
    selectedFiles.value.push(filePath)
  }
}

const sendFiles = async () => {
  if (!canSendFiles.value) return
  sending.value = true
  error.value = ''
  successMessage.value = ''
  resultData.value = null

  try {
    // Replace with your actual API endpoint

    let endpoint: string
    let payload: any

    if (allServersSelected.value) {
      endpoint = `${API}/credentials/send/files`
      payload = {
        files: selectedFiles.value,
        command: '1',
      }
    } else {
      endpoint = `${API}/credentials/send/partialFilesServer`
      payload = {
        data: selectedFiles.value,
        command: '1',
        targets: selectedServers.value,
      }
    }

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (!response.ok) throw new Error(`TRANSMISSION FAILED: STATUS ${response.status}`)

    const result = await response.json()
    resultData.value = result
    successMessage.value = `Data successfully transmitted to ${selectedServers.value.length} node(s)`
    selectedServers.value = []
    selectedFiles.value = []
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'PROTOCOL FAILED'
    console.error('Error sending files:', err)
  } finally {
    sending.value = false
  }
}

const refreshServerList = () => {
  fetchServerList()
}

// Lifecycle
onMounted(() => {
  fetchServerList()
})
</script>

<template>
  <div class="bg-gray-50 text-gray-800 font-sans relative flex flex-col">
    <!-- Subtle Grid Background -->
    <div class="absolute inset-0 opacity-20">
      <div
        class="absolute inset-0"
        style="
          background-image:
            linear-gradient(rgba(0, 81, 136, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 81, 136, 0.05) 1px, transparent 1px);
          background-size: 50px 50px;
        "
      ></div>
    </div>

    <!-- Animated Accent Lines -->
    <div
      class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#005188] to-transparent animate-pulse"
    ></div>
    <div
      class="absolute bottom-0 right-0 w-full h-1 bg-gradient-to-l from-transparent via-[#007C52] to-transparent animate-pulse"
    ></div>

    <div class="relative z-10 p-8 h-full flex flex-col overflow-hidden">
      <div class="max-w-7xl mx-auto h-full flex flex-col overflow-y-auto">
        <!-- Header and Main Content Container -->
        <div class="flex flex-col xl:flex-row gap-8">
          <!-- Left Column (Main Content) -->
          <div class="flex-1 flex flex-col">
            <!-- Header -->
            <div class="text-center mb-8 flex-shrink-0">
              <h1
                class="text-4xl md:text-6xl font-bold bg-gradient-to-r from-[#005188] to-[#007C52] bg-clip-text text-transparent mb-6 transition-all duration-500 hover:scale-105"
              >
                DEPLOY TO NETWORK
              </h1>
              <div class="text-lg md:text-xl text-gray-600 font-light tracking-wider px-4">
                [ SERVER & FILE SYNCHRONIZATION PROTOCOL ]
              </div>
              <div v-if="currentService" class="mt-4 text-[#005188] text-lg font-bold tracking-wider px-4">
                SERVICE: {{ currentService.toUpperCase() }}
              </div>
              <div class="mt-6 flex justify-center px-4">
                <div
                  class="w-64 h-1 bg-gradient-to-r from-[#005188] to-[#007C52] rounded-full animate-pulse"
                ></div>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex flex-col items-center justify-center py-16 flex-1 px-4">
              <div class="relative">
                <div
                  class="w-24 h-24 border-4 border-[#005188] rounded-full animate-spin border-t-transparent"
                ></div>
                <div
                  class="absolute inset-0 w-24 h-24 border-4 border-[#007C52] rounded-full animate-spin border-b-transparent"
                  style="animation-direction: reverse; animation-duration: 1.5s"
                ></div>
              </div>
              <div class="mt-8 text-[#005188] text-xl tracking-wider animate-pulse px-4">
                [ ACCESSING NETWORK NODES... ]
              </div>
              <div class="mt-3 text-gray-500 text-sm px-4">Establishing secure connection protocols</div>
            </div>

            <!-- Error State -->
            <div v-if="error" class="mb-8 flex-shrink-0 px-4">
              <div class="bg-red-100 border-2 border-red-400 rounded-lg p-6 backdrop-blur-sm">
                <div class="flex items-center">
                  <div class="text-red-500 animate-pulse">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-lg font-bold text-red-500 tracking-wider">SYSTEM ERROR</h3>
                    <p class="text-red-600 mt-2">{{ error }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Main Content Grid -->
            <div v-if="!loading && !error" class="grid grid-cols-1 gap-8 mb-8 flex-1 min-h-0 overflow-y-auto px-4">
              <!-- Server Matrix -->
              <div
                class="bg-white border-2 border-[#005188]/30 rounded-lg shadow-lg hover:border-[#005188] transition-all duration-300 flex flex-col min-h-0"
              >
                <div
                  class="px-6 py-5 border-b border-[#005188]/20 bg-gradient-to-r from-[#005188]/5 to-[#005188]/10 flex-shrink-0"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <div class="w-4 h-4 bg-[#005188] rounded-full animate-pulse"></div>
                      <h2 class="text-2xl font-bold text-[#005188] tracking-wider">
                        NETWORK NODES [{{ serverList.total }}]
                      </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                      <button
                        @click="selectAllServers"
                        class="px-4 py-2.5 bg-[#005188]/10 text-[#005188] border border-[#005188]/30 rounded hover:bg-[#005188]/20 hover:border-[#005188] transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        SELECT ALL
                      </button>
                      <button
                        @click="deselectAllServers"
                        class="px-4 py-2.5 bg-gray-200 text-gray-600 border border-gray-300 rounded hover:bg-gray-300 hover:border-gray-400 transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        CLEAR
                      </button>
                    </div>
                  </div>
                </div>
                <div class="p-5 flex-1 overflow-y-auto custom-scrollbar" style="max-height: 400px;">
                  <div class="space-y-4">
                    <div
                      v-for="client in serverList.clients"
                      :key="client.clientId"
                      :class="[
                        'flex items-center p-5 border-2 rounded-lg transition-all duration-300 cursor-pointer transform hover:scale-[1.01]',
                        selectedServers.includes(client.clientId)
                          ? 'border-[#007C52] bg-[#007C52]/10 shadow-md'
                          : 'border-gray-200 bg-white hover:border-gray-300',
                      ]"
                      @click="toggleServer(client.clientId)"
                    >
                      <input
                        :id="client.clientId"
                        v-model="selectedServers"
                        :value="client.clientId"
                        type="checkbox"
                        class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] border-gray-300 rounded"
                      />
                      <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                          <div>
                            <p class="text-lg font-bold text-gray-800 tracking-wider">
                              {{ client.clientId }}
                            </p>
                            <p class="text-[#005188] text-sm mt-1">{{ client.remoteAddress }}</p>
                          </div>
                          <div class="flex items-center space-x-2">
                            <div
                              class="w-3 h-3 bg-[#007C52] rounded-full animate-pulse shadow-md"
                            ></div>
                            <span class="text-xs text-[#007C52] font-bold tracking-wider">ONLINE</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- File Data Stream -->
              <div
                class="bg-white border-2 border-[#007C52]/30 rounded-lg shadow-lg hover:border-[#007C52] transition-all duration-300 flex flex-col min-h-0"
              >
                <div
                  class="px-6 py-5 border-b border-[#007C52]/20 bg-gradient-to-r from-[#007C52]/5 to-[#007C52]/10 flex-shrink-0"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <div class="w-4 h-4 bg-[#007C52] rounded-full animate-pulse"></div>
                      <h2 class="text-2xl font-bold text-[#007C52] tracking-wider">
                        DATA STREAMS [{{ filteredModifiedFiles.length }}]
                      </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                      <button
                        @click="selectAllFiles"
                        class="px-4 py-2.5 bg-[#007C52]/10 text-[#007C52] border border-[#007C52]/30 rounded hover:bg-[#007C52]/20 hover:border-[#007C52] transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        SELECT ALL
                      </button>
                      <button
                        @click="deselectAllFiles"
                        class="px-4 py-2.5 bg-gray-200 text-gray-600 border border-gray-300 rounded hover:bg-gray-300 hover:border-gray-400 transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        CLEAR
                      </button>
                    </div>
                  </div>
                </div>
                <div class="p-5 flex-1 overflow-y-auto custom-scrollbar" style="max-height: 400px;">
                  <div v-if="filteredModifiedFiles.length === 0" class="text-center py-10">
                    <div class="text-gray-500 text-lg px-4">
                      {{ currentService ? `No modified files for service: ${currentService}` : 'No modified files found' }}
                    </div>
                  </div>
                  <div v-else class="space-y-4">
                    <div
                      v-for="file in filteredModifiedFiles"
                      :key="file.path"
                      :class="[
                        'flex items-center p-5 border-2 rounded-lg transition-all duration-300 cursor-pointer transform hover:scale-[1.01]',
                        selectedFiles.includes(file.path)
                          ? 'border-[#005188] bg-[#005188]/10 shadow-md'
                          : 'border-gray-200 bg-white hover:border-gray-300',
                      ]"
                      @click="toggleFile(file.path)"
                    >
                      <input
                        :id="file.path"
                        v-model="selectedFiles"
                        :value="file.path"
                        type="checkbox"
                        class="h-5 w-5 text-[#005188] focus:ring-[#005188] border-gray-300 rounded"
                      />
                      <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center">
                            <svg
                              class="w-6 h-6 text-gray-500 mr-4"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"
                              ></path>
                            </svg>
                            <div>
                              <span class="text-lg font-bold text-gray-800 tracking-wider">{{ file.path }}</span>
                              <div class="text-xs text-gray-500 mt-2">Service: {{ file.service }}</div>
                            </div>
                          </div>
                          <div class="flex items-center space-x-2">
                            <div
                              class="px-3 py-1.5 bg-amber-100 text-amber-800 border border-amber-200 rounded-full text-xs font-bold tracking-wider"
                            >
                              MODIFIED
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Control Panel -->
            <div v-if="!loading && !error" class="flex flex-col items-center space-y-8 pb-8 flex-shrink-0 px-4">
              <!-- Action Buttons -->
              <div class="flex flex-wrap justify-center gap-6">
                <button
                  @click="sendFiles"
                  :disabled="!canSendFiles || sending"
                  :class="[
                    'px-8 py-4 rounded-lg font-bold text-lg tracking-wider transition-all duration-300 transform hover:scale-105 relative overflow-hidden',
                    canSendFiles && !sending
                      ? 'bg-gradient-to-r from-[#005188] to-[#007C52] text-white shadow-xl hover:shadow-2xl border-2 border-transparent hover:border-white/20'
                      : 'bg-gray-200 text-gray-500 cursor-not-allowed border-2 border-gray-300',
                  ]"
                >
                  <div v-if="sending" class="flex items-center">
                    <div
                      class="w-6 h-6 border-2 border-white rounded-full animate-spin border-t-transparent mr-3"
                    ></div>
                    TRANSMITTING DATA...
                  </div>
                  <div v-else class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"
                      ></path>
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    INITIALIZE TRANSFER
                  </div>
                  <div
                    v-if="canSendFiles && !sending"
                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent transform skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"
                  ></div>
                </button>

                <button
                  @click="refreshServerList"
                  :disabled="loading"
                  class="px-8 py-4 bg-white text-[#005188] border-2 border-[#005188]/30 rounded-lg font-bold text-lg tracking-wider hover:bg-[#005188]/10 hover:border-[#005188] transition-all duration-300 transform hover:scale-105 disabled:opacity-50"
                >
                  <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                    REFRESH NODES
                  </div>
                </button>
              </div>

              <!-- Status Panel -->
              <div
                class="bg-white border-2 border-[#007C52]/30 rounded-lg p-6 shadow-lg max-w-4xl w-full"
              >
                <div class="flex items-center mb-5">
                  <svg
                    class="w-6 h-6 text-[#007C52] mr-3"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                      clip-rule="evenodd"
                    ></path>
                  </svg>
                  <div class="text-lg font-bold text-[#007C52] tracking-wider">SYSTEM STATUS</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                  <div class="bg-[#005188]/10 border border-[#005188]/30 rounded-lg p-5">
                    <div class="text-3xl font-bold text-[#005188]">{{ selectedServers.length }}</div>
                    <div class="text-[#005188]/80 text-sm tracking-wider mt-2">NODES SELECTED</div>
                    <div class="text-xs text-gray-500 mt-2">of {{ serverList.total }} available</div>
                  </div>

                  <div class="bg-[#007C52]/10 border border-[#007C52]/30 rounded-lg p-5">
                    <div class="text-3xl font-bold text-[#007C52]">{{ selectedFiles.length }}</div>
                    <div class="text-[#007C52]/80 text-sm tracking-wider mt-2">FILES QUEUED</div>
                    <div class="text-xs text-gray-500 mt-2">
                      of {{ filteredModifiedFiles.length }} modified
                    </div>
                  </div>

                  <div class="bg-green-100 border border-green-200 rounded-lg p-5">
                    <div class="text-3xl font-bold text-green-700">
                      {{ allServersSelected ? 'FULL' : 'PARTIAL' }}
                    </div>
                    <div class="text-green-700/80 text-sm tracking-wider mt-2">SYNC MODE</div>
                    <div class="text-xs text-gray-500 mt-2">
                      {{ allServersSelected ? 'Global deployment' : 'Targeted deployment' }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Success/Error Messages -->
              <div
                v-if="successMessage"
                class="bg-green-100 border-2 border-green-400 rounded-lg p-6 max-w-4xl w-full"
              >
                <div class="flex items-center">
                  <svg
                    class="w-8 h-8 text-green-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    ></path>
                  </svg>
                  <div class="ml-4">
                    <h3 class="text-xl font-bold text-green-700 tracking-wider">
                      TRANSMISSION SUCCESSFUL
                    </h3>
                    <p class="text-green-600 text-lg mt-2">{{ successMessage }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="mt-auto pt-8 text-center flex-shrink-0 px-4">
              <div class="text-gray-500 text-sm tracking-wider">
                [ NETWORK SYNCHRONIZATION PROTOCOL v2.077 ]
              </div>
              <div class="mt-3 text-gray-400 text-xs">
                Last server scan: {{ serverList.timestamp || 'Never' }}
              </div>
            </div>
          </div>

          <!-- Right Column (Server Response) -->
          <div v-if="resultData" class="xl:w-1/3 flex-shrink-0 px-4">
            <div class="bg-white border-2 border-[#007C52]/30 rounded-lg p-6 shadow-lg h-full">
              <div class="text-lg font-bold text-[#007C52] mb-5">SERVER RESPONSE</div>
              <pre class="text-[#005188] font-mono whitespace-pre-wrap text-sm p-4 bg-gray-50 rounded">{{ formattedResponse }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(200, 200, 200, 0.3);
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 81, 136, 0.3);
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 81, 136, 0.5);
}

/* For Firefox */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 81, 136, 0.3) rgba(200, 200, 200, 0.3);
}

.overflow-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.scrollable-content {
  flex: 1;
  overflow-y: auto;
}
</style>
