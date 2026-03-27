<template>
  <AppLayout>
    <div class="space-y-6">
      <h1 v-if="auth.isCompany" class="text-2xl font-bold text-gray-900 dark:text-slate-100">Project Applicants</h1>

      <div v-if="applicationStore.loading" class="space-y-3">
        <div v-for="n in 4" :key="n" class="h-28 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
      </div>

      <template v-else>
        <div v-if="auth.isCompany" class="grid items-stretch gap-6 lg:h-[calc(100vh-8rem)] lg:grid-cols-[320px_1fr]">
          <CompanyProjectList
            :projects="projectStore.projects"
            :selected-project-id="selectedProjectId"
            @select="selectProject"
          />

          <CompanyApplicantsPanel
            :selected-project="selectedProject"
            :applications="filteredApplications"
            :updating-id="updatingId"
            @update-status="handlePanelUpdateStatus"
          />
        </div>

        <div v-else>
          <StudentApplicationsList
            :applications="applicationStore.applications"
            :withdrawing-id="withdrawingId"
            @withdraw="handleWithdraw"
          />
        </div>

        <BasePagination
          v-if="auth.isStudent && pagination && pagination.last_page > 1"
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          @change="handlePageChange"
        />
      </template>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useApplicationStore } from '@/stores/application'
import { useProjectStore } from '@/stores/project'
import {
  type AppPagination,
  type ApplicationListItem,
  type CompanyProject,
  filterApplications,
  findProjectById,
  toPagination,
} from '@/services/applications/ApplicationsViewService'
import AppLayout from '@/layouts/AppLayout.vue'
import CompanyProjectList from '@/components/applications/CompanyProjectList.vue'
import CompanyApplicantsPanel from '@/components/applications/CompanyApplicantsPanel.vue'
import StudentApplicationsList from '@/components/applications/StudentApplicationsList.vue'
import BasePagination from '@/components/ui/BasePagination.vue'

export default defineComponent({
  name: 'ApplicationsView',
  components: {
    AppLayout,
    CompanyProjectList,
    CompanyApplicantsPanel,
    StudentApplicationsList,
    BasePagination,
  },
  setup() {
    return {
      auth: useAuthStore(),
      applicationStore: useApplicationStore(),
      projectStore: useProjectStore(),
    }
  },
  data() {
    return {
      selectedProjectId: null as number | null,
      withdrawingId: null as number | null,
      updatingId: null as number | null,
    }
  },
  computed: {
    pagination(): AppPagination | null {
      return toPagination(this.applicationStore.pagination as Partial<AppPagination> | null)
    },
    selectedProject(): CompanyProject | null {
      return findProjectById(this.projectStore.projects as CompanyProject[], this.selectedProjectId)
    },
    filteredApplications(): ApplicationListItem[] {
      return filterApplications(
        this.applicationStore.applications as ApplicationListItem[],
        'all',
        this.selectedProjectId,
        this.auth.isCompany,
      )
    },
  },
  async mounted() {
    if (this.auth.isCompany) {
      await this.loadCompanyProjectsAndApplicants()
      return
    }
    await this.applicationStore.fetchApplications()
  },
  methods: {
    ensureSelectedProject() {
      if (!this.auth.isCompany) return

      const projects = this.projectStore.projects as CompanyProject[]
      if (!projects.length) {
        this.selectedProjectId = null
        return
      }

      const selectedExists = projects.some((project) => project.id === this.selectedProjectId)
      if (!selectedExists) {
        this.selectedProjectId = projects[0]?.id ?? null
      }
    },
    handlePanelUpdateStatus(payload: { id: number; status: 'accepted' | 'rejected' }) {
      return this.handleUpdateStatus(payload.id, payload.status)
    },
    async loadCompanyProjectsAndApplicants() {
      await this.projectStore.fetchProjects({
        company_id: Number(this.auth.user?.id),
        per_page: 100,
      })

      await this.applicationStore.fetchApplications({
        per_page: 500,
      })
      this.ensureSelectedProject()
    },
    selectProject(projectId: number) {
      this.selectedProjectId = projectId
    },
    async handlePageChange(page: number) {
      await this.applicationStore.fetchApplications({
        page,
      })
    },
    async handleWithdraw(id: number) {
      if (!confirm('Withdraw this application?')) return
      this.withdrawingId = id
      try {
        await this.applicationStore.withdraw(id)
      } finally {
        this.withdrawingId = null
      }
    },
    async handleUpdateStatus(id: number, status: 'accepted' | 'rejected') {
      this.updatingId = id
      try {
        await this.applicationStore.updateStatus(id, status)
        if (this.auth.isCompany) {
          await this.projectStore.fetchProjects({
            company_id: Number(this.auth.user?.id),
            per_page: 100,
          })
          this.ensureSelectedProject()
        }
      } finally {
        this.updatingId = null
      }
    },
  },
})
</script>
