<template>
  <div class="h-screen flex flex-col bg-gray-50">
    <!-- Chat Header -->
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
              {{ conversation.title || 'New Conversation' }}
            </h1>
            <p class="text-sm text-gray-500">
              {{ conversation.message_count }} messages • {{ formatCurrency(conversation.total_cost) }}
            </p>
          </div>
        </div>
        
        <div class="flex items-center space-x-2">
          <!-- Model Selector -->
          <select
            v-model="selectedModel"
            class="input text-sm"
            @change="updateConversationSettings"
          >
            <option v-for="model in availableModels" :key="model.id" :value="model.model_id">
              {{ model.display_name }}
            </option>
          </select>
          
          <!-- Settings Button -->
          <button
            @click="showSettings = true"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <CogIcon class="h-5 w-5" />
          </button>
          
          <!-- More Options -->
          <div class="relative">
            <button
              @click="showOptions = !showOptions"
              class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
            >
              <EllipsisVerticalIcon class="h-5 w-5" />
            </button>
            
            <div v-if="showOptions" class="dropdown-menu">
              <button @click="exportConversation" class="dropdown-item">
                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                Export
              </button>
              <button @click="shareConversation" class="dropdown-item">
                <ShareIcon class="h-4 w-4 mr-2" />
                Share
              </button>
              <hr class="my-1">
              <button @click="archiveConversation" class="dropdown-item">
                <ArchiveBoxIcon class="h-4 w-4 mr-2" />
                Archive
              </button>
              <button @click="deleteConversation" class="dropdown-item text-red-600">
                <TrashIcon class="h-4 w-4 mr-2" />
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages Container -->
    <div
      ref="messagesContainer"
      class="flex-1 overflow-y-auto px-6 py-4 space-y-4"
      @scroll="handleScroll"
    >
      <!-- Loading older messages -->
      <div v-if="loadingOlder" class="text-center py-4">
        <div class="spinner w-6 h-6 mx-auto"></div>
      </div>
      
      <!-- Messages -->
      <div v-for="message in messages" :key="message.id" class="flex">
        <div
          :class="[
            'max-w-xs md:max-w-md lg:max-w-lg xl:max-w-xl',
            message.role === 'user' ? 'ml-auto' : 'mr-auto'
          ]"
        >
          <!-- Message bubble -->
          <div
            :class="[
              'px-4 py-2 rounded-lg',
              message.role === 'user'
                ? 'bg-primary-600 text-white rounded-br-sm'
                : message.role === 'assistant'
                ? 'bg-white border border-gray-200 rounded-bl-sm shadow-sm'
                : 'bg-gray-100 text-gray-600 rounded-lg mx-auto text-center text-sm'
            ]"
          >
            <!-- Message content -->
            <div v-if="message.role !== 'system'" class="prose prose-sm max-w-none">
              <MessageContent :content="message.content" :role="message.role" />
            </div>
            <div v-else class="text-sm">
              {{ message.content }}
            </div>
          </div>
          
          <!-- Message metadata -->
          <div
            :class="[
              'mt-1 text-xs text-gray-500 flex items-center space-x-2',
              message.role === 'user' ? 'justify-end' : 'justify-start'
            ]"
          >
            <span>{{ formatRelativeTime(message.created_at) }}</span>
            <span v-if="message.cost > 0">• {{ formatCurrency(message.cost) }}</span>
            <span v-if="message.input_tokens || message.output_tokens">
              • {{ formatNumber(message.input_tokens + message.output_tokens) }} tokens
            </span>
          </div>
        </div>
      </div>
      
      <!-- Streaming message -->
      <div v-if="streamingMessage" class="flex mr-auto">
        <div class="max-w-xs md:max-w-md lg:max-w-lg xl:max-xl">
          <div class="bg-white border border-gray-200 rounded-lg rounded-bl-sm shadow-sm px-4 py-2">
            <div class="prose prose-sm max-w-none">
              <MessageContent :content="streamingMessage" role="assistant" />
            </div>
            <div class="flex items-center mt-2 text-xs text-gray-500">
              <div class="spinner w-3 h-3 mr-2"></div>
              Thinking...
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty state -->
      <div v-if="messages.length === 0 && !streamingMessage" class="text-center py-12">
        <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">Start a conversation</h3>
        <p class="mt-1 text-sm text-gray-500">Send a message to begin chatting with AI.</p>
      </div>
    </div>

    <!-- Message Input -->
    <div class="bg-white border-t border-gray-200 px-6 py-4">
      <div class="flex items-end space-x-4">
        <!-- Attachment button -->
        <button
          type="button"
          class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          @click="$refs.fileInput.click()"
        >
          <PaperClipIcon class="h-5 w-5" />
        </button>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          multiple
          @change="handleFileUpload"
        >
        
        <!-- Message input -->
        <div class="flex-1">
          <textarea
            ref="messageInput"
            v-model="newMessage"
            placeholder="Type your message..."
            class="input resize-none"
            rows="1"
            @keydown="handleKeydown"
            @input="adjustTextareaHeight"
            :disabled="sending"
          ></textarea>
        </div>
        
        <!-- Send button -->
        <button
          type="button"
          :disabled="!newMessage.trim() || sending"
          class="btn-primary"
          @click="sendMessage"
        >
          <PaperAirplaneIcon v-if="!sending" class="h-5 w-5" />
          <div v-else class="spinner w-5 h-5"></div>
        </button>
      </div>
      
      <!-- Quick actions -->
      <div v-if="quickActions.length > 0" class="mt-3 flex flex-wrap gap-2">
        <button
          v-for="action in quickActions"
          :key="action.id"
          @click="useQuickAction(action)"
          class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-colors duration-200"
        >
          {{ action.text }}
        </button>
      </div>
    </div>

    <!-- Settings Modal -->
    <ConversationSettings
      v-if="showSettings"
      :conversation="conversation"
      :available-models="availableModels"
      @close="showSettings = false"
      @update="updateConversation"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  CogIcon,
  EllipsisVerticalIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  ArchiveBoxIcon,
  TrashIcon,
  ChatBubbleLeftRightIcon,
  PaperClipIcon,
  PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'

