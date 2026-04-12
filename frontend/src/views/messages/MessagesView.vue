<template>
  <AppLayout>
    <div class="h-[calc(100svh-5.5rem)] rounded-3xl bg-[#f3f2f8] p-2 dark:bg-slate-950 sm:h-[calc(100svh-6rem)] sm:p-3 md:h-[calc(100vh-6rem)] md:p-4 lg:h-[calc(100vh-4rem)] lg:p-5">
      <div class="grid h-full gap-4 md:grid-cols-[22rem_minmax(0,1fr)]">
        <div
          :class="[
            'min-h-0 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 flex flex-col',
            currentConversation ? 'hidden md:flex' : 'flex',
          ]"
        >
            <div class="bg-white px-8 py-6 dark:bg-slate-900">
              <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900 dark:text-slate-100">Messages</h2>
                <button
                  @click="toggleNewConversationForm"
                  class="rounded-full bg-[#3f34a6] px-3 py-1.5 text-xs font-semibold text-white hover:brightness-105 dark:bg-indigo-600"
                >
                  New chat
                </button>
              </div>
              <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ messageStore.totalUnread }} unread</p>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search chats"
                class="mt-3 w-full rounded-full bg-[#e8e3f2] px-3 py-2 text-sm text-slate-800 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
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
            'min-h-0 overflow-hidden rounded-3xl bg-white dark:bg-slate-950 flex flex-col',
            !currentConversation ? 'hidden md:flex' : 'flex',
          ]"
        >
            <div
              v-if="!currentConversation"
              class="flex-1 flex items-center justify-center text-slate-400 dark:text-slate-500"
            >
              <div class="px-6 text-center">
                <p class="text-2xl font-semibold text-[#332d52] dark:text-slate-200">Your chats</p>
                <p class="mt-2 text-sm">Select a conversation from the left and start messaging.</p>
              </div>
            </div>

            <template v-else>
              <ConversationHeader
                :title="currentConversationTitle"
                :subtitle="currentConversationSubtitle"
                :participants-label="currentConversationParticipantsLabel"
                :participants-extra-count="currentConversationParticipantsExtraCount"
                :participants-tooltip="currentConversationParticipantsTooltip"
                :subtitle-secondary="currentConversationProjectSubtitle"
                :avatar-url="currentConversationAvatarUrl"
                :participant-names="participantNames"
                :show-group-actions="canManageCurrentGroup"
                @back="backToList"
                @rename-group="openGroupManage('rename')"
                @change-group-avatar="openGroupManage('avatar')"
                @add-group-users="openGroupManage('participants')"
                @delete-group="openGroupManage('delete')"
              />
              <ChatComponent
                :conversation-id="currentConversation.id"
                @message-sent-local="onLocalMessageSent"
              />
            </template>
        </div>
      </div>

      <GroupManageModal
        :visible="showGroupManageModal"
        :mode="groupManageMode"
        :title="groupModalTitle"
        :subtitle="groupModalSubtitle"
        :submitting="groupManageSubmitting"
        :error-message="groupManageError"
        :edit-name="groupEditName"
        :avatar-preview="groupAvatarPreview"
        :has-avatar-file="Boolean(groupAvatarFile)"
        :participants="currentConversation?.participants ?? []"
        :can-manage-current-group="canManageCurrentGroup"
        :can-demote-participant="canDemoteParticipant"
        :add-candidates="groupAddCandidates"
        :add-user-id="groupAddUserId"
        @close="closeGroupManageModal"
        @update:edit-name="groupEditName = String($event ?? '')"
        @save-name="saveGroupName"
        @avatar-selected="onGroupAvatarSelected"
        @remove-avatar="removeGroupAvatar"
        @save-avatar="saveGroupAvatar"
        @promote-admin="promoteParticipantAdmin"
        @demote-admin="demoteParticipantAdmin"
        @remove-user="removeParticipantFromGroup"
        @update:add-user-id="groupAddUserId = Number($event ?? 0) > 0 ? Number($event) : null"
        @add-user="addUserToGroup"
        @delete-group="deleteCurrentGroup"
      />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useMessageStore } from '@/stores/message'
