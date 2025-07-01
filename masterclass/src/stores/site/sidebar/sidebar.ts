import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useSidebarStore = defineStore('sidebar', {
  state: () => ({
    isOpen: true,
  }),
  actions: {
    toggle() {
      this.isOpen = !this.isOpen
    },
  },
})

export const useRightSidebarStore = defineStore('rightsidebar', () => {
  // Sidebar state
  const isOpen = ref(true)

  // Background image state

  // Computed
  const images = import.meta.glob('@/assets/fantasyManic/IMAGE*.jpg', {
    eager: true,
    import: 'default',
  })

  const imageKeys = Object.keys(images)
  const currentImageIndex = ref(Math.floor(Math.random() * imageKeys.length))
  const backgroundImageUrl = computed(() => images[imageKeys[currentImageIndex.value]])

  // Actions
  const toggle = () => {
    isOpen.value = !isOpen.value
    if (!isOpen.value) changeBackgroundImage()
  }

  const changeBackgroundImage = () => {
    currentImageIndex.value = Math.floor(Math.random() * imageKeys.length)
  }

  return {
    // State
    isOpen,
    currentImageIndex,
    // Computed
    backgroundImageUrl,
    // Actions
    toggle,
    changeBackgroundImage,
  }
})
