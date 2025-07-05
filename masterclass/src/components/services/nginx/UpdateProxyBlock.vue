<template>
  <div
    class="fixed inset-0 bg-white flex items-center justify-center z-50 p-8 transition-all duration-500 ease-out"
  >
    <div
      class="w-full max-w-7xl h-full max-h-[95vh] mx-auto flex shadow-2xl transform transition-all duration-500 ease-out bg-white overflow-auto"
    >
      <div
        v-if="showParamsModal"
        class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-auto animate-fade-in"
      >
        <div
          class="bg-white rounded-3xl shadow-2xl w-full max-w-6xl max-h-[95vh] flex flex-col overflow-hidden animate-slide-up hover-lift"
        >
          <!-- Modal Header -->
          <div class="primary-gradient text-white p-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold">Location Parameters</h3>
                <p class="text-white/80 text-sm">Configure directives for your locations</p>
              </div>
            </div>
            <button
              @click="showParamsModal = false"
              class="w-10 h-10 rounded-xl hover:bg-white/20 transition-all duration-200 flex items-center justify-center group"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 group-hover:rotate-90 transition-transform duration-200"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <!-- Modal Content -->
          <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
            <!-- Available Parameters Section -->
            <div
              class="w-full lg:w-2/5 bg-gradient-to-br from-slate-50 to-slate-100 p-6 border-b lg:border-b-0 lg:border-r border-slate-200 overflow-y-auto custom-scrollbar"
            >
              <div class="mb-6">
                <h4 class="text-lg font-semibold mb-2 flex items-center text-slate-800">
                  <div
                    class="w-8 h-8 bg-gradient-to-r from-[#005188] to-[#007C52] rounded-lg flex items-center justify-center mr-3"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-4 w-4 text-white"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                      />
                    </svg>
                  </div>
                  Available Parameters
                </h4>
                <p class="text-slate-600 text-sm">Select parameters to add to your location</p>
              </div>

              <div class="space-y-3">
                <div v-for="param in uniqueLocationParameters" :key="param.param_name + '-' + param.param_value"
                  class="group p-4 rounded-2xl bg-white/70 hover:bg-white hover:shadow-lg transition-all duration-300 cursor-pointer hover-lift"
                  @click="toggleParameter(param)">
                  <div class="flex items-start space-x-3">
                    <div class="flex items-center justify-center w-5 h-5 mt-0.5">
                      <input :id="'param-' + param.param_name + '-' + param.param_value" type="checkbox"
                        :checked="isParameterActive(param)" @change="toggleParameter(param)"
                        class="w-4 h-4 text-[#005188] focus:ring-[#005188] focus:ring-offset-0 border-2 border-slate-300 rounded-md transition-all duration-200" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <label :for="'param-' + param.param_name + '-' + param.param_value"
                        class="block text-sm font-medium text-slate-800 cursor-pointer group-hover:text-[#005188] transition-colors duration-200">
                        {{ param.param_name }}
                      </label>
                      <p class="text-xs text-slate-500 mt-1 break-words">{{ param.param_value }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Current Configuration Section -->
            <div
              class="w-full lg:w-3/5 flex flex-col overflow-hidden"
              v-if="selectedLocationId !== null"
            >
              <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-white to-slate-50">
                <h4 class="text-lg font-semibold flex items-center text-slate-800">
                  <div
                    class="w-8 h-8 bg-gradient-to-r from-[#007C52] to-[#005188] rounded-lg flex items-center justify-center mr-3"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-4 w-4 text-white"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                      />
                    </svg>
                  </div>
                  <div class="flex flex-col">
                    <span>Active Directives</span>
                    <span
                      class="text-sm font-mono text-[#005188] bg-blue-50 px-3 py-1 rounded-full mt-1 inline-block max-w-fit"
                    >
                      {{ locations[selectedLocationId].path || '/' }}
                    </span>
                  </div>
                </h4>
              </div>

              <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                <div class="space-y-4">
                  <div
                    v-for="(directive, index) in locations[selectedLocationId].directives"
                    :key="index"
                    class="group p-4 rounded-2xl bg-white border border-slate-200 hover:border-[#005188] hover:shadow-lg transition-all duration-300 hover-lift"
                  >
                    <div class="flex items-center gap-3">
                      <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="relative">
                          <input
                            v-model="directive.param_name"
                            type="text"
                            class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#005188] focus:border-[#005188] transition-all duration-200 bg-slate-50 focus:bg-white"
                            placeholder="Directive name"
                          />
                        </div>
                        <div class="relative">
                          <input
                            v-model="directive.param_value"
                            type="text"
                            class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#005188] focus:border-[#005188] transition-all duration-200 bg-slate-50 focus:bg-white"
                            placeholder="Directive value"
                          />
                        </div>
                      </div>
                      <button
                        @click="removeDirective(index)"
                        class="w-10 h-10 rounded-xl text-red-500 hover:text-red-600 hover:bg-red-50 transition-all duration-200 flex items-center justify-center group-hover:scale-110"
                        title="Remove directive"
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-5 w-5"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                          />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <div
                  v-if="locations[selectedLocationId].directives.length === 0"
                  class="text-center py-16"
                >
                  <div
                    class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-8 w-8 text-slate-400"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                      />
                    </svg>
                  </div>
                  <p class="text-slate-500 font-medium">No directives configured yet</p>
                  <p class="text-slate-400 text-sm mt-1">Add your first directive below</p>
                </div>
              </div>

              <!-- Add New Directive -->
              <div class="p-6 border-t border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex gap-3">
                  <div class="flex-1 relative">
                    <input
                      v-model="newDirective.param_name"
                      type="text"
                      placeholder="Directive name"
                      class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#005188] focus:border-[#005188] transition-all duration-200 bg-white"
                      @keyup.enter="addCustomDirective"
                    />
                  </div>
                  <div class="flex-1 relative">
                    <input
                      v-model="newDirective.param_value"
                      type="text"
                      placeholder="Directive value"
                      class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#005188] focus:border-[#005188] transition-all duration-200 bg-white"
                      @keyup.enter="addCustomDirective"
                    />
                  </div>
                  <button
                    @click="addCustomDirective"
                    class="px-6 py-3 bg-gradient-to-r from-[#005188] to-[#007C52] text-white text-sm rounded-xl hover:from-[#0066a7] hover:to-[#009d67] transition-all duration-200 font-medium shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!newDirective.param_name.trim()"
                  >
                    Add
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div
            class="bg-gradient-to-r from-slate-50 to-white px-6 py-4 flex justify-between items-center border-t border-slate-200"
          >
            <button
              @click="resetToDefaults"
              class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all duration-200 font-medium"
            >
              Reset to Defaults
            </button>
            <div class="flex space-x-3">
              <button
                @click="showParamsModal = false"
                class="px-6 py-2 text-sm text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all duration-200 font-medium"
              >
                Cancel
              </button>
              <button
                @click="saveDirectives"
                class="px-6 py-2 bg-gradient-to-r from-[#005188] to-[#007C52] text-white text-sm rounded-xl hover:from-[#0066a7] hover:to-[#009d67] transition-all duration-200 font-medium shadow-lg hover:shadow-xl"
              >
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Sidebar (35%) with better padding -->
      <div
        class="w-[35%] flex flex-col gap-9 h-full bg-gradient-to-br from-[#003d6b] via-[#005188] to-[#007C52] text-white relative overflow-hidden"
      >
        <!-- Enhanced Header with padding -->
        <div class="relative flex top-3 mt-auto items-center justify-around">
          <div class="flex items-center gap-2 space-x-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-7 w-7"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"
                />
              </svg>
            </div>
            <h1 class="text-4xl font-bold tracking-tight">Server Config</h1>
          </div>

          <button
            @click="$emit('close')"
            class="p-3 rounded-lg hover:bg-white/20 transition-all duration-300 group"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 group-hover:rotate-90 transition-transform duration-300"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <!-- Enhanced Form Fields -->
        <div
          class="flex flex-col justify-start h-full flex-grow overflow-y-auto px-8 py-6 gap-6 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent"
        >
          <!-- Server Title -->
          <div class="flex flex-col">
            <label
              class="flex items-center space-x-3 text-sm font-semibold uppercase tracking-wider text-white/80"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                />
              </svg>
              <span>Server Name</span>
            </label>
            <div class="relative">
              <input
                v-model="serverTitle"
                type="text"
                placeholder="Enter server title (e.g., Coopolis)"
                class="w-full indent-2 px-6 py-4 rounded-xl border-2 border-white/20 text-white bg-white/10 placeholder-white/50 focus:border-white/40 focus:bg-white/20 focus:ring-0 transition-all duration-300 backdrop-blur-sm text-lg"
              />
              <div
                class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/5 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
              ></div>
            </div>
          </div>

          <!-- Port -->
          <div class="flex flex-col">
            <label
              class="flex items-center space-x-3 text-sm font-semibold uppercase tracking-wider text-white/80"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                />
              </svg>
              <span>Port</span>
            </label>
            <div class="relative">
              <input
                v-model="serverPort"
                type="number"
                placeholder="443"
                class="w-full px-6 py-4 indent-2 rounded-xl border-2 border-white/20 bg-white/10 text-white placeholder-white/50 focus:border-white/40 focus:bg-white/20 focus:ring-0 transition-all duration-300 backdrop-blur-sm text-lg"
              />
            </div>
          </div>

          <!-- Protocol Selection -->
          <div class="flex flex-col">
            <label
              class="flex items-center space-x-3 text-sm font-semibold uppercase tracking-wider text-white/80"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                />
              </svg>
              <span>Protocol</span>
            </label>
            <div class="space-y-3">
              <label
                v-for="option in protocolOptions"
                :key="option"
                class="flex items-center indent-2 justify-between p-4 rounded-xl hover:bg-white/15 transition-all duration-300 cursor-pointer group border border-white/10 hover:border-white/20 backdrop-blur-sm"
              >
                <span class="text-lg font-medium">{{ option }}</span>
                <div class="relative">
                  <input
                    type="radio"
                    name="protocol"
                    :value="option"
                    v-model="selectedProtocol"
                    class="sr-only indent-2"
                  />
                  <div
                    class="w-8 h-8 rounded-full border-2 border-white/40 flex items-center justify-center transition-all duration-300 group-hover:border-white/60 group-hover:scale-110"
                  >
                    <div
                      v-if="selectedProtocol === option"
                      class="w-4 h-4 rounded-full bg-white shadow-lg animate-pulse"
                    ></div>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- Certificate -->
          <div class="flex flex-col" v-if="selectedProtocol !== 'HTTP'">
            <label
              class="flex items-center space-x-3 text-sm font-semibold uppercase tracking-wider text-white/80"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              <span>Certificate</span>
            </label>
            <div class="relative">
              <select
                v-model="selectedCertificate"
                class="w-full px-6 py-4 indent-2 bg-white/10 rounded-xl border-2 border-white/20 focus:border-white/40 focus:bg-white/20 focus:ring-0 text-white placeholder-white/50 appearance-none transition-all duration-300 backdrop-blur-sm text-lg cursor-pointer"
              >
                <option disabled value="" class="bg-gray-800 text-white">Select certificate</option>
                <option
                  v-for="cert in certificates"
                  :key="cert"
                  :value="cert"
                  class="bg-gray-800 text-white indent-2"
                >
                  {{ cert?.split('/').pop() }}
                </option>
              </select>
              <svg
                class="absolute right-4 top-1/2 transform -translate-y-1/2 h-6 w-6 text-white/60 pointer-events-none"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Enhanced Action Buttons with padding -->
        <div class="pt-4 p-2 border-t border-white/20">
          <button
            @click="saveConfiguration"
            class="w-full bg-white text-[#005188] font-bold rounded-lg hover:bg-white/90 transition-all duration-50 flex items-center justify-center space-x-4 text-2xl shadow-lg hover:shadow-xl transform hover:scale-101"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 group-hover:rotate-12 transition-transform duration-300"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span>Save Configuration</span>
          </button>
          <button
            @click="$emit('close')"
            class="w-full bg-transparent text-white font-semibold hover:bg-white/10 transition-all duration-300 text-2xl"
          >
            Cancel
          </button>
        </div>
      </div>

      <!-- Enhanced Main Content (65%) with better padding -->
      <div class="w-[65%] bg-gradient-to-br flex flex-col from-gray-50 to-white relative">
        <!-- Enhanced Header -->
        <div class="relative flex items-center justify-around">
          <div class="flex items-center space-x-4 mb-3">
            <div>
              <h2 class="text-4xl font-bold text-gray-800 tracking-tight">
                {{ serverTitle || 'New Server Configuration' }}
              </h2>
              <p class="text-lg text-gray-600 mt-2">
                Configure server names, locations, and parameters
              </p>
            </div>
          </div>
        </div>

        <!-- Enhanced Content Area with padding -->
        <div
          class="flex flex-col flex-1 overflow-y-auto px-8 gap-4 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent"
        >
          <!-- Enhanced Domain Names Section -->
          <div
            class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border-2 border-blue-200 shadow-lg relative"
          >
            <div class="flex items-center justify-between relative p-2">
              <h3 class="text-2xl gap-2 font-bold text-gray-800 flex items-center">
                <div
                  class="w-10 h-10 bg-[#005188] rounded-xl flex items-center justify-center shadow-md"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-white"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"
                    />
                  </svg>
                </div>
                <span>Domain Names</span>
              </h3>
              <button
                @click="addServerName"
                class="px-6 py-3 bg-gradient-to-r from-[#005188] to-[#0066a7] text-white text-lg rounded-xl hover:from-[#0066a7] hover:to-[#005188] transition-all duration-300 flex items-center space-x-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                  />
                </svg>
                <span>Add Domain</span>
              </button>
            </div>

            <div class="p-2">
              <div class="relative">
                <input
                  v-model="serverNameSearch"
                  type="text"
                  placeholder="Search server names..."
                  class="w-full pl-14 pr-6 py-4 indent-2 text-lg border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-[#005188]/20 focus:border-[#005188] transition-all duration-300 shadow-sm"
                />
                <svg
                  class="absolute right-5 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
              </div>

              <div class="flex p-2 pt-3">
                <input
                  v-model="newServerName"
                  @keyup.enter="addServerName"
                  type="text"
                  placeholder="Add new server name (e.g., api.example.com)"
                  class="flex-grow px-6 py-4 text-lg indent-2 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-[#005188]/20 focus:border-[#005188] transition-all duration-300 shadow-sm"
                />
              </div>

              <div
                v-if="filteredServerNames.length > 0"
                class="mr-2 ml-2 border-2 border-gray-200 rounded-xl divide-y-2 divide-gray-200 max-h-80 overflow-y-auto shadow-inner bg-white"
              >
                <div
                  v-for="(name, index) in filteredServerNames"
                  :key="index"
                  class="hover:bg-blue-50 indent-2 flex justify-between items-center group transition-all duration-300 hover:shadow-sm"
                >
                  <span class="p-2 text-lg font-medium text-gray-700 flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-400 rounded-full shadow-sm"></div>
                    <span>{{ name }}</span>
                  </span>
                  <button
                    @click="removeServerName(index)"
                    class="text-red-500 hover:text-red-700 p-3 rounded-xl hover:bg-red-50 transition-all duration-300 transform scale-90 hover:scale-100"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </div>
              </div>

              <div v-else-if="serverNames.length === 0" class="text-center py-1">
                <div
                  class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-8 w-8 text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                    />
                  </svg>
                </div>
                <p class="text-lg text-gray-500 italic">No server names added yet</p>
                <p class="text-sm text-gray-400 mt-2">Click "Add Domain" to get started</p>
              </div>
            </div>
          </div>

          <!-- Enhanced Locations Section -->
          <div
            class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border-2 border-green-200 shadow-lg relative"
          >
            <div class="flex items-center justify-between p-2 relative">
              <h3 class="text-2xl font-bold gap-2 text-gray-800 flex items-center space-x-4">
                <div
                  class="w-10 h-10 bg-[#007C52] rounded-xl flex items-center justify-center shadow-md"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-white"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                </div>
                <span>Locations</span>
              </h3>
              <button
                @click="addLocation"
                class="px-6 py-3 bg-gradient-to-r from-[#005188] to-[#0066a7] text-white text-lg rounded-xl hover:from-[#0066a7] hover:to-[#005188] transition-all duration-300 flex items-center space-x-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                  />
                </svg>
                <span>Add Location</span>
              </button>
            </div>

            <div class="space-y-6">
              <div v-if="locations.length === 0" class="text-center py-12">
                <div
                  class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-8 w-8 text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                </div>
                <p class="text-lg text-gray-500 italic">No locations configured yet</p>
                <p class="text-sm text-gray-400 mt-2">Click "Add Location" to configure routing</p>
              </div>

              <div
                v-for="(location, index) in locations"
                :key="index"
                class="bg-white p-4 rounded-2xl border-2 border-gray-200 transition-all duration-300 hover:shadow-lg hover:border-green-300 group"
              >
                <div class="flex items-start space-x-6">
                  <div class="flex-grow space-y-6">
                    <div class="flex items-center space-x-4">
                      <div
                        class="w-24 text-md gap-2 font-bold text-gray-600 uppercase tracking-wider flex items-center space-x-2"
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-6 w-6"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m0 8V9m0 0L9 7"
                          />
                        </svg>
                        <span>Path</span>
                      </div>
                      <input
                        v-model="location.path"
                        type="text"
                        placeholder="/api/v1 or /example"
                        class="flex-grow indent-2 px-6 py-2 text-lg border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-[#005188]/20 focus:border-[#005188] transition-all duration-300 shadow-sm"
                      />
                    </div>
                    <div class="flex items-center space-x-4">
                      <div
                        class="w-24 text-md gap-2 font-bold text-gray-600 uppercase tracking-wider flex items-center space-x-2"
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-4 w-4"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"
                          />
                        </svg>
                        <span>Proxy</span>
                      </div>
                      <input
                        v-model="location.proxy_pass"
                        type="text"
                        placeholder="http://backend:8080 or upstream server"
                        class="flex-grow px-6 py-2 indent-2 text-lg border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-[#005188]/20 focus:border-[#005188] transition-all duration-300 shadow-sm"
                      />
                    </div>
                  </div>
                  <div class="flex flex-col space-y-3">
                    <button
                      @click="removeLocation(index)"
                      class="p-3 text-red-500 hover:bg-red-50 rounded-xl transition-all duration-300 transform hover:scale-110 group-hover:opacity-100 opacity-60"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                    <button
                      @click="showLocationHelp(index)"
                      class="p-3 text-[#005188] hover:bg-blue-50 rounded-xl transition-all duration-300 transform hover:scale-110 group-hover:opacity-100 opacity-60"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Enhanced Server Parameters Section -->
          <div
            class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl border-2 border-purple-200 shadow-lg relative"
          >
            <div class="flex items-center justify-between p-2 relative">
              <h3 class="text-2xl font-bold text-gray-800 gap-2 flex items-center space-x-4">
                <div
                  class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-white"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                </div>
                <span>Server Parameters</span>
              </h3>
              <button
                @click="addParameter"
                class="px-6 py-3 bg-gradient-to-r from-[#005188] to-[#0066a7] text-white text-lg rounded-xl hover:from-[#0066a7] hover:to-[#005188] transition-all duration-300 flex items-center space-x-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                  />
                </svg>
                <span>Add Parameter</span>
              </button>
            </div>

            <div class="space-y-6">
              <div v-if="parameters.length === 0" class="text-center">
                <div
                  class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-8 w-8 text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                    />
                  </svg>
                </div>
                <p class="text-lg text-gray-500 italic">No parameters configured yet</p>
                <p class="text-sm text-gray-400 mt-2">
                  Click "Add Parameter" to configure server settings
                </p>
              </div>

              <div
                v-for="(param, index) in parameters"
                :key="index"
                class="bg-white p-6 rounded-2xl border-2 border-gray-200 hover:shadow-lg hover:border-purple-300 transition-all duration-300 group"
              >
                <div class="grid grid-cols-12 gap-4 items-center">
                  <div class="col-span-5">
                    <label class="block text-sm font-medium text-gray-600 mb-2"
                      >Parameter Name</label
                    >
                    <input
                      v-model="param.param_name"
                      type="text"
                      placeholder="e.g., max_connections, timeout"
                      class="w-full indent-2 px-4 py-3 text-lg border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300 shadow-sm"
                    />
                  </div>
                  <div class="col-span-5">
                    <label class="block text-sm font-medium text-gray-600 mb-2"
                      >Parameter Value</label
                    >
                    <input
                      v-model="param.param_value"
                      type="text"
                      placeholder="redirect"
                      class="w-full px-4 indent-2 py-3 text-lg border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300 shadow-sm"
                    />
                  </div>
                  <div class="col-span-2 flex justify-end space-x-2">
                    <button
                      @click="removeParameter(index)"
                      class="p-3 text-red-500 hover:bg-red-50 rounded-xl transition-all duration-300 transform hover:scale-110 group-hover:opacity-100 opacity-60"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar styles */
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: rgba(156, 163, 175, 0.7);
}

