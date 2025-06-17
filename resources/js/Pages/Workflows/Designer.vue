<template>
  <div class="h-screen flex flex-col bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button
            @click="$router.back()"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <ArrowLeftIcon class="h-5 w-5" />
          </button>
          <div>
            <h1 class="text-lg font-semibold text-gray-900">
              {{ workflow.name || 'Untitled Workflow' }}
            </h1>
            <p class="text-sm text-gray-500">
              {{ workflow.status }} • {{ workflow.execution_count }} executions
            </p>
          </div>
        </div>
        
        <div class="flex items-center space-x-2">
          <!-- Save Status -->
          <div class="flex items-center space-x-2 text-sm text-gray-500">
            <div v-if="saving" class="flex items-center">
              <div class="spinner w-4 h-4 mr-2"></div>
              Saving...
            </div>
            <div v-else-if="lastSaved" class="flex items-center">
              <CheckIcon class="h-4 w-4 mr-1 text-green-500" />
              Saved {{ formatRelativeTime(lastSaved) }}
            </div>
          </div>
          
          <!-- Actions -->
          <button
            @click="validateWorkflow"
            class="btn-outline"
          >
            <CheckCircleIcon class="h-4 w-4 mr-2" />
            Validate
          </button>
          
          <button
            @click="testWorkflow"
            class="btn-secondary"
            :disabled="!isValid"
          >
            <PlayIcon class="h-4 w-4 mr-2" />
            Test
          </button>
          
          <button
            @click="saveWorkflow"
            class="btn-primary"
            :disabled="saving"
          >
            <CloudArrowUpIcon class="h-4 w-4 mr-2" />
            Save
          </button>
          
          <div class="relative">
            <button
              @click="showOptions = !showOptions"
              class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
            >
              <EllipsisVerticalIcon class="h-5 w-5" />
            </button>
            
            <div v-if="showOptions" class="dropdown-menu">
              <button @click="exportWorkflow" class="dropdown-item">
                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                Export
              </button>
              <button @click="duplicateWorkflow" class="dropdown-item">
                <DocumentDuplicateIcon class="h-4 w-4 mr-2" />
                Duplicate
              </button>
              <button @click="publishWorkflow" class="dropdown-item">
                <ShareIcon class="h-4 w-4 mr-2" />
                Publish
              </button>
              <hr class="my-1">
              <button @click="showSettings = true" class="dropdown-item">
                <CogIcon class="h-4 w-4 mr-2" />
                Settings
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
      <!-- Node Palette -->
      <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
        <div class="p-4">
          <h3 class="text-sm font-medium text-gray-900 mb-4">Node Types</h3>
          
          <div class="space-y-4">
            <div v-for="category in nodeCategories" :key="category.name">
              <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                {{ category.name }}
              </h4>
              <div class="space-y-2">
                <div
                  v-for="nodeType in category.nodes"
                  :key="nodeType.type"
                  :class="[
                    'p-3 rounded-lg border-2 border-dashed cursor-pointer transition-colors duration-200',
                    nodeType.color,
                    'hover:border-solid hover:shadow-sm'
                  ]"
                  draggable="true"
                  @dragstart="handleNodeDragStart(nodeType, $event)"
                >
                  <div class="flex items-center space-x-2">
                    <component :is="nodeType.icon" class="h-5 w-5" />
                    <div>
                      <p class="text-sm font-medium">{{ nodeType.name }}</p>
                      <p class="text-xs text-gray-500">{{ nodeType.description }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Canvas -->
      <div class="flex-1 relative overflow-hidden">
        <div
          ref="canvas"
          class="w-full h-full bg-gray-50"
          @drop="handleCanvasDrop"
          @dragover="handleCanvasDragOver"
          @click="handleCanvasClick"
        >
          <!-- Grid Background -->
          <svg class="absolute inset-0 w-full h-full pointer-events-none">
            <defs>
              <pattern
                id="grid"
                width="20"
                height="20"
                patternUnits="userSpaceOnUse"
              >
                <path
                  d="M 20 0 L 0 0 0 20"
                  fill="none"
                  stroke="#e5e7eb"
                  stroke-width="1"
                />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
          </svg>

          <!-- Workflow Nodes -->
          <WorkflowNode
            v-for="node in workflowDefinition.nodes"
            :key="node.id"
            :node="node"
            :selected="selectedNode?.id === node.id"
            @select="selectNode"
            @move="moveNode"
            @delete="deleteNode"
            @connect="startConnection"
          />

          <!-- Connections -->
          <svg class="absolute inset-0 w-full h-full pointer-events-none">
            <WorkflowConnection
              v-for="edge in workflowDefinition.edges"
              :key="`${edge.source}-${edge.target}`"
              :edge="edge"
              :nodes="workflowDefinition.nodes"
              @delete="deleteConnection"
            />
            
            <!-- Temporary connection while dragging -->
            <WorkflowConnection
              v-if="tempConnection"
              :edge="tempConnection"
              :nodes="workflowDefinition.nodes"
              :temporary="true"
            />
          </svg>
        </div>
      </div>

      <!-- Properties Panel -->
      <div v-if="selectedNode" class="w-80 bg-white border-l border-gray-200 overflow-y-auto">
        <NodePropertiesPanel
          :node="selectedNode"
          @update="updateNodeProperties"
          @close="selectedNode = null"
        />
      </div>
    </div>

    <!-- Validation Results -->
    <div v-if="validationResults.length > 0" class="bg-white border-t border-gray-200 p-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-medium text-gray-900">Validation Results</h3>
        <button
          @click="validationResults = []"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-4 w-4" />
        </button>
      </div>
      <div class="space-y-2">
        <div
          v-for="(result, index) in validationResults"
          :key="index"
          :class="[
            'p-2 rounded text-sm',
            result.type === 'error' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700'
          ]"
        >
          {{ result.message }}
        </div>
      </div>
    </div>

    <!-- Test Execution Modal -->
    <WorkflowTestModal
      v-if="showTestModal"
      :workflow="workflow"
      @close="showTestModal = false"
    />

    <!-- Settings Modal -->
    <WorkflowSettingsModal
      v-if="showSettings"
      :workflow="workflow"
      @close="showSettings = false"
      @update="updateWorkflow"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  CheckIcon,
  CheckCircleIcon,
  PlayIcon,
  CloudArrowUpIcon,
  EllipsisVerticalIcon,
  ArrowDownTrayIcon,
  DocumentDuplicateIcon,
  ShareIcon,
  CogIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

