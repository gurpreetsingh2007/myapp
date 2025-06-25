// stores/config.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API } from '@/config/index'

import { useModifiedFilesStore } from '@/stores/modified'

interface Config {
  file_name: string
  status: string
  last_modified: string
  errors: string[]
}

export const useConfigStore = defineStore('config', () => {
  // State
  const configs = ref<Config[]>([])

  const loading = ref(true)
  const error = ref<string | null>(null)
  const deploying = ref(false)
  const showDeployStatus = ref(false)
  const deploySuccess = ref(false)
  const deployMessage = ref('')



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

      useModifiedFilesStore().addFile({ path: config.file_name, service:'nginx' })
      }
      })

    } catch (err) {
      error.value = (err as Error).message
    } finally {
      loading.value = false
    }
  }





  return {
    configs,
    loading,
    error,
    deployMessage,
    loadConfigs
  }
})
