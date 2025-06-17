<template>
  <component
    :is="tag"
    :type="tag === 'button' ? type : undefined"
    :href="tag === 'a' ? href : undefined"
    :disabled="disabled"
    :class="buttonClasses"
    @click="handleClick"
  >
    <div v-if="loading" class="spinner w-4 h-4 mr-2"></div>
    <component v-else-if="icon" :is="icon" :class="iconClasses" />
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'warning', 'danger', 'outline', 'ghost'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
  },
  tag: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'a', 'router-link'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  href: {
    type: String,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  icon: {
    type: [String, Object],
    default: null,
  },
  iconPosition: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'right'].includes(value),
  },
  block: {
    type: Boolean,
    default: false,
  },
  rounded: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
  const baseClasses = [
    'inline-flex',
    'items-center',
    'justify-center',
    'font-medium',
    'transition-all',
    'duration-200',
    'focus:outline-none',
    'focus:ring-2',
    'focus:ring-offset-2',
    'disabled:opacity-50',
    'disabled:cursor-not-allowed',
  ]

  // Size classes
  const sizeClasses = {
    xs: ['px-2', 'py-1', 'text-xs'],
    sm: ['px-3', 'py-1.5', 'text-sm'],
    md: ['px-4', 'py-2', 'text-sm'],
    lg: ['px-6', 'py-3', 'text-base'],
    xl: ['px-8', 'py-4', 'text-lg'],
  }

  // Variant classes
  const variantClasses = {
    primary: [
      'bg-primary-600',
      'text-white',
      'border',
      'border-transparent',
      'hover:bg-primary-700',
      'focus:ring-primary-500',
      'shadow-sm',
    ],
    secondary: [
      'bg-secondary-600',
      'text-white',
      'border',
      'border-transparent',
      'hover:bg-secondary-700',
      'focus:ring-secondary-500',
      'shadow-sm',
    ],
    success: [
      'bg-success-600',
      'text-white',
      'border',
      'border-transparent',
      'hover:bg-success-700',
      'focus:ring-success-500',
      'shadow-sm',
    ],
    warning: [
      'bg-warning-600',
      'text-white',
      'border',
      'border-transparent',
      'hover:bg-warning-700',
      'focus:ring-warning-500',
      'shadow-sm',
    ],
    danger: [
      'bg-danger-600',
      'text-white',
      'border',
      'border-transparent',
      'hover:bg-danger-700',
      'focus:ring-danger-500',
      'shadow-sm',
    ],
    outline: [
      'bg-transparent',
      'text-gray-700',
      'border',
      'border-gray-300',
      'hover:bg-gray-50',
      'focus:ring-gray-500',
    ],
    ghost: [
      'bg-transparent',
      'text-gray-700',
      'border',
      'border-transparent',
      'hover:bg-gray-100',
      'focus:ring-gray-500',
    ],
  }

  // Border radius
  const radiusClasses = props.rounded ? ['rounded-full'] : ['rounded-md']

  // Block width
  const widthClasses = props.block ? ['w-full'] : []

  return [
    ...baseClasses,
    ...sizeClasses[props.size],
    ...variantClasses[props.variant],
    ...radiusClasses,
    ...widthClasses,
  ]
})

const iconClasses = computed(() => {
  const baseClasses = ['flex-shrink-0']
  
  const sizeClasses = {
    xs: ['h-3', 'w-3'],
    sm: ['h-4', 'w-4'],
    md: ['h-4', 'w-4'],
    lg: ['h-5', 'w-5'],
    xl: ['h-6', 'w-6'],
  }

  const spacingClasses = props.iconPosition === 'left' ? ['mr-2'] : ['ml-2']

  return [
    ...baseClasses,
    ...sizeClasses[props.size],
    ...spacingClasses,
  ]
})

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>
