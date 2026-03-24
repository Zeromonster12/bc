<template>
  <AppLayout>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
        <span v-if="auth.isStudent">My Applications</span>
        <span v-else>Project Applicants</span>
      </h1>

      <ApplicationStatusFilters
        :active-status="activeStatus"
        :options="statusOptions"
        @change="setStatusFilter"
      />

      <div v-if="applicationStore.loading" class="space-y-3">
        <div v-for="n in 4" :key="n" class="h-28 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
      </div>

      <template v-else>
        <div v-if="auth.isCompany" class="grid gap-6 lg:grid-cols-[320px_1fr]">
          <CompanyProjectList
            :projects="projectStore.projects"
            :selected-project-id="selectedProjectId"
            @select="selectProject"
          />

          <CompanyApplicantsPanel
            :selected-project="selectedProject"
            :applications="filteredApplications"
            :updating-id="updatingId"
            :updating-status="updatingStatus"
            :creating-task-id="creatingTaskId"
            @update-status="handlePanelUpdateStatus"
            @create-task="handleCreateTask"
          />
        </div>

        <div v-else>
          <StudentApplicationsList
            :applications="filteredApplications"
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
  buildCompanyApplicationsParams,
  filterApplications,
  findProjectById,
  toPagination,
} from '@/services/applications/ApplicationsViewService'
import ApplicationService from '@/services/applications/ApplicationService'
import AppLayout from '@/layouts/AppLayout.vue'
import ApplicationStatusFilters from '@/components/applications/ApplicationStatusFilters.vue'
import CompanyProjectList from '@/components/applications/CompanyProjectList.vue'
import CompanyApplicantsPanel from '@/components/applications/CompanyApplicantsPanel.vue'
import StudentApplicationsList from '@/components/applications/StudentApplicationsList.vue'
import BasePagination from '@/components/ui/BasePagination.vue'

export default defineComponent({
  name: 'ApplicationsView',
  components: {
    AppLayout,
    ApplicationStatusFilters,
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
      activeStatus: 'all',
      selectedProjectId: null as number | null,
      withdrawingId: null as number | null,
      updatingId: null as number | null,
      updatingStatus: '',
      creatingTaskId: null as number | null,
      statusOptions: [
        { value: 'all', label: 'All' },
        { value: 'pending', label: 'Pending' },
        { value: 'accepted', label: 'Accepted' },
        { value: 'rejected', label: 'Rejected' },
        { value: 'withdrawn', label: 'Withdrawn' },
      ],
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
        this.activeStatus,
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
    handlePanelUpdateStatus(payload: { id: number; status: 'accepted' | 'rejected' }) {
      return this.handleUpdateStatus(payload.id, payload.status)
    },
    async loadCompanyProjectsAndApplicants() {
      await this.projectStore.fetchProjects({
        company_id: Number(this.auth.user?.id),
        per_page: 100,
      })

      const firstProject = this.projectStore.projects[0]
      if (firstProject) {
        this.selectedProjectId = firstProject.id
        await this.fetchCompanyApplicationsForSelectedProject(1)
      }
    },
    async fetchCompanyApplicationsForSelectedProject(page: number) {
      const params = buildCompanyApplicationsParams(this.selectedProjectId, this.activeStatus, page)
      if (!params) return
      await this.applicationStore.fetchApplications(params)
    },
    async selectProject(projectId: number) {
      this.selectedProjectId = projectId
      await this.fetchCompanyApplicationsForSelectedProject(1)
    },
    async setStatusFilter(status: string) {
      this.activeStatus = status
      if (this.auth.isCompany) {
        await this.fetchCompanyApplicationsForSelectedProject(1)
      }
    },
    async handlePageChange(page: number) {
      await this.applicationStore.fetchApplications({
        page,
        status: this.activeStatus === 'all' ? undefined : this.activeStatus,
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
      this.updatingStatus = status
      try {
        await this.applicationStore.updateStatus(id, status)
        if (this.auth.isCompany) {
          await this.fetchCompanyApplicationsForSelectedProject(1)
          await this.projectStore.fetchProjects({
            company_id: Number(this.auth.user?.id),
            per_page: 100,
          })
        }
      } finally {
        this.updatingId = null
        this.updatingStatus = ''
      }
    },
    async handleCreateTask(payload: {
      applicationId: number
      title: string
      priority: 'low' | 'medium' | 'high' | 'urgent'
      requirements?: string
    }) {
      this.creatingTaskId = payload.applicationId
      try {
        await ApplicationService.createTask(payload.applicationId, {
          title: payload.title,
          priority: payload.priority,
          requirements: payload.requirements,
        })

        if (this.auth.isCompany) {
          await this.fetchCompanyApplicationsForSelectedProject(1)
        }
      } finally {
        this.creatingTaskId = null
      }
    },
  },
})
</script>
