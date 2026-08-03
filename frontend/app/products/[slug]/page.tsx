import { notFound } from "next/navigation";
import { ProductDetail } from "@/components/ProductDetail";
import { StorefrontChrome } from "@/components/StorefrontChrome";
import { getProduct } from "@/lib/api";

export const dynamic = "force-dynamic";

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;

  const product = await getProduct(slug).catch(() => null);

  if (!product) {
    notFound();
  }

  return (
    <StorefrontChrome>
      <ProductDetail product={product} />
    </StorefrontChrome>
  );
}
