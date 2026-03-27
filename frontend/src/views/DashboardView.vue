<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Student dashboard -->
      <template v-if="auth.isStudent">
        <section class="rounded-3xl border border-slate-200 bg-linear-to-br from-white via-indigo-50/40 to-purple-50/30 p-6 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20 sm:p-8">
          <div class="grid gap-6 lg:grid-cols-[1.6fr_0.8fr] lg:items-center">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600 dark:text-indigo-300">Student Overview</p>
              <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl">
                Welcome back, {{ firstName }}
              </h1>
              <p class="mt-3 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                Your profile is active and opportunities are moving. Track applications, unread chat updates and new project matches in one place.
              </p>
              <div class="mt-5 flex flex-wrap gap-3">
                <RouterLink
                  to="/applications"
                  class="inline-flex items-center rounded-full bg-linear-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-[0_10px_26px_rgba(79,70,229,0.35)] hover:brightness-105"
                >
                  Manage Applications
                </RouterLink>
                <RouterLink
                  to="/projects"
                  class="inline-flex items-center rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                  Explore Projects
                </RouterLink>
              </div>
            </div>

            <div class="rounded-2xl border border-indigo-200/70 bg-white/90 p-5 dark:border-indigo-500/30 dark:bg-slate-900/80">
              <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Completion Pulse</p>
              <p class="mt-2 text-4xl font-black text-indigo-700 dark:text-indigo-300">{{ profileStrength }}%</p>
              <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Derived from accepted applications and active conversations.</p>
              <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                <div
                  class="h-full rounded-full bg-linear-to-r from-indigo-500 to-violet-500 transition-all duration-500"
                  :style="{ width: `${profileStrength}%` }"
                />
              </div>
            </div>
          </div>
        </section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Open Projects</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.openProjects }}</p>
            <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Available right now</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Applications Sent</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.myApplications }}</p>
            <p class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">{{ pendingApplicationsCount }} pending review</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Accepted</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.acceptedApplications }}</p>
            <p class="mt-1 text-xs text-violet-600 dark:text-violet-400">Projects where you are in</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Unread Messages</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ unreadMessages }}</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Across all conversations</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.65fr_1fr]">
          <section class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Recommended for You</h2>
              <RouterLink to="/projects" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                See all matches
              </RouterLink>
            </div>

            <div v-if="projectStore.loading" class="grid gap-4 md:grid-cols-2">
              <div
                v-for="n in 4"
                :key="n"
                class="h-56 rounded-2xl bg-gray-100 animate-pulse dark:bg-slate-800"
              />
            </div>

            <div v-else-if="recentProjects.length" class="grid gap-4 md:grid-cols-2">
              <article
                v-for="project in recentProjects"
                :key="project.id"
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
              >
                <div class="flex items-center justify-between gap-3">
                  <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                    {{ normalizeLocationStrategy(project.location_strategy) }}
                  </span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatPostedAt(project.posted_at) }}</span>
                </div>
                <h3 class="mt-3 text-lg font-bold text-slate-900 group-hover:text-indigo-700 dark:text-slate-100 dark:group-hover:text-indigo-300">
                  {{ project.title }}
                </h3>
                <p class="mt-2 line-clamp-3 text-sm text-slate-600 dark:text-slate-300">
                  {{ project.description }}
                </p>
                <div class="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                  <span>{{ formatTechStack(project.tech_stack) }}</span>
                  <RouterLink :to="`/projects/${project.id}`" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                    Open
                  </RouterLink>
                </div>
              </article>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
              No open projects right now. Check back later.
            </div>
          </section>

          <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Recent Activity</h2>
                <RouterLink to="/applications" class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">View</RouterLink>
              </div>
              <div class="mt-4 space-y-3">
                <div
                  v-for="activity in recentActivities"
                  :key="activity.id"
                  class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60"
                >
                  <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ activity.title }}</p>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ activity.meta }}</p>
                </div>
              </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Next Steps</h2>
                <span class="rounded-full bg-indigo-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                  {{ pendingChecklistCount }} pending
                </span>
              </div>

              <ul class="mt-4 space-y-2.5">
                <li
                  v-for="item in checklistItems"
                  :key="item.key"
                  class="flex items-start gap-3 rounded-xl border px-3 py-2.5"
                  :class="item.done
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200'"
                >
                  <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full text-[11px] font-black"
                    :class="item.done ? 'bg-emerald-600 text-white dark:bg-emerald-500' : 'bg-slate-300 text-slate-700 dark:bg-slate-600 dark:text-slate-100'"
                  >
                    {{ item.done ? '✓' : '•' }}
                  </span>
                  <div>
                    <p class="text-sm font-semibold">{{ item.label }}</p>
                    <p class="text-xs opacity-80">{{ item.note }}</p>
                  </div>
                </li>
              </ul>

              <RouterLink
                to="/profile/student"
                class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              >
                Manage Profile
              </RouterLink>
            </section>
          </aside>
        </div>

        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Recent open projects</h2>
            <RouterLink to="/projects" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
              View all →
            </RouterLink>
          </div>
          <div v-if="projectStore.loading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="n in 3"
              :key="n"
              class="h-40 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800"
            />
          </div>
          <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <ProjectCard
              v-for="project in recentProjects"
              :key="project.id"
              :project="project"
              @click="$router.push('/projects/' + project.id)"
              class="cursor-pointer"
            />
          </div>
        </section>
      </template>

      <!-- Company dashboard -->
      <template v-else-if="auth.isCompany">
        <section class="rounded-3xl border border-white/90 bg-linear-to-br from-white via-indigo-50/45 to-violet-50/35 p-6 shadow-[0_10px_30px_rgba(30,27,53,0.08)] dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20 dark:shadow-[0_10px_30px_rgba(2,6,23,0.5)] sm:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b35cb] dark:text-indigo-300">Company Workspace</p>
              <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl">Operations Dashboard</h1>
              <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">Monitor projects, applicants and communication from one place.</p>
            </div>
            <RouterLink
              to="/projects/create"
              class="inline-flex items-center rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] hover:brightness-105"
            >
              + New project
            </RouterLink>
          </div>
        </section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">My Projects</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.myProjects }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Open Projects</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.openProjects }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Applications</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.totalApplications }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Unread Messages</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ unreadMessages }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.6fr_1fr]">
          <section>
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Your projects</h2>
              <RouterLink to="/projects" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">View all</RouterLink>
            </div>
            <div v-if="projectStore.loading" class="grid gap-4 md:grid-cols-2">
              <div v-for="n in 4" :key="n" class="h-48 rounded-xl bg-gray-100 animate-pulse dark:bg-slate-800" />
            </div>
            <div v-else class="grid gap-4 md:grid-cols-2">
              <ProjectCard
                v-for="project in recentProjects"
                :key="project.id"
                :project="project"
                @click="$router.push('/projects/' + project.id)"
                class="cursor-pointer"
              />
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Application Pipeline</h2>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
              <div class="rounded-xl bg-amber-50 p-2.5 dark:bg-amber-900/20">
                <p class="text-xl font-black text-amber-700 dark:text-amber-300">{{ companyPendingApplications }}</p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-700/80 dark:text-amber-300/80">Pending</p>
              </div>
              <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-900/20">
                <p class="text-xl font-black text-emerald-700 dark:text-emerald-300">{{ companyAcceptedApplications }}</p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-emerald-700/80 dark:text-emerald-300/80">Accepted</p>
              </div>
              <div class="rounded-xl bg-rose-50 p-2.5 dark:bg-rose-900/20">
                <p class="text-xl font-black text-rose-700 dark:text-rose-300">{{ companyRejectedApplications }}</p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-rose-700/80 dark:text-rose-300/80">Rejected</p>
              </div>
            </div>

            <div class="mt-5 space-y-2.5">
              <div
                v-for="item in companyRecentApplications"
                :key="item.id"
                class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60"
              >
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.title }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ item.meta }}</p>
              </div>
            </div>
          </section>
        </div>
      </template>

      <!-- Admin dashboard -->
      <template v-else-if="auth.isAdmin">
        <section class="rounded-3xl border border-white/90 bg-linear-to-br from-white via-indigo-50/45 to-violet-50/35 p-6 shadow-[0_10px_30px_rgba(30,27,53,0.08)] dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20 dark:shadow-[0_10px_30px_rgba(2,6,23,0.5)] sm:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#4b35cb] dark:text-indigo-300">Administration</p>
              <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl">Platform Control</h1>
              <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">Overview of users, projects and pending approvals.</p>
            </div>
            <RouterLink to="/admin" class="inline-flex items-center rounded-full bg-linear-to-r from-[#4526c9] to-[#5b45f0] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(77,55,197,0.35)] hover:brightness-105">
              Open Admin Panel
            </RouterLink>
          </div>
        </section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Total Users</p><p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.totalUsers }}</p></div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Total Projects</p><p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.totalProjects }}</p></div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Open Projects</p><p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats.openProjects }}</p></div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Pending Companies</p><p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ pendingCompaniesCount }}</p></div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Unread Messages</p><p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ unreadMessages }}</p></div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Latest Users</h2>
            <div class="mt-4 space-y-2">
              <div v-for="item in adminLatestUsers" :key="item.id" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
                <div>
                  <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ item.email }}</p>
                </div>
                <span class="rounded-full bg-slate-200 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ item.role }}</span>
              </div>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Recent Projects</h2>
            <div class="mt-4 space-y-2">
              <div v-for="project in adminRecentProjects" :key="project.id" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ project.title }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ formatPostedAt(project.created_at) }}</p>
              </div>
            </div>
          </section>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectStore } from '@/stores/project'
