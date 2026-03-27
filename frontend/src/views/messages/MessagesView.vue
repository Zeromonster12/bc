<template>
  <AppLayout>
    <div class="h-[calc(100svh-5.5rem)] rounded-2xl bg-slate-100 p-2 dark:bg-slate-950 sm:h-[calc(100svh-6rem)] sm:p-3 md:h-[calc(100vh-6rem)] md:p-4 lg:h-[calc(100vh-4rem)] lg:p-5">
      <div class="grid h-full gap-3 md:grid-cols-[22rem_minmax(0,1fr)]">
        <div
          :class="[
            'min-h-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900 flex flex-col',
            currentConversation ? 'hidden md:flex' : 'flex',
          ]"
        >
            <div class="border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900 dark:text-slate-100">Messages</h2>
                <button
                  @click="toggleNewConversationForm"
                  class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  New chat
                </button>
              </div>
              <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ messageStore.totalUnread }} unread</p>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search chats"
                class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
              />
            </div>

            <NewConversationForm
              :visible="showNewConversation"
              :mode="conversationMode"
              :recipient-query="newParticipantQuery"
              :group-subject="groupSubject"
              :selected-project-id="selectedProjectId"
              :project-required="!auth.isAdmin"
              :direct-dropdown-value="directRecipientDropdownId"
              :participant-dropdown-value="groupParticipantDropdownId"
              :project-options="projectOptions"
              :selected-recipient="selectedRecipient"
              :selected-participants="selectedParticipants"
              :recipient-options="recipientOptions"
              :searching-users="searchingUsers"
              :error-message="newConvError"
              :creating="creating"
              @update:recipient-query="onRecipientQueryChange"
              @update:mode="onConversationModeChange"
              @update:group-subject="groupSubject = String($event ?? '')"
              @update:selected-project-id="onProjectChange"
              @update:direct-dropdown-value="directRecipientDropdownId = $event"
              @update:participant-dropdown-value="groupParticipantDropdownId = $event"
              @add-participant-from-dropdown="addParticipantFromDropdown"
              @select-recipient="selectRecipient"
              @remove-participant="removeSelectedParticipant"
              @clear-recipient="clearSelectedRecipient"
              @submit="startConversation"
            />

            <ConversationList
              :conversations="filteredConversations"
              :active-id="currentConversation?.id"
              :loading="messageStore.loading"
              :current-user-id="Number(auth.user?.id ?? 0)"
              @select="openConversation"
              class="flex-1 overflow-y-auto"
            />
        </div>

        <div
          :class="[
            'min-h-0 overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-700/80 dark:bg-slate-950 flex flex-col',
            !currentConversation ? 'hidden md:flex' : 'flex',
          ]"
        >
            <div
              v-if="!currentConversation"
              class="flex-1 flex items-center justify-center text-slate-400 dark:text-slate-500"
            >
              <div class="px-6 text-center">
                <p class="text-2xl font-semibold text-slate-700 dark:text-slate-200">Your chats</p>
                <p class="mt-2 text-sm">Select a conversation from the left and start messaging.</p>
              </div>
            </div>

            <template v-else>
              <ConversationHeader
                :title="currentConversationTitle"
                :subtitle="currentConversationSubtitle"
                :participant-names="participantNames"
                @back="backToList"
              />
              <ChatComponent
                :conversation-id="currentConversation.id"
                @message-sent-local="onLocalMessageSent"
              />
            </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import { getEcho } from '@/services/core/echo'
import MessageService from '@/services/messages/MessageService'
import ApplicationService from '@/services/applications/ApplicationService'
import ProjectService from '@/services/projects/ProjectService'
import {
  buildParticipantNames,
  toNewGroupConversationPayload,
  toNewConversationPayload,
  validateNewGroupConversation,
  validateNewConversation,
} from '@/services/messages/MessagesViewService'
import AppLayout from '@/layouts/AppLayout.vue'
import ConversationList from '@/components/messages/ConversationList.vue'
import NewConversationForm from '@/components/messages/NewConversationForm.vue'
import ConversationHeader from '@/components/messages/ConversationHeader.vue'
import ChatComponent from '@/components/messages/ChatComponent.vue'

interface MessageConversation {
  id: number
  type?: 'direct' | 'group'
  subject?: string
  participants?: Array<{ id?: number; name?: string }>
  unread_count?: number
  last_message?: {
    id?: number
    body?: string
    created_at?: string
    read_at?: string | null
    sender?: {
      id?: number
      name?: string
      email?: string
    }
  }
  [key: string]: unknown
}

