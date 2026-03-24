<template>
  <BaseModal v-model="isOpen" title="Apply to this project">
    <div class="space-y-4">
      <BaseAlert v-if="errorMessage" type="error" :message="errorMessage" />
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Cover letter</label>
        <textarea
          :value="coverLetter"
          rows="6"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
          placeholder="Tell the company why you're a great fit for this project... (min. 50 characters)"
          @input="$emit('update:coverLetter', ($event.target as HTMLTextAreaElement).value)"
        />
        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ coverLetter.length }} / 3000</p>
      </div>
    </div>
    <template #footer>
      <BaseButton variant="secondary" @click="$emit('update:show', false)">Cancel</BaseButton>
      <BaseButton variant="primary" :loading="submitting" @click="$emit('submit')"
        >Submit application</BaseButton
      >
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
