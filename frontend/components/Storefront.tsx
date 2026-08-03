"use client";

import { useState } from "react";
import { CategoryStrip } from "@/components/CategoryStrip";
import { FeaturedRow } from "@/components/FeaturedRow";
import { ProductGrid } from "@/components/ProductGrid";
import type { Product, ProductCategory } from "@/lib/types";

export function Storefront({
  products,
  categories,
}: {
  products: Product[];
  categories: ProductCategory[];
}) {
  const [activeCategoryId, setActiveCategoryId] = useState<number | null>(null);

  return (
    <>
      <div className="pt-8 lg:pt-12">
        <CategoryStrip
          categories={categories}
          activeCategoryId={activeCategoryId}
          onSelect={setActiveCategoryId}
        />
      </div>
      <FeaturedRow products={products} />
      <ProductGrid products={products} activeCategoryId={activeCategoryId} />
    </>
  );
}
