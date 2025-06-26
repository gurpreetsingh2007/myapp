
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'
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

//rsync parser
type RsnapshotEntry = {
  id: number
  type: 'general' | 'backup'
  directive: string
  args: string | null
  source: string | null
  dest: string | null
  parameters: string | null
}
function parseJsonArray(value: any): any[] {
  if (!value) return []
  try {
    const parsed = typeof value === 'string' ? JSON.parse(value) : value
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}
function entryToRsnapshotLine(entry: RsnapshotEntry): string | null {
  if (entry.type === 'general') {
    const args = parseJsonArray(entry.args)
    return `${entry.directive} ${args.join(' ')}`
  }

  if (entry.type === 'backup') {
    const params = parseJsonArray(entry.parameters) as { name: string; value: string }[] | null
    const paramLines = params?.map(p => `${p.name} ${p.value}`) || []
    const backupLine = `${entry.directive} ${entry.source} ${entry.dest}`
    return [backupLine, ...paramLines].join('\n')
  }

  return null
}


// Initialize observer for infinite scroll
let observer: IntersectionObserver | null = null

// Computed property to ensure unique history entries
const uniqueHistory = computed(() => {
  const seen = new Set()
  return history.value.filter(entry => {
    const id = entry.id
    if (seen.has(id)) {
      return false
    }
    seen.add(id)
    return true
  })
})

// Handle search input with debounce
const handleSearchInput = () => {
  // Clear existing timer
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }

  // Set new timer
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
        `${API}/credentials/get/searchHistory?q=${encodeURIComponent(searchQuery.value.trim())}&offset=0&limit=${pageSize.value}`
      )

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

      const json = await response.json()

      // FIXED: Handle different response structures
      if (Array.isArray(json)) {
        // Direct array response
        history.value = json
      } else if (json && Array.isArray(json.data)) {
        // Response with data property
        history.value = json.data
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
      `${API}/credentials/get/history?offset=${page.value}&limit=${pageSize.value}`
    )

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const json = await response.json()

    if (json.success) {
      if (json.data.length === 0) {
        endOfHistory.value = true
      } else {
        // Add new data to existing history
        if (isInitial) {
          history.value = json.data
        } else {
          // Filter out any duplicates before adding
          const existingIds = new Set(history.value.map(item => item.id))
          const newData = json.data.filter((item: any) => !existingIds.has(item.id))
          history.value = [...history.value, ...newData]
        }

        // Only increment page if we got data
        if (json.data.length > 0) {
          page.value += 10
        }

        // If we got less data than requested, we're at the end
        if (json.data.length < pageSize.value) {
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

// Get user initials for avatar
const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .substring(0, 2)
}

// Parse directive to readable format
const parseDirective = (data: any, indent = 0): string => {
  const indentStr = '  '.repeat(indent)
  let result = ''

  if (!data || !data.directive || typeof data.directive !== 'string' || data.directive.trim() === '') {
    return ''
  }

  result += `${indentStr}${data.directive}`

  if (Array.isArray(data.args) && data.args.length) {
    result += ' ' + data.args.join(' ')
  }

  if (Array.isArray(data.block) && data.block.length) {
    result += ' {\n'
    for (const item of data.block) {
      const child = parseDirective(item, indent + 1)
      if (child.trim()) result += child + '\n'
    }
    result += `${indentStr}}`
  } else {
    result += ';'
  }

  return result
}

// Initialize infinite scroll
const initInfiniteScroll = () => {
  // Clean up existing observer
  if (observer) {
    observer.disconnect()
  }

  const options = {
    root: null,
    rootMargin: '100px', // Start loading when 100px away from bottom
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

  // Initialize infinite scroll after initial data load and DOM update
  setTimeout(() => {
    if (sentinel.value && history.value.length > 0) {
      initInfiniteScroll()
    }
  }, 200)
})
</script>


<template>
  <div class="min-h-screen p-4 sm:p-6 md:p-8 bg-gradient-to-br from-slate-50 to-blue-50/30">
    <div class="mx-auto">
      <!-- Header Section -->
      <div class="modern-header mb-10">
        <h1 class="text-3xl md:text-4xl font-bold text-[#005188] mb-2 tracking-wide">
          <span class="text-[#007C52]">>></span> EDIT HISTORY <span class="text-[#007C52]"><<</span>
        </h1>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <p class="text-[#007C52] font-semibold">All configuration changes tracked in real-time</p>
          <div class="modern-search flex items-center bg-white/80 backdrop-blur-sm border border-slate-300 px-4 py-2 rounded-full shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#007C52] mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
            <input
              type="text"
              v-model="searchQuery"
              @input="handleSearchInput"
              placeholder="Search history..."
              class="modern-input bg-transparent border-0 text-slate-700 placeholder-slate-500 focus:ring-0 focus:outline-none w-full font-medium"
            />
          </div>
        </div>
        <div class="grid-line mt-6"></div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && !history.length" class="flex flex-col items-center justify-center py-16">
        <div class="modern-loader">
          <div class="loader-circle"></div>
          <div class="loader-text text-[#005188] font-semibold mt-4">LOADING HISTORY DATABASE</div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!history.length && !loading" class="modern-card text-center py-16 px-4">
        <div class="modern-icon mx-auto mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-[#005188] mb-2">NO HISTORY RECORDS FOUND</h3>
        <p class="text-slate-600 max-w-md mx-auto">All edits made to configurations will appear here. Make your first change to see it tracked.</p>
      </div>

      <!-- History List -->
      <div v-else class="flex flex-col items-center space-y-6">
        <div
          v-for="(entry, index) in uniqueHistory"
          :key="entry.id"
          class="modern-card w-full max-w-6xl"
          :class="{'glow-blue': index % 3 === 0, 'glow-green': index % 3 === 1, 'glow-teal': index % 3 === 2}"
        >
          <div class="card-header">
            <div class="flex items-center gap-3">
              <div class="modern-avatar">
                {{ getInitials(entry.editor_name) }}
              </div>
              <div>
                <h3 class="text-lg font-bold text-[#005188]">{{ entry.editor_name }}</h3>
                <p class="text-xs text-[#007C52] font-semibold">{{ entry.editor_gmail }}</p>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-[#005188]">{{ formatDateTime(entry.edit_datetime) }}</div>
              <div class="text-xs text-slate-500 mt-1">ID: {{ entry.id }}</div>
            </div>
          </div>

          <div class="grid-line"></div>

          <div class="modern-grid">
            <div>
              <div class="grid-label">ACTION</div>
              <div class="grid-value text-[#005188]">{{ entry.action }}</div>
            </div>
            <div>
              <div class="grid-label">COMMENT</div>
              <div class="grid-value">{{ entry.comment || 'No comment provided' }}</div>
            </div>
            <div>
              <div class="grid-label">EDITED TABLE</div>
              <div class="grid-value">
                {{ entry.table_edited }} <span class="text-slate-500">(ID: {{ entry.table_row_id }})</span>
              </div>
            </div>
          </div>

          <div class="mt-4">
            <button
              @click="toggleDetails(entry.id)"
              class="modern-button flex items-center"
            >
              <span>{{ expandedEntries.includes(entry.id) ? 'HIDE' : 'VIEW' }} OLD CONFIGURATION</span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 ml-2 transition-transform duration-300"
                :class="{'rotate-180': expandedEntries.includes(entry.id)}"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>

            <div
              v-if="expandedEntries.includes(entry.id)"
              class="modern-codeblock mt-4"
            >
              <div class="code-header">
                <div class="code-dots">
                  <span class="dot bg-red-400"></span>
                  <span class="dot bg-amber-400"></span>
                  <span class="dot bg-emerald-400"></span>
                </div>
                <div class="text-xs text-slate-600 font-semibold">CONFIGURATION DATA</div>
              </div>
              <pre class="modern-pre">{{ entry.table_edited  !== 'cfg_rsnapshot' ? parseDirective(JSON.parse(entry.old_text || '{}')): entryToRsnapshotLine(JSON.parse(entry.old_text || '{}')) }}</pre>
            </div>
          </div>
        </div>

        <!-- Loading More Indicator -->
        <div v-if="loadingMore" class="flex justify-center py-8">
          <div class="modern-loader-sm">
            <div class="loader-circle-sm"></div>
            <div class="text-[#005188] text-sm font-semibold mt-4">LOADING MORE RECORDS...</div>
          </div>
        </div>

        <!-- Sentinel for infinite scroll -->
        <div ref="sentinel" class="h-1"></div>

        <!-- End of History -->
        <div v-if="endOfHistory && !loadingMore" class="text-center py-10">
          <div class="modern-endline inline-flex items-center text-slate-500">
            <div class="h-px bg-gradient-to-r from-transparent via-[#005188] to-transparent w-16 mr-4"></div>
            END OF HISTORY RECORDS
            <div class="h-px bg-gradient-to-r from-transparent via-[#005188] to-transparent w-16 ml-4"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modern-header {
  position: relative;
}

.grid-line {
  height: 2px;
  background: linear-gradient(90deg, transparent, #005188, #007C52, transparent);
  margin-top: 0.5rem;
  border-radius: 1px;
}

.modern-card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(0, 81, 136, 0.2);
  border-radius: 16px;
  padding: 1.5rem;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  margin-top: 1rem;
  margin-right: 1rem;
  margin-left: 1rem;
  shadow: 0 4px 20px rgba(0, 81, 136, 0.1);
}

.modern-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 40px rgba(0, 81, 136, 0.15);
  border-color: rgba(0, 81, 136, 0.3);
}

.glow-blue {
  box-shadow: 0 0 25px rgba(0, 81, 136, 0.2);
  border-color: #005188;
}

.glow-green {
  box-shadow: 0 0 25px rgba(0, 124, 82, 0.2);
  border-color: #007C52;
}

.glow-teal {
  box-shadow: 0 0 25px rgba(20, 184, 166, 0.2);
  border-color: #14b8a6;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.modern-avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #005188 0%, #007C52 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.1rem;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 81, 136, 0.3);
}

.modern-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.grid-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.grid-value {
  font-size: 0.95rem;
  color: #334155;
  line-height: 1.5;
  font-weight: 500;
}

.modern-button {
  background: linear-gradient(135deg, #005188, #007C52);
  border: none;
  padding: 0.6rem 1.5rem;
  border-radius: 50px;
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  box-shadow: 0 4px 12px rgba(0, 81, 136, 0.3);
}

.modern-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 81, 136, 0.4);
  background: linear-gradient(135deg, #007C52, #005188);
}

.modern-codeblock {
  background: rgba(248, 250, 252, 0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(0, 81, 136, 0.2);
  border-radius: 12px;
  overflow: hidden;
  margin-top: 1rem;
  box-shadow: 0 4px 12px rgba(0, 81, 136, 0.1);
}

.code-header {
  background: rgba(0, 81, 136, 0.1);
  padding: 0.75rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(0, 81, 136, 0.1);
}

.code-dots {
  display: flex;
  gap: 6px;
}

.code-dots .dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.modern-pre {
  padding: 1.5rem;
  font-family: 'Fira Code', 'Courier New', monospace;
  font-size: 0.9rem;
  line-height: 1.6;
  color: #475569;
  overflow-x: auto;
  text-align: left;
  background: rgba(255, 255, 255, 0.5);
}

.modern-search {
  transition: all 0.3s ease;
}

.modern-search:hover {
  box-shadow: 0 8px 25px rgba(0, 81, 136, 0.15);
  border-color: rgba(0, 81, 136, 0.4);
}

.modern-input {
  font-family: system-ui, -apple-system, sans-serif;
}

.modern-endline {
  font-family: system-ui, -apple-system, sans-serif;
  letter-spacing: 0.05em;
  font-size: 0.9rem;
  font-weight: 600;
}

/* Loader animation */
.modern-loader {
  position: relative;
}

.loader-circle {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(0, 81, 136, 0.2);
  border-top: 4px solid #005188;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

.loader-text {
  position: relative;
}

.loader-text::after {
  content: "";
  animation: dots 1.5s infinite steps(4, end);
}

.modern-loader-sm {
  text-align: center;
}

.loader-circle-sm {
  width: 30px;
  height: 30px;
  border: 3px solid rgba(0, 81, 136, 0.2);
  border-top: 3px solid #005188;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes dots {
  0%, 20% { content: "."; }
  40% { content: ".."; }
  60% { content: "..."; }
  80%, 100% { content: ""; }
}

/* Scrollbar styling for code blocks */
.modern-pre::-webkit-scrollbar {
  height: 8px;
}

.modern-pre::-webkit-scrollbar-track {
  background: rgba(248, 250, 252, 0.5);
  border-radius: 4px;
}

.modern-pre::-webkit-scrollbar-thumb {
  background: linear-gradient(90deg, #005188, #007C52);
  border-radius: 4px;
}

.modern-pre::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(90deg, #007C52, #005188);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .card-header {
    flex-direction: column;
    gap: 1rem;
  }

  .modern-grid {
    grid-template-columns: 1fr;
  }

  .modern-header h1 {
    font-size: 2rem;
  }
}

/* Additional modern touches */
.modern-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(0, 81, 136, 0.3), transparent);
}

.modern-search:focus-within {
  border-color: #005188;
  box-shadow: 0 0 0 3px rgba(0, 81, 136, 0.1);
}

/* Smooth hover effects */
.modern-card:hover .modern-avatar {
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(0, 81, 136, 0.4);
}

.modern-card:hover .grid-value {
  color: #1e293b;
}
</style>
