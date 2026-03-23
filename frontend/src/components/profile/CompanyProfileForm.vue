<template>
  <form
    @submit.prevent="$emit('submit')"
    novalidate
    class="bg-white border border-gray-200 rounded-xl p-6 space-y-5"
  >
    <div class="flex items-center gap-5">
      <div
        class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden shrink-0"
      >
        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="w-full h-full object-cover" />
        <span v-else class="text-2xl">🏢</span>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Company logo</label>
        <input
          type="file"
          accept="image/*"
          @change="$emit('logo-change', $event)"
          class="text-sm text-gray-500"
        />
        <p v-if="errors.logo" class="mt-1 text-xs text-red-600">{{ errors.logo }}</p>
      </div>
    </div>

    <BaseInput
      :model-value="form.name"
      label="Company name"
      :error="errors.name"
      required
      @update:modelValue="$emit('update-field', { field: 'name', value: $event })"
    />
    <BaseInput
      :model-value="form.website"
      label="Website"
      type="url"
      :error="errors.website"
      @update:modelValue="$emit('update-field', { field: 'website', value: $event })"
    />
    <BaseInput
      :model-value="form.industry"
      label="Industry"
      :error="errors.industry"
      @update:modelValue="$emit('update-field', { field: 'industry', value: $event })"
    />

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
      <textarea
        :value="form.description"
        rows="4"
        class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        placeholder="Describe your company, mission, and the types of projects you offer..."
        @input="
          $emit('update-field', {
            field: 'description',
            value: ($event.target as HTMLTextAreaElement).value,
          })
        "
      />
      <p v-if="errors.description" class="mt-1 text-xs text-red-600">{{ errors.description }}</p>
    </div>

    <div class="flex justify-end pt-2">
      <BaseButton type="submit" variant="primary" :loading="saving">Save changes</BaseButton>
    </div>
  </form>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface CompanyForm {
  name: string
  description: string
  website: string
  industry: string
}

export default defineComponent({
  name: 'CompanyProfileForm',
  components: { BaseInput, BaseButton },
  props: {
    form: {
      type: Object as PropType<CompanyForm>,
      required: true,
    },
    errors: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    logoPreview: {
      type: String,
      default: '',
    },
    saving: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['submit', 'logo-change', 'update-field'],
})
</script>