import { useApplicationStore } from '@/stores/application'
import { useMessageStore } from '@/stores/message'
import {
  countAcceptedApplications,
  countOpenProjects,
  createDefaultDashboardStats,
} from '@/services/dashboard/DashboardService'
import AdminService from '@/services/admin/AdminService'
import AppLayout from '@/layouts/AppLayout.vue'
import ProjectCard from '@/components/projects/ProjectCard.vue'

interface DashboardProject {
  id: number
  status?: string
  title?: string
  created_at?: string | null
  description?: string
  location_strategy?: string | null
  posted_at?: string | null
  tech_stack?: string[]
  [key: string]: unknown
}

interface AdminUserLite {
  id: number
  name: string
  email: string
  role: string
  company_verification_status?: string | null
  created_at?: string | null
  [key: string]: unknown
}

interface StudentActivity {
  id: string
  title: string
  meta: string
}

export default defineComponent({
  name: 'DashboardView',
  components: { AppLayout, ProjectCard },
  setup() {
    return {
      auth: useAuthStore(),
      projectStore: useProjectStore(),
      applicationStore: useApplicationStore(),
      messageStore: useMessageStore(),
    }
  },
  data() {
    return {
      stats: createDefaultDashboardStats(),
      adminUsers: [] as AdminUserLite[],
      adminProjects: [] as DashboardProject[],
    }
  },
  computed: {
    firstName(): string {
      const name = String(this.auth.user?.name ?? '').trim()
      return name.split(' ')[0] || 'Student'
    },
    recentProjects(): DashboardProject[] {
      return (this.projectStore.projects as DashboardProject[]).slice(0, 6)
    },
    unreadMessages(): number {
      return Number(this.messageStore.totalUnread ?? 0)
    },
    pendingApplicationsCount(): number {
      return this.applicationStore.applications.filter((app) => app.status === 'pending').length
    },
    recentActivities(): StudentActivity[] {
      const recentApps = this.applicationStore.applications.slice(0, 4)
      if (!recentApps.length) {
        return [
          {
            id: 'no-activity',
            title: 'No recent application activity',
            meta: 'Start by applying to open projects.',
          },
        ]
      }

      return recentApps.map((app) => {
        const appAsRecord = app as Record<string, unknown>
        const project = (appAsRecord.project as Record<string, unknown> | undefined) ?? {}
        const title = String(project.title ?? 'Project application')
        const status = String(appAsRecord.status ?? 'pending')
        const createdAt = String(appAsRecord.created_at ?? '')

        return {
          id: `app-${String(appAsRecord.id ?? Math.random())}`,
          title: `${this.capitalizeStatus(status)} application • ${title}`,
          meta: this.formatRelativeDate(createdAt),
        }
      })
    },
    checklistItems(): Array<{ key: string; label: string; note: string; done: boolean }> {
      const hasApplied = this.stats.myApplications > 0
      const hasAccepted = this.stats.acceptedApplications > 0
      const hasUnread = this.unreadMessages > 0

      return [
        {
          key: 'apply',
          label: 'Apply to relevant projects',
          note: hasApplied ? 'Great start, keep applying strategically.' : 'Submit your first application.',
          done: hasApplied,
        },
        {
          key: 'accepted',
          label: 'Get accepted to at least one project',
          note: hasAccepted ? 'You are already accepted on at least one.' : 'Focus on improving profile fit.',
          done: hasAccepted,
        },
        {
          key: 'messages',
          label: 'Check chat updates',
          note: hasUnread ? `${this.unreadMessages} unread conversations waiting.` : 'No unread messages right now.',
          done: !hasUnread,
        },
      ]
    },
    pendingChecklistCount(): number {
      return this.checklistItems.filter((item) => !item.done).length
    },
    profileStrength(): number {
      const appliedScore = Math.min(this.stats.myApplications * 8, 40)
      const acceptedScore = Math.min(this.stats.acceptedApplications * 25, 50)
      const messageScore = this.unreadMessages > 0 ? 5 : 10
      return Math.max(15, Math.min(95, appliedScore + acceptedScore + messageScore))
    },
    companyPendingApplications(): number {
      return this.applicationStore.applications.filter((app) => app.status === 'pending').length
    },
    companyAcceptedApplications(): number {
      return this.applicationStore.applications.filter((app) => app.status === 'accepted').length
    },
    companyRejectedApplications(): number {
      return this.applicationStore.applications.filter((app) => app.status === 'rejected').length
    },
    companyRecentApplications(): StudentActivity[] {
      const apps = this.applicationStore.applications.slice(0, 5)
      if (!apps.length) {
        return [
          {
            id: 'no-company-activity',
            title: 'No applications yet',
            meta: 'Your latest applications will appear here.',
          },
        ]
      }

      return apps.map((app) => {
        const appAsRecord = app as Record<string, unknown>
        const project = (appAsRecord.project as Record<string, unknown> | undefined) ?? {}
        const student = (appAsRecord.student as Record<string, unknown> | undefined) ?? {}
        const title = String(project.title ?? 'Project application')
        const studentName = String(student.name ?? 'Unknown student')

        return {
          id: `company-app-${String(appAsRecord.id ?? Math.random())}`,
          title: `${studentName} applied to ${title}`,
          meta: this.formatRelativeDate(String(appAsRecord.created_at ?? '')),
        }
      })
    },
    pendingCompaniesCount(): number {
      return this.adminUsers.filter(
        (user) =>
          user.role === 'company' &&
          String(user.company_verification_status ?? '') === 'pending',
      ).length
    },
    adminLatestUsers(): AdminUserLite[] {
      return this.adminUsers.slice(0, 6)
    },
    adminRecentProjects(): DashboardProject[] {
      return this.adminProjects.slice(0, 6)
    },
  },
  async mounted() {
    if (this.auth.isStudent) {
      await Promise.all([
        this.projectStore.fetchProjects({ status: 'open', per_page: 8 }),
        this.applicationStore.fetchApplications(),
        this.messageStore.fetchConversations(),
      ])
      this.stats.openProjects = this.projectStore.pagination?.total ?? 0
      this.stats.myApplications = this.applicationStore.applications.length
      this.stats.acceptedApplications = countAcceptedApplications(
        this.applicationStore.applications,
      )
    } else if (this.auth.isCompany) {
      await Promise.all([
        this.projectStore.fetchProjects({ per_page: 6 }),
        this.applicationStore.fetchApplications(),
        this.messageStore.fetchConversations(),
      ])
      this.stats.myProjects = this.projectStore.pagination?.total ?? 0
      this.stats.openProjects = countOpenProjects(this.projectStore.projects)
      this.stats.totalApplications = this.applicationStore.applications.length
    } else if (this.auth.isAdmin) {
      const [usersRes, projectsRes] = await Promise.all([
        AdminService.getUsers({ per_page: 50 }),
        AdminService.getProjects({ per_page: 50 }),
        this.projectStore.fetchProjects({ status: 'open', per_page: 1 }),
        this.messageStore.fetchConversations(),
      ])

      this.adminUsers = (usersRes.data ?? []) as AdminUserLite[]
      this.adminProjects = (projectsRes.data ?? []) as DashboardProject[]

      this.stats.totalUsers = Number(usersRes.meta?.total ?? this.adminUsers.length)
      this.stats.totalProjects = Number(projectsRes.meta?.total ?? this.adminProjects.length)
      this.stats.openProjects = Number(this.projectStore.pagination?.total ?? 0)
    }
  },
  methods: {
    normalizeLocationStrategy(value: unknown): string {
      const strategy = String(value ?? '').trim().toLowerCase()
      if (!strategy) return 'Unknown'
      return strategy.charAt(0).toUpperCase() + strategy.slice(1)
    },
    formatPostedAt(value: unknown): string {
      const raw = String(value ?? '').trim()
      if (!raw) return 'Recently'
      const date = new Date(raw)
      if (Number.isNaN(date.getTime())) return 'Recently'

      return this.formatRelativeDate(raw)
    },
    formatRelativeDate(value: string): string {
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return 'Recently'

      const diffMs = Date.now() - date.getTime()
      const hours = Math.floor(diffMs / (1000 * 60 * 60))
      if (hours < 1) return 'Just now'
      if (hours < 24) return `${hours}h ago`
      const days = Math.floor(hours / 24)
      if (days < 7) return `${days}d ago`
      const weeks = Math.floor(days / 7)
      return `${weeks}w ago`
    },
    capitalizeStatus(status: string): string {
      if (!status) return 'Pending'
      return status.charAt(0).toUpperCase() + status.slice(1)
    },
    formatTechStack(stack: unknown): string {
      const items = Array.isArray(stack)
        ? stack.map((item) => String(item ?? '').trim()).filter((item) => item.length > 0)
        : []

      if (!items.length) return 'General skill fit'
      return items.slice(0, 3).join(' • ')
    },
  },
})
</script>
