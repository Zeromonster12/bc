<template>
  <section class="relative mx-auto w-full max-w-7xl overflow-hidden px-4 py-16 sm:px-6 lg:py-24">
    <div class="grid items-center gap-12 lg:grid-cols-12">
      <div class="z-10 lg:col-span-7">
        <span class="inline-block rounded-full bg-[#e4dfff] px-4 py-1.5 text-sm font-bold uppercase tracking-[0.18em] text-[#3f1ccc]">
          The Academic Curator
        </span>

        <h1 class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight text-[#1c1a24] sm:text-5xl lg:text-7xl">
          Curating the Future of <span class="italic text-[#4120cd]">Internships.</span>
        </h1>

        <p class="mt-6 max-w-xl text-lg leading-relaxed text-[#474555] sm:text-xl">
          A high-end, editorial showcase connecting the world's most ambitious students with
          industry-defining innovation labs.
        </p>

        <div class="mt-8 flex flex-col gap-4 sm:flex-row">
          <RouterLink
            to="/register"
            class="rounded-full bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-8 py-4 text-center text-lg font-bold text-white shadow-xl transition hover:brightness-105"
          >
            Join as a Student
          </RouterLink>
          <button
            type="button"
            class="rounded-full border border-[#c8c4d8] bg-white px-8 py-4 text-lg font-bold text-[#1c1a24] transition hover:bg-[#f0ecf9]"
          >
            For Companies
          </button>
        </div>
      </div>

      <div class="relative lg:col-span-5">
        <div class="aspect-[4/5] rotate-2 overflow-hidden rounded-[2rem] shadow-2xl transition-transform duration-500 hover:rotate-0">
          <img
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBEFDqVd-qAipQvcBXQviL9f6TpDsT4BKluKUfaRfT-NasGVkk4_keaDlUEsztbczWEfdQA_NODKe7r6iEMtrphgyP1zV-wGYwQdn9jaZOkkK5Jrm2D9iWFt7T-RDrVWagK_v1oy3o0tQc20HPLiZzO_QiE5iixQ05a49jOYauAbXuDZ0Z_34dVvyS4IBZxwmEH9VjNXuL2j3mwVMK44IfgP2sPF4T_4HwnQJFMFxa1ONcyx8f0k5_ML3wDuXb-agoZOuraqI1-lUOg"
            alt="Student profile preview"
            class="h-full w-full object-cover"
          />
        </div>

        <div class="absolute -bottom-5 -left-5 max-w-[220px] rounded-2xl border border-[#e5e0ee] bg-white p-5 shadow-xl">
          <p class="text-sm font-bold text-[#1c1a24]">Verified Talent</p>
          <p class="mt-1 text-xs leading-relaxed text-[#474555]">
            Top 1% of global academic applicants vetted for excellence.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
