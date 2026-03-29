import type { ApplicationTaskPriority, ApplicationTaskStatus } from '@/services/applications/ApplicationService'
import { taskPriorityLabel, taskStatusLabel } from '@/services/applications/TaskBoardLabelService'

export function useTaskBoardLabels() {
  const statusLabel = (status: ApplicationTaskStatus): string => {
    return taskStatusLabel(status)
  }

  const statusIcon = (status: ApplicationTaskStatus): 'Folder' | 'Pencil' | 'CheckCircle2' => {
    if (status === 'todo') return 'Folder'
    if (status === 'in_progress') return 'Pencil'
    return 'CheckCircle2'
  }

  const statusIconClass = (status: ApplicationTaskStatus): string => {
    if (status === 'todo') return 'h-4 w-4 text-[#4e3aba]'
    if (status === 'in_progress') return 'h-4 w-4 text-amber-600 dark:text-amber-400'
    return 'h-4 w-4 text-emerald-600 dark:text-emerald-400'
  }

  const priorityLabel = (priority: ApplicationTaskPriority | string): string => {
    return taskPriorityLabel(priority)
  }

  const priorityPillClass = (priority: ApplicationTaskPriority | string): string => {
    if (priority === 'urgent') {
      return 'inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
    }
    if (priority === 'high') {
      return 'inline-flex items-center rounded-full bg-orange-100 px-2 py-1 text-[11px] font-semibold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300'
    }
    if (priority === 'medium') {
      return 'inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
    }
    return 'inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300'
  }

  const statusPillClass = (status: ApplicationTaskStatus): string => {
    if (status === 'todo') {
      return 'inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-300'
    }
    if (status === 'in_progress') {
      return 'inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
    }
    return 'inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
  }

  return {
    statusLabel,
    statusIcon,
    statusIconClass,
    priorityLabel,
    priorityPillClass,
    statusPillClass,
  }
}
