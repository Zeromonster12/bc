<template>
  <section class="surface-card p-6 sm:p-7 space-y-5">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-lg font-semibold text-slate-900">Certifications</h2>
      <BaseButton type="button" variant="secondary" size="sm" @click="$emit('add')"
        >Add certification</BaseButton
      >
    </div>

    <div v-if="certifications.length === 0" class="text-sm text-slate-500">
      No certification added yet.
    </div>

    <div
      v-for="(certification, index) in certifications"
      :key="`cert-${index}`"
      class="border border-slate-200 rounded-xl p-4 space-y-3"
    >
      <div class="grid gap-3 sm:grid-cols-2">
        <BaseInput
          :model-value="certification.name"
          label="Certification name"
          placeholder="AWS Cloud Practitioner"
          @update:modelValue="$emit('update-field', { index, field: 'name', value: $event })"
        />
        <BaseInput
          :model-value="certification.issuer"
          label="Issuer"
          placeholder="Amazon"
          @update:modelValue="$emit('update-field', { index, field: 'issuer', value: $event })"
        />
        <BaseInput
          :model-value="certification.year"
          label="Year"
          type="number"
          @update:modelValue="$emit('update-field', { index, field: 'year', value: $event })"
        />
        <BaseInput
          :model-value="certification.url"
          label="Credential URL"
          type="url"
          @update:modelValue="$emit('update-field', { index, field: 'url', value: $event })"
        />
      </div>
      <div class="flex justify-end">
        <BaseButton type="button" variant="ghost" size="sm" @click="$emit('remove', index)"
          >Remove</BaseButton
        >
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface StudentCertification {
  name: string
  issuer: string
  year: string
  url: string
}

export default defineComponent({
  name: 'StudentCertificationsEditor',
  components: { BaseInput, BaseButton },
  props: {
    certifications: {
      type: Array as PropType<StudentCertification[]>,
      required: true,
    },
  },
  emits: ['add', 'remove', 'update-field'],
})
</script>
