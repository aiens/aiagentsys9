<template>
  <div
    :class="toastClasses"
    class="pointer-events-auto max-w-sm w-full bg-white shadow-lg rounded-lg ring-1 ring-black ring-opacity-5 overflow-hidden"
  >
    <div class="p-4">
      <div class="flex items-start">
        <!-- Icon -->
        <div class="flex-shrink-0">
          <component
            :is="iconComponent"
            :class="iconClasses"
            class="h-6 w-6"
          />
        </div>

        <!-- Content -->
        <div class="ml-3 w-0 flex-1">
          <p v-if="toast.title" class="text-sm font-medium text-gray-900">
            {{ toast.title }}
          </p>
          <p :class="toast.title ? 'mt-1 text-sm text-gray-500' : 'text-sm text-gray-900'">
            {{ toast.message }}
          </p>
          
          <!-- Actions -->
          <div v-if="toast.actions && toast.actions.length > 0" class="mt-3 flex space-x-2">
            <button
              v-for="action in toast.actions"
              :key="action.label"
              type="button"
              :class="[
                'text-sm font-medium rounded-md px-3 py-1.5 transition-colors duration-200',
                action.primary
                  ? 'bg-primary-600 text-white hover:bg-primary-700'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
              @click="handleAction(action)"
            >
              {{ action.label }}
            </button>
          </div>
        </div>

        <!-- Close button -->
        <div v-if="toast.closable" class="ml-4 flex-shrink-0 flex">
          <button
            type="button"
            class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            @click="$emit('close', toast.id)"
          >
            <span class="sr-only">Close</span>
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Progress bar for auto-dismiss -->
    <div
      v-if="!toast.persistent && toast.duration > 0"
      class="h-1 bg-gray-200"
    >
      <div
        :class="progressBarClasses"
        class="h-full transition-all ease-linear"
        :style="{ width: progressWidth + '%' }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  toast: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close'])

const progressWidth = ref(100)

// Icon mapping
const iconComponents = {
  success: CheckCircleIcon,
  warning: ExclamationTriangleIcon,
  error: XCircleIcon,
  info: InformationCircleIcon,
}

// Computed properties
const toastClasses = computed(() => {
  const borderClasses = {
    success: 'border-l-4 border-success-400',
    warning: 'border-l-4 border-warning-400',
    error: 'border-l-4 border-danger-400',
    info: 'border-l-4 border-primary-400',
  }

  return borderClasses[props.toast.type] || borderClasses.info
})

const iconComponent = computed(() => {
  return iconComponents[props.toast.type] || iconComponents.info
})

const iconClasses = computed(() => {
  const colorClasses = {
    success: 'text-success-400',
    warning: 'text-warning-400',
    error: 'text-danger-400',
    info: 'text-primary-400',
  }

  return colorClasses[props.toast.type] || colorClasses.info
})

const progressBarClasses = computed(() => {
  const colorClasses = {
    success: 'bg-success-400',
    warning: 'bg-warning-400',
    error: 'bg-danger-400',
    info: 'bg-primary-400',
  }

  return colorClasses[props.toast.type] || colorClasses.info
})

// Methods
const handleAction = (action) => {
  if (action.handler) {
    action.handler()
  }
  
  if (action.closeOnClick !== false) {
    emit('close', props.toast.id)
  }
}

// Auto-dismiss progress
onMounted(() => {
  if (!props.toast.persistent && props.toast.duration > 0) {
    const startTime = Date.now()
    const duration = props.toast.duration
    
    const updateProgress = () => {
      const elapsed = Date.now() - startTime
      const remaining = Math.max(0, duration - elapsed)
      progressWidth.value = (remaining / duration) * 100
      
      if (remaining > 0) {
        requestAnimationFrame(updateProgress)
      }
    }
    
    updateProgress()
  }
})
</script>