// Components
import MessageContent from '@/Components/Chat/MessageContent.vue'
import ConversationSettings from '@/Components/Chat/ConversationSettings.vue'

// Props
const props = defineProps({
  conversation: {
    type: Object,
    required: true,
  },
  messages: {
    type: Array,
    default: () => [],
  },
  availableModels: {
    type: Array,
    default: () => [],
  },
})

// Reactive state
const messagesContainer = ref(null)
const messageInput = ref(null)
const fileInput = ref(null)
const newMessage = ref('')
const sending = ref(false)
const streamingMessage = ref('')
const loadingOlder = ref(false)
const showSettings = ref(false)
const showOptions = ref(false)
const selectedModel = ref(props.conversation.settings?.model || 'gpt-3.5-turbo')
const messages = ref(props.messages || [])

const quickActions = ref([
  { id: 1, text: 'Explain this concept' },
  { id: 2, text: 'Write a summary' },
  { id: 3, text: 'Generate code' },
  { id: 4, text: 'Translate to English' },
])

// Methods
const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return
  
  const message = newMessage.value.trim()
  newMessage.value = ''
  sending.value = true
  streamingMessage.value = ''
  
  // Add user message to UI immediately
  const userMessage = {
    id: Date.now(),
    role: 'user',
    content: message,
    created_at: new Date().toISOString(),
  }
  messages.value.push(userMessage)
  
  await nextTick()
  scrollToBottom()
  
  try {
    // Check if streaming is enabled
    const streamingEnabled = props.conversation.settings?.streaming !== false
    
    if (streamingEnabled) {
      await sendStreamingMessage(message)
    } else {
      await sendRegularMessage(message)
    }
  } catch (error) {
    console.error('Failed to send message:', error)
    window.$toast.error('Failed to send message')
    // Remove the user message if sending failed
    messages.value.pop()
  } finally {
    sending.value = false
    streamingMessage.value = ''
  }
}

