<template>
  <div
    v-if="visible"
    class="space-y-4 bg-white px-8 pb-8 pt-2 dark:bg-slate-900"
  >
    <div class="grid grid-cols-2 rounded-full bg-[#e8e3f2] p-1 dark:bg-slate-900">
      <button
        type="button"
        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
        :class="mode === 'direct' ? 'bg-white text-[#201f35] dark:bg-slate-700 dark:text-slate-100' : 'text-[#5f6078] dark:text-slate-300'"
        @click="$emit('update:mode', 'direct')"
      >
        Direct
      </button>
      <button
        type="button"
        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
        :class="mode === 'group' ? 'bg-white text-[#201f35] dark:bg-slate-700 dark:text-slate-100' : 'text-[#5f6078] dark:text-slate-300'"
        @click="$emit('update:mode', 'group')"
      >
        Group
      </button>
    </div>

    <div v-if="mode === 'group'" class="space-y-3">
      <BaseInput
        :model-value="groupSubject"
        label="Group name"
        placeholder="Project Team Chat"
        @update:modelValue="$emit('update:groupSubject', $event)"
      />

      <div>
        <label class="mb-2 block text-sm font-semibold tracking-normal text-slate-700 dark:text-slate-300">{{ projectLabelText }}</label>
        <div class="relative">
          <button
            type="button"
            :class="[
              'flex w-full items-center justify-between rounded-full bg-[#e8e3f2] px-4 py-2.5 text-left text-sm text-slate-900 outline-none transition hover:bg-[#ddd7f6] focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus:ring-indigo-400',
              projectDropdownOpen
                ? 'bg-[#ddd7f6] ring-2 ring-[#cfc3ee] dark:bg-slate-700 dark:ring-indigo-500/40'
                : '',
            ]"
            @click="projectDropdownOpen = !projectDropdownOpen"
          >
            <span class="truncate">{{ projectDropdownLabel }}</span>
            <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">v</span>
          </button>

          <div
            v-if="projectDropdownOpen"
            class="absolute z-40 mt-2 max-h-56 w-full overflow-y-auto rounded-2xl border border-[#d8d1ec] bg-white p-1 ring-1 ring-[#ece7f8] dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-700/60"
          >
            <div class="sticky top-0 z-10 border-b border-[#ece7f8] bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
              <input
                v-model="projectSearchQuery"
                type="text"
                placeholder="Search project"
                class="w-full rounded-full bg-[#e8e3f2] px-3 py-2 text-sm text-slate-800 outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:focus:bg-slate-900"
              />
            </div>

            <p v-if="filteredProjectOptions.length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
              No projects found.
            </p>
            <button
              v-for="project in filteredProjectOptions"
              :key="project.id"
              type="button"
              class="block w-full rounded-xl px-3 py-2 text-left transition hover:bg-[#f1edf8] dark:hover:bg-slate-800"
              @click="onSelectProjectOption(project)"
            >
              <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ project.title }}</p>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="mode === 'direct'" class="space-y-2">
      <label class="mb-1 block text-sm font-semibold tracking-normal text-slate-700 dark:text-slate-300">Recipient</label>
      <div class="relative">
        <button
          type="button"
          :class="[
            'flex w-full items-center justify-between rounded-full bg-[#e8e3f2] px-4 py-2.5 text-left text-sm text-slate-900 outline-none transition hover:bg-[#ddd7f6] focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus:ring-indigo-400',
            directDropdownOpen
              ? 'bg-[#ddd7f6] ring-2 ring-[#cfc3ee] dark:bg-slate-700 dark:ring-indigo-500/40'
              : '',
          ]"
          @click="directDropdownOpen = !directDropdownOpen"
        >
          <span class="truncate">{{ directDropdownLabel }}</span>
          <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">v</span>
        </button>

        <div
          v-if="directDropdownOpen"
          class="absolute z-40 mt-2 max-h-56 w-full overflow-y-auto rounded-2xl border border-[#d8d1ec] bg-white p-1 ring-1 ring-[#ece7f8] dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-700/60"
        >
          <div class="sticky top-0 z-10 border-b border-[#ece7f8] bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
            <input
              :value="recipientQuery"
              type="text"
              placeholder="Search by name or email"
              class="w-full rounded-full bg-[#e8e3f2] px-3 py-2 text-sm text-slate-800 outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:focus:bg-slate-900"
              @input="$emit('update:recipientQuery', ($event.target as HTMLInputElement).value)"
            />
          </div>

          <p v-if="searchingUsers" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">Searching users...</p>
          <p
            v-else-if="recipientOptions.length === 0"
            class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400"
          >
            No users found.
          </p>
          <button
            v-for="option in recipientOptions"
            :key="option.id"
            type="button"
            class="block w-full rounded-xl px-3 py-2 text-left transition hover:bg-[#f1edf8] dark:hover:bg-slate-800"
            @click="onSelectDirectOption(option)"
          >
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ option.name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ option.email }}</p>
          </button>
        </div>
      </div>
    </div>

    <div v-if="mode === 'group'" class="space-y-3">
      <label class="mb-1 block text-sm font-semibold tracking-normal text-slate-700 dark:text-slate-300">Participants</label>
      <div class="relative">
        <button
          type="button"
          :class="[
            'flex w-full items-center justify-between rounded-full bg-[#e8e3f2] px-4 py-2.5 text-left text-sm text-slate-900 outline-none transition hover:bg-[#ddd7f6] focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus:ring-indigo-400',
            groupDropdownOpen
              ? 'bg-[#ddd7f6] ring-2 ring-[#cfc3ee] dark:bg-slate-700 dark:ring-indigo-500/40'
              : '',
          ]"
          @click="groupDropdownOpen = !groupDropdownOpen"
        >
          <span class="truncate">{{ groupDropdownLabel }}</span>
          <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">v</span>
        </button>

        <div
          v-if="groupDropdownOpen"
          class="absolute z-40 mt-2 max-h-56 w-full overflow-y-auto rounded-2xl border border-[#d8d1ec] bg-white p-1 ring-1 ring-[#ece7f8] dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-700/60"
        >
          <div class="sticky top-0 z-10 border-b border-[#ece7f8] bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
            <input
              v-model="groupSearchQuery"
              type="text"
              placeholder="Search project member"
              class="w-full rounded-full bg-[#e8e3f2] px-3 py-2 text-sm text-slate-800 outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:focus:bg-slate-900"
            />
          </div>

          <p v-if="searchingUsers" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">Loading project members...</p>
          <p v-else-if="groupFilteredRecipientOptions.length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
            No users found.
          </p>
          <button
            v-for="option in groupFilteredRecipientOptions"
            :key="option.id"
            type="button"
            class="block w-full rounded-xl px-3 py-2 text-left transition hover:bg-[#f1edf8] dark:hover:bg-slate-800"
            @click="onSelectGroupOption(option)"
          >
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ option.name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ option.email }}</p>
          </button>
        </div>
      </div>
    </div>

    <p
      v-if="mode === 'direct' && selectedRecipient"
      class="rounded-2xl bg-[#e8e3f2] px-4 py-2.5 text-xs text-[#3f365e] dark:bg-slate-800 dark:text-slate-200"
    >
      Selected: <strong>{{ selectedRecipient.name }}</strong>
      <span class="text-[#5c4b9f] dark:text-indigo-300">({{ selectedRecipient.email }})</span>
      <button
        type="button"
        class="ml-2 text-[#4b3e8f] underline underline-offset-2 hover:text-[#2f2568] dark:text-indigo-300 dark:hover:text-indigo-200"
        @click="$emit('clearRecipient')"
      >
        Clear
      </button>
    </p>

    <div
      v-if="mode === 'group' && selectedParticipants.length"
      class="flex flex-wrap gap-2 rounded-2xl bg-[#e8e3f2] px-3 py-3 dark:bg-slate-800"
    >
      <span
        v-for="participant in selectedParticipants"
        :key="participant.id"
        class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-[#4b3e8f] dark:bg-slate-700 dark:text-indigo-200"
      >
        {{ participant.name }}
        <button
          type="button"
          class="text-[#6a5ab5] transition hover:text-[#3c2f82] dark:text-indigo-300 dark:hover:text-indigo-100"
          @click="$emit('removeParticipant', participant.id)"
        >
          x
        </button>
      </span>
    </div>

    <BaseButton
      size="sm"
      variant="primary"
      :loading="creating"
      @click="$emit('submit')"
      class="w-full rounded-full! bg-[#3f34a6]! px-4! py-2.5! text-sm! font-semibold! hover:bg-[#352b91]! dark:bg-indigo-600! dark:hover:bg-indigo-500!"
    >
      {{ mode === 'group' ? 'Create group chat' : 'Start conversation' }}
    </BaseButton>
    <BaseAlert v-if="errorMessage" type="error" class="text-xs" :message="errorMessage" />
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'

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
  name: 'NewConversationForm',
  components: { BaseInput, BaseButton, BaseAlert },
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    recipientQuery: {
      type: String,
      default: '',
    },
    mode: {
      type: String as PropType<'direct' | 'group'>,
      default: 'direct',
    },
    groupSubject: {
      type: String,
      default: '',
    },
    selectedProjectId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    directDropdownValue: {
      type: Number as PropType<number | null>,
      default: null,
    },
    participantDropdownValue: {
      type: Number as PropType<number | null>,
      default: null,
    },
    projectOptions: {
      type: Array as PropType<ProjectOption[]>,
      default: () => [],
    },
    projectRequired: {
      type: Boolean,
      default: true,
    },
    selectedRecipient: {
      type: Object as PropType<RecipientOption | null>,
      default: null,
    },
    selectedParticipants: {
      type: Array as PropType<RecipientOption[]>,
      default: () => [],
    },
    recipientOptions: {
      type: Array as PropType<RecipientOption[]>,
      default: () => [],
    },
    searchingUsers: {
      type: Boolean,
      default: false,
    },
    errorMessage: {
      type: String,
      default: '',
    },
    creating: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    'update:recipientQuery',
    'update:mode',
    'update:groupSubject',
    'update:selectedProjectId',
    'update:directDropdownValue',
    'update:participantDropdownValue',
    'addParticipantFromDropdown',
    'selectRecipient',
    'removeParticipant',
    'clearRecipient',
    'submit',
  ],
  data() {
    return {
      projectDropdownOpen: false,
      projectSearchQuery: '',
      directDropdownOpen: false,
      groupDropdownOpen: false,
      groupSearchQuery: '',
    }
  },
  computed: {
    projectLabelText(): string {
      return this.projectRequired ? 'Project' : 'Project (optional)'
    },
    projectDropdownLabel(): string {
      const selectedId = Number(this.selectedProjectId ?? 0)
      if (!Number.isFinite(selectedId) || selectedId <= 0) {
        return this.projectRequired ? 'Select project' : 'No project selected'
      }

      const selected = this.projectOptions.find((project) => project.id === selectedId)
      if (!selected) {
        return 'Select project'
      }

      return selected.title
    },
    filteredProjectOptions(): ProjectOption[] {
      const q = this.projectSearchQuery.trim().toLowerCase()
      if (!q) {
        return this.projectOptions
      }

      return this.projectOptions.filter((project) =>
        String(project.title ?? '').toLowerCase().includes(q),
      )
    },
    directDropdownLabel(): string {
      if (this.selectedRecipient?.id) {
        return `${this.selectedRecipient.name} (${this.selectedRecipient.email})`
      }

      const selectedId = Number(this.directDropdownValue ?? 0)
      if (!Number.isFinite(selectedId) || selectedId <= 0) {
        return 'Select recipient'
      }

      const selected = this.recipientOptions.find((option) => option.id === selectedId)
      if (!selected) {
        return 'Select recipient'
      }

      return `${selected.name} (${selected.email})`
    },
    groupDropdownLabel(): string {
      const selectedId = Number(this.participantDropdownValue ?? 0)
      if (!Number.isFinite(selectedId) || selectedId <= 0) {
        return 'Add participant'
      }

      const selected = this.recipientOptions.find((option) => option.id === selectedId)
      if (!selected) {
        return 'Add participant'
      }

      return `${selected.name} (${selected.email})`
    },
    groupFilteredRecipientOptions(): RecipientOption[] {
      const q = this.groupSearchQuery.trim().toLowerCase()
      if (!q) {
        return this.recipientOptions
      }

      return this.recipientOptions.filter((option) => {
        const name = String(option.name ?? '').toLowerCase()
        const email = String(option.email ?? '').toLowerCase()
        return name.includes(q) || email.includes(q)
      })
    },
  },
  methods: {
    onSelectProjectOption(project: ProjectOption) {
      this.$emit('update:selectedProjectId', project.id)
      this.projectSearchQuery = ''
      this.projectDropdownOpen = false
    },
    onSelectDirectOption(option: RecipientOption) {
      this.$emit('update:directDropdownValue', option.id)
      this.$emit('selectRecipient', option)
      this.directDropdownOpen = false
    },
    onSelectGroupOption(option: RecipientOption) {
      this.$emit('update:participantDropdownValue', option.id)
      this.$emit('addParticipantFromDropdown', option.id)
      this.groupSearchQuery = ''
      this.groupDropdownOpen = false
    },
  },
})
</script>