import { useNotificationStore } from '@/stores/notification'
import MessageService, {
  type RealtimeMessagePayload,
  type RealtimeReadPayload,
} from '@/services/messages/MessageService'
import ApplicationService from '@/services/applications/ApplicationService'
import ProjectService from '@/services/projects/ProjectService'
import {
  applyLocalConversationMessagePreview,
  applyRealtimeConversationMessagePreview,
  applyRealtimeConversationReadPreview,
  buildGroupAddCandidates,
  buildParticipantNames,
  canDemoteGroupParticipant,
  extractAcceptedApplicationProjects,
  extractErrorMessage,
  normalizeProjectOptions,
  normalizeRecipientOptions,
  type ProjectOption,
  type RecipientOption,
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
import GroupManageModal from '@/components/messages/GroupManageModal.vue'

interface MessageConversation {
  id: number
  type?: 'direct' | 'group'
  subject?: string
  avatar_url?: string | null
  project?: { id?: number; title?: string }
  participants?: Array<{ id?: number; name?: string; avatar_url?: string | null; is_admin?: boolean }>
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

export default defineComponent({
  name: 'MessagesView',
  components: {
    AppLayout,
    ConversationList,
    NewConversationForm,
    ConversationHeader,
    ChatComponent,
    GroupManageModal,
  },
  setup() {
    return {
      auth: useAuthStore(),
      messageStore: useMessageStore(),
      notificationStore: useNotificationStore(),
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
      showGroupManageModal: false,
      groupManageMode: 'rename' as 'rename' | 'avatar' | 'participants' | 'delete',
      groupManageSubmitting: false,
      groupManageError: '',
      groupEditName: '',
      groupAvatarFile: null as File | null,
      groupAvatarPreview: '',
      groupAddUserId: null as number | null,
      groupAddCandidates: [] as RecipientOption[],
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
    currentConversationAvatarUrl(): string {
      if (this.currentConversation?.type === 'group') {
        return String(this.currentConversation?.avatar_url ?? '').trim()
      }

      if (this.currentConversation?.type === 'direct') {
        const currentUserId = Number(this.auth.user?.id ?? 0)
        return String(
          (this.currentConversation?.participants ?? []).find(
            (participant) => Number(participant.id ?? 0) !== currentUserId,
          )?.avatar_url ?? '',
        ).trim()
      }

      return ''
    },
    currentConversationSubtitle(): string {
      if (this.currentConversation?.type !== 'group') {
        return ''
      }

      return this.currentConversationParticipantsLabel || 'Group conversation'
    },
    currentConversationParticipantsLabel(): string {
      if (this.currentConversation?.type !== 'group') {
        return ''
      }

      const currentUserId = Number(this.auth.user?.id ?? 0)
      const participantIds = (this.currentConversation?.participants ?? [])
        .map((participant) => Number(participant.id ?? 0))
        .filter((id) => Number.isFinite(id) && id > 0)

      if (!participantIds.length) {
        return ''
      }

      return currentUserId > 0 && participantIds.includes(currentUserId)
        ? 'Participants: You'
        : 'Participants'
    },
    currentConversationParticipantsExtraCount(): number {
      if (this.currentConversation?.type !== 'group') {
        return 0
      }

      const currentUserId = Number(this.auth.user?.id ?? 0)
      const uniqueParticipantIds = Array.from(
        new Set(
          (this.currentConversation?.participants ?? [])
            .map((participant) => Number(participant.id ?? 0))
            .filter((id) => Number.isFinite(id) && id > 0),
        ),
      )

      if (!uniqueParticipantIds.length) {
        return 0
      }

      if (currentUserId > 0 && uniqueParticipantIds.includes(currentUserId)) {
        return uniqueParticipantIds.filter((id) => id !== currentUserId).length
      }

      return uniqueParticipantIds.length
    },
    currentConversationParticipantsTooltip(): string {
      if (this.currentConversation?.type !== 'group') {
        return ''
      }

      const currentUserId = Number(this.auth.user?.id ?? 0)
      const names = (this.currentConversation?.participants ?? [])
        .filter((participant) => Number(participant.id ?? 0) !== currentUserId)
        .map((participant) => {
          const label = String(participant.name ?? '').trim()
          if (!label) {
            return ''
          }

          return participant.is_admin ? `${label} (Admin)` : label
        })
        .filter((name, index, arr) => Boolean(name) && arr.indexOf(name) === index)

      return names.join(', ')
    },
    currentConversationProjectSubtitle(): string {
      if (this.currentConversation?.type !== 'group') {
        return ''
      }

      const projectTitle = String((this.currentConversation?.project as { title?: string } | null)?.title ?? '').trim()

      return projectTitle ? `Project: ${projectTitle}` : ''
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
    canManageCurrentGroup(): boolean {
      if (this.currentConversation?.type !== 'group') {
        return false
      }

      const currentUserId = Number(this.auth.user?.id ?? 0)
      if (currentUserId <= 0) {
        return false
      }

      return Boolean(
        (this.currentConversation?.participants ?? []).find(
          (participant) => Number(participant.id ?? 0) === currentUserId && Boolean(participant.is_admin),
        ),
      )
    },
    groupModalTitle(): string {
      if (this.groupManageMode === 'rename') return 'Rename Group'
      if (this.groupManageMode === 'avatar') return 'Group Photo'
      if (this.groupManageMode === 'participants') return 'Add Users'
      return 'Delete Group'
    },
    groupModalSubtitle(): string {
      if (this.groupManageMode === 'rename') return 'Change the group chat name.'
      if (this.groupManageMode === 'avatar') return 'Upload a new photo to the groupavatar bucket.'
      if (this.groupManageMode === 'participants') return 'Add eligible users to this group.'
      return 'This action cannot be undone.'
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
    openGroupManage(mode: 'rename' | 'avatar' | 'participants' | 'delete') {
      if (!this.canManageCurrentGroup) {
        return
      }

      this.groupManageMode = mode
      this.groupManageError = ''
      this.groupManageSubmitting = false
      this.groupEditName = String(this.currentConversation?.subject ?? '').trim()
      this.groupAvatarFile = null
      this.groupAvatarPreview = String(this.currentConversationAvatarUrl ?? '').trim()
      this.groupAddUserId = null
      this.groupAddCandidates = []
      this.showGroupManageModal = true

      if (mode === 'participants') {
        void this.loadGroupAddCandidates()
      }
    },
    closeGroupManageModal() {
      this.showGroupManageModal = false
      this.groupManageError = ''
      this.groupManageSubmitting = false
      this.groupAvatarFile = null
      this.groupAddUserId = null
    },
    currentConversationId(): number {
      return Number(this.currentConversation?.id ?? 0)
    },
    async reloadCurrentConversationPreservingSelection(deletedConversationId?: number) {
      await this.messageStore.fetchConversations()
      this.refreshRealtimeSubscriptions()

      const activeId = this.currentConversationId()
      const targetId = deletedConversationId && activeId === deletedConversationId ? 0 : activeId

      if (targetId > 0) {
        await this.messageStore.openConversation(targetId)
        return
      }

      if (deletedConversationId && activeId === deletedConversationId) {
        this.messageStore.currentConversation = null
      }
    },
    onGroupAvatarSelected(event: Event) {
      const input = event.target as HTMLInputElement
      const file = input.files?.[0] ?? null
      this.groupAvatarFile = file
      this.groupManageError = ''

      if (file) {
        this.groupAvatarPreview = URL.createObjectURL(file)
      }
    },
    async saveGroupName() {
      const conversationId = this.currentConversationId()
      const subject = this.groupEditName.trim()
      if (!conversationId || !subject) {
        this.groupManageError = 'Group name is required.'
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.updateGroupConversation(conversationId, { subject })
        await this.reloadCurrentConversationPreservingSelection()
        this.closeGroupManageModal()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to rename group.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    async saveGroupAvatar() {
      const conversationId = this.currentConversationId()
      if (!conversationId || !this.groupAvatarFile) {
        this.groupManageError = 'Select an image first.'
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.updateGroupConversation(conversationId, { avatar: this.groupAvatarFile })
        await this.reloadCurrentConversationPreservingSelection()
        this.closeGroupManageModal()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to update group photo.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    async removeGroupAvatar() {
      const conversationId = this.currentConversationId()
      if (!conversationId) {
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.updateGroupConversation(conversationId, { remove_avatar: true })
        await this.reloadCurrentConversationPreservingSelection()
        this.closeGroupManageModal()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to remove group photo.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    async loadGroupAddCandidates() {
      const conversation = this.currentConversation
      if (!conversation) {
        this.groupAddCandidates = []
        return
      }

      const projectId = Number((conversation.project as { id?: number } | null)?.id ?? 0)
      const projectParam = Number.isFinite(projectId) && projectId > 0 ? projectId : null

      try {
        const response = await MessageService.searchConversationUsers('', 100, projectParam)
        this.groupAddCandidates = buildGroupAddCandidates(response?.data, conversation.participants ?? [])
      } catch {
        this.groupAddCandidates = []
      }
    },
    async addUserToGroup() {
      const conversationId = this.currentConversationId()
      const userId = Number(this.groupAddUserId ?? 0)
      if (!conversationId || userId <= 0) {
        this.groupManageError = 'Select user first.'
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.addConversationParticipant(conversationId, userId)
        await this.reloadCurrentConversationPreservingSelection()
        this.groupAddUserId = null
        await this.loadGroupAddCandidates()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to add user.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    canDemoteParticipant(participantUserId: number): boolean {
      return canDemoteGroupParticipant(this.currentConversation?.participants ?? [], participantUserId)
    },
    async promoteParticipantAdmin(participantUserId: number) {
      const conversationId = this.currentConversationId()
      if (!conversationId || participantUserId <= 0) {
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.promoteConversationParticipantAdmin(conversationId, participantUserId)
        await this.reloadCurrentConversationPreservingSelection()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to grant admin permission.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    async demoteParticipantAdmin(participantUserId: number) {
      const conversationId = this.currentConversationId()
      if (!conversationId || participantUserId <= 0) {
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.demoteConversationParticipantAdmin(conversationId, participantUserId)
        await this.reloadCurrentConversationPreservingSelection()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to revoke admin permission.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    async removeParticipantFromGroup(participantUserId: number) {
      const conversationId = this.currentConversationId()
      if (!conversationId || participantUserId <= 0) {
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.removeConversationParticipant(conversationId, participantUserId)
        await this.reloadCurrentConversationPreservingSelection()
        await this.loadGroupAddCandidates()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to remove user from group.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
    async deleteCurrentGroup() {
      const conversationId = this.currentConversationId()
      if (!conversationId) {
        return
      }

      this.groupManageSubmitting = true
      this.groupManageError = ''
      try {
        await MessageService.deleteConversation(conversationId)
        await this.reloadCurrentConversationPreservingSelection(conversationId)
        this.closeGroupManageModal()
      } catch (e: unknown) {
        this.groupManageError = extractErrorMessage(e, 'Failed to delete group.')
      } finally {
        this.groupManageSubmitting = false
      }
    },
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
        this.recipientOptions = normalizeRecipientOptions(response?.data)
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

          this.projectOptions = normalizeProjectOptions(response?.data)
          return
        }

        if (this.auth.isCompany) {
          const response = await ProjectService.getAll({
            company_id: Number(this.auth.user?.id ?? 0),
            per_page: 100,
          })

          this.projectOptions = normalizeProjectOptions(response?.data)
          return
        }

        if (this.auth.isStudent) {
          const response = await ApplicationService.getAll({
            status: 'accepted',
            per_page: 200,
          })

          this.projectOptions = extractAcceptedApplicationProjects(response?.data)
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
        const response = await MessageService.searchConversationUsers(query, 8, null)
        if (nonce !== this.latestRecipientSearchNonce) {
          return
        }

        this.recipientOptions = normalizeRecipientOptions(response?.data)

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
      const wanted = new Set(this.conversationIds)

      for (const conversationId of this.subscribedConversationIds) {
        if (!wanted.has(conversationId)) {
          MessageService.unsubscribeFromConversationRealtime(conversationId)
        }
      }

      const current = new Set(this.subscribedConversationIds)
      for (const conversationId of wanted) {
        if (current.has(conversationId)) continue

        MessageService.subscribeToConversationRealtime(conversationId, {
          onMessageSent: (payload) => {
            this.onRealtimeMessage(payload)
          },
          onMessageRead: (payload) => {
            this.onRealtimeRead(payload)
          },
        })
      }

      this.subscribedConversationIds = [...wanted]
    },
    unsubscribeAllRealtime() {
      for (const conversationId of this.subscribedConversationIds) {
        MessageService.unsubscribeFromConversationRealtime(conversationId)
      }

      this.subscribedConversationIds = []
    },
    onRealtimeMessage(payload: RealtimeMessagePayload) {
      const conversations = this.messageStore.conversations as MessageConversation[]

      applyRealtimeConversationMessagePreview(
        conversations,
        payload,
        Number(this.auth.user?.id ?? 0),
        Number(this.currentConversation?.id ?? 0),
      )
    },
    onRealtimeRead(payload: RealtimeReadPayload) {
      const conversations = this.messageStore.conversations as MessageConversation[]

      applyRealtimeConversationReadPreview(
        conversations,
        payload,
        Number(this.auth.user?.id ?? 0),
      )
    },
    onLocalMessageSent(message: MessageConversation['last_message']) {
      const conversationId = Number(this.currentConversation?.id ?? 0)
      const conversations = this.messageStore.conversations as MessageConversation[]

      applyLocalConversationMessagePreview(conversations, conversationId, message)
    },
    async openConversation(id: number) {
      await this.messageStore.openConversation(id)
      await this.notificationStore.markConversationRead(id)
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
          this.newConvError = extractErrorMessage(e, 'Failed to create group conversation.')
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
        this.newConvError = extractErrorMessage(e, 'Failed to start conversation.')
      } finally {
        this.creating = false
      }
    },
  },
})
</script>
