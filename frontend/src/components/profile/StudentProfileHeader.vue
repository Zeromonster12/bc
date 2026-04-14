<template>
  <div class="rounded-3xl bg-white p-6 sm:p-7 dark:bg-slate-900">
    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
      <div class="flex items-center gap-5">
        <div
          class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-[#f1edf8] flex items-center justify-center dark:bg-slate-800"
        >
          <img
            v-if="avatarPreview"
            :src="avatarPreview"
            alt="Avatar"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-2xl font-bold text-[#3f34a6] dark:text-indigo-300">{{ initials }}</span>
        </div>
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100">{{ studentName }}</h1>
          <p class="text-sm text-slate-600 dark:text-slate-300 max-w-xl">
            {{ studentBio }}
          </p>
        </div>
      </div>

      <div class="w-full md:w-72">
        <div class="mb-2 flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
          <span>Profile completion</span>
          <span class="font-semibold text-slate-800 dark:text-slate-100">{{ completionRate }}%</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-[#e8e3f2] dark:bg-slate-700">
          <div
            class="h-full rounded-full bg-[#3f34a6] transition-all duration-500 dark:bg-indigo-500"
            :style="{ width: `${completionRate}%` }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'StudentProfileHeader',
  props: {
    avatarPreview: {
      type: String,
      default: '',
    },
    initials: {
      type: String,
      default: 'U',
    },
    studentName: {
      type: String,
      default: 'Student Profile',
    },
    studentBio: {
      type: String,
      default: 'Complete your profile so companies can quickly understand your skills and education.',
    },
    completionRate: {
      type: Number,
      default: 0,
    },
  },
})
</script>
