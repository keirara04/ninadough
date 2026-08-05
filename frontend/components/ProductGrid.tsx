import { ProductCard } from "@/components/ProductCard";
import type { Product } from "@/lib/types";

export function ProductGrid({
  products,
  activeCategoryId,
  heading = "All products",
  emptyMessage = "No products available right now — check back soon.",
}: {
  products: Product[];
  activeCategoryId?: number | null;
  heading?: string;
  emptyMessage?: string;
}) {
  const filtered =
    activeCategoryId == null
      ? products
      : products.filter((product) => product.category?.id === activeCategoryId);

  return (
    <section id="catalogue" className="py-12 lg:py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 className="mb-4 font-display text-xl font-bold text-brand-cocoa lg:text-2xl">
          {heading}
        </h2>

        {filtered.length === 0 ? (
          <p className="py-8 text-center text-sm text-brand-cocoa/60">{emptyMessage}</p>
        ) : (
          <div
            key={activeCategoryId ?? "all"}
            className="animate-fade-in grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5"
          >
            {filtered.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
