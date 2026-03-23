const apiBaseUrl = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api').replace(/\/+$/, '')
const backendOrigin = apiBaseUrl.replace(/\/api(?:\/.*)?$/, '')

export const resolveAssetUrl = (value: string | null | undefined): string => {
  if (!value) return ''

  if (/^https?:\/\//i.test(value) || value.startsWith('blob:') || value.startsWith('data:')) {
    return value
  }

  if (value.startsWith('/')) {
    return `${backendOrigin}${value}`
  }

  return `${backendOrigin}/${value}`
}
