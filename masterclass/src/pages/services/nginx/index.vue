<template>
  <div class="h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col justify-center items-center p-8">
    <!-- Modal Components -->
    <AddProxyBlockModal v-if="showProxyModal" @close="showProxyModal = false" />
    <AddCertificateModal v-if="showCertificateModal" @close="showCertificateModal = false" />
    <FindAddResourceModal v-if="showResourceModal" @close="showResourceModal = false" />
    <DeployConfigurationModal v-if="showDeployModal" @close="showDeployModal = false" />

    <div class="w-full max-w-5xl mx-auto space-y-8 gap-3 flex flex-col items-center">
      <!-- Quick Actions Title -->
      <div class="flex items-center justify-center space-x-3 mb-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#005188]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <h2 class="text-5xl font-bold  text-gray-800 tracking-wide">QUICK ACTIONS</h2>
      </div>

      <!-- Quick Action Buttons -->
      <div class="grid grid-cols-2 gap-6 mb-8 w-full">
        <button
          class="px-8 py-6 bg-gradient-to-br from-[#005188] to-[#0066a1] text-white rounded-xl hover:from-[#003d6a] hover:to-[#005188] transition-all duration-300 transform hover:-translate-y-1 shadow-lg hover:shadow-xl flex items-center justify-center space-x-4 text-xl w-full group"
          @click="addProxyBlock"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
          </svg>
          <span class="font-medium">Add Proxy Block</span>
        </button>

        <button
          class="px-8 py-6 bg-gradient-to-br from-[#007C52] to-[#009966] text-white rounded-xl hover:from-[#006044] hover:to-[#007C52] transition-all duration-300 transform hover:-translate-y-1 shadow-lg hover:shadow-xl flex items-center justify-center space-x-4 text-xl w-full group"
          @click="addCertificate"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <span class="font-medium">Add Certificate</span>
        </button>

        <button
          class="px-8 py-6 bg-white text-[#005188] border-2 border-[#005188] rounded-xl hover:bg-[#005188] hover:text-white transition-all duration-300 transform hover:-translate-y-1 shadow-lg hover:shadow-xl flex items-center justify-center space-x-4 text-xl w-full col-span-2 group"
          @click="findAdd"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
          </svg>
          <span class="font-medium">Find/Add Resource</span>
        </button>
      </div>

      <!-- Secondary Action Buttons -->
      <div class="space-y-6 mb-10 w-full">
        <button
          class="w-full text-left px-8 py-6 bg-white border-2 border-gray-200 rounded-2xl hover:border-[#005188] transition-all duration-300 hover:shadow-lg flex items-center justify-between group"
          @click="manageReverseProxy"
        >
          <div class="flex items-center space-x-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#005188]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <span class="font-semibold text-gray-800 group-hover:text-[#005188] text-xl">Reverse Proxy Configuration</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 group-hover:text-[#005188]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <button
          class="w-full text-left px-8 py-6 bg-white border-2 border-gray-200 rounded-2xl hover:border-[#007C52] transition-all duration-300 hover:shadow-lg flex items-center justify-between group"
          @click="manageCertificates"
        >
          <div class="flex items-center space-x-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="font-semibold text-gray-800 group-hover:text-[#007C52] text-xl">SSL/TLS Certificate Management</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 group-hover:text-[#007C52]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Deploy Button -->
      <button
        class="w-full px-8 py-6 bg-gradient-to-r from-[#005188] to-[#007C52] text-white rounded-2xl hover:from-[#003d6a] hover:to-[#006044] transition-all duration-500 transform hover:-translate-y-1 shadow-xl hover:shadow-2xl flex items-center justify-center space-x-4 group"
        @click="deployChanges"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 animate-pulse group-hover:animate-none group-hover:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <span class="font-bold tracking-wide text-2xl">DEPLOY CONFIGURATION</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router'
import AddProxyBlockModal from '@/components/services/nginx/AddProxyBlockModal.vue';
import AddCertificateModal from '@/components/services/nginx/AddCertificateModal.vue';
import FindAddResourceModal from '@/components/services/nginx/FindAddResourceModal.vue';
import DeployConfigurationModal from '@/components/services/nginx/DeployConfigurationModal.vue';

const router = useRouter()

const showProxyModal = ref(false);
const showCertificateModal = ref(false);
const showResourceModal = ref(false);
const showDeployModal = ref(false);

const addProxyBlock = () => {
  showProxyModal.value = true;
};

const addCertificate = () => {
  showCertificateModal.value = true;
};

const findAdd = () => {
  showResourceModal.value = true;
};

const manageReverseProxy = () => {
  router.push("/services/nginx/servers")
};

const manageCertificates = () => {
  router.push("/services/nginx/certificates")
};

const deployChanges = () => {
  showDeployModal.value = true;
};
</script>

<style>
/* Enhanced transitions for all interactive elements */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 250ms;
}

/* Custom pulse animation */
@keyframes tech-pulse {
  0%, 100% {
    opacity: 0.8;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.05);
  }
}
.animate-pulse {
  animation: tech-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
