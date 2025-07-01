<template>
  <div class="rsnapshot-manager">
    <!-- Tab Navigation -->
    <div class="tab-navigation">
      <button v-for="tab in tabs" :key="tab.id" :class="['tab-button', { active: activeTab === tab.id }]"
        @click="activeTab = tab.id">
        {{ tab.label }}
      </button>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
      <input v-model="searchQuery" type="text" placeholder="Search configuration..." class="search-input" />
      <div class="search-icon">🔍</div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading">
      Loading rsnapshot configuration...
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="error">
      Error: {{ error }}
    </div>

    <!-- Tab Content -->
    <div v-else class="tab-content">
      <!-- General Configuration Tab -->
      <div v-if="activeTab === 'general'" class="config-section">
        <div class="section-header">
          <h2>General Configuration</h2>
          <div class="header-actions">
            <div v-if="isUpdating" class="save-indicator">
              <span class="saving">Auto-saving...</span>
            </div>
            <button @click="showAddGeneralModal = true" class="btn-add-new">
              + Add General Item
            </button>
          </div>
        </div>

        <div v-if="filteredGeneral.length === 0" class="no-data">
          No general configuration items found.
        </div>

        <div v-else class="config-form">
          <div v-for="item in filteredGeneral" :key="item.id" class="config-item-form">
            <div class="form-row">
              <label class="form-label">Directive:</label>
              <input v-model="item.directive" @input="debouncedUpdate(item)" class="form-input directive-input"
                type="text" />
            </div>
            <div class="form-row">
              <label class="form-label">Arguments:</label>
              <div class="args-container">
                <div v-for="(arg, index) in item.args" :key="index" class="arg-input-group">
                  <input v-model="item.args[index]" @input="debouncedUpdate(item)" class="form-input arg-input"
                    type="text" />
                  <button @click="removeArg(item, index)" class="btn-remove" title="Remove argument">
                    ×
                  </button>
                </div>
                <button @click="addArg(item)" class="btn-add" title="Add argument">
                  + Add Argument
                </button>
              </div>
            </div>
            <button @click="openDeleteModal(item, BackupItem)" class="btn-delete" title="Delete this item">
              🗑️ Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Backup Configuration Tab -->
      <div v-if="activeTab === 'backup'" class="config-section">
        <div class="section-header">
          <h2>Backup Configuration</h2>
          <div class="header-actions">
            <div v-if="isUpdating" class="save-indicator">
              <span class="saving">Auto-saving...</span>
            </div>
            <button @click="showBackupWizard = true" class="btn-add-new">
              + Add Backup
            </button>
          </div>
        </div>

        <div v-if="filteredBackups.length === 0" class="no-data">
          No backup configuration items found.
        </div>

        <div v-else class="backup-form">
          <div v-for="item in filteredBackups" :key="item.id" class="backup-item-form">
            <div class="form-row">
              <label class="form-label">Source:</label>
              <input v-model="item.source" @input="debouncedUpdate(item)" class="form-input source-input" type="text" />
            </div>

            <div class="form-row">
              <label class="form-label">Destination:</label>
              <input v-model="item.dest" @input="debouncedUpdate(item)" class="form-input dest-input" type="text" />
            </div>

            <div class="form-row">
              <label class="form-label">Parameters:</label>
              <div class="parameters-container">
                <div v-for="(param, index) in item.parameters" :key="index" class="parameter-input-group">
                  <input v-model="param.name" @input="debouncedUpdate(item)" class="form-input param-name-input"
                    type="text" placeholder="Parameter name" />
                  <input v-model="param.value" @input="debouncedUpdate(item)" class="form-input param-value-input"
                    type="text" placeholder="Parameter value" />
                  <button @click="removeParameter(item, index)" class="btn-remove" title="Remove parameter">
                    ×
                  </button>
                </div>
                <button @click="addParameter(item)" class="btn-add" title="Add parameter">
                  + Add Parameter
                </button>
              </div>
            </div>
            <buttons @click="openDeleteModal(item, BackupItem)" class="btn-delete" title="Delete this backup">
              🗑️ Delete
            </buttons>
          </div>
        </div>
      </div>

      <!-- Deploy Tab -->
      <div v-if="activeTab === 'deploy'" class="config-section">
        <div class="section-header">
          <h2>Deploy Configuration</h2>
        </div>
        <div class="deploy-placeholder">
          <button class="btn-deploy" @click="showDeployUI">
            Deploy Configuration
          </button>
        </div>
      </div>
    </div>

    <!-- Add General Item Modal -->
    <div v-if="showAddGeneralModal" class="modal-overlay" @click="closeAddGeneralModal">
      <div class="modal-content" @click.stop>
        <h3>Add General Configuration Item</h3>
        <div class="form-row">
          <label class="form-label">Directive:</label>
          <input v-model="newGeneral.directive" class="form-input" type="text" />
        </div>
        <div class="form-row">
          <label class="form-label">Arguments (comma-separated):</label>
          <input v-model="newGeneralArgs" class="form-input" type="text" placeholder="arg1, arg2, arg3" />
        </div>
        <div class="modal-actions">
          <button @click="closeAddGeneralModal" class="btn-cancel">Cancel</button>
          <button @click="addGeneralItem" class="btn-confirm">Add Item</button>
        </div>
      </div>
    </div>

    <!-- Backup Wizard Modal -->
    <div v-if="showBackupWizard" class="modal-overlay" @click="closeBackupWizard">
      <div class="modal-content wizard-modal" @click.stop>
        <h3>Add New Backup - Step {{ wizardStep }} of 4</h3>

        <!-- Step 1: Source and Destination -->
        <div v-if="wizardStep === 1" class="wizard-step">
          <div class="form-row">
            <label class="form-label">Protocol:</label>
            <div class="flex gap-4">
              <label class="checkbox-label">
                <input type="radio" value="ssh" v-model="wizardOptions.protocol" />
                SSH
              </label>
              <label class="checkbox-label">
                <input type="radio" value="rsync" v-model="wizardOptions.protocol" />
                RSYNC
              </label>
            </div>
          </div>

          <div v-if="wizardOptions.protocol === 'ssh'" class="form-row">
            <label class="form-label">SSH User:</label>
            <input v-model="sshUser" class="form-input" type="text" placeholder="e.g., root@/path/" />
          </div>

          <div v-if="wizardOptions.protocol === 'rsync'" class="form-row">
            <label class="form-label">RSYNC Path:</label>
            <input v-model="sshUser" class="form-input" type="text" placeholder="e.g., rsync://path/" />
          </div>
          <div class="form-row">
            <label class="form-label">Source:</label>
            <input v-model="newBackup.source" class="form-input" type="text" placeholder="e.g., host/path/" />
          </div>
          <div class="form-row">
            <label class="form-label">Destination:</label>
            <input v-model="newBackup.dest" class="form-input" type="text" placeholder="e.g., server-name/" />
          </div>
        </div>

        <!-- Step 2: Add Parameters -->
        <div v-if="wizardStep === 2" class="wizard-step">
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="wizardOptions.addRsyncArgs" />
              Add +rsync_long_args parameter
            </label>
          </div>
        </div>

        <!-- Step 3: Filter or Exclude -->
        <div v-if="wizardStep === 3" class="wizard-step">
          <div class="radio-group">
            <label class="radio-label">
              <input type="radio" v-model="wizardOptions.filterType" value="filter" />
              Use Filter (--filter='merge /storage/config/{{ dest }}/filter.rsync')
            </label>
            <label class="radio-label">
              <input type="radio" v-model="wizardOptions.filterType" value="exclude" />
              Use Exclude (--exclude-from=/storage/config/{{ dest }}/exclude.rsync)
            </label>
            <label class="radio-label">
              <input type="radio" v-model="wizardOptions.filterType" value="none" />
              No filter/exclude
            </label>
          </div>
        </div>

        <!-- Step 4: Password File -->
        <div v-if="wizardStep === 4" class="wizard-step">
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="wizardOptions.addPassword" />
              Add password file (--password-file=/storage/config/{{ dest }}/password.rsync)
            </label>
          </div>
        </div>

        <div class="modal-actions">
          <button v-if="wizardStep > 1" @click="wizardStep--" class="btn-cancel">Back</button>
          <button @click="closeBackupWizard" class="btn-cancel">Cancel</button>
          <button v-if="wizardStep < 4" @click="wizardStep++" class="btn-confirm">Next</button>
          <button v-if="wizardStep === 4" @click="createBackupItem" class="btn-confirm">Create Backup</button>
        </div>
      </div>
    </div>
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <h3>Confirm Delete</h3>
        <p class="text-black">Are you sure you want to delete this {{ deleteType }} item?</p>
        <div class="modal-actions">
          <button @click="closeDeleteModal" class="btn-cancel">Cancel</button>
          <button @click="confirmDelete" class="btn-delete-confirm">Delete</button>
        </div>
      </div>
    </div>

  </div>
  <Transition name="dialog">
    <div v-if="showDeployComponent" class="fixed inset-0 z-[100] flex items-center justify-center">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/80 backdrop-blur-xsm transition-opacity" @click="hideDeployUI"></div>

      <!-- Animated modal wrapper -->
      <div
        class="relative z-10 bg-gradient-to-br from-[rgba(12,12,12,0.95)] to-[rgba(20,20,20,0.98)] border border-[rgba(0,240,255,0.3)] rounded-2xl w-full max-w-3xl p-6 shadow-2xl shadow-[rgba(0,240,255,0.15)] transform transition-all">

        <!-- Animated border gradient -->
        <div class="absolute inset-0 rounded-2xl pointer-events-none">
          <div
            class="absolute -inset-1 bg-gradient-to-r from-[#00f0ff55] to-[#d000ff55] rounded-2xl blur-lg opacity-30 animate-pulse-slow">
          </div>
        </div>

        <!-- Noise overlay -->
        <div class="absolute inset-0 rounded-2xl bg-noise opacity-10 pointer-events-none"></div>

        <!-- Render your dynamic component -->
        <component :is="dynamicComponent" :message="rsnapshot" class="relative z-10" @close="hideDeployUI" />
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRsnapshotDataStore } from '@/stores/block'
import type { Component } from 'vue'
import EssentialDeploy from '@/components/Deploy.vue'

