"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { AuthenticatedImage } from "@/components/AuthenticatedImage";
import { Button } from "@/components/ui/Button";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { useToast } from "@/components/ui/ToastProvider";
import { ApiError } from "@/lib/api";
import { getAdminOrder, reviewAdminPayment, updateAdminOrderStatus } from "@/lib/admin-api";
import { isOwner, useAdminStore } from "@/lib/admin-store";
import { formatSen } from "@/lib/format";
import type { AdminOrder } from "@/lib/admin-types";

const STATUSES = [
  "whatsapp_pending", "awaiting_payment", "payment_submitted", "payment_confirmed",
  "preparing", "ready_for_pickup", "out_for_delivery", "completed", "cancelled", "rejected", "expired",
];

const POST_PAYMENT_STATUSES = ["payment_confirmed", "preparing", "ready_for_pickup", "out_for_delivery"];

export default function AdminOrderDetailPage() {
  const params = useParams<{ id: string }>();
  const orderId = Number(params.id);
  const toast = useToast();
  const confirm = useConfirm();
  const user = useAdminStore((state) => state.user);
  const owner = isOwner(user);
  const [order, setOrder] = useState<AdminOrder | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [isBusy, setIsBusy] = useState(false);
  const [rejectingPaymentId, setRejectingPaymentId] = useState<number | null>(null);
  const [rejectReason, setRejectReason] = useState("");

  function reload() {
    return getAdminOrder(orderId)
      .then((result) => {
        setOrder(result);
        setLoadError(null);
      })
      .catch((error: unknown) => {
        setLoadError(error instanceof ApiError ? error.message : "Could not load this order.");
      });
  }

  useEffect(() => {
    reload();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [orderId]);

  if (loadError) {
    return <p className="text-sm text-red-600">{loadError}</p>;
  }

  if (!order) {
    return <p className="text-sm text-brand-cocoa/60">Loading...</p>;
  }

  async function handleStatusChange(status: string) {
    if (status === "cancelled" && order && POST_PAYMENT_STATUSES.includes(order.status)) {
      const confirmed = await confirm(
        "This order was already paid. Cancelling now won't release inventory — it flags the order as needing a manual refund. Continue?",
      );
      if (!confirmed) return;
    }

    setIsBusy(true);
    try {
      await updateAdminOrderStatus(orderId, status);
      await reload();
      toast.show("Order status updated");
    } catch (error) {
      toast.show(error instanceof ApiError ? error.message : "Could not update status.", "error");
    } finally {
      setIsBusy(false);
    }
  }

  async function handleApprove(paymentId: number) {
    setIsBusy(true);
    try {
      await reviewAdminPayment(paymentId, "approve");
      await reload();
      toast.show("Payment approved");
    } catch (error) {
      toast.show(error instanceof ApiError ? error.message : "Could not review payment.", "error");
    } finally {
      setIsBusy(false);
    }
  }

  async function handleConfirmReject(paymentId: number) {
    const reason = rejectReason.trim();
    if (!reason) return;

    setIsBusy(true);
    try {
      await reviewAdminPayment(paymentId, "reject", { rejectionMessage: reason });
      await reload();
      setRejectingPaymentId(null);
      setRejectReason("");
      toast.show("Payment rejected");
    } catch (error) {
      toast.show(error instanceof ApiError ? error.message : "Could not review payment.", "error");
    } finally {
      setIsBusy(false);
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="rounded-xl bg-white p-4 shadow-sm">
        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h1 className="font-display text-xl font-bold text-brand-cocoa">{order.order_number}</h1>
          {owner ? (
            <select
              value={order.status}
              disabled={isBusy}
              onChange={(event) => handleStatusChange(event.target.value)}
              className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm capitalize focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2"
            >
              {STATUSES.map((status) => (
                <option key={status} value={status}>
                  {status.replace(/_/g, " ")}
                </option>
              ))}
            </select>
          ) : (
            <span className="rounded-lg bg-brand-cream px-2 py-1 text-sm capitalize text-brand-cocoa/70">
              {order.status.replace(/_/g, " ")}
            </span>
          )}
        </div>

        <p className="text-sm text-brand-cocoa/70">
          {order.customer_name} &middot; {order.customer_phone}
        </p>
        <p className="text-sm text-brand-cocoa/70">
          {order.fulfilment_method} &middot; {order.checkout_channel}
          {order.time_slot && ` · ${order.time_slot.label} (${order.time_slot.starts_at}-${order.time_slot.ends_at})`}
        </p>
        <p className="mt-2 font-semibold text-brand-cocoa">{formatSen(order.total_sen)}</p>

        {order.refund_required && (
          <p className="mt-2 inline-block rounded-full bg-brand-gold/20 px-3 py-1 text-xs font-semibold text-brand-cocoa">
            Refund required{order.refund_note ? ` — ${order.refund_note}` : ""}
          </p>
        )}
        {order.rejection_message && (
          <p className="mt-2 text-xs text-red-600">Rejected: {order.rejection_message}</p>
        )}
      </div>

      {(order.notes || order.card_message || order.allergies_note || order.hide_price_on_package) && (
        <div className="rounded-xl bg-white p-4 shadow-sm">
          <h2 className="mb-2 text-sm font-semibold text-brand-cocoa">Extra details</h2>
          <div className="flex flex-col gap-2 text-sm text-brand-cocoa/80">
            {order.notes && (
              <p>
                <span className="font-medium text-brand-cocoa">Notes:</span> {order.notes}
              </p>
            )}
            {order.card_message && (
              <p>
                <span className="font-medium text-brand-cocoa">Card message:</span> {order.card_message}
              </p>
            )}
            {order.allergies_note && (
              <p>
                <span className="font-medium text-brand-cocoa">Allergies:</span> {order.allergies_note}
              </p>
            )}
            {order.hide_price_on_package && (
              <p className="font-medium text-brand-cocoa">Gift — hide price on package</p>
            )}
          </div>
        </div>
      )}

      <div className="rounded-xl bg-white p-4 shadow-sm">
        <h2 className="mb-2 text-sm font-semibold text-brand-cocoa">Items</h2>
        <ul className="flex flex-col gap-1 text-sm">
          {order.items?.map((item, index) => (
            <li key={index} className="flex justify-between">
              <span>
                {item.product_name} {item.variant_name ? `· ${item.variant_name}` : ""} &times; {item.quantity}
              </span>
              <span>{formatSen(item.line_total_sen)}</span>
            </li>
          ))}
        </ul>
      </div>

      {order.payments?.map((payment) => (
        <div key={payment.id} className="rounded-xl bg-white p-4 shadow-sm">
          <div className="mb-2 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-brand-cocoa capitalize">
              Payment &middot; {payment.status}
            </h2>
            <span className="text-sm text-brand-cocoa/70">{formatSen(payment.amount_sen)}</span>
          </div>

          {payment.proofs.map((proof) => (
            <div key={proof.id} className="mb-2">
              <AuthenticatedImage
                src={proof.url}
                alt="Payment proof"
                className="max-h-64 rounded-lg border border-brand-cocoa/10"
              />
              {proof.reviewed_at ? (
                <p className="mt-1 text-xs text-brand-cocoa/50">Reviewed</p>
              ) : rejectingPaymentId === payment.id ? (
                <div className="mt-2 flex flex-col gap-2">
                  <label className="flex flex-col gap-1 text-sm text-brand-cocoa">
                    <span className="text-xs font-medium text-brand-cocoa/70">
                      Reason for rejecting (shown to the customer)
                    </span>
                    <textarea
                      autoFocus
                      required
                      value={rejectReason}
                      onChange={(event) => setRejectReason(event.target.value)}
                      maxLength={500}
                      rows={2}
                      className="w-full rounded-xl border border-brand-cocoa/15 px-3 py-2 text-sm"
                    />
                  </label>
                  <div className="flex gap-2">
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={() => {
                        setRejectingPaymentId(null);
                        setRejectReason("");
                      }}
                    >
                      Cancel
                    </Button>
                    <Button
                      variant="danger"
                      size="sm"
                      isLoading={isBusy}
                      disabled={!rejectReason.trim()}
                      onClick={() => handleConfirmReject(payment.id)}
                    >
                      Confirm reject
                    </Button>
                  </div>
                </div>
              ) : (
                <div className="mt-2 flex gap-2">
                  <Button variant="success" size="sm" isLoading={isBusy} onClick={() => handleApprove(payment.id)}>
                    Approve
                  </Button>
                  <Button
                    variant="danger"
                    size="sm"
                    isLoading={isBusy}
                    onClick={() => {
                      setRejectingPaymentId(payment.id);
                      setRejectReason("");
                    }}
                  >
                    Reject
                  </Button>
                </div>
              )}
            </div>
          ))}

          {payment.proofs.length === 0 && (
            <p className="text-sm text-brand-cocoa/50">No proof uploaded yet.</p>
          )}
        </div>
      ))}
    </div>
  );
}