.scrollbar-thumb-white\/20::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
  background: rgba(209, 213, 219, 0.8);
}

.scrollbar-track-gray-100::-webkit-scrollbar-track {
  background: rgba(243, 244, 246, 0.5);
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.hover-lift {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}



.primary-gradient {
  background: linear-gradient(135deg, #005188 0%, #007c52 100%);
}
/* Enhanced animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideIn {
  from {
    transform: translateX(-100%);
  }

  to {
    transform: translateX(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

/* Backdrop blur fallback */
.backdrop-blur-md {
  backdrop-filter: blur(12px);
}

.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Enhanced hover effects */
.hover\:shadow-3xl:hover {
  box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
}

/* Focus ring enhancements */
.focus\:ring-4:focus {
  ring-width: 4px;
}

/* Gradient text */
.bg-gradient-text {
  background: linear-gradient(135deg, #005188, #007c52);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
</style>
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useNginxStore } from '@/stores/services/nginx/nginx'
import type { Server, Location, Parameter } from '@/stores/services/nginx/nginx'
const emit = defineEmits<{
  close: []
}>()

const props = defineProps<{
  serverId: number
}>()

const showParamsModal = ref(false)
const selectedLocationId = ref<number | null>(null)

const showLocationHelp = (index: number): void => {
  selectedLocationId.value = index
  showParamsModal.value = true
}

const nginxStore = useNginxStore()
const newDirective = ref({
  param_name: '',
  param_value: '',
  is_common: false
})

// Computed property to get unique parameters
const uniqueLocationParameters = computed(() => {
  const seen = new Set()
  return nginxStore.locationParameters.filter((param) => {
    if (seen.has(param.param_value) && seen.has(param.param_name)) return false
    seen.add(param.param_value)
    return true
  })
})

const isParameterActive = (param: { param_name: string; param_value: string }) => {
  if (selectedLocationId.value === null) return false
  const location = locations.value[selectedLocationId.value]
  return location.directives.some((d) => d.param_name === param.param_name && d.param_value === param.param_value)
}

const toggleParameter = (param: { param_name: string; param_value: string, is_common?: boolean }) => {
  if (selectedLocationId.value === null) return

  const location = locations.value[selectedLocationId.value]
  const existingIndex = location.directives.findIndex(
    (d) => d.param_name === param.param_name && d.param_value === param.param_value,
  )

  if (existingIndex >= 0) {
    // Remove exact match
    location.directives.splice(existingIndex, 1)
  } else {
    // Add if doesn't exist
    location.directives.push({
      param_name: param.param_name,
      param_value: param.param_value,
      is_common: false
    })
  }
}

const addCustomDirective = () => {
  if (selectedLocationId.value === null || !newDirective.value.param_name.trim()) return

  const location = locations.value[selectedLocationId.value]

  // Check if directive already exists
  const existingIndex = location.directives.findIndex(
    (d) => d.param_name === newDirective.value.param_name.trim() && d.param_value === newDirective.value.param_value.trim(),
  )

  if (existingIndex >= 0) {
    // Update existing directive
    location.directives[existingIndex].param_value = newDirective.value.param_value.trim()
  } else {
    // Add new directive
    location.directives.push({
      param_name: newDirective.value.param_name.trim(),
      param_value: newDirective.value.param_value.trim(),
      is_common: false
    })
  }

  newDirective.value = { param_name: '', param_value: ''}
}

const removeDirective = (index: number) => {
  if (selectedLocationId.value === null) return
  locations.value[selectedLocationId.value].directives.splice(index, 1)
}

const resetToDefaults = () => {
  if (selectedLocationId.value === null) return

  // Get unique default parameters
  const uniqueDefaults = uniqueLocationParameters.value.map((p) => ({ ...p }))
  locations.value[selectedLocationId.value].directives = uniqueDefaults
}

const saveDirectives = () => {
  showParamsModal.value = false
}
// Form data
const serverTitle = ref('')
const serverPort = ref(80)
const serverNames = ref<string[]>([])
const newServerName = ref('')
const serverNameSearch = ref('')
const selectedProtocol = ref<'HTTP' | 'HTTPS' | 'MTLS'>('HTTP')
const selectedCertificate = ref<string>('')
const locations = ref<Omit<Location, 'location_id'>[]>([])
const parameters = ref<Parameter[]>([])

// UI state
const isLoading = ref(true)
const error = ref<string | null>(null)

// Computed properties
const filteredServerNames = computed(() =>
  serverNames.value.filter((name) =>
    name.toLowerCase().trim().includes(serverNameSearch.value.toLowerCase()),
  ),
)

const certificates = computed(() => nginxStore.certificates.map((cert) => cert.cert_name))

const protocolOptions = computed(() => {
  const options = ['HTTP', 'HTTPS'] as const
  if (nginxStore.selfSignedCertificates.length > 0) {
    return [...options, 'MTLS'] as const
  }
  return options
})

// Methods
const addServerName = (): void => {
  if (newServerName.value.trim()) {
    serverNames.value.push(newServerName.value.trim())
    newServerName.value = ''
  }
}

const removeServerName = (index: number): void => {
  serverNames.value.splice(index, 1)
}

const addLocation = (): void => {
  // Create new location with default parameters
  const newLocation: Omit<Location, 'location_id'> = {
    path: '',
    proxy_pass: '',
    directives: nginxStore.locationParameters.map((param) => ({
      param_name: param.param_name,
      param_value: param.param_value,
    })),
  }
  locations.value.push(newLocation)
}

const removeLocation = (index: number): void => {
  if (locations.value.length > 1) {
    locations.value.splice(index, 1)
  }
}

const addParameter = (): void => {
  parameters.value.push({
    param_name: '',
    param_value: '',
  })
}

const removeParameter = (index: number): void => {
  parameters.value.splice(index, 1)
}

const loadServerData = async (): Promise<void> => {
  try {
    isLoading.value = true
    error.value = null
    await nginxStore.fetchServers()

    const server = nginxStore.getServerById(props.serverId)
    if (!server) throw new Error('Server not found')

    // Populate form data
    serverTitle.value = server.server_title
    serverPort.value = server.port
    serverNames.value = server.server_name.split(' ')
    selectedProtocol.value = server.is_mtls ? 'MTLS' : server.ssl_enabled ? 'HTTPS' : 'HTTP'

    // For certificates
    if (selectedProtocol.value !== 'HTTP') {
      const matchedCert = nginxStore.certificates.find(
        (cert) => cert.cert_path === server.ssl_certificate,
      )
      selectedCertificate.value = matchedCert?.cert_name || ''
    }

    // Load locations EXACTLY as they are from server
    locations.value = server.locations.map((loc) => ({
      path: loc.path,
      proxy_pass: loc.proxy_pass,
      directives: [...loc.directives], // Keep original directives
    }))

    parameters.value = [...server.directives]
  } catch (err) {
    error.value = (err as Error).message
    console.error('Failed to load server data:', err)
  } finally {
    isLoading.value = false
  }
}

const saveConfiguration = async (): Promise<void> => {
  try {
    error.value = null

    // Validate required fields
    if (!serverTitle.value.trim()) {
      throw new Error('Server title is required')
    }

    if (serverNames.value.length === 0) {
      throw new Error('At least one server name is required')
    }

    const validLocations = locations.value
      .filter((loc) => loc.path.trim() && loc.proxy_pass.trim())
      .map((loc) => ({
        path: loc.path.trim(),
        proxy_pass: loc.proxy_pass.trim(),
        directives: loc.directives
          .filter((d) => d.param_name.trim() && d.param_value.trim())
          .map((d) => ({
            param_name: d.param_name.trim(),
            param_value: d.param_value.trim(),
            is_common: !!d.is_common || false,
          })),
      }))

    if (validLocations.length === 0 && parameters.value.length === 0) {
      throw new Error('At least one valid location or server parameter is required')
    }
    const matchedCert = computed(() => {
      return nginxStore.certificates.find(
        (cert) => cert.cert_name === selectedCertificate.value.trim(),
      )
    })
    const updateData: Partial<Server> = {
      server_title: serverTitle.value.trim(),
      port: serverPort.value,
      ssl_enabled: selectedProtocol.value !== 'HTTP',
      is_mtls: selectedProtocol.value === 'MTLS',
      is_http2: selectedProtocol.value === 'HTTPS',
      server_name: serverNames.value.join(' ').trim(),
      locations: validLocations.map((loc) => ({
        path: loc.path,
        proxy_pass: loc.proxy_pass,
        directives: loc.directives,
      })),
      directives: parameters.value
        .filter((param) => param.param_name.trim() && param.param_value.trim())
        .map((param) => ({
          param_name: param.param_name.trim(),
          param_value: param.param_value.trim(),
          is_common: param.is_common || false,
        })),
      ssl_certificate: selectedProtocol.value !== 'HTTP' ? matchedCert.value?.cert_path : '',
      ssl_certificate_key: selectedProtocol.value !== 'HTTP' ? matchedCert.value?.key_path : '',
      ssl_client_certificate:
        selectedProtocol.value === 'MTLS'
          ? '' // You'll need to handle this properly
          : '',
      ssl_verify_client: selectedProtocol.value === 'MTLS' ? 'on' : 'off',
    }
    console.log(updateData)
    await nginxStore.updateServer(props.serverId, updateData)
    emit('close')
  } catch (err) {
    error.value = (err as Error).message
    console.error('Failed to save configuration:', err)
  }
}

// Initialize component
onMounted(async () => {
  try {
    await Promise.all([
      nginxStore.fetchCertificates(),
      nginxStore.fetchParameters(),
      loadServerData(),
    ])
  } catch (err) {
    error.value = (err as Error).message
    console.error('Initialization failed:', err)
  }
})

// Watch for serverId changes in case this is a reusable component
watch(
  () => props.serverId,
  (newId) => {
    if (newId) {
      loadServerData()
    }
  },
)
</script>
