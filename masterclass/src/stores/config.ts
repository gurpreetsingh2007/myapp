// stores/config.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API } from '@/config/index'

interface Config {
  file_name: string
  status: string
  last_modified: string
  errors: string[]
}

export const useConfigStore = defineStore('config', () => {
  // State
  const configs = ref<Config[]>([])
  const modifiedFiles = ref<Set<string>>(new Set())
  const loading = ref(true)
  const error = ref<string | null>(null)
  const deploying = ref(false)
  const showDeployStatus = ref(false)
  const deploySuccess = ref(false)
  const deployMessage = ref('')

  // Getters
  const pendingCount = computed(() => modifiedFiles.value.size)
  const isModified = (fileName: string) => modifiedFiles.value.has(fileName)

  // Actions
  async function loadConfigs() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API}/credentials/get/index`, {
        method: 'GET',
        credentials: 'include',
      })
      const data = await res.json()
      if (!data.success) throw new Error('Failed to load configurations')
      configs.value = data.message

      data.message.forEach((config: { file_name: string; deployed: string }) => {
      if (config.deployed === 'n') {
        markModified(config.file_name)
      }
      })

    } catch (err) {
      error.value = (err as Error).message
    } finally {
      loading.value = false
    }
  }

  function markModified(fileName: string) {
    modifiedFiles.value.add(fileName)
  }



  return {
    configs,
    modifiedFiles,
    loading,
    error,
    deploying,
    showDeployStatus,
    deploySuccess,
    deployMessage,
    pendingCount,
    isModified,
    loadConfigs,
    markModified
  }
})
