<template>
  <div class="h-full w-full flex overflow-hidden">
    <!-- Left Sidebar (Advanced Mode) -->
    <Transition name="slide-left">
      <div
        v-if="isAdvanced"
        class="w-10 bg-gradient-to-b from-black via-gray-900 to-black border-r-4 border-cyan-400 flex flex-col items-center justify-between shadow-inner shadow-cyan-500/20"
      >
        <div class="flex-1 flex items-center justify-center">
          <button
            @click="toggleMode"
            class="bg-pink-600 hover:bg-pink-500 p-3 rounded-full transition-all duration-300 transform hover:scale-110 hover:rotate-12 shadow-lg shadow-pink-500/50"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>
        </div>

        <div
          class="flex flex-col items-center text-lg tracking-widest space-y-1 text-cyan-400 font-mono pb-4"
        >
          <span>S</span><span>I</span><span>M</span><span>P</span><span>L</span><span>E</span>
        </div>
      </div>
    </Transition>

    <!-- Center Text -->
    <div class="flex-1 flex">
      <Transition name="fade" mode="out-in">
        <div v-if="isAdvanced" class="w-full bg-gray-850 rounded-2xl">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-6 text-cyan-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              Configuration Manager
            </h2>
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-400">{{ config.configs.length }} files</span>
              <span v-if="config.pendingCount" class="text-sm text-amber-400"
                >{{ config.pendingCount }} modified</span
              >
            </div>
          </div>

          <!-- Search and Deploy Actions -->
          <div class="flex flex-col md:flex-row gap-4 !mt-2 !mb-2">
            <!-- Search bar -->
            <div class="relative flex-grow">
              <div
                class="absolute inset-y-0 right-2 w-10 flex items-center justify-center pointer-events-none"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-5 h-5 text-gray-400"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                  />
                </svg>
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search configurations..."
                class="block w-full pl-10 pr-4 py-3 text-base border-1 border-gray-700 rounded-xl bg-gray-800 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
              />
            </div>

            <!-- Deploy bar -->
            <div class="flex items-center gap-2 bg-gray-800 p-2 rounded-md border border-gray-700">
              <div class="flex items-center">
                <span class="text-sm text-gray-300 mr-2">Pending changes:</span>
                <span
                  :class="config.pendingCount ? 'text-amber-400' : 'text-green-400'"
                  class="text-sm font-medium"
                >
                  {{ config.pendingCount || 'None' }}
                </span>
              </div>
              <button
                @click="showDeployUI"
                class="ml-4 px-3 py-1 bg-cyan-600 text-white rounded-md hover:bg-cyan-500 disabled:bg-gray-600 disabled:text-gray-400 disabled:cursor-not-allowed flex items-center gap-1"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                  />
                </svg>
                Deploy
              </button>
              <button
                @click="refreshConfigs"
                class="px-3 py-1 bg-gray-700 text-white rounded-md hover:bg-gray-600 flex items-center gap-1"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                  />
                </svg>
                Refresh
              </button>
            </div>
          </div>

          <!-- File Tree -->
          <div
            class="file-tree bg-gray-950 rounded-lg p-4 shadow-lg overflow-auto max-h-[60vh] border border-gray-700"
          >
            <div v-if="config.loading" class="flex justify-center items-center py-8">
              <svg
                class="animate-spin h-8 w-8 text-cyan-500"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
            </div>
            <div v-else-if="config.error" class="text-red-400 text-center py-4">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-8 mx-auto mb-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
              <p>{{ config.error }}</p>
              <button @click="config.loadConfigs" class="text-cyan-400 hover:underline mt-2">
                Try Again
              </button>
            </div>
            <div
              v-else-if="filteredRootFiles.length === 0 && filteredFolderTree.length === 0"
              class="py-8 text-center text-gray-400"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-8 mx-auto mb-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                />
              </svg>
              <p>No matching files found</p>
            </div>
            <ul v-else class="list-none">
              <!-- root-level files -->
              <li
                v-for="file in filteredRootFiles"
                :key="file.fullPath"
                class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-700/30 transition-colors duration-200 cursor-pointer group"
                @click="navigateToConfig(file.fullPath)"
              >
                <div class="flex items-center gap-3">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="w-5 h-5 text-cyan-400/80 flex-shrink-0"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                    />
                  </svg>
                  <div class="min-w-0">
                    <span class="text-base font-medium text-gray-100 truncate">{{
                      file.name
                    }}</span>
                    <span class="block text-xs text-gray-400 mt-0.5">{{
                      formatDate(file.lastModified)
                    }}</span>
                  </div>
                </div>
                <span
                  class="px-2.5 py-1 text-xs font-medium rounded-full tracking-wide uppercase"
                  :class="
                    file.status === 'ok'
                      ? 'bg-green-900/30 text-green-400'
                      : 'bg-red-900/30 text-red-400'
                  "
                >
                  {{ file.status }}
                </span>
              </li>
              <!-- folders -->
              <TreeNode
                v-for="node in filteredFolderTree"
                :key="node.name"
                :node="node"
                :depth="0"
                :search-query="searchQuery"
                @navigate="navigateToConfig"
              />
            </ul>
          </div>

          <div class="w-full">
        <component
