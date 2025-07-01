<template>
  <div class="fixed inset-0 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden max-h-[90vh] overflow-y-auto">
      <div class="bg-gradient-to-r from-[#007C52] to-[#009966] p-6 flex justify-between items-center">
        <h3 class="text-2xl font-bold text-white">Update Server Configuration</h3>
        <button @click="closeModal" class="text-white hover:text-gray-200 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#007C52]"></div>
          <span class="ml-3 text-gray-600">Loading server data...</span>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
              <p class="text-sm text-red-800">{{ error }}</p>
            </div>
          </div>
        </div>

        <!-- Form Content -->
        <div v-if="!isLoading" class="space-y-6">
          <!-- Basic Server Information -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-4">Basic Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Server Title -->
              <div>
                <label class="block text-gray-700 font-medium mb-2">Server Title <span class="text-red-500">*</span></label>
                <input
                  v-model="formData.server_title"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                  placeholder="My Server"
                  :class="{ 'border-red-300': formErrors.server_title }"
                >
                <p v-if="formErrors.server_title" class="text-red-500 text-sm mt-1">{{ formErrors.server_title }}</p>
              </div>

              <!-- Server Name -->
              <div>
                <label class="block text-gray-700 font-medium mb-2">Server Name <span class="text-red-500">*</span></label>
                <input
                  v-model="formData.server_name"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                  placeholder="example.com"
                  :class="{ 'border-red-300': formErrors.server_name }"
                >
                <p v-if="formErrors.server_name" class="text-red-500 text-sm mt-1">{{ formErrors.server_name }}</p>
              </div>

              <!-- Port -->
              <div>
                <label class="block text-gray-700 font-medium mb-2">Port <span class="text-red-500">*</span></label>
                <input
                  v-model.number="formData.port"
                  type="number"
                  min="1"
                  max="65535"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                  placeholder="80"
                  :class="{ 'border-red-300': formErrors.port }"
                >
                <p v-if="formErrors.port" class="text-red-500 text-sm mt-1">{{ formErrors.port }}</p>
              </div>
            </div>
          </div>

          <!-- SSL Configuration -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-4">SSL/TLS Configuration</h4>

            <!-- SSL Options -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="flex items-center">
                <input
                  type="checkbox"
                  id="ssl-enabled"
                  v-model="formData.ssl_enabled"
                  class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] rounded border-gray-300"
                >
                <label for="ssl-enabled" class="ml-2 text-gray-700 cursor-pointer">Enable SSL/TLS</label>
              </div>

              <div class="flex items-center">
                <input
                  type="checkbox"
                  id="mtls"
                  v-model="formData.is_mtls"
                  :disabled="!formData.ssl_enabled"
                  class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] rounded border-gray-300 disabled:opacity-50"
                >
                <label for="mtls" class="ml-2 text-gray-700 cursor-pointer">Enable mTLS</label>
              </div>

              <div class="flex items-center">
                <input
                  type="checkbox"
                  id="http2"
                  v-model="formData.is_http2"
                  class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] rounded border-gray-300"
                >
                <label for="http2" class="ml-2 text-gray-700 cursor-pointer">Enable HTTP/2</label>
              </div>
            </div>

            <!-- SSL Certificate Fields -->
            <div v-if="formData.ssl_enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 font-medium mb-2">SSL Certificate Path</label>
                <input
                  v-model="formData.ssl_certificate"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                  placeholder="/path/to/cert.pem"
                  :class="{ 'border-red-300': formErrors.ssl_certificate }"
                >
                <p v-if="formErrors.ssl_certificate" class="text-red-500 text-sm mt-1">{{ formErrors.ssl_certificate }}</p>
              </div>

              <div>
                <label class="block text-gray-700 font-medium mb-2">SSL Certificate Key Path</label>
                <input
                  v-model="formData.ssl_certificate_key"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                  placeholder="/path/to/key.pem"
                  :class="{ 'border-red-300': formErrors.ssl_certificate_key }"
                >
                <p v-if="formErrors.ssl_certificate_key" class="text-red-500 text-sm mt-1">{{ formErrors.ssl_certificate_key }}</p>
              </div>

              <!-- mTLS fields -->
              <div v-if="formData.is_mtls">
                <label class="block text-gray-700 font-medium mb-2">Client Certificate Path</label>
                <input
                  v-model="formData.ssl_client_certificate"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                  placeholder="/path/to/client-cert.pem"
                >
              </div>

              <div v-if="formData.is_mtls">
                <label class="block text-gray-700 font-medium mb-2">SSL Verify Client</label>
                <select
                  v-model="formData.ssl_verify_client"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
                >
                  <option value="">Select verification level</option>
                  <option value="on">On</option>
                  <option value="off">Off</option>
                  <option value="optional">Optional</option>
                  <option value="optional_no_ca">Optional No CA</option>
                </select>
              </div>
            </div>
          </div>

          <!-- WebSocket Support -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-4">Additional Features</h4>
            <div class="flex items-center">
              <input
                type="checkbox"
                id="websocket"
                v-model="formData.is_websocket_enabled"
                class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] rounded border-gray-300"
              >
              <label for="websocket" class="ml-2 text-gray-700 cursor-pointer">Enable WebSocket Support</label>
            </div>
          </div>

          <!-- Locations -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
              <h4 class="font-semibold text-gray-900">Locations</h4>
              <button
                @click="addLocation"
                class="px-3 py-2 bg-[#007C52] text-white text-sm rounded-lg hover:bg-[#006044] transition-colors"
              >
                Add Location
              </button>
            </div>

            <div class="space-y-4">
              <div v-for="(location, index) in formData.locations" :key="index" class="border border-gray-200 rounded-lg p-4 bg-white">
                <div class="flex justify-between items-start mb-3">
                  <h5 class="font-medium text-gray-800">Location #{{ index + 1 }}</h5>
                  <button
                    @click="removeLocation(index)"
                    class="text-red-500 hover:text-red-700 transition-colors"
                    :disabled="formData.locations.length === 1"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Path <span class="text-red-500">*</span></label>
                    <input
                      v-model="location.path"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors text-sm"
                      placeholder="/"
                      :class="{ 'border-red-300': formErrors[`location_${index}_path`] }"
                    >
                    <p v-if="formErrors[`location_${index}_path`]" class="text-red-500 text-xs mt-1">{{ formErrors[`location_${index}_path`] }}</p>
                  </div>

                  <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Proxy Pass <span class="text-red-500">*</span></label>
                    <input
                      v-model="location.proxy_pass"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors text-sm"
                      placeholder="http://backend:8080"
                      :class="{ 'border-red-300': formErrors[`location_${index}_proxy_pass`] }"
                    >
                    <p v-if="formErrors[`location_${index}_proxy_pass`]" class="text-red-500 text-xs mt-1">{{ formErrors[`location_${index}_proxy_pass`] }}</p>
                  </div>
                </div>
              </div>
            </div>
            <p v-if="formErrors.locations" class="text-red-500 text-sm mt-2">{{ formErrors.locations }}</p>
          </div>

          <!-- Server Directives -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
              <h4 class="font-semibold text-gray-900">Server Directives</h4>
              <button
                @click="addDirective"
                class="px-3 py-2 bg-[#007C52] text-white text-sm rounded-lg hover:bg-[#006044] transition-colors"
              >
                Add Directive
              </button>
            </div>

            <div class="space-y-3">
              <div v-for="(directive, index) in formData.directives" :key="index" class="flex gap-3 items-start">
                <input
                  v-model="directive.name"
                  type="text"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors text-sm"
                  placeholder="Directive name"
                >
                <input
                  v-model="directive.value"
                  type="text"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors text-sm"
                  placeholder="Directive value"
                >
                <button
                  @click="removeDirective(index)"
                  class="text-red-500 hover:text-red-700 transition-colors p-2"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
          <button
            @click="closeModal"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-gray-200"
            :disabled="isSubmitting"
          >
            Cancel
          </button>
          <button
            @click="submitForm"
            class="px-6 py-3 bg-[#007C52] text-white rounded-lg hover:bg-[#006044] transition-colors flex items-center focus:ring-2 focus:ring-[#007C52] focus:ring-offset-2"
            :disabled="isSubmitting || isLoading"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSubmitting ? 'Updating...' : 'Update Server' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useNginxStore, type Server, type Location, type Parameter } from '@/stores/services/nginx/nginx'

