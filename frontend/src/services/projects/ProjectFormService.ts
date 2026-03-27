import { addUniqueTrimmedTag } from '@/services/shared/FormUtilsService'

export interface CreateProjectForm {
  title: string
  description: string
  requirements: string
  location: string
  location_strategy: 'remote' | 'onsite' | 'hybrid'
  industry: string
  internship_duration: string
  tech_stack: string[]
  status: string
  max_students: number
}

export const createDefaultProjectForm = (): CreateProjectForm => ({
  title: '',
  description: '',
  requirements: '',
  location: '',
  location_strategy: 'remote',
  industry: 'Technology & Software',
  internship_duration: '3 Months (Summer)',
  tech_stack: [],
  status: 'draft',
  max_students: 1,
})

export const addTechTag = (form: CreateProjectForm, rawValue: string): CreateProjectForm => {
  return {
    ...form,
    tech_stack: addUniqueTrimmedTag(form.tech_stack, rawValue),
  }
}
