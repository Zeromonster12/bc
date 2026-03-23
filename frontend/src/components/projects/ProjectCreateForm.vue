<template>
  <form
    @submit.prevent="$emit('submit')"
    novalidate
    class="bg-white border border-gray-200 rounded-xl p-6 space-y-5"
  >
    <BaseInput
      :model-value="form.title"
      label="Project title"
      :error="errors.title"
      required
      @update:modelValue="$emit('update-field', { field: 'title', value: $event })"
    />

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
      <textarea
        :value="form.description"
        rows="4"
        class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
      <label class="block text-sm font-medium text-gray-700 mb-1">Requirements</label>
      <textarea
        :value="form.requirements"
        rows="4"
        class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
          :value="form.status"
          class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Max students</label>
        <input
          :value="form.max_students"
          type="number"
          min="1"
          max="20"
          class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
