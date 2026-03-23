<template>
  <div>
    <div v-if="applications.length === 0" class="text-center py-16 text-gray-500">
      <p class="text-4xl mb-3">📝</p>
      <p class="font-medium">No applications found</p>
    </div>

    <div v-else class="space-y-3">
      <ApplicationCard
        v-for="application in applications"
        :key="application.id"
        :application="application"
      >
        <template #actions>
          <BaseButton
            v-if="application.status === 'pending'"
            variant="secondary"
            size="sm"
            :loading="withdrawingId === application.id"
            @click="$emit('withdraw', application.id)"
          >
            Withdraw
          </BaseButton>
        </template>
      </ApplicationCard>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import ApplicationCard from '@/components/applications/ApplicationCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface ApplicationListItem {
  id: number
  status?: string
  [key: string]: unknown
}

export default defineComponent({
  name: 'StudentApplicationsList',
  components: { ApplicationCard, BaseButton },
  props: {
    applications: {
      type: Array as PropType<ApplicationListItem[]>,
      required: true,
    },
    withdrawingId: {
      type: Number as PropType<number | null>,
      default: null,
    },
  },
  emits: ['withdraw'],
})
</script>
