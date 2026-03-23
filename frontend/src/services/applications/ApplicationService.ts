import http from '@/services/core/http'

export type ApplicationTaskStatus = 'todo' | 'in_progress' | 'complete'
export type ApplicationTaskPriority = 'low' | 'medium' | 'high' | 'urgent'

export interface ProjectTaskBoardTask {
  id: number
  application_id: number
  title: string
  priority: ApplicationTaskPriority
  status: ApplicationTaskStatus
  position: number
  assignee: {
    id: number
    name: string | null
    email: string | null
  }
}

export interface ProjectTaskBoardCategory {
  id: number
  name: string
  position: number
  tasks: ProjectTaskBoardTask[]
}

export interface ProjectTaskBoardFolder {
  id: number
  name: string
  position: number
  parent_folder_id: number | null
  uncategorized_tasks: ProjectTaskBoardTask[]
  categories: ProjectTaskBoardCategory[]
}

export interface ProjectTaskBoardResponse {
  project: {
    id: number
    title: string
  }
  counts: {
    todo: number
    in_progress: number
    complete: number
  }
  sections: Record<ApplicationTaskStatus, ProjectTaskBoardFolder[]>
}

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

  async listTasks(id: number) {
    const { data } = await http.get(`/applications/${id}/tasks`)
    return data
  },

  async createTask(
    id: number,
    payload: {
      title: string
      requirements?: string
      priority: ApplicationTaskPriority
      assignee_user_id?: number
      task_folder_id?: number
      task_category_id?: number
      position?: number
      due_at?: string
    },
  ) {
    const { data } = await http.post(`/applications/${id}/tasks`, payload)
    return data
  },

  async updateTask(
    applicationId: number,
    taskId: number,
    payload: {
      title?: string
      requirements?: string | null
      priority?: ApplicationTaskPriority
      status?: ApplicationTaskStatus
      student_note?: string
      assignee_user_id?: number
      task_folder_id?: number | null
      task_category_id?: number | null
      position?: number
      due_at?: string | null
    },
  ) {
    const { data } = await http.patch(`/applications/${applicationId}/tasks/${taskId}`, payload)
    return data
  },

  async deleteTask(applicationId: number, taskId: number) {
    const { data } = await http.delete(`/applications/${applicationId}/tasks/${taskId}`)
    return data
  },

  async getProjectTaskBoard(projectId: number) {
    const { data } = await http.get(`/projects/${projectId}/task-board`)
    return data as { data: ProjectTaskBoardResponse }
  },

  async listTaskFolders(projectId: number) {
    const { data } = await http.get(`/projects/${projectId}/task-folders`)
    return data
  },

  async createTaskFolder(
    projectId: number,
    payload: { name: string; color?: string; position?: number },
  ) {
    const { data } = await http.post(`/projects/${projectId}/task-folders`, payload)
    return data
  },

  async createTaskCategory(
    projectId: number,
    folderId: number,
    payload: { name: string; color?: string; position?: number },
  ) {
    const { data } = await http.post(
      `/projects/${projectId}/task-folders/${folderId}/categories`,
      payload,
    )
    return data
  },

  async updateTaskFolder(
    projectId: number,
    folderId: number,
    payload: {
      name?: string
      color?: string | null
      position?: number
      parent_folder_id?: number | null
    },
  ) {
    const { data } = await http.patch(`/projects/${projectId}/task-folders/${folderId}`, payload)
    return data
  },

  async deleteTaskFolder(projectId: number, folderId: number) {
    const { data } = await http.delete(`/projects/${projectId}/task-folders/${folderId}`)
    return data
  },

  async updateTaskCategory(
    projectId: number,
    folderId: number,
    categoryId: number,
    payload: { name?: string; color?: string | null; position?: number },
  ) {
    const { data } = await http.patch(
      `/projects/${projectId}/task-folders/${folderId}/categories/${categoryId}`,
      payload,
    )
    return data
  },

  async deleteTaskCategory(projectId: number, folderId: number, categoryId: number) {
    const { data } = await http.delete(
      `/projects/${projectId}/task-folders/${folderId}/categories/${categoryId}`,
    )
    return data
  },

  async withdraw(id: number) {
    const { data } = await http.delete(`/applications/${id}`)
    return data
  },
}

export default ApplicationService
