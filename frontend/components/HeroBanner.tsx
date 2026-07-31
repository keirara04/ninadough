export function HeroBanner() {
  return (
    <section className="relative overflow-hidden bg-brand-cream px-4 pb-8 pt-6">
      <div className="relative z-10 max-w-xs">
        <h1 className="font-[family-name:var(--font-display)] text-3xl font-bold leading-tight text-brand-cocoa">
          Fresh bakes,
          <br />
          pre-order with ease
        </h1>
      </div>

      <svg
        aria-hidden
        viewBox="0 0 200 200"
        className="pointer-events-none absolute -right-6 top-2 h-32 w-32 text-brand-gold/70"
        fill="none"
        stroke="currentColor"
        strokeWidth={4}
      >
        <circle cx="100" cy="130" r="45" />
        <path strokeLinecap="round" d="M100 40v50M75 55l25-15 25 15" />
      </svg>
    </section>
  );
}
