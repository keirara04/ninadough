export function HeroBanner() {
  return (
    <section className="bg-brand-cream px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
      <div className="mx-auto flex max-w-7xl flex-col-reverse items-center gap-8 lg:flex-row lg:gap-16">
        <div className="flex flex-col items-start gap-5 lg:w-1/2">
          <h1 className="font-display text-3xl font-bold leading-tight text-brand-cocoa sm:text-4xl lg:text-5xl">
            Fresh bakes,
            <br />
            pre-order with ease
          </h1>
          <p className="max-w-sm text-sm text-brand-cocoa/70 lg:text-base">
            Small-batch cakes, cookies and pastries — made to order for pickup or delivery
            across Klang Valley.
          </p>
          <a
            href="#catalogue"
            className="inline-flex min-h-11 items-center rounded-full bg-brand-pink px-6 text-sm font-semibold text-white transition-transform hover:scale-[1.03] active:scale-95"
          >
            Shop preorders
          </a>
        </div>

        <div className="aspect-[4/3] w-full overflow-hidden rounded-3xl bg-brand-gold/15 lg:w-1/2">
          <div className="flex h-full w-full items-center justify-center text-brand-gold/70">
            <svg
              aria-hidden
              viewBox="0 0 200 200"
              className="h-24 w-24 sm:h-32 sm:w-32"
              fill="none"
              stroke="currentColor"
              strokeWidth={4}
            >
              <circle cx="100" cy="130" r="45" />
              <path strokeLinecap="round" d="M100 40v50M75 55l25-15 25 15" />
            </svg>
          </div>
        </div>
      </div>
    </section>
  );
}
