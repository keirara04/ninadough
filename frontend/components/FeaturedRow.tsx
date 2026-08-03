import { ProductCard } from "@/components/ProductCard";
import type { Product } from "@/lib/types";

export function FeaturedRow({ products }: { products: Product[] }) {
  const featured = products.filter((product) => product.is_featured);
  const list = featured.length > 0 ? featured : products.slice(0, 4);

  if (list.length < 4) {
    return null;
  }

  return (
    <section id="featured" className="py-12 lg:py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 className="mb-4 font-display text-xl font-bold text-brand-cocoa lg:text-2xl">
          Featured
        </h2>
        <div className="scrollbar-none flex snap-x gap-3 overflow-x-auto pb-2 lg:grid lg:grid-cols-4 lg:gap-4 lg:overflow-visible">
          {list.map((product) => (
            <div key={product.id} className="w-40 shrink-0 snap-start sm:w-48 lg:w-auto">
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
