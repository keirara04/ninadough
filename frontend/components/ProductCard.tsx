"use client";

import Image from "next/image";
import { useState } from "react";
import { useCartStore } from "@/lib/cart-store";
import { formatSen } from "@/lib/format";
import type { Product } from "@/lib/types";

export function ProductCard({ product }: { product: Product }) {
  const addItem = useCartStore((state) => state.addItem);
  const [selectedVariantId, setSelectedVariantId] = useState(
    product.variants[0]?.id ?? null,
  );
  const [isFavourited, setIsFavourited] = useState(false);

  const selectedVariant =
    product.variants.find((variant) => variant.id === selectedVariantId) ??
    product.variants[0];

  const primaryImage = product.images.find((image) => image.is_primary) ?? product.images[0];

  if (!selectedVariant) {
    return null;
  }

  return (
    <div className="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm">
      <div className="relative aspect-square w-full bg-brand-cream">
        {primaryImage?.url ? (
          <Image
            src={primaryImage.url}
            alt={primaryImage.alt_text ?? product.name}
            fill
            sizes="(max-width: 640px) 50vw, 300px"
            className="object-cover"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-brand-cocoa/30">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-12 w-12">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <path strokeLinecap="round" strokeLinejoin="round" d="m21 15-5-5L5 21" />
            </svg>
          </div>
        )}

        <button
          type="button"
          aria-label={isFavourited ? "Remove from favourites" : "Add to favourites"}
          aria-pressed={isFavourited}
          onClick={() => setIsFavourited((prev) => !prev)}
          className="absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-full bg-white/90"
        >
          <svg
            viewBox="0 0 24 24"
            fill={isFavourited ? "currentColor" : "none"}
            stroke="currentColor"
            strokeWidth={2}
            className="h-5 w-5 text-brand-pink"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M12 21s-7-4.6-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.4-9.5 9-9.5 9Z"
            />
          </svg>
        </button>
      </div>

      <div className="flex flex-1 flex-col gap-2 p-3">
        <h3 className="font-[family-name:var(--font-display)] text-sm font-semibold text-brand-cocoa">
          {product.name}
        </h3>

        {product.variants.length > 1 && (
          <div>
            <p className="mb-1 text-xs text-brand-cocoa/60">Flavour</p>
            <div className="flex flex-wrap gap-1.5">
              {product.variants.map((variant) => (
                <button
                  key={variant.id}
                  type="button"
                  onClick={() => setSelectedVariantId(variant.id)}
                  className={`rounded-full border px-2.5 py-1 text-xs font-medium transition ${
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

        <div className="mt-auto flex items-center justify-between pt-1">
          <span className="text-base font-bold text-brand-cocoa">
            {formatSen(selectedVariant.price_sen)}
          </span>
          <button
            type="button"
            onClick={() =>
              addItem({
                productId: product.id,
                variantId: selectedVariant.id,
                productName: product.name,
                variantName: selectedVariant.option_values.join(" ") || selectedVariant.name,
                unitPriceSen: selectedVariant.price_sen,
                capacityUnitsEach: selectedVariant.capacity_units,
              })
            }
            className="min-h-11 rounded-full bg-brand-pink px-5 text-sm font-semibold text-white"
          >
            Add
          </button>
        </div>
      </div>
    </div>
  );
}
