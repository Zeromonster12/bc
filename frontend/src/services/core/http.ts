import axios, { type AxiosInstance } from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'
import { getExistingEcho } from '@/services/core/echo'

const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
})

http.interceptors.request.use((config) => {
  const echo = getExistingEcho()
  const socketId = echo?.socketId?.()
  if (socketId) {
    config.headers['X-Socket-ID'] = socketId
  }

  return config
})

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const requestUrl = String(error.config?.url ?? '')
      const isSessionProbeRequest = requestUrl === '/auth/user' || requestUrl.endsWith('/auth/user')

      const auth = useAuthStore()
      auth.clearSession()

      if (!isSessionProbeRequest) {
        await router.push({ name: 'login' })
      }
    }
    return Promise.reject(error)
  },
)

export default http
