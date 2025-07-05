<template>
  <div class="fixed inset-0 bg-white z-50 overflow-y-auto">
    <div class="min-h-screen w-full">
      <!-- Header Section -->
      <div class="relative overflow-hidden bg-gradient-to-br from-[#007C52] via-[#008A5A] to-[#009966] px-8 py-8">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_25%_25%,rgba(255,255,255,0.2)_0%,transparent_50%)]"></div>

        <div class="relative flex justify-between items-center max-w-7xl mx-auto">
          <div class="flex items-center gap-4">
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur transition-all duration-200 hover:bg-white/30 hover:scale-105">
              <svg class="w-8 h-8 text-white stroke-[1.5]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <h3 class="text-3xl font-bold text-white leading-tight drop-shadow-sm">
                {{ mode === 'add' ? 'Add SSL/TLS Certificate' : 'Edit Certificate' }}
              </h3>
              <p class="text-lg text-white/80 mt-1 drop-shadow-sm">
                {{ mode === 'add' ? 'Upload and configure your certificate' : 'Update certificate settings' }}
              </p>
            </div>
          </div>
          <button
            @click="closeModal"
            class="text-white/80 p-3 rounded-xl backdrop-blur transition-all duration-200 hover:text-white hover:bg-white/20"
            aria-label="Close modal"
          >
            <svg class="w-6 h-6 stroke-2 transition-transform duration-200 hover:rotate-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-8 py-8 lg:px-12">
        <!-- Error Display -->
        <div v-if="error" class="animate-fade-in bg-gradient-to-r from-[#FEF2F2] to-[#FEE2E2] border-l-4 border-[#F87171] rounded-xl shadow-sm p-6 flex">
          <div class="bg-[#FECACA] p-2 rounded-lg mr-4">
            <svg class="w-6 h-6 text-[#EF4444]" viewBox="0 0 20 20">
              <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" />
            </svg>
          </div>
          <div class="flex-1">
            <h4 class="text-lg font-semibold text-[#991B1B]">Error</h4>
            <p class="text-[#B91C1C] mt-1">{{ error }}</p>
          </div>
        </div>

        <div class="flex flex-col gap-8">
          <!-- Certificate Name -->
          <div class="flex flex-col gap-3">
            <label class="text-gray-900 font-semibold text-lg flex items-center">
              <span class="bg-[#007C52] text-white p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 stroke-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </span>
              Certificate Name
              <span class="text-[#EF4444] ml-2">*</span>
            </label>
            <input
              v-model="formData.cert_name"
              type="text"
              class="w-full px-6 py-4 border-2 border-gray-200 rounded-xl text-lg transition-all duration-200 outline-none focus:border-[#007C52] focus:ring-4 focus:ring-[#007C52]/10 placeholder:text-gray-400"
              :class="{ 'border-[#FCA5A5] focus:border-[#F87171] focus:ring-[#F87171]/10': formErrors.cert_name }"
              placeholder="Enter a descriptive name for your certificate"
            >
            <p v-if="formErrors.cert_name" class="text-[#DC2626] text-sm mt-2 flex items-center">
              <svg class="w-4 h-4 mr-1" viewBox="0 0 20 20">
                <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/>
              </svg>
              {{ formErrors.cert_name }}
            </p>
          </div>

          <!-- File Upload Section -->
          <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Certificate File -->
            <div class="flex flex-col gap-3">
              <label class="block text-gray-900 font-semibold text-lg flex items-center">
                <span class="bg-[#007C52] text-white p-2 rounded-lg mr-3">
                  <svg class="w-5 h-5 stroke-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </span>
                Certificate File
                <span class="text-[#EF4444] ml-2">*</span>
              </label>

              <div class="relative">
                <label class="flex flex-col w-full border-3 border-dashed rounded-xl cursor-pointer transition-all duration-300 overflow-hidden"
                  :class="{
                    'border-gray-300 hover:border-[#007C52]/50 hover:bg-gradient-to-br hover:from-[#007C52]/5 hover:to-[#009966]/5': !formErrors.cert_file && !certFile,
                    'border-[#FCA5A5] bg-[#FEF2F2]': formErrors.cert_file,
                    'border-[#007C52] bg-gradient-to-br from-[#007C52]/10 to-[#009966]/10': certFile
                  }"
                >
                  <div class="flex flex-col items-center justify-center px-6 py-12">
                    <div class="bg-gradient-to-br from-[#007C52] to-[#009966] p-4 rounded-xl mb-4 transition-transform duration-200 group-hover:scale-110">
                      <svg class="w-12 h-12 text-white stroke-[1.5]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                      </svg>
                    </div>
                    <div class="text-center">
                      <template v-if="!certFile">
                        <p class="text-gray-700 text-lg font-medium mb-1">Drop certificate file here</p>
                        <p class="text-gray-500 text-sm">or click to browse</p>
                      </template>
                      <template v-else>
                        <p class="text-[#007C52] font-semibold text-lg">{{ certFile.name }}</p>
                        <p class="text-gray-500 text-sm">{{ (certFile.size / 1024).toFixed(1) }} KB</p>
                      </template>
                    </div>
                    <p class="text-gray-400 text-xs mt-4">Supported: .crt, .pem, .cer (max 5MB)</p>
                  </div>
                  <input
                    type="file"
                    ref="certFileInput"
                    class="absolute w-px h-px opacity-0 overflow-hidden"
                    accept=".crt,.pem,.cer"
                    @change="handleCertFileChange"
                  >
                </label>
              </div>

              <p v-if="formErrors.cert_file" class="text-[#DC2626] text-sm mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" viewBox="0 0 20 20">
                  <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/>
                </svg>
                {{ formErrors.cert_file }}
              </p>
            </div>

            <!-- Private Key File -->
            <div class="flex flex-col gap-3">
              <label class="block text-gray-900 font-semibold text-lg flex items-center">
                <span class="bg-[#007C52] text-white p-2 rounded-lg mr-3">
                  <svg class="w-5 h-5 stroke-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                  </svg>
                </span>
                Private Key
                <span class="text-[#EF4444] ml-2">*</span>
              </label>

              <div class="relative">
                <label class="flex flex-col w-full border-3 border-dashed rounded-xl cursor-pointer transition-all duration-300 overflow-hidden"
                  :class="{
                    'border-gray-300 hover:border-[#007C52]/50 hover:bg-gradient-to-br hover:from-[#007C52]/5 hover:to-[#009966]/5': !formErrors.key_file && !keyFile,
                    'border-[#FCA5A5] bg-[#FEF2F2]': formErrors.key_file,
                    'border-[#007C52] bg-gradient-to-br from-[#007C52]/10 to-[#009966]/10': keyFile
                  }"
                >
                  <div class="flex flex-col items-center justify-center px-6 py-12">
                    <div class="bg-gradient-to-br from-[#007C52] to-[#009966] p-4 rounded-xl mb-4 transition-transform duration-200 group-hover:scale-110">
                      <svg class="w-12 h-12 text-white stroke-[1.5]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                      </svg>
                    </div>
                    <div class="text-center">
                      <template v-if="!keyFile">
                        <p class="text-gray-700 text-lg font-medium mb-1">Drop private key here</p>
                        <p class="text-gray-500 text-sm">or click to browse</p>
                      </template>
                      <template v-else>
                        <p class="text-[#007C52] font-semibold text-lg">{{ keyFile.name }}</p>
                        <p class="text-gray-500 text-sm">{{ (keyFile.size / 1024).toFixed(1) }} KB</p>
                      </template>
                    </div>
                    <p class="text-gray-400 text-xs mt-4">Supported: .key, .pem (max 5MB)</p>
                  </div>
                  <input
                    type="file"
                    ref="keyFileInput"
                    class="absolute w-px h-px opacity-0 overflow-hidden"
                    accept=".key,.pem"
                    @change="handleKeyFileChange"
                  >
                </label>
              </div>

              <p v-if="formErrors.key_file" class="text-[#DC2626] text-sm mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" viewBox="0 0 20 20">
                  <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/>
                </svg>
                {{ formErrors.key_file }}
              </p>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="flex flex-col gap-3">
            <label class="block text-gray-900 font-semibold text-lg flex items-center">
              <span class="bg-[#007C52] text-white p-2 rounded-lg mr-3">
                <svg class="w-5 h-5 stroke-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </span>
              Additional Notes
            </label>
            <textarea
              v-model="formData.notes"
              rows="4"
              class="w-full px-6 py-4 border-2 border-gray-200 rounded-xl text-lg transition-all duration-200 outline-none focus:border-[#007C52] focus:ring-4 focus:ring-[#007C52]/10 placeholder:text-gray-400 resize-none min-h-[7.5rem]"
              placeholder="Add any additional information about this certificate, such as usage notes, renewal reminders, or configuration details..."
            ></textarea>
            <div class="flex justify-end mt-1">
              <span class="text-gray-500 text-xs">{{ formData.notes.length }}/500</span>
            </div>
          </div>

          <!-- Checkbox Options -->
          <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 shadow-inner shadow-black/5">
            <h4 class="text-gray-900 font-semibold text-lg mb-4">Certificate Options</h4>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <label class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 transition-all duration-200 cursor-pointer hover:border-[#007C52]/30 hover:-translate-y-0.5 hover:shadow hover:shadow-gray-200"
                :class="{ 'border-[#007C52] bg-[#007C52]/5': formData.is_self_signed }"
              >
                <input
                  type="checkbox"
                  id="self-signed"
                  v-model="formData.is_self_signed"
                  class="absolute w-px h-px opacity-0 overflow-hidden"
                >
                <span class="w-6 h-6 rounded-lg border-2 border-gray-300 transition-all duration-200 relative after:absolute after:inset-0 after:bg-[#007C52] after:rounded-md after:scale-0 after:opacity-0 after:transition-all after:duration-200 after:bg-[url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;white&quot;><path d=&quot;M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z&quot;/></svg>')] after:bg-center after:bg-no-repeat after:bg-[length:1rem]"
                  :class="{
                    'border-[#007C52] bg-[#007C52]': formData.is_self_signed,
                    'after:scale-100 after:opacity-100': formData.is_self_signed
                  }"
                ></span>
                <span class="flex-1">
                  <span class="block text-gray-700 text-lg font-medium">Self-signed certificate</span>
                  <span class="block text-gray-500 text-sm mt-1">Mark if this is a self-signed certificate</span>
                </span>
              </label>

              <label class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 transition-all duration-200 cursor-pointer hover:border-[#007C52]/30 hover:-translate-y-0.5 hover:shadow hover:shadow-gray-200"
                :class="{ 'border-[#007C52] bg-[#007C52]/5': formData.auto_renew }"
              >
                <input
                  type="checkbox"
                  id="auto-renew"
                  v-model="formData.auto_renew"
                  class="absolute w-px h-px opacity-0 overflow-hidden"
                >
                <span class="w-6 h-6 rounded-lg border-2 border-gray-300 transition-all duration-200 relative after:absolute after:inset-0 after:bg-[#007C52] after:rounded-md after:scale-0 after:opacity-0 after:transition-all after:duration-200 after:bg-[url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;white&quot;><path d=&quot;M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z&quot;/></svg>')] after:bg-center after:bg-no-repeat after:bg-[length:1rem]"
                  :class="{
                    'border-[#007C52] bg-[#007C52]': formData.auto_renew,
                    'after:scale-100 after:opacity-100': formData.auto_renew
                  }"
                ></span>
                <span class="flex-1">
                  <span class="block text-gray-700 text-lg font-medium">Enable Auto-Renewal</span>
                  <span class="block text-gray-500 text-sm mt-1">Automatically renew when possible</span>
                </span>
              </label>
            </div>
          </div>

          <!-- Certificate Preview (Edit Mode) -->
          <div v-if="mode === 'edit' && certificate" class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-8 border border-blue-200">
            <div class="flex items-center mb-6">
              <div class="bg-gradient-to-br from-blue-500 to-indigo-500 p-3 rounded-xl mr-4">
                <svg class="w-6 h-6 text-white stroke-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h4 class="text-gray-900 font-bold text-xl">Current Certificate Details</h4>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div class="bg-white rounded-lg p-4 border border-blue-200 transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Issuer</span>
                <p class="text-gray-900 font-semibold text-lg mt-1">{{ certificate.issuer }}</p>
              </div>
              <div class="bg-white rounded-lg p-4 border border-blue-200 transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Valid Until</span>
                <p class="text-gray-900 font-semibold text-lg mt-1">{{ formatDate(certificate.valid_to) }}</p>
              </div>
              <div class="bg-white rounded-lg p-4 border border-blue-200 transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Subject</span>
                <p class="text-gray-900 font-semibold text-lg mt-1 break-words">{{ certificate.subject }}</p>
              </div>
              <div class="bg-white rounded-lg p-4 border border-blue-200 transition-transform duration-200 hover:-translate-y-0.5">
                <span class="text-gray-500 font-medium text-xs uppercase tracking-wider">Key Size</span>
                <p class="text-gray-900 font-semibold text-lg mt-1">{{ certificate.key_size }} bits</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-4 pt-8 border-t-2 border-gray-100 sticky bottom-0 bg-white pb-8 sm:flex-row sm:justify-end sm:gap-6">
          <button
            @click="closeModal"
            class="px-8 py-4 border-2 border-gray-300 text-gray-700 rounded-xl transition-all duration-200 font-semibold text-lg flex items-center justify-center hover:bg-gray-50 hover:border-gray-400 focus:ring-4 focus:ring-gray-200"
            :disabled="isSubmitting"
          >
            <svg class="w-5 h-5 stroke-2 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M6 18L18 6M6 6l12 12" />
            </svg>
            Cancel
          </button>
          <button
            @click="submitForm"
            class="px-8 py-4 bg-gradient-to-r from-[#007C52] to-[#009966] text-white rounded-xl transition-all duration-200 font-semibold text-lg flex items-center justify-center shadow hover:from-[#006044] hover:to-[#007D54] hover:shadow-md hover:-translate-y-0.5 focus:ring-4 focus:ring-[#007C52]/30 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            :disabled="isSubmitting || !isFormValid"
          >
            <svg v-if="isSubmitting" class="animate-spin w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" class="opacity-25 stroke-current stroke-4" />
              <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="fill-current" />
            </svg>
            <svg v-else class="w-5 h-5 stroke-2 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ isSubmitting ? 'Processing...' : mode === 'add' ? 'Add Certificate' : 'Update Certificate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

