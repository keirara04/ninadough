# Homepage Revamp — Design Spec

Date: 2026-08-03
Status: Approved, ready for implementation planning

## Context

Ninadough's current homepage is a single mobile-first column: sticky header (non-functional hamburger), a decorative hero, a plain product grid, and a persistent cart bar. It works but doesn't read as a professional ecommerce storefront — no categories, no featured/curated section, no trust-building content, no footer.

The original ask was "revamp the whole homepage" with a scope answer of "full rebuild." Because a true full rebuild (nav + search + category pages + product detail pages + homepage) spans multiple independent subsystems, this spec **scopes to the homepage only**. Category/collection pages, dedicated product detail pages, and working site-wide search are deferred to later specs. Nav links to categories are anchor-scrolls to a filtered section of this same page, not new routes.

Current catalogue: 12 products, mostly factory-seeded placeholder data (`Sed itaque sed`, `Consequuntur qui sint`, etc.) with one real entry (`Chocolate Fudge Cake`). No product photos are uploaded yet — `product_images.public_url` is empty on every row. Category and featured-flag seed data in this spec will necessarily be placeholder-appropriate (arbitrary assignment), not reflective of a real catalogue.

## Backend changes

### `product_categories` table
- `id`, `name` (string, 120), `slug` (string, 120, unique), `sort_order` (int, default 0), timestamps.
- No soft deletes needed (categories are low-churn admin data).

### `products.category_id`
- Nullable FK to `product_categories`, `nullOnDelete()`.
- Nullable because a product without a category should still render in the full grid, just excluded from category-filtered views.

### `products.is_featured`
- Boolean, default `false`.

### Seed data
- 4 categories: Cakes, Cookies, Pastries, Drinks (generic bakery taxonomy, placeholder-appropriate given the current seed data; the business owner can rename/reorganize later via direct DB edit since there's no admin UI yet).
- Existing 12 products get `category_id` assigned round-robin across the 4 categories in the seeder/factory, and `is_featured = true` on 3–4 of them (enough to populate the featured row without flagging everything).

### API changes
- `ProductResource` gains `category: { id, name, slug } | null` and `is_featured: bool`.
- New `GET /v1/categories` → `CategoryResource` collection (`id`, `name`, `slug`, `sort_order`), ordered by `sort_order`. Mirrors the existing `DeliveryZoneController` pattern (simple index, no auth, no throttle group needed — matches `/products` and `/preorder-dates`).
- `ProductController@index` unchanged: still all active products in one response, eager-loading `category` alongside existing relations.

## Frontend structure

### New/changed types & API (`lib/types.ts`, `lib/api.ts`)
- `ProductCategory { id, name, slug, sort_order }`
- `Product.category: ProductCategory | null`, `Product.is_featured: boolean`
- `getCategories(): Promise<ProductCategory[]>`, called in `Promise.all` alongside `getProducts()`/`getPreorderDates()` in `app/page.tsx`. Failure is caught independently (`.catch(() => [])`) so a categories outage doesn't break the whole page.

### Component changes

**`StorefrontHeader`** (rewritten): logo + text nav (`Shop`, `Featured`, `About`, `Contact` — anchor links to section ids) visible at `lg:`, collapsed behind a working hamburger below `lg:` that opens a slide-in drawer (simple client-side `useState` toggle, no new deps) with the same links. Search `<input>` visible at `lg:` in the bar, icon-trigger only below `lg:` (opens the same drawer to a search field — non-functional placeholder, wired in the future search spec). Cart icon/count unchanged. Sticky, adds a shadow once `window.scrollY > 0` (small scroll listener) instead of always-on backdrop-blur, since blur is being replaced with a real background transition.

**`HeroBanner`** (rewritten): headline + subcopy + primary CTA button ("Shop preorders", anchor-scrolls to `#catalogue`) on the left, an `aspect-[4/3]` image slot on the right (`lg:` — stacks above text on mobile) using a static placeholder graphic (reuse/expand the existing gold circle motif) since no product photography exists. Structured so swapping in a real `<Image>` later is a one-line change (slot is already an `Image`-shaped container).

**`CategoryStrip`** (new): horizontal scrollable row of rounded-full pill buttons, one per category + an "All" pill, `active` state styling (filled cocoa/gold vs outline). Scrollbar hidden via a small CSS utility. On `lg:`, wraps to a static inline row instead of scrolling (enough categories to fit). Emits `onSelect(categoryId | null)`.

**`FeaturedRow`** (new): horizontally snap-scrolling row of `ProductCard`s where `product.is_featured`. Falls back to first 4 products by `sort_order` if none are flagged. Hides entirely if the resulting list has fewer than 4 items (avoids a near-duplicate of the grid below it). Static row (no scroll) at `lg:`.

**`ProductGrid`** (updated): accepts an `activeCategoryId` prop, filters `products` client-side before rendering. No change to `ProductCard` itself.

**`Storefront`** (new client component, wraps sections 3–5): owns `activeCategoryId` state, passes it to `CategoryStrip` and `ProductGrid`. This is the one new piece of client interactivity glue — everything else in `app/page.tsx` stays server-rendered.

**`TrustSection`** (new): `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`, 4 columns, each an icon + short heading + one line of real fact sourced from `BusinessSetting` values already in the system (preorder cutoff behavior, `pickup_instructions`, `bank_transfer_instructions` summarized, WhatsApp support). Since `BusinessSetting` isn't currently exposed via API, this section's copy is static text mirroring the known seeded values (not live-fetched) — matches how `WHATSAPP_NUMBER` is already handled via `NEXT_PUBLIC_WHATSAPP_NUMBER` env var elsewhere in the app.

**`Footer`** (new): dark cocoa background, cream text, 3 columns (Business info / Ordering info / Contact) stacking on mobile. Content: business name, WhatsApp link, pickup instructions, privacy contact email, copyright line with current year.

### Page assembly (`app/page.tsx`)
```
<StorefrontHeader />
<HeroBanner />
<Storefront products categories>   -- client component
  <CategoryStrip />
  <FeaturedRow />
  <ProductGrid id="catalogue" />
</Storefront>
<TrustSection />
<Footer />
<CartSheet />                      -- unchanged, stays persistent
```

## Error handling

- `getCategories()` failure/empty → `CategoryStrip` and category filtering don't render; `ProductGrid` shows all products. Page doesn't break.
- `FeaturedRow` hides if fewer than 4 qualifying products.
- Existing `getProducts()`/`getPreorderDates()` failure handling (page-level, via existing `force-dynamic` + fetch) is unchanged.

## Testing

- Backend: new Feature test for `GET /v1/categories` (returns seeded categories, ordered). Update existing `ProductApiTest` to assert `category` and `is_featured` keys appear in the resource response.
- Frontend: no test suite currently exists in the repo; this spec doesn't introduce one. Verification is manual — dev server render checks + curl against the new endpoint, consistent with how the checkout flow was verified.

## Out of scope (deferred to later specs)
- Category/collection browsing pages (real routes, not anchor-scroll)
- Product detail pages
- Working search (backend query + results)
- Real product photography
- Admin UI for managing categories/featured flag (seeded directly for now)