:is="dynamicComponent" v-if="showDeployComponent" />
      </div>
        </div>
        <div v-else class="buttons-container w-full h-full">

          <div class=" bg-gray-950 p-6 rounded-lg border border-cyan-500 shadow-lg">
            <!-- Title -->
            <h2 class="text-4xl font-bold text-cyan-400 mb-4 flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-15 w-15 text-pink-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 17v-2m3 2v-4m3 4v-6"
                />
              </svg>
              Essential Files
            </h2>

            <!-- Search -->
            <div class="mb-4">
              <input
                v-model="searchQuery2"
                type="text"
                placeholder="Search files..."
                class="w-full px-4 py-2 rounded-md bg-gray-800 text-cyan-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-pink-500"
              />
            </div>

            <!-- File List -->
            <div class="space-y-2 max-h-[300px] overflow-y-auto">
              <button
                v-for="file in filteredFiles"
                :key="file.name"
                @click="navigateToConfig(file.path)"
                class="w-full flex items-center  justify-between px-4 py-2 bg-gray-900 hover:bg-gray-800 text-left border border-gray-700 rounded-md transition-colors duration-200 text-cyan-100 hover:text-pink-400 group"
              >
                <div class="flex items-center gap-2">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-cyan-400 group-hover:text-pink-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 4h16v16H4V4z"
                    />
                  </svg>
                  <span class="text-lg">{{ file.name }}</span>
                </div>
                <span class="text-xs text-gray-500 group-hover:text-gray-300">{{ file.path }}</span>
              </button>
            </div>
          </div>
          <div class="mt-6 flex justify-end">
          <button
            @click="showDeployUI"
            class="px-5 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-500 transition-colors duration-300 shadow-lg shadow-pink-400/30 flex items-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Deploy Changes
          </button>
</div>
      <div class="w-full">
        <component
:is="dynamicComponent" v-if="showDeployComponent" />
      </div>

        </div>
      </Transition>
    </div>

    <Transition name="slide-right">
      <div
        v-if="!isAdvanced"
        class="w-10 bg-gradient-to-b from-black via-gray-900 to-black border-l-4 border-pink-500 flex flex-col items-center justify-between shadow-inner shadow-pink-500/20"
      >
        <div class="flex-1 flex items-center justify-center">
          <button
            @click="toggleMode"
            class="bg-cyan-600 hover:bg-cyan-500 p-3 rounded-full transition-all duration-300 transform hover:scale-110 hover:-rotate-12 shadow-lg shadow-cyan-500/50"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>
        </div>

        <div
          class="flex flex-col items-center text-lg tracking-widest space-y-1 text-pink-400 font-mono pb-4"
        >
          <span>A</span><span>D</span><span>V</span><span>A</span><span>N</span><span>C</span
          ><span>E</span><span>D</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, defineComponent, h } from 'vue'
import { useRouter } from 'vue-router'
//import { API } from '@/config/index'
import { useRightSidebarStore } from '@/stores/sidebar.ts'
import { useConfigStore } from '@/stores/config'
import type { Component } from 'vue'
const sidebar = useRightSidebarStore()
const config = useConfigStore()

import EssentialDeploy from '@/components/Deploy.vue'

// Dynamically control display
const showDeployComponent = ref(false)
const dynamicComponent = ref<Component | null>(null)

function showDeployUI() {
  showDeployComponent.value = !showDeployComponent.value
  dynamicComponent.value = EssentialDeploy
}

interface Config {
  file_name: string
  status: string
  last_modified: string
  errors: string[]
}

interface FileNode {
  name: string
  status: string
  fullPath: string
  lastModified: string
  errors: string[]
}

interface TreeNodeData {
  name: string
  children: TreeNodeData[]
  files: FileNode[]
}

const searchQuery = ref('')

// Router
const router = useRouter()
const navigateToConfig = (path: string) => {
  router.push(`/dashboard/config?block_id=nginx&section_id=${encodeURIComponent(path)}`)
  sidebar.changeBackgroundImage()
}
const isAdvanced = ref<boolean>(false)

const toggleMode = (): void => {
  isAdvanced.value = !isAdvanced.value
}

const files = ref([
  { name: 'reverse_proxy', path: '/etc/nginx/sites-enabled/reverse_proxy' },
])

const searchQuery2 = ref('')

