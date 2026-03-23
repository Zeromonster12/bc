import type { ProjectFilters } from '@/services/projects/ProjectService'

export const buildProjectsListParams = (
  filters: Record<string, unknown>,
  page = 1,
): ProjectFilters => {
  return {
    ...(filters as ProjectFilters),
    per_page: 12,
    page,
  }
}
