<template>
  <div class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white/90 backdrop-blur-md rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-white/20">
      <!-- Header -->
      <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200/50 flex-shrink-0">
        <h2 class="text-2xl font-bold text-gray-800">Location Parameters Manager</h2>
        <button @click="$emit('close')" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100/50 rounded-full transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Parameters List - Scrollable -->
      <div class="flex-1 overflow-y-auto px-6 py-4 min-h-0">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-700">Parameters List</h3>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">
              {{ selectedItems.length }} selected
            </span>
            <button
              @click="selectAllCommon"
              class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
            >
              Select Common
            </button>
            <button
              @click="clearSelection"
              class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors"
            >
              Clear
            </button>
          </div>
        </div>

        <div
          v-for="param in locationParameters"
          :key="param.param_key"
          class="flex items-start mb-3 p-4 bg-gray-50/80 hover:bg-gray-100/50 rounded-lg transition-colors group border"
          :class="{
            'border-blue-300 bg-blue-50/50': selectedItems.includes(param.param_key),
            'border-transparent': !selectedItems.includes(param.param_key)
          }"
        >
          <input
            type="checkbox"
            :id="`param-${param.param_key}`"
            v-model="selectedItems"
            :value="param.param_key"
            class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500 flex-shrink-0 mt-1"
          />
          <div class="flex flex-1 ml-4 min-w-0 gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-gray-700 font-medium truncate">{{ param.param_name }}</span>
                <span
                  v-if="param.is_common"
                  class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full"
                >
                  Common
                </span>
              </div>
              <p class="text-gray-500 text-sm mt-1 break-all">{{ param.param_value }}</p>
              <p v-if="param.description" class="text-gray-400 text-xs mt-1">{{ param.description }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                @click.stop="editParameter(param)"
                class="text-gray-400 hover:text-blue-500 transition-colors p-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-if="locationParameters.length === 0" class="text-center py-8 text-gray-500">
          <div class="w-16 h-16 mx-auto mb-4 bg-gray-100/50 rounded-full flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </div>
          <p>No parameters found</p>
          <p class="text-sm mt-2">{{ parametersError || 'Loading parameters...' }}</p>
        </div>
      </div>

      <!-- Edit Section - Fixed at bottom -->
      <div class="px-6 py-4 border-t border-gray-200/50 bg-gray-50/50 flex-shrink-0">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-700 mb-2">
            {{ isEditing ? 'Edit Parameter' : 'Add New Parameter' }}
          </h3>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Name</label>
              <input
                v-model="currentParam.param_name"
                type="text"
                placeholder="e.g., proxy_set_header"
                class="w-full px-4 py-2 border border-gray-300/80 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white/90 transition-all"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Value</label>
              <input
                v-model="currentParam.param_value"
                type="text"
                placeholder="e.g., X-Forwarded-For $proxy_add_x_forwarded_for"
                class="w-full px-4 py-2 border border-gray-300/80 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white/90 transition-all"
              />
            </div>
          </div>

          <!-- Common checkbox and description -->
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="currentParam.is_common"
                  class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500 mr-2"
                />
                <span class="text-sm text-gray-700">Mark as common parameter</span>
              </label>
            </div>
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
              <input
                v-model="currentParam.description"
                type="text"
                placeholder="e.g., Sets proxy headers for load balancing"
                class="w-full px-4 py-2 border border-gray-300/80 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white/90 transition-all"
              />
            </div>
          </div>

          <!-- Action buttons -->
          <div class="flex gap-3">
            <button
              v-if="isEditing"
              @click="cancelEdit"
              class="flex-1 py-2 px-4 rounded-lg text-gray-700 font-medium bg-gray-200 hover:bg-gray-300 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="handleAction"
              :disabled="isLoading || !isFormValid"
              class="flex-1 py-2 px-4 rounded-lg text-white font-medium shadow transition-all duration-300 relative overflow-hidden"
              :class="{
                'bg-blue-600 hover:bg-blue-700': !isEditing,
                'bg-green-600 hover:bg-green-700': isEditing,
                'opacity-75 cursor-not-allowed': isLoading || !isFormValid
              }"
            >
              <div class="flex items-center justify-center">
                <transition name="fade" mode="out-in">
                  <div v-if="isLoading" key="loading" class="flex items-center">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                    Processing...
                  </div>
                  <div v-else key="action" class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path v-if="!isEditing" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      <path v-if="isEditing" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isEditing ? 'Update Parameter' : 'Add Parameter' }}
                  </div>
                </transition>
              </div>
            </button>
          </div>
        </div>

        <!-- Bulk actions -->
        <div v-if="selectedItems.length > 0" class="pt-4 border-t border-gray-200/50">
          <div class="flex gap-3">
            <button
              @click="updateSelection"
              :disabled="isLoading"
              class="flex-1 py-2 px-4 rounded-lg text-white font-medium bg-blue-600 hover:bg-blue-700 transition-colors flex items-center justify-center"
              :class="{'opacity-75 cursor-not-allowed': isLoading}"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Update Selection
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'
import type { Parameter } from '@/stores/services/nginx/nginx'

const emit = defineEmits(['close'])

