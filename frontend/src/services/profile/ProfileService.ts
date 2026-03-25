import http from '@/services/core/http'
import type { AxiosProgressEvent } from 'axios'

export interface GitHubConnectionData {
  username: string
  profile_url: string
  avatar_url: string | null
  connected_at: string | null
}

export interface GitHubConnectionResponse {
  connected: boolean
  data: GitHubConnectionData | null
}

export interface GitHubRepositoryInsight {
  name: string
  url: string
  stars: number
  language: string
  updated_at: string
}

export interface GitHubCommitInsight {
  repo: string
  message: string
  sha: string
  pushed_at: string
}

export interface GitHubInsightsResponse {
  connected: boolean
  data: {
    repositories: GitHubRepositoryInsight[]
    recent_commits: GitHubCommitInsight[]
  }
}

export interface OAuthUrlResponse {
  url: string
}

export interface StudentCvFileItem {
  id: number
  original_filename: string
  mime_type: string
  size_bytes: number
  scan_status: string
  scan_message: string | null
  scanned_at: string | null
  uploaded_at: string | null
  download_url: string
}

export interface StudentCvFilesResponse {
  data: StudentCvFileItem[]
}

const ProfileService = {
  async getStudentProfile() {
    const { data } = await http.get('/profile/student')
    return data
  },

  async updateStudentProfile(payload: FormData | Record<string, unknown>) {
    if (payload instanceof FormData) {
      if (!payload.has('_method')) {
        payload.append('_method', 'PUT')
      }

      const { data } = await http.post('/profile/student', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      return data
    }

    const { data } = await http.put('/profile/student', payload)
    return data
  },

  async getCompanyProfile() {
    const { data } = await http.get('/profile/company')
    return data
  },

  async updateCompanyProfile(payload: FormData | Record<string, unknown>) {
    if (payload instanceof FormData) {
      if (!payload.has('_method')) {
        payload.append('_method', 'PUT')
      }

      const { data } = await http.post('/profile/company', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      return data
    }

    const { data } = await http.put('/profile/company', payload)
    return data
  },

  async getGitHubConnectionStatus(): Promise<GitHubConnectionResponse> {
    const { data } = await http.get('/auth/github/connect/status')
    return data
  },

  async getGitHubConnectionRedirectUrl(): Promise<OAuthUrlResponse> {
    const { data } = await http.get('/auth/github/connect/redirect')
    return data
  },

  async completeGitHubConnection(payload: {
    code: string
    state?: string
  }): Promise<GitHubConnectionResponse> {
    const { data } = await http.get('/auth/github/connect/callback', { params: payload })
    return data
  },

  async disconnectGitHubConnection(): Promise<GitHubConnectionResponse> {
    const { data } = await http.delete('/auth/github/connect')
    return data
  },

  async getGitHubInsights(): Promise<GitHubInsightsResponse> {
    const { data } = await http.get('/auth/github/connect/insights')
    return data
  },

  async getStudentCvFiles(): Promise<StudentCvFilesResponse> {
    const { data } = await http.get('/profile/student/cv')
    return data
  },

  async uploadStudentCv(
    file: File,
    onProgress?: (progressPercent: number) => void,
  ): Promise<{ data: StudentCvFileItem; message: string }> {
    const payload = new FormData()
    payload.append('cv', file)

    const { data } = await http.post('/profile/student/cv', payload, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (event: AxiosProgressEvent) => {
        if (!onProgress) return

        const total = event.total ?? file.size
        if (!total || total <= 0) return

        const progress = Math.min(100, Math.max(1, Math.round((event.loaded / total) * 100)))
        onProgress(progress)
      },
    })

    return data
  },

  async deleteStudentCv(id: number): Promise<{ message: string }> {
    const { data } = await http.delete(`/profile/student/cv/${id}`)
    return data
  },

  async downloadStudentCv(id: number): Promise<Blob> {
    const { data } = await http.get(`/profile/student/cv/${id}/download`, {
      responseType: 'blob',
    })

    return data as Blob
  },
}

export default ProfileService
