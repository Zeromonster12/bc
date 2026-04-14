<template>
  <section class="space-y-5 rounded-3xl bg-white p-6 sm:p-7 dark:bg-slate-900">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Projects and Experience</h2>
      <BaseButton type="button" variant="secondary" size="sm" @click="$emit('add')"
        >Add project</BaseButton
      >
    </div>

    <div v-if="projects.length === 0" class="text-sm text-slate-500 dark:text-slate-400">No project added yet.</div>

    <div
      v-for="(project, index) in projects"
      :key="`project-${index}`"
      class="space-y-3 rounded-2xl bg-[#f1edf8] p-4 dark:bg-slate-800"
    >
      <div class="grid gap-3 sm:grid-cols-2">
        <BaseInput
          :model-value="project.title"
          label="Project title"
          placeholder="Team collaboration app"
          @update:modelValue="$emit('update-field', { index, field: 'title', value: $event })"
        />
        <BaseInput
          :model-value="project.link"
          label="Project link"
          type="url"
          @update:modelValue="$emit('update-field', { index, field: 'link', value: $event })"
        />
        <BaseInput
          :model-value="project.tech"
          label="Technologies"
          placeholder="Vue, Laravel, PostgreSQL"
          @update:modelValue="$emit('update-field', { index, field: 'tech', value: $event })"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Description</label>
        <textarea
          :value="project.description"
          rows="3"
          class="w-full rounded-2xl bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
          placeholder="What was your role, what was delivered, what impact did it have"
          @input="
            $emit('update-field', {
              index,
              field: 'description',
              value: ($event.target as HTMLTextAreaElement).value,
            })
          "
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

interface StudentProject {
  title: string
  tech: string
  link: string
  description: string
}

export default defineComponent({
  name: 'StudentProjectsEditor',
  components: { BaseInput, BaseButton },
  props: {
    projects: {
      type: Array as PropType<StudentProject[]>,
      required: true,
    },
  },
  emits: ['add', 'remove', 'update-field'],
})
</script>
