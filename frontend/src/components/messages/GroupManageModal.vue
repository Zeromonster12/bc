<template>
  <div
    v-if="visible"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-lg rounded-3xl border border-white/90 bg-white p-6 shadow-[0_10px_30px_rgba(30,27,53,0.12)] dark:border-slate-700 dark:bg-slate-900 dark:shadow-[0_10px_30px_rgba(2,6,23,0.45)]">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-xl font-semibold text-[#1d1f31] dark:text-slate-100">{{ title }}</h3>
          <p class="mt-1 text-sm font-medium text-[#7d8195] dark:text-slate-400">{{ subtitle }}</p>
        </div>
        <button
          class="rounded-xl p-2 text-[#686d84] transition hover:bg-[#f8f7fc] hover:text-[#474a61] dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
          @click="$emit('close')"
        >
          x
        </button>
      </div>

      <div v-if="mode === 'rename'" class="space-y-3">
        <input
          :value="editName"
          type="text"
          maxlength="160"
          placeholder="Group name"
          class="w-full rounded-2xl border border-[#ebeaf2] bg-[#f8f7fc] px-3 py-2.5 text-sm font-medium text-[#474a61] placeholder:text-[#a3a7bb] focus:border-[#4b35cb] focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-indigo-400 dark:focus:bg-slate-900"
          @input="$emit('update:editName', ($event.target as HTMLInputElement).value)"
        />
        <div class="flex justify-end gap-2">
          <button class="rounded-2xl border border-[#ebeaf2] bg-white px-4 py-2 text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="$emit('close')">Cancel</button>
          <button
            :disabled="submitting || !editName.trim()"
            class="rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-5 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] transition hover:brightness-105 disabled:opacity-50"
            @click="$emit('save-name')"
          >
            Save name
          </button>
        </div>
      </div>

      <div v-else-if="mode === 'avatar'" class="space-y-3">
        <div class="flex items-center gap-3">
          <div class="h-16 w-16 overflow-hidden rounded-full border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800">
            <img v-if="avatarPreview" :src="avatarPreview" alt="Group avatar preview" class="h-full w-full object-cover" />
            <div v-else class="flex h-full w-full items-center justify-center text-xs font-semibold text-slate-500 dark:text-slate-400">No photo</div>
          </div>
          <input type="file" accept="image/*" @change="$emit('avatar-selected', $event)" class="block text-xs text-slate-500 dark:text-slate-300" />
        </div>
        <div class="flex justify-between gap-2">
          <button
            :disabled="submitting"
            class="rounded-2xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-500/50 dark:bg-slate-800 dark:text-rose-400"
            @click="$emit('remove-avatar')"
          >
            Remove photo
          </button>
          <div class="flex gap-2">
            <button class="rounded-2xl border border-[#ebeaf2] bg-white px-4 py-2 text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="$emit('close')">Cancel</button>
            <button
              :disabled="submitting || !hasAvatarFile"
              class="rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-5 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] transition hover:brightness-105 disabled:opacity-50"
              @click="$emit('save-avatar')"
            >
              Upload photo
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="mode === 'participants'" class="space-y-3">
        <div class="max-h-52 space-y-2 overflow-y-auto rounded-2xl border border-[#ebeaf2] bg-[#f8f7fc] p-2 dark:border-slate-700 dark:bg-slate-800/40">
          <div
            v-for="participant in participants"
            :key="Number(participant.id ?? 0)"
            class="flex items-center justify-between gap-2 rounded-xl border border-[#ebeaf2] bg-white px-2.5 py-2 dark:border-slate-700 dark:bg-slate-900"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-[#474a61] dark:text-slate-100">
                {{ participant.name || 'Unknown user' }}
              </p>
              <p v-if="participant.is_admin" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">Admin</p>
            </div>
            <div class="flex items-center gap-1">
              <button
                v-if="!participant.is_admin"
                :disabled="submitting || !canManageCurrentGroup"
                class="rounded-xl border border-[#ebeaf2] bg-white px-2.5 py-1 text-[11px] font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                @click="$emit('promote-admin', Number(participant.id ?? 0))"
              >
                Make admin
              </button>
              <button
                v-else
                :disabled="submitting || !canDemoteParticipant(Number(participant.id ?? 0))"
                class="rounded-xl border border-rose-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-500/40 dark:bg-slate-800 dark:text-rose-400"
                @click="$emit('demote-admin', Number(participant.id ?? 0))"
              >
                Remove admin
              </button>
              <button
                :disabled="submitting || !canManageCurrentGroup || Number(participant.id ?? 0) <= 0"
                class="rounded-xl border border-rose-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-500/40 dark:bg-slate-800 dark:text-rose-400"
                @click="$emit('remove-user', Number(participant.id ?? 0))"
              >
                Remove user
              </button>
            </div>
          </div>
        </div>

        <select
          :value="addUserId ?? ''"
          class="w-full rounded-2xl border border-[#ebeaf2] bg-[#f8f7fc] px-3 py-2.5 text-sm font-medium text-[#474a61] focus:border-[#4b35cb] focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-indigo-400 dark:focus:bg-slate-900"
          @change="onAddUserChange"
        >
          <option value="">Select user</option>
          <option v-for="candidate in addCandidates" :key="candidate.id" :value="candidate.id">
            {{ candidate.name }} ({{ candidate.email }})
          </option>
        </select>
        <div class="flex justify-end gap-2">
          <button class="rounded-2xl border border-[#ebeaf2] bg-white px-4 py-2 text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="$emit('close')">Close</button>
          <button
            :disabled="submitting || !addUserId"
            class="rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-5 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] transition hover:brightness-105 disabled:opacity-50"
            @click="$emit('add-user')"
          >
            Add user
          </button>
        </div>
      </div>

      <div v-else class="space-y-3">
        <p class="text-sm text-slate-700 dark:text-slate-200">This will permanently delete this group chat for all participants.</p>
        <div class="flex justify-end gap-2">
          <button class="rounded-2xl border border-[#ebeaf2] bg-white px-4 py-2 text-sm font-semibold text-[#474a61] transition hover:bg-[#f8f7fc] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="$emit('close')">Cancel</button>
          <button
            :disabled="submitting"
            class="rounded-full bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(225,29,72,0.25)] transition hover:brightness-105 disabled:opacity-50"
            @click="$emit('delete-group')"
          >
            Delete group
          </button>
        </div>
      </div>

      <p v-if="errorMessage" class="mt-3 text-xs font-medium text-rose-600 dark:text-rose-400">{{ errorMessage }}</p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'

interface GroupParticipant {
  id?: number
  name?: string
  is_admin?: boolean
}

interface GroupCandidate {
  id: number
  name: string
  email: string
}

export default defineComponent({
  name: 'GroupManageModal',
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    mode: {
      type: String as PropType<'rename' | 'avatar' | 'participants' | 'delete'>,
      default: 'rename',
    },
    title: {
      type: String,
      default: '',
    },
    subtitle: {
      type: String,
      default: '',
    },
    submitting: {
      type: Boolean,
      default: false,
    },
    errorMessage: {
      type: String,
      default: '',
    },
    editName: {
      type: String,
      default: '',
    },
    avatarPreview: {
      type: String,
      default: '',
    },
    hasAvatarFile: {
      type: Boolean,
      default: false,
    },
    participants: {
      type: Array as PropType<GroupParticipant[]>,
      default: () => [],
    },
    canManageCurrentGroup: {
      type: Boolean,
      default: false,
    },
    canDemoteParticipant: {
      type: Function as PropType<(participantUserId: number) => boolean>,
      required: true,
    },
    addCandidates: {
      type: Array as PropType<GroupCandidate[]>,
      default: () => [],
    },
    addUserId: {
      type: Number as PropType<number | null>,
      default: null,
    },
  },
  emits: [
    'close',
    'update:editName',
    'save-name',
    'avatar-selected',
    'remove-avatar',
    'save-avatar',
    'promote-admin',
    'demote-admin',
    'remove-user',
    'update:addUserId',
    'add-user',
    'delete-group',
  ],
  methods: {
    onAddUserChange(event: Event) {
      const value = Number((event.target as HTMLSelectElement).value ?? 0)
      this.$emit('update:addUserId', Number.isFinite(value) && value > 0 ? value : null)
    },
  },
})
</script>
