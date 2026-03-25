<template>
  <section class="surface-card p-6 sm:p-7 space-y-5">
    <div class="flex items-center justify-between gap-4">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Languages</h2>
      <BaseButton type="button" variant="secondary" size="sm" @click="$emit('add')"
        >Add language</BaseButton
      >
    </div>

    <div v-if="languages.length === 0" class="text-sm text-slate-500 dark:text-slate-400">No language added yet.</div>

    <div
      v-for="(language, index) in languages"
      :key="`lang-${index}`"
      class="grid gap-3 sm:grid-cols-[1fr_220px_auto] items-end"
    >
      <BaseInput
        :model-value="language.name"
        label="Language"
        placeholder="English"
        @update:modelValue="$emit('update-name', { index, value: $event })"
      />
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Level</label>
        <select
          :value="language.level"
          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          @change="
            $emit('update-level', { index, value: ($event.target as HTMLSelectElement).value })
          "
        >
          <option value="">Select level</option>
          <option value="A1">A1</option>
          <option value="A2">A2</option>
          <option value="B1">B1</option>
          <option value="B2">B2</option>
          <option value="C1">C1</option>
          <option value="C2">C2</option>
          <option value="native">Native</option>
        </select>
      </div>
      <BaseButton type="button" variant="ghost" size="sm" @click="$emit('remove', index)"
        >Remove</BaseButton
      >
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

interface StudentLanguage {
  name: string
  level: string
}

export default defineComponent({
  name: 'StudentLanguagesEditor',
  components: { BaseInput, BaseButton },
  props: {
    languages: {
      type: Array as PropType<StudentLanguage[]>,
      required: true,
    },
  },
  emits: ['add', 'remove', 'update-name', 'update-level'],
})
</script>
