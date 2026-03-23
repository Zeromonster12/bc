export const mapValidationErrors = (errors: Record<string, unknown>): Record<string, string> => {
  return Object.fromEntries(
    Object.entries(errors).map(([key, value]) => {
      const firstMessage = Array.isArray(value) ? value[0] : value
      return [key, String(firstMessage ?? 'Invalid value')]
    }),
  )
}

export const addUniqueTrimmedTag = (list: string[], rawValue: string): string[] => {
  const value = rawValue.trim().replace(/,$/, '')
  if (!value) return list

  const normalized = value.toLowerCase()
  const alreadyExists = list.some((item) => item.toLowerCase() === normalized)
  if (alreadyExists) return list

  return [...list, value]
}
