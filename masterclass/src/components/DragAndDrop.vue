<template>
  <!-- Main Container -->
  <div class="flex flex-col h-full w-full bg-[var(--bg-color)] overflow-hidden">
    <!-- Header -->
    <div class="p-4 flex items-center justify-between border-b border-cyan-400 bg-black shadow-md">
      <h2
        class="text-xl font-semibold bg-gradient-to-r from-cyan-400 to-fuchsia-600 bg-clip-text text-transparent"
      >
        EDITOR
      </h2>
      <div class="flex items-center gap-3">
        <div v-if="isLoading" class="flex items-center gap-2 text-cyan-400 animate-pulse">
          <div
            class="h-4 w-4 border-2 border-cyan-400 border-t-transparent rounded-full animate-spin"
          ></div>
          <span class="text-sm font-mono">INITIALIZING...</span>
        </div>
        <button
          class="relative px-4 py-2 font-bold text-black bg-gradient-to-br from-cyan-400 to-fuchsia-600 rounded-md transition duration-300 hover:shadow-[0_0_12px_rgba(0,240,255,0.5)] overflow-hidden"
          @click="refreshData"
        >
          <span class="relative z-10">REFRESH BLOCK</span>
          <div
            class="absolute inset-0 opacity-0 hover:opacity-100 transition-opacity bg-[radial-gradient(circle_at_center,#00f0ff55_0%,transparent_70%)]"
          ></div>
        </button>
        <button
          v-if="isDataLoaded"
          class="relative px-4 py-2 font-bold text-black bg-gradient-to-br from-cyan-400 to-fuchsia-600 rounded-md transition duration-300 hover:shadow-[0_0_12px_rgba(0,240,255,0.5)] overflow-hidden"
          @click="save"
        >
          <span class="relative z-10">SAVE</span>
          <div
            class="absolute inset-0 opacity-0 hover:opacity-100 transition-opacity bg-[radial-gradient(circle_at_center,#00f0ff55_0%,transparent_70%)]"
          ></div>
        </button>
      </div>
    </div>

    <!-- Main Body -->
    <div
      class="flex-grow flex flex-col relative bg-gradient-to-br from-black to-[#0a0a2a] overflow-auto p-4"
    >
      <!-- System Ready Message -->
      <div
        v-if="!isDataLoaded && !isLoading && !hasError"
        class="absolute inset-0 flex flex-col z-[10] items-center justify-center text-[#00f0ff] bg-black/50 backdrop-blur-sm"
      >
        <div class="relative mb-4">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-16 w-16 text-[#00f0ff]"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              style="filter: drop-shadow(0 0 8px #00f0ff)"
            />
          </svg>
        </div>
        <p class="text-center font-mono text-xl animate-text-glitch">
          [ SYSTEM READY ]<br />
          <span class="text-sm text-[#d000ff]">SELECT BLOCK</span>
        </p>
      </div>

      <!-- Directive Nodes -->
      <div v-else class="flex flex-col gap-4">
        <DirectiveNode v-if="jsonRoot" :node="jsonRoot" :depth="0" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { h, ref, watch, defineComponent, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useJsonDataStore } from '@/stores/block'
import { Path } from '@/stores/path'

const info = Path()
const jsonDataStore = useJsonDataStore()
const { isLoading, error, jsonData } = storeToRefs(jsonDataStore)
const jsonRoot = ref<any>(null)
const hasError = computed(() => error.value !== null)
const isDataLoaded = computed(() => jsonData.value !== null && jsonData.value.success !== false)
let isUpdatingFromEditor = false

// Watch for json_data changes
watch(
  () => jsonData.value.json_data,
  (newJson, _, onCleanup) => {
    if (isUpdatingFromEditor) return

    isUpdatingFromEditor = true
    onCleanup(() => {
      isUpdatingFromEditor = false
    })

    try {
      jsonRoot.value = JSON.parse(newJson ?? '{}')
    } catch (e) {
      console.error('Invalid JSON:', e)
    }
  },
  { immediate: true },
)

// Helper function to count nodes
function countNodes(node: any): number {
  if (!node) return 0
  let count = 1
  if (node.block?.length) {
    count += node.block.reduce((sum: number, child: any) => sum + countNodes(child), 0)
  }
  return count
}
function refreshData() {
  jsonDataStore.fetchJsonData(info.info.sectionId, info.info.store_number)
}

function parseDirective(data: any, indent = 0): string {
  const indentStr = '  '.repeat(indent)
  let result = ''
  if (
    !data ||
    !data.directive ||
    typeof data.directive !== 'string' ||
    data.directive.trim() === ''
  )
    return ''

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

// Manual save
function save() {
  if (info.info.store_number === jsonData.value.id) {
    const content = parseDirective(jsonRoot.value)
    try {
      jsonDataStore.updateJsonData(info.info.store_number, info.info.sectionId, content)
      jsonData.value.json_data = JSON.stringify(jsonRoot.value) // no flag set here
    } catch (e) {
      console.error('Manual save failed:', e)
    }
  }
}

// Debounced auto-save
function debounce<T extends (...args: any[]) => void>(fn: T, delay: number) {
  let timeout: ReturnType<typeof setTimeout>
  return (...args: Parameters<T>) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fn(...args), delay)
  }
}

