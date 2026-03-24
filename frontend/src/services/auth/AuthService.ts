import http from '@/services/core/http'
import { backendOrigin } from '@/services/core/url'

export interface LoginCredentials {
  email: string
  password: string
  turnstile_token: string
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  role: 'student' | 'company'
  turnstile_token: string
}

export interface VerifyEmailCodePayload {
  email: string
  code: string
}

export interface ResendVerificationEmailPayload {
  email: string
  turnstile_token: string
}

export interface ForgotPasswordPayload {
  email: string
  turnstile_token: string
}

export interface OAuthUrlResponse {
  url: string
}

export interface OAuthCallbackPayload {
  code: string
  state?: string
}

const AuthService = {
  async login(credentials: LoginCredentials) {
    await http.get(`${backendOrigin}/sanctum/csrf-cookie`)
    const { data } = await http.post('/auth/login', credentials)
    return data
  },

  async getGoogleOAuthRedirectUrl(): Promise<OAuthUrlResponse> {
    const { data } = await http.get('/auth/google/redirect')
    return data
  },

  async completeGoogleOAuth(payload: OAuthCallbackPayload) {
    const { data } = await http.get('/auth/google/callback', { params: payload })
    return data
  },

  async register(payload: RegisterData) {
    await http.get(`${backendOrigin}/sanctum/csrf-cookie`)
    const { data } = await http.post('/auth/register', payload)
    return data
  },

  async verifyEmailCode(payload: VerifyEmailCodePayload) {
    await http.get(`${backendOrigin}/sanctum/csrf-cookie`)
    const { data } = await http.post('/auth/verify-email-code', payload)
    return data
  },

  async logout() {
    await http.get(`${backendOrigin}/sanctum/csrf-cookie`)
    await http.post('/auth/logout')
  },

  async getUser() {
    const { data } = await http.get('/auth/user')
    return data
  },

  async forgotPassword(payload: ForgotPasswordPayload) {
    await http.get(`${backendOrigin}/sanctum/csrf-cookie`)
    const { data } = await http.post('/auth/forgot-password', payload)
    return data
  },

  async resetPassword(payload: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }) {
    const { data } = await http.post('/auth/reset-password', payload)
    return data
  },

  async resendVerificationEmail(payload: ResendVerificationEmailPayload) {
    const { data } = await http.post('/auth/email/verification-notification', payload)
    return data
  },
}

export default AuthService
