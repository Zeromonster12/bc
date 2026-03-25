<template>
  <form
    @submit.prevent="$emit('submit')"
    novalidate
    class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700/70 dark:bg-slate-900/90"
  >
    <div class="surface-card p-3 sm:p-4">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="rounded-full px-5 py-2 text-sm font-medium transition"
          :class="
            activeTab === tab.id
              ? 'bg-[#4e3aba] text-white shadow-sm'
              : 'bg-[#4e3aba]/10 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
          "
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <section v-show="activeTab === 'identity'" class="surface-card space-y-5 p-6 sm:p-7">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Identity</h2>

      <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div
          class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 dark:bg-slate-800"
        >
          <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-cover" />
          <span v-else class="text-2xl">🏢</span>
        </div>
        <div class="space-y-2">
          <label
            for="company-logo-upload"
            class="inline-flex cursor-pointer items-center justify-center rounded-full bg-[#4e3aba] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#3f2ea1]"
          >
            Choose company logo
          </label>
          <input
            id="company-logo-upload"
            type="file"
            accept="image/*"
            class="sr-only"
            @change="$emit('logo-change', $event)"
          />
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ logoFileName || 'No logo selected' }}</p>
          <p v-if="errors.logo" class="text-xs text-red-600">{{ errors.logo }}</p>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput
          :model-value="form.name"
          label="Company name"
          :error="errors.name"
          required
          @update:modelValue="emitField('name', $event)"
        />
        <BaseInput
          :model-value="form.tagline"
          label="Tagline"
          :error="errors.tagline"
          placeholder="Building products that matter"
          @update:modelValue="emitField('tagline', $event)"
        />
        <BaseInput
          :model-value="form.industry"
          label="Industry"
          :error="errors.industry"
          @update:modelValue="emitField('industry', $event)"
        />
        <BaseInput
          :model-value="form.company_size"
          label="Company size"
          :error="errors.company_size"
          placeholder="e.g. 11-50"
          @update:modelValue="emitField('company_size', $event)"
        />
        <BaseInput
          :model-value="form.founded_year"
          label="Founded year"
          type="number"
          :error="errors.founded_year"
          @update:modelValue="emitField('founded_year', $event)"
        />
      </div>
    </section>

    <section v-show="activeTab === 'overview'" class="surface-card space-y-5 p-6 sm:p-7">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Overview and Culture</h2>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Description</label>
        <textarea
          :value="form.description"
          rows="4"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
          placeholder="Describe what your company does and who your products are for"
          @input="emitField('description', ($event.target as HTMLTextAreaElement).value)"
        />
        <p v-if="errors.description" class="mt-1 text-xs text-red-600">{{ errors.description }}</p>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Mission</label>
          <textarea
            :value="form.mission"
            rows="4"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
            placeholder="What drives your team"
            @input="emitField('mission', ($event.target as HTMLTextAreaElement).value)"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Values</label>
          <textarea
            :value="form.values"
            rows="4"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
            placeholder="Team values and ways of working"
            @input="emitField('values', ($event.target as HTMLTextAreaElement).value)"
          />
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">Benefits</label>
        <textarea
          :value="form.benefits"
          rows="3"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
          placeholder="What students can expect: mentoring, flexibility, growth, etc."
          @input="emitField('benefits', ($event.target as HTMLTextAreaElement).value)"
        />
      </div>
    </section>

    <section v-show="activeTab === 'hiring'" class="surface-card space-y-5 p-6 sm:p-7">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Hiring and Team</h2>

      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput
          :model-value="form.hiring_focus"
          label="Hiring focus"
          :error="errors.hiring_focus"
          placeholder="Frontend, backend, QA"
          @update:modelValue="emitField('hiring_focus', $event)"
        />
        <BaseInput
          :model-value="form.tech_stack"
          label="Tech stack"
          :error="errors.tech_stack"
          placeholder="Laravel, Vue, PostgreSQL"
          @update:modelValue="emitField('tech_stack', $event)"
        />
        <BaseInput
          :model-value="form.remote_policy"
          label="Remote policy"
          :error="errors.remote_policy"
          placeholder="Remote / Hybrid / On-site"
          @update:modelValue="emitField('remote_policy', $event)"
        />
        <BaseInput
          :model-value="form.contact_email"
          label="Hiring contact email"
          type="email"
          :error="errors.contact_email"
          @update:modelValue="emitField('contact_email', $event)"
        />
        <BaseInput
          :model-value="form.contact_phone"
          label="Hiring contact phone"
          :error="errors.contact_phone"
          @update:modelValue="emitField('contact_phone', $event)"
        />
      </div>
    </section>

    <section v-show="activeTab === 'presence'" class="surface-card space-y-5 p-6 sm:p-7">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Online Presence</h2>

      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput
          :model-value="form.website"
          label="Website"
          type="url"
          :error="errors.website"
          @update:modelValue="emitField('website', $event)"
        />
        <BaseInput
          :model-value="form.careers_url"
          label="Careers page"
          type="url"
          :error="errors.careers_url"
          @update:modelValue="emitField('careers_url', $event)"
        />
        <BaseInput
          :model-value="form.linkedin_url"
          label="LinkedIn URL"
          type="url"
          :error="errors.linkedin_url"
          @update:modelValue="emitField('linkedin_url', $event)"
        />
        <BaseInput
          :model-value="form.headquarters_city"
          label="Headquarters city"
          :error="errors.headquarters_city"
          @update:modelValue="emitField('headquarters_city', $event)"
        />
        <BaseInput
          :model-value="form.headquarters_country"
          label="Headquarters country"
          :error="errors.headquarters_country"
          @update:modelValue="emitField('headquarters_country', $event)"
        />
      </div>
    </section>

    <div class="surface-card p-4 sm:p-5">
      <div class="flex justify-end">
        <BaseButton type="submit" variant="primary" :loading="saving" class="rounded-full px-6">
          {{ saveButtonLabel }}
        </BaseButton>
      </div>
    </div>
  </form>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { CompanyProfileForm } from '@/services/profile/CompanyProfileFormService'

type CompanyTab = 'identity' | 'overview' | 'hiring' | 'presence'

export default defineComponent({
  name: 'CompanyProfileForm',
  components: { BaseInput, BaseButton },
  props: {
    form: {
      type: Object as PropType<CompanyProfileForm>,
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
    logoFileName: {
      type: String,
      default: '',
    },
    saving: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      activeTab: 'identity' as CompanyTab,
      tabs: [
        { id: 'identity' as CompanyTab, label: 'Identity' },
        { id: 'overview' as CompanyTab, label: 'Overview' },
        { id: 'hiring' as CompanyTab, label: 'Hiring' },
        { id: 'presence' as CompanyTab, label: 'Presence' },
      ],
    }
  },
  computed: {
    saveButtonLabel(): string {
      if (this.activeTab === 'identity') return 'Save identity'
      if (this.activeTab === 'overview') return 'Save overview'
      if (this.activeTab === 'hiring') return 'Save hiring details'
      return 'Save online presence'
    },
  },
  methods: {
    emitField(field: keyof CompanyProfileForm, value: string) {
      this.$emit('update-field', { field, value })
    },
  },
  emits: ['submit', 'logo-change', 'update-field'],
})
</script>
