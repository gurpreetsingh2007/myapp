<template>
  <div class="dashboard-wrapper">
    <div class="dashboard-container">
      <section class="services-section">
        <header class="section-header">
          <h1 class="section-title">SERVICES</h1>
          <div class="grid-line"></div>
        </header>

        <div class="services-grid">
           <button v-for="item in menuItems" :key="item.title" class="service-button" @click="navigateTo(item.path)">
              <component :is="iconMap[item.icon]" class="button-icon" />
              <span>{{ item.title }}</span>
          </button>
        </div>
      </section>

      <section class="servers-section">
        <header class="section-header">
          <h2 class="section-title">ACTIVE SERVERS</h2>
          <div class="grid-line"></div>
        </header>

        <div v-if="loading" class="loading-state">
          <div class="pulse-animation"></div>
          <span>LOADING SERVER LIST...</span>
        </div>

        <div v-else-if="error" class="error-state">
          <div class="error-badge">!</div>
          <span>{{ error }}</span>
        </div>

        <div v-else-if="servers.length === 0" class="empty-state">
            <div class="empty-icon">?</div>
            <span>No active servers found</span>
        </div>

        <div v-else class="servers-grid">
          <div v-for="client in servers" :key="client.clientId" class="server-card">
            <ServerIcon class="server-icon" />
            <span class="server-name">{{ client.clientId }}</span>
            <span class="server-address">{{ client.remoteAddress }}</span>
            <div class="status-indicator active"></div>
          </div>
        </div>
      </section>
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
  { title: 'RSNAPSHOT', path: '/dashboard/rsnapshot', icon: 'rsnapshot' },
  { title: 'PHP-FPM', path: '/dashboard/phpfpm', icon: 'php' },
  { title: 'MARIA DB', path: '/dashboard/MariaDB', icon: 'MariDb' },
]

const servers = ref<{ clientId: string; remoteAddress: string }[]>([])
const loading = ref(false)
const error = ref('')

const fetchServers = async () => {
  try {
    loading.value = true
    const response = await fetch('https://172.18.90.167:4173/backend/credentials/get/server_list')
    if (!response.ok) throw new Error(`SERVER ERROR: ${response.status}`)

    const data = await response.json()
    if (data && Array.isArray(data.clients)) {
      servers.value = data.clients
    } else {
      throw new Error('Invalid data format: expected clients array')
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'FAILED TO FETCH SERVERS'
  } finally {
    loading.value = false
  }
}

onMounted(fetchServers)

const navigateTo = (path: string) => router.push(path)
</script>

<style scoped>
.dashboard-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
}

.dashboard-container {
  width: 100%;
  max-width: 1200px;
  padding: 1.5rem;
}

.section-header {
  margin-bottom: 1.5rem;
  text-align: center;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 2px;
  background: var(--primary-glow);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  text-shadow: var(--neon-shadow);
  margin-bottom: 0.5rem;
}

.grid-line {
  height: 2px;
  background: var(--primary-glow);
  margin: 0 auto;
  width: 80%;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 160px));
  justify-content: center;
  gap: 1rem;
  margin: 1.5rem 0;
}

.service-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  font-size: 0.9rem;
  font-weight: 500;
  letter-spacing: 1px;
  color: var(--text-color);
  background: rgba(10, 10, 10, 0.7);
  border: 1px solid rgba(0, 240, 255, 0.3);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  height: 100%;
  min-height: 90px;
  width: 100%;
}

.service-button:hover {
  background: rgba(0, 240, 255, 0.1);
  border-color: rgba(208, 0, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 0 15px rgba(0, 240, 255, 0.3);
}

.button-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #00f0ff;
}

.servers-section {
  margin-top: 2rem;
}

.servers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 220px));
  justify-content: center;
  gap: 1rem;
}

.server-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 1rem;
  background: rgba(15, 15, 15, 0.8);
  border: 1px solid rgba(208, 0, 255, 0.2);
  border-radius: 6px;
  transition: all 0.2s ease;
  position: relative;
}

.server-card:hover {
  border-color: rgba(0, 240, 255, 0.5);
  background: rgba(0, 240, 255, 0.05);
}

.server-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #d000ff;
}

.server-name {
  font-size: 0.85rem;
  font-weight: 500;
  letter-spacing: 0.5px;
  color: var(--text-color);
  word-break: break-all;
}

.server-address {
  font-size: 0.75rem;
  opacity: 0.8;
}

.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.status-indicator.active {
  background-color: #00ff88;
  box-shadow: 0 0 8px #00ff88;
  animation: pulse 2s infinite;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
  font-size: 0.9rem;
  letter-spacing: 1px;
  color: #00f0ff;
}

.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
  color: #ff5555;
  font-size: 0.9rem;
  letter-spacing: 1px;
}

.pulse-animation {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: #00f0ff;
  animation: pulse 1.5s infinite;
}

.error-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background-color: #ff5555;
  color: #0a0a0a;
  border-radius: 50%;
  font-weight: bold;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
  color: #888;
  grid-column: 1 / -1; /* Occupa tutta la larghezza della griglia */
}

.empty-icon {
  font-size: 2rem;
  opacity: 0.5;
}

@keyframes pulse {
  0% { opacity: 0.7; }
  50% { opacity: 1; }
  100% { opacity: 0.7; }
}

@media (max-width: 768px) {
  .services-grid {
    grid-template-columns: repeat(auto-fit, minmax(120px, 120px));
  }

  .servers-grid {
    grid-template-columns: repeat(auto-fit, minmax(160px, 160px));
  }

  .service-button {
    padding: 0.5rem;
    min-height: 80px;
    font-size: 0.8rem;
  }

  .button-icon {
    width: 1.25rem;
    height: 1.25rem;
  }
}
</style>
