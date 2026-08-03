const WHATSAPP_NUMBER = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "";

export function Footer() {
  return (
    <footer id="contact" className="bg-brand-cocoa text-brand-cream/80">
      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-8 sm:grid-cols-3">
          <div>
            <p className="font-display text-lg font-semibold text-white">ninadough</p>
            <p className="mt-2 text-sm">Fresh bakes, made to order.</p>
          </div>

          <div>
            <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-cream/50">
              Ordering
            </h3>
            <p className="text-sm">Pickup at our kitchen, 10am–6pm.</p>
            <p className="mt-1 text-sm">Bank transfer, confirm via WhatsApp.</p>
          </div>

          <div>
            <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-cream/50">
              Contact
            </h3>
            {WHATSAPP_NUMBER && (
              <p className="text-sm">
                <a href={`https://wa.me/${WHATSAPP_NUMBER}`} className="hover:text-white">
                  WhatsApp us
                </a>
              </p>
            )}
            <p className="mt-1 text-sm">
              <a href="mailto:privacy@ninadough.test" className="hover:text-white">
                privacy@ninadough.test
              </a>
            </p>
          </div>
        </div>

        <p className="mt-8 border-t border-brand-cream/10 pt-4 text-xs text-brand-cream/40">
          &copy; {new Date().getFullYear()} ninadough. All rights reserved.
        </p>
      </div>
    </footer>
  );
}
