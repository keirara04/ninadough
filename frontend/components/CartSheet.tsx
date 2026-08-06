"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { DatePickerModal } from "@/components/DatePickerModal";
import { ApiError, getTimeSlots, lookupDeliveryFee } from "@/lib/api";
import {
  getItemCount,
  getMaxLeadTimeDays,
  getSubtotalSen,
  useCartStore,
  type FulfilmentMethod,
} from "@/lib/cart-store";
import { formatSen } from "@/lib/format";
import type { PreorderDate, TimeSlot } from "@/lib/types";

const WHATSAPP_NUMBER = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "";

export function CartSheet({ preorderDates }: { preorderDates: PreorderDate[] }) {
  const router = useRouter();
  const items = useCartStore((state) => state.items);
  const preorderDate = useCartStore((state) => state.preorderDate);
  const fulfilmentMethod = useCartStore((state) => state.fulfilmentMethod);
  const updateQuantity = useCartStore((state) => state.updateQuantity);
  const removeItem = useCartStore((state) => state.removeItem);
  const setPreorderDate = useCartStore((state) => state.setPreorderDate);
  const setFulfilmentMethod = useCartStore((state) => state.setFulfilmentMethod);
  const timeSlotId = useCartStore((state) => state.timeSlotId);
  const setTimeSlotId = useCartStore((state) => state.setTimeSlotId);
  const postcode = useCartStore((state) => state.postcode);
  const setPostcode = useCartStore((state) => state.setPostcode);
  const deliveryQuote = useCartStore((state) => state.deliveryQuote);
  const setDeliveryQuote = useCartStore((state) => state.setDeliveryQuote);
  const isCollapsed = useCartStore((state) => state.isCartCollapsed);
  const setIsCollapsed = useCartStore((state) => state.setCartCollapsed);

  const [isDatePickerOpen, setIsDatePickerOpen] = useState(false);
  const [isExpanded, setIsExpanded] = useState(true);
  const [timeSlots, setTimeSlots] = useState<TimeSlot[]>([]);
  const [postcodeError, setPostcodeError] = useState<string | null>(null);

  useEffect(() => {
    if (fulfilmentMethod !== "delivery" || !postcode || postcode.trim().length < 4) {
      return;
    }

    let cancelled = false;
    const timeout = window.setTimeout(() => {
      Promise.resolve()
        .then(() => setPostcodeError(null))
        .then(() => lookupDeliveryFee(postcode.trim()))
        .then((zone) => {
          if (cancelled) return;
          setDeliveryQuote({ postcode: postcode.trim(), zoneName: zone.name, deliveryFeeSen: zone.delivery_fee_sen });
        })
        .catch((error: unknown) => {
          if (cancelled) return;
          setDeliveryQuote(null);
          setPostcodeError(error instanceof ApiError ? error.message : "Couldn't look up this postcode.");
        });
    }, 400);

    return () => {
      cancelled = true;
      window.clearTimeout(timeout);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [postcode, fulfilmentMethod]);

  useEffect(() => {
    if (!preorderDate) {
      Promise.resolve().then(() => setTimeSlots([]));
      return;
    }

    let cancelled = false;
    Promise.resolve()
      .then(() => getTimeSlots(preorderDate, fulfilmentMethod))
      .then((slots) => {
        if (!cancelled) setTimeSlots(slots);
      })
      .catch(() => {
        if (!cancelled) setTimeSlots([]);
      });

    return () => {
      cancelled = true;
    };
  }, [preorderDate, fulfilmentMethod]);

  if (items.length === 0) {
    return null;
  }

  // Mandatory per plan §0a: a non-empty slot list for this date+method means
  // a slot must be chosen before checkout; an empty list means slots aren't
  // configured for this date/method at all, so the step is skipped entirely.
  const slotRequired = timeSlots.length > 0;
  const slotSatisfied = !slotRequired || timeSlotId !== null;

  const itemCount = getItemCount(items);
  const subtotalSen = getSubtotalSen(items);
  const selectedDate = preorderDates.find((date) => date.order_date === preorderDate);

  if (isCollapsed) {
    return (
      <button
        id="cart-sheet"
        type="button"
        onClick={() => setIsCollapsed(false)}
        className="animate-slide-up sticky bottom-4 z-10 mx-auto flex min-h-11 w-[calc(100%-2rem)] max-w-md items-center justify-between rounded-full bg-brand-cocoa px-5 text-sm font-semibold text-white shadow-lg transition-transform active:scale-[0.98] lg:max-w-lg"
      >
        <span>Your order ({itemCount})</span>
        <span>{formatSen(subtotalSen)}</span>
      </button>
    );
  }

  return (
    <>
      <div
        id="cart-sheet"
        className="animate-slide-up sticky bottom-0 z-10 rounded-t-2xl border-t border-brand-cocoa/10 bg-white px-4 pb-4 pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] sm:px-6 lg:px-8"
      >
        <div className="mx-auto w-full max-w-md lg:max-w-lg">
        <div className="mb-2 flex w-full items-center justify-between">
          <button
            type="button"
            onClick={() => setIsExpanded((prev) => !prev)}
            className="flex items-center gap-2 font-display font-semibold text-brand-cocoa"
          >
            Your order ({itemCount})
            <span className="text-sm font-medium text-brand-pink">
              {isExpanded ? "Hide" : "Edit"}
            </span>
          </button>
          <button
            type="button"
            aria-label="Minimize cart"
            onClick={() => setIsCollapsed(true)}
            className="flex h-8 w-8 items-center justify-center rounded-full text-brand-cocoa/50 transition-transform active:scale-90"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-4 w-4">
              <path strokeLinecap="round" strokeLinejoin="round" d="m6 9 6 6 6-6" />
            </svg>
          </button>
        </div>

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
                      className="flex h-7 w-7 items-center justify-center rounded-full border border-brand-cocoa/20 text-brand-cocoa transition-transform active:scale-90"
                    >
                      &minus;
                    </button>
                    <span className="w-4 text-center">{line.quantity}</span>
                    <button
                      type="button"
                      aria-label="Increase quantity"
                      onClick={() => updateQuantity(line.variantId, line.quantity + 1)}
                      className="flex h-7 w-7 items-center justify-center rounded-full border border-brand-cocoa/20 text-brand-cocoa transition-transform active:scale-90"
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
          className="mb-2 flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-brand-pink text-sm font-semibold text-white transition-transform active:scale-[0.98]"
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
              className={`min-h-11 rounded-full border text-sm font-medium capitalize transition active:scale-[0.98] ${
                fulfilmentMethod === method
                  ? "border-brand-gold bg-brand-gold/20 text-brand-cocoa"
                  : "border-brand-cocoa/15 text-brand-cocoa/70"
              }`}
            >
              {method}
            </button>
          ))}
        </div>

        {fulfilmentMethod === "delivery" && (
          <div className="mb-2">
            <label className="mb-1.5 block text-xs font-medium text-brand-cocoa/70">
              Delivery postcode
            </label>
            <input
              value={postcode ?? ""}
              onChange={(event) => setPostcode(event.target.value)}
              placeholder="e.g. 50450"
              className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
            />
            {postcodeError && <p className="mt-1 text-xs text-red-600">{postcodeError}</p>}
            {deliveryQuote && (
              <p className="mt-1 text-xs text-brand-cocoa/60">
                {deliveryQuote.zoneName} &middot; {formatSen(deliveryQuote.deliveryFeeSen)} delivery
              </p>
            )}
          </div>
        )}

        {slotRequired && (
          <div className="mb-2">
            <p className="mb-1.5 text-xs font-medium text-brand-cocoa/70">Choose a time slot</p>
            <div className="flex flex-wrap gap-2">
              {timeSlots.map((slot) => {
                const isFull = slot.remaining_capacity <= 0;
                return (
                  <button
                    key={slot.id}
                    type="button"
                    disabled={isFull}
                    onClick={() => setTimeSlotId(slot.id)}
                    className={`min-h-11 rounded-full border px-3 text-sm font-medium transition active:scale-[0.98] ${
                      timeSlotId === slot.id
                        ? "border-brand-gold bg-brand-gold/20 text-brand-cocoa"
                        : isFull
                          ? "border-brand-cocoa/10 text-brand-cocoa/30"
                          : "border-brand-cocoa/15 text-brand-cocoa/70"
                    }`}
                  >
                    {slot.label} &middot; {slot.starts_at}-{slot.ends_at}
                    {isFull ? " · Full" : ""}
                  </button>
                );
              })}
            </div>
          </div>
        )}

        <button
          type="button"
          disabled={!selectedDate || !slotSatisfied}
          onClick={() => router.push("/checkout")}
          className="mb-2 min-h-11 w-full rounded-full bg-brand-cocoa text-sm font-semibold text-white transition-transform active:scale-[0.98] disabled:opacity-40"
        >
          Checkout
        </button>

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
      </div>

      {isDatePickerOpen && (
        <DatePickerModal
          dates={preorderDates}
          minLeadTimeDays={getMaxLeadTimeDays(items)}
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
