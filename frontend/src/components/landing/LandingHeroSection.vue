<template>
  <section class="relative mx-auto w-full max-w-7xl overflow-hidden px-4 py-12 sm:px-6 sm:py-16 lg:py-24">
    <div class="grid items-center gap-8 sm:gap-10 lg:grid-cols-12 lg:gap-12">
      <div class="z-10 lg:col-span-7">
        <span class="inline-block rounded-full bg-[#e4dfff] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-[#3f1ccc] dark:bg-indigo-500/20 dark:text-indigo-200 sm:px-4 sm:py-1.5 sm:text-sm sm:tracking-[0.18em]">
          The Academic Curator
        </span>

        <h1 class="mt-5 text-3xl font-extrabold leading-[1.12] tracking-tight text-[#1c1a24] dark:text-slate-100 sm:mt-6 sm:text-5xl lg:text-7xl">
          Curating the Future of <span class="italic text-[#4120cd] dark:text-indigo-300">Internships.</span>
        </h1>

        <p class="mt-5 max-w-xl text-base leading-relaxed text-[#474555] dark:text-slate-300 sm:mt-6 sm:text-xl">
          A high-end, editorial showcase connecting the world's most ambitious students with
          industry-defining innovation labs.
        </p>

        <div class="mt-7 flex flex-col gap-3 sm:mt-8 sm:gap-4 sm:flex-row">
          <RouterLink
            to="/register"
            class="rounded-full bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-6 py-3 text-center text-base font-bold text-white shadow-xl transition hover:brightness-105 sm:px-8 sm:py-4 sm:text-lg"
          >
            Join as a Student
          </RouterLink>
          <button
            type="button"
            class="rounded-full border border-[#c8c4d8] bg-white px-6 py-3 text-base font-bold text-[#1c1a24] transition hover:bg-[#f0ecf9] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 sm:px-8 sm:py-4 sm:text-lg"
          >
            For Companies
          </button>
        </div>
      </div>

      <div class="relative lg:col-span-5">
        <div class="aspect-[4/5] overflow-hidden rounded-[1.5rem] shadow-2xl transition-transform duration-500 sm:rounded-[2rem] sm:rotate-2 sm:hover:rotate-0">
          <img
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBEFDqVd-qAipQvcBXQviL9f6TpDsT4BKluKUfaRfT-NasGVkk4_keaDlUEsztbczWEfdQA_NODKe7r6iEMtrphgyP1zV-wGYwQdn9jaZOkkK5Jrm2D9iWFt7T-RDrVWagK_v1oy3o0tQc20HPLiZzO_QiE5iixQ05a49jOYauAbXuDZ0Z_34dVvyS4IBZxwmEH9VjNXuL2j3mwVMK44IfgP2sPF4T_4HwnQJFMFxa1ONcyx8f0k5_ML3wDuXb-agoZOuraqI1-lUOg"
            alt="Student profile preview"
            class="h-full w-full object-cover"
          />
        </div>

        <div class="mt-4 max-w-full rounded-2xl border border-[#e5e0ee] bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-900 sm:absolute sm:-bottom-5 sm:-left-5 sm:mt-0 sm:max-w-[220px] sm:p-5">
          <p class="text-sm font-bold text-[#1c1a24] dark:text-slate-100">Verified Talent</p>
          <p class="mt-1 text-xs leading-relaxed text-[#474555] dark:text-slate-300">
            Top 1% of global academic applicants vetted for excellence.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
