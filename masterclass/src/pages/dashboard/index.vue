<template>
  <div class="services-section">
    <h1 class="section-title">services</h1>
    <div class="divider"></div>

    <div class="buttons-container">
      <button
        v-for="item in menuItems"
        :key="item.title"
        class="service-button"
        @click="navigateTo(item.path)"
      >
        <component :is="iconMap[item.icon]" class="button-icon" />
        <span>{{ item.title }}</span>
      </button>
    </div>

    <div class="servers-section">
      <h2 class="section-title">active servers</h2>
      <div class="divider"></div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Loading server list...</span>
      </div>

      <div v-else-if="error" class="error-state">
        <span class="error-badge">!</span>
        <span>{{ error }}</span>
      </div>

      <div v-else class="servers-list">
        <div v-for="server in servers" :key="server" class="server-item">
          <ServerIcon class="server-icon" />
          <span class="server-name">{{ server }}</span>
          <div class="status active"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'DashboardIndex' })
import { Terminal, Code, Database, HardDriveUpload, Server as ServerIcon } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'

const router = useRouter()

const iconMap = {
  nginx: Terminal,
  php: Code,
  MariDb: Database,
  rsnapshot: HardDriveUpload,
}

const menuItems = [
  { title: 'NGINX', path: '/dashboard/nginx', icon: 'nginx' },
  { title: 'RSNAPSHOT', path: '/dashboard/rsnapshot', icon: 'rsnapshot'},
  { title: 'PHP-FPM', path: '/dashboard/phpfpm', icon: 'php' },
  { title: 'MariaDB', path: '/dashboard/MariaDB', icon: 'MariDb' },
]

const servers = ref<string[]>([])
const loading = ref(false)
const error = ref('')

const fetchServers = async () => {
  try {
    loading.value = true
    const response = await fetch('https://172.18.90.167:4173/backend/credentials/get/server_list')

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

    servers.value = await response.json()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to fetch servers'
  } finally {
    loading.value = false
  }
}

onMounted(fetchServers)

const navigateTo = (path: string) => router.push(path)
</script>

<style scoped>
.services-section {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.section-title {
  font-size: 1.8rem;
  font-weight: 600;
  text-align: center;
  margin-bottom: 1rem;
  color: #2d3748;
}

.divider {
  height: 1px;
  background-color: #e2e8f0;
  margin: 1rem 0;
}

.buttons-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin: 2rem 0;
}

.service-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  color: #2d3748;
  cursor: pointer;
  transition: all 0.2s ease;
}

.service-button:hover {
  background-color: #f7fafc;
  transform: translateY(-2px);
}

.button-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #4a5568;
}

.servers-section {
  margin-top: 3rem;
}

.servers-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

.server-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.server-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #4a5568;
}

.server-name {
  flex: 1;
  font-size: 0.95rem;
  color: #4a5568;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.status.active {
  background-color: #48bb78;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: #718096;
}

.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: #e53e3e;
}

.error-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background-color: #e53e3e;
  color: white;
  border-radius: 50%;
  font-weight: bold;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e0;
  border-top-color: #4a5568;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .buttons-container {
    grid-template-columns: 1fr;
  }

  .section-title {
    font-size: 1.5rem;
  }
}
</style>
