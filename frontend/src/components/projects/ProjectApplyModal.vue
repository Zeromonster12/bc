<template>
  <BaseModal v-model="isOpen" :title="title">
    <div class="space-y-4">
      <BaseAlert v-if="errorMessage" type="error" :message="errorMessage" />
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Cover letter</label>
        <textarea
          :value="coverLetter"
          rows="6"
          class="w-full rounded-2xl bg-[#f1edf8] px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-400/30"
          placeholder="Tell the company why you're a great fit for this project... (min. 50 characters)"
          @input="$emit('update:coverLetter', ($event.target as HTMLTextAreaElement).value)"
        />
        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ coverLetter.length }} / 3000</p>
      </div>
    </div>
    <template #footer>
      <BaseButton
        variant="secondary"
        class="rounded-full! bg-[#e8e3f2]! text-[#4d466b]! border-0! hover:bg-[#ddd7f6]! dark:bg-slate-800! dark:text-slate-200! dark:hover:bg-slate-700!"
        @click="$emit('update:show', false)"
      >
        Cancel
      </BaseButton>
      <BaseButton
        variant="primary"
        class="rounded-full! bg-[#3f34a6]! text-white! hover:bg-[#352b91]! dark:bg-indigo-600! dark:hover:bg-indigo-500!"
        :loading="submitting"
        @click="$emit('submit')"
      >
        {{ submitLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

export default defineComponent({
  name: 'ProjectApplyModal',
  components: { BaseModal, BaseAlert, BaseButton },
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    coverLetter: {
      type: String,
      default: '',
    },
    submitting: {
      type: Boolean,
      default: false,
    },
    errorMessage: {
      type: String,
      default: '',
    },
    title: {
      type: String,
      default: 'Apply to this project',
    },
    submitLabel: {
      type: String,
      default: 'Submit application',
    },
  },
  emits: ['update:show', 'update:coverLetter', 'submit'],
  computed: {
    isOpen: {
      get(): boolean {
        return this.show
      },
      set(value: boolean) {
        this.$emit('update:show', value)
      },
    },
  },
})
</script>
