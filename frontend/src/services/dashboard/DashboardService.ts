export interface DashboardStats {
  openProjects: number
  myApplications: number
  acceptedApplications: number
  myProjects: number
  totalApplications: number
  totalUsers: number
  totalProjects: number
}

interface StatusProject {
  status?: string
}

interface StatusApplication {
  status?: string
}

export const createDefaultDashboardStats = (): DashboardStats => ({
  openProjects: 0,
  myApplications: 0,
  acceptedApplications: 0,
  myProjects: 0,
  totalApplications: 0,
  totalUsers: 0,
  totalProjects: 0,
})

export const countOpenProjects = (projects: StatusProject[]): number => {
  return projects.filter((project) => project.status === 'open').length
}

export const countAcceptedApplications = (applications: StatusApplication[]): number => {
  return applications.filter((application) => application.status === 'accepted').length
}
