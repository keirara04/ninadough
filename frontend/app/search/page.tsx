import { ProductGrid } from "@/components/ProductGrid";
import { StorefrontChrome } from "@/components/StorefrontChrome";
import { getProducts } from "@/lib/api";

export const dynamic = "force-dynamic";

export default async function SearchPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string }>;
}) {
  const { q } = await searchParams;
  const query = q?.trim() ?? "";

  const products = query ? await getProducts({ q: query }) : [];

  return (
    <StorefrontChrome>
      <ProductGrid
        products={products}
        heading={query ? `Results for "${query}"` : "Search"}
        emptyMessage={
          query
            ? `No products match "${query}" — try a different search.`
            : "Enter a search term above to find bakes."
        }
      />
    </StorefrontChrome>
  );
}
