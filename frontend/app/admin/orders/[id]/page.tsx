"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { useToast } from "@/components/ui/ToastProvider";
import { ApiError } from "@/lib/api";
import { getAdminOrder, reviewAdminPayment, updateAdminOrderStatus } from "@/lib/admin-api";
import { formatSen } from "@/lib/format";
import type { AdminOrder } from "@/lib/admin-types";

const STATUSES = [
  "whatsapp_pending", "awaiting_payment", "payment_submitted", "payment_confirmed",
  "preparing", "ready_for_pickup", "out_for_delivery", "completed", "cancelled", "rejected", "expired",
];

export default function AdminOrderDetailPage() {
  const params = useParams<{ id: string }>();
  const orderId = Number(params.id);
  const toast = useToast();
  const [order, setOrder] = useState<AdminOrder | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [isBusy, setIsBusy] = useState(false);

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

  async function handleReview(paymentId: number, action: "approve" | "reject") {
    setIsBusy(true);
    try {
      await reviewAdminPayment(paymentId, action);
      await reload();
      toast.show(action === "approve" ? "Payment approved" : "Payment rejected");
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
        </div>

        <p className="text-sm text-brand-cocoa/70">
          {order.customer_name} &middot; {order.customer_phone}
        </p>
        <p className="text-sm text-brand-cocoa/70">
          {order.fulfilment_method} &middot; {order.checkout_channel}
        </p>
        <p className="mt-2 font-semibold text-brand-cocoa">{formatSen(order.total_sen)}</p>
      </div>

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
              <a href={proof.url} target="_blank" rel="noreferrer">
                <img src={proof.url} alt="Payment proof" className="max-h-64 rounded-lg border border-brand-cocoa/10" />
              </a>
              {proof.reviewed_at ? (
                <p className="mt-1 text-xs text-brand-cocoa/50">Reviewed</p>
              ) : (
                <div className="mt-2 flex gap-2">
                  <Button
                    variant="success"
                    size="sm"
                    isLoading={isBusy}
                    onClick={() => handleReview(payment.id, "approve")}
                  >
                    Approve
                  </Button>
                  <Button variant="danger" size="sm" isLoading={isBusy} onClick={() => handleReview(payment.id, "reject")}>
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
