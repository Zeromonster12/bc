import http from '@/services/core/http'

export interface ProjectFilters {
  search?: string
  status?: string
  location?: string
  sort_date?: 'newest' | 'oldest'
  tech_stack?: string[]
  company_id?: number
  per_page?: number
  page?: number
}

const ProjectService = {
  async getAll(params: ProjectFilters = {}) {
    const { data } = await http.get('/projects', { params })
    return data
  },

  async getById(id: number) {
    const { data } = await http.get(`/projects/${id}`)
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await http.post('/projects', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await http.put(`/projects/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await http.delete(`/projects/${id}`)
    return data
  },

  async getMyProjects(params: ProjectFilters = {}) {
    const { data } = await http.get('/projects', { params: { ...params } })
    return data
  },
}

export default ProjectService