import { useModifiedFilesStore } from '@/stores/modified'
interface GeneralItem {
  id: number
  directive: string
  args: string[]
}

interface BackupItem {
  id: number
  directive: string
  source: string
  dest: string
  parameters: Array<{ name: string; value: string }>
}
const rsnapshot = "rsnapshot"
// Delete modal state
const showDeleteModal = ref(false)
const deleteType = ref<DeleteType>('general')
const deleteTarget = ref<GeneralItem | BackupItem | null>(null)
const store = useRsnapshotDataStore()
const { isLoading, hasError, error, rsnapshotData } = store

const activeTab = ref('general')
const isUpdating = ref(false)
const searchQuery = ref('')

const showDeployComponent = ref(false)
const dynamicComponent = ref<Component | null>(null)

function showDeployUI() {
  showDeployComponent.value = !showDeployComponent.value
  dynamicComponent.value = EssentialDeploy
}


// Modal states
const showAddGeneralModal = ref(false)
const showBackupWizard = ref(false)
const wizardStep = ref(1)

// New item data
const newGeneral = ref({ directive: '', args: [] as string[] })
const newGeneralArgs = ref('')
const newBackup = ref({
  id: 0,
  directive: 'backup',
  source: '',
  dest: '',
  parameters: [] as Array<{ name: string; value: string }>
})
const sshUser = ref()
const wizardOptions = ref({
  protocol: false,
  addRsyncArgs: false,
  filterType: 'none', // 'filter', 'exclude', 'none'
  addPassword: false
})

