<script lang="ts" setup>
import { useAuthStore } from '@/stores/site/login/auth'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import Sidebar from '@/components/sidebar.vue'
import { onMounted, onBeforeUnmount } from 'vue'
import { useSidebarStore } from '@/stores/site/sidebar/sidebar'
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
  <div class="app-container bg-gradient-to-br from-slate-50 to-blue-50 text-slate-800" id="App">
    <!-- Show content with sidebar only if user is authenticated and not on login page -->
    <template v-if="token && route.path !== '/'">
      <div class="dashboard-layout transition-all duration-300 ease-in-out" :class="{ 'sidebar-expanded': isOpen }">
        <Sidebar class="z-[9999] shadow-xl shadow-slate-200/50" />
        <div class="content-container">
          <Titlebar class="z-[100] titlebar-fixed bg-black/100 backdrop-blur-xl border-b border-slate-200/60 shadow-sm">
            <!-- Optional: Add action buttons in the slot -->
            <template #actions> </template>
          </Titlebar>
          <main class="main-content  backdrop-blur-sm rounded-xl shadow-lg shadow-slate-200/40 border border-slate-200/50">
            <Transition name="fade" mode="out-in">
              <RouterView />
            </Transition>
          </main>
        </div>
      </div>
    </template>

    <!-- Show only RouterView for non-authenticated routes -->
    <template v-else>
      <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-emerald-50">
        <Transition name="fade" mode="out-in">
          <RouterView />
        </Transition>
      </div>
    </template>
  </div>
</template>

<style scoped>
.app-container {
  height: 100vh;
  height: 100dvh; /* Dynamic viewport height for mobile */
  width: 100vw;
  overflow: hidden;
  /* Ensure proper positioning on mobile */
  position: relative;
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
  transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  /* Ensure proper stacking context */
  position: relative;
}

/* Fix for titlebar positioning on mobile */
.titlebar-fixed {
  position: sticky;
  top: 0;
  /* Handle safe areas on mobile devices */
  padding-top: env(safe-area-inset-top, 0);
  /* Ensure it's above other content */
  z-index: 100;
  /* Prevent content from flowing behind */
  /*background: inherit;*/
}

.main-content {
  flex: 1;
  overflow-y: auto;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  margin-left: 5px; /* Default width of collapsed sidebar */
  margin-right: 5px;
  margin-top: 5px;
  /* Handle bottom safe area on mobile */
  padding-bottom: env(safe-area-inset-bottom, 0);
}

.sidebar-expanded .main-content {
  margin-left: 5px; /* Width of expanded sidebar */
  margin-right: 5px;
  margin-top: 5px;
}

/* Enhanced mobile media query */
@media (max-width: 768px) {
  .app-container {
    /* Use dynamic viewport units for better mobile support */
    height: 100dvh;
    /* Prevent horizontal overflow */
    width: 100vw;
    overflow-x: hidden;
  }

  .content-container {
    /* Ensure full height on mobile */
    min-height: 100%;
  }

  .titlebar-fixed {
    /* Ensure titlebar is properly positioned */
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    /* Add safe area handling */
    padding-top: max(env(safe-area-inset-top, 0), 0px);
    /* Ensure proper z-index */
    z-index: 1000;
  }

  .main-content {
    margin-left: 0px;
    margin-right: 0px;
    width: 100%;
    /* Account for mobile browser chrome */
    min-height: calc(100vh - env(safe-area-inset-top, 0) - env(safe-area-inset-bottom, 0));
    /* Alternative fallback */
    min-height: calc(100dvh - env(safe-area-inset-top, 0) - env(safe-area-inset-bottom, 0));
  }

  .sidebar-expanded .main-content {
    margin-left: 0;
    width: 100%;
  }
}

/* Additional mobile-specific fixes */
@media screen and (max-width: 768px) {
  /* Handle landscape orientation */
  @media (orientation: landscape) {
    .app-container {
      height: 100vh;
      height: 100dvh;
    }
  }

  /* Handle specific mobile browsers */
  @supports (-webkit-touch-callout: none) {
    /* iOS Safari specific fixes */
    .app-container {
      height: -webkit-fill-available;
    }
  }
}

/* Enhanced fade transitions with smooth easing */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar styling for webkit browsers */
.main-content::-webkit-scrollbar {
  width: 6px;
}

.main-content::-webkit-scrollbar-track {
  background: transparent;
}

.main-content::-webkit-scrollbar-thumb {
  background: #005188;
  border-radius: 3px;
  opacity: 0.3;
}

.main-content::-webkit-scrollbar-thumb:hover {
  background: #007C52;
  opacity: 0.6;
}

/* Additional viewport meta fixes (add this to your HTML head if not present) */
/*
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
*/
</style>
