import type { ApplicationTaskPriority, ApplicationTaskStatus } from '@/services/applications/ApplicationService'

export const taskStatusLabel = (status: ApplicationTaskStatus): string => {
  if (status === 'todo') return 'TO DO'
  if (status === 'in_progress') return 'IN PROGRESS'
  return 'COMPLETED'
}

export const taskPriorityLabel = (priority: ApplicationTaskPriority | string): string => {
  if (priority === 'low') return 'Low'
  if (priority === 'medium') return 'Medium'
  if (priority === 'high') return 'High'
  if (priority === 'urgent') return 'Urgent'
  return String(priority)
}
