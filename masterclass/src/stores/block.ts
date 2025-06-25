import { defineStore } from 'pinia'
import { reactive, ref, computed } from 'vue'
import { API } from '@/config/index'
import { useConfigStore } from '@/stores/config'
import { useModifiedFilesStore } from '@/stores/modified'

interface JsonDataType {
  id?: string
  json_data?: string
  success?: boolean
}

export const useJsonDataStore = defineStore('jsonData', () => {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const jsonData = reactive<JsonDataType>({
    id: '',
    json_data: '',
    success: false,
  })

  const hasError = computed(() => error.value !== null)
  const isDataLoaded = computed(() => !!jsonData.id)

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
      jsonData.success = false
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

      Object.assign(jsonData, data)
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
      useModifiedFilesStore().addFile({ path, service:'nginx' })
      return response
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    isLoading,
    error,
    jsonData,
    hasError,
    isDataLoaded,
    fetchJsonData,
    updateJsonData,
  }
})
/** Types for each block */
interface GeneralItem {
  id: number
  directive: string
  args: string[]
}

interface BackupItem {
  id: number
  directive: string
  source: string
  dest: string
  parameters: Array<{ name: string; value: string }>
}
type UpdatePayload = (GeneralPayload | BackupPayload) & { comment?: string }

interface UpdateResponse {
  success: boolean
  message: string
}
export const useRsnapshotDataStore = defineStore('rsnapshotData', () => {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const rsnapshotData = reactive<{
    general: GeneralItem[]
    backups: BackupItem[]
  }>({
    general: [],
    backups: [],
  })

  const hasError = computed(() => error.value !== null)
  const isDataLoaded = computed(
    () => rsnapshotData.general.length > 0 || rsnapshotData.backups.length > 0,
  )

  /** shared fetch wrapper */
  async function fetchWithHandling(url: string, options: RequestInit = {}) {
    const res = await fetch(url, options)
    const isJson = res.headers.get('content-type')?.includes('application/json')
    const data = isJson ? await res.json() : null

    if (!res.ok) {
      const msg = (data as any)?.error || res.statusText
      throw new Error(msg)
    }
    return data
  }

  /** Load the full rsnapshot config */
  async function fetchRsnapshotData() {
    isLoading.value = true
    error.value = null

    try {
      const url = `${API}/credentials/get/rsnapshotData`
      const data = await fetchWithHandling(url, { method: 'GET' })

      rsnapshotData.general = data.general ?? []
      rsnapshotData.backups = data.backup ?? []
      return data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Create a new GENERAL entry
   */
  async function createGeneralItem(newItem: { directive: string; args: string[] }) {
    isLoading.value = true
    error.value = null

    try {
      const response = await fetch(`${API}/credentials/post/rsnapshotData`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: 'general',
          directive: newItem.directive,
          args: newItem.args,
          comment: 'Created new general item',
        }),
      })

      const result = await response.json()
      if (!response.ok) {
        throw new Error(result.error || 'Failed to create general item')
      }

      // Refresh the list
      await fetchRsnapshotData()
      useModifiedFilesStore().addFile({ path: 'rsnapshot.conf', service:'rsnapshot' })
      return result
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      throw err
    } finally {
      isLoading.value = false
    }
  }
  /**
 * Delete one entry by ID.
 */
async function deleteRsnapshotData(
  id: number,
  comment = 'Deleted rsnapshot entry'
): Promise<UpdateResponse> {
  isLoading.value = true
  error.value     = null

  try {
    const url  = `${API}/credentials/delete/rsnapshotData`
    const body = JSON.stringify({ id, comment })

    const resData = (await fetchWithHandling(url, {
      method:  'DELETE', // or 'DELETE' if your endpoint supports it
      headers: { 'Content-Type': 'application/json' },
      body
    })) as UpdateResponse

    // refresh the config list
    await fetchRsnapshotData()
      useModifiedFilesStore().addFile({ path: 'rsnapshot.conf', service:'rsnapshot' })

    return resData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unknown error'
    throw err
  } finally {
    isLoading.value = false
  }
}

  /**
   * Create a new BACKUP entry
   */
  async function createBackupItem(newItem: {
    directive: string
    source: string
    dest: string
    parameters: Array<{ name: string; value: string }>
  }) {
    isLoading.value = true
    error.value = null

    try {
      const response = await fetch(`${API}/credentials/post/rsnapshotData`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: 'backup',
          directive: newItem.directive,
          source: newItem.source,
          dest: newItem.dest,
          parameters: newItem.parameters,
          comment: 'Created new backup item',
        }),
      })

      const result = await response.json()
      if (!response.ok) {
        throw new Error(result.error || 'Failed to create backup item')
      }

      await fetchRsnapshotData()
      useModifiedFilesStore().addFile({ path: 'rsnapshot.conf', service:'rsnapshot' })
      return result
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update one entry by ID.
   */
  async function updateRsnapshotData(id: number, payload: UpdatePayload): Promise<UpdateResponse> {
    isLoading.value = true
    error.value = null

    try {
      const url = `${API}/credentials/update/rsnapshotData`
      const body = JSON.stringify({ id, ...payload })

      const resData = (await fetchWithHandling(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body,
      })) as UpdateResponse

      // mark config dirty so UI knows to re-deploy
      useModifiedFilesStore().addFile({ path: 'rsnapshot.conf', service:'rsnapshot' })

      return resData
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    isLoading,
    error,
    rsnapshotData,
    hasError,
    isDataLoaded,
    fetchRsnapshotData,
    createGeneralItem,
    createBackupItem,
    updateRsnapshotData,
    deleteRsnapshotData
  }
})
