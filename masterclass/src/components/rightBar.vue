<script setup lang="ts">
/*

*/
import { ref, onMounted, computed, onBeforeMount, onBeforeUnmount, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Path } from '@/stores/path'
import { API } from '@/config/index'
import { useRightSidebarStore } from '@/stores/site/sidebar/sidebar'
import { storeToRefs } from 'pinia'
const info = Path()
import { useJsonDataStore } from '@/stores/block'

const jsonDataStore = useJsonDataStore()
const showSetupWizard = ref(false)
const currentStep = ref(1)
const totalSteps = ref(5)
const stepTitles = [
  'Server Information',
  'SSL Configuration',
  'Proxy Locations',
  'Additional Options',
  'Review & Generate'
]

// Reverse Proxy Configuration
const serverConfig = ref({
  serverName: '',
  enableSSL: true,
  sslPort: '443',
  certificatePath: '/etc/ssl/certs/nginx.crt',
  privateKeyPath: '/etc/ssl/private/nginx.key',
  locations: [
    { path: '/', proxyPass: 'http://localhost:3000' }
  ],
  customDirectives: ''
})
const searchQuery = ref('')

const filteredRows = computed(() => {
  if (!searchQuery.value.trim()) return configRows.value
  const q = searchQuery.value.toLowerCase()
  return configRows.value.filter(
    row =>
      row.title?.toLowerCase().includes(q) ||
      String(row.id).includes(q)
  )
})

// Generated Nginx Config
const generatedConfig = computed(() => {
  const config = []

  // Server block
  config.push(`server {`)
  if (serverConfig.value.enableSSL) {
    config.push(`    # SSL Configuration`)
    config.push(`    listen ${serverConfig.value.sslPort} ssl;`)
    config.push(`    ssl_certificate ${serverConfig.value.certificatePath};`)
    config.push(`    ssl_certificate_key ${serverConfig.value.privateKeyPath};`)
    config.push('')
  }
  else {
    config.push(`    listen ${serverConfig.value.listenPort};`)
  }
  config.push(`    server_name ${serverConfig.value.serverName};`)
  config.push('')

  // HTTP to HTTPS redirect
  if (serverConfig.value.redirectHttpToHttps && !serverConfig.value.enableSSL) {
    config.push(`    # Redirect HTTP to HTTPS`)
    config.push(`    return 301 https://$host$request_uri;`)
    config.push('')
  }

  // SSL configuration

  if (serverConfig.value.enableSSL) {
    // Locations
    config.push(`    # Proxy Locations`)
    serverConfig.value.locations.forEach(loc => {
      config.push(`    location ${loc.path} {`)
      config.push(`        proxy_set_header Host $host;`)
      config.push(`        proxy_set_header X-Real-IP $remote_addr;`)
      config.push(`        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;`)
      config.push(`        proxy_set_header X-Forwarded-Proto $scheme;`)
      config.push(`        proxy_pass ${loc.proxyPass};`)
      config.push(`    }`)
      config.push('')
    })

    // Custom directives
    if (serverConfig.value.customDirectives) {
      config.push(`    # Custom Directives`)
      serverConfig.value.customDirectives.split('\n').forEach(line => {
        if (line.trim()) config.push(`    ${line.trim()}`)
      })
    }
  }

  config.push('}')

  return config.join('\n')
})

