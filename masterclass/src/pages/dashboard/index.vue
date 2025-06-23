<template>
  <div class="services-section">
    <h1 class="section-title cyberpunk-header">services</h1>
    <div class="grid-line"></div>

    <div class="buttons-container">
      <button
        v-for="item in menuItems"
        :key="item.title"
        class="button glow-cyan"
        @click="navigateTo(item.path)"
      >
        <component :is="iconMap[item.icon]" class="icon" />
        <span>{{ item.title }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'DashboardIndex' })
import { Terminal, Code, Database } from 'lucide-vue-next'
import { useRouter } from 'vue-router'

const router = useRouter()

// Define the icon map
const iconMap = {
  nginx: Terminal,
  php: Code,
  MariDb: Database,
} as const

type IconKey = keyof typeof iconMap

// Define menu items
interface MenuItem {
  title: string
  path: string
  icon: IconKey
}

const menuItems: MenuItem[] = [
  { title: 'NGINX', path: '/dashboard/nginx', icon: 'nginx' },
  { title: 'PHP-FPM', path: '/dashboard/phpfpm', icon: 'php' },
  { title: 'MariaDB', path: '/dashboard/MariaDB', icon: 'MariDb' },
]

// Navigation function
const navigateTo = (path: string) => {
  router.push(path)
}
</script>

<style scoped>
.services-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  width: 100%;
  gap: 2rem;
}

.section-title {
  font-family: 'Orbitron', sans-serif;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 1rem;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 1px;
  background: linear-gradient(90deg, #06b6d4, #8b5cf6);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  text-shadow: 0 0 10px rgba(6, 182, 212, 0.3);
}

.buttons-container {
  display: flex;
  gap: 2rem;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  width: 100%;
  max-width: 1200px;
}

.button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 2rem;
  font-family: 'Orbitron', sans-serif;
  font-weight: 600;
  font-size: 1.2rem;
  border: 2px solid transparent;
  border-radius: 12px;
  color: #0f172a;
  background: linear-gradient(90deg, #06b6d4, #8b5cf6);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.button:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(6, 182, 212, 0.4);
}

.icon {
  width: 1.5rem;
  height: 1.5rem;
}

/* Stili cyberpunk */
.cyberpunk-header {
  position: relative;
  width: 100%;
}

.grid-line {
  height: 2px;
  background: linear-gradient(90deg, transparent, #06b6d4, #ec4899, transparent);
  margin: 0.5rem 0;
  width: 100%;
}

.glow-cyan {
  box-shadow: 0 0 15px rgba(6, 182, 212, 0.3);
  border-color: #06b6d4;
}

@media (max-width: 768px) {
  .buttons-container {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
