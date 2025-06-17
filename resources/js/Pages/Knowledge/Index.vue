<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Knowledge Bases</h1>
        <p class="mt-2 text-gray-600">Manage your document collections and vector databases</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="btn-primary"
      >
        <PlusIcon class="h-5 w-5 mr-2" />
        New Knowledge Base
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <BookOpenIcon class="h-8 w-8 text-primary-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Knowledge Bases</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total_knowledge_bases }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentIcon class="h-8 w-8 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Documents</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total_documents }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CubeIcon class="h-8 w-8 text-purple-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Chunks</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_chunks) }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CurrencyDollarIcon class="h-8 w-8 text-orange-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Cost</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.total_cost) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
      <div class="flex-1">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search knowledge bases..."
            class="input pl-10"
            @input="debouncedSearch"
          >
        </div>
      </div>
      <div class="flex gap-2">
        <select v-model="filterType" class="input">
          <option value="">All Types</option>
          <option value="pinecone">Pinecone</option>
          <option value="weaviate">Weaviate</option>
          <option value="qdrant">Qdrant</option>
          <option value="elasticsearch">Elasticsearch</option>
        </select>
        <select v-model="filterVisibility" class="input">
          <option value="">All Visibility</option>
          <option value="private">Private</option>
          <option value="public">Public</option>
        </select>
      </div>
    </div>

    <!-- Knowledge Bases Grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <KnowledgeBaseSkeleton v-for="i in 6" :key="i" />
    </div>

    <div v-else-if="knowledgeBases.length === 0" class="text-center py-12">
      <BookOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No knowledge bases</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first knowledge base.</p>
      <div class="mt-6">
        <button @click="showCreateModal = true" class="btn-primary">
          <PlusIcon class="h-5 w-5 mr-2" />
          Create Knowledge Base
        </button>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <KnowledgeBaseCard
        v-for="kb in knowledgeBases"
        :key="kb.id"
        :knowledge-base="kb"
        @click="openKnowledgeBase(kb.id)"
        @edit="editKnowledgeBase(kb)"
        @delete="deleteKnowledgeBase(kb)"
        @search="searchKnowledgeBase(kb)"
      />
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-8">
      <Pagination :pagination="pagination" @page-change="loadKnowledgeBases" />
    </div>

    <!-- Create Knowledge Base Modal -->
    <CreateKnowledgeBaseModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleKnowledgeBaseCreated"
    />

    <!-- Edit Knowledge Base Modal -->
    <EditKnowledgeBaseModal
      v-if="showEditModal"
      :knowledge-base="selectedKnowledgeBase"
      @close="showEditModal = false"
      @updated="handleKnowledgeBaseUpdated"
    />

    <!-- Search Modal -->
    <KnowledgeSearchModal
      v-if="showSearchModal"
      :knowledge-base="selectedKnowledgeBase"
      @close="showSearchModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  BookOpenIcon,
  DocumentIcon,
  CubeIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline'

// Components
import KnowledgeBaseCard from '@/Components/Knowledge/KnowledgeBaseCard.vue'
import KnowledgeBaseSkeleton from '@/Components/Knowledge/KnowledgeBaseSkeleton.vue'
import CreateKnowledgeBaseModal from '@/Components/Knowledge/CreateKnowledgeBaseModal.vue'
import EditKnowledgeBaseModal from '@/Components/Knowledge/EditKnowledgeBaseModal.vue'
import KnowledgeSearchModal from '@/Components/Knowledge/KnowledgeSearchModal.vue'
import Pagination from '@/Components/Common/Pagination.vue'

// Props
const props = defineProps({
  knowledgeBases: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
  stats: {
    type: Object,
    default: () => ({
      total_knowledge_bases: 0,
      total_documents: 0,
      total_chunks: 0,
      total_cost: 0,
    }),
  },
})

// Reactive state
const loading = ref(false)
const searchQuery = ref('')
const filterType = ref('')
const filterVisibility = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showSearchModal = ref(false)
const selectedKnowledgeBase = ref(null)
const knowledgeBases = ref(props.knowledgeBases || [])
const pagination = ref(props.pagination)
const stats = ref(props.stats)

// Computed
const debouncedSearch = computed(() => {
  return window.debounce(() => {
    loadKnowledgeBases()
  }, 300)
})

// Methods
const loadKnowledgeBases = async (page = 1) => {
  loading.value = true
  
  try {
    const params = {
      page,
      search: searchQuery.value,
      type: filterType.value,
      visibility: filterVisibility.value,
    }
    
    const response = await axios.get('/knowledge-bases', { params })
    knowledgeBases.value = response.data.data
    pagination.value = response.data.pagination
  } catch (error) {
    console.error('Failed to load knowledge bases:', error)
    window.$toast.error('Failed to load knowledge bases')
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await axios.get('/knowledge-bases/stats')
    stats.value = response.data.data
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

const openKnowledgeBase = (id) => {
  router.visit(`/knowledge/${id}`)
}

const editKnowledgeBase = (kb) => {
  selectedKnowledgeBase.value = kb
  showEditModal.value = true
}

const deleteKnowledgeBase = async (kb) => {
  if (!confirm(`Are you sure you want to delete "${kb.name}"? This action cannot be undone.`)) {
    return
  }
  
  try {
    await axios.delete(`/knowledge-bases/${kb.id}`)
    knowledgeBases.value = knowledgeBases.value.filter(k => k.id !== kb.id)
    window.$toast.success('Knowledge base deleted')
    loadStats()
  } catch (error) {
    console.error('Failed to delete knowledge base:', error)
    window.$toast.error('Failed to delete knowledge base')
  }
}

const searchKnowledgeBase = (kb) => {
  selectedKnowledgeBase.value = kb
  showSearchModal.value = true
}

const handleKnowledgeBaseCreated = (newKb) => {
  knowledgeBases.value.unshift(newKb)
  showCreateModal.value = false
  window.$toast.success('Knowledge base created')
  loadStats()
}

const handleKnowledgeBaseUpdated = (updatedKb) => {
  const index = knowledgeBases.value.findIndex(kb => kb.id === updatedKb.id)
  if (index !== -1) {
    knowledgeBases.value[index] = updatedKb
  }
  showEditModal.value = false
  window.$toast.success('Knowledge base updated')
}

// Watchers
watch([filterType, filterVisibility], () => {
  loadKnowledgeBases()
})

// Lifecycle
onMounted(() => {
  if (!props.knowledgeBases) {
    loadKnowledgeBases()
  }
  if (!props.stats) {
    loadStats()
  }
})
</script>
