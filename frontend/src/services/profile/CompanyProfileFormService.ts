export interface CompanyProfileForm {
  name: string
  tagline: string
  description: string
  mission: string
  values: string
  benefits: string
  website: string
  careers_url: string
  linkedin_url: string
  industry: string
  company_size: string
  founded_year: string
  headquarters_city: string
  headquarters_country: string
  contact_email: string
  contact_phone: string
  tech_stack: string
  hiring_focus: string
  remote_policy: string
}

export const createDefaultCompanyProfileForm = (): CompanyProfileForm => ({
  name: '',
  tagline: '',
  description: '',
  mission: '',
  values: '',
  benefits: '',
  website: '',
  careers_url: '',
  linkedin_url: '',
  industry: '',
  company_size: '',
  founded_year: '',
  headquarters_city: '',
  headquarters_country: '',
  contact_email: '',
  contact_phone: '',
  tech_stack: '',
  hiring_focus: '',
  remote_policy: '',
})

export const hydrateCompanyProfileForm = (data: Record<string, unknown>): CompanyProfileForm => ({
  name: String(data.name ?? ''),
  tagline: String(data.tagline ?? ''),
  description: String(data.description ?? ''),
  mission: String(data.mission ?? ''),
  values: String(data.values ?? ''),
  benefits: String(data.benefits ?? ''),
  website: String(data.website ?? ''),
  careers_url: String(data.careers_url ?? ''),
  linkedin_url: String(data.linkedin_url ?? ''),
  industry: String(data.industry ?? ''),
  company_size: String(data.company_size ?? ''),
  founded_year: String(data.founded_year ?? ''),
  headquarters_city: String(data.headquarters_city ?? ''),
  headquarters_country: String(data.headquarters_country ?? ''),
  contact_email: String(data.contact_email ?? ''),
  contact_phone: String(data.contact_phone ?? ''),
  tech_stack: String(data.tech_stack ?? ''),
  hiring_focus: String(data.hiring_focus ?? ''),
  remote_policy: String(data.remote_policy ?? ''),
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
