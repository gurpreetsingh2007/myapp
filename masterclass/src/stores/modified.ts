import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API } from '@/config/index'

export interface ModifiedFile {
  path: string
  service: string
}

export const useModifiedFilesStore = defineStore('modifiedFiles', () => {
  const files = ref<ModifiedFile[]>([])

  const count = computed(() => files.value.length)

  const filesByService = computed(() => {
    const grouped: Record<string, ModifiedFile[]> = {}
    for (const file of files.value) {
      if (!grouped[file.service]) grouped[file.service] = []
      grouped[file.service].push(file)
    }
    return grouped
  })

  async function fetchFiles() {
    try {
      const res = await fetch(`${API}/credentials/get/modifiedFiles`, {
        method: 'GET',
        credentials: 'include'
      })
      const data = await res.json()
      if (data.success && Array.isArray(data.data)) {
        files.value = data.data
      } else {
        console.warn('Invalid data format received:', data)
      }
    } catch (err) {
      console.error('Failed to fetch modified files:', err)
    }
  }

  function addFile(file: ModifiedFile) {
    const exists = files.value.some(
      f => f.path === file.path && f.service === file.service
    )
    if (!exists) {
      files.value.push(file)
    }
  }

  function removeFile(path: string, service: string) {
    files.value = files.value.filter(
      f => !(f.path === path && f.service === service)
    )
  }

  function clearFiles() {
    files.value = []
  }

  return {
    files,
    count,
    filesByService,
    addFile,
    removeFile,
    clearFiles,
    fetchFiles
  }
})
