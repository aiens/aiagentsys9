<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Left side -->
          <div class="flex items-center">
            <!-- Logo -->
            <Link href="/" class="flex items-center space-x-2">
              <div class="w-8 h-8 bg-gradient-to-r from-primary-600 to-purple-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">AI</span>
              </div>
              <span class="text-xl font-bold text-gray-900">Agent System</span>
            </Link>

            <!-- Main Navigation -->
            <div class="hidden md:ml-10 md:flex md:space-x-8">
              <NavLink href="/dashboard" :active="$page.url.startsWith('/dashboard')">
                Dashboard
              </NavLink>
              <NavLink href="/conversations" :active="$page.url.startsWith('/conversations')">
                Conversations
              </NavLink>
              <NavLink href="/knowledge" :active="$page.url.startsWith('/knowledge')">
                Knowledge
              </NavLink>
              <NavLink href="/workflows" :active="$page.url.startsWith('/workflows')">
                Workflows
              </NavLink>
              <NavLink href="/tools" :active="$page.url.startsWith('/tools')">
                Tools
              </NavLink>
            </div>
          </div>

          <!-- Right side -->
          <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button
              type="button"
              class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-full transition-colors duration-200"
              @click="showNotifications = !showNotifications"
            >
              <BellIcon class="h-6 w-6" />
              <span v-if="unreadNotifications > 0" class="absolute -mt-1 -mr-1 px-2 py-1 text-xs bg-red-500 text-white rounded-full">
                {{ unreadNotifications }}
              </span>
            </button>

            <!-- User menu -->
            <div class="relative">
              <button
                type="button"
                class="flex items-center space-x-2 p-2 text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                @click="showUserMenu = !showUserMenu"
              >
                <img
                  :src="$page.props.auth.user.avatar || '/images/default-avatar.png'"
                  :alt="$page.props.auth.user.name"
                  class="w-8 h-8 rounded-full"
                >
                <span class="hidden md:block text-sm font-medium">
                  {{ $page.props.auth.user.name }}
                </span>
                <ChevronDownIcon class="h-4 w-4" />
              </button>

              <!-- User dropdown -->
              <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <div
                  v-show="showUserMenu"
                  class="dropdown-menu"
                  @click="showUserMenu = false"
                >
                  <Link href="/profile" class="dropdown-item">
                    <UserIcon class="h-4 w-4 mr-2" />
                    Profile
                  </Link>
                  <Link href="/settings" class="dropdown-item">
                    <CogIcon class="h-4 w-4 mr-2" />
                    Settings
                  </Link>
                  <Link href="/usage" class="dropdown-item">
                    <ChartBarIcon class="h-4 w-4 mr-2" />
                    Usage & Billing
                  </Link>
                  <hr class="my-1">
                  <Link href="/logout" method="post" class="dropdown-item text-red-600">
                    <ArrowRightOnRectangleIcon class="h-4 w-4 mr-2" />
                    Sign out
                  </Link>
                </div>
              </Transition>
            </div>

            <!-- Mobile menu button -->
            <button
              type="button"
              class="md:hidden p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg"
              @click="showMobileMenu = !showMobileMenu"
            >
              <Bars3Icon v-if="!showMobileMenu" class="h-6 w-6" />
              <XMarkIcon v-else class="h-6 w-6" />
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div v-show="showMobileMenu" class="md:hidden border-t border-gray-200 bg-white">
          <div class="px-2 pt-2 pb-3 space-y-1">
            <MobileNavLink href="/dashboard" :active="$page.url.startsWith('/dashboard')">
              Dashboard
            </MobileNavLink>
            <MobileNavLink href="/conversations" :active="$page.url.startsWith('/conversations')">
              Conversations
            </MobileNavLink>
            <MobileNavLink href="/knowledge" :active="$page.url.startsWith('/knowledge')">
              Knowledge
            </MobileNavLink>
            <MobileNavLink href="/workflows" :active="$page.url.startsWith('/workflows')">
              Workflows
            </MobileNavLink>
            <MobileNavLink href="/tools" :active="$page.url.startsWith('/tools')">
              Tools
            </MobileNavLink>
          </div>
        </div>
      </Transition>
    </nav>

    <!-- Page content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Toast notifications -->
    <ToastContainer />

    <!-- Global modals -->
    <ModalContainer />

    <!-- Loading overlay -->
    <LoadingOverlay />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  BellIcon,
  ChevronDownIcon,
  UserIcon,
  CogIcon,
  ChartBarIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

// Components
import NavLink from '@/Components/Navigation/NavLink.vue'
import MobileNavLink from '@/Components/Navigation/MobileNavLink.vue'
import ToastContainer from '@/Components/Toast/ToastContainer.vue'
import ModalContainer from '@/Components/Modal/ModalContainer.vue'
import LoadingOverlay from '@/Components/Loading/LoadingOverlay.vue'

// Reactive state
const showUserMenu = ref(false)
const showMobileMenu = ref(false)
const showNotifications = ref(false)
const unreadNotifications = ref(0)

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showUserMenu.value = false
    showNotifications.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Load unread notifications count
  loadNotificationsCount()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const loadNotificationsCount = async () => {
  try {
    const response = await axios.get('/notifications/unread-count')
    unreadNotifications.value = response.data.count
  } catch (error) {
    console.error('Failed to load notifications count:', error)
  }
}
</script>
