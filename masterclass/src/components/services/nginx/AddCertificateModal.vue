<template>
  <div class="fixed inset-0 backdrop-blur-sm  bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
      <div class="bg-gradient-to-r from-[#007C52] to-[#009966] p-6 flex justify-between items-center">
        <h3 class="text-2xl font-bold text-white">{{ mode === 'add' ? 'Add SSL/TLS Certificate' : 'Edit Certificate' }}</h3>
        <button @click="closeModal" class="text-white hover:text-gray-200 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-6">
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

        <div class="space-y-4">
          <!-- Certificate Name -->
          <div>
            <label class="block text-gray-700 font-medium mb-2">Certificate Name <span class="text-red-500">*</span></label>
            <input
              v-model="formData.cert_name"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors"
              placeholder="My Certificate"
              :class="{ 'border-red-300': formErrors.cert_name }"
            >
            <p v-if="formErrors.cert_name" class="text-red-500 text-sm mt-1">{{ formErrors.cert_name }}</p>
          </div>

          <!-- File Upload Section -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Certificate File -->
            <div>
              <label class="block text-gray-700 font-medium mb-2">Certificate File (.crt/.pem) <span class="text-red-500">*</span></label>
              <div class="flex items-center justify-center w-full">
                <label class="flex flex-col w-full border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                       :class="{ 'border-red-300': formErrors.cert_file, 'border-gray-300': !formErrors.cert_file }">
                  <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p v-if="!certFile" class="text-sm text-gray-500 mt-2 text-center">Click to upload certificate file</p>
                    <p v-else class="text-sm text-[#007C52] font-medium mt-2 text-center break-all">{{ certFile.name }}</p>
                  </div>
                  <input
                    type="file"
                    ref="certFileInput"
                    class="hidden"
                    accept=".crt,.pem,.cer"
                    @change="handleCertFileChange"
                  >
                </label>
              </div>
              <p v-if="formErrors.cert_file" class="text-red-500 text-sm mt-1">{{ formErrors.cert_file }}</p>
            </div>

            <!-- Private Key File -->
            <div>
              <label class="block text-gray-700 font-medium mb-2">Private Key (.key) <span class="text-red-500">*</span></label>
              <div class="flex items-center justify-center w-full">
                <label class="flex flex-col w-full border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                       :class="{ 'border-red-300': formErrors.key_file, 'border-gray-300': !formErrors.key_file }">
                  <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <p v-if="!keyFile" class="text-sm text-gray-500 mt-2 text-center">Click to upload private key</p>
                    <p v-else class="text-sm text-[#007C52] font-medium mt-2 text-center break-all">{{ keyFile.name }}</p>
                  </div>
                  <input
                    type="file"
                    ref="keyFileInput"
                    class="hidden"
                    accept=".key,.pem"
                    @change="handleKeyFileChange"
                  >
                </label>
              </div>
              <p v-if="formErrors.key_file" class="text-red-500 text-sm mt-1">{{ formErrors.key_file }}</p>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-gray-700 font-medium mb-2">Notes</label>
            <textarea
              v-model="formData.notes"
              rows="3"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent transition-colors resize-none"
              placeholder="Any additional notes about this certificate..."
            ></textarea>
          </div>

          <!-- Checkboxes -->
          <div class="flex items-center space-x-6">
            <div class="flex items-center">
              <input
                type="checkbox"
                id="self-signed"
                v-model="formData.is_self_signed"
                class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] rounded border-gray-300"
              >
              <label for="self-signed" class="ml-2 text-gray-700 cursor-pointer">Self-signed certificate</label>
            </div>

            <div class="flex items-center">
              <input
                type="checkbox"
                id="auto-renew"
                v-model="formData.auto_renew"
                class="h-5 w-5 text-[#007C52] focus:ring-[#007C52] rounded border-gray-300"
              >
              <label for="auto-renew" class="ml-2 text-gray-700 cursor-pointer">Enable Auto-Renewal</label>
            </div>
          </div>

          <!-- Certificate Preview (Edit Mode) -->
          <div v-if="mode === 'edit' && certificate" class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-3">Current Certificate Details</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Issuer:</span>
                <p class="font-medium">{{ certificate.issuer }}</p>
              </div>
              <div>
                <span class="text-gray-600">Valid Until:</span>
                <p class="font-medium">{{ formatDate(certificate.valid_to) }}</p>
              </div>
              <div>
                <span class="text-gray-600">Subject:</span>
                <p class="font-medium">{{ certificate.subject }}</p>
              </div>
              <div>
                <span class="text-gray-600">Key Size:</span>
                <p class="font-medium">{{ certificate.key_size }} bits</p>
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
            :disabled="isSubmitting"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSubmitting ? 'Processing...' : mode === 'add' ? 'Add Certificate' : 'Update Certificate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx' // Adjust import path as needed

// Types
interface Certificate {
  cert_id: number
  cert_name: string
  cert_path: string
  key_path: string
  issuer: string
  subject: string
  valid_from: string
  valid_to: string
  serial_number: string
  fingerprint: string
  algorithm: string
  key_size: number
  is_self_signed: boolean
  notes: string
}