const tabs = [
  { id: 'general', label: 'General' },
  { id: 'backup', label: 'Backup' },
  { id: 'deploy', label: 'Deploy' }
]

// Computed properties for filtering
const filteredGeneral = computed(() => {
  if (!searchQuery.value) return rsnapshotData.general
  const query = searchQuery.value.toLowerCase()
  return rsnapshotData.general.filter(item =>
    item.directive.toLowerCase().includes(query) ||
    item.args.some(arg => arg.toLowerCase().includes(query))
  )
})

const filteredBackups = computed(() => {
  if (!searchQuery.value) return rsnapshotData.backups
  const query = searchQuery.value.toLowerCase()
  return rsnapshotData.backups.filter(item =>
    item.source.toLowerCase().includes(query) ||
    item.dest.toLowerCase().includes(query) ||
    item.parameters.some(param =>
      param.name.toLowerCase().includes(query) ||
      param.value.toLowerCase().includes(query)
    )
  )
})

// Debounced update function
const debouncedUpdate = debounce(async (item: GeneralItem | BackupItem) => {
  isUpdating.value = true
  try {
    await store.updateRsnapshotData(item.id, item)
  } catch (e) {
    console.error('Auto-save failed:', e)
  } finally {
    isUpdating.value = false
  }
}, 1000)

