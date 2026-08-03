"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { ApiError, getDeliveryZones, postCheckoutQuote, postOrder } from "@/lib/api";
import { getSubtotalSen, useCartStore } from "@/lib/cart-store";
import { formatSen } from "@/lib/format";
import type { CartQuote, DeliveryZone } from "@/lib/types";

export default function CheckoutPage() {
  const router = useRouter();
  const items = useCartStore((state) => state.items);
  const preorderDate = useCartStore((state) => state.preorderDate);
  const fulfilmentMethod = useCartStore((state) => state.fulfilmentMethod);
  const idempotencyKey = useCartStore((state) => state.idempotencyKey);
  const clearCart = useCartStore((state) => state.clear);

  const [deliveryZones, setDeliveryZones] = useState<DeliveryZone[]>([]);
  const [deliveryZoneId, setDeliveryZoneId] = useState<number | null>(null);
  const [quote, setQuote] = useState<CartQuote | null>(null);
  const [quoteError, setQuoteError] = useState<string | null>(null);

  const [customerName, setCustomerName] = useState("");
  const [customerPhone, setCustomerPhone] = useState("");
  const [customerEmail, setCustomerEmail] = useState("");
  const [recipientName, setRecipientName] = useState("");
  const [recipientPhone, setRecipientPhone] = useState("");
  const [addressLine1, setAddressLine1] = useState("");
  const [addressLine2, setAddressLine2] = useState("");
  const [city, setCity] = useState("");
  const [stateName, setStateName] = useState("");
  const [postcode, setPostcode] = useState("");

  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (items.length === 0 || !preorderDate) {
      router.replace("/");
    }
  }, [items.length, preorderDate, router]);

  useEffect(() => {
    if (fulfilmentMethod === "delivery") {
      getDeliveryZones()
        .then((zones) => {
          setDeliveryZones(zones);
          setDeliveryZoneId((current) => current ?? zones[0]?.id ?? null);
        })
        .catch(() => setDeliveryZones([]));
    }
  }, [fulfilmentMethod]);

  useEffect(() => {
    if (!preorderDate || items.length === 0) {
      return;
    }

    if (fulfilmentMethod === "delivery" && !deliveryZoneId) {
      return;
    }

    let cancelled = false;
    setQuoteError(null);

    postCheckoutQuote({
      items: items.map((line) => ({ product_variant_id: line.variantId, quantity: line.quantity })),
      preorder_date: preorderDate,
      fulfilment_method: fulfilmentMethod,
      delivery_zone_id: fulfilmentMethod === "delivery" ? deliveryZoneId : null,
    })
      .then((result) => {
        if (!cancelled) {
          setQuote(result);
        }
      })
      .catch((error: unknown) => {
        if (!cancelled) {
          setQuote(null);
          setQuoteError(error instanceof ApiError ? error.message : "Could not price your order.");
        }
      });

    return () => {
      cancelled = true;
    };
  }, [items, preorderDate, fulfilmentMethod, deliveryZoneId]);

  if (items.length === 0 || !preorderDate) {
    return null;
  }

  const subtotalSen = getSubtotalSen(items);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      const order = await postOrder({
        items: items.map((line) => ({ product_variant_id: line.variantId, quantity: line.quantity })),
        preorder_date: preorderDate!,
        fulfilment_method: fulfilmentMethod,
        delivery_zone_id: fulfilmentMethod === "delivery" ? deliveryZoneId : null,
        delivery_address:
          fulfilmentMethod === "delivery"
            ? {
                recipient_name: recipientName,
                recipient_phone_e164: recipientPhone,
                line_1: addressLine1,
                line_2: addressLine2 || undefined,
                city,
                state: stateName,
                postcode,
              }
            : null,
        checkout_channel: "website",
        idempotency_key: idempotencyKey,
        customer: {
          name: customerName,
          phone_e164: customerPhone,
          email: customerEmail || null,
        },
      });

      clearCart();
      const statusUrl = new URL(order.status_url);
      const signature = statusUrl.searchParams.get("signature") ?? "";
      const expires = statusUrl.searchParams.get("expires") ?? "";
      const proofUrl = new URL(order.payment_proof_url);
      const proofSignature = proofUrl.searchParams.get("signature") ?? "";
      router.push(
        `/orders/${order.order_number}?signature=${signature}&expires=${expires}&proof_signature=${proofSignature}`,
      );
    } catch (error) {
      if (error instanceof ApiError) {
        setSubmitError(error.message);
        setFieldErrors(error.errors ?? {});
      } else {
        setSubmitError("Something went wrong. Please try again.");
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  function fieldError(name: string): string | null {
    return fieldErrors[name]?.[0] ?? null;
  }

  return (
    <div className="mx-auto w-full max-w-2xl px-4 py-6 sm:px-6 lg:px-8">
      <h1 className="mb-6 font-display text-2xl font-bold text-brand-cocoa">
        Checkout
      </h1>

      <form onSubmit={handleSubmit} className="flex flex-col gap-6">
        <section className="rounded-2xl bg-white p-4 shadow-sm">
          <h2 className="mb-3 font-semibold text-brand-cocoa">Your details</h2>
          <div className="flex flex-col gap-3">
            <Field label="Full name" error={fieldError("customer.name")}>
              <input
                required
                value={customerName}
                onChange={(event) => setCustomerName(event.target.value)}
                className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
              />
            </Field>
            <Field label="Phone (e.g. +60123456789)" error={fieldError("customer.phone_e164")}>
              <input
                required
                value={customerPhone}
                onChange={(event) => setCustomerPhone(event.target.value)}
                className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
              />
            </Field>
            <Field label="Email (optional)" error={fieldError("customer.email")}>
              <input
                type="email"
                value={customerEmail}
                onChange={(event) => setCustomerEmail(event.target.value)}
                className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
              />
            </Field>
          </div>
        </section>

        {fulfilmentMethod === "delivery" && (
          <section className="rounded-2xl bg-white p-4 shadow-sm">
            <h2 className="mb-3 font-semibold text-brand-cocoa">Delivery address</h2>
            <div className="flex flex-col gap-3">
              <Field label="Delivery zone" error={fieldError("delivery_zone_id")}>
                <select
                  required
                  value={deliveryZoneId ?? ""}
                  onChange={(event) => setDeliveryZoneId(Number(event.target.value))}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                >
                  <option value="" disabled>
                    Select a zone
                  </option>
                  {deliveryZones.map((zone) => (
                    <option key={zone.id} value={zone.id}>
                      {zone.name} &middot; {formatSen(zone.delivery_fee_sen)}
                    </option>
                  ))}
                </select>
              </Field>
              <Field label="Recipient name" error={fieldError("delivery_address.recipient_name")}>
                <input
                  required
                  value={recipientName}
                  onChange={(event) => setRecipientName(event.target.value)}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                />
              </Field>
              <Field label="Recipient phone" error={fieldError("delivery_address.recipient_phone_e164")}>
                <input
                  required
                  value={recipientPhone}
                  onChange={(event) => setRecipientPhone(event.target.value)}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                />
              </Field>
              <Field label="Address line 1" error={fieldError("delivery_address.line_1")}>
                <input
                  required
                  value={addressLine1}
                  onChange={(event) => setAddressLine1(event.target.value)}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                />
              </Field>
              <Field label="Address line 2 (optional)" error={fieldError("delivery_address.line_2")}>
                <input
                  value={addressLine2}
                  onChange={(event) => setAddressLine2(event.target.value)}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                />
              </Field>
              <div className="grid grid-cols-2 gap-3">
                <Field label="City" error={fieldError("delivery_address.city")}>
                  <input
                    required
                    value={city}
                    onChange={(event) => setCity(event.target.value)}
                    className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                  />
                </Field>
                <Field label="State" error={fieldError("delivery_address.state")}>
                  <input
                    required
                    value={stateName}
                    onChange={(event) => setStateName(event.target.value)}
                    className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                  />
                </Field>
              </div>
              <Field label="Postcode" error={fieldError("delivery_address.postcode")}>
                <input
                  required
                  value={postcode}
                  onChange={(event) => setPostcode(event.target.value)}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                />
              </Field>
            </div>
          </section>
        )}

        <section className="rounded-2xl bg-white p-4 shadow-sm">
          <h2 className="mb-3 font-semibold text-brand-cocoa">Order summary</h2>
          <ul className="mb-3 flex flex-col gap-1 text-sm text-brand-cocoa">
            {items.map((line) => (
              <li key={line.variantId} className="flex justify-between">
                <span>
                  {line.productName} &middot; {line.variantName} &times; {line.quantity}
                </span>
                <span>{formatSen(line.unitPriceSen * line.quantity)}</span>
              </li>
            ))}
          </ul>

          {quoteError && <p className="mb-2 text-sm text-red-600">{quoteError}</p>}

          <div className="flex flex-col gap-1 border-t border-brand-cocoa/10 pt-2 text-sm">
            <div className="flex justify-between">
              <span>Subtotal</span>
              <span>{formatSen(quote?.subtotal_sen ?? subtotalSen)}</span>
            </div>
            {fulfilmentMethod === "delivery" && (
              <div className="flex justify-between">
                <span>Delivery fee</span>
                <span>{formatSen(quote?.delivery_fee_sen ?? 0)}</span>
              </div>
            )}
            <div className="flex justify-between font-semibold text-brand-cocoa">
              <span>Total</span>
              <span>{formatSen(quote?.total_sen ?? subtotalSen)}</span>
            </div>
          </div>
        </section>

        {submitError && <p className="text-sm text-red-600">{submitError}</p>}

        <button
          type="submit"
          disabled={isSubmitting}
          className="min-h-11 rounded-full bg-brand-pink text-sm font-semibold text-white disabled:opacity-60"
        >
          {isSubmitting ? "Placing order..." : "Place order"}
        </button>
      </form>
    </div>
  );
}

function Field({
  label,
  error,
  children,
}: {
  label: string;
  error: string | null;
  children: React.ReactNode;
}) {
  return (
    <label className="flex flex-col gap-1 text-sm text-brand-cocoa">
      <span className="text-xs font-medium text-brand-cocoa/70">{label}</span>
      {children}
      {error && <span className="text-xs text-red-600">{error}</span>}
    </label>
  );
}
