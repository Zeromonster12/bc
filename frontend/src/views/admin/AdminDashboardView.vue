<template>
  <AppLayout>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>

      <AdminTabNav :tabs="tabs" :active-tab="activeTab" @change="activeTab = $event" />

      <AdminUsersPanel
        v-if="activeTab === 'users'"
        :users="users"
        :loading="usersLoading"
        :search="userSearch"
        :role-filter="userRoleFilter"
        :pagination="userPagination"
        @update:search="handleUserSearch"
        @update:roleFilter="handleUserRoleFilter"
        @change-role="changeRoleFromEvent"
        @delete-user="deleteUser"
        @page-change="fetchUsers"
      />

      <AdminProjectsPanel
        v-if="activeTab === 'projects'"
        :projects="adminProjects"
        :loading="projectsLoading"
        :pagination="projectPagination"
        @delete-project="deleteProject"
        @page-change="fetchAdminProjects"
      />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import AdminService from '@/services/admin/AdminService'
import { buildAdminUsersParams, filterOutById } from '@/services/admin/AdminDashboardViewService'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminTabNav from '@/components/admin/AdminTabNav.vue'
import AdminUsersPanel from '@/components/admin/AdminUsersPanel.vue'
import AdminProjectsPanel from '@/components/admin/AdminProjectsPanel.vue'

interface AdminUser {
  id: number
  role: string
  [key: string]: unknown
}

interface AdminProject {
  id: number
  [key: string]: unknown
}

interface AdminPagination {
  current_page: number
  last_page: number
  total: number
}

export default defineComponent({
  name: 'AdminDashboardView',
  components: { AppLayout, AdminTabNav, AdminUsersPanel, AdminProjectsPanel },
  data() {
    return {
      activeTab: 'users',
      tabs: [
        { id: 'users', label: 'Users' },
        { id: 'projects', label: 'Projects' },
      ],
      // Users
      users: [] as AdminUser[],
      userSearch: '',
      userRoleFilter: '',
      userPagination: null as AdminPagination | null,
      usersLoading: false,
      // Projects
      adminProjects: [] as AdminProject[],
      projectPagination: null as AdminPagination | null,
      projectsLoading: false,
      searchTimeout: undefined as ReturnType<typeof setTimeout> | undefined,
    }
  },
  watch: {
    activeTab(tab: string) {
      if (tab === 'users') this.fetchUsers(1)
      if (tab === 'projects') this.fetchAdminProjects(1)
    },
  },
  mounted() {
    this.fetchUsers(1)
  },
  methods: {
    handleUserSearch(value: string) {
      this.userSearch = value
      this.fetchUsers(1)
    },
    handleUserRoleFilter(value: string) {
      this.userRoleFilter = value
      this.fetchUsers(1)
    },
    changeRoleFromEvent(payload: { id: number; role: string }) {
      return this.changeRole(payload.id, payload.role)
    },
    async fetchUsers(page = 1) {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(async () => {
        this.usersLoading = true
        try {
          const params = buildAdminUsersParams(page, this.userSearch, this.userRoleFilter)
          const result = await AdminService.getUsers(params)
          this.users = result.data
          this.userPagination = result.meta
        } finally {
          this.usersLoading = false
        }
      }, 300)
    },
    async fetchAdminProjects(page = 1) {
      this.projectsLoading = true
      try {
        const result = await AdminService.getProjects({ page })
        this.adminProjects = result.data
        this.projectPagination = result.meta
      } finally {
        this.projectsLoading = false
      }
    },
    async changeRole(userId: number, role: string) {
      await AdminService.updateUserRole(userId, role)
      const user = this.users.find((u) => u.id === userId)
      if (user) user.role = role
    },
    async deleteUser(userId: number) {
      if (!confirm('Delete this user? This cannot be undone.')) return
      await AdminService.deleteUser(userId)
      this.users = filterOutById(this.users, userId)
    },
    async deleteProject(projectId: number) {
      if (!confirm('Permanently delete this project?')) return
      await AdminService.deleteProject(projectId)
      this.adminProjects = filterOutById(this.adminProjects, projectId)
    },
  },
})
</script>
