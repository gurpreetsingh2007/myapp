<template>
  <div class="fixed inset-0 backdrop-blur-sm bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
      <div class="bg-gradient-to-r from-[#005188] to-[#007C52] p-6 flex justify-between items-center">
        <h3 class="text-2xl font-bold text-white">Deploy Configuration</h3>
        <button @click="$emit('close')" class="text-white hover:text-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
          <div class="flex">
            <div class="flex-shrink-0">
              <InformationCircleIcon class="h-5 w-5 text-blue-500" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Select servers to deploy your configuration. The status indicator will show deployment results.
              </p>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="flex items-start gap-4">
            <!-- Status Indicator -->
            <div class="relative">
              <button
                @click="showError"
                :disabled="deployStatus !== 'error'"
                :class="[
                  'w-12 h-12 rounded-full border-2 border-white shadow-md flex items-center justify-center transition-all duration-300',
                  statusButtonClass,
                  { 'cursor-pointer hover:shadow-lg': deployStatus === 'error', 'cursor-default': deployStatus !== 'error' }
                ]"
                :title="errorMessage"
              >
                <template v-if="isDeploying">
                  <ArrowPathIcon class="h-6 w-6 text-white animate-spin" />
                </template>
                <template v-else-if="deployStatus === 'success'">
                  <CheckIcon class="h-6 w-6 text-white" />
                </template>
                <template v-else-if="deployStatus === 'error'">
                  <ExclamationTriangleIcon class="h-6 w-6 text-white" />
                </template>
                <template v-else>
                  <ServerIcon class="h-6 w-6 text-gray-500" />
                </template>
              </button>
              <div v-if="isDeploying" class="absolute -bottom-2 left-0 right-0 text-center">
                <span class="text-xs font-medium text-gray-500 animate-pulse">Deploying...</span>
              </div>
            </div>

            <!-- Server Selection - Checkbox List with individual status -->
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-2">Target Servers</label>

              <div class="border border-gray-300 rounded-lg p-2 max-h-60 overflow-y-auto">
                <div
                  v-for="server in serverList"
                  :key="server.id"
                  :class="[
                    'flex items-center p-2 hover:bg-gray-50 rounded transition-colors',
                    { 'bg-green-50': serverStatus[server.id] === 'success' },
                    { 'bg-red-50': serverStatus[server.id] === 'error' }
                  ]"
                >
                  <input
                    type="checkbox"
                    :id="`server-${server.id}`"
                    :value="server.id"
                    v-model="selectedServers"
                    :disabled="isDeploying"
                    class="h-4 w-4 text-[#005188] focus:ring-[#005188] border-gray-300 rounded"
                  >
                  <label :for="`server-${server.id}`" class="ml-2 text-sm text-gray-700 cursor-pointer flex items-center gap-2">
                    <span>{{ server.name }}</span>
                    <span v-if="serverStatus[server.id] === 'success'" class="text-green-600">
                      <CheckCircleIcon class="h-4 w-4" />
                    </span>
                    <span v-if="serverStatus[server.id] === 'error'" class="text-red-600">
                      <ExclamationCircleIcon class="h-4 w-4" />
                    </span>
                  </label>
                </div>
              </div>

              <div class="mt-2 flex justify-between items-center">
                <p class="text-xs text-gray-500">
                  {{ selectedServers.length }} server{{ selectedServers.length !== 1 ? 's' : '' }} selected
                </p>
                <button
                  @click="toggleSelectAll"
                  type="button"
                  class="text-xs text-[#005188] hover:text-[#003d6a] font-medium"
                >
                  {{ selectedServers.length === serverList.length ? 'Deselect all' : 'Select all' }}
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Deployment Comment</label>
            <textarea
              v-model="deployComment"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005188] focus:border-transparent shadow-sm"
              rows="2"
              placeholder="Optional: describe what you're changing..."
            ></textarea>
          </div>

          <div class="space-y-3">
            <div class="flex items-center">
              <input
                type="checkbox"
                id="test-config"
                v-model="testBeforeDeploy"
                class="h-5 w-5 text-[#005188] focus:ring-[#005188] rounded"
              >
              <label for="test-config" class="ml-2 text-sm text-gray-700">Test configuration before deploying</label>
            </div>
            <div class="flex items-center">
              <input
                type="checkbox"
                id="restart-service"
                v-model="restartAfterDeploy"
                class="h-5 w-5 text-[#005188] focus:ring-[#005188] rounded"
              >
              <label for="restart-service" class="ml-2 text-sm text-gray-700">Restart service after deployment</label>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
          <button
            @click="$emit('close')"
            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 shadow-sm"
          >
            Cancel
          </button>
          <button
            @click="handleDeploy"
            :disabled="isDeploying || selectedServers.length === 0"
            :class="[
              'px-6 py-2.5 bg-gradient-to-r from-[#005188] to-[#007C52] text-white rounded-lg shadow-sm transition-all duration-200',
              { 'opacity-70 cursor-not-allowed': isDeploying || selectedServers.length === 0 },
              { 'hover:from-[#003d6a] hover:to-[#006044] hover:shadow-md': !isDeploying && selectedServers.length > 0 }
            ]"
          >
            <span v-if="isDeploying">Deploying...</span>
            <span v-else>Deploy Now</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  InformationCircleIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  ServerIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline';