// Utility function for debouncing
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
  let timeout: NodeJS.Timeout | null = null
  return ((...args: any[]) => {
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }) as T
}

// General configuration methods
function addArg(item: GeneralItem) {
  item.args.push('')
  debouncedUpdate(item)
}

function removeArg(item: GeneralItem, index: number) {
  item.args.splice(index, 1)
  debouncedUpdate(item)
}

// Backup configuration methods
function addParameter(item: BackupItem) {
  item.parameters.push({ name: '', value: '' })
  debouncedUpdate(item)
}

function removeParameter(item: BackupItem, index: number) {
  item.parameters.splice(index, 1)
  debouncedUpdate(item)
}
const hideDeployUI = () => {
  showDeployComponent.value = false
}

// Modal methods
function closeAddGeneralModal() {
  showAddGeneralModal.value = false
  newGeneral.value = { directive: '', args: [] }
  newGeneralArgs.value = ''
}

function closeBackupWizard() {
  showBackupWizard.value = false
  wizardStep.value = 1
  newBackup.value = { id: 0, directive: 'backup', source: '', dest: '', parameters: [] }
  wizardOptions.value = {
    addRsyncArgs: false,
    filterType: 'none',
    addPassword: false
  }

}
function openDeleteModal(item: GeneralItem | BackupItem, type: DeleteType) {
  deleteTarget.value = item
  deleteType.value = type
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  deleteTarget.value = null
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  await store.deleteRsnapshotData(deleteTarget.value.id)
  closeDeleteModal()
}

async function addGeneralItem() {
  const args = newGeneralArgs.value
    .split(',')
    .map(arg => arg.trim())
    .filter(arg => arg !== '')

  const newItem = {
    directive: newGeneral.value.directive,
    args: args
  }

  try {
    // This would need to be implemented in your store
    await store.createGeneralItem(newItem)
    console.log('Creating general item:', newItem)
    closeAddGeneralModal()
  } catch (e) {
    console.error('Failed to create general item:', e)
  }
}

async function createBackupItem() {
  const parameters: Array<{ name: string; value: string }> = []

  if (wizardOptions.value.addRsyncArgs) {
    let rsyncValue = ''

    if (wizardOptions.value.filterType === 'filter') {
      rsyncValue = `--filter='merge /storage/config/${newBackup.value.dest}/filter.rsync'`
    } else if (wizardOptions.value.filterType === 'exclude') {
      rsyncValue = `--exclude-from=/storage/config/${newBackup.value.dest}/exclude.rsync`
    }

    if (wizardOptions.value.addPassword) {
      if (rsyncValue) {
        rsyncValue += ` --password-file=/storage/config/${newBackup.value.dest}/password.rsync`
      } else {
        rsyncValue = `--password-file=/storage/config/${newBackup.value.dest}/password.rsync`
      }
    }

    if (rsyncValue) {
      parameters.push({
        name: '+rsync_long_args',
        value: rsyncValue
      })
    }
  }
  if (wizardOptions.value.protocol === 'ssh') {
    newBackup.value.source = `${sshUser.value || 'root'}@${newBackup.value.source}`
  } else if (wizardOptions.value.protocol === 'rsync') {
    newBackup.value.source = `${sshUser.value || 'rsync'}://${newBackup.value.source}`
  }
  const newItem = {
    type: 'backup',
    directive: 'backup',
    source: newBackup.value.source,
    dest: newBackup.value.dest,
    parameters: parameters
  }

  try {
    // This would need to be implemented in your store
    await store.createBackupItem(newItem)
    console.log('Creating backup item:', newItem)
    closeBackupWizard()
  } catch (e) {
    console.error('Failed to create backup item:', e)
  }
}

onMounted(() => {
  store.fetchRsnapshotData()
})
onMounted(() => {
  const modifiedStore = useModifiedFilesStore()
  modifiedStore.fetchFiles()
})
</script>

<style scoped>
.rsnapshot-manager {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

/* Search Container */
.search-container {
  position: relative;
  margin-bottom: 2rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  color: black;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #1976d2;
}

.search-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
  font-size: 1.2rem;
}

