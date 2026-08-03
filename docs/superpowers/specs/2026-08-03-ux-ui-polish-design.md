# UX/UI Polish Pass — Design Spec

Date: 2026-08-03
Status: Approved, implementing

## Context

A senior-engineer UX/UI audit of the whole frontend (storefront + admin) surfaced a consistent root cause behind most issues: **no shared Button/Toast/focus primitives**. Every "did this work?" signal is either a copy-pasted color-swap + `setTimeout` hack (storefront add-to-cart) or nothing at all (admin mutations fail or succeed silently, destructive deletes fire with no confirmation). Keyboard/focus handling is effectively absent — no `focus-visible` rings anywhere, no Escape-to-close on modals/drawers.

This spec covers both storefront and admin in one pass, built on a shared foundation, since the fixes in each area depend on the same three primitives.

## Foundation primitives (`components/ui/`)

No new dependencies — matches this app's existing zero-UI-library approach (plain Tailwind, zustand for state).

**`Button.tsx`** — single component replacing hand-rolled button classNames across the app.
- `variant`: `primary` (pink, main CTAs) / `secondary` (cocoa outline) / `danger` (red, admin deletes) / `ghost` (icon-only, header/nav)
- `size`: `default` (`min-h-11`) / `sm` (`min-h-9`, admin tables/forms)
- `isLoading`: shows an inline spinner, forces `disabled`, prevents double-submit
- Built-in `focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2` on every instance

**`ToastProvider.tsx` + `useToast()`** — React context rendered once near the root (in `app/layout.tsx`). `useToast().show(message, variant?)` where `variant` is `"success" | "error"` (default success). Rendered in an `aria-live="polite"` region so screen readers announce it. Auto-dismisses after ~3s, stacks if multiple fire.

**`ConfirmDialog.tsx` + `useConfirm()`** — `useConfirm()` returns a function `(message) => Promise<boolean>`; renders a small modal (reusing the same overlay pattern as `DatePickerModal`) with Confirm/Cancel buttons. Used before any destructive action.

**Modal/drawer keyboard handling** — `DatePickerModal` and the `StorefrontHeader` mobile drawer both get: Escape key closes them, focus moves to the close button on open. Explicitly **not** building a full Tab-cycling focus trap — scoped out as lower-value-for-effort here.

## Storefront changes

- `ProductCard` / `ProductDetail`: remove the `justAdded`/`added` local-state + `setTimeout` pattern entirely. `addItem()` call followed by `toast.show("Added to cart")`.
- `CategoryStrip` pills and `StorefrontHeader` nav links: restyle through the shared `Button` (`ghost`/`secondary`) so they inherit the focus ring without per-component manual styling.
- Checkout page (`app/checkout/page.tsx`): delivery-zone fetch failure currently sets `[]` silently — add a visible inline error line above the zone `<select>`, matching the existing `quoteError` UI already on that page.
- Order status page (`app/orders/[reference]/page.tsx`): distinguish `ApiError` with `status === 404` ("This order wasn't found") from other failures ("Something went wrong — try again") instead of collapsing both into one message.
- Cart quantity guard: no change — confirmed `updateQuantity` in `lib/cart-store.ts` already removes the line at `quantity <= 0`; this was a false alarm from the audit, just not obvious on first read.

## Admin changes

- Every mutation (order status change, payment approve/reject, category create/update/delete, product create/update/delete/toggle) — button becomes `<Button isLoading={...}>`, call wrapped in try/catch, success → `toast.show(...)`, failure → `toast.show(..., "error")`.
- Category and product delete routed through `useConfirm()` before firing.
- Category inline-rename-on-blur (`app/admin/categories/page.tsx`) gets the same try/catch + toast pattern instead of silently reverting on failure.
- `AdminOrderDetailPage` (`app/admin/orders/[id]/page.tsx`): add an explicit error branch — currently only has loading/loaded states, so a rejected fetch leaves the page stuck on "Loading..." forever.

## Data flow / error handling

Purely a frontend change — no backend modifications. Every API call already returns proper error shapes (`ApiError` with `.status` / `.errors` from `lib/api.ts`); this pass is about actually surfacing what's already available rather than discarding it. `Toast` and `ConfirmDialog` state is client-only React context, nothing persisted.

## Testing / verification

Consistent with how this project has been verified throughout: `tsc --noEmit`, curl-based page-render checks after each component change, and manual scripted checks of the newly-added error paths (e.g., temporarily breaking a fetch URL to confirm the inline error actually renders instead of silently failing). No test suite exists in this repo; not introducing one for a UI-polish pass.

## Out of scope

- Full Tab-cycling focus trap in modals/drawers (Escape + initial focus only)
- Design token file / theme abstraction beyond the existing Tailwind `@theme` block in `globals.css`
- Any backend changes
