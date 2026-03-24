<template>
  <form
    @submit.prevent="$emit('submit')"
    novalidate
    class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700/70 dark:bg-slate-900/90"
  >
    <BaseInput
      :model-value="form.title"
      label="Project title"
      :error="errors.title"
      required
      @update:modelValue="$emit('update-field', { field: 'title', value: $event })"
    />

    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Description</label>
      <textarea
        :value="form.description"
        rows="4"
        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
        placeholder="Describe the project..."
        @input="
          $emit('update-field', {
            field: 'description',
            value: ($event.target as HTMLTextAreaElement).value,
          })
        "
      />
      <p v-if="errors.description" class="mt-1 text-xs text-red-600">{{ errors.description }}</p>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Requirements</label>
      <textarea
        :value="form.requirements"
        rows="4"
        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
        placeholder="List student requirements..."
        @input="
          $emit('update-field', {
            field: 'requirements',
            value: ($event.target as HTMLTextAreaElement).value,
          })
        "
      />
      <p v-if="errors.requirements" class="mt-1 text-xs text-red-600">{{ errors.requirements }}</p>
    </div>

    <ProfileTagInput
      label="Tech stack"
      tone="indigo"
      :tags="form.tech_stack"
      :input-value="techInput"
      placeholder="e.g. Vue.js"
      :error="errors.tech_stack"
      @update:inputValue="$emit('update:techInput', $event)"
      @add="$emit('add-tech')"
      @remove="$emit('remove-tech', $event)"
    />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Status</label>
        <select
          :value="form.status"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          @change="
            $emit('update-field', {
              field: 'status',
              value: ($event.target as HTMLSelectElement).value,
            })
          "
        >
          <option value="draft">Draft</option>
          <option value="open">Open</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Max students</label>
        <input
          :value="form.max_students"
          type="number"
          min="1"
          max="20"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          @input="
            $emit('update-field', {
              field: 'max_students',
              value: Number(($event.target as HTMLInputElement).value) || 1,
            })
          "
        />
        <p v-if="errors.max_students" class="mt-1 text-xs text-red-600">
          {{ errors.max_students }}
        </p>
      </div>
    </div>

    <BaseInput
      :model-value="form.deadline"
      label="Application deadline"
      type="date"
      :error="errors.deadline"
      required
      @update:modelValue="$emit('update-field', { field: 'deadline', value: $event })"
    />

    <div class="flex justify-end gap-3 pt-2">
      <BaseButton type="button" variant="secondary" @click="$emit('cancel')">Cancel</BaseButton>
      <BaseButton type="submit" variant="primary" :loading="loading">Create project</BaseButton>
    </div>
  </form>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ProfileTagInput from '@/components/profile/ProfileTagInput.vue'

interface ProjectFormData {
  title: string
  description: string
  requirements: string
  tech_stack: string[]
  status: string
  max_students: number
  deadline: string
}

export default defineComponent({
  name: 'ProjectCreateForm',
  components: { BaseInput, BaseButton, ProfileTagInput },
  props: {
    form: {
      type: Object as PropType<ProjectFormData>,
      required: true,
    },
    techInput: {
      type: String,
      default: '',
    },
    errors: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['submit', 'cancel', 'update-field', 'update:techInput', 'add-tech', 'remove-tech'],
})
</script>
