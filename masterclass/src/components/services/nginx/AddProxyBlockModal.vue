<template>
  <div class="fixed inset-0 backdrop-blur-sm  bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="w-full max-w-5xl mx-auto">

      <!-- Header -->
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-[#005188]">Dashboard</h1>
          <p class="text-xs text-gray-500">Server Configuration Panel</p>
        </div>
        <button @click="$emit('close')"
          class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded-full transition-colors"
          title="Close Dashboard">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <!-- Container principale -->
      <div class="flex w-full rounded-lg overflow-hidden border border-gray-200 shadow-sm bg-white">

        <!-- SEZIONE SINISTRA (70%) -->
        <div class="w-7/12 p-4 border-r border-gray-200 space-y-4">

          <!-- Server Names -->
          <div class="space-y-2">
            <h2 class="text-base font-semibold text-gray-800">SERVER NAMES</h2>
            <div class="flex items-center gap-1">
              <div class="relative flex-1">
                <input v-model="serverNameSearch" type="text" placeholder="Search server names..."
                  class="block w-full pl-7 pr-1 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]" />
                <svg class="absolute left-1.5 top-1/2 -translate-y-1/2 h-3 w-3 text-gray-400" fill="none"
                  viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <button @click="addServerName"
                class="px-2 py-1 text-xs bg-[#005188] text-white rounded hover:bg-[#0066a7] transition-colors flex items-center gap-1 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
                </svg>
                ADD
              </button>
            </div>
            <div class="space-y-1">
              <input v-model="newServerName" @keyup.enter="addServerName" type="text" placeholder="Add new server name"
                class="w-full p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">
              <div v-if="filteredServerNames.length > 0" class="max-h-40 overflow-y-auto border rounded">
                <div v-for="(name, index) in filteredServerNames" :key="index"
                  class="p-1 text-sm hover:bg-gray-100 flex justify-between items-center">
                  <span>{{ name }}</span>
                  <button @click="removeServerName(index)" class="text-red-500 hover:text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
              <p v-else-if="serverNames.length === 0" class="text-xs text-gray-500">No server names added yet</p>
            </div>
          </div>

          <!-- Locations -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-800">LOCATIONS</h2>
              <button @click="addLocation"
                class="px-2 py-1 text-xs bg-[#005188] text-white rounded hover:bg-[#0066a7] transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
                </svg>
                ADD
              </button>
            </div>
            <div v-for="(location, index) in locations" :key="index" class="flex items-center gap-1">
              <input v-model="location.path" type="text" placeholder="path"
                class="flex-1 p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 shrink-0" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
              <input v-model="location.proxy_pass" type="text" placeholder="proxy_pass"
                class="flex-1 p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">
              <div class="flex gap-0.5 shrink-0">
                <button @click="removeLocation(index)"
                  class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                      d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
                <button @click="showLocationHelp(index)"
                  class="p-1 text-[#005188] hover:bg-blue-50 rounded transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Server Parameters -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-800">SERVER PARAMETERS</h2>
              <button @click="addParameter"
                class="px-2 py-1 text-xs bg-[#005188] text-white rounded hover:bg-[#0066a7] transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
                </svg>
                ADD
              </button>
            </div>
            <div v-for="(param, index) in parameters" :key="index" class="flex items-center gap-1">
              <input v-model="param.key" type="text" placeholder="parameter name"
                class="flex-1 p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">
              <input v-model="param.value" type="text" placeholder="parameter value"
                class="flex-1 p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">
              <div class="flex gap-0.5 shrink-0">
                <button @click="removeParameter(index)"
                  class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                      d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
                <button @click="showParameterHelp(index)"
                  class="p-1 text-[#005188] hover:bg-blue-50 rounded transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SEZIONE DESTRA (30%) -->
        <div class="w-5/12 p-4 bg-gray-50 flex flex-col space-y-3">
          <h2 class="text-base font-semibold text-gray-800 uppercase">SERVER TITLE</h2>

          <input v-model="serverTitle" type="text" placeholder="titolo"
            class="p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">

          <input type="number" v-model="serverPort" placeholder="porta"
            class="p-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">

          <!-- Protocolli -->
          <div class="space-y-1">
            <div v-for="option in protocolOptions" :key="option" class="flex items-center justify-between">
              <span class="text-xs uppercase tracking-wider">{{ option }}</span>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="radio" name="protocol" :value="option" v-model="selectedProtocol" class="sr-only peer">
                <div
                  class="w-7 h-4 bg-gray-200 rounded-full peer peer-checked:after:translate-x-3 peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#005188]">
                </div>
              </label>
            </div>
          </div>

          <!-- Certificati -->
          <div class="space-y-1">
            <h3 class="text-xs uppercase tracking-wider text-gray-600">CERTIFICATI</h3>
            <select v-model="selectedCertificate"
              class="w-full p-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-[#005188]">
              <option disabled value="">Scegli certificato</option>
              <option v-for="cert in certificates" :key="cert">{{ cert }}</option>
            </select>
          </div>

          <div class="mt-auto space-y-2">
            <button @click="saveConfiguration"
              class="w-full px-3 py-1 text-xs bg-[#005188] text-white rounded hover:bg-[#0066a7] transition-colors uppercase tracking-wider">
              Save
            </button>
            <button @click="$emit('close')"
              class="w-full px-3 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors uppercase tracking-wider">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'
import type { Server, Certificate, Parameter as StoreParameter } from '@/stores/services/nginx/nginx'

// Emit declaration
const emit = defineEmits<{
  close: []
}>()

// Use types from the Pinia store
interface Directive {
  name: string
  value: string
}

interface Location {
  path: string
  proxy_pass: string
  directives: Directive[]
}

interface Configuration extends Omit<Server, 'server_id' | 'locations'> {
  locations: Omit<Location, 'location_id'>[]
  directives: StoreParameter[]
}

// Pinia store
const nginxStore = useNginxStore()

// Reactive data
const selectedProtocol = ref<'HTTP' | 'HTTPS' | 'MTLS'>('HTTPS')
const serverPort = ref<number>(443)
const serverTitle = ref<string>('')
const newServerName = ref<string>('')
const serverNameSearch = ref<string>('')
const serverNames = ref<string[]>([])

const parameters = ref<StoreParameter[]>([])
const selectedCertificate = ref<string>('')

// Computed
const filteredServerNames = computed(() =>
  serverNames.value.filter(name =>
    name.toLowerCase().includes(serverNameSearch.value.toLowerCase())
  )
)

const certificates = computed(() =>
  nginxStore.certificates.map(cert => cert.cert_path)
)

const protocolOptions = computed(() => {
  const options = ['HTTP', 'HTTPS'] as const
  if (nginxStore.selfSignedCertificates.length > 0) {
    return [...options, 'MTLS'] as const
  }
  return options
})

const defaultDirectives = computed(() =>
  nginxStore.locationParameters.map(p => ({
    name: p.name,
    value: p.value
  }))
)

// Helper to create a new location object
const createNewLocation = (): Location => ({
  path: '',
  proxy_pass: '',
  directives: defaultDirectives.value.map(d => ({
    name: d.name,
    value: d.value
  }))
})

// Locations initialized with one entry
const locations = ref<Location[]>([createNewLocation()])

// Methods
const addServerName = (): void => {
  if (newServerName.value.trim()) {
    serverNames.value.push(newServerName.value.trim())
    newServerName.value = ''
  }
}

const removeServerName = (index: number): void => {
  serverNames.value.splice(index, 1)
}

const addLocation = (): void => {
  locations.value.push(createNewLocation())
}

const removeLocation = (index: number): void => {
  if (locations.value.length > 1) {
    locations.value.splice(index, 1)
  } else {
    locations.value[0] = createNewLocation()
  }
}

const saveConfiguration = async (): Promise<void> => {
  try {
    // Validate required fields
    if (!serverTitle.value.trim()) {
      throw new Error('Server title is required')
    }

    if (serverNames.value.length === 0) {
      throw new Error('At least one server name is required')
    }

    const validLocations = locations.value
      .filter(loc => loc.path.trim() || loc.proxy_pass.trim())
      .map(loc => ({
        path: loc.path.trim(),
        proxy_pass: loc.proxy_pass.trim(),
        directives: loc.directives
          .filter(d => d.name.trim() || d.value.trim())
          .map(d => ({
            name: d.name.trim(),
            value: d.value.trim()
          }))
      }))

    if (validLocations.length === 0) {
      throw new Error('At least one valid location is required')
    }

    const config: Omit<Server, 'server_id'> = {
      server_title: serverTitle.value.trim(),
      port: serverPort.value,
      ssl_enabled: selectedProtocol.value !== 'HTTP',
      is_mtls: selectedProtocol.value === 'MTLS',
      is_http2: selectedProtocol.value === 'HTTPS',
      server_name: serverNames.value.join(' ').trim(),
      locations: validLocations.map(loc => ({
        path: loc.path,
        proxy_pass: loc.proxy_pass,
        directives: loc.directives
      })),
      directives: parameters.value
        .filter(param => param.name.trim() || param.value.trim())
        .map(param => ({
          name: param.name.trim(),
          value: param.value.trim()
        })),
      ssl_certificate: selectedProtocol.value !== 'HTTP'
        ? selectedCertificate.value.trim()
        : undefined,
      ssl_certificate_key: selectedProtocol.value !== 'HTTP'
        ? '' // You'll need to handle this properly
        : undefined,
      ssl_client_certificate: selectedProtocol.value === 'MTLS'
        ? '' // You'll need to handle this properly
        : undefined,
      ssl_verify_client: selectedProtocol.value === 'MTLS'
        ? 'on'
        : undefined
    }

    await nginxStore.addServer(config)
    emit('close')
  } catch (error) {
    console.error('Failed to save configuration:', error)
    alert(`Failed to save configuration: ${(error as Error).message}`)
  }
}

// Initialization
onMounted(async () => {
  try {
    await Promise.all([
      nginxStore.fetchCertificates(),
      nginxStore.fetchParameters()
    ])
  } catch (error) {
    console.error('Initialization failed:', error)
    alert(`Initialization failed: ${(error as Error).message}`)
  }
})
</script>
