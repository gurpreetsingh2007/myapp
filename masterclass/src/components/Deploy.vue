<template>
  <div class="bg-black text-green-400 font-mono relative flex flex-col">
    <!-- Cyberpunk Grid Background -->
    <div class="absolute inset-0 opacity-10">
      <div
        class="absolute inset-0"
        style="
          background-image:
            linear-gradient(rgba(0, 255, 0, 0.1) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 255, 0, 0.1) 1px, transparent 1px);
          background-size: 50px 50px;
        "
      ></div>
    </div>

    <!-- Animated Circuit Lines -->
    <div
      class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-transparent via-cyan-400 to-transparent animate-pulse"
    ></div>
    <div
      class="absolute bottom-0 right-0 w-full h-2 bg-gradient-to-l from-transparent via-purple-400 to-transparent animate-pulse"
    ></div>

    <div class="relative z-10 p-6 h-full flex flex-col overflow-hidden">
      <div class="max-w-7xl mx-auto h-full flex flex-col overflow-y-auto">
        <!-- Header and Main Content Container -->
        <div class="flex flex-col xl:flex-row gap-6">
          <!-- Left Column (Main Content) -->
          <div class="flex-1 flex flex-col">
            <!-- Header -->
            <div class="text-center mb-6 flex-shrink-0">
              <h1
                class="text-4xl md:text-6xl font-bold bg-gradient-to-r from-cyan-400 via-green-400 to-purple-400 bg-clip-text text-transparent mb-4 animate-pulse"
              >
                DEPLOY TO NETWORK
              </h1>
              <div class="text-lg md:text-xl text-gray-400 font-light tracking-wider">
                [ SERVER & FILE SYNCHRONIZATION PROTOCOL ]
              </div>
              <div class="mt-4 flex justify-center">
                <div
                  class="w-64 h-1 bg-gradient-to-r from-cyan-400 to-purple-400 rounded-full animate-pulse"
                ></div>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex flex-col items-center justify-center py-12 flex-1">
              <div class="relative">
                <div
                  class="w-24 h-24 border-4 border-cyan-400 rounded-full animate-spin border-t-transparent"
                ></div>
                <div
                  class="absolute inset-0 w-24 h-24 border-4 border-purple-400 rounded-full animate-spin border-b-transparent"
                  style="animation-direction: reverse; animation-duration: 1.5s"
                ></div>
              </div>
              <div class="mt-8 text-cyan-400 text-xl tracking-wider animate-pulse">
                [ ACCESSING NEURAL NODES... ]
              </div>
              <div class="mt-2 text-gray-500 text-sm">Establishing quantum entanglement protocols</div>
            </div>

            <!-- Error State -->
            <div v-if="error" class="mb-6 flex-shrink-0">
              <div class="bg-red-900/30 border-2 border-red-500 rounded-lg p-6 backdrop-blur-sm">
                <div class="flex items-center">
                  <div class="text-red-400 animate-pulse">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-lg font-bold text-red-400 tracking-wider">SYSTEM ERROR</h3>
                    <p class="text-red-300 mt-1">{{ error }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Main Content Grid -->
            <div v-if="!loading && !error" class="grid grid-cols-1 gap-6 mb-6 flex-1 min-h-0 overflow-y-auto">
              <!-- Server Matrix -->
              <div
                class="bg-gray-900/50 border-2 border-cyan-400/50 rounded-lg backdrop-blur-sm shadow-2xl shadow-cyan-400/20 hover:border-cyan-400 transition-all duration-300 flex flex-col min-h-0"
              >
                <div
                  class="px-6 py-4 border-b border-cyan-400/30 bg-gradient-to-r from-gray-900/80 to-gray-800/80 flex-shrink-0"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="w-4 h-4 bg-cyan-400 rounded-full animate-pulse"></div>
                      <h2 class="text-2xl font-bold text-cyan-400 tracking-wider">
                        NEURAL NODES [{{ serverList.total }}]
                      </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                      <button
                        @click="selectAllServers"
                        class="px-4 py-2 bg-cyan-500/20 text-cyan-400 border border-cyan-400/50 rounded hover:bg-cyan-400/30 hover:border-cyan-400 transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        SELECT ALL
                      </button>
                      <button
                        @click="deselectAllServers"
                        class="px-4 py-2 bg-gray-500/20 text-gray-400 border border-gray-400/50 rounded hover:bg-gray-400/30 hover:border-gray-400 transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        CLEAR
                      </button>
                    </div>
                  </div>
                </div>
                <div class="p-4 flex-1 overflow-y-auto custom-scrollbar" style="max-height: 400px;">
                  <div class="space-y-4">
                    <div
                      v-for="client in serverList.clients"
                      :key="client.clientId"
                      :class="[
                        'flex items-center p-4 border-2 rounded-lg transition-all duration-300 cursor-pointer transform',
                        selectedServers.includes(client.clientId)
                          ? 'border-purple-400 bg-purple-900/30 shadow-lg shadow-purple-400/20'
                          : 'border-gray-600/50 bg-gray-800/30 hover:border-gray-500 hover:bg-gray-700/30',
                      ]"
                      @click="toggleServer(client.clientId)"
                    >
                      <input
                        :id="client.clientId"
                        v-model="selectedServers"
                        :value="client.clientId"
                        type="checkbox"
                        class="h-5 w-5 text-purple-500 focus:ring-purple-500 border-gray-600 rounded bg-gray-800"
                      />
                      <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                          <div>
                            <p class="text-lg font-bold text-white tracking-wider">
                              {{ client.clientId }}
                            </p>
                            <p class="text-cyan-400 text-sm">{{ client.remoteAddress }}</p>
                          </div>
                          <div class="flex items-center space-x-2">
                            <div
                              class="w-3 h-3 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"
                            ></div>
                            <span class="text-xs text-green-400 font-bold tracking-wider">ONLINE</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- File Data Stream -->
              <div
                class="bg-gray-900/50 border-2 border-purple-400/50 rounded-lg backdrop-blur-sm shadow-2xl shadow-purple-400/20 hover:border-purple-400 transition-all duration-300 flex flex-col min-h-0"
              >
                <div
                  class="px-6 py-4 border-b border-purple-400/30 bg-gradient-to-r from-gray-900/80 to-gray-800/80 flex-shrink-0"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="w-4 h-4 bg-purple-400 rounded-full animate-pulse"></div>
                      <h2 class="text-2xl font-bold text-purple-400 tracking-wider">
                        DATA STREAMS [{{ modifiedFilesArray.length }}]
                      </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                      <button
                        @click="selectAllFiles"
                        class="px-4 py-2 bg-purple-500/20 text-purple-400 border border-purple-400/50 rounded hover:bg-purple-400/30 hover:border-purple-400 transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        SELECT ALL
                      </button>
                      <button
                        @click="deselectAllFiles"
                        class="px-4 py-2 bg-gray-500/20 text-gray-400 border border-gray-400/50 rounded hover:bg-gray-400/30 hover:border-gray-400 transition-all duration-300 text-sm font-bold tracking-wider"
                      >
                        CLEAR
                      </button>
                    </div>
                  </div>
                </div>
                <div class="p-4 flex-1 overflow-y-auto custom-scrollbar" style="max-height: 400px;">
                  <div class="space-y-4">
                    <div
                      v-for="file in modifiedFilesArray"
                      :key="file"
                      :class="[
                        'flex items-center p-4 border-2 rounded-lg transition-all duration-300 cursor-pointer transform',
                        selectedFiles.includes(file)
                          ? 'border-cyan-400 bg-cyan-900/30 shadow-lg shadow-cyan-400/20'
                          : 'border-gray-600/50 bg-gray-800/30 hover:border-gray-500 hover:bg-gray-700/30',
                      ]"
                      @click="toggleFile(file)"
                    >
                      <input
                        :id="file"
                        v-model="selectedFiles"
                        :value="file"
                        type="checkbox"
                        class="h-5 w-5 text-cyan-500 focus:ring-cyan-500 border-gray-600 rounded bg-gray-800"
                      />
                      <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center">
                            <svg
                              class="w-6 h-6 text-gray-400 mr-3"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"
                              ></path>
                            </svg>
                            <span class="text-lg font-bold text-white tracking-wider">{{ file }}</span>
                          </div>
                          <div class="flex items-center space-x-2">
                            <div
                              class="px-3 py-1 bg-orange-500/20 text-orange-400 border border-orange-400/50 rounded-full text-xs font-bold tracking-wider animate-pulse"
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
            <div v-if="!loading && !error" class="flex flex-col items-center space-y-6 pb-6 flex-shrink-0">
              <!-- Action Buttons -->
              <div class="flex flex-wrap justify-center gap-6">
                <button
                  @click="sendFiles"
                  :disabled="!canSendFiles || sending"
                  :class="[
                    'px-8 py-4 rounded-lg font-bold text-lg tracking-wider transition-all duration-300 transform hover:scale-105 relative overflow-hidden',
                    canSendFiles && !sending
                      ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white shadow-2xl shadow-cyan-500/50 hover:shadow-purple-500/50 border-2 border-transparent hover:border-white/20'
                      : 'bg-gray-600/30 text-gray-500 cursor-not-allowed border-2 border-gray-600/50',
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
                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent transform skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"
                  ></div>
                </button>

                <button
                  @click="refreshServerList"
                  :disabled="loading"
                  class="px-8 py-4 bg-gray-700/50 text-cyan-400 border-2 border-cyan-400/50 rounded-lg font-bold text-lg tracking-wider hover:bg-cyan-400/20 hover:border-cyan-400 transition-all duration-300 transform hover:scale-105 disabled:opacity-50"
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
                class="bg-gray-900/70 border-2 border-green-400/50 rounded-lg p-6 backdrop-blur-sm shadow-2xl shadow-green-400/20 max-w-4xl w-full"
              >
                <div class="flex items-center mb-4">
                  <svg
                    class="w-6 h-6 text-green-400 mr-3 animate-pulse"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                      clip-rule="evenodd"
                    ></path>
                  </svg>
                  <div class="text-lg font-bold text-green-400 tracking-wider">SYSTEM STATUS</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                  <div class="bg-cyan-900/30 border border-cyan-400/50 rounded-lg p-4">
                    <div class="text-3xl font-bold text-cyan-400">{{ selectedServers.length }}</div>
                    <div class="text-cyan-400/70 text-sm tracking-wider">NODES SELECTED</div>
                    <div class="text-xs text-gray-400 mt-1">of {{ serverList.total }} available</div>
                  </div>

                  <div class="bg-purple-900/30 border border-purple-400/50 rounded-lg p-4">
                    <div class="text-3xl font-bold text-purple-400">{{ selectedFiles.length }}</div>
                    <div class="text-purple-400/70 text-sm tracking-wider">FILES QUEUED</div>
                    <div class="text-xs text-gray-400 mt-1">
                      of {{ modifiedFilesArray.length }} modified
                    </div>
                  </div>

                  <div class="bg-green-900/30 border border-green-400/50 rounded-lg p-4">
                    <div class="text-3xl font-bold text-green-400">
                      {{ allServersSelected ? 'FULL' : 'PARTIAL' }}
                    </div>
                    <div class="text-green-400/70 text-sm tracking-wider">SYNC MODE</div>
                    <div class="text-xs text-gray-400 mt-1">
                      {{ allServersSelected ? 'Global deployment' : 'Targeted deployment' }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Success/Error Messages -->
              <div
                v-if="successMessage"
                class="bg-green-900/30 border-2 border-green-400 rounded-lg p-6 max-w-4xl w-full"
              >
                <div class="flex items-center">
                  <svg
                    class="w-8 h-8 text-green-400 animate-pulse"
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
                    <h3 class="text-xl font-bold text-green-400 tracking-wider">
                      TRANSMISSION SUCCESSFUL
                    </h3>
                    <p class="text-green-300 text-lg">{{ successMessage }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="mt-auto pt-6 text-center flex-shrink-0">
              <div class="text-gray-500 text-sm tracking-wider">
                [ NEURAL NETWORK SYNCHRONIZATION PROTOCOL v2.077 ]
              </div>
              <div class="mt-2 text-gray-600 text-xs">
                Last server scan: {{ serverList.timestamp || 'Never' }}
              </div>
            </div>
          </div>

          <!-- Right Column (Server Response) -->
          <div v-if="resultData" class="xl:w-1/3 flex-shrink-0">
            <div class="bg-gray-900/50 border-2 border-purple-400/50 rounded-lg p-6 backdrop-blur-sm h-full">
              <div class="text-lg font-bold text-purple-400 mb-4">SERVER RESPONSE</div>
              <pre class="text-green-400 font-mono whitespace-pre-wrap">{{ formattedResponse }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { API } from '@/config/index'
import { useConfigStore } from '@/stores/config'
import { storeToRefs } from 'pinia'

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
const configStore = useConfigStore()
const { modifiedFiles } = storeToRefs(configStore)

// Convert Set to Array for template usage
const modifiedFilesArray = computed(() => Array.from(modifiedFiles.value))

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
  selectedFiles.value = [...modifiedFilesArray.value]
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

const toggleFile = (fileName: string) => {
  const index = selectedFiles.value.indexOf(fileName)
  if (index > -1) {
    selectedFiles.value.splice(index, 1)
  } else {
    selectedFiles.value.push(fileName)
  }
}

const sendFiles = async () => {
  if (!canSendFiles.value) return
  sending.value = true
  error.value = ''
  successMessage.value = ''
  resultData.value = null
  try {
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
  // Load configs to populate modifiedFiles if not already loaded
  if (modifiedFilesArray.value.length === 0) {
    configStore.loadConfigs()
  }
})
</script>

<style scoped>
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.5;
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

/* Matrix-style text effect */
@keyframes matrix-glow {
  0%,
  100% {
    text-shadow: 0 0 5px currentColor;
  }

  50% {
    text-shadow:
      0 0 20px currentColor,
      0 0 30px currentColor;
  }
}

.text-matrix {
  animation: matrix-glow 2s infinite;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 12px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(30, 30, 30, 0.5);
  border-radius: 4px;
  box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(45deg, #00c6ff, #0072ff, #00c6ff);
  background-size: 200% 200%;
  border-radius: 4px;
  border: 2px solid rgba(0, 0, 0, 0.3);
  animation: scrollbar-glow 1.5s ease infinite;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(45deg, #00f2fe, #4facfe, #00f2fe);
}

@keyframes scrollbar-glow {
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

/* For Firefox */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #00c6ff rgba(30, 30, 30, 0.5);
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
