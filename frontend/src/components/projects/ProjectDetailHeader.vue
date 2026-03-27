<template>
  <div class="flex items-start justify-between gap-4">
    <div>
      <div class="flex items-center gap-3 mb-2">
        <ProjectStatusBadge :status="project.status ?? 'draft'" />
        <RouterLink
          v-if="project.company?.user_id"
          :to="`/companies/${project.company.user_id}/profile`"
          class="text-sm text-gray-400 transition hover:text-indigo-600 dark:text-slate-500 dark:hover:text-indigo-300"
        >
          {{ project.company?.name }}
        </RouterLink>
        <span v-else class="text-sm text-gray-400 dark:text-slate-500">{{ project.company?.name }}</span>
      </div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ project.title }}</h1>
    </div>

    <div class="flex gap-2 shrink-0">
      <template v-if="isOwnerCompany">
        <RouterLink :to="'/projects/' + project.id + '/edit'">
          <BaseButton variant="secondary" size="sm">Edit</BaseButton>
        </RouterLink>
        <BaseButton variant="danger" size="sm" :loading="deleting" @click="$emit('delete')">
          Delete
        </BaseButton>
      </template>

      <template v-if="canStudentApply">
        <BaseButton variant="primary" size="sm" @click="$emit('open-apply')" :disabled="hasApplied">
          {{ hasApplied ? 'Applied' : 'Apply now' }}
        </BaseButton>
      </template>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import ProjectStatusBadge from '@/components/projects/ProjectStatusBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface ProjectDetailHeaderItem {
  id: number
  title?: string
  status?: string
  company?: {
    user_id?: number
    name?: string
  }
}

export default defineComponent({
  name: 'ProjectDetailHeader',
  components: { ProjectStatusBadge, BaseButton },
  props: {
    project: {
      type: Object as PropType<ProjectDetailHeaderItem>,
      required: true,
    },
    isOwnerCompany: {
      type: Boolean,
      default: false,
    },
    canStudentApply: {
      type: Boolean,
      default: false,
    },
    hasApplied: {
      type: Boolean,
      default: false,
    },
    deleting: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['delete', 'open-apply'],
})
</script>
