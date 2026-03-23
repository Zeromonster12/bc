<template>
  <section class="space-y-3">
    <div
      v-if="!selectedProject"
      class="text-center py-16 text-gray-500 bg-white border border-gray-200 rounded-xl"
    >
      <p class="font-medium">Select a project to view applicants</p>
    </div>

    <template v-else>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ selectedProject.title }}</h2>
        <p class="text-sm text-gray-500 mt-1">Applicants for this project are listed below.</p>
      </div>

      <div
        v-if="applications.length === 0"
        class="text-center py-16 text-gray-500 bg-white border border-gray-200 rounded-xl"
      >
        <p class="text-4xl mb-3">📝</p>
        <p class="font-medium">No applicants for this project</p>
      </div>

      <div v-else class="space-y-3">
        <ApplicationCard
          v-for="application in applications"
          :key="application.id"
          :application="application"
        >
          <template #actions>
            <div class="flex gap-2 mt-3" v-if="application.status === 'pending'">
              <BaseButton
                variant="primary"
                size="sm"
                :loading="updatingId === application.id && updatingStatus === 'accepted'"
                @click="$emit('update-status', { id: application.id, status: 'accepted' })"
              >
                Accept
              </BaseButton>
              <BaseButton
                variant="danger"
                size="sm"
                :loading="updatingId === application.id && updatingStatus === 'rejected'"
                @click="$emit('update-status', { id: application.id, status: 'rejected' })"
              >
                Reject
              </BaseButton>
            </div>
          </template>
        </ApplicationCard>
      </div>
    </template>
  </section>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import ApplicationCard from '@/components/applications/ApplicationCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface CompanyProject {
  id: number
  title: string
}

interface ApplicationListItem {
  id: number
  status?: string
  [key: string]: unknown
}

export default defineComponent({
  name: 'CompanyApplicantsPanel',
  components: { ApplicationCard, BaseButton },
  props: {
    selectedProject: {
      type: Object as PropType<CompanyProject | null>,
      default: null,
    },
    applications: {
      type: Array as PropType<ApplicationListItem[]>,
      required: true,
    },
    updatingId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    updatingStatus: {
      type: String,
      default: '',
    },
  },
  emits: ['update-status'],
})
</script>
