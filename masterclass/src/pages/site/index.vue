<script setup lang="ts">
defineOptions({ name: 'DashboardIndex' })
import { Terminal, Code, Database, HardDriveUpload, Server as ServerIcon } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'
import { API } from '@/config/index'
const router = useRouter()

const iconMap = {
  nginx: Terminal,
  php: Code,
  MariDb: Database,
  rsnapshot: HardDriveUpload,
}

const menuItems = [
  { title: 'NGINX', path: '/services/nginx', icon: 'nginx' },
  { title: 'RSNAPSHOT', path: '/services/rsnapshot', icon: 'rsnapshot' },
  { title: 'PHP-FPM', path: '/site/phpfpm', icon: 'php' },
  { title: 'MARIA DB', path: '/site/MariaDB', icon: 'MariDb' },
]

const servers = ref<{ clientId: string; remoteAddress: string }[]>([])
const loading = ref(false)
const error = ref('')

const fetchServers = async () => {
  try {
    loading.value = true
    const response = await fetch(API + '/credentials/get/server_list')
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

<template>
  <div class="h-full dashboard-wrapper bg-gradient-to-br from-[#005188]/5 to-[#007C52]/5">
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


<style scoped>
.dashboard-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
}

.dashboard-container {
  width: 100%;
  max-width: 1200px;
  padding: 2rem;
}

.section-header {
  margin-bottom: 2rem;
  text-align: center;
}

.section-title {
  font-size: 2rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 2px;
  background: linear-gradient(90deg, #005188, #007C52);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  margin-bottom: 0.75rem;
  position: relative;
  display: inline-block;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 2px;
  background: linear-gradient(90deg, #005188, #007C52);
  border-radius: 2px;
}

.grid-line {
  height: 1px;
  background: linear-gradient(90deg, transparent, #005188, #007C52, transparent);
  margin: 0 auto;
  width: 80%;
  opacity: 0.3;
}

/* Servizi - max 4 per riga */
/* MODIFIED SERVERS GRID - Centered layout */
.servers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  justify-content: center;
  gap: 1.5rem;
  max-width: 1000px;
  margin: 0 auto;
}

/* MODIFIED SERVICES GRID - Centered layout */
.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  justify-content: center;
  gap: 1.5rem;
  margin: 2rem auto;
  max-width: 800px;
}

/* MODIFIED MEDIA QUERIES */
@media (max-width: 1024px) {
  .services-grid {
    max-width: 600px;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
  .servers-grid {
    max-width: 600px;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  }
}

@media (max-width: 640px) {
  .services-grid {
    max-width: 300px;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }
  .servers-grid {
    max-width: 300px;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }
  .service-button {
    min-height: 80px;
  }
}


.service-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.25rem 0.75rem;
  font-size: 0.95rem;
  font-weight: 500;
  letter-spacing: 0.5px;
  color: #005188;
  background: white;
  border: 1px solid rgba(0, 81, 136, 0.2);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  height: 100%;
  min-height: 100px;
  box-shadow: 0 2px 8px rgba(0, 81, 136, 0.08);
}

.service-button:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 16px rgba(0, 81, 136, 0.12);
  border-color: rgba(0, 124, 82, 0.4);
  background: linear-gradient(to bottom right, white, rgba(0, 124, 82, 0.05));
}

.button-icon {
  width: 1.75rem;
  height: 1.75rem;
  color: #005188;
  transition: transform 0.3s ease;
}

.service-button:hover .button-icon {
  transform: scale(1.1);
  color: #007C52;
}

.servers-section {
  margin-top: 3rem;
}

.server-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1.25rem;
  background: white;
  border: 1px solid rgba(0, 81, 136, 0.2);
  border-radius: 12px;
  transition: all 0.3s ease;
  position: relative;
  box-shadow: 0 2px 8px rgba(0, 81, 136, 0.08);
}

.server-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 81, 136, 0.12);
  border-color: rgba(0, 124, 82, 0.4);
}

.server-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #005188;
}

.server-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #005188;
}

.server-address {
  font-size: 0.8rem;
  color: rgba(0, 81, 136, 0.7);
}

.status-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
}

.status-indicator.active {
  background-color: #007C52;
  box-shadow: 0 0 0 4px rgba(0, 124, 82, 0.2);
  animation: pulse 2s infinite;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem;
  font-size: 1rem;
  letter-spacing: 0.5px;
  color: #005188;
  grid-column: 1 / -1;
}

.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem;
  color: #dc2626;
  font-size: 1rem;
  letter-spacing: 0.5px;
  grid-column: 1 / -1;
}

.pulse-animation {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: #005188;
  animation: pulse 1.5s infinite;
}

.error-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background-color: #dc2626;
  color: white;
  border-radius: 50%;
  font-weight: bold;
  font-size: 0.9rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem;
  color: rgba(0, 81, 136, 0.6);
  grid-column: 1 / -1;
}

.empty-icon {
  font-size: 2.5rem;
  opacity: 0.3;
}

@keyframes pulse {
  0% { transform: scale(0.95); opacity: 0.9; }
  50% { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.9; }
}

@media (max-width: 1024px) {
  .services-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: 600px;
  }

  .servers-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: 600px;
  }
}

@media (max-width: 640px) {
  .services-grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
    max-width: 300px;
  }

  .servers-grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
    max-width: 300px;
  }

  .service-button {
    min-height: 80px;
  }
}
</style>