const filteredFiles = computed(() => {
  return files.value.filter((file) =>
    file.name.toLowerCase().includes(searchQuery.value.toLowerCase()),
  )
})

// Format date
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  try {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date)
  } catch {
    return dateString
  }
}

// Build tree and cache
function buildTree(list: Config[]) {
  const root: {
    __children: Record<string, any>
    __files: FileNode[]
  } = { __children: {}, __files: [] }

  list.forEach((cfg) => {
    const parts = cfg.file_name.replace(/^\//, '').split('/')
    const fileName = parts.pop()!

    // If no directory structure, add to root
    if (parts.length === 0) {
      root.__files.push({
        name: fileName,
        status: cfg.status,
        fullPath: cfg.file_name,
        lastModified: cfg.last_modified,
        errors: cfg.errors || [],
      })
      return
    }

    // Navigate through directory structure
    let curr = root
    parts.forEach((p) => {
      if (!curr.__children[p]) curr.__children[p] = { __children: {}, __files: [] }
      curr = curr.__children[p]
    })

    // Add file to current directory
    curr.__files.push({
      name: fileName,
      status: cfg.status,
      fullPath: cfg.file_name,
      lastModified: cfg.last_modified,
      errors: cfg.errors || [],
    })
  })

  const convert = (
    name: string,
    node: {
      __children: Record<string, any>
      __files: FileNode[]
    },
  ): TreeNodeData => ({
    name,
    children: Object.entries(node.__children)
      .sort((a, b) => a[0].localeCompare(b[0]))
      .map(([k, v]) => convert(k, v)),
    files: node.__files.sort((a, b) => a.name.localeCompare(b.name)),
  })

  const folderTree = Object.entries(root.__children)
    .sort((a, b) => a[0].localeCompare(b[0]))
    .map(([k, v]) => convert(k, v))

  const rootFiles = root.__files.sort((a, b) => a.name.localeCompare(b.name))
  return { folderTree, rootFiles }
}

const tree = computed(() => buildTree(config.configs))
const folderTree = computed(() => tree.value.folderTree)
const rootFiles = computed(() => tree.value.rootFiles)

// Filter tree based on search
const filteredRootFiles = computed(() => {
  if (!searchQuery.value) return rootFiles.value
  return rootFiles.value.filter(
    (file) =>
      file.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      file.fullPath.toLowerCase().includes(searchQuery.value.toLowerCase()),
  )
})

const filteredFolderTree = computed(() => {
  if (!searchQuery.value) return folderTree.value

  // Helper function to filter TreeNodeData
  const filterTree = (node: TreeNodeData): TreeNodeData | null => {
    // Create a copy with filtered children and files
    const filteredNode = { ...node }

    // Filter files
    filteredNode.files = node.files.filter(
      (file) =>
        file.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        file.fullPath.toLowerCase().includes(searchQuery.value.toLowerCase()),
    )

    // Filter children recursively
    filteredNode.children = node.children
      .map((child) => filterTree(child))
      .filter((child): child is TreeNodeData => child !== null)

    // Return node if it has matching files or children with matches
    if (
      filteredNode.files.length > 0 ||
      filteredNode.children.length > 0 ||
      node.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    ) {
      return filteredNode
    }
    return null
  }

  // Apply filter to each root node
  return folderTree.value
    .map((node) => filterTree(node))
    .filter((node): node is TreeNodeData => node !== null)
})

// TreeNode component with search capability and proper indentation
const TreeNode = defineComponent({
  name: 'TreeNode',
  props: {
    node: {
      type: Object as () => TreeNodeData,
      required: true,
    },
    depth: {
      type: Number,
      default: 0,
    },
    searchQuery: {
      type: String,
      default: '',
    },
  },
  emits: ['navigate'],
  setup(props, { emit }) {
    const open = ref(true)
    const toggle = (e: Event) => {
      e.stopPropagation()
      open.value = !open.value
    }

    return () => {
      if (!props.node) return null

      const currentDepth = props.depth
      const folderIndent = `${currentDepth * 1}vw`
      const fileIndent = `${(currentDepth + 1) * 1}vw`

      const nodes: ReturnType<typeof h>[] = []

      // Folder header
      nodes.push(
        h(
          'li',
          {
            class: [
              'flex items-center justify-between py-2.5 px-3 rounded-lg',
              'hover:bg-gray-700/30 transition-colors cursor-pointer group',
            ],
            style: { paddingLeft: folderIndent },
          },
          [
            h(
              'div',
              {
                class: 'flex items-center gap-3 flex-grow',
                onClick: toggle,
              },
              [
                // Chevron icon
                h(
                  'svg',
                  {
                    class: [
                      'w-5 h-5 text-gray-400 flex-shrink-0 transition-transform transform',
                      'group-hover:text-cyan-400',
                      open.value ? 'rotate-90' : '',
                    ],
                    xmlns: 'http://www.w3.org/2000/svg',
                    fill: 'none',
                    viewBox: '0 0 24 24',
                    stroke: 'currentColor',
                    'stroke-width': '1.5',
                  },
                  h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    d: 'M8.25 4.5l7.5 7.5-7.5 7.5',
                  }),
                ),
                // Folder icon
                h(
                  'svg',
                  {
                    class: 'w-5 h-5 text-amber-400/80 flex-shrink-0',
                    xmlns: 'http://www.w3.org/2000/svg',
                    fill: 'none',
                    viewBox: '0 0 24 24',
                    stroke: 'currentColor',
                    'stroke-width': '1.5',
                  },
                  h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    d: 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776',
                  }),
                ),
                h(
                  'span',
                  {
                    class: 'text-base font-medium text-gray-100 truncate',
                  },
                  props.node.name,
                ),
              ],
            ),
            h(
              'span',
              {
                class: [
                  'px-2 py-1 text-xs font-medium rounded-full',
                  'bg-gray-700/50 text-gray-300',
                  'group-hover:bg-cyan-500/20 group-hover:text-cyan-400',
                ],
              },
              `${props.node.files.length} items`,
            ),
          ],
        ),
      )

      if (open.value) {
        const items: ReturnType<typeof h>[] = []

        // Files in this folder
        props.node.files.forEach((file) => {
          items.push(
            h(
              'li',
              {
                class: [
                  'flex items-center justify-between py-2 px-3 rounded-lg',
                  'hover:bg-gray-700/30 transition-colors cursor-pointer',
                  'group-file',
                ],
                style: { paddingLeft: fileIndent },
                onClick: () => emit('navigate', file.fullPath),
              },
              [
                h('div', { class: 'flex items-center gap-3' }, [
                  // File icon
                  h(
                    'svg',
                    {
                      class: 'w-5 h-5 text-cyan-400/80 flex-shrink-0',
                      xmlns: 'http://www.w3.org/2000/svg',
                      fill: 'none',
                      viewBox: '0 0 24 24',
                      stroke: 'currentColor',
                      'stroke-width': '1.5',
                    },
                    h('path', {
                      'stroke-linecap': 'round',
                      'stroke-linejoin': 'round',
                      d: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                    }),
                  ),
                  h('div', { class: 'min-w-0' }, [
                    h(
                      'span',
                      {
                        class: 'text-sm font-medium text-gray-100 truncate',
                      },
                      file.name,
                    ),
                    h(
                      'div',
                      { class: 'text-xs text-gray-400 mt-0.5' },
                      formatDate(file.lastModified),
                    ),
                  ]),
                ]),
                h(
                  'span',
                  {
                    class: [
                      'px-2.5 py-1 text-xs font-medium rounded-full',
                      'tracking-wide uppercase',
                      file.status === 'ok'
                        ? 'bg-green-900/30 text-green-400'
                        : 'bg-red-900/30 text-red-400',
                    ],
                  },
                  file.status,
                ),
              ],
            ),
          )
        })

        // Child folders
        props.node.children.forEach((child) => {
          items.push(
            h(TreeNode, {
              node: child,
              depth: currentDepth + 1,
              searchQuery: props.searchQuery,
              onNavigate: (path: string) => emit('navigate', path),
            }),
          )
        })

        nodes.push(h('ul', { class: 'list-none space-y-1' }, items))
      }

      return h('ul', { class: 'list-none space-y-1.5' }, nodes)
    }
  },
})

