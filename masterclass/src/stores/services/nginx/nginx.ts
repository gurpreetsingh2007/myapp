import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { API } from '@/config'
export interface Server {
  server_id: number
  server_title: string
  port: number
  ssl_enabled: boolean
  is_mtls: boolean
  is_http2: boolean
  server_name: string
  locations: Location[]
  directives: Parameter[]
  ssl_certificate?: string
  ssl_certificate_key?: string
  ssl_client_certificate?: string
  ssl_verify_client?: string
  is_websocket_enabled?: boolean
}

export interface Location {
  location_id?: number
  path: string
  proxy_pass: string
  directives: Parameter[]
}

export interface Certificate {
  cert_id?: number
  cert_path: string
  cert_content: string
  private_key: string
  valid_from: string
  valid_to: string
  is_self_signed: boolean
}

export interface Parameter {
  param_id?: number
  name: string
  value: string
  is_common?: boolean
}

export interface Stats {
  active_connections: number
  accepted_connections: number
  handled_connections: number
  total_requests: number
  reading: number
  writing: number
  waiting: number
}

export interface HealthStatus {
  success: boolean
  status: 'healthy' | 'unhealthy'
  error?: string
}

export interface ApiResponse {
  success: boolean
  data?: any
  error?: string
}

// Base API URL
const API_BASE_URL = API

// Define types

// Helper function to make API calls
async function apiCall<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`

  const defaultOptions: RequestInit = {
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
  }

  const response = await fetch(url, { ...defaultOptions, ...options })

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({ error: 'Network error' }))
    throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`)
  }

  return (await response.json()) as T
}

