<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <RouterLink v-if="auth.isCompany" to="/projects/create">
          <BaseButton variant="primary" size="sm">+ New project</BaseButton>
        </RouterLink>
      </div>

      <ProjectFilters @change="handleFilterChange" />

      <div v-if="projectStore.loading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="n in 6" :key="n" class="h-48 bg-gray-100 rounded-xl animate-pulse" />
      </div>

      <template v-else>
        <div v-if="projectStore.projects.length === 0" class="text-center py-16 text-gray-500">
          <p class="text-4xl mb-3">📂</p>
          <p class="font-medium">No projects found</p>
          <p class="text-sm mt-1">Try adjusting your filters.</p>
        </div>
        <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <ProjectCard
            v-for="project in projectStore.projects"
            :key="project.id"
            :project="project"
            @click="$router.push('/projects/' + project.id)"
            class="cursor-pointer"
          />
        </div>

        <BasePagination
          v-if="projectStore.pagination && projectStore.pagination.last_page > 1"
          :current-page="projectStore.pagination.current_page"
          :last-page="projectStore.pagination.last_page"
          :total="projectStore.pagination.total"
          @change="handlePageChange"
        />
      </template>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectStore } from '@/stores/project'
import { buildProjectsListParams } from '@/services/projects/ProjectsViewService'
import AppLayout from '@/layouts/AppLayout.vue'
import ProjectCard from '@/components/projects/ProjectCard.vue'
import ProjectFilters from '@/components/projects/ProjectFilters.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BasePagination from '@/components/ui/BasePagination.vue'

export default defineComponent({
  name: 'ProjectsView',
  components: { AppLayout, ProjectCard, ProjectFilters, BaseButton, BasePagination },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
    }
  },
  data() {
    return {
      filters: {} as Record<string, unknown>,
    }
  },
  mounted() {
    this.projectStore.fetchProjects(buildProjectsListParams(this.filters, 1))
  },
  methods: {
    handleFilterChange(newFilters: Record<string, unknown>) {
      this.filters = newFilters
      this.projectStore.fetchProjects(buildProjectsListParams(newFilters, 1))
    },
    handlePageChange(page: number) {
      this.projectStore.fetchProjects(buildProjectsListParams(this.filters, page))
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
  },
})
</script>
