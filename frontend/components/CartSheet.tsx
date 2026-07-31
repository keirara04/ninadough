"use client";

import { useState } from "react";
import { DatePickerModal } from "@/components/DatePickerModal";
import { getItemCount, getSubtotalSen, useCartStore, type FulfilmentMethod } from "@/lib/cart-store";
import { formatSen } from "@/lib/format";
import type { PreorderDate } from "@/lib/types";

const WHATSAPP_NUMBER = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "";

export function CartSheet({ preorderDates }: { preorderDates: PreorderDate[] }) {
  const items = useCartStore((state) => state.items);
  const preorderDate = useCartStore((state) => state.preorderDate);
  const fulfilmentMethod = useCartStore((state) => state.fulfilmentMethod);
  const updateQuantity = useCartStore((state) => state.updateQuantity);
  const removeItem = useCartStore((state) => state.removeItem);
  const setPreorderDate = useCartStore((state) => state.setPreorderDate);
  const setFulfilmentMethod = useCartStore((state) => state.setFulfilmentMethod);

  const [isDatePickerOpen, setIsDatePickerOpen] = useState(false);
  const [isExpanded, setIsExpanded] = useState(true);

  if (items.length === 0) {
    return null;
  }

  const itemCount = getItemCount(items);
  const subtotalSen = getSubtotalSen(items);
  const selectedDate = preorderDates.find((date) => date.order_date === preorderDate);

  return (
    <>
      <div className="sticky bottom-0 z-10 rounded-t-2xl border-t border-brand-cocoa/10 bg-white px-4 pb-4 pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.08)]">
        <button
          type="button"
          onClick={() => setIsExpanded((prev) => !prev)}
          className="mb-2 flex w-full items-center justify-between"
        >
          <span className="flex items-center gap-2 font-[family-name:var(--font-display)] font-semibold text-brand-cocoa">
            Your order ({itemCount})
          </span>
          <span className="text-sm font-medium text-brand-pink">
            {isExpanded ? "Hide" : "Edit"}
          </span>
        </button>

        {isExpanded && (
          <ul className="mb-3 flex max-h-40 flex-col gap-2 overflow-y-auto">
            {items.map((line) => (
              <li key={line.variantId} className="flex items-center justify-between gap-2 text-sm">
                <div className="min-w-0">
                  <p className="truncate font-medium text-brand-cocoa">{line.productName}</p>
                  <p className="text-xs text-brand-cocoa/60">
                    {line.variantName} &middot; Qty {line.quantity}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      aria-label="Decrease quantity"
                      onClick={() => updateQuantity(line.variantId, line.quantity - 1)}
                      className="flex h-7 w-7 items-center justify-center rounded-full border border-brand-cocoa/20 text-brand-cocoa"
                    >
                      &minus;
                    </button>
                    <span className="w-4 text-center">{line.quantity}</span>
                    <button
                      type="button"
                      aria-label="Increase quantity"
                      onClick={() => updateQuantity(line.variantId, line.quantity + 1)}
                      className="flex h-7 w-7 items-center justify-center rounded-full border border-brand-cocoa/20 text-brand-cocoa"
                    >
                      +
                    </button>
                  </div>
                  <span className="w-16 text-right font-medium text-brand-cocoa">
                    {formatSen(line.unitPriceSen * line.quantity)}
                  </span>
                  <button
                    type="button"
                    aria-label="Remove item"
                    onClick={() => removeItem(line.variantId)}
                    className="text-brand-cocoa/40"
                  >
                    &times;
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}

        <div className="mb-3 flex items-center justify-between text-sm font-semibold text-brand-cocoa">
          <span>Subtotal</span>
          <span>{formatSen(subtotalSen)}</span>
        </div>

        <button
          type="button"
          onClick={() => setIsDatePickerOpen(true)}
          className="mb-2 flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-brand-pink text-sm font-semibold text-white"
        >
          {selectedDate
            ? `Order for ${new Date(`${selectedDate.order_date}T00:00:00`).toLocaleDateString("en-MY", { day: "numeric", month: "short" })}`
            : "Choose order date"}
        </button>

        <div className="mb-2 grid grid-cols-2 gap-2">
          {(["pickup", "delivery"] as FulfilmentMethod[]).map((method) => (
            <button
              key={method}
              type="button"
              onClick={() => setFulfilmentMethod(method)}
              className={`min-h-11 rounded-full border text-sm font-medium capitalize ${
                fulfilmentMethod === method
                  ? "border-brand-gold bg-brand-gold/20 text-brand-cocoa"
                  : "border-brand-cocoa/15 text-brand-cocoa/70"
              }`}
            >
              {method}
            </button>
          ))}
        </div>

        {WHATSAPP_NUMBER && (
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}`}
            target="_blank"
            rel="noreferrer"
            className="block min-h-11 rounded-full bg-brand-pink/10 py-2.5 text-center text-sm font-medium text-brand-pink"
          >
            Custom orders? Chat with us on WhatsApp
          </a>
        )}
      </div>

      {isDatePickerOpen && (
        <DatePickerModal
          dates={preorderDates}
          onClose={() => setIsDatePickerOpen(false)}
          onSelect={(date) => {
            setPreorderDate(date.order_date);
            setIsDatePickerOpen(false);
          }}
        />
      )}
    </>
  );
}
