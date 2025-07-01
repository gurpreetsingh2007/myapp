<template>
  <div class="h-full w-full p-4 sm:p-6 md:p-8 bg-gray-50">
    <div class=" mx-auto">
      <!-- Header Section -->
      <div class="mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <p class="text-gray-600">Track all changes to your Nginx configurations</p>
          <div class="relative w-full md:w-64">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              @input="handleSearchInput"
              type="text"
              placeholder="Search history..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>
        </div>
        <div class="border-b border-gray-200 mt-6"></div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && !history.length" class="flex justify-center py-16">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!history.length && !loading" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No history records found</h3>
        <p class="mt-1 text-sm text-gray-500">All configuration changes will appear here once made.</p>
      </div>

      <!-- History List -->
      <div v-else class="space-y-6">
        <div
          v-for="entry in history"
          :key="entry.history_id"
          class="bg-white shadow overflow-hidden rounded-lg"
        >
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                {{ entry.action_type }} on {{ entry.table_name }} (ID: {{ entry.record_id }})
              </h3>
              <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Changed by {{ entry.changed_by }} on {{ formatDateTime(entry.changed_at) }}
              </p>
            </div>
            <button
              @click="toggleDetails(entry.history_id)"
              class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              {{ expandedEntries.includes(entry.history_id) ? 'Hide' : 'Show' }} Details
              <svg
                class="-mr-0.5 ml-2 h-4 w-4"
                :class="{ 'transform rotate-180': expandedEntries.includes(entry.history_id) }"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>

          <div v-if="expandedEntries.includes(entry.history_id)" class="border-t border-gray-200">
            <dl>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Change Reason</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ entry.change_reason || 'No reason provided' }}
                </dd>
              </div>

              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Old Configuration</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <pre v-if="entry.old_data" class="bg-gray-100 p-3 rounded overflow-x-auto text-xs">{{ JSON.stringify(entry.old_data, null, 2) }}</pre>
                  <span v-else class="text-gray-400">No old data</span>
                </dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">New Configuration</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <pre v-if="entry.new_data" class="bg-gray-100 p-3 rounded overflow-x-auto text-xs">{{ JSON.stringify(entry.new_data, null, 2) }}</pre>
                  <span v-else class="text-gray-400">No new data</span>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Loading More Indicator -->
        <div v-if="loadingMore" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
        </div>

        <!-- Sentinel for infinite scroll -->
        <div ref="sentinel" class="h-1"></div>

        <!-- End of History -->
        <div v-if="endOfHistory && !loadingMore" class="text-center py-8 text-sm text-gray-500">
          End of history records
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { API } from '@/config/index'

// History data and state
const history = ref<any[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const page = ref(0)
const pageSize = ref(10)
const endOfHistory = ref(false)
const expandedEntries = ref<string[]>([])
const sentinel = ref<HTMLElement | null>(null)
const searchQuery = ref('')
const isSearchActive = ref(false)
const debounceTimer = ref<number | null>(null)

// Initialize observer for infinite scroll
let observer: IntersectionObserver | null = null

// Handle search input with debounce
const handleSearchInput = () => {
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }

  debounceTimer.value = setTimeout(() => {
    performSearch()
  }, 300) as unknown as number
}

// Perform search when user types
const performSearch = async () => {
  isSearchActive.value = searchQuery.value.trim() !== ''

  if (isSearchActive.value) {
    // Reset pagination and history state for search
    page.value = 0
    history.value = []
    endOfHistory.value = true // Disable infinite scroll during search
    loading.value = true

    try {
      const response = await fetch(
        `${API}/nginx/history?q=${encodeURIComponent(searchQuery.value.trim())}&offset=0&limit=${pageSize.value}`
      )

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

      const json = await response.json()

      // Handle different response structures
      if (Array.isArray(json)) {
        // Direct array response
        history.value = json
      } else if (json && Array.isArray(json.data)) {
        // Response with data property
        history.value = json.data
      } else if (json && json.success && Array.isArray(json.data.results)) {
        // Success-based response with results array
        history.value = json.data.results
      } else if (json && json.success && Array.isArray(json.data)) {
        // Success-based response
        history.value = json.data
      } else {
        history.value = []
      }
    } catch (err) {
      console.error('Search error:', err)
      history.value = []
    } finally {
      loading.value = false
    }
  } else {
    // When search is cleared, reset to regular history
    resetHistory()
  }
}