const sendStreamingMessage = async (message) => {
  const response = await fetch(`/api/conversations/${props.conversation.id}/messages`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'text/event-stream',
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify({
      content: message,
      stream: true,
      model: selectedModel.value,
    }),
  })
  
  if (!response.ok) {
    throw new Error('Failed to send message')
  }
  
  const reader = response.body.getReader()
  const decoder = new TextDecoder()
  
  while (true) {
    const { done, value } = await reader.read()
    if (done) break
    
    const chunk = decoder.decode(value)
    const lines = chunk.split('\n')
    
    for (const line of lines) {
      if (line.startsWith('data: ')) {
        try {
          const data = JSON.parse(line.slice(6))
          if (data.content) {
            streamingMessage.value += data.content
            await nextTick()
            scrollToBottom()
          }
          if (data.finished) {
            // Add the complete message to the messages array
            const assistantMessage = {
              id: data.message_id || Date.now() + 1,
              role: 'assistant',
              content: streamingMessage.value,
              created_at: new Date().toISOString(),
              cost: data.cost || 0,
              input_tokens: data.usage?.input_tokens || 0,
              output_tokens: data.usage?.output_tokens || 0,
            }
            messages.value.push(assistantMessage)
            streamingMessage.value = ''
          }
        } catch (error) {
          console.error('Error parsing streaming data:', error)
        }
      }
    }
  }
}

const sendRegularMessage = async (message) => {
  const response = await axios.post(`/conversations/${props.conversation.id}/messages`, {
    content: message,
    model: selectedModel.value,
  })
  
  messages.value.push(response.data.data)
  await nextTick()
  scrollToBottom()
}

const handleKeydown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

const adjustTextareaHeight = () => {
  const textarea = messageInput.value
  if (textarea) {
    textarea.style.height = 'auto'
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px'
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const handleScroll = () => {
  const container = messagesContainer.value
  if (container && container.scrollTop === 0 && !loadingOlder.value) {
    loadOlderMessages()
  }
}

const loadOlderMessages = async () => {
  if (messages.value.length === 0) return
  
  loadingOlder.value = true
  
  try {
    const response = await axios.get(`/conversations/${props.conversation.id}/messages`, {
      params: {
        before: messages.value[0].id,
        limit: 20,
      },
    })
    
    const olderMessages = response.data.data
    if (olderMessages.length > 0) {
      messages.value.unshift(...olderMessages.reverse())
    }
  } catch (error) {
    console.error('Failed to load older messages:', error)
  } finally {
    loadingOlder.value = false
  }
}

const updateConversationSettings = async () => {
  try {
    await axios.put(`/conversations/${props.conversation.id}`, {
      settings: {
        ...props.conversation.settings,
        model: selectedModel.value,
      },
    })
  } catch (error) {
    console.error('Failed to update conversation settings:', error)
  }
}

const useQuickAction = (action) => {
  newMessage.value = action.text
  messageInput.value.focus()
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  // TODO: Implement file upload functionality
  console.log('Files selected:', files)
}

const exportConversation = () => {
  // TODO: Implement export functionality
  console.log('Export conversation')
}

const shareConversation = () => {
  // TODO: Implement share functionality
  console.log('Share conversation')
}

const archiveConversation = async () => {
  try {
    await axios.post(`/conversations/${props.conversation.id}/archive`)
    router.visit('/conversations')
  } catch (error) {
    console.error('Failed to archive conversation:', error)
    window.$toast.error('Failed to archive conversation')
  }
}

const deleteConversation = async () => {
  if (!confirm('Are you sure you want to delete this conversation?')) return
  
  try {
    await axios.delete(`/conversations/${props.conversation.id}`)
    router.visit('/conversations')
  } catch (error) {
    console.error('Failed to delete conversation:', error)
    window.$toast.error('Failed to delete conversation')
  }
}

// Lifecycle
onMounted(() => {
  scrollToBottom()
  messageInput.value?.focus()
})

// Watchers
watch(messages, () => {
  nextTick(() => scrollToBottom())
}, { deep: true })
</script>
