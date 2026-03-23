import { defineStore } from 'pinia'
import ApplicationService from '@/services/applications/ApplicationService'

interface Application {
  id: number
  project: Record<string, unknown>
  student: Record<string, unknown>
  cover_letter: string
  status: 'pending' | 'accepted' | 'rejected' | 'withdrawn'
  tasks: Array<Record<string, unknown>>
  reviewed_at: string | null
  created_at: string
}

interface ApplicationState {
  applications: Application[]
  pagination: Record<string, unknown> | null
  loading: boolean
  error: string | null
}

export const useApplicationStore = defineStore('application', {
  state: (): ApplicationState => ({
    applications: [],
    pagination: null,
    loading: false,
    error: null,
  }),

  getters: {
    pendingApplications: (state): Application[] =>
      state.applications.filter((a) => a.status === 'pending'),
  },

  actions: {
    async fetchApplications(params: Record<string, unknown> = {}) {
      this.loading = true
      this.error = null
      try {
        const result = await ApplicationService.getAll(params)
        this.applications = result.data
        this.pagination = result.meta ?? null
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to fetch applications.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async applyToProject(projectId: number, coverLetter: string) {
      this.loading = true
      this.error = null
      try {
        const result = await ApplicationService.apply(projectId, { cover_letter: coverLetter })
        this.applications.unshift(result.data)
        return result.data as Application
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to apply.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async updateStatus(id: number, status: 'accepted' | 'rejected') {
      const result = await ApplicationService.updateStatus(id, status)
      const index = this.applications.findIndex((a) => a.id === id)
      if (index !== -1) this.applications[index] = result.data
      return result.data
    },

    async withdraw(id: number) {
      await ApplicationService.withdraw(id)
      const index = this.applications.findIndex((a) => a.id === id)
      if (index !== -1) {
        const application = this.applications[index]
        if (application) application.status = 'withdrawn'
      }
    },
  },
})
