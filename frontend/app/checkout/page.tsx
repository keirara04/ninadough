"use client";

import { useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { Button } from "@/components/ui/Button";
import { ApiError, postCheckoutQuote, postOrder } from "@/lib/api";
import { getSubtotalSen, useCartStore } from "@/lib/cart-store";
import { formatSen } from "@/lib/format";
import type { CartQuote } from "@/lib/types";

export default function CheckoutPage() {
  const router = useRouter();
  const items = useCartStore((state) => state.items);
  const preorderDate = useCartStore((state) => state.preorderDate);
  const fulfilmentMethod = useCartStore((state) => state.fulfilmentMethod);
  const timeSlotId = useCartStore((state) => state.timeSlotId);
  const cartPostcode = useCartStore((state) => state.postcode);
  const idempotencyKey = useCartStore((state) => state.idempotencyKey);
  const clearCart = useCartStore((state) => state.clear);

  const [quote, setQuote] = useState<CartQuote | null>(null);
  const [quoteError, setQuoteError] = useState<string | null>(null);
  const [postcodeError, setPostcodeError] = useState<string | null>(null);

  const [customerName, setCustomerName] = useState("");
  const [customerPhone, setCustomerPhone] = useState("");
  const [customerEmail, setCustomerEmail] = useState("");
  const [recipientName, setRecipientName] = useState("");
  const [recipientPhone, setRecipientPhone] = useState("");
  const [addressLine1, setAddressLine1] = useState("");
  const [addressLine2, setAddressLine2] = useState("");
  const [city, setCity] = useState("");
  const [stateName, setStateName] = useState("");
  const [postcode, setPostcode] = useState(cartPostcode ?? "");
  const [notes, setNotes] = useState("");
  const [cardMessage, setCardMessage] = useState("");
  const [allergiesNote, setAllergiesNote] = useState("");
  const [hidePriceOnPackage, setHidePriceOnPackage] = useState(false);

  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const hasSubmittedRef = useRef(false);

  useEffect(() => {
    // Skip once an order has been placed — clearing the cart on success
    // empties `items`/`preorderDate` while this page is still mid-navigation
    // to /orders/..., and this guard would otherwise redirect to "/" first.
    if (hasSubmittedRef.current) return;
    if (items.length === 0 || !preorderDate) {
      router.replace("/");
    }
  }, [items.length, preorderDate, router]);

  useEffect(() => {
    if (!preorderDate || items.length === 0) {
      return;
    }

    if (fulfilmentMethod === "delivery" && postcode.trim().length < 4) {
      Promise.resolve().then(() => setQuote(null));
      return;
    }

    let cancelled = false;

    const timeout = window.setTimeout(() => {
      Promise.resolve()
        .then(() => {
          setQuoteError(null);
          setPostcodeError(null);
        })
        .then(() =>
          postCheckoutQuote({
            items: items.map((line) => ({ product_variant_id: line.variantId, quantity: line.quantity })),
            preorder_date: preorderDate,
            fulfilment_method: fulfilmentMethod,
            postcode: fulfilmentMethod === "delivery" ? postcode.trim() : null,
          }),
        )
        .then((result) => {
          if (!cancelled) {
            setQuote(result);
          }
        })
        .catch((error: unknown) => {
          if (cancelled) return;
          setQuote(null);
          if (error instanceof ApiError && error.errors?.postcode) {
            setPostcodeError(error.errors.postcode[0]);
          } else {
            setQuoteError(error instanceof ApiError ? error.message : "Could not price your order.");
          }
        });
    }, 400);

    return () => {
      cancelled = true;
      window.clearTimeout(timeout);
    };
  }, [items, preorderDate, fulfilmentMethod, postcode]);

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
        time_slot_id: timeSlotId,
        payment_method: "bank_transfer",
        notes: notes || null,
        card_message: cardMessage || null,
        allergies_note: allergiesNote || null,
        hide_price_on_package: hidePriceOnPackage,
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

      hasSubmittedRef.current = true;
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
              <Field
                label="Postcode"
                error={postcodeError ?? fieldError("delivery_address.postcode")}
              >
                <input
                  required
                  value={postcode}
                  onChange={(event) => setPostcode(event.target.value)}
                  className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
                />
              </Field>
              {quote && (
                <p className="text-xs text-brand-cocoa/60">
                  Delivery fee: {formatSen(quote.delivery_fee_sen)}
                </p>
              )}
            </div>
          </section>
        )}

        <section className="rounded-2xl bg-white p-4 shadow-sm">
          <h2 className="mb-3 font-semibold text-brand-cocoa">Extra details (optional)</h2>
          <div className="flex flex-col gap-3">
            <Field label="Order notes" error={fieldError("notes")}>
              <textarea
                value={notes}
                onChange={(event) => setNotes(event.target.value)}
                maxLength={1000}
                rows={2}
                className="w-full rounded-xl border border-brand-cocoa/15 px-3 py-2 text-sm"
              />
            </Field>
            <Field label="Message on card" error={fieldError("card_message")}>
              <input
                value={cardMessage}
                onChange={(event) => setCardMessage(event.target.value)}
                maxLength={200}
                className="min-h-11 w-full rounded-xl border border-brand-cocoa/15 px-3 text-sm"
              />
            </Field>
            <Field label="Allergies" error={fieldError("allergies_note")}>
              <textarea
                value={allergiesNote}
                onChange={(event) => setAllergiesNote(event.target.value)}
                maxLength={500}
                rows={2}
                className="w-full rounded-xl border border-brand-cocoa/15 px-3 py-2 text-sm"
              />
              <p className="mt-1 text-xs text-brand-cocoa/50">
                Our kitchen handles nuts, dairy, egg, and gluten — we take care with allergy notes but
                can&apos;t guarantee an allergen-free product.
              </p>
            </Field>
            <label className="flex min-h-11 items-center gap-2 text-sm text-brand-cocoa">
              <input
                type="checkbox"
                checked={hidePriceOnPackage}
                onChange={(event) => setHidePriceOnPackage(event.target.checked)}
                className="h-5 w-5 rounded border-brand-cocoa/30"
              />
              This is a gift — don&apos;t include the price on the package
            </label>
          </div>
        </section>

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

        <Button type="submit" isLoading={isSubmitting} className="w-full">
          {isSubmitting ? "Placing order..." : "Place order"}
        </Button>
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
