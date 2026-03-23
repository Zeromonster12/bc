<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-3 items-center">
      <input
        :value="search"
        type="text"
        placeholder="Search by name or email..."
        class="border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
      />
      <select
        :value="roleFilter"
        class="border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @change="$emit('update:roleFilter', ($event.target as HTMLSelectElement).value)"
      >
        <option value="">All roles</option>
        <option value="student">Student</option>
        <option value="company">Company</option>
        <option value="admin">Admin</option>
      </select>
    </div>

    <div v-if="loading" class="space-y-2">
      <div v-for="n in 5" :key="n" class="h-12 bg-gray-100 rounded-xl animate-pulse" />
    </div>

    <div v-else class="bg-white border border-gray-200 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">Verified</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="users.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-gray-400">No users found.</td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="capitalize px-2 py-0.5 bg-gray-100 rounded text-xs">{{
                user.role
              }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="user.email_verified_at ? 'text-green-600' : 'text-gray-400'"
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
                  class="text-xs border border-gray-200 rounded px-2 py-1"
                >
                  <option value="student">student</option>
                  <option value="company">company</option>
                  <option value="admin">admin</option>
                </select>
                <button
                  @click="$emit('delete-user', user.id)"
                  class="text-xs text-red-600 hover:text-red-800"
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