export const useNginxStore = defineStore('nginx', () => {
  // =============================================
  // STATE
  // =============================================

  // Servers
  const servers = ref<Server[]>([])
  const serversLoading = ref(false)
  const serversError = ref<string | null>(null)

  // Certificates
  const certificates = ref<Certificate[]>([])
  const certificatesLoading = ref(false)
  const certificatesError = ref<string | null>(null)

  // Parameters
  const parameters = ref<Parameter[]>([])
  const locationParameters = ref<Parameter[]>([])
  const parametersLoading = ref(false)
  const parametersError = ref<string | null>(null)

  // Statistics and Health
  const stats = ref<Stats | null>(null)
  const health = ref<HealthStatus | null>(null)
  const statusLoading = ref(false)

  // UI State
  const selectedServer = ref<Server | null>(null)
  const selectedCertificate = ref<Certificate | null>(null)

  // =============================================
  // COMPUTED
  // =============================================

  const sslEnabledServers = computed(() => servers.value.filter((server) => server.ssl_enabled))

  const http2EnabledServers = computed(() => servers.value.filter((server) => server.is_http2))

  const selfSignedCertificates = computed(() =>
    certificates.value.filter((cert) => cert.is_self_signed),
  )

  const expiringSoonCertificates = computed(() => {
    const thirtyDaysFromNow = new Date()
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30)

    return certificates.value.filter((cert) => {
      if (!cert.valid_to) return false
      const validTo = new Date(cert.valid_to)
      return validTo <= thirtyDaysFromNow && validTo >= new Date()
    })
  })

  const commonParameters = computed(() => parameters.value.filter((param) => param.is_common))

  // =============================================
  // SERVER ACTIONS
  // =============================================

  async function fetchServers(): Promise<Server[]> {
    serversLoading.value = true
    serversError.value = null

    try {
      const response = await apiCall<{ data: Server[] }>('/nginx/servers')
      servers.value = response.data || []
      return servers.value
    } catch (error) {
      serversError.value = (error as Error).message
      console.error('Failed to fetch servers:', error)
      throw error
    } finally {
      serversLoading.value = false
    }
  }

  async function addServer(serverData: Omit<Server, 'server_id'>): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>('/nginx/servers', {
        method: 'POST',
        body: JSON.stringify(serverData),
      })

      if (response.success) {
        await fetchServers()
        return response
      } else {
        throw new Error(response.error || 'Failed to add server')
      }
    } catch (error) {
      console.error('Failed to add server:', error)
      throw error
    }
  }

  async function updateServer(serverId: number, serverData: Partial<Server>): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>(`/nginx/servers/${serverId}`, {
        method: 'PUT',
        body: JSON.stringify(serverData),
      })

      if (response.success) {
        await fetchServers()
        return response
      } else {
        throw new Error(response.error || 'Failed to update server')
      }
    } catch (error) {
      console.error('Failed to update server:', error)
      throw error
    }
  }

  async function deleteServer(serverId: number): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>(`/nginx/servers/${serverId}`, {
        method: 'DELETE',
      })

      if (response.success) {
        servers.value = servers.value.filter((server) => server.server_id !== serverId)
        if (selectedServer.value?.server_id === serverId) {
          selectedServer.value = null
        }
        return response
      } else {
        throw new Error(response.error || 'Failed to delete server')
      }
    } catch (error) {
      console.error('Failed to delete server:', error)
      throw error
    }
  }

  function selectServer(server: Server | null): void {
    selectedServer.value = server
  }

  function clearSelectedServer(): void {
    selectedServer.value = null
  }

  // =============================================
  // CERTIFICATE ACTIONS
  // =============================================

  async function fetchCertificates(): Promise<Certificate[]> {
    certificatesLoading.value = true
    certificatesError.value = null

    try {
      const response = await apiCall<{ data: Certificate[] }>('/nginx/certificates')
      certificates.value = response.data || []
      return certificates.value
    } catch (error) {
      certificatesError.value = (error as Error).message
      console.error('Failed to fetch certificates:', error)
      throw error
    } finally {
      certificatesLoading.value = false
    }
  }

  async function addCertificate(
    certificateData: FormData | Omit<Certificate, 'cert_id'>,
  ): Promise<ApiResponse> {
    try {
      const isFormData = certificateData instanceof FormData

      const options: RequestInit = {
        method: 'POST',
      }

      if (isFormData) {
        options.body = certificateData as FormData
      } else {
        options.body = JSON.stringify(certificateData)
        options.headers = {
          'Content-Type': 'application/json',
        }
      }

      const response = await apiCall<ApiResponse>('/nginx/certificates', options)

      if (response.success) {
        await fetchCertificates()
        return response
      } else {
        throw new Error(response.error || 'Failed to add certificate')
      }
    } catch (error) {
      console.error('Failed to add certificate:', error)
      throw error
    }
  }

  async function updateCertificate(
    certId: number,
    certificateData: FormData | Partial<Certificate>,
  ): Promise<ApiResponse> {
    try {
      const isFormData = certificateData instanceof FormData

      const options: RequestInit = {
        method: 'PUT',
      }

      if (isFormData) {
        options.body = certificateData as FormData
      } else {
        options.body = JSON.stringify(certificateData)
        options.headers = {
          'Content-Type': 'application/json',
        }
      }

      const response = await apiCall<ApiResponse>(`/nginx/certificates/${certId}`, options)

      if (response.success) {
        await fetchCertificates()
        return response
      } else {
        throw new Error(response.error || 'Failed to update certificate')
      }
    } catch (error) {
      console.error('Failed to update certificate:', error)
      throw error
    }
  }

  async function deleteCertificate(certId: number): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>(`/nginx/certificates/${certId}`, {
        method: 'DELETE',
      })

      if (response.success) {
        certificates.value = certificates.value.filter((cert) => cert.cert_id !== certId)
        if (selectedCertificate.value?.cert_id === certId) {
          selectedCertificate.value = null
        }
        return response
      } else {
        throw new Error(response.error || 'Failed to delete certificate')
      }
    } catch (error) {
      console.error('Failed to delete certificate:', error)
      throw error
    }
  }

  function selectCertificate(certificate: Certificate | null): void {
    selectedCertificate.value = certificate
  }

  function clearSelectedCertificate(): void {
    selectedCertificate.value = null
  }

  // =============================================
  // PARAMETER ACTIONS
  // =============================================

  async function fetchParameters(): Promise<{
    parameters: Parameter[]
    locationParameters: Parameter[]
  }> {
    parametersLoading.value = true
    parametersError.value = null

    try {
      const [allParams, locationParams] = await Promise.all([
        apiCall<{ data: Parameter[] }>('/nginx/parameters'),
        apiCall<{ data: Parameter[] }>('/nginx/parameters/locations'),
      ])

      parameters.value = allParams.data || []
      locationParameters.value = locationParams.data || []

      return {
        parameters: parameters.value,
        locationParameters: locationParameters.value,
      }
    } catch (error) {
      parametersError.value = (error as Error).message
      console.error('Failed to fetch parameters:', error)
      throw error
    } finally {
      parametersLoading.value = false
    }
  }

  async function addParameter(parameterData: Omit<Parameter, 'param_id'>): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>('/nginx/parameters', {
        method: 'POST',
        body: JSON.stringify(parameterData),
      })

      if (response.success) {
        await fetchParameters()
        return response
      } else {
        throw new Error(response.error || 'Failed to add parameter')
      }
    } catch (error) {
      console.error('Failed to add parameter:', error)
      throw error
    }
  }

  // =============================================
  // CONFIG MANAGEMENT ACTIONS
  // =============================================

  async function loadCertificatesFromJson(jsonFile: string): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>('/nginx/config/load-certificates', {
        method: 'POST',
        body: JSON.stringify({ json_file: jsonFile }),
      })

      if (response.success) {
        await fetchCertificates()
        return response
      } else {
        throw new Error(response.error || 'Failed to load certificates from JSON')
      }
    } catch (error) {
      console.error('Failed to load certificates from JSON:', error)
      throw error
    }
  }

  async function loadServersFromJson(jsonFile: string): Promise<ApiResponse> {
    try {
      const response = await apiCall<ApiResponse>('/nginx/config/load-servers', {
        method: 'POST',
        body: JSON.stringify({ json_file: jsonFile }),
      })

      if (response.success) {
        await fetchServers()
        return response
      } else {
        throw new Error(response.error || 'Failed to load servers from JSON')
      }
    } catch (error) {
      console.error('Failed to load servers from JSON:', error)
      throw error
    }
  }

  async function exportConfiguration(): Promise<void> {
    try {
      const response = await apiCall<ApiResponse & { data: any }>('/nginx/config/export')

      if (response.success) {
        const blob = new Blob([JSON.stringify(response.data, null, 2)], {
          type: 'application/json',
        })
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `nginx-config-${new Date().toISOString().split('T')[0]}.json`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
      } else {
        throw new Error(response.error || 'Failed to export configuration')
      }
    } catch (error) {
      console.error('Failed to export configuration:', error)
      throw error
    }
  }

  // =============================================
  // HEALTH & STATISTICS ACTIONS
  // =============================================

  async function fetchHealth(): Promise<HealthStatus> {
    statusLoading.value = true

    try {
      const response = await apiCall<HealthStatus>('/nginx/health')
      health.value = response
      return health.value
    } catch (error) {
      console.error('Failed to fetch health status:', error)
      health.value = {
        success: false,
        status: 'unhealthy',
        error: (error as Error).message,
      }
      throw error
    } finally {
      statusLoading.value = false
    }
  }

  async function fetchStats(): Promise<Stats> {
    statusLoading.value = true

    try {
      const response = await apiCall<{ data: Stats }>('/nginx/stats')
      stats.value = response.data
      return response.data
    } catch (error) {
      console.error('Failed to fetch statistics:', error)
      stats.value = null
      throw error
    } finally {
      statusLoading.value = false
    }
  }

  // =============================================
  // UTILITY ACTIONS
  // =============================================

  async function refreshAll(): Promise<void> {
    try {
      await Promise.all([fetchServers(), fetchCertificates(), fetchParameters(), fetchStats()])
    } catch (error) {
      console.error('Failed to refresh all data:', error)
      throw error
    }
  }

  function clearAllErrors(): void {
    serversError.value = null
    certificatesError.value = null
    parametersError.value = null
  }

  function getServerById(serverId: number): Server | undefined {
    return servers.value.find((server) => server.server_id === serverId)
  }

  function getCertificateById(certId: number): Certificate | undefined {
    return certificates.value.find((cert) => cert.cert_id === certId)
  }

  function getServersByCertificate(certId: number): Server[] {
    const cert = getCertificateById(certId)
    if (!cert) return []

    return servers.value.filter(
      (server) => server.ssl_enabled && server.ssl_certificate === cert.cert_path,
    )
  }

  function validateServerConfig(serverData: Partial<Server>): {
    isValid: boolean
    errors: string[]
  } {
    const errors: string[] = []

    if (!serverData.server_title?.trim()) {
      errors.push('Server title is required')
    }

    if (!serverData.server_name?.trim()) {
      errors.push('Server name is required')
    }

    if (!serverData.port || serverData.port < 1 || serverData.port > 65535) {
      errors.push('Valid port number (1-65535) is required')
    }

    if (serverData.ssl_enabled) {
      if (!serverData.ssl_certificate?.trim()) {
        errors.push('SSL certificate path is required when SSL is enabled')
      }
      if (!serverData.ssl_certificate_key?.trim()) {
        errors.push('SSL certificate key path is required when SSL is enabled')
      }
    }



    return {
      isValid: errors.length === 0,
      errors,
    }
  }

  // =============================================
  // RETURN STORE INTERFACE
  // =============================================

  return {
    // State
    servers,
    serversLoading,
    serversError,
    certificates,
    certificatesLoading,
    certificatesError,
    parameters,
    locationParameters,
    parametersLoading,
    parametersError,
    stats,
    health,
    statusLoading,
    selectedServer,
    selectedCertificate,

    // Computed
    sslEnabledServers,
    http2EnabledServers,
    selfSignedCertificates,
    expiringSoonCertificates,
    commonParameters,

    // Server Actions
    fetchServers,
    addServer,
    updateServer,
    deleteServer,
    selectServer,
    clearSelectedServer,

    // Certificate Actions
    fetchCertificates,
    addCertificate,
    updateCertificate,
    deleteCertificate,
    selectCertificate,
    clearSelectedCertificate,

    // Parameter Actions
    fetchParameters,
    addParameter,

    // Config Management
    loadCertificatesFromJson,
    loadServersFromJson,
    exportConfiguration,

    // Health & Stats
    fetchHealth,
    fetchStats,

    // Utility
    refreshAll,
    clearAllErrors,
    getServerById,
    getCertificateById,
    getServersByCertificate,
    validateServerConfig,
  }
})
