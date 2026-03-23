export interface CompanyProfileForm {
  name: string
  description: string
  website: string
  industry: string
}

export const createDefaultCompanyProfileForm = (): CompanyProfileForm => ({
  name: '',
  description: '',
  website: '',
  industry: '',
})

export const hydrateCompanyProfileForm = (data: Record<string, unknown>): CompanyProfileForm => ({
  name: String(data.name ?? ''),
  description: String(data.description ?? ''),
  website: String(data.website ?? ''),
  industry: String(data.industry ?? ''),
})

export const toCompanyProfileFormData = (
  form: CompanyProfileForm,
  logoFile: File | null,
): FormData => {
  const payload = new FormData()

  Object.entries(form).forEach(([key, value]) => {
    payload.append(key, String(value ?? ''))
  })

  if (logoFile) {
    payload.append('logo', logoFile)
  }

  return payload
}
