<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header and Add Certificate Button -->
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800">SSL/TLS Certificates</h1>
      <button
        @click="openAddModal"
        class="bg-[#007C52] hover:bg-[#006044] text-white px-6 py-3 rounded-lg flex items-center space-x-2 transition-colors"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        <span>Add Certificate</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="nginxStore.certificatesLoading" class="bg-white rounded-xl shadow p-8 text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#007C52] mx-auto mb-4"></div>
      <p class="text-gray-600">Loading certificates...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="nginxStore.certificatesError" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <h3 class="text-red-800 font-medium">Error loading certificates</h3>
          <p class="text-red-600">{{ nginxStore.certificatesError }}</p>
        </div>
      </div>
    </div>

    <!-- Certificate Table -->
    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issuer</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid From</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid To</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="cert in nginxStore.certificates as Certificate[]" :key="cert.cert_id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ cert.cert_name }}</div>
                    <div class="text-sm text-gray-500">{{ cert.subject || 'No subject information' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ cert.issuer || 'Unknown' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ formatDate(cert.valid_from) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900" :class="{'text-red-600': isExpiringSoon(cert.valid_to)}">
                  {{ formatDate(cert.valid_to) }}
                  <span v-if="isExpiringSoon(cert.valid_to)" class="ml-1 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Expiring</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(cert.valid_to)">
                  {{ getStatusText(cert.valid_to) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button @click="openEditModal(cert)" class="text-blue-600 hover:text-blue-900 mr-4">Edit</button>
                <button @click="confirmDelete(cert.cert_id)" class="text-red-600 hover:text-red-900">Delete</button>
              </td>
            </tr>
            <tr v-if="nginxStore.certificates.length === 0">
              <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                No certificates found. Add your first certificate to get started.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Certificate Modal -->
    <div v-if="showCertificateModal" class="fixed inset-0 backdrop-blur-sm bg-black bg-opacity-30 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-[#007C52] to-[#009966] p-6 flex justify-between items-center">
          <h3 class="text-2xl font-bold text-white">{{ modalMode === 'add' ? 'Add SSL/TLS Certificate' : 'Edit Certificate' }}</h3>
          <button @click="closeModal" class="text-white hover:text-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-6">
          <div class="space-y-4">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Certificate Name</label>
              <input
                v-model="currentCert.cert_name"
                type="text"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent"
                placeholder="My Certificate"
              >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 font-medium mb-2">Certificate File (.crt/.pem)</label>
                <div class="flex items-center justify-center w-full">
                  <label class="flex flex-col w-full border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <p v-if="!certFile" class="text-sm text-gray-500 mt-2">Click to upload certificate file</p>
                      <p v-else class="text-sm text-[#007C52] font-medium mt-2">{{ certFile.name }}</p>
                    </div>
                    <input
                      type="file"
                      ref="certFileInput"
                      class="hidden"
                      accept=".crt,.pem"
                      @change="handleCertFileChange"
                    >
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-gray-700 font-medium mb-2">Private Key (.key)</label>
                <div class="flex items-center justify-center w-full">
                  <label class="flex flex-col w-full border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                      </svg>
                      <p v-if="!keyFile" class="text-sm text-gray-500 mt-2">Click to upload private key</p>
                      <p v-else class="text-sm text-[#007C52] font-medium mt-2">{{ keyFile.name }}</p>
                    </div>
                    <input
                      type="file"
                      ref="keyFileInput"
                      class="hidden"
                      accept=".key"
                      @change="handleKeyFileChange"
                    >
                  </label>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">Notes</label>
              <textarea
                v-model="currentCert.notes"
                rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007C52] focus:border-transparent"
                placeholder="Any additional notes about this certificate"
              ></textarea>
            </div>

            <div class="flex items-center space-x-4">
              <div class="flex items-center">
                <input
                  type="checkbox"
                  id="is_self_signed"
                  v-model="currentCert.is_self_signed"
                  class="w-4 h-4 text-[#007C52] bg-gray-100 border-gray-300 rounded focus:ring-[#007C52] focus:ring-2"
                >
                <label for="is_self_signed" class="ml-2 text-sm font-medium text-gray-700">Self-signed certificate</label>
              </div>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
            <button
              @click="closeModal"
              class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="saveCertificate"
              :disabled="saving"
              class="px-6 py-3 bg-[#007C52] hover:bg-[#006044] text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
            >
              <span v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
              <span>{{ saving ? 'Saving...' : (modalMode === 'add' ? 'Add Certificate' : 'Update Certificate') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 backdrop-blur-sm bg-black bg-opacity-30 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900">Delete Certificate</h3>
              <p class="text-sm text-gray-500">This action cannot be undone.</p>
            </div>
          </div>
          <p class="text-gray-700 mb-6">Are you sure you want to delete this certificate? This will remove the certificate from the system permanently.</p>
          <div class="flex justify-end space-x-4">
            <button
              @click="cancelDelete"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="deleteCertificate"
              :disabled="deleting"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
            >
              <span v-if="deleting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
              <span>{{ deleting ? 'Deleting...' : 'Delete' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'

type Certificate = {
  cert_id: number
  cert_name: string
  subject?: string
  issuer?: string
  valid_from?: string
  valid_to?: string
  notes?: string
  is_self_signed?: boolean
}

const nginxStore = useNginxStore()

// Modal state
const showCertificateModal = ref(false)
const showDeleteModal = ref(false)
const modalMode = ref<'add' | 'edit'>('add')
const saving = ref(false)
const deleting = ref(false)
const certificateToDelete = ref<number | null>(null)

// Form data
const currentCert = reactive<{
  cert_id: number | null
  cert_name: string
  notes: string
  is_self_signed: boolean
}>({
  cert_id: null,
  cert_name: '',
  notes: '',
  is_self_signed: false
})

// File uploads
const certFile = ref<File | null>(null)
const keyFile = ref<File | null>(null)
const certFileInput = ref<HTMLInputElement | null>(null)
const keyFileInput = ref<HTMLInputElement | null>(null)

// Load certificates on mount
onMounted(async () => {
  try {
    await nginxStore.fetchCertificates()
  } catch (error) {
    console.error('Failed to load certificates:', error)
  }
})

// Helper functions
const formatDate = (dateString: string | undefined | null): string => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return String(dateString)
  }
}

const isExpiringSoon = (validTo: string | undefined | null): boolean => {
  if (!validTo) return false
  const expiryDate = new Date(validTo)
  const thirtyDaysFromNow = new Date()
  thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30)
  return expiryDate <= thirtyDaysFromNow && expiryDate >= new Date()
}

const getStatusClass = (validTo: string | undefined | null): string => {
  if (!validTo) return 'bg-gray-100 text-gray-800'
  const expiryDate = new Date(validTo)
  const now = new Date()

  if (expiryDate < now) {
    return 'bg-red-100 text-red-800'
  } else if (isExpiringSoon(validTo)) {
    return 'bg-yellow-100 text-yellow-800'
  } else {
    return 'bg-green-100 text-green-800'
  }
}

const getStatusText = (validTo: string | undefined | null): string => {
  if (!validTo) return 'Unknown'
  const expiryDate = new Date(validTo)
  const now = new Date()

  if (expiryDate < now) {
    return 'Expired'
  } else if (isExpiringSoon(validTo)) {
    return 'Expiring Soon'
  } else {
    return 'Valid'
  }
}

// Modal functions
const openAddModal = () => {
  modalMode.value = 'add'
  resetForm()
  showCertificateModal.value = true
}

const openEditModal = (cert: any) => {
  modalMode.value = 'edit'
  currentCert.cert_id = cert.cert_id
  currentCert.cert_name = cert.cert_name || ''
  currentCert.notes = cert.notes || ''
  currentCert.is_self_signed = cert.is_self_signed || false
  showCertificateModal.value = true
}

const closeModal = () => {
  showCertificateModal.value = false
  resetForm()
}

const resetForm = () => {
  currentCert.cert_id = null
  currentCert.cert_name = ''
  currentCert.notes = ''
  currentCert.is_self_signed = false
  certFile.value = null
  keyFile.value = null
  if (certFileInput.value) certFileInput.value.value = ''
  if (keyFileInput.value) keyFileInput.value.value = ''
}

// File handling
const handleCertFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files && target.files[0]
  if (file) {
    certFile.value = file
  }
}

const handleKeyFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files && target.files[0]
  if (file) {
    keyFile.value = file
  }
}

// Save certificate
const saveCertificate = async () => {
  if (!currentCert.cert_name.trim()) {
    alert('Certificate name is required')
    return
  }

  if (modalMode.value === 'add' && !certFile.value) {
    alert('Certificate file is required')
    return
  }

  saving.value = true

  try {
    const formData = new FormData()
    formData.append('cert_name', currentCert.cert_name)
    formData.append('notes', currentCert.notes || '')
    formData.append('is_self_signed', currentCert.is_self_signed ? '1' : '0')

    if (certFile.value) {
      formData.append('certificate', certFile.value)
    }

    if (keyFile.value) {
      formData.append('private_key', keyFile.value)
    }

    if (modalMode.value === 'add') {
      await nginxStore.addCertificate(formData)
    } else {
      await nginxStore.updateCertificate(currentCert.cert_id, formData)
    }

    closeModal()
  } catch (error: any) {
    console.error('Failed to save certificate:', error)
    alert('Failed to save certificate: ' + (error?.message ?? error))
  } finally {
    saving.value = false
  }
}

// Delete functions
const confirmDelete = (certId: number) => {
  certificateToDelete.value = certId
  showDeleteModal.value = true
}

const cancelDelete = () => {
  showDeleteModal.value = false
  certificateToDelete.value = null
}

const deleteCertificate = async () => {
  if (!certificateToDelete.value) return

  deleting.value = true

  try {
    await nginxStore.deleteCertificate(certificateToDelete.value)
    showDeleteModal.value = false
    certificateToDelete.value = null
  } catch (error: any) {
    console.error('Failed to delete certificate:', error)
    alert('Failed to delete certificate: ' + (error?.message ?? error))
  } finally {
    deleting.value = false
  }
}
</script>
