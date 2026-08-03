import { notFound } from "next/navigation";
import { ProductGrid } from "@/components/ProductGrid";
import { StorefrontChrome } from "@/components/StorefrontChrome";
import { getCategories, getProducts } from "@/lib/api";

export const dynamic = "force-dynamic";

export default async function CategoryPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;

  const [categories, products] = await Promise.all([
    getCategories().catch(() => []),
    getProducts({ category: slug }),
  ]);

  const category = categories.find((item) => item.slug === slug);

  if (!category) {
    notFound();
  }

  return (
    <StorefrontChrome>
      <ProductGrid
        products={products}
        heading={category.name}
        emptyMessage={`No ${category.name.toLowerCase()} available right now — check back soon.`}
      />
    </StorefrontChrome>
  );
}
