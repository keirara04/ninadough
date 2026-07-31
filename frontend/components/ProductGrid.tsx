import { ProductCard } from "@/components/ProductCard";
import type { Product } from "@/lib/types";

export function ProductGrid({ products }: { products: Product[] }) {
  if (products.length === 0) {
    return (
      <p className="px-4 py-8 text-center text-sm text-brand-cocoa/60">
        No products available right now — check back soon.
      </p>
    );
  }

  return (
    <div className="grid grid-cols-2 gap-3 px-4 pb-4">
      {products.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  );
}
