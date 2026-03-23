import { defineStore } from 'pinia'
import AuthService, { type LoginCredentials, type RegisterData } from '@/services/auth/AuthService'

interface User {
  id: number
  name: string
  email: string
  role: 'student' | 'company' | 'admin'
  email_verified_at: string | null
  avatar_url?: string | null
  student?: Record<string, unknown> | null
  company?: Record<string, unknown> | null
}

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
  error: string | null
}

interface AuthResult {
  token: string
  data: User
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: sessionStorage.getItem('auth_token'),
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.token && !!state.user,
    isStudent: (state): boolean => state.user?.role === 'student',
    isCompany: (state): boolean => state.user?.role === 'company',
    isAdmin: (state): boolean => state.user?.role === 'admin',
    isVerified: (state): boolean => !!state.user?.email_verified_at,
  },

  actions: {
    async login(credentials: LoginCredentials) {
      this.loading = true
      this.error = null
      try {
        const result = await AuthService.login(credentials)
        this.setAuthenticatedSession(result)
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Login failed.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async register(payload: RegisterData) {
      this.loading = true
      this.error = null
      try {
        return await AuthService.register(payload)
      } catch (err: unknown) {
        this.error =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Registration failed.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await AuthService.logout()
      } finally {
        this.clearSession()
      }
    },

    async fetchUser() {
      if (!this.token) return
      try {
        const result = await AuthService.getUser()
        this.user = result.data
      } catch {
        this.clearSession()
      }
    },

    setAuthenticatedSession(result: AuthResult) {
      this.token = result.token
      this.user = result.data
      sessionStorage.setItem('auth_token', result.token)
    },

    clearSession() {
      this.user = null
      this.token = null
      this.error = null
      sessionStorage.removeItem('auth_token')
    },
  },
})