/* Tab Navigation */
.tab-navigation {
  display: flex;
  border-bottom: 2px solid #e0e0e0;
  margin-bottom: 2rem;
  gap: 4px;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  border: none;
  background-color: #f5f5f5;
  color: #666;
  cursor: pointer;
  border-radius: 6px 6px 0 0;
  font-weight: 500;
  transition: all 0.2s ease;
}

.tab-button:hover {
  background-color: #e0e0e0;
  color: #333;
}

.tab-button.active {
  background-color: #1976d2;
  color: white;
  transform: translateY(2px);
}

/* Loading and Error States */
.loading,
.error {
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
  margin: 2rem 0;
}

.loading {
  background-color: #e3f2fd;
  color: #1565c0;
  border: 1px solid #bbdefb;
}

.error {
  background-color: #ffebee;
  color: #c62828;
  border: 1px solid #ffcdd2;
}

/* Tab Content */
.tab-content {
  min-height: 400px;
}

.config-section {
  background-color: #fafafa;
  border-radius: 8px;
  padding: 2rem;
  border: 1px solid #e0e0e0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e0e0e0;
}

.section-header h2 {
  margin: 0;
  color: #333;
  font-size: 1.5rem;
  font-weight: 600;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.save-indicator .saving {
  color: #1976d2;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.25rem 0.75rem;
  background-color: #e3f2fd;
  border-radius: 12px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.6;
  }
}

/* Form Styles */
.config-form,
.backup-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.config-item-form,
.backup-item-form {
  background-color: white;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-label {
  font-weight: 600;
  color: #333;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
  color: black;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
}

.directive-input {
  background-color: #e3f2fd;
  font-weight: 600;
}

.source-input {
  background-color: #fff3e0;
  font-family: 'Courier New', monospace;
}

.dest-input {
  background-color: #e8f5e8;
  font-family: 'Courier New', monospace;
}

/* Arguments Container */
.args-container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.arg-input-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.arg-input {
  flex: 1;
  font-family: 'Courier New', monospace;
  background-color: #f5f5f5;
}

/* Parameters Container */
.parameters-container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.parameter-input-group {
  display: grid;
  grid-template-columns: 200px 1fr auto;
  gap: 0.5rem;
  align-items: center;
}

.param-name-input {
  background-color: #f3e5f5;
  font-weight: 500;
}

.param-value-input {
  font-family: 'Courier New', monospace;
  background-color: #f5f5f5;
}

/* Buttons */
.btn-add,
.btn-remove,
.btn-delete,
.btn-add-new {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-add,
.btn-add-new {
  background-color: #4caf50;
  color: white;
  align-self: flex-start;
}

.btn-add:hover,
.btn-add-new:hover {
  background-color: #45a049;
}

.btn-remove,
.btn-delete {
  background-color: #f44336;
  color: white;
  padding: 0.5rem;
  min-width: 32px;
  font-size: 1.2rem;
  line-height: 1;
}

.btn-remove:hover {
  background-color: #d32f2f;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.wizard-modal {
  max-width: 600px;
}

.modal-content h3 {
  margin: 0 0 1.5rem 0;
  color: #333;
}

.wizard-step {
  margin-bottom: 2rem;
}

.checkbox-group,
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.checkbox-label,
.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  cursor: pointer;
  color: black;
}

.checkbox-label input,
.radio-label input {
  margin: 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 2rem;
}

.btn-cancel,
.btn-confirm {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-delete-confirm {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  background-color: #d32f2f;
  transition: background-color 0.2s ease;
}

.btn-cancel {
  background-color: #ccc;
  color: #333;
}

.btn-cancel:hover {
  background-color: #bbb;
}

.btn-confirm {
  background-color: #1976d2;
  color: white;
}

.btn-confirm:hover {
  background-color: #1565c0;
}

/* Deploy Section */
.deploy-placeholder {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.btn-deploy {
  padding: 1rem 2rem;
  background-color: green;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  margin-top: 1rem;
}

.no-data {
  color: #666;
  font-style: italic;
  padding: 2rem;
  text-align: center;
  background-color: #f5f5f5;
  border-radius: 4px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .parameter-input-group {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }

  .arg-input-group {
    flex-direction: column;
    align-items: stretch;
  }

  .tab-navigation {
    flex-direction: column;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-actions {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
