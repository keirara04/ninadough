"use client";

import Image from "next/image";
import { getItemCount, useCartStore } from "@/lib/cart-store";

export function StorefrontHeader() {
  const itemCount = useCartStore((state) => getItemCount(state.items));

  return (
    <header className="sticky top-0 z-20 flex items-center justify-between gap-4 bg-brand-cream/95 px-4 py-3 backdrop-blur">
      <button
        type="button"
        aria-label="Open menu"
        className="flex h-11 w-11 items-center justify-center rounded-full text-brand-cocoa"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-6 w-6">
          <path strokeLinecap="round" d="M4 7h16M4 12h16M4 17h16" />
        </svg>
      </button>

      <Image
        src="/brand/ninadough-logo.svg"
        alt="ninadough"
        width={160}
        height={49}
        priority
        className="h-8 w-auto"
      />

      <button
        type="button"
        aria-label="View cart"
        className="relative flex h-11 w-11 items-center justify-center rounded-full text-brand-pink"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-6 w-6">
          <path strokeLinecap="round" strokeLinejoin="round" d="M6 7V6a3 3 0 1 1 6 0v1m-8 0h10l1 12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
        </svg>
        {itemCount > 0 && (
          <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-pink px-1 text-xs font-semibold text-white">
            {itemCount}
          </span>
        )}
      </button>
    </header>
  );
}
