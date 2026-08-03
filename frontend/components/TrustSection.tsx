const WHATSAPP_NUMBER = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "";

const FACTS = [
  {
    title: "Preorder, always fresh",
    body: "Every bake is made to order for a specific date — nothing sits on a shelf.",
    icon: CalendarIcon,
  },
  {
    title: "Pickup or delivery",
    body: "Collect from our kitchen, or have it delivered to your door within Klang Valley.",
    icon: BagIcon,
  },
  {
    title: "Simple payment",
    body: "Pay by bank transfer, then send your proof of payment straight over WhatsApp.",
    icon: BankIcon,
  },
  {
    title: "Real human support",
    body: "Questions about your order? Message us directly, no chatbots.",
    icon: ChatIcon,
  },
];

export function TrustSection() {
  return (
    <section id="about" className="bg-brand-cream/60 py-12 lg:py-20">
      <div className="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
        {FACTS.map((fact) => (
          <div key={fact.title} className="flex flex-col gap-2">
            <fact.icon className="h-8 w-8 text-brand-pink" />
            <h3 className="font-display text-sm font-semibold text-brand-cocoa">{fact.title}</h3>
            <p className="text-sm text-brand-cocoa/70">{fact.body}</p>
          </div>
        ))}
        {WHATSAPP_NUMBER && (
          <p className="col-span-full text-center text-xs text-brand-cocoa/50">
            Chat with us anytime on{" "}
            <a href={`https://wa.me/${WHATSAPP_NUMBER}`} className="font-medium text-brand-pink">
              WhatsApp
            </a>
            .
          </p>
        )}
      </div>
    </section>
  );
}

function CalendarIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} {...props}>
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path strokeLinecap="round" d="M8 3v4M16 3v4M3 10h18" />
    </svg>
  );
}

function BagIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} {...props}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M6 7V6a3 3 0 1 1 6 0v1m-8 0h10l1 12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
    </svg>
  );
}

function BankIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} {...props}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3 10l9-6 9 6M4 10v9h16v-9M9 19v-6h6v6" />
    </svg>
  );
}

function ChatIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} {...props}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M21 11.5a8.38 8.38 0 0 1-8.5 8.4A8.5 8.5 0 0 1 4 15.5 8.38 8.38 0 0 1 12.5 3a8.5 8.5 0 0 1 8.5 8.5Z" />
    </svg>
  );
}