// Components
import WorkflowNode from '@/Components/Workflow/WorkflowNode.vue'
import WorkflowConnection from '@/Components/Workflow/WorkflowConnection.vue'
import NodePropertiesPanel from '@/Components/Workflow/NodePropertiesPanel.vue'
import WorkflowTestModal from '@/Components/Workflow/WorkflowTestModal.vue'
import WorkflowSettingsModal from '@/Components/Workflow/WorkflowSettingsModal.vue'

// Props
const props = defineProps({
  workflow: {
    type: Object,
    required: true,
  },
})

// Reactive state
const canvas = ref(null)
const saving = ref(false)
const lastSaved = ref(null)
const showOptions = ref(false)
const showTestModal = ref(false)
const showSettings = ref(false)
const selectedNode = ref(null)
const tempConnection = ref(null)
const validationResults = ref([])
const isValid = ref(true)

const workflowDefinition = ref({
  nodes: props.workflow.definition?.nodes || [],
  edges: props.workflow.definition?.edges || [],
})

// Node categories and types
const nodeCategories = ref([
  {
    name: 'AI & Models',
    nodes: [
      {
        type: 'ai_call',
        name: 'AI Call',
        description: 'Call an AI model',
        icon: 'CpuChipIcon',
        color: 'border-blue-200 bg-blue-50 text-blue-700',
      },
      {
        type: 'prompt_template',
        name: 'Prompt Template',
        description: 'Dynamic prompt generation',
        icon: 'DocumentTextIcon',
        color: 'border-blue-200 bg-blue-50 text-blue-700',
      },
    ],
  },
  {
    name: 'Data & Logic',
    nodes: [
      {
        type: 'condition',
        name: 'Condition',
        description: 'Conditional branching',
        icon: 'ArrowPathIcon',
        color: 'border-purple-200 bg-purple-50 text-purple-700',
      },
      {
        type: 'data_transform',
        name: 'Transform',
        description: 'Data transformation',
        icon: 'ArrowsRightLeftIcon',
        color: 'border-purple-200 bg-purple-50 text-purple-700',
      },
      {
        type: 'loop',
        name: 'Loop',
        description: 'Iterate over data',
        icon: 'ArrowPathRoundedSquareIcon',
        color: 'border-purple-200 bg-purple-50 text-purple-700',
      },
    ],
  },
  {
    name: 'Knowledge & Memory',
    nodes: [
      {
        type: 'knowledge_search',
        name: 'Knowledge Search',
        description: 'Search knowledge base',
        icon: 'MagnifyingGlassIcon',
        color: 'border-green-200 bg-green-50 text-green-700',
      },
      {
        type: 'memory_store',
        name: 'Store Memory',
        description: 'Store in memory',
        icon: 'ArchiveBoxIcon',
        color: 'border-green-200 bg-green-50 text-green-700',
      },
      {
        type: 'memory_retrieve',
        name: 'Retrieve Memory',
        description: 'Retrieve from memory',
        icon: 'FolderOpenIcon',
        color: 'border-green-200 bg-green-50 text-green-700',
      },
    ],
  },
  {
    name: 'Tools & External',
    nodes: [
      {
        type: 'api_call',
        name: 'API Call',
        description: 'External API request',
        icon: 'GlobeAltIcon',
        color: 'border-orange-200 bg-orange-50 text-orange-700',
      },
      {
        type: 'mcp_tool',
        name: 'MCP Tool',
        description: 'Execute MCP tool',
        icon: 'WrenchScrewdriverIcon',
        color: 'border-orange-200 bg-orange-50 text-orange-700',
      },
      {
        type: 'webhook',
        name: 'Webhook',
        description: 'Send webhook',
        icon: 'BoltIcon',
        color: 'border-orange-200 bg-orange-50 text-orange-700',
      },
    ],
  },
])