// Wizard Navigation
const nextStep = () => {

  if (!serverConfig.value.enableSSL && currentStep.value === 2) {
    currentStep.value++
  } else if (serverConfig.value.enableSSL && currentStep.value === 3) {
    serverConfig.value.redirectHttpToHttps = false
  }
  if (currentStep.value < totalSteps.value) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const addLocation = () => {
  serverConfig.value.locations.push({
    path: '/new',
    proxyPass: 'http://localhost:8080'
  })
}

const removeLocation = (index: number) => {
  serverConfig.value.locations.splice(index, 1)
}

// Modified openNewBlockDialog to handle wizard mode
const openNewBlockDialog = (wizardMode = false) => {
  showSetupWizard.value = wizardMode
  editMode.value = false
  newBlockTitle.value = ''

  if (wizardMode) {
    // Reset wizard state
    currentStep.value = 1
    serverConfig.value = {
      serverName: '',
      listenPort: '80',
      enableSSL: true,
      sslPort: '443',
      certificatePath: '/etc/ssl/certs/nginx.crt',
      privateKeyPath: '/etc/ssl/private/nginx.key',
      locations: [{ path: '/', proxyPass: 'http://localhost:3000' }],
      redirectHttpToHttps: true,
      enableGzip: true,
      customDirectives: ''
    }
  }

  showNewBlockDialog.value = true
}
function parseNginxBlock(configText: string): NginxEntry | null {
  const lines = configText.split(/\r?\n/);
  let lineNumber = 0;
  let index = 0;

  function parseArgs(argString: string): string[] {
    return argString
      .split(/\s+/)
      .map(arg => arg.trim())
      .filter(arg => arg.length > 0);
  }

  function parseBlock(): NginxEntry[] {
    const block: NginxEntry[] = [];

    while (index < lines.length) {
      let line = lines[index].trim();
      lineNumber++;

      if (line === '') {
        index++;
        continue;
      }

      if (line === '}') {
        index++;
        break;
      }

      const match = line.match(/^([a-zA-Z_][a-zA-Z0-9_~]*)\s*(.*?)\s*(\{?|;)?$/);
      if (match) {
        const [, directive, argStringRaw, ending] = match;
        let args = parseArgs(argStringRaw.replace(/;$/, ''));

        const entry: NginxEntry = {
          directive,
          line: lineNumber,
          args,
        };

        // Handle multiline arguments
        while (
          index + 1 < lines.length &&
          !line.trim().endsWith(';') &&
          !line.trim().endsWith('{') &&
          !ending
        ) {
          index++;
          lineNumber++;
          const extra = lines[index].trim();
          if (extra === '') continue;
          args = args.concat(parseArgs(extra.replace(/;$/, '')));
          entry.args = args;
          line = lines[index]; // update line for loop
        }

        if (ending === '{') {
          index++;
          entry.block = parseBlock();
        } else {
          index++;
        }

        block.push(entry);
      } else {
        index++;
      }
    }

    return block;
  }

  while (index < lines.length) {
    const line = lines[index].trim();
    lineNumber++;

    if (line === '' || line.startsWith('#')) {
      index++;
      continue;
    }

    const blockStartMatch = line.match(/^([a-zA-Z_][a-zA-Z0-9_~]*)\s*\{$/);
    if (blockStartMatch) {
      const [, directive] = blockStartMatch;
      index++;
      return {
        directive,
        line: lineNumber,
        args: [],
        block: parseBlock(),
      };
    }

    const singleLineMatch = line.match(/^([a-zA-Z_][a-zA-Z0-9_~]*)\s+(.*?)\s*;$/);
    if (singleLineMatch) {
      const [, directive, argsRaw] = singleLineMatch;
      const args = parseArgs(argsRaw);
      index++;
      return {
        directive,
        line: lineNumber,
        args,
      };
    }

    index++;
  }

  return null;
}
const { jsonData } = storeToRefs(jsonDataStore)
// Save reverse proxy block
const saveReverseProxyBlock = async () => {
  try {
    // Create the block with the generated config
    const res = await fetch(API + `/backend/credentials/create/block?path=${encodeURIComponent(filePath.value)}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        title: newBlockTitle.value,
        file_name: info.info.sectionId,
        json_data: generatedConfig.value
      }),
    })

    if (!res.ok) {
      throw new Error('Failed to create reverse proxy block')
    }

    const data = await res.json()

    // Add to UI
    configRows.value.push({
      id: data.id,
      title: newBlockTitle.value,
    })
    showNewBlockDialog.value = false
  } catch (err: any) {
    error.value = err?.message || 'Failed to create reverse proxy block.'
  }




}



const sidebar = useRightSidebarStore()

const router = useRouter()
const route = useRoute()
const path = Path()
const filePath = computed(() => path.info.sectionId)
const isMobile = ref(false)
interface ConfigRow {
  id: string
  title: string
  // other fields as needed
}
const configRows = ref<ConfigRow[]>([])

const loading = ref(true)
const error = ref<string | null>(null)

// Menu state
const activeMenu = ref<string | null>(null)

// Dialog states
const showNewBlockDialog = ref(false)
const showDeleteDialog = ref(false)
const newBlockTitle = ref('')
const editMode = ref(false)
const currentBlockId = ref<string | null>(null)

const navigateTo = (route: string) => {
  router.push(route)
}

const toggleSidebar = () => {
  sidebar.isOpen = !sidebar.isOpen
  // Emit event to parent component so it can adjust layout if needed
  emit('toggle', sidebar.isOpen)
}

// Toggle menu dropdown
const toggleMenu = (id: string) => {
  if (activeMenu.value === id) {
    activeMenu.value = null
  } else {
    activeMenu.value = id
  }
}

// Close menu when clicking outside
const closeMenuOnOutsideClick = (event: MouseEvent) => {
  if (activeMenu.value !== null) {
    activeMenu.value = null
  }
}



// Edit block
const editBlock = (block: any) => {
  editMode.value = true
  currentBlockId.value = block.id
  newBlockTitle.value = block.title
  showNewBlockDialog.value = true
  activeMenu.value = null
}

// Delete block
const deleteBlock = (id: string) => {
  currentBlockId.value = id
  showDeleteDialog.value = true
  activeMenu.value = null
}
const fetchBlock = async () => {
  const path_name = path.info.sectionId
  const id = path.info.store_number
  try {
    await jsonDataStore.fetchJsonData(path_name, id)
  } catch (err) {
    console.error('Failed to fetch block data:', err)
  }
}

// Confirm deletion
const confirmDelete = async () => {
  try {
    // API call to delete block
    const res = await fetch(
      API +
      `/backend/credentials/delete/block?id=${currentBlockId.value}&path=${encodeURIComponent(filePath.value)}`,
      {
        method: 'DELETE',
      },
    )

    if (!res.ok) {
      throw new Error('Failed to delete block')
    }

    // Remove block from UI
    configRows.value = configRows.value.filter((row) => row.id !== currentBlockId.value)
    showDeleteDialog.value = false
  } catch (err: any) {
    error.value = err?.message || 'Failed to delete block.'
  }
}

// Save block (create or update)
const saveBlock = async () => {
  if (!newBlockTitle.value.trim()) {
    return
  }

  try {
    const endpoint = editMode.value
      ? `/backend/credentials/update/block?id=${currentBlockId.value}&path=${encodeURIComponent(filePath.value)}`
      : `/backend/credentials/create/block?path=${encodeURIComponent(filePath.value)}`

    const method = editMode.value ? 'PUT' : 'POST'

    const res = await fetch(API + endpoint, {
      method,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        title: newBlockTitle.value,
        // You can add more data here if needed
        // json_data: {} // Optional: Add custom JSON data for the block
      }),
    })

    if (!res.ok) {
      throw new Error(`Failed to ${editMode.value ? 'update' : 'create'} block`)
    }

    const data = await res.json()

    if (editMode.value) {
      // Update existing block
      const index = configRows.value.findIndex((row) => row.id === currentBlockId.value)
      if (index !== -1) {
        configRows.value[index].title = newBlockTitle.value
      }
    } else {
      // Add new block to the list
      configRows.value.push({
        id: data.id,
        title: newBlockTitle.value,
      })
    }

    showNewBlockDialog.value = false
  } catch (err: any) {
    error.value = err?.message || `Failed to ${editMode.value ? 'update' : 'create'} block.`
  }
}

// Define emits
const emit = defineEmits(['toggle'])

// Check if device is mobile
const checkIfMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onBeforeMount(() => {
  checkIfMobile()
  window.addEventListener('resize', checkIfMobile)
  document.addEventListener('click', closeMenuOnOutsideClick)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkIfMobile)
  document.removeEventListener('click', closeMenuOnOutsideClick)
})

onMounted(async () => {
  try {
    const res = await fetch(
      API + `/backend/credentials/get/block?path=${encodeURIComponent(filePath.value)}`,
    )

    if (!res.ok) {
      throw new Error('Failed to fetch config')
    }

    const data = await res.json()
    configRows.value = data.rows
  } catch (err: any) {
    error.value = err?.message || 'Failed to load config.'
  } finally {
    loading.value = false
  }
  fetchBlock()
})
watch(
  () => route.fullPath,
  async () => {
    fetchBlock()
  },
  { immediate: true },
)
</script>

<template>
  <div class="flex flex-col h-full md:flex-row">
    <!-- Backdrop (mobile only) -->
    <Transition name="fade">
      <div v-if="sidebar.isOpen && isMobile" @click="toggleSidebar"
        class="fixed inset-0 z-50 bg-gray-400/30 backdrop-blur-sm"></div>
    </Transition>

    <!-- Sidebar -->
    <aside
      class="h-full flex flex-col flex-shrink-0 bg-gradient-to-b from-white to-gray-100 border-l border-gray-200 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] z-50 shadow-lg"
      :class="{
        'w-80': sidebar.isOpen,
        'w-0': !sidebar.isOpen,
      }">
      <div
        class="relative px-6 py-5 border-b border-gray-200 h-16 flex items-center justify-center overflow-hidden flex-shrink-0">
        <!-- Centered content group -->
        <div class="relative z-10 flex items-center gap-4">
          <!-- Title -->
          <Transition name="slide-fade">
            <h2 v-if="sidebar.isOpen" class="text-xl font-semibold text-gray-800 tracking-tight">
              <span class="text-[#005188] font-bold">
                Blocks
              </span>
            </h2>
          </Transition>

          <!-- Add New Block Button -->
          <Transition name="slide-fade">
            <button v-if="sidebar.isOpen" @click="openNewBlockDialog"
              class="flex items-center justify-center p-2 rounded-full bg-[#007C52]/10 border border-[#007C52]/20 text-[#007C52] transition-all duration-300 hover:bg-[#007C52]/15 hover:scale-110">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </Transition>
        </div>
      </div>

      <!-- Content - Scrollable Area -->
      <div ref="scrollRef" class="flex-grow overflow-y-auto bg-white"
        :class="{ 'opacity-100': sidebar.isOpen, 'opacity-0': !sidebar.isOpen }">

        <div class="p-6">
          <!-- Loading state -->
          <div v-if="loading" class="flex justify-center items-center h-40">
            <div
              class="w-12 h-12 rounded-sm border-4 border-t-[#005188] border-r-[#007C52] border-b-[#005188] border-l-transparent animate-spin">
            </div>
          </div>

          <!-- Error state -->
          <div v-else-if="error"
            class="p-4 bg-red-50 rounded-lg border border-red-200 text-red-600">
            {{ error }}
          </div>

          <!-- Config Items -->
          <div v-else class="grid grid-cols-1 gap-4">
            <div class="mb-4">
              <input v-model="searchQuery" type="text" placeholder="Search by title or ID..."
                class="w-full px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#005188]/40 focus:border-[#005188] transition" />
            </div>
            <div v-for="row in filteredRows" :key="row.id"
              class="group relative top-1 p-5 mb-1 rounded-xl bg-white backdrop-blur-sm border border-gray-200 hover:border-[#007C52]/50 transition-all duration-300 cursor-pointer text-gray-700 hover:-translate-y-0.5 hover:shadow-md">
              <!-- Background effect -->
              <div
                class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-br from-[#007C52]/5 to-[#005188]/5 z-0">
              </div>

              <!-- Enhanced Three Dots Menu -->
              <div class="absolute top-4 right-3 z-[999]">
                <div class="relative">
                  <!-- Menu Trigger Button -->
                  <button @click.stop="toggleMenu(row.id)"
                    class="p-1.5 rounded-lg transition-all duration-300 hover:bg-gray-100 group/trigger"
                    :class="{ 'bg-gray-100': activeMenu === row.id }">
                    <div class="relative">
                      <svg class="w-6 h-6 text-gray-500 transform transition-transform duration-300 hover:scale-110"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                          d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
                      </svg>
                    </div>
                  </button>

                  <!-- Enhanced Dropdown Menu -->
                  <transition enter-active-class="transition-all duration-200 ease-out"
                    leave-active-class="transition-all duration-150 ease-in" enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0" leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1">
                    <div v-if="activeMenu === row.id"
                      class="absolute right-8 top-3 mt- w-40 origin-top-right z-[9999] overflow: !visible !important">
                      <div
                        class="relative border border-gray-300 rounded-xl bg-white backdrop-blur-lg shadow-lg overflow-hidden">
                        <!-- Menu Items -->
                        <div class="space-y-1 p-1.5">
                          <button @click.stop="editBlock(row)"
                            class="flex items-center w-full px-4 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-all duration-300 group/edit">
                            <div class="mr-3 relative">
                              <svg class="w-5 h-5 text-[#005188]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                              </svg>
                            </div>
                            <span>Edit Block</span>
                          </button>

                          <button @click.stop="deleteBlock(row.id)"
                            class="flex items-center w-full px-4 py-2.5 rounded-lg text-sm text-red-500 hover:bg-red-50 transition-all duration-300 group/delete">
                            <div class="mr-3 relative">
                              <svg class="w-5 h-5 text-red-500" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                            </div>
                            <span class="relative">
                              Delete
                            </span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>
              </div>

              <!-- Button content -->
              <div @click="
                navigateTo(
                  `/site/config?block_id=${path.info.service}&section_id=${encodeURIComponent(path.info.sectionId)}&store_id=${row.title}&store_number=${row.id}`,
                )
                " class="relative z-10 flex flex-col items-center top-0.5 space-y-3">
                <div class="bg-gray-100 rounded-md px-3 py-1 border border-gray-200">
                  <span class="text-md font-bold text-[#005188]">
                    #{{ row.id }}
                  </span>
                </div>
                <span class="text-lg font-small tracking-wide text-center">{{ row.title }}</span>
                <div
                  class="h-0.5 w-10 mt-2 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 bg-gradient-to-r from-[#005188] to-[#007C52]">
                </div>
              </div>

              <!-- Corner decorations -->
              <div
                class="absolute top-0 left-0 w-3 h-3 border-t border-l border-[#005188]/50 rounded-tl-lg">
              </div>
              <div
                class="absolute top-0 right-0 w-3 h-3 border-t border-r border-[#007C52]/50 rounded-tr-lg">
              </div>
              <div
                class="absolute bottom-0 left-0 w-3 h-3 border-b border-l border-[#007C52]/50 rounded-bl-lg">
              </div>
              <div
                class="absolute bottom-0 right-0 w-3 h-3 border-b border-r border-[#005188]/50 rounded-br-lg">
              </div>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <Transition name="dialog">
    <div v-if="showNewBlockDialog" class="fixed inset-0 z-[100] flex items-center justify-center">
      <div class="absolute inset-0 bg-gray-500/50 backdrop-blur-sm transition-opacity"
        @click="showNewBlockDialog = false"></div>
      <div v-if="editMode"
        class="relative z-10 bg-white border border-gray-300 rounded-2xl w-full max-w-md p-8 shadow-xl transform transition-all">
        <h3 class="text-2xl font-bold text-gray-800 mb-6 relative">
          <span class="text-[#005188]">
            {{ editMode ? 'Edit Block' : 'Create New Block' }}
          </span>
          <div class="h-[2px] mt-1 bg-gradient-to-r from-[#005188] to-[#007C52] w-20 rounded-full">
          </div>
        </h3>

        <div class="space-y-6">
          <div>
            <label for="blockTitle" class="block text-sm font-medium text-[#005188] mb-2">Title</label>
            <div class="relative">
              <input type="text" id="blockTitle" v-model="newBlockTitle"
                class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188] focus:ring-2 focus:ring-[#005188]/30 placeholder-gray-500 transition-all duration-300"
                placeholder="Enter block title" />
            </div>
          </div>

          <div class="flex justify-end space-x-4 mt-8">
            <button @click="showNewBlockDialog = false"
              class="px-5 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-[#005188] hover:bg-gray-100 transition-all duration-300 flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Cancel
            </button>
            <button @click="saveBlock"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#005188] to-[#007C52] text-white font-semibold hover:shadow-md transition-all duration-300 relative overflow-hidden group">
              <span class="relative z-10 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ editMode ? 'Update' : 'Create' }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Setup Wizard -->
      <div v-if="showSetupWizard && !editMode" class="relative z-10 bg-white rounded-2xl border border-gray-300 shadow-xl w-full max-w-md p-8">
        <div class="space-y-6">
          <!-- Step Navigation -->
          <div class="flex justify-between items-center mb-6">
            <button v-if="currentStep > 1" @click="prevStep"
              class="px-4 py-2 rounded-lg bg-gray-100 text-[#005188] flex items-center">
              <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Back
            </button>
            <div class="flex-grow text-center">
              <span class="text-[#005188]">Step {{ currentStep }} of {{ totalSteps }}</span>
              <h3 class="text-xl font-bold text-gray-800">{{ stepTitles[currentStep - 1] }}</h3>
            </div>
            <div v-if="currentStep === 1" class="w-24"></div>
          </div>

          <!-- Progress Bar -->
          <div class="h-2 bg-gray-200 rounded-full mb-6">
            <div class="h-full bg-gradient-to-r from-[#005188] to-[#007C52] rounded-full transition-all duration-500"
              :style="{ width: `${(currentStep / totalSteps) * 100}%` }"></div>
          </div>

          <!-- Step 1: Server Info -->
          <div v-if="currentStep === 1" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-[#005188] mb-2">Server Name</label>
              <input v-model="serverConfig.serverName"
                class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                placeholder="example.com" />
            </div>
          </div>

          <!-- Step 2: SSL Configuration -->
          <div v-if="currentStep === 2" class="space-y-4">
            <div class="flex items-center">
              <input type="checkbox" v-model="serverConfig.enableSSL" id="enableSSL"
                class="mr-3 h-5 w-5 text-[#005188] rounded focus:ring-[#005188]" />
              <label for="enableSSL" class="text-gray-700">Enable SSL/TLS</label>
            </div>

            <div v-if="serverConfig.enableSSL" class="space-y-4 pl-8 border-l border-gray-200">
              <div>
                <label class="block text-sm font-medium text-[#005188] mb-2">SSL Listen Port</label>
                <input v-model="serverConfig.sslPort" type="number"
                  class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                  placeholder="443" />
              </div>

              <div>
                <label class="block text-sm font-medium text-[#005188] mb-2">Certificate Path</label>
                <input v-model="serverConfig.certificatePath"
                  class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                  placeholder="/etc/ssl/certs/example.crt" />
              </div>

              <div>
                <label class="block text-sm font-medium text-[#005188] mb-2">Private Key Path</label>
                <input v-model="serverConfig.privateKeyPath"
                  class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                  placeholder="/etc/ssl/private/example.key" />
              </div>
            </div>
            <div v-else>
              <div>
                <label class="block text-sm font-medium text-[#005188] mb-2">Listen Port</label>
                <input v-model="serverConfig.sslPort" type="number"
                  class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                  placeholder="80" />
              </div>
            </div>
          </div>

          <!-- Step 3: Proxy Locations -->
          <div v-if="currentStep === 3" class="space-y-6">
            <div v-for="(location, index) in serverConfig.locations" :key="index"
              class="bg-gray-50 p-4 rounded-xl border border-gray-200">
              <div class="flex justify-between items-center mb-3">
                <h4 class="text-[#005188] font-medium">Location #{{ index + 1 }}</h4>
                <button v-if="serverConfig.locations.length > 1" @click="removeLocation(index)"
                  class="text-red-500 hover:text-red-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-[#005188] mb-2">Path</label>
                  <input v-model="location.path"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-700"
                    placeholder="/" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-[#005188] mb-2">Proxy Pass</label>
                  <input v-model="location.proxyPass"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-700"
                    placeholder="http://localhost:3000" />
                </div>
              </div>
            </div>

            <button @click="addLocation"
              class="flex items-center justify-center w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-[#007C52] hover:bg-gray-50">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add Location
            </button>
          </div>

          <!-- Step 4: Additional Options -->
          <div v-if="currentStep === 4" class="space-y-4">
            <div class="flex items-center">
              <input type="checkbox" v-model="serverConfig.redirectHttpToHttps" id="redirectHttp"
                class="mr-3 h-5 w-5 text-[#005188] rounded focus:ring-[#005188]" />
              <label for="redirectHttp" class="text-gray-700">Redirect HTTP to HTTPS</label>
            </div>

            <div>
              <label class="block text-sm font-medium text-[#005188] mb-2">Custom Directives</label>
              <textarea v-model="serverConfig.customDirectives"
                class="w-full h-32 bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                placeholder="Add any custom Nginx directives..."></textarea>
            </div>
          </div>

          <!-- Step 5: Review & Generate -->
          <div v-if="currentStep === 5" class="space-y-6">
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
              <h4 class="text-[#005188] mb-3 font-medium">Generated Nginx Configuration</h4>
              <pre class="modern-pre text-sm text-gray-600 overflow-auto p-3 bg-white rounded-lg border border-gray-200">{{ generatedConfig }}</pre>
            </div>

            <div>
              <label class="block text-sm font-medium text-[#005188] mb-2">Block Title</label>
              <input v-model="newBlockTitle"
                class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:border-[#005188]"
                placeholder="Enter block title" />
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-between pt-6">
            <button v-if="currentStep > 1" @click="prevStep"
              class="px-5 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-[#005188] hover:bg-gray-100">
              Back
            </button>

            <div class="flex-grow"></div>

            <button v-if="currentStep < totalSteps" @click="nextStep"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#005188] to-[#007C52] text-white font-semibold">
              Next
            </button>

            <button v-if="currentStep === totalSteps" @click="saveReverseProxyBlock"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#005188] to-[#007C52] text-white font-semibold">
              Save Block
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Enhanced Delete Confirmation Dialog -->
  <Transition name="dialog">
    <div v-if="showDeleteDialog" class="fixed inset-0 z-[100] flex items-center justify-center">
      <div class="absolute inset-0 bg-gray-500/50 backdrop-blur-sm transition-opacity" @click="showDeleteDialog = false">
      </div>
      <div
        class="relative z-10 bg-white border border-red-200 rounded-2xl w-full max-w-md p-8 shadow-xl transform transition-all">
        <div class="text-center">
          <div class="mb-5 inline-block p-4 bg-red-50 rounded-full">
            <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>

          <h3 class="text-2xl font-bold text-red-600 mb-3">Confirm Deletion</h3>
          <p class="text-gray-600 mb-6 px-4 leading-relaxed">
            Are you sure you want to delete this block?<br />
            <span class="text-red-500 font-medium">This action can be undone from the history!</span>
          </p>

          <div class="flex justify-center space-x-4">
            <button @click="showDeleteDialog = false"
              class="px-5 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition-all duration-300">
              Cancel
            </button>
            <button @click="confirmDelete"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-br from-red-500 to-red-600 text-white font-semibold hover:shadow-md transition-all duration-300 relative overflow-hidden">
              <span class="relative z-10 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.dialog-enter-active,
.dialog-leave-active {
  transition: all 0.3s ease;
}

.dialog-enter-from,
.dialog-leave-to {
  transform: scale(0.95);
  opacity: 0;
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
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
</style>
