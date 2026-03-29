import { defineStore } from 'pinia'
import AuthService, { type LoginCredentials, type RegisterData } from '@/services/auth/AuthService'

interface User {
  id: number
  name: string
  first_name?: string | null
  last_name?: string | null
  email: string
  role: 'student' | 'company' | 'admin'
  email_verified_at: string | null
  company_verification_status?: 'pending' | 'approved' | 'rejected'
  company_verified_at?: string | null
  avatar_url?: string | null
  student?: Record<string, unknown> | null
  company?: Record<string, unknown> | null
}

interface AuthState {
  user: User | null
  loading: boolean
  error: string | null
  initialized: boolean
}

interface AuthResult {
  data: User
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    loading: false,
    error: null,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.user,
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
      try {
        const result = await AuthService.getUser()
        this.user = result.data
        this.initialized = true
        return true
      } catch {
        this.clearSession()
        return false
      }
    },

    async fetchUserSilently(): Promise<boolean> {
      try {
        const result = await AuthService.getUser()
        this.user = result.data
        this.initialized = true
        return true
      } catch {
        this.user = null
        this.initialized = true
        return false
      }
    },

    async hydrateSession(): Promise<boolean> {
      if (this.initialized) {
        return this.isAuthenticated
      }

      return this.fetchUserSilently()
    },

    setAuthenticatedSession(result: AuthResult) {
      this.user = result.data
      this.initialized = true
    },

    clearSession() {
      this.user = null
      this.error = null
      this.initialized = true
    },
  },
})
