<template>
  <div class="flex items-start justify-between gap-4">
    <div>
      <div class="flex items-center gap-3 mb-2">
        <ProjectStatusBadge :status="project.status" />
        <span class="text-sm text-gray-400">{{ project.company?.name }}</span>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">{{ project.title }}</h1>
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
