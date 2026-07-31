import { CartSheet } from "@/components/CartSheet";
import { HeroBanner } from "@/components/HeroBanner";
import { ProductGrid } from "@/components/ProductGrid";
import { StorefrontHeader } from "@/components/StorefrontHeader";
import { getPreorderDates, getProducts } from "@/lib/api";

export const dynamic = "force-dynamic";

export default async function Home() {
  const [products, preorderDates] = await Promise.all([
    getProducts(),
    getPreorderDates(),
  ]);

  return (
    <div className="flex flex-1 flex-col">
      <StorefrontHeader />
      <HeroBanner />
      <main className="flex-1">
        <ProductGrid products={products} />
      </main>
      <CartSheet preorderDates={preorderDates} />
    </div>
  );
}