// Types
interface FormData {
  server_title: string
  server_name: string
  port: number
  ssl_enabled: boolean
  is_mtls: boolean
  is_http2: boolean
  ssl_certificate?: string
  ssl_certificate_key?: string
  ssl_client_certificate?: string
  ssl_verify_client?: string
  is_websocket_enabled?: boolean
  locations: Location[]
  directives: Parameter[]
}

interface FormErrors {
  [key: string]: string
}

// Props
const props = defineProps<{
  serverId: number
}>()

// Emits
const emit = defineEmits<{
  close: []
  submit: [serverId: number, formData: FormData]
}>()

// Store
const nginxStore = useNginxStore()

// Form state
const formData = ref<FormData>({
  server_title: '',
  server_name: '',
  port: 80,
  ssl_enabled: false,
  is_mtls: false,
  is_http2: false,
  ssl_certificate: '',
  ssl_certificate_key: '',
  ssl_client_certificate: '',
  ssl_verify_client: '',
  is_websocket_enabled: false,
  locations: [],
  directives: []
})

// UI state
const isLoading = ref(true)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const formErrors = ref<FormErrors>({})

// Computed


// Methods
const loadServerData = async () => {
  isLoading.value = true
  error.value = null

  try {
    // Get server from store or fetch if not available
    let server = nginxStore.getServerById(props.serverId)

    if (!server) {
      await nginxStore.fetchServers()
      server = nginxStore.getServerById(props.serverId)
    }

    if (!server) {
      throw new Error('Server not found')
    }

    // Populate form data
    formData.value = {
      server_title: server.server_title,
      server_name: server.server_name,
      port: server.port,
      ssl_enabled: server.ssl_enabled,
      is_mtls: server.is_mtls,
      is_http2: server.is_http2,
      ssl_certificate: server.ssl_certificate || '',
      ssl_certificate_key: server.ssl_certificate_key || '',
      ssl_client_certificate: server.ssl_client_certificate || '',
      ssl_verify_client: server.ssl_verify_client || '',
      is_websocket_enabled: server.is_websocket_enabled || false,
      locations: server.locations.map(loc => ({
        path: loc.path,
        proxy_pass: loc.proxy_pass,
        directives: [...loc.directives]
      })),
      directives: [...server.directives]
    }

  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load server data'
    console.error('Failed to load server data:', err)
  } finally {
    isLoading.value = false
  }
}
const defaultDirectives = computed(() =>
  nginxStore.locationParameters.map(p => ({
    name: p.name,
    value: p.value
  }))
)

