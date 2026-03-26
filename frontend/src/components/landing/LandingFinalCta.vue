<template>
  <section class="px-4 py-14 sm:px-6 sm:py-20">
    <div class="relative mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-5 py-10 text-center text-white shadow-2xl sm:rounded-[2.5rem] sm:px-8 sm:py-14 lg:px-16 lg:py-20">
      <h2 class="text-2xl font-extrabold leading-tight sm:text-5xl">
        Ready to Curate Your <span class="italic text-[#ddd7ff]">Next Chapter?</span>
      </h2>
      <p class="mx-auto mt-5 max-w-2xl text-base text-[#ddd7ff] sm:mt-6 sm:text-lg">
        Join the elite network connecting academic excellence with industrial innovation.
      </p>

      <div class="mt-8 flex flex-col justify-center gap-3 sm:mt-10 sm:gap-4 sm:flex-row">
        <RouterLink
          to="/register"
          class="rounded-full bg-white px-8 py-3 text-base font-bold text-[#4120cd] transition hover:bg-[#e4dfff] sm:px-10 sm:py-4 sm:text-lg"
        >
          Sign Up Now
        </RouterLink>
        <button
          type="button"
          class="rounded-full border border-white/30 bg-white/10 px-8 py-3 text-base font-bold text-white transition hover:bg-white/20 sm:px-10 sm:py-4 sm:text-lg"
        >
          Contact Sales
        </button>
      </div>
    </div>
  </section>
</template>