// Use the nginx store
const nginxStore = useNginxStore()

// Local state
const selectedItems = ref<string[]>([])
const isEditing = ref(false)
const isLoading = ref(false)
const currentParam = ref<{
  param_key: string | null
  param_name: string
  param_value: string
  is_common: boolean
  description: string
  param_id?: number
}>({
  param_key: null,
  param_name: '',
  param_value: '',
  is_common: false,
  description: ''
})

// Computed properties from store
const locationParameters = computed(() => {
  const params = nginxStore.locationParameters || []
  return params.map((param, index) => ({
    ...param,
    param_key: `${param.param_name}-${param.param_value}-${index}`,
    param_id: param.param_id
  }))
})

const parametersLoading = computed(() => nginxStore.parametersLoading || nginxStore.parametersUpdating)
const parametersError = computed(() => nginxStore.parametersError)

// Form validation
const isFormValid = computed(() => {
  return currentParam.value.param_name.trim() !== '' &&
         currentParam.value.param_value.trim() !== ''
})

// Function to preselect common parameters
const preselectCommonParameters = () => {
  const commonParams = locationParameters.value
    .filter(param => param.is_common)
    .map(param => param.param_key)

  selectedItems.value = [...new Set(commonParams)]
}

// Select all common parameters
const selectAllCommon = () => {
  const commonParams = locationParameters.value
    .filter(param => param.is_common)
    .map(param => param.param_key)

  selectedItems.value = [...new Set([...selectedItems.value, ...commonParams])]
}

// Clear selection
const clearSelection = () => {
  selectedItems.value = []
}

// Update all parameters based on selection
const updateSelection = async () => {
  try {
    isLoading.value = true

    // Prepare all parameters with updated is_common status
    const paramsToUpdate = locationParameters.value.map(param => ({
      param_id: param.param_id,
      param_name: param.param_name,
      param_value: param.param_value,
      is_common: selectedItems.value.includes(param.param_key), // true if selected
      description: param.description || ''
    }))

    const result = await nginxStore.updateParametersBulk(paramsToUpdate)
    console.log(`Updated ${result.successCount} parameters`)
    await nginxStore.fetchParameters()
    emit('close')
  } catch (error) {
    console.error('Failed to update parameters:', error)
  } finally {
    isLoading.value = false
  }
}

// Add new parameter
const addParameter = async () => {
  if (!isFormValid.value) return

  try {
    isLoading.value = true

    await nginxStore.addParameter({
      param_name: currentParam.value.param_name.trim(),
      param_value: currentParam.value.param_value.trim(),
      is_common: currentParam.value.is_common,
      description: currentParam.value.description.trim() || 'User-created parameter'
    })

    resetForm()
    await nginxStore.fetchParameters()

  } catch (error) {
    console.error('Failed to add parameter:', error)
  } finally {
    isLoading.value = false
  }
}

// Edit parameter
const editParameter = (param: Parameter & { param_key: string }) => {
  // If this parameter is already selected, don't clear other selections
  if (!selectedItems.value.includes(param.param_key)) {
    selectedItems.value = [param.param_key]
  }

  currentParam.value = {
    param_key: param.param_key,
    param_name: param.param_name,
    param_value: param.param_value,
    is_common: param.is_common || false,
    description: param.description || '',
    param_id: param.param_id
  }
  isEditing.value = true
}

// Cancel edit
const cancelEdit = () => {
  resetForm()
  isEditing.value = false
}

// Reset form
const resetForm = () => {
  currentParam.value = {
    param_key: null,
    param_name: '',
    param_value: '',
    is_common: false,
    description: ''
  }
}

// Handle main action button
const handleAction = async () => {
  if (isEditing.value) {
    await updateParameter()
  } else {
    await addParameter()
  }
}

// Update parameter
const updateParameter = async () => {
  if (!isFormValid.value || !currentParam.value.param_id) return

  try {
    isLoading.value = true

    await nginxStore.updateParametersBulk([{
      param_id: currentParam.value.param_id,
      param_name: currentParam.value.param_name.trim(),
      param_value: currentParam.value.param_value.trim(),
      is_common: currentParam.value.is_common,
      description: currentParam.value.description.trim() || 'Updated parameter'
    }])

    resetForm()
    isEditing.value = false
    await nginxStore.fetchParameters()

  } catch (error) {
    console.error('Failed to update parameter:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for changes in location parameters to preselect common ones
watch(locationParameters, (newParams) => {
  if (newParams && newParams.length > 0) {
    preselectCommonParameters()
  }
}, { immediate: true })

// Load parameters on component mount
onMounted(async () => {
  if (locationParameters.value.length === 0) {
    try {
      await nginxStore.fetchParameters()
    } catch (error) {
      console.error('Failed to load parameters:', error)
    }
  }
})
</script>

<style scoped>
/* Animation for button text */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

/* Smooth transitions */
button, input {
  transition: all 0.2s ease;
}

/* Ensure proper flexbox behavior */
.flex-1 {
  flex: 1;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

.min-h-0 {
  min-height: 0;
}

/* Break long parameter values */
.break-all {
  word-break: break-all;
}
</style>