const addLocation = () => {
  formData.value.locations.push({
    path: '',
    proxy_pass: '',
    directives: defaultDirectives.value.map(d => ({
    name: d.name,
    value: d.value
    }))
  })
}

const removeLocation = (index: number) => {
  if (formData.value.locations.length > 1) {
    formData.value.locations.splice(index, 1)
  }
}

const addDirective = () => {
  formData.value.directives.push({
    name: '',
    value: '',
  })
}

const removeDirective = (index: number) => {
  formData.value.directives.splice(index, 1)
}

const validateForm = (): boolean => {
  formErrors.value = {}

  // Basic validation
  if (!formData.value.server_title.trim()) {
    formErrors.value.server_title = 'Server title is required'
  }

  if (!formData.value.server_name.trim()) {
    formErrors.value.server_name = 'Server name is required'
  }

  if (!formData.value.port || formData.value.port < 1 || formData.value.port > 65535) {
    formErrors.value.port = 'Valid port number (1-65535) is required'
  }

  // SSL validation
  if (formData.value.ssl_enabled) {
    if (!formData.value.ssl_certificate?.trim()) {
      formErrors.value.ssl_certificate = 'SSL certificate path is required when SSL is enabled'
    }
    if (!formData.value.ssl_certificate_key?.trim()) {
      formErrors.value.ssl_certificate_key = 'SSL certificate key path is required when SSL is enabled'
    }
  }

  // Locations validation
  if ((formData.value.locations.length === 0 && formData.value.directives.length === 0)) {
    formErrors.value.locations = 'At least one location is required'
  } else {
    formData.value.locations.forEach((location, index) => {
      if (!location.path?.trim()) {
        formErrors.value[`location_${index}_path`] = 'Path is required'
      }
      if (!location.proxy_pass?.trim()) {
        formErrors.value[`location_${index}_proxy_pass`] = 'Proxy pass is required'
      }
    })
  }

  return Object.keys(formErrors.value).length === 0
}

const closeModal = () => {
  emit('close')
}

async function submitForm () {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    // Use the store's validation
    const validation = nginxStore.validateServerConfig(formData.value)
    if (!validation.isValid) {
      error.value = validation.errors.join(', ')
      return
    }


    // Update server via store
    await nginxStore.updateServer(props.serverId, formData.value)

    emit('submit', props.serverId, formData.value)
    closeModal()

  } catch (err) {
    error.value = err instanceof Error ? err.message : 'An error occurred while updating the server'
    console.error('Server update error:', err)
  } finally {
    isSubmitting.value = false
  }
}

// Watch for SSL enabled changes
watch(() => formData.value.ssl_enabled, (newValue) => {
  if (!newValue) {
    // Clear SSL-related fields when SSL is disabled
    formData.value.is_mtls = false
    formData.value.ssl_certificate = ''
    formData.value.ssl_certificate_key = ''
    formData.value.ssl_client_certificate = ''
    formData.value.ssl_verify_client = ''
  }
})

watch(() => formData.value.is_mtls, (newValue) => {
  if (!newValue) {
    // Clear mTLS-related fields when mTLS is disabled
    formData.value.ssl_client_certificate = ''
    formData.value.ssl_verify_client = ''
  }
})

// Load server data on mount
onMounted(() => {
  loadServerData()
})
</script>
