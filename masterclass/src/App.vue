<script lang="ts" setup>
import { useAuthStore } from '@/stores/auth.ts'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import Sidebar from '@/components/sidebar.vue'
import { onMounted, onBeforeUnmount } from 'vue'

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
  <div class="header" id="App">
    <!-- Show sidebar only if user is authenticated and not on login page -->
    <nav v-if="token && route.path !== '/'">
      <div><Sidebar /></div>
    </nav>

    <Transition name="fade" mode="out-in">
      <RouterView />
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