// Reset to regular history when search is cleared
const resetHistory = async () => {
  isSearchActive.value = false
  page.value = 0
  history.value = []
  endOfHistory.value = false
  loading.value = true

  // Reset observer for infinite scroll
  if (observer) {
    observer.disconnect()
    observer = null
  }

  // Fetch regular history
  await fetchHistory(true)

  // Reinitialize infinite scroll
  if (sentinel.value && history.value.length > 0) {
    initInfiniteScroll()
  }
}

// Fetch history data
const fetchHistory = async (isInitial = false) => {
  // Prevent multiple concurrent requests
  if (loadingMore.value && !isInitial) return
  if (endOfHistory.value && !isInitial) return
  if (isSearchActive.value) return

  if (isInitial) {
    loading.value = true
    page.value = 0 // Reset page for initial load
    history.value = [] // Clear existing data
    endOfHistory.value = false
  } else {
    loadingMore.value = true
  }

  try {
    const response = await fetch(
      `${API}/nginx/history?offset=${page.value}&limit=${pageSize.value}`
    )

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const json = await response.json()

    if (json.success) {
      // Handle both response formats:
      // 1. { success: true, data: { results: [], total: X } }
      // 2. { success: true, data: [] }
      const results = json.data.results || json.data

      if (results.length === 0) {
        endOfHistory.value = true
      } else {
        // Add new data to existing history
        if (isInitial) {
          history.value = results
        } else {
          // Filter out any duplicates before adding
          const existingIds = new Set(history.value.map(item => item.history_id || item.id))
          const newData = results.filter((item: any) => !existingIds.has(item.history_id || item.id))
          history.value = [...history.value, ...newData]
        }

        // Only increment page if we got data
        if (results.length > 0) {
          page.value += pageSize.value
        }

        // If we got less data than requested, we're at the end
        if (results.length < pageSize.value) {
          endOfHistory.value = true
        }
      }
    } else {
      console.error('API Error:', json.error)
      if (json.data && json.data.length === 0) {
        endOfHistory.value = true
      }
    }
  } catch (err) {
    console.error('Fetch error:', err)
    // Don't set endOfHistory on network errors, allow retry
  } finally {
    if (isInitial) {
      loading.value = false
    } else {
      loadingMore.value = false
    }
  }
}

// Toggle configuration details
const toggleDetails = (id: string) => {
  if (expandedEntries.value.includes(id)) {
    expandedEntries.value = expandedEntries.value.filter(entryId => entryId !== id)
  } else {
    expandedEntries.value = [...expandedEntries.value, id]
  }
}

// Format date and time
const formatDateTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Initialize infinite scroll
const initInfiniteScroll = () => {
  if (observer) {
    observer.disconnect()
  }

  const options = {
    root: null,
    rootMargin: '100px',
    threshold: 0.1
  }

  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting && !loadingMore.value && !endOfHistory.value && !loading.value && !isSearchActive.value) {
        fetchHistory(false)
      }
    })
  }, options)

  if (sentinel.value) {
    observer.observe(sentinel.value)
  }
}

// Clean up observer
onBeforeUnmount(() => {
  if (observer) {
    observer.disconnect()
  }

  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }
})

// Watch for search query changes
watch(searchQuery, (newVal) => {
  if (newVal === '') {
    resetHistory()
  }
})

// Initial load
onMounted(async () => {
  await fetchHistory(true)

  setTimeout(() => {
    if (sentinel.value && history.value.length > 0) {
      initInfiniteScroll()
    }
  }, 200)
})
</script>
