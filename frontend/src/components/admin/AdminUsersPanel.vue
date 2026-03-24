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
      <select
        :value="roleFilter"
        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        @change="$emit('update:roleFilter', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">All roles</option>
        <option value="student">Student</option>
        <option value="company">Company</option>
        <option value="admin">Admin</option>
      </select>
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
            <th class="px-4 py-3 text-left">Verified</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
          <tr v-if="users.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-slate-500">No users found.</td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/70">
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-100">{{ user.name }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="rounded bg-gray-100 px-2 py-0.5 text-xs capitalize dark:bg-slate-700 dark:text-slate-200">{{
                user.role
              }}</span>
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
              <div class="flex gap-2">
                <select
                  :value="user.role"
                  @change="
                    $emit('change-role', {
                      id: user.id,
                      role: ($event.target as HTMLSelectElement).value,
                    })
                  "
                  class="rounded border border-gray-200 bg-white px-2 py-1 text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                >
                  <option value="student">student</option>
                  <option value="company">company</option>
                  <option value="admin">admin</option>
                </select>
                <button
                  @click="$emit('delete-user', user.id)"
                  class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                >
                  Delete
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
    pagination: {
      type: Object as PropType<Pagination | null>,
      default: null,
    },
  },
  emits: ['update:search', 'update:roleFilter', 'change-role', 'delete-user', 'page-change'],
})
</script>
