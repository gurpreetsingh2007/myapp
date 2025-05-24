<script lang="ts" setup>
import { useAuthStore } from '@/stores/auth.ts'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import Sidebar from '@/components/sidebar.vue'
import { onMounted, onBeforeUnmount } from 'vue'
import { useSidebarStore } from '@/stores/sidebar'
import Titlebar from './components/titlebar.vue'
const sidebarState = useSidebarStore()
const { isOpen } = storeToRefs(sidebarState)

const route = useRoute()
const authStore = useAuthStore()
const { token } = storeToRefs(authStore)

onMounted(() => {
  //window.addEventListener('beforeunload', handleBeforeUnload)
})

onBeforeUnmount(() => {
  //window.removeEventListener('beforeunload', handleBeforeUnload)
})

function handleBeforeUnload(event: BeforeUnloadEvent) {
  authStore.clearToken()
  event.preventDefault()
}
</script>

<template>
  <div class="app-container" id="App">
    <!-- Show content with sidebar only if user is authenticated and not on login page -->
    <template v-if="token && route.path !== '/'">
      <div class="dashboard-layout" :class="{ 'sidebar-expanded': isOpen }">
        <Sidebar class="z-[9999]" />
        <div class="content-container">
          <Titlebar class="z-[100]">
            <!-- Optional: Add action buttons in the slot -->
            <template #actions> </template>
          </Titlebar>
          <main class="main-content">
            <Transition name="fade" mode="out-in">
              <RouterView />
            </Transition>
          </main>
        </div>
      </div>
    </template>

    <!-- Show only RouterView for non-authenticated routes -->
    <template v-else>
      <Transition name="fade" mode="out-in">
        <RouterView />
      </Transition>
    </template>
  </div>
</template>
<style scoped>
.app-container {
  height: 100vh;
  width: 100vw;
  overflow: hidden;
}

.dashboard-layout {
  display: flex;
  height: 100%;
  width: 100%;
  position: relative;
}
.content-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  margin-left: 0px; /* Default width of collapsed sidebar */
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.main-content {
  flex: 1;
  overflow-y: auto;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  margin-left: 5px; /* Default width of collapsed sidebar */
  margin-right: 5px;
  margin-top: 5px;
}

.sidebar-expanded .main-content {
  margin-left: 5px; /* Width of expanded sidebar */
  margin-right: 5px;
  margin-top: 5px;
}

/* Media query for mobile */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0px;
    margin-right: 0px;
    width: 100%;
  }

  .sidebar-expanded .main-content {
    margin-left: 40;
    width: 100%;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
