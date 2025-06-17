<template>
  <Teleport to="body">
    <div class="fixed inset-0 pointer-events-none z-50">
      <!-- Top Right -->
      <div class="absolute top-4 right-4 space-y-2">
        <TransitionGroup
          name="toast"
          tag="div"
          class="space-y-2"
        >
          <ToastItem
            v-for="toast in topRightToasts"
            :key="toast.id"
            :toast="toast"
            @close="removeToast"
          />
        </TransitionGroup>
      </div>

      <!-- Top Left -->
      <div class="absolute top-4 left-4 space-y-2">
        <TransitionGroup
          name="toast"
          tag="div"
          class="space-y-2"
        >
          <ToastItem
            v-for="toast in topLeftToasts"
            :key="toast.id"
            :toast="toast"
            @close="removeToast"
          />
        </TransitionGroup>
      </div>

      <!-- Bottom Right -->
      <div class="absolute bottom-4 right-4 space-y-2">
        <TransitionGroup
          name="toast"
          tag="div"
          class="space-y-2"
        >
          <ToastItem
            v-for="toast in bottomRightToasts"
            :key="toast.id"
            :toast="toast"
            @close="removeToast"
          />
        </TransitionGroup>
      </div>

      <!-- Bottom Left -->
      <div class="absolute bottom-4 left-4 space-y-2">
        <TransitionGroup
          name="toast"
          tag="div"
          class="space-y-2"
        >
          <ToastItem
            v-for="toast in bottomLeftToasts"
            :key="toast.id"
            :toast="toast"
            @close="removeToast"
          />
        </TransitionGroup>
      </div>

      <!-- Top Center -->
      <div class="absolute top-4 left-1/2 transform -translate-x-1/2 space-y-2">
        <TransitionGroup
          name="toast"
          tag="div"
          class="space-y-2"
        >
          <ToastItem
            v-for="toast in topCenterToasts"
            :key="toast.id"
            :toast="toast"
            @close="removeToast"
          />
        </TransitionGroup>
      </div>

      <!-- Bottom Center -->
      <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 space-y-2">
        <TransitionGroup
          name="toast"
          tag="div"
          class="space-y-2"
        >
          <ToastItem
            v-for="toast in bottomCenterToasts"
            :key="toast.id"
            :toast="toast"
            @close="removeToast"
          />
        </TransitionGroup>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { toastStore } from '@/Plugins/toast.js'
import ToastItem from './ToastItem.vue'

// Computed properties for different positions
const topRightToasts = computed(() => 
  toastStore.toasts.filter(toast => toast.position === 'top-right')
)

const topLeftToasts = computed(() => 
  toastStore.toasts.filter(toast => toast.position === 'top-left')
)

const bottomRightToasts = computed(() => 
  toastStore.toasts.filter(toast => toast.position === 'bottom-right')
)

const bottomLeftToasts = computed(() => 
  toastStore.toasts.filter(toast => toast.position === 'bottom-left')
)

const topCenterToasts = computed(() => 
  toastStore.toasts.filter(toast => toast.position === 'top-center')
)

const bottomCenterToasts = computed(() => 
  toastStore.toasts.filter(toast => toast.position === 'bottom-center')
)

// Methods
const removeToast = (id) => {
  const index = toastStore.toasts.findIndex(toast => toast.id === id)
  if (index > -1) {
    toastStore.toasts.splice(index, 1)
  }
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
