export interface CompanyProject {
  id: number
  title: string
  status: string
  applications_count?: number
}

export interface AppPagination {
  current_page: number
  last_page: number
  total: number
}

export interface ApplicationListItem {
  id: number
  project_id?: number
  status?: string
  [key: string]: unknown
}

export type ApplicantQuickStatus = 'all' | 'pending' | 'accepted' | 'rejected'

export interface CompanyApplicantListItem {
  id: number
  status?: string
  created_at?: string
  student?: {
    name?: string
    email?: string
    profile?: {
      university?: string
      degree?: string
      field_of_study?: string
      skills?: string[]
      interests?: string[]
      bio?: string
      about_me?: string
    }
  }
  project?: {
    tech_stack?: string[]
  }
}

export const toPagination = (value: Partial<AppPagination> | null): AppPagination | null => {
  if (!value) return null
  if (typeof value.current_page !== 'number' || typeof value.last_page !== 'number') return null
  return {
    current_page: value.current_page,
    last_page: value.last_page,
    total: typeof value.total === 'number' ? value.total : 0,
  }
}

export const findProjectById = (
  projects: CompanyProject[],
  selectedProjectId: number | null,
): CompanyProject | null => {
  if (!selectedProjectId) return null
  const selectedId = Number(selectedProjectId)
  return projects.find((project) => Number(project.id) === selectedId) ?? null
}

export const filterApplications = (
  applications: ApplicationListItem[],
  activeStatus: string,
  selectedProjectId: number | null,
  isCompany: boolean,
): ApplicationListItem[] => {
  let list = applications
  if (isCompany && selectedProjectId) {
    const selectedId = Number(selectedProjectId)
    list = list.filter((application) => Number(application.project_id) === selectedId)
  }
  if (activeStatus !== 'all') {
    list = list.filter((application) => application.status === activeStatus)
  }
  return list
}

export const buildCompanyApplicationsParams = (
  selectedProjectId: number | null,
  activeStatus: string,
  page: number,
): Record<string, unknown> | null => {
  if (!selectedProjectId) return null

  const params: Record<string, unknown> = {
    page,
    per_page: 50,
    project_id: selectedProjectId,
  }

  if (activeStatus !== 'all') {
    params.status = activeStatus
  }

  return params
}

const normalizeChip = (value: string): string => {
  return String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '')
    .replace(/[^a-z0-9+#]/g, '')
}

export const filterCompanyApplicants = <T extends CompanyApplicantListItem>(
  applications: T[],
  activeQuickStatus: ApplicantQuickStatus,
  searchQuery: string,
): T[] => {
  const query = String(searchQuery ?? '').trim().toLowerCase()

  return applications.filter((application) => {
    const status = String(application.status ?? 'pending')
    if (activeQuickStatus !== 'all' && status !== activeQuickStatus) {
      return false
    }

    if (!query) return true

    const haystack = [
      application.student?.name,
      application.student?.email,
      application.student?.profile?.university,
      application.student?.profile?.degree,
      application.student?.profile?.field_of_study,
      ...(application.student?.profile?.skills ?? []),
      ...(application.student?.profile?.interests ?? []),
      application.student?.profile?.bio,
      application.student?.profile?.about_me,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(query)
  })
}

export const calculateReviewedPercent = (applications: CompanyApplicantListItem[]): number => {
  if (!applications.length) return 0

  const reviewed = applications.filter(
    (app) => app.status === 'accepted' || app.status === 'rejected',
  ).length
  return Math.round((reviewed / applications.length) * 100)
}

export const applicantStatusLabel = (status?: string): string => {
  if (status === 'accepted') return 'Approved'
  if (status === 'rejected') return 'Rejected'
  if (status === 'withdrawn') return 'Withdrawn'
  return 'Pending'
}

export const applicantStatusTabClass = (
  activeQuickStatus: ApplicantQuickStatus,
  status: ApplicantQuickStatus,
): string => {
  if (activeQuickStatus === status) {
    return 'bg-[#d8cdff] text-[#3f1ccc] dark:bg-indigo-500/25 dark:text-indigo-300'
  }

  return 'bg-white text-slate-600 hover:bg-[#ece6fb] dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
}

export const applicantStatusPillClass = (status?: string): string => {
  if (status === 'accepted') {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
  }
  if (status === 'rejected') {
    return 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300'
  }
  if (status === 'withdrawn') {
    return 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
  }
  return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
}

export const applicantStatusDotClass = (status?: string): string => {
  if (status === 'accepted') return 'bg-emerald-500'
  if (status === 'rejected') return 'bg-rose-500'
  if (status === 'withdrawn') return 'bg-slate-500'
  return 'bg-amber-500'
}

export const applicantEducationLine = (application: CompanyApplicantListItem): string => {
  const degree = application.student?.profile?.degree?.trim() ?? ''
  const field = application.student?.profile?.field_of_study?.trim() ?? ''
  const pieces = [degree, field].filter(Boolean)
  return pieces.length ? pieces.join(' - ') : 'Education not specified'
}

export const applicantTopSkillsLine = (application: CompanyApplicantListItem): string => {
  const skills = application.student?.profile?.skills ?? []
  if (!skills.length) return 'Skills not specified'
  return skills.slice(0, 3).join(', ')
}

export const applicantMatchScore = (application: CompanyApplicantListItem): number => {
  const projectChips = new Set(
    (application.project?.tech_stack ?? [])
      .map((chip) => normalizeChip(chip))
      .filter((chip) => chip.length > 0),
  )

  if (!projectChips.size) {
    return 0
  }

  const studentChips = new Set(
    (application.student?.profile?.skills ?? [])
      .map((chip) => normalizeChip(chip))
      .filter((chip) => chip.length > 0),
  )

  let overlap = 0
  projectChips.forEach((chip) => {
    if (studentChips.has(chip)) {
      overlap += 1
    }
  })

  const ratio = overlap / projectChips.size
  return Math.round(Math.max(0, Math.min(100, ratio * 100)))
}
