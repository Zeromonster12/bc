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
  return projects.find((project) => project.id === selectedProjectId) ?? null
}

export const filterApplications = (
  applications: ApplicationListItem[],
  activeStatus: string,
  selectedProjectId: number | null,
  isCompany: boolean,
): ApplicationListItem[] => {
  let list = applications
  if (isCompany && selectedProjectId) {
    list = list.filter((application) => application.project_id === selectedProjectId)
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
