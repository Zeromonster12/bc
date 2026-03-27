import http from '@/services/core/http'

const AdminService = {
  async getUsers(params: { page?: number; per_page?: number; search?: string; role?: string; company_status?: string }) {
    const { data } = await http.get('/admin/users', { params })
    return data
  },

  async getProjects(params: { page?: number; per_page?: number }) {
    const { data } = await http.get('/admin/projects', { params })
    return data
  },

  async updateUserRole(userId: number, role: string) {
    const { data } = await http.patch(`/admin/users/${userId}`, { role })
    return data
  },

  async deleteUser(userId: number) {
    const { data } = await http.delete(`/admin/users/${userId}`)
    return data
  },

  async approveCompany(userId: number) {
    const { data } = await http.patch(`/admin/users/${userId}/approve-company`)
    return data
  },

  async rejectCompany(userId: number) {
    const { data } = await http.patch(`/admin/users/${userId}/reject-company`)
    return data
  },

  async deleteProject(projectId: number) {
    const { data } = await http.delete(`/admin/projects/${projectId}`)
    return data
  },
}

export default AdminService
