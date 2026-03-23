import http from '@/services/core/http'

const ApplicationService = {
  async getAll(params: Record<string, unknown> = {}) {
    const { data } = await http.get('/applications', { params })
    return data
  },

  async apply(projectId: number, payload: { cover_letter: string }) {
    const { data } = await http.post(`/projects/${projectId}/apply`, payload)
    return data
  },

  async updateStatus(id: number, status: 'accepted' | 'rejected') {
    const { data } = await http.patch(`/applications/${id}`, { status })
    return data
  },

  async updateStudentProjectProgress(
    id: number,
    payload: {
      student_project_status: 'not_started' | 'in_progress' | 'blocked' | 'completed'
      student_project_note?: string
    },
  ) {
    const { data } = await http.patch(`/applications/${id}/student-progress`, payload)
    return data
  },

  async listProgressUpdates(id: number) {
    const { data } = await http.get(`/applications/${id}/progress-updates`)
    return data
  },

  async submitProgressUpdate(
    id: number,
    payload: {
      title: string
      notes?: string
      student_project_status?: 'not_started' | 'in_progress' | 'blocked' | 'completed'
    },
  ) {
    const { data } = await http.post(`/applications/${id}/progress-updates`, payload)
    return data
  },

  async withdraw(id: number) {
    const { data } = await http.delete(`/applications/${id}`)
    return data
  },
}

export default ApplicationService