// Methods
const handleNodeDragStart = (nodeType, event) => {
  event.dataTransfer.setData('application/json', JSON.stringify(nodeType))
}

const handleCanvasDragOver = (event) => {
  event.preventDefault()
}

const handleCanvasDrop = (event) => {
  event.preventDefault()
  
  try {
    const nodeType = JSON.parse(event.dataTransfer.getData('application/json'))
    const rect = canvas.value.getBoundingClientRect()
    const x = event.clientX - rect.left
    const y = event.clientY - rect.top
    
    addNode(nodeType, x, y)
  } catch (error) {
    console.error('Failed to parse dropped node:', error)
  }
}

const handleCanvasClick = (event) => {
  if (event.target === canvas.value) {
    selectedNode.value = null
  }
}

const addNode = (nodeType, x, y) => {
  const newNode = {
    id: generateNodeId(),
    type: nodeType.type,
    name: nodeType.name,
    position: { x, y },
    config: getDefaultNodeConfig(nodeType.type),
  }
  
  workflowDefinition.value.nodes.push(newNode)
  selectedNode.value = newNode
}

const selectNode = (node) => {
  selectedNode.value = node
}

const moveNode = (nodeId, position) => {
  const node = workflowDefinition.value.nodes.find(n => n.id === nodeId)
  if (node) {
    node.position = position
  }
}

const deleteNode = (nodeId) => {
  workflowDefinition.value.nodes = workflowDefinition.value.nodes.filter(n => n.id !== nodeId)
  workflowDefinition.value.edges = workflowDefinition.value.edges.filter(
    e => e.source !== nodeId && e.target !== nodeId
  )
  if (selectedNode.value?.id === nodeId) {
    selectedNode.value = null
  }
}

const startConnection = (sourceNodeId) => {
  // TODO: Implement connection creation
  console.log('Start connection from:', sourceNodeId)
}

