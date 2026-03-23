<template>
  <div class="surface-card p-6 sm:p-7">
    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
      <div class="flex items-center gap-5">
        <div
          class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center overflow-hidden shrink-0 border border-indigo-200"
        >
          <img
            v-if="avatarPreview"
            :src="avatarPreview"
            alt="Avatar"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-2xl font-bold text-indigo-700">{{ initials }}</span>
        </div>
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Student Profile</h1>
          <p class="text-sm text-slate-600 max-w-xl">
            Complete your profile so companies can quickly understand your skills, education, and
            career goals.
          </p>
        </div>
      </div>

      <div class="w-full md:w-72">
        <div class="flex items-center justify-between text-sm text-slate-600 mb-2">
          <span>Profile completion</span>
          <span class="font-semibold text-slate-800">{{ completionRate }}%</span>
        </div>
        <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
          <div
            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
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
    completionRate: {
      type: Number,
      default: 0,
    },
  },
})
</script>
