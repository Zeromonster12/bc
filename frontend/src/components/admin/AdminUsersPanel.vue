<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center gap-3">
      <input
        :value="search"
        type="text"
        placeholder="Search by name or email..."
        class="rounded-full bg-[#e8e3f2] px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
        @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
      />
      <div class="relative admin-users-dropdown">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full bg-[#e8e3f2] px-4 py-2.5 text-sm font-medium text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:focus:ring-indigo-400/30"
          @click="toggleRoleFilterDropdown"
        >
          {{ roleFilterLabel }}
          <ChevronDown class="h-4 w-4 transition" :class="roleFilterDropdownOpen ? 'rotate-180' : ''" />
        </button>
        <div
          v-if="roleFilterDropdownOpen"
          class="absolute z-20 mt-1 min-w-44 rounded-2xl bg-white p-1.5 dark:bg-slate-900"
        >
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setRoleFilter('')">All roles</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setRoleFilter('student')">Student</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setRoleFilter('company')">Company</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setRoleFilter('admin')">Admin</button>
        </div>
      </div>

      <div class="relative admin-users-dropdown">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full bg-[#e8e3f2] px-4 py-2.5 text-sm font-medium text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:focus:ring-indigo-400/30"
          @click="toggleCompanyStatusDropdown"
        >
          {{ companyStatusFilterLabel }}
          <ChevronDown class="h-4 w-4 transition" :class="companyStatusDropdownOpen ? 'rotate-180' : ''" />
        </button>
        <div
          v-if="companyStatusDropdownOpen"
          class="absolute z-20 mt-1 min-w-52 rounded-2xl bg-white p-1.5 dark:bg-slate-900"
        >
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('')">All company statuses</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('pending')">Pending</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('approved')">Approved</button>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('rejected')">Rejected</button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="space-y-2">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-3xl bg-[#f1edf8] dark:bg-slate-800" />
    </div>

    <div
      v-else
      class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900/90"
    >
      <div class="overflow-x-auto">
        <table class="min-w-190 w-full text-sm">
        <thead class="bg-[#f1edf8] text-xs font-semibold uppercase tracking-wide text-[#6b6682] dark:bg-slate-800/80 dark:text-slate-400">
          <tr>
            <th class="px-5 py-4 text-left">Name</th>
            <th class="px-5 py-4 text-left">Email</th>
            <th class="px-5 py-4 text-left">Role</th>
            <th class="px-5 py-4 text-left">Company status</th>
            <th class="px-5 py-4 text-left">Verified</th>
            <th class="px-5 py-4 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#ece8f4] dark:divide-slate-700/60">
          <tr v-if="users.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-slate-400 dark:text-slate-500">No users found.</td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-[#f7f4fc] dark:hover:bg-slate-800/70">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#ddd7ef] text-xs font-bold text-[#4d466b] dark:bg-slate-700 dark:text-slate-200">
                  <img
                    v-if="userAvatarUrl(user)"
                    :src="userAvatarUrl(user)"
                    :alt="`${user.name ?? 'User'} avatar`"
                    class="h-full w-full object-cover"
                  >
                  <span v-else>{{ userInitials(user.name) }}</span>
                </div>

                <div class="min-w-0">
                  <RouterLink
                    v-if="user.role === 'student'"
                    :to="{ name: 'students.profile', params: { id: user.id } }"
                    class="block truncate font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200"
                  >
                    {{ user.name }}
                  </RouterLink>
                  <RouterLink
                    v-else-if="user.role === 'company'"
                    :to="{ name: 'companies.profile', params: { id: user.id } }"
                    class="block truncate font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200"
                  >
                    {{ user.name }}
                  </RouterLink>
                  <span v-else class="block truncate font-medium text-slate-900 dark:text-slate-100">{{ user.name }}</span>
                  <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">ID {{ user.id }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-[#ece8f4] px-2.5 py-0.5 text-xs font-medium capitalize text-[#5b5676] dark:bg-slate-700 dark:text-slate-200">{{
                user.role
              }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="companyStatusClass(user)"
                class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
              >
                {{ user.role === 'company' ? user.company_verification_status ?? 'pending' : 'n/a' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <CheckCircle2 v-if="user.email_verified_at" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
              <XCircle v-else class="h-5 w-5 text-rose-600 dark:text-rose-400" />
            </td>
            <td class="px-4 py-3">
              <div class="relative flex flex-wrap items-center gap-2 admin-users-dropdown">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-full bg-[#e8e3f2] px-3 py-1.5 text-xs font-semibold text-[#4d466b] dark:bg-slate-800 dark:text-slate-200"
                  @click="toggleRoleDropdown(user.id)"
                >
                  {{ user.role }}
                  <ChevronDown class="h-3.5 w-3.5 transition" :class="roleDropdownUserId === user.id ? 'rotate-180' : ''" />
                </button>
                <div
                  v-if="roleDropdownUserId === user.id"
                  class="absolute left-0 top-[calc(100%+4px)] z-20 min-w-28 rounded-2xl bg-white p-1.5 dark:bg-slate-900"
                >
                  <button type="button" class="block w-full rounded-xl px-2 py-1.5 text-left text-xs font-medium capitalize text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="changeRole(user.id, 'student')">student</button>
                  <button type="button" class="block w-full rounded-xl px-2 py-1.5 text-left text-xs font-medium capitalize text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="changeRole(user.id, 'company')">company</button>
                  <button type="button" class="block w-full rounded-xl px-2 py-1.5 text-left text-xs font-medium capitalize text-slate-700 hover:bg-[#f1edf8] dark:text-slate-200 dark:hover:bg-slate-800" @click="changeRole(user.id, 'admin')">admin</button>
                </div>
                <button
                  @click="$emit('delete-user', user.id)"
                  class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/30"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                  Delete
                </button>
                <button
                  v-if="user.role === 'company' && user.company_verification_status !== 'approved'"
                  @click="$emit('approve-company', user.id)"
                  class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/30"
                >
                  <CheckCircle2 class="h-3.5 w-3.5" />
                  Approve
                </button>
                <button
                  v-if="user.role === 'company' && user.company_verification_status !== 'rejected'"
                  @click="$emit('reject-company', user.id)"
                  class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-200 dark:bg-red-500/20 dark:text-red-300 dark:hover:bg-red-500/30"
                >
                  <XCircle class="h-3.5 w-3.5" />
                  Reject
                </button>
              </div>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    <BasePagination
      v-if="pagination && pagination.last_page > 1"
      variant="company-applications"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :total="pagination.total"
      @change="$emit('page-change', $event)"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { CheckCircle2, ChevronDown, Trash2, XCircle } from 'lucide-vue-next'
import BasePagination from '@/components/ui/BasePagination.vue'
import { resolveAssetUrl } from '@/services/core/url'

interface Pagination {
  current_page: number
  last_page: number
  total: number
}

interface AdminUserRow {
  id: number
  name?: string
  email?: string
  role?: string
  avatar_url?: string | null
  email_verified_at?: string | null
  company_verification_status?: 'pending' | 'approved' | 'rejected'
}

export default defineComponent({
  name: 'AdminUsersPanel',
  components: { BasePagination, CheckCircle2, ChevronDown, Trash2, XCircle },
  props: {
    users: {
      type: Array as PropType<AdminUserRow[]>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    search: {
      type: String,
      default: '',
    },
    roleFilter: {
      type: String,
      default: '',
    },
    companyStatusFilter: {
      type: String,
      default: '',
    },
    pagination: {
      type: Object as PropType<Pagination | null>,
      default: null,
    },
  },
  emits: [
    'update:search',
    'update:roleFilter',
    'update:companyStatusFilter',
    'change-role',
    'delete-user',
    'approve-company',
    'reject-company',
    'page-change',
  ],
  methods: {
    closeAllDropdowns() {
      this.roleFilterDropdownOpen = false
      this.companyStatusDropdownOpen = false
      this.roleDropdownUserId = null
    },
    toggleRoleFilterDropdown() {
      const next = !this.roleFilterDropdownOpen
      this.closeAllDropdowns()
      this.roleFilterDropdownOpen = next
    },
    toggleCompanyStatusDropdown() {
      const next = !this.companyStatusDropdownOpen
      this.closeAllDropdowns()
      this.companyStatusDropdownOpen = next
    },
    toggleRoleDropdown(userId: number) {
      const next = this.roleDropdownUserId !== userId
      this.closeAllDropdowns()
      this.roleDropdownUserId = next ? userId : null
    },
    onDocumentClick(event: Event) {
      const target = event.target as HTMLElement | null
      if (target?.closest('.admin-users-dropdown')) return
      this.closeAllDropdowns()
    },
    userAvatarUrl(user: AdminUserRow): string {
      return resolveAssetUrl(user.avatar_url)
    },
    userInitials(name?: string): string {
      const safe = String(name ?? '').trim()
      if (!safe) return 'U'

      return safe
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
    },
    setRoleFilter(value: string) {
      this.closeAllDropdowns()
      this.$emit('update:roleFilter', value)
    },
    setCompanyStatusFilter(value: string) {
      this.closeAllDropdowns()
      this.$emit('update:companyStatusFilter', value)
    },
    changeRole(userId: number, role: string) {
      this.closeAllDropdowns()
      this.$emit('change-role', { id: userId, role })
    },
    companyStatusClass(user: AdminUserRow): string {
      if (user.role !== 'company') {
        return 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-300'
      }

      if (user.company_verification_status === 'approved') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
      }

      if (user.company_verification_status === 'rejected') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
      }

      return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
    },
  },
  data() {
    return {
      roleFilterDropdownOpen: false,
      companyStatusDropdownOpen: false,
      roleDropdownUserId: null as number | null,
    }
  },
  mounted() {
    document.addEventListener('click', this.onDocumentClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onDocumentClick)
  },
  computed: {
    roleFilterLabel(): string {
      if (!this.roleFilter) return 'All roles'
      return this.roleFilter.charAt(0).toUpperCase() + this.roleFilter.slice(1)
    },
    companyStatusFilterLabel(): string {
      if (!this.companyStatusFilter) return 'All company statuses'
      return this.companyStatusFilter.charAt(0).toUpperCase() + this.companyStatusFilter.slice(1)
    },
  },
})
</script>
