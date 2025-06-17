<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Conversations</h1>
        <p class="mt-2 text-gray-600">Manage your AI conversations and chat history</p>
      </div>
      <button
        @click="createConversation"
        class="btn-primary"
      >
        <PlusIcon class="h-5 w-5 mr-2" />
        New Conversation
      </button>
    </div>

    <!-- Filters and Search -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
      <div class="flex-1">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search conversations..."
            class="input pl-10"
            @input="debouncedSearch"
          >
        </div>
      </div>
      <div class="flex gap-2">
        <select v-model="filterStatus" class="input">
          <option value="">All Conversations</option>
          <option value="active">Active</option>
          <option value="archived">Archived</option>
        </select>
        <select v-model="sortBy" class="input">
          <option value="updated_at">Last Updated</option>
          <option value="created_at">Created Date</option>
          <option value="message_count">Message Count</option>
        </select>
      </div>
    </div>

    <!-- Conversations List -->
    <div v-if="loading" class="space-y-4">
      <ConversationSkeleton v-for="i in 5" :key="i" />
    </div>

    <div v-else-if="conversations.length === 0" class="text-center py-12">
      <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No conversations</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating a new conversation.</p>
      <div class="mt-6">
        <button @click="createConversation" class="btn-primary">
          <PlusIcon class="h-5 w-5 mr-2" />
          New Conversation
        </button>
      </div>
    </div>

    <div v-else class="space-y-4">
      <ConversationCard
        v-for="conversation in conversations"
        :key="conversation.id"
        :conversation="conversation"
        @archive="archiveConversation"
        @unarchive="unarchiveConversation"
        @delete="deleteConversation"
        @click="openConversation(conversation.id)"
      />
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-8">
      <Pagination :pagination="pagination" @page-change="loadConversations" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

// Components
import ConversationCard from '@/Components/Conversations/ConversationCard.vue'
import ConversationSkeleton from '@/Components/Conversations/ConversationSkeleton.vue'
import Pagination from '@/Components/Common/Pagination.vue'

// Props
const props = defineProps({
  conversations: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
})

// Reactive state
const loading = ref(false)
const searchQuery = ref('')
const filterStatus = ref('')
const sortBy = ref('updated_at')
const conversations = ref(props.conversations || [])
const pagination = ref(props.pagination)

// Computed
const debouncedSearch = computed(() => {
  return window.debounce(() => {
    loadConversations()
  }, 300)
})

// Methods
const loadConversations = async (page = 1) => {
  loading.value = true
  
  try {
    const params = {
      page,
      search: searchQuery.value,
      status: filterStatus.value,
      sort: sortBy.value,
    }
    
    const response = await axios.get('/conversations', { params })
    conversations.value = response.data.data
    pagination.value = response.data.pagination
  } catch (error) {
    console.error('Failed to load conversations:', error)
    window.$toast.error('Failed to load conversations')
  } finally {
    loading.value = false
  }
}

const createConversation = async () => {
  try {
    const response = await axios.post('/conversations', {
      title: 'New Conversation',
    })
    
    router.visit(`/conversations/${response.data.data.id}`)
  } catch (error) {
    console.error('Failed to create conversation:', error)
    window.$toast.error('Failed to create conversation')
  }
}

const openConversation = (id) => {
  router.visit(`/conversations/${id}`)
}

const archiveConversation = async (conversation) => {
  try {
    await axios.post(`/conversations/${conversation.id}/archive`)
    conversation.is_archived = true
    window.$toast.success('Conversation archived')
  } catch (error) {
    console.error('Failed to archive conversation:', error)
    window.$toast.error('Failed to archive conversation')
  }
}

const unarchiveConversation = async (conversation) => {
  try {
    await axios.post(`/conversations/${conversation.id}/unarchive`)
    conversation.is_archived = false
    window.$toast.success('Conversation unarchived')
  } catch (error) {
    console.error('Failed to unarchive conversation:', error)
    window.$toast.error('Failed to unarchive conversation')
  }
}

const deleteConversation = async (conversation) => {
  if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
    return
  }
  
  try {
    await axios.delete(`/conversations/${conversation.id}`)
    conversations.value = conversations.value.filter(c => c.id !== conversation.id)
    window.$toast.success('Conversation deleted')
  } catch (error) {
    console.error('Failed to delete conversation:', error)
    window.$toast.error('Failed to delete conversation')
  }
}

// Watchers
watch([filterStatus, sortBy], () => {
  loadConversations()
})

// Lifecycle
onMounted(() => {
  if (!props.conversations) {
    loadConversations()
  }
})
</script>
