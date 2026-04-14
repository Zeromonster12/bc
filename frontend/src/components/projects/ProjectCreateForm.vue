<template>
  <form @submit.prevent="$emit('submit')" novalidate class="space-y-6">
    <section class="rounded-3xl bg-white p-6 dark:bg-slate-900/90 sm:p-8">
      <div class="space-y-6">
        <div class="flex items-center gap-3">
          <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8e1ff] text-[#4b35cb] dark:bg-indigo-500/20 dark:text-indigo-200">
            <FileText class="h-5 w-5" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-[#1f1a38] dark:text-slate-100">Project overview</h2>
            <p class="text-sm text-[#6c6786] dark:text-slate-400">Define the core details students will see first.</p>
          </div>
        </div>

        <BaseInput
          :model-value="form.title"
          label="Project title"
          label-class="text-sm font-semibold tracking-normal text-[#433d5c] dark:text-slate-300"
          :error="errors.title"
          required
          placeholder="e.g. AI-supported Quality Assurance Dashboard"
          @update:modelValue="$emit('update-field', { field: 'title', value: $event })"
        />

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Industry<span class="ml-0.5 text-red-500">*</span></label>
            <div class="project-custom-dropdown relative">
              <button
                type="button"
                class="flex w-full items-center justify-between rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-left text-sm font-medium text-[#2f2a47] transition hover:bg-[#e8e2f3] dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                @click="toggleDropdown('industry')"
              >
                <span>{{ form.industry }}</span>
                <ChevronDown class="h-4 w-4" />
              </button>

              <div
                v-if="openDropdown === 'industry'"
                class="absolute z-20 mt-2 w-full rounded-xl border border-[#ddd7ef] bg-white p-1 dark:border-slate-700 dark:bg-slate-900"
              >
                <button
                  v-for="option in industryOptions"
                  :key="option"
                  type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition hover:bg-[#f3eeff] dark:hover:bg-slate-800"
                  @click="selectIndustry(option)"
                >
                  <span>{{ option }}</span>
                  <Check v-if="form.industry === option" class="h-4 w-4 text-indigo-600 dark:text-indigo-300" />
                </button>
              </div>
            </div>
          </div>
          <div>
            <label class="mb-2 block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Internship duration<span class="ml-0.5 text-red-500">*</span></label>
            <div class="project-custom-dropdown relative">
              <button
                type="button"
                class="flex w-full items-center justify-between rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-left text-sm font-medium text-[#2f2a47] transition hover:bg-[#e8e2f3] dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                @click="toggleDropdown('duration')"
              >
                <span>{{ form.internship_duration || 'Select duration' }}</span>
                <ChevronDown class="h-4 w-4" />
              </button>

              <div
                v-if="openDropdown === 'duration'"
                class="absolute z-20 mt-2 w-full rounded-xl border border-[#ddd7ef] bg-white p-1 dark:border-slate-700 dark:bg-slate-900"
              >
                <button
                  v-for="option in durationOptions"
                  :key="option"
                  type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition hover:bg-[#f3eeff] dark:hover:bg-slate-800"
                  @click="selectDuration(option)"
                >
                  <span>{{ option }}</span>
                  <Check v-if="form.internship_duration === option" class="h-4 w-4 text-indigo-600 dark:text-indigo-300" />
                </button>
                <button
                  type="button"
                  class="mt-1 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition hover:bg-[#f3eeff] dark:hover:bg-slate-800"
                  @click="selectDurationCustom()"
                >
                  <span>Custom</span>
                  <Check v-if="isCustomDuration" class="h-4 w-4 text-indigo-600 dark:text-indigo-300" />
                </button>
              </div>
            </div>

            <input
              v-if="isCustomDuration"
              :value="form.internship_duration"
              type="text"
              class="mt-2 w-full rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-sm text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100"
              placeholder="Type custom duration"
              @input="
                $emit('update-field', {
                  field: 'internship_duration',
                  value: ($event.target as HTMLInputElement).value,
                })
              "
            />
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Location strategy<span class="ml-0.5 text-red-500">*</span></label>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <button
              type="button"
              class="rounded-xl border px-4 py-3 text-center transition"
              :class="
                form.location_strategy === 'remote'
                  ? 'border-[#cbc0f4] bg-[#ece5ff] text-[#4533a2] dark:border-indigo-400 dark:bg-indigo-500/20 dark:text-indigo-200'
                  : 'border-transparent bg-[#f1edf8] text-[#4d4766] hover:bg-[#e8e2f3] dark:bg-slate-800 dark:text-slate-300'
              "
              @click="$emit('update-field', { field: 'location_strategy', value: 'remote' })"
            >
              <Globe class="mx-auto mb-1 h-4 w-4" />
              <span class="text-sm font-semibold">Remote</span>
            </button>

            <button
              type="button"
              class="rounded-xl border px-4 py-3 text-center transition"
              :class="
                form.location_strategy === 'onsite'
                  ? 'border-[#cbc0f4] bg-[#ece5ff] text-[#4533a2] dark:border-indigo-400 dark:bg-indigo-500/20 dark:text-indigo-200'
                  : 'border-transparent bg-[#f1edf8] text-[#4d4766] hover:bg-[#e8e2f3] dark:bg-slate-800 dark:text-slate-300'
              "
              @click="$emit('update-field', { field: 'location_strategy', value: 'onsite' })"
            >
              <MapPin class="mx-auto mb-1 h-4 w-4" />
              <span class="text-sm font-semibold">On-site</span>
            </button>

            <button
              type="button"
              class="rounded-xl border px-4 py-3 text-center transition"
              :class="
                form.location_strategy === 'hybrid'
                  ? 'border-[#cbc0f4] bg-[#ece5ff] text-[#4533a2] dark:border-indigo-400 dark:bg-indigo-500/20 dark:text-indigo-200'
                  : 'border-transparent bg-[#f1edf8] text-[#4d4766] hover:bg-[#e8e2f3] dark:bg-slate-800 dark:text-slate-300'
              "
              @click="$emit('update-field', { field: 'location_strategy', value: 'hybrid' })"
            >
              <Building2 class="mx-auto mb-1 h-4 w-4" />
              <span class="text-sm font-semibold">Hybrid</span>
            </button>
          </div>
        </div>

        <div class="grid gap-4" :class="showStatusField ? 'sm:grid-cols-2' : 'sm:grid-cols-1'">
          <div v-if="showStatusField">
            <label class="mb-2 block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Project status<span class="ml-0.5 text-red-500">*</span></label>
            <div class="project-custom-dropdown relative">
              <button
                type="button"
                class="flex w-full items-center justify-between rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-left text-sm text-[#2f2a47] transition hover:bg-[#e8e2f3] dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                @click="toggleDropdown('status')"
              >
                <span class="capitalize">{{ form.status }}</span>
                <ChevronDown class="h-4 w-4" />
              </button>

              <div
                v-if="openDropdown === 'status'"
                class="absolute z-20 mt-2 w-full rounded-xl border border-[#ddd7ef] bg-white p-1 dark:border-slate-700 dark:bg-slate-900"
              >
                <button
                  v-for="option in statusOptions"
                  :key="option"
                  type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm capitalize transition hover:bg-[#f3eeff] dark:hover:bg-slate-800"
                  @click="selectStatus(option)"
                >
                  <span>{{ option }}</span>
                  <Check v-if="form.status === option" class="h-4 w-4 text-indigo-600 dark:text-indigo-300" />
                </button>
              </div>
            </div>
          </div>
          <div>
            <label class="mb-2 block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Max students<span class="ml-0.5 text-red-500">*</span></label>
            <input
              :value="form.max_students"
              type="number"
              min="1"
              max="20"
              class="w-full rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-sm text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100"
              @input="
                $emit('update-field', {
                  field: 'max_students',
                  value: Number(($event.target as HTMLInputElement).value) || 1,
                })
              "
            />
            <p v-if="errors.max_students" class="mt-1 text-xs text-red-600">{{ errors.max_students }}</p>
          </div>
        </div>

        <BaseInput
          :model-value="form.location"
          label="Full address"
          label-class="text-sm font-semibold tracking-normal text-[#433d5c] dark:text-slate-300"
          :error="errors.location"
          placeholder="e.g. Mlynske nivy 16, 821 09 Bratislava"
          @update:modelValue="$emit('update-field', { field: 'location', value: $event })"
        />

        <div>
          <div class="mb-2 flex items-center justify-between gap-3">
            <label class="block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Project description<span class="ml-0.5 text-red-500">*</span></label>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#e9e4f3] text-slate-700 transition hover:bg-[#ddd5ea] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                @mousedown.prevent
                @click="formatEditor('description', 'bold')"
              >
                <Bold class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#e9e4f3] text-slate-700 transition hover:bg-[#ddd5ea] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                @mousedown.prevent
                @click="formatEditor('description', 'italic')"
              >
                <Italic class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#e9e4f3] text-slate-700 transition hover:bg-[#ddd5ea] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                @mousedown.prevent
                @click="formatEditor('description', 'list')"
              >
                <List class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
          <div
            ref="descriptionTextarea"
            contenteditable="true"
            class="wysiwyg-editor min-h-36 w-full rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-sm text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100"
            data-placeholder="Describe the project goals, outcomes and expected day-to-day work."
            @input="onEditorInput('description')"
            @focus="activeEditor = 'description'"
            @blur="activeEditor = null"
          ></div>
          <p v-if="errors.description" class="mt-1 text-xs text-red-600">{{ errors.description }}</p>
        </div>
      </div>
    </section>

    <section class="rounded-3xl bg-white p-6 dark:bg-slate-900/90 sm:p-8">
      <div class="space-y-6">
        <div class="flex items-center gap-3">
          <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8e1ff] text-[#4b35cb] dark:bg-violet-500/20 dark:text-violet-200">
            <BriefcaseBusiness class="h-5 w-5" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-[#1f1a38] dark:text-slate-100">Role and requirements</h2>
            <p class="text-sm text-[#6c6786] dark:text-slate-400">Set clear expectations to attract the right candidates.</p>
          </div>
        </div>

        <div>
          <div class="mb-2 flex items-center justify-between gap-3">
            <label class="block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Requirements</label>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#e9e4f3] text-slate-700 transition hover:bg-[#ddd5ea] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                @mousedown.prevent
                @click="formatEditor('requirements', 'bold')"
              >
                <Bold class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#e9e4f3] text-slate-700 transition hover:bg-[#ddd5ea] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                @mousedown.prevent
                @click="formatEditor('requirements', 'italic')"
              >
                <Italic class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#e9e4f3] text-slate-700 transition hover:bg-[#ddd5ea] dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
                @mousedown.prevent
                @click="formatEditor('requirements', 'list')"
              >
                <List class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
          <div
            ref="requirementsTextarea"
            contenteditable="true"
            class="wysiwyg-editor min-h-36 w-full rounded-xl border border-transparent bg-[#f1edf8] px-4 py-3 text-sm text-[#2f2a47] focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-slate-800 dark:text-slate-100"
            data-placeholder="List required skills, expected experience and preferred tools."
            @input="onEditorInput('requirements')"
            @focus="activeEditor = 'requirements'"
            @blur="activeEditor = null"
          ></div>
          <p v-if="errors.requirements" class="mt-1 text-xs text-red-600">{{ errors.requirements }}</p>
        </div>

        <div>
          <label class="mb-2 block text-sm font-semibold text-[#433d5c] dark:text-slate-300">Required skills</label>
          <div class="rounded-2xl bg-[#f1edf8] p-5 dark:bg-slate-800">
            <div class="flex flex-wrap items-center gap-2">
              <span
                v-for="(tag, index) in form.tech_stack"
                :key="tag + '-' + index"
                class="inline-flex items-center gap-1 rounded-full bg-[#e6deff] px-3 py-1 text-xs font-semibold text-[#4735a3] dark:bg-indigo-500/20 dark:text-indigo-200"
              >
                {{ tag }}
                <button
                  type="button"
                  class="rounded-full p-0.5 text-indigo-500 hover:bg-indigo-200/60 hover:text-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-500/30"
                  @click="$emit('remove-tech', index)"
                >
                  <X class="h-3 w-3" />
                </button>
              </span>

              <div class="inline-flex items-center gap-1 rounded-full border border-[#d8d0ec] bg-white px-2 py-1 dark:border-slate-600 dark:bg-slate-900">
                <input
                  :value="techInput"
                  type="text"
                  class="w-28 border-0 bg-transparent px-1 py-0.5 text-xs text-[#4a4464] outline-none placeholder:text-[#8d87aa] dark:text-slate-200 dark:placeholder:text-slate-500"
                  placeholder="Add skill..."
                  @input="$emit('update:techInput', ($event.target as HTMLInputElement).value)"
                  @keydown.enter.prevent="$emit('add-tech')"
                />
                <button
                  type="button"
                  class="rounded-full p-1 text-slate-500 transition hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                  @click="$emit('add-tech')"
                >
                  <Plus class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </div>
          <p v-if="errors.tech_stack" class="mt-1 text-xs text-red-600">{{ errors.tech_stack }}</p>
        </div>
      </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
      <div class="flex flex-wrap gap-3">
        <button
          v-if="showSaveDraft"
          type="button"
          class="inline-flex items-center justify-center rounded-full border border-[#d9d2f0] bg-white px-5 py-2.5 text-sm font-semibold text-[#4b35cb] transition hover:bg-[#f7f3ff] dark:border-slate-600 dark:bg-slate-800 dark:text-indigo-300 dark:hover:bg-slate-700"
          @click="$emit('save-draft')"
        >
          <Save class="mr-1.5 h-4 w-4" />
          {{ draftButtonLabel }}
        </button>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-full border border-[#d9d2f0] bg-white px-5 py-2.5 text-sm font-semibold text-[#4b35cb] transition hover:bg-[#f7f3ff] dark:border-slate-600 dark:bg-slate-800 dark:text-indigo-300 dark:hover:bg-slate-700"
          @click="$emit('cancel')"
        >
          Cancel
        </button>
      </div>
      <button
        type="submit"
        :disabled="loading"
        class="inline-flex items-center justify-center rounded-full bg-[#4b35cb] px-7 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3f2cb0] disabled:cursor-not-allowed disabled:opacity-60"
      >
        <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
          <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" class="opacity-90" />
        </svg>
        {{ submitButtonLabel }}
      </button>
    </div>
  </form>
</template>

<script lang="ts">
import { defineComponent, nextTick, type PropType } from 'vue'
import {
  Bold,
  Check,
  ChevronDown,
  BriefcaseBusiness,
  Building2,
  FileText,
  Globe,
  Italic,
  List,
  MapPin,
  Plus,
  Save,
  X,
} from 'lucide-vue-next'
import BaseInput from '@/components/ui/BaseInput.vue'

interface ProjectFormData {
  title: string
  location: string
  location_strategy: 'remote' | 'onsite' | 'hybrid'
  industry: string
  internship_duration: string
  description: string
  requirements: string
  tech_stack: string[]
  status: string
  max_students: number
}

export default defineComponent({
  name: 'ProjectCreateForm',
  components: {
    BaseInput,
    Bold,
    Check,
    ChevronDown,
    BriefcaseBusiness,
    Building2,
    FileText,
    Globe,
    Italic,
    List,
    MapPin,
    Plus,
    Save,
    X,
  },
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
    showSaveDraft: {
      type: Boolean,
      default: true,
    },
    showStatusField: {
      type: Boolean,
      default: true,
    },
    allowClosedStatus: {
      type: Boolean,
      default: false,
    },
    submitButtonLabel: {
      type: String,
      default: 'Publish project',
    },
    draftButtonLabel: {
      type: String,
      default: 'Save as draft',
    },
  },
  emits: [
    'submit',
    'save-draft',
    'cancel',
    'update-field',
    'update:techInput',
    'add-tech',
    'remove-tech',
  ],
  data() {
    return {
      activeEditor: null as 'description' | 'requirements' | null,
      openDropdown: null as 'industry' | 'duration' | 'status' | null,
      isCustomDuration: false,
      industryOptions: [
        'Technology & Software',
        'Biotechnology',
        'Renewable Energy',
        'Financial Services',
        'Digital Humanities',
      ],
      durationOptions: [
        '3 Months (Summer)',
        '6 Months (Semester)',
        '12 Months (Full Year)',
        'Flexible / Project Based',
      ],
    }
  },
  mounted() {
    this.syncEditorText('description', this.form.description)
    this.syncEditorText('requirements', this.form.requirements)
    this.syncCustomDurationState(this.form.internship_duration)
    document.addEventListener('click', this.onDocumentClick)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onDocumentClick)
  },
  watch: {
    'form.description'(value: string) {
      if (this.activeEditor !== 'description') {
        this.syncEditorText('description', value)
      }
    },
    'form.requirements'(value: string) {
      if (this.activeEditor !== 'requirements') {
        this.syncEditorText('requirements', value)
      }
    },
    'form.internship_duration'(value: string) {
      this.syncCustomDurationState(value)
    },
  },
  computed: {
    statusOptions(): string[] {
      const base = ['draft', 'open']
      if (this.allowClosedStatus) base.push('closed')
      return base
    },
  },
  methods: {
    toggleDropdown(dropdown: 'industry' | 'duration' | 'status') {
      this.openDropdown = this.openDropdown === dropdown ? null : dropdown
    },
    selectIndustry(option: string) {
      this.$emit('update-field', { field: 'industry', value: option })
      this.openDropdown = null
    },
    selectDuration(option: string) {
      this.isCustomDuration = false
      this.$emit('update-field', { field: 'internship_duration', value: option })
      this.openDropdown = null
    },
    selectDurationCustom() {
      this.isCustomDuration = true
      if (!this.form.internship_duration.trim() || this.durationOptions.includes(this.form.internship_duration)) {
        this.$emit('update-field', { field: 'internship_duration', value: '' })
      }
      this.openDropdown = null
    },
    selectStatus(option: string) {
      this.$emit('update-field', { field: 'status', value: option })
      this.openDropdown = null
    },
    syncCustomDurationState(value: string) {
      this.isCustomDuration = value !== '' && !this.durationOptions.includes(value)
    },
    onDocumentClick(event: Event) {
      const target = event.target as HTMLElement | null
      if (target?.closest('.project-custom-dropdown')) return
      this.openDropdown = null
    },
    onEditorInput(field: 'description' | 'requirements') {
      const refName = field === 'description' ? 'descriptionTextarea' : 'requirementsTextarea'
      const editor = this.$refs[refName] as HTMLDivElement | undefined
      if (!editor) return

      const value = this.normalizeEditorText(editor.innerText)
      this.$emit('update-field', { field, value })
    },
    formatEditor(
      field: 'description' | 'requirements',
      action: 'bold' | 'italic' | 'list',
    ) {
      const refName = field === 'description' ? 'descriptionTextarea' : 'requirementsTextarea'
      const editor = this.$refs[refName] as HTMLDivElement | undefined
      if (!editor) return

      this.activeEditor = field
      editor.focus()

      if (action === 'bold') {
        document.execCommand('bold')
      } else if (action === 'italic') {
        document.execCommand('italic')
      } else {
        document.execCommand('insertUnorderedList')
      }

      nextTick(() => {
        this.onEditorInput(field)
      })
    },
    normalizeEditorText(value: string): string {
      return value.replace(/\u00A0/g, ' ').replace(/\r/g, '').trim()
    },
    syncEditorText(field: 'description' | 'requirements', value: string) {
      const refName = field === 'description' ? 'descriptionTextarea' : 'requirementsTextarea'
      const editor = this.$refs[refName] as HTMLDivElement | undefined
      if (!editor) return

      const next = value ?? ''
      const current = this.normalizeEditorText(editor.innerText)
      if (current === this.normalizeEditorText(next)) return

      editor.innerText = next
    },
  },
})
</script>

<style scoped>
.wysiwyg-editor:empty::before {
  content: attr(data-placeholder);
  color: rgb(141 135 170);
  pointer-events: none;
}

:deep(.wysiwyg-editor ul) {
  list-style: disc;
  list-style-position: outside;
  margin: 0.25rem 0;
  padding-left: 1.25rem;
}

:deep(.wysiwyg-editor li) {
  display: list-item;
}
</style>
