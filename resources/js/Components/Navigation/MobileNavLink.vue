<template>
  <Link
    :href="href"
    :class="[
      'block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200',
      active
        ? 'bg-primary-50 border-primary-500 text-primary-700'
        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
    ]"
  >
    <slot />
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  href: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
})
</script>
