import { mapValidationErrors } from '@/services/shared/FormUtilsService'

interface BackendError {
  response?: {
    status?: number
    data?: {
      message?: string
      errors?: Record<string, unknown>
    }
  }
}

export const hasTurnstileToken = (token: string): boolean => Boolean(token && token.trim())

export const resolveValidationErrors = (error: unknown): Record<string, string> => {
  const backend = error as BackendError
  return mapValidationErrors(backend.response?.data?.errors ?? {})
}

export const resolveErrorMessage = (error: unknown, fallback: string): string => {
  const backend = error as BackendError
  return backend.response?.data?.message ?? fallback
}

export const resolveSingleFieldError = (error: unknown, field: string): string => {
  const backend = error as BackendError
  const value = backend.response?.data?.errors?.[field]
  if (Array.isArray(value)) return String(value[0] ?? '')
  if (typeof value === 'string') return value
  return ''
}

export const resolveEmailVerificationResultMessage = (status: string, message: string): string => {
  if (message) return message
  return status === 'success'
    ? 'Your email was verified successfully.'
    : 'Email verification failed.'
}