// Load configs

// Refresh configs
const refreshConfigs = () => {
  config.loadConfigs()
}

onMounted(config.loadConfigs)
</script>

<style scoped>
/* === Slide Transitions for Sidebars === */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition:
    transform 0.7s ease-in-out,
    opacity 0.7s ease-in-out;
}

/* Left Sidebar */
.slide-left-enter-from,
.slide-right-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}
.slide-left-enter-to,
.slide-right-leave-from {
  transform: translateX(0%);
  opacity: 1;
}
.slide-left-leave-from {
  transform: translateX(0%);
  opacity: 1;
}
.slide-left-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}

/* Right Sidebar */
.slide-right-enter-from,
.slide-left-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
.slide-right-enter-to,
.slide-left-leave-from {
  transform: translateX(0%);
  opacity: 1;
}
.slide-right-leave-from {
  transform: translateX(0%);
  opacity: 1;
}
.slide-right-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* === Center Fade === */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease-in-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.fade-enter-to,
.fade-leave-from {
  opacity: 1;
}

/* === Drop Shadow Utility === */
.drop-shadow-lg {
  filter: drop-shadow(0 10px 8px rgba(0, 255, 255, 0.3))
    drop-shadow(0 4px 3px rgba(255, 0, 255, 0.2));
}
</style>
