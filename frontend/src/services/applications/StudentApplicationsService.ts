export type StudentTab = 'active' | 'archive'

export interface StudentApplicationListItem {
  id: number
  status?: string
  project_id?: number
  created_at?: string
  cover_letter?: string
  project?: {
    title?: string
    company?: {
      user_id?: number
      name?: string
    }
  }
}

export const STUDENT_APPLICATION_TABS: Array<{ value: StudentTab; label: string }> = [
  { value: 'active', label: 'Active applications' },
  { value: 'archive', label: 'Past archives' },
]

const normalizeStatus = (status?: string): string => String(status ?? 'pending')

export const isArchiveStatus = (status?: string): boolean => {
  const normalized = normalizeStatus(status)
  return normalized === 'rejected' || normalized === 'withdrawn'
}

export const filterStudentApplicationsByTab = (
  applications: StudentApplicationListItem[],
  tab: StudentTab,
): StudentApplicationListItem[] => {
  return applications.filter((application) => {
    const archived = isArchiveStatus(application.status)
    return tab === 'archive' ? archived : !archived
  })
}

export const calculateProfileStrength = (applications: StudentApplicationListItem[]): number => {
  if (!applications.length) return 0

  const activeCount = applications.filter((application) => !isArchiveStatus(application.status)).length
  return Math.max(10, Math.min(100, Math.round((activeCount / applications.length) * 100)))
}

export const profileStrengthLabel = (strength: number): string => {
  if (strength >= 80) return 'Highly competitive'
  if (strength >= 55) return 'Strong profile'
  return 'Needs improvement'
}

export const statusLabel = (status?: string): string => {
  if (status === 'accepted') return 'Accepted'
  if (status === 'rejected') return 'Rejected'
  if (status === 'withdrawn') return 'Withdrawn'
  return 'Pending'
}

export const statusHint = (status?: string): string => {
  if (status === 'accepted') return 'Offer active'
  if (status === 'rejected') return 'Selection closed'
  if (status === 'withdrawn') return 'Application cancelled'
  return 'Under review'
}

export const statusBadgeClass = (status?: string): string => {
  if (status === 'accepted') {
    return 'inline-flex rounded-full bg-[#d7f3dd] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[#1a6b35]'
  }
  if (status === 'rejected') {
    return 'inline-flex rounded-full bg-[#ffe0dc] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[#9f2727]'
  }
  if (status === 'withdrawn') {
    return 'inline-flex rounded-full bg-[#ece8f4] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[#6b6682]'
  }

  return 'inline-flex rounded-full bg-[#e9e4ff] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[#4530b5]'
}

export const projectTitle = (application: StudentApplicationListItem): string => {
  return application.project?.title ?? 'Untitled project'
}

export const companyName = (application: StudentApplicationListItem): string => {
  return application.project?.company?.name ?? 'Unknown company'
}

export const companyProfilePath = (application: StudentApplicationListItem): string | null => {
  const companyUserId = Number(application.project?.company?.user_id ?? 0)
  if (!Number.isFinite(companyUserId) || companyUserId <= 0) {
    return null
  }

  return `/companies/${companyUserId}/profile`
}

export const coverLetterPreview = (application: StudentApplicationListItem): string => {
  return String(application.cover_letter ?? '').trim()
}
