<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <header class="flex flex-col md:flex-row justify-between items-center mb-8 md:mb-12">
        <div class="mb-4 md:mb-0">
          <h1 class="text-3xl md:text-4xl font-bold text-primary">
            <i class="fas fa-lock mr-2"></i>SSL Certificate Manager
          </h1>
          <p class="text-gray-600 mt-1">Manage your Nginx certificates with ease</p>
        </div>
        <div class="flex items-center space-x-4">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search certificates..."
              class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>
          <button
            @click="openSettings"
            class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-all flex items-center"
          >
            <i class="fas fa-cog mr-2"></i> Settings
          </button>
        </div>
      </header>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <StatsCard
          title="Total Certificates"
          :value="certificates.length"
          icon="certificate"
          color="primary"
          :trend="12"
        />
        <StatsCard
          title="Wildcard Certificates"
          :value="wildcardCount"
          icon="globe"
          color="secondary"
          :trend="5"
        />
        <StatsCard
          title="Expiring Soon"
          :value="expiringSoonCount"
          icon="exclamation-triangle"
          color="accent"
          :trend="-2"
          trend-negative
        />
        <StatsCard
          title="Last Updated"
          value="Just now"
          icon="history"
          color="purple"
        />
      </div>

      <!-- Main Content -->
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Upload Section -->
        <div class="lg:w-2/3">
          <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-xl font-bold text-primary">
                <i class="fas fa-cloud-upload-alt mr-2"></i>Upload Certificate
              </h2>
              <button
                @click="showCertificateForm = true"
                class="text-sm bg-secondary hover:bg-secondary/90 text-white px-4 py-2 rounded-lg transition-all flex items-center"
              >
                <i class="fas fa-plus mr-2"></i> New Certificate
              </button>
            </div>

            <!-- Certificate Form Modal -->
            <CertificateFormModal
              v-if="showCertificateForm"
              v-model:show="showCertificateForm"
              @submit="handleCertificateSubmit"
            />

            <!-- Upload Section -->
            <div
              v-if="currentCertificate"
              @dragover.prevent="dragOver = true"
              @dragleave="dragOver = false"
              @drop.prevent="handleDrop"
              :class="{
                'border-secondary bg-secondary/10 border-4': dragOver,
                'border-gray-300': !dragOver,
                'drag-active': dragOver
              }"
              class="border-2 border-dashed rounded-xl p-8 mb-6 transition-all duration-300 ease-in-out relative"
            >
              <div class="text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="fas fa-file-upload text-primary text-2xl"></i>
                </div>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Upload Certificate Files</h3>
                <p class="mt-2 text-sm text-gray-600">
                  <span class="font-medium text-primary">{{ currentCertificate.name }}</span>
                  <span
                    v-if="currentCertificate.wildcard"
                    class="ml-2 bg-secondary/20 text-secondary text-xs px-2 py-1 rounded-full"
                  >
                    Wildcard
                  </span>
                </p>
                <p class="mt-1 text-sm text-gray-600">
                  Drag and drop your certificate files here or
                  <span class="text-primary font-medium cursor-pointer" @click="triggerFileInput">
                    browse files
                  </span>
                </p>
                <input
                  type="file"
                  ref="fileInput"
                  class="hidden"
                  multiple
                  @change="handleFileSelect"
                  accept=".crt,.key,.pem,.cer,.pfx"
                />
              </div>

              <!-- Selected Files Preview -->
              <div v-if="selectedFiles.length > 0" class="mt-6 space-y-3">
                <div
                  v-for="(file, index) in selectedFiles"
                  :key="file.name + index"
                  class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200 animate-fade-in"
                >
                  <div class="flex items-center space-x-3">
                    <i
                      :class="{
                        'fa-file-certificate text-primary': file.name.endsWith('.crt') || file.name.endsWith('.cer'),
                        'fa-key text-yellow-500': file.name.endsWith('.key'),
                        'fa-file-alt text-gray-500': file.name.endsWith('.pem'),
                        'fa-file-archive text-purple-500': file.name.endsWith('.pfx')
                      }"
                      class="fas"
                    ></i>
                    <div>
                      <span class="text-sm font-medium">{{ file.name }}</span>
                      <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                    </div>
                  </div>
                  <button
                    @click="removeFile(index)"
                    class="text-gray-400 hover:text-red-500 transition-colors"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                  <button
                    @click="cancelUpload"
                    class="py-2 px-4 rounded-md text-gray-700 font-medium transition-all duration-200 bg-gray-100 hover:bg-gray-200"
                  >
                    Cancel
                  </button>
                  <button
                    @click="uploadFiles"
                    :disabled="uploading"
                    :class="{
                      'bg-primary hover:bg-primary/90': !uploading,
                      'bg-primary/70 cursor-not-allowed': uploading
                    }"
                    class="py-2 px-4 rounded-md text-white font-medium transition-all duration-200 flex items-center justify-center"
                  >
                    <span v-if="!uploading">Upload Certificate</span>
                    <span v-else class="flex items-center">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Uploading...
                    </span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="!currentCertificate" class="text-center py-12">
              <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-plus text-primary text-3xl"></i>
              </div>
              <h3 class="text-lg font-medium text-gray-900">No certificate selected</h3>
              <p class="mt-1 text-sm text-gray-600">Get started by creating a new certificate</p>
              <button
                @click="showCertificateForm = true"
                class="mt-4 bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-all"
              >
                Create Certificate
              </button>
            </div>
          </div>

          <!-- Certificate List -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4 flex flex-col md:flex-row items-start md:items-center justify-between">
              <h2 class="text-lg font-medium text-gray-900 mb-2 md:mb-0">
                <i class="fas fa-list mr-2 text-primary"></i>Managed Certificates
              </h2>
              <div class="flex space-x-3">
                <div class="relative">
                  <select
                    v-model="filter"
                    class="appearance-none bg-white pr-8 py-2 pl-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                  >
                    <option value="all">All Certificates</option>
                    <option value="expiring">Expiring Soon</option>
                    <option value="expired">Expired</option>
                    <option value="valid">Valid</option>
                    <option value="wildcard">Wildcard</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i class="fas fa-chevron-down text-sm"></i>
                  </div>
                </div>
                <button
                  @click="refreshCertificates"
                  class="bg-primary/10 text-primary px-3 rounded-md hover:bg-primary/20 transition-colors"
                >
                  <i class="fas fa-sync-alt" :class="{ 'fa-spin': refreshing }"></i>
                </button>
              </div>
            </div>

            <div v-if="loading" class="p-8 flex justify-center">
              <i class="fas fa-spinner fa-spin text-3xl text-primary"></i>
            </div>

            <ul v-else class="divide-y divide-gray-200">
              <transition-group name="fade">
                <li
                  v-for="cert in filteredCertificates"
                  :key="cert.id"
                  class="cert-card px-6 py-4 hover:bg-gray-50 transition-all duration-300"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <div
                        :class="{
                          'bg-green-100 text-green-800': cert.status === 'valid',
                          'bg-yellow-100 text-yellow-800': cert.status === 'expiring',
                          'bg-red-100 text-red-800': cert.status === 'expired'
                        }"
                        class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0"
                      >
                        <i class="fas fa-lock text-lg"></i>
                      </div>
                      <div>
                        <h3 class="font-bold text-gray-900">{{ cert.name }}</h3>
                        <p class="text-sm text-gray-500 flex items-center mt-1">
                          <i class="fas fa-globe mr-1 text-secondary"></i>
                          {{ cert.domain }}
                          <span
                            v-if="cert.wildcard"
                            class="ml-2 bg-secondary/20 text-secondary text-xs px-2 py-0.5 rounded-full status-badge"
                          >
                            Wildcard
                          </span>
                        </p>
                      </div>
                    </div>

                    <div class="flex items-center space-x-4">
                      <span
                        :class="{
                          'bg-green-100 text-green-800': cert.status === 'valid',
                          'bg-yellow-100 text-yellow-800': cert.status === 'expiring',
                          'bg-red-100 text-red-800': cert.status === 'expired'
                        }"
                        class="px-3 py-1 rounded-full text-xs font-medium status-badge"
                      >
                        <i
                          :class="{
                            'fa-check-circle text-green-500': cert.status === 'valid',
                            'fa-exclamation-circle text-yellow-500': cert.status === 'expiring',
                            'fa-times-circle text-red-500': cert.status === 'expired'
                          }"
                          class="fas mr-1"
                        ></i>
                        {{ cert.status === 'valid' ? 'Valid' : cert.status === 'expiring' ? 'Expiring Soon' : 'Expired' }}
                      </span>

                      <div class="relative group">
                        <button class="text-gray-400 hover:text-primary p-1 rounded-full">
                          <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-150 origin-top-right border border-gray-200">
                          <a
                            href="#"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click.prevent="renewCertificate(cert.id)"
                          >
                            <i class="fas fa-redo mr-2 text-green-500"></i> Renew
                          </a>
                          <a
                            href="#"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click.prevent="downloadCertificate(cert.id)"
                          >
                            <i class="fas fa-download mr-2 text-blue-500"></i> Download
                          </a>
                          <a
                            href="#"
                            class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                            @click.prevent="deleteCertificate(cert.id)"
                          >
                            <i class="fas fa-trash mr-2"></i> Delete
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 bg-gray-50 p-4 rounded-lg grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <p class="text-xs text-gray-500 uppercase tracking-wider">Expiration</p>
                      <p class="font-medium flex items-center">
                        <i class="fas fa-calendar-day mr-2 text-primary"></i>
                        {{ formatDate(cert.expires) }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 uppercase tracking-wider">Issuer</p>
                      <p class="font-medium flex items-center">
                        <i class="fas fa-building mr-2 text-primary"></i>
                        {{ cert.details.issuer }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 uppercase tracking-wider">Days Left</p>
                      <p
                        :class="{
                          'text-green-600': cert.status === 'valid',
                          'text-yellow-600': cert.status === 'expiring',
                          'text-red-600': cert.status === 'expired'
                        }"
                        class="font-medium flex items-center"
                      >
                        <i class="fas fa-clock mr-2"></i>
                        {{ cert.details.days_remaining }} days
                      </p>
                    </div>
                  </div>
                </li>
              </transition-group>
            </ul>

            <div v-if="!loading && filteredCertificates.length === 0" class="p-8 text-center">
              <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-inbox text-gray-400 text-2xl"></i>
              </div>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No certificates found</h3>
              <p class="mt-1 text-sm text-gray-500">Create your first certificate to get started</p>
              <button
                @click="showCertificateForm = true"
                class="mt-4 bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-all"
              >
                Create Certificate
              </button>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:w-1/3">
          <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-bold text-primary mb-4">
              <i class="fas fa-info-circle mr-2"></i>Certificate Information
            </h2>
            <div class="space-y-4">
              <div>
                <h3 class="text-sm font-medium text-gray-900 mb-1">About SSL Certificates</h3>
                <p class="text-sm text-gray-600">
                  SSL certificates encrypt data between your server and visitors' browsers.
                  Wildcard certificates secure a domain and all its subdomains.
                </p>
              </div>
              <div class="border-t border-gray-200 pt-4">
                <h3 class="text-sm font-medium text-gray-900 mb-1">Best Practices</h3>
                <ul class="text-sm text-gray-600 space-y-2">
                  <li class="flex items-start">
                    <i class="fas fa-check-circle text-secondary mt-1 mr-2"></i>
                    <span>Renew certificates at least 30 days before expiration</span>
                  </li>
                  <li class="flex items-start">
                    <i class="fas fa-check-circle text-secondary mt-1 mr-2"></i>
                    <span>Use wildcard certificates for multiple subdomains</span>
                  </li>
                  <li class="flex items-start">
                    <i class="fas fa-check-circle text-secondary mt-1 mr-2"></i>
                    <span>Store private keys securely and never share them</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-r from-primary to-secondary rounded-xl shadow-sm p-6 text-white">
            <h2 class="text-lg text-black font-bold mb-4">Certificate Status</h2>
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-black opacity-80">Expiring Certificates</p>
                  <p class="text-xl text-black font-bold">{{ expiringSoonCount }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
              </div>
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-black opacity-80">Total Wildcards</p>
                  <p class="text-xl text-black font-bold">{{ wildcardCount }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <i class="fas fa-globe"></i>
                </div>
              </div>
              <button
                @click="showReports"
                class="mt-4 w-full bg-white text-black text-primary py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors"
              >
                View Reports
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <footer class="mt-12 pt-6 border-t border-gray-200 text-center text-gray-600 text-sm">
        <p>SSL Certificate Manager v2.0 • Securely manage your Nginx certificates</p>
        <p class="mt-1">© {{ new Date().getFullYear() }} Your Company. All rights reserved.</p>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Ref } from 'vue'
import { API } from '@/config/index'



interface Certificate {
  id: string | number
  name: string
  domain: string
  wildcard: boolean
  expires: string
  status: 'valid' | 'expiring' | 'expired'
  details: {
    issuer: string
    algorithm: string
    days_remaining: number
  }
}

interface CertificateFormData {
  name: string
  wildcard: boolean
  domain?: string
}

// Refs
const dragOver: Ref<boolean> = ref(false)
const selectedFiles: Ref<File[]> = ref([])
const uploading: Ref<boolean> = ref(false)
const filter: Ref<string> = ref('all')
const loading: Ref<boolean> = ref(false)
const refreshing: Ref<boolean> = ref(false)
const showCertificateForm: Ref<boolean> = ref(false)
const currentCertificate: Ref<Certificate | null> = ref(null)
const searchQuery: Ref<string> = ref('')
const fileInput: Ref<HTMLInputElement | null> = ref(null)



async function fetchCertificates() {
  loading.value = true
  try {
    const response = await fetch(`${API}/certificates`)
    if (!response.ok) throw new Error('Failed to fetch certificates')

    const data = await response.json()
    certificates.value = data.map((cert: any) => ({
      ...cert,
      name: cert.filename.replace(/\.[^/.]+$/, ""), // Remove file extension for display name
      domain: extractDomainFromCertName(cert.filename),
      wildcard: cert.filename.includes('*'),
      status: getCertificateStatus(cert.expires),
      details: {
        issuer: 'Unknown', // Your PHP endpoint doesn't currently provide this
        algorithm: 'Unknown', // Your PHP endpoint doesn't currently provide this
        days_remaining: getDaysRemaining(cert.expires)
      }
    }))
  } catch (error) {
    console.error('Error fetching certificates:', error)
  } finally {
    loading.value = false
  }
}
function extractDomainFromCertName(filename: string): string {
  // Extract domain from filename - adjust based on your naming convention
  const base = filename.replace(/\.[^/.]+$/, "")
  return base.includes('*') ? base : `${base}.com` // Example transformation
}
function getDaysRemaining(expiryDate: string): number {
  const now = new Date()
  const expiry = new Date(expiryDate)
  return Math.floor((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
}


// Sample certificate data
const certificates: Ref<Certificate[]> = ref([
  
])

// Computed properties
const wildcardCount = computed(() => {
  return certificates.value.filter(cert => cert.wildcard).length
})

const expiringSoonCount = computed(() => {
  return certificates.value.filter(cert => cert.status === 'expiring').length
})

const filteredCertificates = computed(() => {
  let filtered = certificates.value

  // Apply status filter
  if (filter.value !== 'all') {
    if (filter.value === 'wildcard') {
      filtered = filtered.filter(cert => cert.wildcard)
    } else {
      filtered = filtered.filter(cert => cert.status === filter.value)
    }
  }

  // Apply search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(cert =>
      cert.name.toLowerCase().includes(query) ||
      cert.domain.toLowerCase().includes(query)
    )
  }

  return filtered
})

// Methods
function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files) {
    selectedFiles.value = Array.from(target.files)
    dragOver.value = false
  }
}

