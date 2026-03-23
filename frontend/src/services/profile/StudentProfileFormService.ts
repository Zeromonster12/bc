import { addUniqueTrimmedTag } from '@/services/shared/FormUtilsService'

export type LanguageLevel = 'A1' | 'A2' | 'B1' | 'B2' | 'C1' | 'C2' | 'native' | ''

export interface StudentLanguage {
  name: string
  level: LanguageLevel
}

export interface StudentCertification {
  name: string
  issuer: string
  year: string
  url: string
}

export interface StudentProject {
  title: string
  tech: string
  link: string
  description: string
}

export interface StudentProfileForm {
  headline: string
  date_of_birth: string
  gender: string
  phone: string
  alternate_email: string
  country: string
  city: string
  address_line: string
  postal_code: string
  university: string
  faculty: string
  degree: string
  field_of_study: string
  year_of_study: number | ''
  graduation_year: number | ''
  gpa: string
  bio: string
  about_me: string
  availability: string
  preferred_work_type: string
  preferred_locations: string[]
  expected_salary_min: string
  expected_salary_max: string
  skills: string[]
  interests: string[]
  portfolio_url: string
  cv_url: string
  github_url: string
  linkedin_url: string
  website_url: string
  languages: StudentLanguage[]
  certifications: StudentCertification[]
  projects: StudentProject[]
  emergency_contact_name: string
  emergency_contact_phone: string
  consent_public_profile: boolean
}

export const createDefaultStudentProfileForm = (): StudentProfileForm => ({
  headline: '',
  date_of_birth: '',
  gender: '',
  phone: '',
  alternate_email: '',
  country: '',
  city: '',
  address_line: '',
  postal_code: '',
  university: '',
  faculty: '',
  degree: '',
  field_of_study: '',
  year_of_study: 1,
  graduation_year: '',
  gpa: '',
  bio: '',
  about_me: '',
  availability: '',
  preferred_work_type: '',
  preferred_locations: [],
  expected_salary_min: '',
  expected_salary_max: '',
  skills: [],
  interests: [],
  portfolio_url: '',
  cv_url: '',
  github_url: '',
  linkedin_url: '',
  website_url: '',
  languages: [],
  certifications: [],
  projects: [],
  emergency_contact_name: '',
  emergency_contact_phone: '',
  consent_public_profile: false,
})

const parseArray = (input: unknown): string[] => {
  if (Array.isArray(input)) return input.map((item) => String(item).trim()).filter(Boolean)
  if (typeof input === 'string') {
    try {
      const parsed = JSON.parse(input)
      return Array.isArray(parsed) ? parsed.map((item) => String(item).trim()).filter(Boolean) : []
    } catch {
      return input
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
    }
  }
  return []
}

const parseLanguages = (input: unknown): StudentLanguage[] => {
  let source = input
  if (typeof input === 'string') {
    try {
      source = JSON.parse(input)
    } catch {
      return []
    }
  }
  if (!Array.isArray(source)) return []

  return source
    .map((item) => {
      const value = item as Partial<StudentLanguage>
      return {
        name: String(value?.name ?? '').trim(),
        level: String(value?.level ?? '').trim() as LanguageLevel,
      }
    })
    .filter((item) => item.name)
}

const parseCertifications = (input: unknown): StudentCertification[] => {
  let source = input
  if (typeof input === 'string') {
    try {
      source = JSON.parse(input)
    } catch {
      return []
    }
  }
  if (!Array.isArray(source)) return []

  return source
    .map((item) => {
      const value = item as Partial<StudentCertification>
      return {
        name: String(value?.name ?? '').trim(),
        issuer: String(value?.issuer ?? '').trim(),
        year: String(value?.year ?? '').trim(),
        url: String(value?.url ?? '').trim(),
      }
    })
    .filter((item) => item.name || item.issuer || item.year || item.url)
}

const parseProjects = (input: unknown): StudentProject[] => {
  let source = input
  if (typeof input === 'string') {
    try {
      source = JSON.parse(input)
    } catch {
      return []
    }
  }
  if (!Array.isArray(source)) return []

  return source
    .map((item) => {
      const value = item as Partial<StudentProject>
      return {
        title: String(value?.title ?? '').trim(),
        tech: String(value?.tech ?? '').trim(),
        link: String(value?.link ?? '').trim(),
        description: String(value?.description ?? '').trim(),
      }
    })
    .filter((item) => item.title || item.tech || item.link || item.description)
}