const debouncedUpdate = debounce((content: string) => {
  if (info.info.store_number === jsonData.value.id) {
    try {
      jsonDataStore.updateJsonData(info.info.store_number, info.info.sectionId, content)
      jsonData.value.json_data = JSON.stringify(jsonRoot.value) // no flag set here
      //console.log(JSON.stringify(jsonRoot.value))
    } catch (e) {
      console.error('Live update failed:', e)
    }
  }
}, 1000)

// DirectiveNode
const DirectiveNode = defineComponent({
  name: 'DirectiveNode',
  props: {
    node: { type: Object, required: true },
    depth: { type: Number, default: 0 },
  },
  setup(props) {
    const triggerUpdate = () => {
      const content = parseDirective(jsonRoot.value)
      debouncedUpdate(content)
    }

    const addArg = () => {
      props.node.args.push('')
      triggerUpdate()
    }

    const removeArg = (index: number) => {
      props.node.args.splice(index, 1)
      triggerUpdate()
    }

    const addBlockDirective = () => {
      if (!props.node.block) props.node.block = []
      props.node.block.push({
        directive: '',
        line: 0,
        args: [],
        block: [],
      })
      triggerUpdate()
    }

    // SVG Icons
    const TrashIcon = h(
      'svg',
      {
        xmlns: 'http://www.w3.org/2000/svg',
        fill: 'none',
        viewBox: '0 0 24 24',
        'stroke-width': '1.5',
        stroke: 'currentColor',
        class: 'w-4 h-4',
      },
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        d: 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
      }),
    )

    const PlusIcon = h(
      'svg',
      {
        xmlns: 'http://www.w3.org/2000/svg',
        fill: 'none',
        viewBox: '0 0 24 24',
        'stroke-width': '1.5',
        stroke: 'currentColor',
        class: 'w-4 h-4',
      },
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        d: 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
      }),
    )

    const FolderPlusIcon = h(
      'svg',
      {
        xmlns: 'http://www.w3.org/2000/svg',
        fill: 'none',
        viewBox: '0 0 24 24',
        'stroke-width': '1.5',
        stroke: 'currentColor',
        class: 'w-4 h-4',
      },
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        d: 'M12 10.5v6m3-3H9m4.06-7.19l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
      }),
    )

    return () =>
      h(
        'div',
        {
          class: ['relative group transition-all duration-300'],
          style: {
            marginLeft: `${props.depth * 24}px`,
          },
        },
        [
          // Connection line for nested items
          props.depth > 0 &&
            h('div', {
              class:
                'absolute -left-6 top-6 w-4 h-px bg-gradient-to-r from-cyan-500/50 to-transparent',
            }),

          // Main card
          h(
            'div',
            {
              class: [
                'relative overflow-hidden rounded-xl border  transition-all duration-300',
                'bg-black/40 border-gray-700/50',
                'hover:border-cyan-500/50 hover:shadow-[0_0_20px_rgba(6,182,212,0.2)]',
                'group-hover:bg-black/60',
              ],
            },
            [
              // Gradient overlay
              h('div', {
                class:
                  'absolute inset-0 bg-gradient-to-br from-cyan-500/5 via-transparent to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300',
              }),

              // Content
              h('div', { class: 'relative p-6' }, [
                // Header section
                h('div', { class: 'flex items-center gap-3 mb-4' }, [
                  // Status indicator
                  h(
                    'div',
                    {
                      class:
                        'relative w-3 h-3 rounded-full bg-gradient-to-r from-cyan-400 to-blue-500',
                    },
                    [
                      h('div', {
                        class: 'absolute inset-0 rounded-full bg-cyan-400 animate-ping opacity-75',
                      }),
                    ],
                  ),

                  // Directive input
                  h('input', {
                    class: [
                      'flex-grow bg-transparent border-b border-gray-600 px-2 py-1',
                      'text-xl font-bold text-white placeholder-gray-500',
                      'focus:outline-none focus:border-cyan-400 transition-colors',
                      'font-mono tracking-wider',
                    ],
                    value: props.node.directive,
                    placeholder: 'directive_name',
                    onInput: (e: any) => {
                      props.node.directive = e.target.value
                      triggerUpdate()
                    },
                  }),
                ]),

                // Arguments section
                h('div', { class: 'mb-4' }, [
                  h('div', { class: 'flex items-center justify-between mb-3' }, [
                    h(
                      'span',
                      {
                        class: 'text-md font-mono text-cyan-400 tracking-wider flex items-center',
                      },
                      [h('div', { class: 'w-1 h-4 bg-cyan-400 mr-2' }), 'PARAMETERS'],
                    ),
                    h(
                      'button',
                      {
                        class: [
                          'px-3 py-1 bg-green-500/20 border border-green-500/30 rounded-md',
                          'text-green-400 text-md font-mono hover:bg-green-500/30',
                          'transition-all duration-200 flex items-center gap-2',
                        ],
                        onClick: addArg,
                      },
                      [PlusIcon, 'ADD'],
                    ),
                  ]),

                  // Arguments list
                  h(
                    'div',
                    { class: 'space-y-2' },
                    (props.node.args || []).map((arg: string, index: number) =>
                      h('div', { class: 'flex items-center gap-2' }, [
                        h('div', { class: 'w-2 h-2 bg-gray-500 rounded-full flex-shrink-0' }),
                        h('input', {
                          class: [
                            'bg-gray-800/50 border border-gray-600 rounded-lg px-3 py-2',
                            'text-white placeholder-gray-400 text-md font-mono',
                            'focus:outline-none focus:border-cyan-400 focus:bg-gray-800/70',
                            'transition-all duration-200',
                          ],
                          value: arg,
                          placeholder: 'parameter_value',
                          style: {
                            width: `${Math.max(arg.length + 5 || 1, 10)}ch`,
                            maxWidth: '100%',
                            minWidth: '6ch',
                          },
                          onInput: (e: any) => {
                            props.node.args[index] = e.target.value
                            triggerUpdate()
                          },
                        }),
                        h(
                          'button',
                          {
                            class: [
                              'p-2 bg-red-500/20 border border-red-500/30 rounded-lg',
                              'text-red-400 hover:bg-red-500/30 transition-all duration-200',
                            ],
                            onClick: () => removeArg(index),
                          },
                          TrashIcon,
                        ),
                      ]),
                    ),
                  ),
                ]),

                // Nested directives
                props.node.block?.length
                  ? h(
                      'div',
                      { class: 'space-y-3 pt-4 border-t border-gray-700/50' },
                      props.node.block.map((child: any, i: number) =>
                        h(DirectiveNode, {
                          node: child,
                          depth: props.depth + 1,
                          key: i,
                        }),
                      ),
                    )
                  : null,

                // Add nested directive button
                h('div', { class: 'mt-4 pt-4 border-t border-gray-700/30' }, [
                  h(
                    'button',
                    {
                      class: [
                        'w-full flex items-center justify-center gap-3 px-4 py-3',
                        'bg-purple-500/10 border border-purple-500/30 rounded-lg',
                        'text-purple-400 font-mono text-sm tracking-wider',
                        'hover:bg-purple-500/20 hover:border-purple-400/50',
                        'transition-all duration-200 group/btn',
                      ],
                      onClick: addBlockDirective,
                    },
                    [
                      FolderPlusIcon,
                      h(
                        'span',
                        { class: 'group-hover/btn:text-purple-300 transition-colors' },
                        'ADD NESTED DIRECTIVE',
                      ),
                    ],
                  ),
                ]),
              ]),
            ],
          ),
        ],
      )
  },
})
</script>

<style scoped>
/* Custom shadows for neon glow */
.shadow-neon {
  box-shadow:
    0 0 6px #00f0ff,
    0 0 12px #d000ff,
    0 0 24px #00f0ff;
}

.shadow-neon-hover {
  box-shadow:
    0 0 12px #00f0ff,
    0 0 24px #d000ff,
    0 0 48px #00f0ff;
}

/* Smooth animations */
@keyframes pulse-glow {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse-glow {
  animation: pulse-glow 2s ease-in-out infinite;
}
</style>
