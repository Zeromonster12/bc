<template>
  <section class="px-4 py-20 sm:px-6">
    <div class="relative mx-auto max-w-5xl overflow-hidden rounded-[2.5rem] bg-linear-to-r from-[#4120cd] to-[#5a42e5] px-8 py-14 text-center text-white shadow-2xl lg:px-16 lg:py-20">
      <h2 class="text-3xl font-extrabold leading-tight sm:text-5xl">
        Ready to Curate Your <span class="italic text-[#ddd7ff]">Next Chapter?</span>
      </h2>
      <p class="mx-auto mt-6 max-w-2xl text-lg text-[#ddd7ff]">
        Join the elite network connecting academic excellence with industrial innovation.
      </p>

      <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
        <RouterLink
          to="/register"
          class="rounded-full bg-white px-10 py-4 text-lg font-bold text-[#4120cd] transition hover:bg-[#e4dfff]"
        >
          Sign Up Now
        </RouterLink>
        <button
          type="button"
          class="rounded-full border border-white/30 bg-white/10 px-10 py-4 text-lg font-bold text-white transition hover:bg-white/20"
        >
          Contact Sales
        </button>
      </div>
    </div>
  </section>
</template>
