<template>
  <div class="h-full flex items-center justify-center bg-white">
    <div role="alert" class="animate-fade-in overflow-hidden max-w-2xl w-full">
      <!-- Testata con gradiente -->
      <div id="danger" class="bg-gradient-to-r from-[#005188] to-[#007C52] text-white font-bold rounded-t px-4 py-2 flex items-center transition-all duration-300 hover:from-[#005188]/90 hover:to-[#007C52]/90">
        <svg class="w-5 h-5 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
          </path>
        </svg>
        <span class="text-shadow-sm">Attenzione</span>
      </div>

      <!-- Corpo con gradiente sottile -->
      <div
        class="border border-t-0 border-[#007C52] rounded-b bg-gradient-to-br from-white to-[#007C52]/5 px-4 py-3 text-[#005188] transition-all duration-300 hover:to-[#007C52]/10 relative">
        <!-- Effetto di luce al passaggio del mouse -->
        <div
          class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-[#005188]/0 via-[#005188]/0 to-[#005188]/5 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none">
        </div>
        <p class="relative z-10">Si ? verificato un errore imprevisto</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.text-shadow-sm {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}
</style>
