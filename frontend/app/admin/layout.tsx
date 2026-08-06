"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { adminLogout } from "@/lib/admin-api";

const FOCUS_RING =
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2 rounded";
import { isOwner, useAdminStore } from "@/lib/admin-store";

const OWNER_ONLY_PATHS = ["/admin/products", "/admin/categories", "/admin/preorder-dates"];

const NAV_LINKS = [
  { label: "Dashboard", href: "/admin", ownerOnly: true },
  { label: "Orders", href: "/admin/orders", ownerOnly: false },
  { label: "Products", href: "/admin/products", ownerOnly: true },
  { label: "Categories", href: "/admin/categories", ownerOnly: true },
  { label: "Preorder dates", href: "/admin/preorder-dates", ownerOnly: true },
];

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const token = useAdminStore((state) => state.token);
  const user = useAdminStore((state) => state.user);
  const clearSession = useAdminStore((state) => state.clearSession);

  const isLoginPage = pathname === "/admin/login";
  const isOwnerOnlyPath = pathname === "/admin" || OWNER_ONLY_PATHS.some((path) => pathname.startsWith(path));
  const staffBlocked = !!token && !isOwner(user) && isOwnerOnlyPath;

  useEffect(() => {
    if (!token && !isLoginPage) {
      router.replace("/admin/login");
      return;
    }
    if (staffBlocked) {
      router.replace("/admin/orders");
    }
  }, [token, isLoginPage, staffBlocked, router]);

  if (isLoginPage) {
    return <>{children}</>;
  }

  if (!token || staffBlocked) {
    return null;
  }

  const visibleLinks = NAV_LINKS.filter((link) => !link.ownerOnly || isOwner(user));

  return (
    <div className="min-h-screen bg-brand-cream/40">
      <header className="border-b border-brand-cocoa/10 bg-white px-4 py-3">
        <div className="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center justify-between gap-4 sm:justify-start sm:gap-6">
            <span className="shrink-0 font-display text-lg font-semibold text-brand-cocoa">
              ninadough admin
            </span>

            <div className="flex items-center gap-3 sm:hidden">
              <span className="truncate text-sm text-brand-cocoa/60">{user?.name}</span>
              <button
                type="button"
                onClick={async () => {
                  await adminLogout();
                  clearSession();
                  router.push("/admin/login");
                }}
                className={`shrink-0 text-sm font-medium text-brand-pink ${FOCUS_RING}`}
              >
                Log out
              </button>
            </div>
          </div>

          <nav className="scrollbar-none flex items-center gap-4 overflow-x-auto">
            {visibleLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={`shrink-0 text-sm font-medium ${FOCUS_RING} ${
                  pathname === link.href || (link.href !== "/admin" && pathname.startsWith(link.href))
                    ? "text-brand-pink"
                    : "text-brand-cocoa/70 hover:text-brand-cocoa"
                }`}
              >
                {link.label}
              </Link>
            ))}
          </nav>

          <div className="hidden items-center gap-3 sm:flex">
            <span className="text-sm text-brand-cocoa/60">{user?.name}</span>
            <button
              type="button"
              onClick={async () => {
                await adminLogout();
                clearSession();
                router.push("/admin/login");
              }}
              className={`text-sm font-medium text-brand-pink ${FOCUS_RING}`}
            >
              Log out
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
    </div>
  );
}