interface Server {
  id: number;
  name: string;
}

type DeployStatus = 'idle' | 'success' | 'error';
type ServerDeployStatus = 'idle' | 'success' | 'error';

// Server data
const serverList = ref<Server[]>([
  { id: 1, name: 'Production - Web Server' },
  { id: 2, name: 'Production - Database' },
  { id: 3, name: 'Staging Environment' },
  { id: 4, name: 'Development Server' },
]);

const selectedServers = ref<number[]>([]);
const deployStatus = ref<DeployStatus>('idle');
const serverStatus = ref<Record<number, ServerDeployStatus>>({});
const errorMessage = ref<string>('');
const isDeploying = ref<boolean>(false);
const deployComment = ref<string>('');
const testBeforeDeploy = ref<boolean>(true);
const restartAfterDeploy = ref<boolean>(true);

// Toggle select all servers
const toggleSelectAll = () => {
  if (selectedServers.value.length === serverList.value.length) {
    selectedServers.value = [];
  } else {
    selectedServers.value = serverList.value.map(server => server.id);
  }
};

// Computed property for status button classes
const statusButtonClass = computed(() => {
  switch (deployStatus.value) {
    case 'success':
      return 'bg-green-500 hover:bg-green-600';
    case 'error':
      return 'bg-red-500 hover:bg-red-600';
    case 'idle':
      return 'bg-gray-300';
    default:
      return 'bg-blue-500';
  }
});

// Simulate deploy with individual server status
const handleDeploy = () => {
  if (selectedServers.value.length === 0) return;

  isDeploying.value = true;
  deployStatus.value = 'idle';

  // Reset all server statuses
  serverStatus.value = {};
  selectedServers.value.forEach(id => {
    serverStatus.value[id] = 'idle';
  });

  // Simulate API calls with individual outcomes
  const promises = selectedServers.value.map(serverId => {
    return new Promise<void>((resolve) => {
      const delay = 1000 + Math.random() * 2000; // Random delay between 1-3s

      setTimeout(() => {
        const isSuccess = Math.random() > 0.3; // 70% success rate

        if (isSuccess) {
          serverStatus.value[serverId] = 'success';
        } else {
          serverStatus.value[serverId] = 'error';
        }

        resolve();
      }, delay);
    });
  });

  // When all deployments complete
  Promise.all(promises).then(() => {
    isDeploying.value = false;

    // Determine overall status
    const hasErrors = selectedServers.value.some(id => serverStatus.value[id] === 'error');
    const allSuccess = selectedServers.value.every(id => serverStatus.value[id] === 'success');

    if (hasErrors) {
      deployStatus.value = 'error';
      const failedServers = selectedServers.value
        .filter(id => serverStatus.value[id] === 'error')
        .map(id => serverList.value.find(s => s.id === id)?.name)
        .join(', ');
      errorMessage.value = `Deploy failed to: ${failedServers}`;
    } else if (allSuccess) {
      deployStatus.value = 'success';
      errorMessage.value = '';
    }
  });
};

// Show error message
const showError = () => {
  if (deployStatus.value === 'error') {
    alert(`Deployment Error:\n\n${errorMessage.value}`);
  }
};
</script>
