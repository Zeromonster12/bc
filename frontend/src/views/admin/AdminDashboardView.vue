<template>
  <AppLayout>
    <div class="space-y-8">
      <section class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-[#f8f6ff] via-[#f4f0ff] to-[#eef5ff] p-6 shadow-[0_20px_45px_rgba(30,27,53,0.08)] dark:border-slate-700/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 dark:shadow-[0_20px_45px_rgba(2,6,23,0.45)] sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b35cb] dark:text-indigo-300">Administration</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl">Admin Control Center</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
              Review users, company verifications and projects in one moderation workspace.
            </p>
          </div>
          <span class="inline-flex rounded-full bg-white/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-[#4b35cb] dark:bg-slate-800/90 dark:text-indigo-300">
            Active: {{ activeTab === 'users' ? 'Users' : 'Projects' }}
          </span>
        </div>
      </section>

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
        @update:companyStatusFilter="handleCompanyStatusFilter"
        :company-status-filter="userCompanyStatusFilter"
        @change-role="changeRoleFromEvent"
        @approve-company="approveCompany"
        @reject-company="rejectCompany"
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
  company_verification_status?: 'pending' | 'approved' | 'rejected'
  company_verified_at?: string | null
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
      userCompanyStatusFilter: '',
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
    handleCompanyStatusFilter(value: string) {
      this.userCompanyStatusFilter = value
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
          const params = buildAdminUsersParams(
            page,
            this.userSearch,
            this.userRoleFilter,
            this.userCompanyStatusFilter,
          )
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
    async approveCompany(userId: number) {
      const result = await AdminService.approveCompany(userId)
      const user = this.users.find((u) => u.id === userId)
      if (user && result?.data) {
        Object.assign(user, result.data)
      }
    },
    async rejectCompany(userId: number) {
      const result = await AdminService.rejectCompany(userId)
      const user = this.users.find((u) => u.id === userId)
      if (user && result?.data) {
        Object.assign(user, result.data)
      }
    },
    async deleteProject(projectId: number) {
      if (!confirm('Permanently delete this project?')) return
      await AdminService.deleteProject(projectId)
      this.adminProjects = filterOutById(this.adminProjects, projectId)
    },
  },
})
</script>
