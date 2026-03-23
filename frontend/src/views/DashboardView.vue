<template>
  <AppLayout>
    <div class="space-y-8">
      <DashboardHero :user-name="auth.user?.name ?? 'User'" :role="auth.user?.role ?? ''" />

      <!-- Student dashboard -->
      <template v-if="auth.isStudent">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <StatCard label="Open Projects" :value="stats.openProjects" tone="teal" />
          <StatCard label="My Applications" :value="stats.myApplications" tone="slate" />
          <StatCard label="Accepted" :value="stats.acceptedApplications" tone="emerald" />
        </div>

        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Recent open projects</h2>
            <RouterLink to="/projects" class="text-sm text-indigo-600 hover:underline"
              >View all →</RouterLink
            >
          </div>
          <div v-if="projectStore.loading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="n in 3" :key="n" class="h-40 bg-gray-100 rounded-xl animate-pulse" />
          </div>
          <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <ProjectCard
              v-for="project in recentProjects"
              :key="project.id"
              :project="project"
              @click="$router.push('/projects/' + project.id)"
              class="cursor-pointer"
            />
          </div>
        </section>
      </template>

      <!-- Company dashboard -->
      <template v-else-if="auth.isCompany">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <StatCard label="My Projects" :value="stats.myProjects" tone="teal" />
          <StatCard label="Open Projects" :value="stats.openProjects" tone="cyan" />
          <StatCard label="Total Applications" :value="stats.totalApplications" tone="slate" />
        </div>

        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Your projects</h2>
            <RouterLink to="/projects/create" class="text-sm text-indigo-600 hover:underline"
              >+ New project</RouterLink
            >
          </div>
          <div v-if="projectStore.loading" class="space-y-3">
            <div v-for="n in 3" :key="n" class="h-20 bg-gray-100 rounded-xl animate-pulse" />
          </div>
          <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <ProjectCard
              v-for="project in recentProjects"
              :key="project.id"
              :project="project"
              @click="$router.push('/projects/' + project.id)"
              class="cursor-pointer"
            />
          </div>
        </section>
      </template>

      <!-- Admin dashboard -->
      <template v-else-if="auth.isAdmin">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <StatCard label="Total Users" :value="stats.totalUsers" tone="teal" />
          <StatCard label="Total Projects" :value="stats.totalProjects" tone="slate" />
          <StatCard label="Open Projects" :value="stats.openProjects" tone="cyan" />
        </div>
        <div class="surface-card p-6 text-sm text-slate-600">
          Go to
          <RouterLink to="/admin" class="text-indigo-600 hover:underline font-medium"
            >Admin Panel</RouterLink
          >
          to manage users and projects.
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectStore } from '@/stores/project'
import { useApplicationStore } from '@/stores/application'
import {
  countAcceptedApplications,
  countOpenProjects,
  createDefaultDashboardStats,
} from '@/services/dashboard/DashboardService'
import AppLayout from '@/layouts/AppLayout.vue'
import ProjectCard from '@/components/projects/ProjectCard.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'

interface DashboardProject {
  id: number
  status?: string
  [key: string]: unknown
}

export default defineComponent({
  name: 'DashboardView',
  components: { AppLayout, ProjectCard, StatCard, DashboardHero },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
      applicationStore: useApplicationStore(),
    }
  },
  data() {
    return {
      stats: createDefaultDashboardStats(),
    }
  },
  computed: {
    recentProjects(): DashboardProject[] {
      return (this.projectStore.projects as DashboardProject[]).slice(0, 6)
    },
  },
  async mounted() {
    if (this.auth.isStudent) {
      await Promise.all([
        this.projectStore.fetchProjects({ status: 'open', per_page: 6 }),
        this.applicationStore.fetchApplications(),
      ])
      this.stats.openProjects = this.projectStore.pagination?.total ?? 0
      this.stats.myApplications = this.applicationStore.applications.length
      this.stats.acceptedApplications = countAcceptedApplications(
        this.applicationStore.applications,
      )
    } else if (this.auth.isCompany) {
      await Promise.all([
        this.projectStore.fetchProjects({ per_page: 6 }),
        this.applicationStore.fetchApplications(),
      ])
      this.stats.myProjects = this.projectStore.pagination?.total ?? 0
      this.stats.openProjects = countOpenProjects(this.projectStore.projects)
      this.stats.totalApplications = this.applicationStore.applications.length
    } else if (this.auth.isAdmin) {
      await this.projectStore.fetchProjects({ per_page: 3 })
      this.stats.openProjects = countOpenProjects(this.projectStore.projects)
    }
  },
})
</script>
