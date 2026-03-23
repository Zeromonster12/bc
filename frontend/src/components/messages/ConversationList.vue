<template>
  <div class="h-full overflow-y-auto bg-white">
    <div v-if="loading" class="flex h-full items-center justify-center">
      <p class="text-sm text-slate-400">Loading conversations...</p>
    </div>
    <div v-else-if="!conversations.length" class="flex h-full items-center justify-center px-4">
      <p class="text-sm text-slate-400 text-center">No conversations yet</p>
    </div>
    <ul v-else class="divide-y divide-slate-100">
      <li
        v-for="convo in conversations"
        :key="convo.id"
        :class="[
          'cursor-pointer px-4 py-3 transition',
          activeId === convo.id ? 'bg-indigo-50/70' : 'hover:bg-slate-50',
        ]"
        @click="$emit('select', convo.id)"
      >
        <div class="flex items-start gap-3">
          <div
            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700"
          >
            {{ initialsForConversation(convo) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <p class="truncate text-sm font-semibold text-slate-900">
                {{ conversationTitle(convo) }}
              </p>
              <span class="ml-auto shrink-0 text-[11px] text-slate-400">
                {{ formatConversationTime(convo.last_message?.created_at || convo.created_at) }}
              </span>
            </div>
            <div class="mt-0.5 flex items-center gap-2">
              <p class="truncate text-xs text-slate-500">
                {{ convo.last_message?.body || 'No messages yet' }}
              </p>
              <span
                v-if="lastMessageStatus(convo)"
                :class="[
                  'ml-auto shrink-0 text-[11px] font-semibold',
                  lastMessageStatus(convo) === 'seen' ? 'text-sky-600' : 'text-slate-500',
                ]"
              >
                {{ lastMessageStatus(convo) === 'seen' ? 'Seen' : 'Delivered' }}
              </span>
              <span
                v-if="convo.unread_count > 0"
                class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-semibold text-white"
              >
                {{ convo.unread_count }}
              </span>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface ConversationItem {
  id: number
  subject?: string
  participants?: Array<{ id?: number; name?: string }>
  unread_count?: number
  created_at?: string
  project?: {
    title?: string
  }
  last_message?: {
    id?: number
    body?: string
    created_at?: string
    read_at?: string | null
    sender?: {
      id?: number
    }
  }
}

export default defineComponent({
  name: 'ConversationList',
  props: {
    conversations: {
      type: Array as PropType<ConversationItem[]>,
      default: () => [],
    },
    activeId: {
      type: Number,
      default: null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    currentUserId: {
      type: Number,
      default: 0,
    },
  },
  emits: ['select'],
  methods: {
    conversationTitle(conversation: ConversationItem): string {
      const participantNames = (conversation.participants ?? [])
        .filter((participant) => Number(participant.id ?? 0) !== this.currentUserId)
        .map((participant) => String(participant.name ?? '').trim())
        .filter(Boolean)
        .join(', ')

      return participantNames || conversation.project?.title || 'Chat'
    },
    lastMessageStatus(conversation: ConversationItem): 'delivered' | 'seen' | null {
      const senderId = Number(conversation.last_message?.sender?.id ?? 0)
      if (!senderId || senderId !== this.currentUserId) {
        return null
      }

      return conversation.last_message?.read_at ? 'seen' : 'delivered'
    },
    initialsForConversation(conversation: ConversationItem): string {
      const source = this.conversationTitle(conversation)
      const words = source
        .split(' ')
        .map((w) => w.trim())
        .filter(Boolean)
      const initials = words
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
      return (initials || 'C').toUpperCase()
    },
    formatConversationTime(value?: string): string {
      if (!value) return ''
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return ''

      const now = new Date()
      const isSameDay =
        date.getDate() === now.getDate() &&
        date.getMonth() === now.getMonth() &&
        date.getFullYear() === now.getFullYear()

      if (isSameDay) {
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
      }

      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    },
  },
})
</script>
