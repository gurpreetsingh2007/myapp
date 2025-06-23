
<template>
  hello
</template>

<script setup lang="ts">
import { ref } from 'vue'

const isAdvanced = ref<boolean>(false)

const toggleMode = (): void => {
  isAdvanced.value = !isAdvanced.value
}
</script>

<style scoped>
/* Transition for left sidebar */
.slide-left-enter-active,
.slide-left-leave-active {
  transition:
    transform 0.7s ease-in-out,
    opacity 0.7s ease-in-out;
}

.slide-left-enter-from {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-left-enter-to {
  transform: translateX(0%);
  opacity: 1;
}

.slide-left-leave-from {
  transform: translateX(0%);
  opacity: 1;
}

.slide-left-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}

/* Transition for right sidebar */
.slide-right-enter-active,
.slide-right-leave-active {
  transition:
    transform 0.7s ease-in-out,
    opacity 0.7s ease-in-out;
}

.slide-right-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.slide-right-enter-to {
  transform: translateX(0%);
  opacity: 1;
}

.slide-right-leave-from {
  transform: translateX(0%);
  opacity: 1;
}

.slide-right-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* Fade transition for center text */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
  opacity: 1;
}



/* Add some visual enhancements */
.drop-shadow-lg {
  filter: drop-shadow(0 10px 8px rgb(0 0 0 / 0.04)) drop-shadow(0 4px 3px rgb(0 0 0 / 0.1));
}
</style>
