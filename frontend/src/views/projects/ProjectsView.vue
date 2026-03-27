<template>
  <AppLayout>
    <div class="space-y-5 pt-12 lg:space-y-6 lg:pt-0">
      <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ pageTitle }}</h1>
      </div>

      <div class="grid items-stretch gap-6 lg:min-h-[calc(100vh-11rem)] lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,1fr)]">
        <aside class="flex h-full flex-col gap-4 lg:sticky lg:top-24 lg:h-[calc(100vh-8rem)]">
          <ProjectFilters
            class="flex-1"
            variant="sidebar"
            :tech-options="availableTechOptions"
            @change="handleFilterChange"
          />
        </aside>

        <section class="space-y-4">
          <div class="flex w-full flex-wrap items-center justify-end gap-2 rounded-2xl border border-slate-200/80 bg-white/80 p-2.5 dark:border-slate-700/70 dark:bg-slate-900/70">
            <div class="grid grid-cols-2 rounded-full border border-[#ded8ee] bg-[#e8e3f2] p-1 dark:border-slate-600 dark:bg-slate-800">
              <button
                type="button"
                class="rounded-full px-3 py-1.5 text-xs font-semibold transition sm:px-4 sm:text-sm"
                :class="
                  viewMode === 'grid'
                    ? 'bg-white text-[#201f35] shadow-sm dark:bg-slate-700 dark:text-slate-100'
                    : 'text-[#5f6078] hover:bg-white/70 dark:text-slate-300 dark:hover:bg-slate-700/70'
                "
                @click="viewMode = 'grid'"
              >
                Grid
              </button>
              <button
                type="button"
                class="rounded-full px-3 py-1.5 text-xs font-semibold transition sm:px-4 sm:text-sm"
                :class="
                  viewMode === 'list'
                    ? 'bg-white text-[#201f35] shadow-sm dark:bg-slate-700 dark:text-slate-100'
                    : 'text-[#5f6078] hover:bg-white/70 dark:text-slate-300 dark:hover:bg-slate-700/70'
                "
                @click="viewMode = 'list'"
              >
                List
              </button>
            </div>

            <RouterLink v-if="auth.isCompany" to="/projects/create">
              <span
                class="inline-flex items-center justify-center rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-3 py-2 text-xs font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] transition hover:brightness-105 sm:px-5 sm:py-2.5 sm:text-sm"
              >
                + New project
              </span>
            </RouterLink>
          </div>

          <div
            v-if="projectStore.loading"
            :class="
              viewMode === 'grid'
                ? 'grid gap-4 sm:grid-cols-2 xl:grid-cols-3'
                : 'grid gap-4 grid-cols-1'
            "
          >
            <div
              v-for="n in viewMode === 'grid' ? 6 : 4"
              :key="n"
              class="h-48 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800"
            />
          </div>

          <template v-else>
            <div v-if="projectStore.projects.length === 0" class="py-16 text-center text-gray-500 dark:text-slate-400">
              <p class="text-4xl mb-3">📂</p>
              <p class="font-medium">No projects found</p>
              <p class="text-sm mt-1">Try adjusting your filters.</p>
            </div>
            <div
              v-else
              :class="
                viewMode === 'grid'
                  ? 'grid gap-4 sm:grid-cols-2 xl:grid-cols-3'
                  : 'grid gap-4 grid-cols-1'
              "
            >
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
        </section>
      </div>
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
import BasePagination from '@/components/ui/BasePagination.vue'

export default defineComponent({
  name: 'ProjectsView',
  components: { AppLayout, ProjectCard, ProjectFilters, BasePagination },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
    }
  },
  data() {
    return {
      filters: {} as Record<string, unknown>,
      viewMode: 'grid' as 'grid' | 'list',
    }
  },
  computed: {
    pageTitle(): string {
      return this.isCompanyMyProjectsScope ? 'My Projects' : 'Projects'
    },
    isCompanyMyProjectsScope(): boolean {
      return this.auth.isCompany && this.$route.query.company === 'me'
    },
    availableTechOptions(): string[] {
      const fromProjects = this.projectStore.projects.flatMap((project) => project.tech_stack ?? [])
      const selected = Array.isArray(this.filters.tech_stack)
        ? (this.filters.tech_stack as string[])
        : []

      return Array.from(new Set([...fromProjects, ...selected])).sort((a, b) => a.localeCompare(b))
    },
  },
  mounted() {
    this.fetchWithCurrentScope(1)
  },
  watch: {
    '$route.query.company'() {
      this.fetchWithCurrentScope(1)
    },
  },
  methods: {
    scopedFilters(baseFilters: Record<string, unknown>): Record<string, unknown> {
      if (!this.isCompanyMyProjectsScope) {
        return { ...baseFilters }
      }

      return {
        ...baseFilters,
        company_id: this.auth.user?.id,
      }
    },
    fetchWithCurrentScope(page: number) {
      this.projectStore.fetchProjects(buildProjectsListParams(this.scopedFilters(this.filters), page))
    },
    handleFilterChange(newFilters: Record<string, unknown>) {
      this.filters = newFilters
      this.fetchWithCurrentScope(1)
    },
    handlePageChange(page: number) {
      this.fetchWithCurrentScope(page)
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
  },
})
</script>
