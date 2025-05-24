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
    } catch (err) {
      error.value = (err as Error).message
    } finally {
      loading.value = false
    }
  }

  function markModified(fileName: string) {
    modifiedFiles.value.add(fileName)
  }

  async function deployChanges() {
    if (!modifiedFiles.value.size) return

    deploying.value = true
    showDeployStatus.value = true

    try {
      // Simulate API call
      await new Promise((resolve) => setTimeout(resolve, 2000))

      // Update statuses for modified files
      configs.value = configs.value.map((config) => ({
        ...config,
        status: modifiedFiles.value.has(config.file_name) ? 'ok' : config.status,
      }))

      deploySuccess.value = true
      deployMessage.value = `Successfully deployed ${modifiedFiles.value.size} file(s).`
      modifiedFiles.value.clear()
    } catch (err) {
      deploySuccess.value = false
      deployMessage.value = (err as Error).message || 'Deployment failed'
    } finally {
      deploying.value = false
    }
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
    markModified,
    deployChanges,
  }
})
