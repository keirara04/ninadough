import { HeroBanner } from "@/components/HeroBanner";
import { Storefront } from "@/components/Storefront";
import { StorefrontChrome } from "@/components/StorefrontChrome";
import { TrustSection } from "@/components/TrustSection";
import { getCategories, getProducts } from "@/lib/api";

export const dynamic = "force-dynamic";

export default async function Home() {
  const [products, categories] = await Promise.all([
    getProducts(),
    getCategories().catch(() => []),
  ]);

  return (
    <StorefrontChrome>
      <HeroBanner />
      <Storefront products={products} categories={categories} />
      <TrustSection />
    </StorefrontChrome>
  );
}
