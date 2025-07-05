<template>
  <div class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white/90 backdrop-blur-md rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden border border-white/20">
      <!-- Header with close button -->
      <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200/50">
        <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
        <button @click="$emit('close')" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100/50 rounded-full transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- List of items -->
      <div class="flex-1 overflow-y-auto px-6 py-4">
        <div v-for="(item, index) in items" :key="index" class="flex items-center mb-3 p-4 bg-gray-50/80 hover:bg-gray-100/50 rounded-lg transition-colors">
          <input
            type="checkbox"
            v-model="selectedItems"
            :value="index"
            class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
            @change="updateButtonState"
          >
          <div class="flex flex-1 ml-4 space-x-4 min-w-0">
            <span class="text-gray-700 font-medium flex-1 px-2 py-1 truncate">{{ item.field1 }}</span>
            <span class="text-gray-500 flex-1 px-2 py-1 truncate">{{ item.field2 }}</span>
          </div>
        </div>

        <div v-if="items.length === 0" class="text-center py-8 text-gray-500">
          <div class="w-16 h-16 mx-auto mb-4 bg-gray-100/50 rounded-full flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </div>
          No settings available
        </div>
      </div>

      <!-- Input fields and action button -->
      <div class="px-6 py-4 border-t border-gray-200/50 bg-gray-50/50">
        <div class="grid grid-cols-2 gap-4 mb-4">
          <input
            v-model="newField1"
            type="text"
            placeholder="Setting 1"
            class="w-full px-4 py-3 border border-gray-300/80 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white/90"
          >
          <input
            v-model="newField2"
            type="text"
            placeholder="Setting 2"
            class="w-full px-4 py-3 border border-gray-300/80 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white/90"
          >
        </div>

        <!-- Dynamic action button -->
        <button
          @click="handleAction"
          class="w-full py-4 px-6 rounded-xl text-white font-bold text-lg shadow-lg transition-all duration-300 relative overflow-hidden"
          :class="{
            'bg-blue-600 hover:bg-blue-700': !showDelete,
            'bg-red-600 hover:bg-red-700': showDelete,
            'scale-95': isPressed
          }"
          @mousedown="isPressed = true"
          @mouseup="isPressed = false"
          @mouseleave="isPressed = false"
        >
          <div class="flex items-center justify-center">
            <transition name="flip" mode="out-in">
              <div v-if="!showDelete" key="add" class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Setting
              </div>
              <div v-else key="delete" class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Selected ({{ selectedItems.length }})
              </div>
            </transition>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['close']);

const items = ref([]);
const selectedItems = ref([]);
const newField1 = ref('');
const newField2 = ref('');
const isPressed = ref(false);

const showDelete = computed(() => selectedItems.value.length > 0);

const updateButtonState = () => {
  selectedItems.value = [...selectedItems.value];
};

const addItem = () => {
  if (newField1.value.trim() && newField2.value.trim()) {
    items.value.push({
      field1: newField1.value.trim(),
      field2: newField2.value.trim()
    });
    newField1.value = '';
    newField2.value = '';
  }
};

const deleteSelected = () => {
  selectedItems.value.sort((a, b) => b - a).forEach(index => {
    items.value.splice(index, 1);
  });
  selectedItems.value = [];
};

const handleAction = () => {
  if (showDelete.value) {
    deleteSelected();
  } else {
    addItem();
  }
};
</script>

<style scoped>
.flip-enter-active,
.flip-leave-active {
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
  transform-origin: center center;
}

.flip-enter-from {
  opacity: 0;
  transform: rotateX(90deg);
}

.flip-leave-to {
  opacity: 0;
  transform: rotateX(-90deg);
}

button:active {
  transform: scale(0.98);
}

button, input {
  transition: all 0.2s ease;
}

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
</style>