function handleDrop(e: DragEvent) {
  if (e.dataTransfer?.files) {
    selectedFiles.value = Array.from(e.dataTransfer.files)
    dragOver.value = false
  }
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1)
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i])
}

function formatDate(dateString: string): string {
  const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }
  return new Date(dateString).toLocaleDateString(undefined, options)
}

function getCertificateStatus(expiryDate: string): 'valid' | 'expiring' | 'expired' {
  const now = new Date()
  const expiry = new Date(expiryDate)
  const daysUntilExpiry = Math.floor((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

  if (daysUntilExpiry < 0) return 'expired'
  if (daysUntilExpiry < 30) return 'expiring'
  return 'valid'
}

async function handleCertificateSubmit(formData: CertificateFormData) {
  showCertificateForm.value = false

  const newCertificate: Certificate = {
    id: Date.now().toString(),
    name: formData.name,
    domain: formData.wildcard ? '*.example.com' : 'example.com',
    wildcard: formData.wildcard,
    expires: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    status: 'valid',
    details: {
      issuer: 'Let\'s Encrypt',
      algorithm: 'RSA 2048',
      days_remaining: 365
    }
  }

  currentCertificate.value = newCertificate

  // Optionally store this metadata to your backend
  try {
    await storeCertificateData({
      filename: `${formData.name}.crt`, // Or whatever naming convention you use
      content: '' // You might store metadata here or leave empty for file upload
    })
  } catch (error) {
    console.error('Could not store certificate metadata', error)
  }
}

function cancelUpload() {
  currentCertificate.value = null
  selectedFiles.value = []
}
async function uploadFiles() {
  if (selectedFiles.value.length === 0 || uploading.value || !currentCertificate.value) return

  uploading.value = true

  try {
    const formData = new FormData()

    // Add all selected files
    selectedFiles.value.forEach(file => {
      formData.append('files[]', file)
    })

    const response = await fetch(`${API.BASE_URL}${API.ENDPOINTS.UPLOAD}`, {
      method: 'POST',
      body: formData
    })

    if (!response.ok) throw new Error('Upload failed')

    const result = await response.json()
    console.log('Upload successful:', result)

    // Clear the form
    currentCertificate.value = null
    selectedFiles.value = []

    // Refresh the certificate list
    await fetchCertificates()
  } catch (error) {
    console.error('Error uploading files:', error)
    alert('Failed to upload certificates')
  } finally {
    uploading.value = false
  }
}

async function deleteCertificate(id: string) {
  if (!confirm('Are you sure you want to delete this certificate?')) return

  try {
    const response = await fetch(`${API}/certificates/${id}`, {
      method: 'DELETE'
    })

    if (!response.ok) throw new Error('Deletion failed')

    const result = await response.json()
    console.log('Deletion successful:', result)

    // Refresh the certificate list
    await fetchCertificates()
  } catch (error) {
    console.error('Error deleting certificate:', error)
    alert('Failed to delete certificate')
  }
}
async function storeCertificateData(certData: any) {
  try {
    const response = await fetch(`${API}/certificates`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        certificates: [certData]
      })
    })

    if (!response.ok) throw new Error('Storage failed')

    const result = await response.json()
    console.log('Storage successful:', result)
    return result
  } catch (error) {
    console.error('Error storing certificate data:', error)
    throw error
  }
}

