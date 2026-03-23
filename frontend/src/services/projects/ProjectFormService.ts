import { addUniqueTrimmedTag } from '@/services/shared/FormUtilsService'

export interface CreateProjectForm {
  title: string
  description: string
  requirements: string
  tech_stack: string[]
  status: string
  max_students: number
  deadline: string
}

export const createDefaultProjectForm = (): CreateProjectForm => ({
  title: '',
  description: '',
  requirements: '',
  tech_stack: [],
  status: 'draft',
  max_students: 1,
  deadline: '',
})

export const addTechTag = (form: CreateProjectForm, rawValue: string): CreateProjectForm => {
  return {
    ...form,
    tech_stack: addUniqueTrimmedTag(form.tech_stack, rawValue),
  }
}