c<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'

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
  auto_renew?: boolean
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
  (e: 'close'): void
  (e: 'submit', formData: FormData, certFile: File | null, keyFile: File | null): void
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
      const formDataPayload = new FormData()
      formDataPayload.append('cert_name', formData.value.cert_name)
      formDataPayload.append('notes', formData.value.notes)
      formDataPayload.append('is_self_signed', formData.value.is_self_signed.toString())
      formDataPayload.append('auto_renew', formData.value.auto_renew.toString())

      if (certFile.value) formDataPayload.append('cert_file', certFile.value)
      if (keyFile.value) formDataPayload.append('key_file', keyFile.value)

      const response = await fetch(`/api/nginx/certificates`, {
        method: 'POST',
        body: formDataPayload,
        credentials: 'include'
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`)
      }

      const newCertificate = await response.json()
      nginxStore.certificates.push(newCertificate)

    } else if (props.mode === 'edit' && props.certificate) {
      const updateData = {
        cert_name: formData.value.cert_name,
        notes: formData.value.notes,
        is_self_signed: formData.value.is_self_signed,
        auto_renew: formData.value.auto_renew
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

// Watchers
watch(() => props.certificate, (newCert) => {
  if (newCert && props.mode === 'edit') {
    formData.value = {
      cert_name: newCert.cert_name,
      notes: newCert.notes || '',
      is_self_signed: newCert.is_self_signed,
      auto_renew: newCert.auto_renew || false
    }
  }
}, { immediate: true })

watch(() => props.mode, () => {
  if (props.mode === 'add') {
    resetForm()
  }
})
</script>

<style>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-0.625rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