const deleteConnection = (edge) => {
  workflowDefinition.value.edges = workflowDefinition.value.edges.filter(
    e => !(e.source === edge.source && e.target === edge.target)
  )
}

const updateNodeProperties = (nodeId, properties) => {
  const node = workflowDefinition.value.nodes.find(n => n.id === nodeId)
  if (node) {
    Object.assign(node, properties)
  }
}

const validateWorkflow = () => {
  const errors = []
  
  // Check for nodes without connections
  const connectedNodes = new Set()
  workflowDefinition.value.edges.forEach(edge => {
    connectedNodes.add(edge.source)
    connectedNodes.add(edge.target)
  })
  
  workflowDefinition.value.nodes.forEach(node => {
    if (!connectedNodes.has(node.id) && workflowDefinition.value.nodes.length > 1) {
      errors.push({
        type: 'warning',
        message: `Node "${node.name}" is not connected to any other nodes`,
      })
    }
  })
  
  // Check for cycles
  if (hasCycles()) {
    errors.push({
      type: 'error',
      message: 'Workflow contains cycles which may cause infinite loops',
    })
  }
  
  validationResults.value = errors
  isValid.value = errors.filter(e => e.type === 'error').length === 0
  
  if (errors.length === 0) {
    window.$toast.success('Workflow validation passed')
  }
}

const hasCycles = () => {
  // TODO: Implement cycle detection algorithm
  return false
}

const saveWorkflow = async () => {
  saving.value = true
  
  try {
    await axios.put(`/workflows/${props.workflow.id}`, {
      definition: workflowDefinition.value,
    })
    
    lastSaved.value = new Date()
    window.$toast.success('Workflow saved')
  } catch (error) {
    console.error('Failed to save workflow:', error)
    window.$toast.error('Failed to save workflow')
  } finally {
    saving.value = false
  }
}

const testWorkflow = () => {
  showTestModal.value = true
}

const exportWorkflow = () => {
  const dataStr = JSON.stringify(workflowDefinition.value, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  
  const link = document.createElement('a')
  link.href = url
  link.download = `${props.workflow.name || 'workflow'}.json`
  link.click()
  
  URL.revokeObjectURL(url)
}

const duplicateWorkflow = async () => {
  try {
    const response = await axios.post(`/workflows/${props.workflow.id}/duplicate`)
    router.visit(`/workflows/${response.data.data.id}/design`)
  } catch (error) {
    console.error('Failed to duplicate workflow:', error)
    window.$toast.error('Failed to duplicate workflow')
  }
}

const publishWorkflow = async () => {
  try {
    await axios.post(`/workflows/${props.workflow.id}/publish`)
    window.$toast.success('Workflow published')
  } catch (error) {
    console.error('Failed to publish workflow:', error)
    window.$toast.error('Failed to publish workflow')
  }
}

const updateWorkflow = (updates) => {
  Object.assign(props.workflow, updates)
}

const generateNodeId = () => {
  return 'node_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
}

const getDefaultNodeConfig = (nodeType) => {
  const defaults = {
    ai_call: {
      model: 'gpt-3.5-turbo',
      temperature: 0.7,
      max_tokens: 2048,
    },
    condition: {
      condition: 'input.value > 0',
    },
    data_transform: {
      transform_type: 'json_parse',
    },
    knowledge_search: {
      knowledge_base_id: null,
      top_k: 5,
    },
    memory_store: {
      memory_type: 'working',
      key: 'result',
    },
    memory_retrieve: {
      memory_type: 'working',
      key: 'result',
    },
    api_call: {
      method: 'GET',
      url: '',
      headers: {},
    },
    mcp_tool: {
      tool_id: null,
      parameters: {},
    },
  }
  
  return defaults[nodeType] || {}
}

// Auto-save
watch(workflowDefinition, () => {
  // Debounced auto-save
  window.debounce(() => {
    if (!saving.value) {
      saveWorkflow()
    }
  }, 2000)()
}, { deep: true })

// Lifecycle
onMounted(() => {
  // Set initial last saved time
  lastSaved.value = new Date(props.workflow.updated_at)
})
</script>
