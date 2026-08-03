"use client";

import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { getItemCount, useCartStore } from "@/lib/cart-store";

const NAV_LINKS = [
  { label: "Shop", href: "#catalogue" },
  { label: "Featured", href: "#featured" },
  { label: "About", href: "#about" },
  { label: "Contact", href: "#contact" },
];

export function StorefrontHeader() {
  const router = useRouter();
  const itemCount = useCartStore((state) => getItemCount(state.items));
  const setCartCollapsed = useCartStore((state) => state.setCartCollapsed);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const [searchValue, setSearchValue] = useState("");

  useEffect(() => {
    function onScroll() {
      setIsScrolled(window.scrollY > 0);
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  function submitSearch(event: React.FormEvent) {
    event.preventDefault();
    const query = searchValue.trim();
    if (query) {
      router.push(`/search?q=${encodeURIComponent(query)}`);
      setIsDrawerOpen(false);
    }
  }

  function openCart() {
    setCartCollapsed(false);
    document.getElementById("cart-sheet")?.scrollIntoView({ behavior: "smooth", block: "end" });
  }

  return (
    <header
      className={`sticky top-0 z-20 bg-brand-cream px-4 py-3 transition-shadow sm:px-6 lg:px-8 ${
        isScrolled ? "shadow-[0_2px_8px_rgba(0,0,0,0.06)]" : ""
      }`}
    >
      <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4">
        <button
          type="button"
          aria-label="Open menu"
          onClick={() => setIsDrawerOpen(true)}
          className="flex h-11 w-11 items-center justify-center rounded-full text-brand-cocoa lg:hidden"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-6 w-6">
            <path strokeLinecap="round" d="M4 7h16M4 12h16M4 17h16" />
          </svg>
        </button>

        <Link href="/" aria-label="ninadough home" className="shrink-0">
          <Image
            src="/brand/ninadough-logo.svg"
            alt="ninadough"
            width={160}
            height={49}
            priority
            className="h-8 w-auto lg:h-9"
          />
        </Link>

        <nav className="hidden items-center gap-6 lg:flex">
          {NAV_LINKS.map((link) => (
            <a
              key={link.href}
              href={link.href}
              className="text-sm font-medium text-brand-cocoa/80 hover:text-brand-cocoa"
            >
              {link.label}
            </a>
          ))}
        </nav>

        <form onSubmit={submitSearch} className="hidden items-center lg:flex">
          <input
            type="search"
            value={searchValue}
            onChange={(event) => setSearchValue(event.target.value)}
            placeholder="Search bakes..."
            className="min-h-9 w-48 rounded-full border border-brand-cocoa/15 px-3 text-sm text-brand-cocoa placeholder:text-brand-cocoa/40"
          />
        </form>

        <button
          type="button"
          aria-label="Search"
          onClick={() => setIsDrawerOpen(true)}
          className="flex h-11 w-11 items-center justify-center rounded-full text-brand-cocoa lg:hidden"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-5 w-5">
            <circle cx="11" cy="11" r="7" />
            <path strokeLinecap="round" d="m21 21-4.3-4.3" />
          </svg>
        </button>

        <button
          type="button"
          aria-label="View cart"
          onClick={openCart}
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
      </div>

      {isDrawerOpen && (
        <div className="fixed inset-0 z-30 lg:hidden">
          <button
            type="button"
            aria-label="Close menu"
            onClick={() => setIsDrawerOpen(false)}
            className="animate-fade-in absolute inset-0 bg-black/40"
          />
          <div className="animate-slide-in-left absolute inset-y-0 left-0 flex w-72 flex-col gap-1 bg-white p-4 shadow-xl">
            <div className="mb-4 flex items-center justify-between">
              <span className="font-display text-lg font-semibold text-brand-cocoa">Menu</span>
              <button
                type="button"
                aria-label="Close menu"
                onClick={() => setIsDrawerOpen(false)}
                className="flex h-9 w-9 items-center justify-center rounded-full text-brand-cocoa/60"
              >
                &times;
              </button>
            </div>

            <form onSubmit={submitSearch}>
              <input
                type="search"
                value={searchValue}
                onChange={(event) => setSearchValue(event.target.value)}
                placeholder="Search bakes..."
                className="mb-3 min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm text-brand-cocoa placeholder:text-brand-cocoa/40"
              />
            </form>

            {NAV_LINKS.map((link) => (
              <a
                key={link.href}
                href={link.href}
                onClick={() => setIsDrawerOpen(false)}
                className="min-h-11 rounded-xl px-3 py-2.5 text-sm font-medium text-brand-cocoa hover:bg-brand-cream"
              >
                {link.label}
              </a>
            ))}
          </div>
        </div>
      )}
    </header>
  );
}
