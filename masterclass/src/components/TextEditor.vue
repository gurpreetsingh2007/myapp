<template>
  <div class="flex flex-col h-full w-full bg-[var(--bg-color)]">
    <!-- Header -->
    <div
      class="p-4 flex items-center justify-between border-b border-[#00f0ff] bg-black/50 backdrop-blur-sm shadow-[0_0_20px_rgba(0,240,255,0.1)]"
    >
      <h2
        class="text-xl font-semibold bg-gradient-to-r from-[#00f0ff] to-[#d000ff] bg-clip-text text-transparent"
      >
        EDITOR
      </h2>
      <div class="flex items-center gap-3">
        <div v-if="isLoading" class="flex items-center gap-2 text-[#00f0ff] animate-pulse">
          <div
            class="h-4 w-4 border-2 border-[#00f0ff] border-t-transparent rounded-full animate-spin"
          ></div>
          <span class="text-sm font-mono">INITIALIZING...</span>
        </div>
        <button
          class="px-4 py-2 bg-gradient-to-br from-[#00f0ff] to-[#d000ff] text-black font-bold rounded-md hover:shadow-[0_0_20px_rgba(0,240,255,0.5)] transition-all duration-300 group"
          @click="refreshData"
        >
          <span class="relative z-10">REFRESH BLOCK</span>
          <div
            class="absolute inset-0 rounded-md opacity-0 group-hover:opacity-100 transition-opacity bg-[radial-gradient(circle_at_center,#00f0ff55_0%,transparent_70%)]"
          ></div>
        </button>
        <button
          v-if="isDataLoaded"
          class="px-4 py-2 bg-gradient-to-br from-[#00f0ff] to-[#d000ff] text-black font-bold rounded-md hover:shadow-[0_0_20px_rgba(0,240,255,0.5)] transition-all duration-300 group"
          @click="save"
        >
          <span class="relative z-10">SAVE</span>
          <div
            class="absolute inset-0 rounded-md opacity-0 group-hover:opacity-100 transition-opacity bg-[radial-gradient(circle_at_center,#00f0ff55_0%,transparent_70%)]"
          ></div>
        </button>
      </div>
    </div>

    <!-- Error notification -->
    <div
      v-if="hasError"
      class="px-4 py-3 flex items-center bg-[#d000ff15] border-b border-[#d000ff] text-[#ff006a] animate-pulse-fast"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 mr-2 text-[#ff006a]"
        viewBox="0 0 20 20"
        fill="currentColor"
      >
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"
        />
      </svg>
      <span class="font-mono text-sm">{{ error }}</span>
    </div>

    <!-- Editor container -->
    <div class="flex-grow flex flex-col relative bg-gradient-to-br from-black/80 to-[#0a0a2a]/80">
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

      <div v-else
        class="absolute inset-0 border-[1px] border-[#00f0ff33] shadow-[inset_0_0_30px_rgba(0,240,255,0.1)]"
      >
        <div ref="editorContainer" class="h-full w-full neon-editor"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useJsonDataStore } from '@/stores/block'
import { Path } from '@/stores/path'
import { storeToRefs } from 'pinia'
import { EditorView, basicSetup } from 'codemirror'
import { oneDark } from '@codemirror/theme-one-dark'
import { StreamLanguage } from '@codemirror/language'
import { nginx } from '@codemirror/legacy-modes/mode/nginx'
import { debounce } from 'lodash-es'

// CodeMirror config
const nginxLanguage = StreamLanguage.define(nginx)
const editorContainer = ref<HTMLElement | null>(null)
let view: EditorView | null = null
let isProgrammaticChange = false
let isUpdatingFromEditor = false

// Path and store
const info = Path()
const jsonDataStore = useJsonDataStore()
const { isLoading, error, jsonData } = storeToRefs(jsonDataStore)

// Computed state
const hasError = computed(() => error.value !== null)
const isDataLoaded = computed(() => jsonData.value !== null && jsonData.value.success !== false)

// Refresh button handler
function refreshData() {
  jsonDataStore.fetchJsonData(info.info.sectionId, info.info.store_number)
}



// Properly handles optional/invalid directives
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
    if (data.directive == 'server_name') {
      result += ' ' + data.args.join('\n\t\t\t  ')
    }
    else {
      result += ' ' + data.args.join(' ')
    }
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

type NginxEntry = {
  directive: string;
  line: number;
  args: string[];
  block?: NginxEntry[];
};

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

// Save button handler
function save() {
  if (info.info.store_number === jsonData.value.id && view) {
    const content = view.state.doc.toString()
    try {
      jsonDataStore.updateJsonData(info.info.store_number, info.info.sectionId, content)
      isUpdatingFromEditor = true
      jsonData.value.json_data = JSON.stringify(parseNginxBlock(content))
      //isUpdatingFromEditor = false
    } catch (e) {
      console.error('Manual save failed:', e)
    }
  }
}

