import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const APP_DISPLAY_NAME = (import.meta.env.VITE_APP_NAME as string | undefined)?.trim() || 'ProjectLinker'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ── Auth ──────────────────────────────────────────────────────────────
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { requiresGuest: true, layout: 'auth' },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { requiresGuest: true, layout: 'auth' },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/auth/ForgotPasswordView.vue'),
      meta: { requiresGuest: true, layout: 'auth' },
    },
    {
      path: '/password-reset/:token',
      name: 'password-reset',
      component: () => import('@/views/auth/ResetPasswordView.vue'),
      meta: { requiresGuest: true, layout: 'auth' },
    },
    {
      path: '/email-verification',
      name: 'email-verification',
      component: () => import('@/views/auth/EmailVerificationResultView.vue'),
      meta: { layout: 'auth' },
    },
    {
      path: '/verify-email-code',
      name: 'verify-email-code',
      component: () => import('@/views/auth/VerifyEmailCodeView.vue'),
      meta: { requiresGuest: true, layout: 'auth' },
    },
    {
      path: '/auth/google/callback',
      name: 'auth.google.callback',
      component: () => import('@/views/auth/GoogleOAuthCallbackView.vue'),
      meta: { requiresGuest: true, layout: 'auth' },
    },

    // ── App ───────────────────────────────────────────────────────────────
    {
      path: '/',
      name: 'landing',
      component: () => import('@/views/LandingView.vue'),
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/projects',
      name: 'projects',
      component: () => import('@/views/projects/ProjectsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/projects/create',
      name: 'projects.create',
      component: () => import('@/views/projects/CreateProjectView.vue'),
      meta: { requiresAuth: true, roles: ['company'] },
    },
    {
      path: '/projects/:id',
      name: 'projects.show',
      component: () => import('@/views/projects/ProjectDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/projects/:id/edit',
      name: 'projects.edit',
      component: () => import('@/views/projects/EditProjectView.vue'),
      meta: { requiresAuth: true, roles: ['company', 'admin'] },
    },
    {
      path: '/applications',
      name: 'applications',
      component: () => import('@/views/applications/ApplicationsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/applications/company-board',
      name: 'applications.company-board',
      component: () => import('@/views/applications/CompanyTaskBoardView.vue'),
      meta: { requiresAuth: true, roles: ['company'] },
    },
    {
      path: '/applications/accepted',
      name: 'applications.accepted',
      component: () => import('@/views/applications/CompanyTaskBoardView.vue'),
      meta: { requiresAuth: true, roles: ['student'] },
    },
    {
      path: '/profile/student',
      name: 'profile.student',
      component: () => import('@/views/profile/StudentProfileView.vue'),
      meta: { requiresAuth: true, roles: ['student'] },
    },
    {
      path: '/profile/student/github/callback',
      name: 'profile.student.github.callback',
      component: () => import('@/views/profile/GitHubConnectCallbackView.vue'),
      meta: { requiresAuth: true, roles: ['student'] },
    },
    {
      path: '/students/:id/profile',
      name: 'students.profile',
      component: () => import('@/views/profile/StudentPublicProfileView.vue'),
      meta: { requiresAuth: true, roles: ['company', 'admin'] },
    },
    {
      path: '/companies/:id/profile',
      name: 'companies.profile',
      component: () => import('@/views/profile/CompanyPublicProfileView.vue'),
      meta: { requiresAuth: true, roles: ['student', 'company', 'admin'] },
    },
    {
      path: '/profile/company',
      name: 'profile.company',
      component: () => import('@/views/profile/CompanyProfileView.vue'),
      meta: { requiresAuth: true, roles: ['company'] },
    },
    {
      path: '/messages',
      name: 'messages',
      component: () => import('@/views/messages/MessagesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('@/views/admin/AdminDashboardView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] },
    },

    // ── Fallback ──────────────────────────────────────────────────────────
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      redirect: '/',
    },
  ],
})

// Navigation guard
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Attempt to rehydrate user from token on first load
  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.name === 'landing' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.roles && Array.isArray(to.meta.roles)) {
    if (!to.meta.roles.includes(auth.user?.role)) {
      return { name: 'dashboard' }
    }
  }

  if (to.name === 'projects.create') {
    const isPendingCompany =
      auth.user?.role === 'company' && auth.user?.company_verification_status !== 'approved'

    if (isPendingCompany) {
      return {
        name: 'profile.company',
        query: { approval: String(auth.user?.company_verification_status ?? 'pending') },
      }
    }
  }
})

const DYNAMIC_IMPORT_RELOAD_KEY = 'bc_platform_dynamic_import_reload'

router.onError((error, to) => {
  const message = error instanceof Error ? error.message : String(error ?? '')
  const isDynamicImportFailure =
    message.includes('Failed to fetch dynamically imported module') ||
    message.includes('Importing a module script failed')

  if (!isDynamicImportFailure) return

  const hasReloaded = sessionStorage.getItem(DYNAMIC_IMPORT_RELOAD_KEY) === '1'

  if (!hasReloaded) {
    sessionStorage.setItem(DYNAMIC_IMPORT_RELOAD_KEY, '1')
    const target =
      to?.fullPath ?? window.location.pathname + window.location.search + window.location.hash
    window.location.assign(target)
    return
  }

  sessionStorage.removeItem(DYNAMIC_IMPORT_RELOAD_KEY)
})

router.afterEach((to) => {
  const pageTitle = typeof to.meta.title === 'string' ? to.meta.title.trim() : ''
  document.title = pageTitle !== '' ? `${pageTitle} | ${APP_DISPLAY_NAME}` : APP_DISPLAY_NAME

  sessionStorage.removeItem(DYNAMIC_IMPORT_RELOAD_KEY)
})

export default router
