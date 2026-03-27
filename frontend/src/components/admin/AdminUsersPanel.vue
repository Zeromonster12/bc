<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-3 items-center">
      <input
        :value="search"
        type="text"
        placeholder="Search by name or email..."
        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
        @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
      />
      <div class="relative">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          @click="roleFilterDropdownOpen = !roleFilterDropdownOpen"
        >
          {{ roleFilterLabel }}
          <span class="text-xs">v</span>
        </button>
        <div
          v-if="roleFilterDropdownOpen"
          class="absolute z-20 mt-1 min-w-44 rounded-xl border border-gray-200 bg-white p-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
        >
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setRoleFilter('')">All roles</button>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setRoleFilter('student')">Student</button>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setRoleFilter('company')">Company</button>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setRoleFilter('admin')">Admin</button>
        </div>
      </div>

      <div class="relative">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          @click="companyStatusDropdownOpen = !companyStatusDropdownOpen"
        >
          {{ companyStatusFilterLabel }}
          <span class="text-xs">v</span>
        </button>
        <div
          v-if="companyStatusDropdownOpen"
          class="absolute z-20 mt-1 min-w-52 rounded-xl border border-gray-200 bg-white p-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
        >
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('')">All company statuses</button>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('pending')">Pending</button>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('approved')">Approved</button>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50 dark:hover:bg-slate-800" @click="setCompanyStatusFilter('rejected')">Rejected</button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="space-y-2">
      <div v-for="n in 5" :key="n" class="h-12 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
    </div>

    <div
      v-else
      class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-700/70 dark:bg-slate-900/90"
    >
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">Company status</th>
            <th class="px-4 py-3 text-left">Verified</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
          <tr v-if="users.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-slate-500">No users found.</td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/70">
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-100">
              <RouterLink
                v-if="user.role === 'student'"
                :to="{ name: 'students.profile', params: { id: user.id } }"
                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200"
              >
                {{ user.name }}
              </RouterLink>
              <RouterLink
                v-else-if="user.role === 'company'"
                :to="{ name: 'companies.profile', params: { id: user.id } }"
                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200"
              >
                {{ user.name }}
              </RouterLink>
              <span v-else>{{ user.name }}</span>
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="rounded bg-gray-100 px-2 py-0.5 text-xs capitalize dark:bg-slate-700 dark:text-slate-200">{{
                user.role
              }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="companyStatusClass(user)"
                class="rounded px-2 py-0.5 text-xs capitalize"
              >
                {{ user.role === 'company' ? user.company_verification_status ?? 'pending' : 'n/a' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="user.email_verified_at ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-slate-500'"
                class="text-xs"
              >
                {{ user.email_verified_at ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="relative flex gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded border border-gray-200 bg-white px-2 py-1 text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                  @click="roleDropdownUserId = roleDropdownUserId === user.id ? null : user.id"
                >
                  {{ user.role }}
                  <span class="text-[10px]">v</span>
                </button>
                <div
                  v-if="roleDropdownUserId === user.id"
                  class="absolute left-0 top-[calc(100%+4px)] z-20 min-w-28 rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
                >
                  <button type="button" class="block w-full rounded px-2 py-1 text-left text-xs capitalize hover:bg-gray-50 dark:hover:bg-slate-800" @click="changeRole(user.id, 'student')">student</button>
                  <button type="button" class="block w-full rounded px-2 py-1 text-left text-xs capitalize hover:bg-gray-50 dark:hover:bg-slate-800" @click="changeRole(user.id, 'company')">company</button>
                  <button type="button" class="block w-full rounded px-2 py-1 text-left text-xs capitalize hover:bg-gray-50 dark:hover:bg-slate-800" @click="changeRole(user.id, 'admin')">admin</button>
                </div>
                <button
                  @click="$emit('delete-user', user.id)"
                  class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                >
                  Delete
                </button>
                <button
                  v-if="user.role === 'company' && user.company_verification_status !== 'approved'"
                  @click="$emit('approve-company', user.id)"
                  class="text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                  Approve
                </button>
                <button
                  v-if="user.role === 'company' && user.company_verification_status !== 'rejected'"
                  @click="$emit('reject-company', user.id)"
                  class="text-xs text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                >
                  Reject
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      v-if="pagination && pagination.last_page > 1"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :total="pagination.total"
      @change="$emit('page-change', $event)"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BasePagination from '@/components/ui/BasePagination.vue'

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
  email_verified_at?: string | null
  company_verification_status?: 'pending' | 'approved' | 'rejected'
}

export default defineComponent({
  name: 'AdminUsersPanel',
  components: { BasePagination },
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
    setRoleFilter(value: string) {
      this.roleFilterDropdownOpen = false
      this.$emit('update:roleFilter', value)
    },
    setCompanyStatusFilter(value: string) {
      this.companyStatusDropdownOpen = false
      this.$emit('update:companyStatusFilter', value)
    },
    changeRole(userId: number, role: string) {
      this.roleDropdownUserId = null
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
