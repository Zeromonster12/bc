<template>
  <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl">
    <nav class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
      <RouterLink to="/" class="text-2xl font-extrabold tracking-tight text-indigo-700">Project Linker</RouterLink>

      <div class="hidden items-center gap-8 md:flex">
        <a href="#" class="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600">Students</a>
        <a href="#" class="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600">Companies</a>
        <a href="#" class="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600">About Us</a>
      </div>

      <div class="flex items-center gap-3 sm:gap-4">
        <RouterLink
          to="/login"
          class="hidden rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 sm:block"
        >
          Log In
        </RouterLink>
        <RouterLink
          to="/register"
          class="rounded-full bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-200/70 transition hover:brightness-105"
        >
          Get Started
        </RouterLink>
      </div>
    </nav>
  </header>
</template>
