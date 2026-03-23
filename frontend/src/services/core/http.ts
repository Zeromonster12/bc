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
})

http.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }

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
      const auth = useAuthStore()
      auth.clearSession()
      await router.push({ name: 'login' })
    }
    return Promise.reject(error)
  },
)

export default http
