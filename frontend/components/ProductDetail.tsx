"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { useCartStore } from "@/lib/cart-store";
import { formatSen } from "@/lib/format";
import type { Product } from "@/lib/types";

export function ProductDetail({ product }: { product: Product }) {
  const addItem = useCartStore((state) => state.addItem);
  const [selectedVariantId, setSelectedVariantId] = useState(product.variants[0]?.id ?? null);
  const [added, setAdded] = useState(false);

  const selectedVariant =
    product.variants.find((variant) => variant.id === selectedVariantId) ?? product.variants[0];
  const primaryImage = product.images.find((image) => image.is_primary) ?? product.images[0];

  if (!selectedVariant) {
    return (
      <p className="px-4 py-10 text-center text-sm text-brand-cocoa/60">
        This product isn&apos;t available right now.
      </p>
    );
  }

  return (
    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
      <nav className="mb-4 text-xs text-brand-cocoa/50">
        <Link href="/" className="hover:text-brand-cocoa">
          Home
        </Link>
        {product.category && (
          <>
            {" / "}
            <Link href={`/categories/${product.category.slug}`} className="hover:text-brand-cocoa">
              {product.category.name}
            </Link>
          </>
        )}
        {" / "}
        <span className="text-brand-cocoa">{product.name}</span>
      </nav>

      <div className="flex flex-col gap-8 lg:flex-row">
        <div className="relative aspect-square w-full overflow-hidden rounded-3xl bg-brand-cream lg:w-1/2">
          {primaryImage?.url ? (
            <Image
              src={primaryImage.url}
              alt={primaryImage.alt_text ?? product.name}
              fill
              sizes="(max-width: 1024px) 100vw, 50vw"
              className="object-cover"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-brand-cocoa/30">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-16 w-16">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="8.5" cy="8.5" r="1.5" />
                <path strokeLinecap="round" strokeLinejoin="round" d="m21 15-5-5L5 21" />
              </svg>
            </div>
          )}
        </div>

        <div className="flex flex-col gap-4 lg:w-1/2">
          <h1 className="font-display text-2xl font-bold text-brand-cocoa lg:text-3xl">
            {product.name}
          </h1>

          {product.short_description && (
            <p className="text-sm text-brand-cocoa/70">{product.short_description}</p>
          )}

          <p className="text-2xl font-bold text-brand-cocoa">{formatSen(selectedVariant.price_sen)}</p>

          {product.variants.length > 1 && (
            <div>
              <p className="mb-1.5 text-xs font-medium text-brand-cocoa/60">Flavour</p>
              <div className="flex flex-wrap gap-2">
                {product.variants.map((variant) => (
                  <button
                    key={variant.id}
                    type="button"
                    onClick={() => setSelectedVariantId(variant.id)}
                    className={`min-h-9 rounded-full border px-3 text-sm font-medium transition ${
                      variant.id === selectedVariant.id
                        ? "border-brand-pink bg-brand-pink text-white"
                        : "border-brand-cocoa/20 text-brand-cocoa/80"
                    }`}
                  >
                    {variant.option_values.join(" ") || variant.name}
                  </button>
                ))}
              </div>
            </div>
          )}

          <button
            type="button"
            onClick={() => {
              addItem({
                productId: product.id,
                variantId: selectedVariant.id,
                productName: product.name,
                variantName: selectedVariant.option_values.join(" ") || selectedVariant.name,
                unitPriceSen: selectedVariant.price_sen,
                capacityUnitsEach: selectedVariant.capacity_units,
              });
              setAdded(true);
              setTimeout(() => setAdded(false), 1500);
            }}
            className={`min-h-11 rounded-full px-6 text-sm font-semibold text-white transition-all active:scale-95 ${
              added ? "bg-green-600" : "bg-brand-pink"
            }`}
          >
            {added ? "Added!" : "Add to cart"}
          </button>

          {product.description && (
            <div>
              <h2 className="mb-1 text-xs font-medium uppercase tracking-wide text-brand-cocoa/50">
                Description
              </h2>
              <p className="text-sm text-brand-cocoa/70">{product.description}</p>
            </div>
          )}

          {product.allergen_information && (
            <div>
              <h2 className="mb-1 text-xs font-medium uppercase tracking-wide text-brand-cocoa/50">
                Allergen information
              </h2>
              <p className="text-sm text-brand-cocoa/70">{product.allergen_information}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