interface FormData {
  cert_name: string
  notes: string
  is_self_signed: boolean
  auto_renew: boolean
}

interface FormErrors {
  cert_name?: string
  cert_file?: string
  key_file?: string
}

// Props
const props = defineProps<{
  mode: 'add' | 'edit'
  certificate?: Certificate | null
}>()

// Emits
const emit = defineEmits<{
  close: []
  submit: [formData: FormData, certFile: File | null, keyFile: File | null]
}>()

// Store
const nginxStore = useNginxStore()

// Form state
const formData = ref<FormData>({
  cert_name: '',
  notes: '',
  is_self_signed: false,
  auto_renew: false
})

// File handling
const certFile = ref<File | null>(null)
const keyFile = ref<File | null>(null)
const certFileInput = ref<HTMLInputElement | null>(null)
const keyFileInput = ref<HTMLInputElement | null>(null)

// UI state
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const formErrors = ref<FormErrors>({})

// Computed
const isFormValid = computed(() => {
  const hasName = formData.value.cert_name.trim().length > 0
  const hasFiles = props.mode === 'edit' || (certFile.value && keyFile.value)
  return hasName && hasFiles
})

// Methods
const handleCertFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]

    // Validate file type
    const validExtensions = ['.crt', '.pem', '.cer']
    const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'))

    if (!validExtensions.includes(fileExtension)) {
      formErrors.value.cert_file = 'Please select a valid certificate file (.crt, .pem, .cer)'
      return
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      formErrors.value.cert_file = 'File size must be less than 5MB'
      return
    }

    certFile.value = file
    delete formErrors.value.cert_file
  }
}

const handleKeyFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]

    // Validate file type
    const validExtensions = ['.key', '.pem']
    const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'))

    if (!validExtensions.includes(fileExtension)) {
      formErrors.value.key_file = 'Please select a valid private key file (.key, .pem)'
      return
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      formErrors.value.key_file = 'File size must be less than 5MB'
      return
    }

    keyFile.value = file
    delete formErrors.value.key_file
  }
}

const validateForm = (): boolean => {
  formErrors.value = {}

  if (!formData.value.cert_name.trim()) {
    formErrors.value.cert_name = 'Certificate name is required'
  }

  if (props.mode === 'add') {
    if (!certFile.value) {
      formErrors.value.cert_file = 'Certificate file is required'
    }

    if (!keyFile.value) {
      formErrors.value.key_file = 'Private key file is required'
    }
  }

  return Object.keys(formErrors.value).length === 0
}

const closeModal = () => {
  resetForm()
  emit('close')
}

const resetForm = () => {
  formData.value = {
    cert_name: '',
    notes: '',
    is_self_signed: false,
    auto_renew: false
  }
  certFile.value = null
  keyFile.value = null
  formErrors.value = {}
  error.value = null

  // Clear file inputs
  if (certFileInput.value) certFileInput.value.value = ''
  if (keyFileInput.value) keyFileInput.value.value = ''
}

const submitForm = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    if (props.mode === 'add') {
      // Create FormData for file upload
      const formDataPayload = new FormData()

      // Append form data
      formDataPayload.append('cert_name', formData.value.cert_name)
      formDataPayload.append('notes', formData.value.notes)
      formDataPayload.append('is_self_signed', formData.value.is_self_signed.toString())

      // Append files
      if (certFile.value) {
        formDataPayload.append('cert_file', certFile.value)
      }
      if (keyFile.value) {
        formDataPayload.append('key_file', keyFile.value)
      }

      // Use fetch directly for file upload
      const response = await fetch(`${nginxStore.API_BASE_URL}/nginx/certificates`, {
        method: 'POST',
        body: formDataPayload,
        credentials: 'include'
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`)
      }

      const newCertificate = await response.json()

      // Update store
      nginxStore.certificates.push(newCertificate)

    } else if (props.mode === 'edit' && props.certificate) {
      // For edit mode, only update the metadata
      const updateData = {
        cert_name: formData.value.cert_name,
        notes: formData.value.notes,
        is_self_signed: formData.value.is_self_signed
      }

      await nginxStore.updateCertificate(props.certificate.cert_id, updateData)
    }

    emit('submit', formData.value, certFile.value, keyFile.value)
    closeModal()

  } catch (err) {
    error.value = err instanceof Error ? err.message : 'An error occurred while processing the certificate'
    console.error('Certificate submission error:', err)
  } finally {
    isSubmitting.value = false
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Watch for certificate prop changes (for edit mode)
watch(() => props.certificate, (newCert) => {
  if (newCert && props.mode === 'edit') {
    formData.value = {
      cert_name: newCert.cert_name,
      notes: newCert.notes || '',
      is_self_signed: newCert.is_self_signed,
      auto_renew: false // This might need to come from your certificate data
    }
  }
}, { immediate: true })

// Reset form when modal opens
watch(() => props.mode, () => {
  if (props.mode === 'add') {
    resetForm()
  }
})
</script>
