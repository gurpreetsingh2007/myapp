<template>
  <div class="h-full bg-gradient-to-br from-slate-50 via-blue-50 to-emerald-50 flex flex-col overflow-hidden">
    <AddCertificateModal v-if="showCertificateModal" @close="showCertificateModal = false" />

    <div class="w-full mx-auto px-4 py-2 flex-1 flex flex-col min-h-0">
      <!-- Action Bar -->
      <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow border border-white/20 p-4 mb-6 flex-shrink-0">
        <div class="flex flex-col sm:flex-row gap-3 items-stretch">
          <!-- Action Buttons -->
          <div class="flex flex-wrap gap-2 flex-1">
            <button @click="addCertificate"
              class="flex items-center px-4 py-2 bg-gradient-to-r from-[#007C52] to-[#009966] text-white font-medium rounded-lg hover:shadow-md transition-all hover:from-[#009966] hover:to-[#007C52]">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Add Certificate
            </button>
          </div>

          <!-- Search Bar -->
          <div class="flex flex-col sm:flex-row gap-2 flex-1 sm:max-w-md justify-end">
            <div class="relative flex-1">
              <div class="absolute inset-y-0 right-5 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input v-model="searchQuery" type="text" placeholder="Search certificates..."
                class="w-full pl-5 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#007C52]/30 bg-white/90 transition-all" />
            </div>

            <button @click="refreshCertificates"
              class="flex items-center justify-center px-4 py-2 bg-white/90 text-gray-700 font-medium rounded-lg shadow border border-gray-200 hover:border-[#007C52]/50 transition-all hover:bg-[#007C52]/5">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="nginxStore.certificatesLoading" class="flex-1 flex items-center justify-center">
        <div class="text-center space-y-4">
          <div class="inline-flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-[#007C52]/10 border-t-[#007C52]"></div>
          </div>
          <p class="text-gray-600">Loading certificates...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="nginxStore.certificatesError" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6 flex-shrink-0">
        <div class="flex items-start">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 0v6m0-6h6m-6 0H6" />
          </svg>
          <div>
            <p class="text-red-700 font-medium">Error loading certificates</p>
            <p class="text-red-600 text-sm mt-1">{{ nginxStore.certificatesError }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <div v-else class="flex-1 flex flex-col min-h-0">
        <!-- No Data State -->
        <div v-if="filteredCertificates.length === 0"
          class="flex-1 flex flex-col items-center justify-center text-center space-y-4 p-8">
          <div
            class="w-16 h-16 bg-gradient-to-r from-[#007C52]/10 to-[#009966]/10 rounded-full flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <p class="text-gray-600 font-medium">No certificates found</p>
          <button @click="addCertificate"
            class="flex items-center px-6 py-2 bg-gradient-to-r from-[#007C52] to-[#009966] text-white font-medium rounded-lg hover:shadow-md transition-all hover:from-[#009966] hover:to-[#007C52]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Your First Certificate
          </button>
        </div>

        <!-- Certificate Table Container -->
        <div v-else
          class="flex-1 bg-white/80 backdrop-blur-sm rounded-xl shadow border border-white/20 overflow-hidden flex flex-col min-h-0">
          <!-- Table Wrapper with proper scrolling -->
          <div class="flex-1 overflow-auto">
            <table class="w-full table-fixed">
              <thead class="bg-white/95 backdrop-blur-sm sticky top-0 z-10 border-b border-gray-200">
                <tr>
                  <th class="w-64 px-4 py-3 text-left text-md font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                  <th class="w-64 px-4 py-3 text-left text-md font-medium text-gray-500 uppercase tracking-wider">Issuer</th>
                  <th class="w-48 px-4 py-3 text-left text-md font-medium text-gray-500 uppercase tracking-wider">Valid From</th>
                  <th class="w-48 px-4 py-3 text-left text-md font-medium text-gray-500 uppercase tracking-wider">Valid To</th>
                  <th class="w-32 px-4 py-3 text-left text-md font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="w-32 px-4 py-3 text-right text-md font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white/50">
                <tr v-for="cert in filteredCertificates" :key="cert.cert_id"
                  :class="getRowColorClass(cert.valid_to)"
                  class="transition-colors duration-150">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <div
                        class="flex-shrink-0 h-6 w-6 bg-gradient-to-r from-[#007C52]/10 to-[#009966]/10 rounded-sm flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="text-md font-medium text-gray-900 truncate" :title="cert.cert_name">
                          {{ cert.cert_name }}
                        </div>
                        <div class="text-sm text-gray-500 truncate" :title="cert.subject || 'No subject information'">
                          {{ cert.subject || 'No subject information' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-md text-gray-700 truncate" :title="cert.issuer || 'Unknown'">
                      {{ cert.issuer || 'Unknown' }}
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-md text-gray-700">
                      {{ formatDate(cert.valid_from) }}
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-md" :class="{'text-yellow-600': expiresInTwoMonths(cert.valid_to), 'text-red-600': isExpired(cert.valid_to)}">
                      {{ formatDate(cert.valid_to) }}
                      <span v-if="expiresInTwoMonths(cert.valid_to)" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">Expires Soon</span>
                      <span v-if="isExpired(cert.valid_to)" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">Expired</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(cert.valid_to)">
                      {{ getStatusText(cert.valid_to) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex justify-end space-x-2">
                      <router-link
                        :to="`/certificates/edit/${cert.cert_id}`"
                        class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-[#007C52] to-[#009966] hover:from-[#009966] hover:to-[#007C52] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#007C52]/50 transition-all"
                      >
                        Edit
                      </router-link>
                      <button @click="confirmDelete(cert.cert_id)" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500/50 transition-all">
                        Delete
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
import { ref, onMounted, computed } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'
import { useRouter } from 'vue-router'
import AddCertificateModal from '@/components/services/nginx/AddCertificateModal.vue';

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
const router = useRouter()
const showCertificateModal = ref(false)
const showDeleteModal = ref(false)
const deleting = ref(false)
const certificateToDelete = ref<number | null>(null)
const searchQuery = ref('')

// Filter certificates based on search query
const filteredCertificates = computed(() => {
  if (!searchQuery.value) return nginxStore.certificates

  const query = searchQuery.value.toLowerCase().trim()
  return nginxStore.certificates.filter(cert =>
    cert.cert_name.toLowerCase().includes(query) ||
    cert.algorithm.toLowerCase().includes(query) ||
    (cert.valid_from && cert.valid_from.toLowerCase().includes(query)) ||
    (cert.valid_to && cert.valid_to.toLowerCase().includes(query)) ||
    (cert.subject && cert.subject.toLowerCase().includes(query)) ||
    (cert.issuer && cert.issuer.toLowerCase().includes(query)))
})

const addCertificate = () => {
  showCertificateModal.value = true
}

// Refresh certificates
const refreshCertificates = async () => {
  try {
    await nginxStore.fetchCertificates()
  } catch (error) {
    console.error('Failed to refresh certificates:', error)
  }
}

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

const isExpired = (validTo: string | undefined | null): boolean => {
  if (!validTo) return false
  const expiryDate = new Date(validTo)
  return expiryDate < new Date()
}

const expiresInTwoMonths = (validTo: string | undefined | null): boolean => {
  if (!validTo) return false

  const expiryDate = new Date(validTo)
  const twoMonthsFromNow = new Date()
  twoMonthsFromNow.setMonth(twoMonthsFromNow.getMonth() + 2)

  return expiryDate <= twoMonthsFromNow && expiryDate >= new Date()
}

// Restituisce le classi CSS per la colorazione della riga
const getRowColorClass = (validTo: string | undefined | null): string => {
  if (!validTo) return 'hover:bg-gray-50';

  if (isExpired(validTo)) {
    return 'bg-red-50 hover:bg-red-100';
  } else if (expiresInTwoMonths(validTo)) {
    return 'bg-yellow-50 hover:bg-yellow-100';
  }
  return 'hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-emerald-50/50';
};

const getStatusClass = (validTo: string | undefined | null): string => {
  if (!validTo) return 'bg-gray-100 text-gray-800'

  if (isExpired(validTo)) {
    return 'bg-red-100 text-red-800'
  } else if (expiresInTwoMonths(validTo)) {
    return 'bg-yellow-100 text-yellow-800'
  }
  return 'bg-green-100 text-green-800'
}

const getStatusText = (validTo: string | undefined | null): string => {
  if (!validTo) return 'Unknown'

  if (isExpired(validTo)) {
    return 'Expired'
  } else if (expiresInTwoMonths(validTo)) {
    return 'Expires Soon'
  }
  return 'Valid'
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
    await nginxStore.fetchCertificates()
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