interface MessageSentPayload {
  conversation_id?: number
  message?: MessageConversation['last_message']
}

interface MessageReadPayload {
  conversation_id?: number
  reader_user_id?: number
  last_read_message_id?: number
  read_at?: string
}

interface RecipientOption {
  id: number
  name: string
  email: string
}

interface ProjectOption {
  id: number
  title: string
}

export default defineComponent({
  name: 'MessagesView',
  components: {
    AppLayout,
    ConversationList,
    NewConversationForm,
    ConversationHeader,
    ChatComponent,
  },
  setup() {
    return {
      auth: useAuthStore(),
      messageStore: useMessageStore(),
    }
  },
  data() {
    return {
      showNewConversation: false,
      conversationMode: 'direct' as 'direct' | 'group',
      newParticipantQuery: '',
      groupSubject: '',
      selectedProjectId: null as number | null,
      directRecipientDropdownId: null as number | null,
      groupParticipantDropdownId: null as number | null,
      selectedRecipient: null as RecipientOption | null,
      selectedParticipants: [] as RecipientOption[],
      recipientOptions: [] as RecipientOption[],
      projectOptions: [] as ProjectOption[],
      searchingUsers: false,
      loadingProjectOptions: false,
      newConvError: '',
      creating: false,
      searchQuery: '',
      subscribedConversationIds: [] as number[],
      recipientSearchTimerId: null as ReturnType<typeof setTimeout> | null,
      latestRecipientSearchNonce: 0,
    }
  },
  computed: {
    currentConversation(): MessageConversation | null {
      return this.messageStore.currentConversation as MessageConversation | null
    },
    participantNames(): string {
      return buildParticipantNames(this.currentConversation, this.auth.user?.id)
    },
    currentConversationTitle(): string {
      if (this.currentConversation?.type === 'group' && String(this.currentConversation?.subject ?? '').trim()) {
        return String(this.currentConversation.subject ?? '').trim()
      }

      return this.participantNames || 'Conversation'
    },
    currentConversationSubtitle(): string {
      if (this.currentConversation?.type !== 'group') {
        return ''
      }

      const projectTitle = String((this.currentConversation?.project as { title?: string } | null)?.title ?? '').trim()
      return projectTitle ? `Project: ${projectTitle}` : 'Group conversation'
    },
    filteredConversations(): MessageConversation[] {
      const q = this.searchQuery.trim().toLowerCase()
      if (!q) return this.messageStore.conversations as MessageConversation[]

      return (this.messageStore.conversations as MessageConversation[]).filter((conversation) => {
        const names = String(buildParticipantNames(conversation, this.auth.user?.id)).toLowerCase()
        const lastMessage = String(
          (conversation as { last_message?: { body?: string } }).last_message?.body ?? '',
        ).toLowerCase()

        return names.includes(q) || lastMessage.includes(q)
      })
    },
    conversationIds(): number[] {
      return (this.messageStore.conversations as MessageConversation[])
        .map((conversation) => Number(conversation.id))
        .filter((id) => Number.isFinite(id) && id > 0)
    },
    conversationIdsKey(): string {
      return this.conversationIds.join(',')
    },
  },
  watch: {
    conversationIdsKey() {
      this.refreshRealtimeSubscriptions()
    },
  },
  async mounted() {
    await this.loadGroupProjectOptions()
    await this.messageStore.fetchConversations()
    this.refreshRealtimeSubscriptions()
  },
  beforeUnmount() {
    if (this.recipientSearchTimerId) {
      clearTimeout(this.recipientSearchTimerId)
      this.recipientSearchTimerId = null
    }

    this.unsubscribeAllRealtime()
  },
  methods: {
    onRecipientQueryChange(value: string) {
      this.newParticipantQuery = value
      if (this.conversationMode === 'direct') {
        this.selectedRecipient = null
      }
      this.newConvError = ''

      if (this.recipientSearchTimerId) {
        clearTimeout(this.recipientSearchTimerId)
      }

      this.recipientSearchTimerId = setTimeout(() => {
        this.searchRecipients(this.newParticipantQuery)
      }, 220)
    },
    selectRecipient(option: RecipientOption) {
      if (this.conversationMode === 'group') {
        const exists = this.selectedParticipants.some((participant) => participant.id === option.id)
        if (!exists) {
          this.selectedParticipants.push(option)
        }

        this.newParticipantQuery = ''
        this.newConvError = ''
        return
      }

      this.selectedRecipient = option
      this.directRecipientDropdownId = option.id
      this.newParticipantQuery = `${option.name} (${option.email})`
      this.newConvError = ''
    },
    removeSelectedParticipant(userId: number) {
      this.selectedParticipants = this.selectedParticipants.filter((participant) => participant.id !== userId)
    },
    addParticipantFromDropdown(selectedUserId?: number) {
      const selectedId = Number(selectedUserId ?? this.groupParticipantDropdownId ?? 0)
      if (!Number.isFinite(selectedId) || selectedId <= 0) {
        this.newConvError = 'Select participant first.'
        return
      }

      const option = this.recipientOptions.find((candidate) => candidate.id === selectedId)
      if (!option) {
        this.newConvError = 'Selected participant is no longer available.'
        return
      }

      this.selectRecipient(option)
      this.groupParticipantDropdownId = null
      void this.loadGroupParticipantOptions(this.selectedProjectId)
    },
    clearSelectedRecipient() {
      this.selectedRecipient = null
      this.directRecipientDropdownId = null
      this.newParticipantQuery = ''
      void this.searchRecipients('')
    },
    onConversationModeChange(mode: 'direct' | 'group') {
      this.conversationMode = mode
      this.newConvError = ''
      this.newParticipantQuery = ''
      this.recipientOptions = []
      this.directRecipientDropdownId = null
      this.groupParticipantDropdownId = null

      if (mode === 'direct') {
        this.groupSubject = ''
        this.selectedProjectId = null
        this.selectedParticipants = []
        void this.searchRecipients('')
        return
      }

      this.selectedRecipient = null

      if (this.auth.isAdmin) {
        this.selectedProjectId = null
        void this.loadGroupParticipantOptions(null)
      }
    },
    onProjectChange(projectId: number | null) {
      this.selectedProjectId = projectId
      this.selectedParticipants = []
      this.groupParticipantDropdownId = null
      this.newParticipantQuery = ''
      this.recipientOptions = []
      this.newConvError = ''

      void this.loadGroupParticipantOptions(projectId)
    },
    async loadGroupParticipantOptions(projectId: number | null) {
      const normalizedProjectId = Number(projectId ?? 0)
      const isAdmin = this.auth.isAdmin

      if (!isAdmin && (!Number.isFinite(normalizedProjectId) || normalizedProjectId <= 0)) {
        this.recipientOptions = []
        return
      }

      this.searchingUsers = true
      try {
        const response = await MessageService.searchConversationUsers('', 50, isAdmin ? null : normalizedProjectId)
        const options = Array.isArray(response?.data) ? response.data : []

        this.recipientOptions = options
          .map((item: Partial<RecipientOption>) => ({
            id: Number(item.id ?? 0),
            name: String(item.name ?? ''),
            email: String(item.email ?? ''),
          }))
          .filter((item: RecipientOption) => item.id > 0 && item.name && item.email)
      } catch {
        this.recipientOptions = []
      } finally {
        this.searchingUsers = false
      }
    },
    async loadGroupProjectOptions() {
      this.loadingProjectOptions = true
      try {
        if (this.auth.isAdmin) {
          const response = await ProjectService.getAll({
            per_page: 100,
          })

          const projects = Array.isArray(response?.data) ? response.data : []
          this.projectOptions = projects
            .map((project: { id?: number; title?: string }) => ({
              id: Number(project.id ?? 0),
              title: String(project.title ?? '').trim(),
            }))
            .filter((project: ProjectOption) => project.id > 0 && project.title.length > 0)
          return
        }

        if (this.auth.isCompany) {
          const response = await ProjectService.getAll({
            company_id: Number(this.auth.user?.id ?? 0),
            per_page: 100,
          })

          const projects = Array.isArray(response?.data) ? response.data : []
          this.projectOptions = projects
            .map((project: { id?: number; title?: string }) => ({
              id: Number(project.id ?? 0),
              title: String(project.title ?? '').trim(),
            }))
            .filter((project: ProjectOption) => project.id > 0 && project.title.length > 0)
          return
        }

        if (this.auth.isStudent) {
          const response = await ApplicationService.getAll({
            status: 'accepted',
            per_page: 200,
          })

          const applications = Array.isArray(response?.data) ? response.data : []
          const uniqueByProject = new Map<number, ProjectOption>()

          applications.forEach((application: { project?: { id?: number; title?: string } }) => {
            const projectId = Number(application.project?.id ?? 0)
            const projectTitle = String(application.project?.title ?? '').trim()
            if (projectId > 0 && projectTitle) {
              uniqueByProject.set(projectId, { id: projectId, title: projectTitle })
            }
          })

          this.projectOptions = Array.from(uniqueByProject.values())
          return
        }

        this.projectOptions = []
      } catch {
        this.projectOptions = []
      } finally {
        this.loadingProjectOptions = false
      }
    },
    async searchRecipients(rawQuery: string) {
      if (this.conversationMode !== 'direct') {
        return
      }

      const query = rawQuery.trim()
      if (query.length > 0 && query.length < 2) {
        this.searchingUsers = false
        return
      }

      const nonce = ++this.latestRecipientSearchNonce
      this.searchingUsers = true

      try {
        const projectId = this.conversationMode === 'group' ? this.selectedProjectId : null
        const response = await MessageService.searchConversationUsers(query, 8, projectId)
        if (nonce !== this.latestRecipientSearchNonce) {
          return
        }

        const options = Array.isArray(response?.data) ? response.data : []
        this.recipientOptions = options
          .map((item: Partial<RecipientOption>) => ({
            id: Number(item.id ?? 0),
            name: String(item.name ?? ''),
            email: String(item.email ?? ''),
          }))
          .filter((item: RecipientOption) => item.id > 0 && item.name && item.email)

        if (
          this.directRecipientDropdownId &&
          !this.recipientOptions.some((item: RecipientOption) => item.id === this.directRecipientDropdownId)
        ) {
          this.directRecipientDropdownId = null
        }
      } catch {
        if (nonce === this.latestRecipientSearchNonce) {
          this.recipientOptions = []
          this.directRecipientDropdownId = null
        }
      } finally {
        if (nonce === this.latestRecipientSearchNonce) {
          this.searchingUsers = false
        }
      }
    },
    toggleNewConversationForm() {
      this.showNewConversation = !this.showNewConversation

      if (this.showNewConversation && this.conversationMode === 'direct') {
        this.newConvError = ''
        void this.searchRecipients('')
        return
      }

      if (this.showNewConversation && this.conversationMode === 'group' && this.auth.isAdmin) {
        this.newConvError = ''
        void this.loadGroupParticipantOptions(null)
      }
    },
    refreshRealtimeSubscriptions() {
      if (!this.auth.token) return

      const echo = getEcho(this.auth.token)
      const wanted = new Set(this.conversationIds)

      for (const conversationId of this.subscribedConversationIds) {
        if (!wanted.has(conversationId)) {
          echo.leave(`private-conversations.${conversationId}`)
        }
      }

      const current = new Set(this.subscribedConversationIds)
      for (const conversationId of wanted) {
        if (current.has(conversationId)) continue

        echo
          .private(`conversations.${conversationId}`)
          .listen('.message.sent', (payload: MessageSentPayload) => {
            this.onRealtimeMessage(payload)
          })
          .listen('.message.read', (payload: MessageReadPayload) => {
            this.onRealtimeRead(payload)
          })
      }

      this.subscribedConversationIds = [...wanted]
    },
    unsubscribeAllRealtime() {
      if (!this.auth.token) return

      const echo = getEcho(this.auth.token)
      for (const conversationId of this.subscribedConversationIds) {
        echo.leave(`private-conversations.${conversationId}`)
      }

      this.subscribedConversationIds = []
    },
    onRealtimeMessage(payload: MessageSentPayload) {
      const conversationId = Number(payload?.conversation_id ?? 0)
      const incomingMessage = payload?.message
      if (!conversationId || !incomingMessage) return

      const conversations = this.messageStore.conversations as MessageConversation[]
      const index = conversations.findIndex(
        (conversation) => Number(conversation.id) === conversationId,
      )
      if (index === -1) return

      const conversation = conversations[index]
      if (!conversation) return

      conversation.last_message = incomingMessage

      const senderId = Number(incomingMessage.sender?.id ?? 0)
      const isCurrentUserSender = senderId === Number(this.auth.user?.id ?? 0)
      const isOpenConversation = Number(this.currentConversation?.id ?? 0) === conversationId

      if (!isCurrentUserSender && !isOpenConversation) {
        conversation.unread_count = Number(conversation.unread_count ?? 0) + 1
      } else {
        conversation.unread_count = 0
      }

      if (index > 0) {
        const moved = conversations.splice(index, 1)[0]
        if (moved) {
          conversations.unshift(moved)
        }
      }
    },
    onRealtimeRead(payload: MessageReadPayload) {
      const conversationId = Number(payload?.conversation_id ?? 0)
      const readerUserId = Number(payload?.reader_user_id ?? 0)
      const lastReadMessageId = Number(payload?.last_read_message_id ?? 0)
      const readAt = String(payload?.read_at ?? '')

      if (!conversationId || !readerUserId || !lastReadMessageId || !readAt) return
      if (readerUserId === Number(this.auth.user?.id ?? 0)) return

      const conversations = this.messageStore.conversations as MessageConversation[]
      const conversation = conversations.find((item) => Number(item.id) === conversationId)
      if (!conversation?.last_message) return

      const lastMessageId = Number(conversation.last_message.id ?? 0)
      const lastMessageSenderId = Number(conversation.last_message.sender?.id ?? 0)
      const isCurrentUserSender = lastMessageSenderId === Number(this.auth.user?.id ?? 0)

      if (!isCurrentUserSender || !lastMessageId || lastMessageId > lastReadMessageId) {
        return
      }

      conversation.last_message.read_at = readAt
    },
    onLocalMessageSent(message: MessageConversation['last_message']) {
      const conversationId = Number(this.currentConversation?.id ?? 0)
      if (!conversationId || !message) return

      const conversations = this.messageStore.conversations as MessageConversation[]
      const index = conversations.findIndex((item) => Number(item.id) === conversationId)
      if (index === -1) return

      const conversation = conversations[index]
      if (!conversation) return

      conversation.last_message = {
        ...message,
        read_at: message.read_at ?? null,
      }
      conversation.unread_count = 0

      if (index > 0) {
        const moved = conversations.splice(index, 1)[0]
        if (moved) {
          conversations.unshift(moved)
        }
      }
    },
    async openConversation(id: number) {
      await this.messageStore.openConversation(id)
    },
    backToList() {
      this.messageStore.currentConversation = null
    },
    async startConversation() {
      if (this.conversationMode === 'group') {
        const participantIds = this.selectedParticipants.map((participant) => participant.id)
        const validationError = this.auth.isAdmin
          ? (participantIds.length < 1
              ? 'Select at least one participant.'
              : (String(this.groupSubject ?? '').trim().length < 3
                  ? 'Group name must have at least 3 characters.'
                  : ''))
          : validateNewGroupConversation(
              this.selectedProjectId,
              this.groupSubject,
              participantIds,
            )

        if (validationError) {
          this.newConvError = validationError
          return
        }

        this.newConvError = ''
        this.creating = true

        try {
          const payload = this.auth.isAdmin
            ? {
                subject: String(this.groupSubject ?? '').trim(),
                participant_user_ids: Array.from(new Set(participantIds)),
                ...(Number(this.selectedProjectId ?? 0) > 0
                  ? { project_id: Number(this.selectedProjectId) }
                  : {}),
              }
            : toNewGroupConversationPayload(
                Number(this.selectedProjectId),
                this.groupSubject,
                participantIds,
              )

          await this.messageStore.startConversation({
            ...payload,
            type: 'group',
          })

          await this.messageStore.fetchConversations()
          this.refreshRealtimeSubscriptions()
          this.showNewConversation = false
          this.newParticipantQuery = ''
          this.selectedParticipants = []
          this.groupParticipantDropdownId = null
          this.groupSubject = ''
          this.selectedProjectId = null
          this.recipientOptions = []
          return
        } catch (e: unknown) {
          const err = e as { response?: { data?: { message?: string } } }
          this.newConvError = err?.response?.data?.message ?? 'Failed to create group conversation.'
          return
        } finally {
          this.creating = false
        }
      }

      const normalizedRecipientId = Number(this.selectedRecipient?.id ?? this.directRecipientDropdownId ?? 0)
      const hasRecipientId = Number.isFinite(normalizedRecipientId) && normalizedRecipientId > 0
      const emailMatch = String(this.newParticipantQuery ?? '')
        .match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i)
      const recipientEmail = String(emailMatch?.[0] ?? '').trim()
      const hasRecipientEmail = recipientEmail.length > 0

      if (!hasRecipientId && !hasRecipientEmail) {
        const validationError = validateNewConversation(null)
        this.newConvError = validationError
        return
      }

      this.newConvError = ''
      this.creating = true

      try {
        const payload = hasRecipientId
          ? toNewConversationPayload(normalizedRecipientId)
          : { recipient_email: recipientEmail }
        await this.messageStore.startConversation(payload)
        this.refreshRealtimeSubscriptions()
        this.showNewConversation = false
        this.newParticipantQuery = ''
        this.selectedRecipient = null
        this.directRecipientDropdownId = null
        this.recipientOptions = []
      } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } }
        this.newConvError = err?.response?.data?.message ?? 'Failed to start conversation.'
      } finally {
        this.creating = false
      }
    },
  },
})
</script>