async function renewCertificate(id: string | number) {
  const cert = certificates.value.find(c => c.id === id)
  if (!cert) return

  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))

    // Update the certificate
    cert.expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    cert.status = 'valid'
    cert.details.days_remaining = 365
  } catch (error) {
    console.error('Error renewing certificate:', error)
  }
}

async function downloadCertificate(id: string | number) {
  const cert = certificates.value.find(c => c.id === id)
  if (!cert) return

  try {
    // Simulate download
    console.log('Downloading certificate:', cert.name)
    await new Promise(resolve => setTimeout(resolve, 500))
    alert(`Certificate ${cert.name} download started`)
  } catch (error) {
    console.error('Error downloading certificate:', error)
  }
}

async function refreshCertificates() {
  refreshing.value = true
  try {
    // Simulate API refresh
    await new Promise(resolve => setTimeout(resolve, 800))
  } finally {
    refreshing.value = false
  }
}

function openSettings() {
  alert('Settings panel would open here')
}

function showReports() {
  alert('Certificate reports would be displayed here')
}

// Component lifecycle
onMounted(() => {
  // Initialize with loading state
  loading.value = true
  setTimeout(() => {
    loading.value = false
  }, 800)
})
onMounted(() => {
  fetchCertificates()
})
</script>

<style>
/* Animation classes */
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

.drag-active {
  animation: pulse-slow 2s infinite;
  box-shadow: 0 0 0 10px rgba(0, 124, 82, 0.1);
}

/* Keyframes */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse-slow {
  0%, 100% { box-shadow: 0 0 0 0 rgba(0, 124, 82, 0.1); }
  50% { box-shadow: 0 0 0 10px rgba(0, 124, 82, 0.1); }
}

/* Transition group */
.fade-move,
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-leave-active {
  position: absolute;
}
</style>
