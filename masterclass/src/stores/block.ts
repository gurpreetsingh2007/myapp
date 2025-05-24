import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API } from '@/config/index'
interface JsonDataType {
  id?: string
  json_data?: string
  success?: boolean
  // other fields...
}
export const useJsonDataStore = defineStore('jsonData', () => {
  // State
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const jsonData = ref<JsonDataType>({})

  // Getters
  const hasError = computed(() => error.value !== null)
  const isDataLoaded = computed(() => jsonData.value !== null)

  // Helper for fetch with error handling
  async function fetchWithHandling(url: string, options = {}) {
    const response = await fetch(url, options)
    const contentType = response.headers.get('content-type')
    const isJson = contentType && contentType.includes('application/json')
    const data = isJson ? await response.json() : null

    if (!response.ok) {
      const message = data?.error || response.statusText
      throw new Error(message)
    }

    return data
  }

  async function fetchJsonData(path: string, id: string) {
    isLoading.value = true
    error.value = null
    if (!id) {
      isLoading.value = false
      jsonData.value.success = false
      return ''
    }
    const url = new URL(`${API}/credentials/get/json`)
    url.searchParams.append('path', encodeURIComponent(path))
    url.searchParams.append('id', id)

    try {
      const data = await fetchWithHandling(url.toString(), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      jsonData.value = data

      return data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'

      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateJsonData(id: string, path: string, data: string) {
    isLoading.value = true
    error.value = null

    const url = new URL(`${API}/credentials/update/json`)
    url.searchParams.append('id', id)
    url.searchParams.append('path', encodeURIComponent(path))

    try {
      const response = await fetchWithHandling(url.toString(), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ path, id, data }),
      })

      return response
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'unknow shit'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    isLoading,
    error,
    jsonData,

    // Getters
    hasError,
    isDataLoaded,

    // Actions
    fetchJsonData,
    updateJsonData,
  }
})
