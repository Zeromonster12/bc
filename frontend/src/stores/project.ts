import { defineStore } from 'pinia'
import ProjectService, { type ProjectFilters } from '@/services/projects/ProjectService'

interface Project {
  id: number
  company: Record<string, unknown>
  title: string
  description: string
  requirements: string | null
  tech_stack: string[]
  status: string
  max_students: number
  deadline: string | null
  applications_count: number
  created_at: string
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface ProjectState {
  projects: Project[]
  currentProject: Project | null
  pagination: Pagination | null
  loading: boolean
  error: string | null
}

export const useProjectStore = defineStore('project', {
  state: (): ProjectState => ({
    projects: [],
    currentProject: null,
    pagination: null,
    loading: false,
    error: null,
  }),

  getters: {
    openProjects: (state): Project[] => state.projects.filter((p) => p.status === 'open'),
  },

  actions: {
    async fetchProjects(filters: ProjectFilters = {}) {
      this.loading = true
      this.error = null
      try {
        const result = await ProjectService.getAll(filters)
        this.projects = result.data
        this.pagination = {
          current_page: result.meta.current_page,
          last_page: result.meta.last_page,
          per_page: result.meta.per_page,
          total: result.meta.total,
        }
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to fetch projects.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchProject(id: number) {
      this.loading = true
      this.error = null
      try {
        const result = await ProjectService.getById(id)
        this.currentProject = result.data
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to fetch project.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async createProject(payload: Record<string, unknown>) {
      this.loading = true
      this.error = null
      try {
        const result = await ProjectService.create(payload)
        return result.data as Project
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to create project.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async updateProject(id: number, payload: Record<string, unknown>) {
      this.loading = true
      try {
        const result = await ProjectService.update(id, payload)
        this.currentProject = result.data
        return result.data as Project
      } finally {
        this.loading = false
      }
    },

    async deleteProject(id: number) {
      await ProjectService.remove(id)
      this.projects = this.projects.filter((p) => p.id !== id)
    },
  },
})
