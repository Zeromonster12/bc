import { addUniqueTrimmedTag } from '@/services/shared/FormUtilsService'

export interface CreateProjectForm {
  title: string
  description: string
  requirements: string
  location: string
  tech_stack: string[]
  status: string
  max_students: number
}

export const createDefaultProjectForm = (): CreateProjectForm => ({
  title: '',
  description: '',
  requirements: '',
  location: '',
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