// Debounced live update handler
const debouncedUpdate = debounce((content: string) => {
  if (info.info.store_number === jsonData.value.id) {
    try {
      jsonDataStore.updateJsonData(info.info.store_number, info.info.sectionId, content)
      isUpdatingFromEditor = true
      jsonData.value.json_data = JSON.stringify(parseNginxBlock(content))
      //isUpdatingFromEditor = false
    } catch (e) {
      console.error('Live update failed:', e)
    }
  }
}, 1000)

// Mount editor
onMounted(() => {
  if (!editorContainer.value) return

  let initial = ''
  try {
    if (jsonData.value?.json_data) {
      const parsed = JSON.parse(jsonData.value.json_data)
      initial = parseDirective(parsed)
    }
  } catch (e) {
    console.error('Initial parse failed:', e)
  }

  view = new EditorView({
    doc: initial,
    extensions: [
      basicSetup,
      nginxLanguage,
      oneDark,
      EditorView.updateListener.of((update) => {
        if (update.docChanged && !isProgrammaticChange) {
          const content = update.state.doc.toString()
          debouncedUpdate(content)
        }
      }),
      EditorView.theme({
        '&': {
          height: '100%',
          fontSize: '14px',
          fontFamily: '"Fira Code", monospace',
          textAlign: 'left',
          '& .cm-content': {
            fontFamily: '"Fira Code", monospace',
            textAlign: 'left',
            paddingLeft: '10px',
            paddingTop: '5px',
            paddingBottom: '5px',
          },
          '& .cm-line': {
            textAlign: 'left',
            padding: '0 !important',
            paddingLeft: '10px !important',
          },
        },
        '.cm-gutters': {
          backgroundColor: 'rgba(0, 0, 0, 0.3) !important',
          color: 'rgba(0, 240, 255, 0.5) !important',
          border: 'none !important',
          borderRight: '1px solid rgba(0, 240, 255, 0.2) !important',
        },
        '.cm-activeLineGutter': {
          backgroundColor: 'rgba(0, 240, 255, 0.1) !important',
          color: '#00f0ff !important',
        },
        '.cm-activeLine': {
          backgroundColor: 'rgba(0, 240, 255, 0.05) !important',
          borderLeft: '2px solid #00f0ff',
        },
        '.cm-cursor': {
          borderLeft: '2px solid #00f0ff !important',
          boxShadow: '0 0 8px #00f0ff',
        },
        '.cm-content': {
          caretColor: '#00f0ff',
        },
        '.cm-scroller': {
          overflow: 'auto',
        },
        '.cm-selectionBackground': {
          backgroundColor: 'rgba(0, 240, 255, 0.15) !important',
        },
        '.cm-matchingBracket': {
          backgroundColor: 'rgba(0, 240, 255, 0.2) !important',
          color: '#00f0ff !important',
        },
      }),
    ],
    parent: editorContainer.value,
  })

  if (!isDataLoaded.value && !isLoading.value) {
    refreshData()
  }
})

// Watch for actual changes in json_data and update editor
watch(
  () => jsonData.value.json_data,
  (newJson) => {
    if (isUpdatingFromEditor) {
      isUpdatingFromEditor = false
      return
    }
    if (!view || jsonData.value?.success === false) return;

    let parsed: any
    try {
      parsed = typeof newJson === 'string' ? JSON.parse(newJson) : null
    } catch (e) {
      console.error('Failed to parse updated JSON:', e)
      return
    }
    if (!parsed) return

    const newContent = parseDirective(parsed)
    const currentContent = view.state.doc.toString()

    if (currentContent !== newContent) {
      isProgrammaticChange = true
      view.dispatch({
        changes: {
          from: 0,
          to: view.state.doc.length,
          insert: newContent,
        },
      })
      isProgrammaticChange = false
    }
  }
)

// Cleanup
onBeforeUnmount(() => {
  if (view) view.destroy()
})
</script>

<style>
.neon-editor {
  --cm-background: #000000;
  --cm-foreground: #00f0ff;
  --cm-gutter-background: #00000090;
  --cm-cursor: #00f0ff;
  --cm-selection: #00f0ff30;
}
.cm-editor {
  background: var(--cm-background) !important;
  font-family: 'Orbitron', monospace !important;
}

.cm-content {
  color: var(--cm-foreground) !important;
  caret-color: var(--cm-cursor) !important;
}

.cm-gutters {
  background: var(--cm-gutter-background) !important;
  border-right: 1px solid #00f0ff33 !important;
}

.cm-activeLine {
  background: #00f0ff10 !important;
}

.cm-selectionBackground {
  background: var(--cm-selection) !important;
}

/* Animations */
@keyframes pulse-fast {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

@keyframes text-glitch {
  0% {
    text-shadow:
      2px 0 0 #ff00c1,
      -2px 0 0 #00fff9;
  }
  100% {
    text-shadow:
      -2px 0 0 #ff00c1,
      2px 0 0 #00fff9;
  }
}

.animate-pulse-fast {
  animation: pulse-fast 1s infinite;
}

.animate-text-glitch {
  animation: text-glitch 0.3s infinite alternate;
}
</style>
