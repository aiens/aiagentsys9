import { reactive } from 'vue'

// Toast store
const toastStore = reactive({
  toasts: [],
})

// Toast types
const TOAST_TYPES = {
  SUCCESS: 'success',
  ERROR: 'error',
  WARNING: 'warning',
  INFO: 'info',
}

// Default options
const DEFAULT_OPTIONS = {
  duration: 5000,
  position: 'top-right',
  closable: true,
  persistent: false,
}

// Generate unique ID
const generateId = () => {
  return Date.now().toString(36) + Math.random().toString(36).substr(2)
}

// Create toast
const createToast = (type, message, options = {}) => {
  const toast = {
    id: generateId(),
    type,
    message,
    ...DEFAULT_OPTIONS,
    ...options,
    createdAt: Date.now(),
  }

  toastStore.toasts.push(toast)

  // Auto remove toast if not persistent
  if (!toast.persistent && toast.duration > 0) {
    setTimeout(() => {
      removeToast(toast.id)
    }, toast.duration)
  }

  return toast.id
}

// Remove toast
const removeToast = (id) => {
  const index = toastStore.toasts.findIndex(toast => toast.id === id)
  if (index > -1) {
    toastStore.toasts.splice(index, 1)
  }
}

// Clear all toasts
const clearToasts = () => {
  toastStore.toasts.splice(0)
}

// Toast methods
const toast = {
  success: (message, options) => createToast(TOAST_TYPES.SUCCESS, message, options),
  error: (message, options) => createToast(TOAST_TYPES.ERROR, message, options),
  warning: (message, options) => createToast(TOAST_TYPES.WARNING, message, options),
  info: (message, options) => createToast(TOAST_TYPES.INFO, message, options),
  remove: removeToast,
  clear: clearToasts,
  store: toastStore,
}

// Vue plugin
export default {
  install(app) {
    // Add to global properties
    app.config.globalProperties.$toast = toast
    
    // Add to window for global access
    window.$toast = toast
    
    // Provide for composition API
    app.provide('toast', toast)
  }
}

export { toast, toastStore, TOAST_TYPES }