export const hydrateStudentProfileForm = (data: Record<string, unknown>): StudentProfileForm => {
  const fallback = createDefaultStudentProfileForm()

  return {
    ...fallback,
    headline: String(data.headline ?? ''),
    date_of_birth: String(data.date_of_birth ?? ''),
    gender: String(data.gender ?? ''),
    phone: String(data.phone ?? ''),
    alternate_email: String(data.alternate_email ?? ''),
    country: String(data.country ?? ''),
    city: String(data.city ?? ''),
    address_line: String(data.address_line ?? ''),
    postal_code: String(data.postal_code ?? ''),
    university: String(data.university ?? ''),
    faculty: String(data.faculty ?? ''),
    degree: String(data.degree ?? ''),
    field_of_study: String(data.field_of_study ?? ''),
    year_of_study:
      typeof data.year_of_study === 'number'
        ? data.year_of_study
        : Number(data.year_of_study ?? 1) || 1,
    graduation_year:
      typeof data.graduation_year === 'number'
        ? data.graduation_year
        : Number(data.graduation_year ?? '') || '',
    gpa: String(data.gpa ?? ''),
    bio: String(data.bio ?? ''),
    about_me: String(data.about_me ?? ''),
    availability: String(data.availability ?? ''),
    preferred_work_type: String(data.preferred_work_type ?? ''),
    preferred_locations: parseArray(data.preferred_locations),
    expected_salary_min: String(data.expected_salary_min ?? ''),
    expected_salary_max: String(data.expected_salary_max ?? ''),
    skills: parseArray(data.skills),
    interests: parseArray(data.interests),
    portfolio_url: String(data.portfolio_url ?? ''),
    cv_url: String(data.cv_url ?? ''),
    github_url: String(data.github_url ?? ''),
    linkedin_url: String(data.linkedin_url ?? ''),
    website_url: String(data.website_url ?? ''),
    languages: parseLanguages(data.languages),
    certifications: parseCertifications(data.certifications),
    projects: parseProjects(data.projects),
    emergency_contact_name: String(data.emergency_contact_name ?? ''),
    emergency_contact_phone: String(data.emergency_contact_phone ?? ''),
    consent_public_profile: Boolean(data.consent_public_profile),
  }
}

export const calculateStudentProfileCompletion = (form: StudentProfileForm): number => {
  const checkpoints = [
    form.headline,
    form.phone,
    form.country,
    form.city,
    form.university,
    form.faculty,
    form.field_of_study,
    form.bio,
    form.about_me,
    form.availability,
    form.preferred_work_type,
    form.github_url,
    form.linkedin_url,
    form.skills.length > 0,
    form.languages.length > 0,
    form.projects.length > 0,
  ]

  const completed = checkpoints.filter((item) => {
    if (typeof item === 'boolean') return item
    return String(item).trim().length > 0
  }).length

  return Math.round((completed / checkpoints.length) * 100)
}

export const addTagToField = (
  form: StudentProfileForm,
  field: 'skills' | 'interests' | 'preferred_locations',
  rawValue: string,
): StudentProfileForm => {
  return {
    ...form,
    [field]: addUniqueTrimmedTag(form[field], rawValue),
  }
}

export const validateStudentProfileForm = (form: StudentProfileForm): Record<string, string> => {
  const errors: Record<string, string> = {}

  const urlFields = [
    'portfolio_url',
    'cv_url',
    'github_url',
    'linkedin_url',
    'website_url',
  ] as const
  urlFields.forEach((field) => {
    const value = form[field].trim()
    if (!value) return
    try {
      new URL(value)
    } catch {
      errors[field] = 'Please enter a valid URL including protocol (https://...)'
    }
  })

  if (form.alternate_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.alternate_email)) {
    errors.alternate_email = 'Please enter a valid email address.'
  }

  if (form.phone && !/^[+()\d\s-]{7,}$/.test(form.phone)) {
    errors.phone = 'Please enter a valid phone number.'
  }

  if (form.expected_salary_min && form.expected_salary_max) {
    const minSalary = Number(form.expected_salary_min)
    const maxSalary = Number(form.expected_salary_max)
    if (!Number.isNaN(minSalary) && !Number.isNaN(maxSalary) && minSalary > maxSalary) {
      errors.expected_salary_max = 'Maximum salary must be greater than or equal to minimum salary.'
    }
  }

  return errors
}

export const sanitizeStudentProfileForm = (form: StudentProfileForm): StudentProfileForm => {
  return {
    ...form,
    skills: form.skills.map((item) => item.trim()).filter(Boolean),
    interests: form.interests.map((item) => item.trim()).filter(Boolean),
    preferred_locations: form.preferred_locations.map((item) => item.trim()).filter(Boolean),
    languages: form.languages
      .map((item) => ({ name: item.name.trim(), level: item.level }))
      .filter((item) => item.name),
    certifications: form.certifications
      .map((item) => ({
        name: item.name.trim(),
        issuer: item.issuer.trim(),
        year: item.year.trim(),
        url: item.url.trim(),
      }))
      .filter((item) => item.name || item.issuer || item.year || item.url),
    projects: form.projects
      .map((item) => ({
        title: item.title.trim(),
        tech: item.tech.trim(),
        link: item.link.trim(),
        description: item.description.trim(),
      }))
      .filter((item) => item.title || item.tech || item.link || item.description),
  }
}

export const toStudentProfileFormData = (
  form: StudentProfileForm,
  avatarFile: File | null,
): FormData => {
  const payload = new FormData()

  Object.entries(form).forEach(([key, rawValue]) => {
    if (rawValue === null || rawValue === undefined || rawValue === '') return

    if (Array.isArray(rawValue)) {
      if (rawValue.length === 0) return
      const isArrayOfObject = typeof rawValue[0] === 'object' && rawValue[0] !== null
      if (isArrayOfObject) {
        payload.append(key, JSON.stringify(rawValue))
      } else {
        rawValue.forEach((value) => payload.append(`${key}[]`, String(value)))
      }
      return
    }

    if (typeof rawValue === 'boolean') {
      payload.append(key, rawValue ? '1' : '0')
      return
    }

    payload.append(key, String(rawValue))
  })

  if (avatarFile) {
    payload.append('avatar', avatarFile)
  }

  return payload
}
