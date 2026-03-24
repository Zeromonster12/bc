<template>
  <form
    @submit.prevent="$emit('submit')"
    novalidate
    class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700/70 dark:bg-slate-900/90"
  >
    <div class="flex items-center gap-5">
      <div
        class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 dark:bg-slate-800"
      >
        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="w-full h-full object-cover" />
        <span v-else class="text-2xl">🏢</span>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Company logo</label>
        <input
          type="file"
          accept="image/*"
          @change="$emit('logo-change', $event)"
          class="text-sm text-gray-500 dark:text-slate-400"
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
      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Description</label>
      <textarea
        :value="form.description"
        rows="4"
        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
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
