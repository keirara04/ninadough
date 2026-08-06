"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { getAdminOrders } from "@/lib/admin-api";
import { formatSen } from "@/lib/format";
import type { AdminOrder } from "@/lib/admin-types";

export default function AdminOrdersPage() {
  const searchParams = useSearchParams();
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [statusFilter, setStatusFilter] = useState(searchParams.get("status") ?? "");
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    Promise.resolve()
      .then(() => setIsLoading(true))
      .then(() => getAdminOrders(statusFilter || undefined))
      .then(setOrders)
      .finally(() => setIsLoading(false));
  }, [statusFilter]);

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="font-display text-xl font-bold text-brand-cocoa">Orders</h1>
        <select
          value={statusFilter}
          onChange={(event) => setStatusFilter(event.target.value)}
          className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
        >
          <option value="">All statuses</option>
          <option value="awaiting_payment">Awaiting payment</option>
          <option value="payment_submitted">Payment submitted</option>
          <option value="payment_confirmed">Payment confirmed</option>
          <option value="preparing">Preparing</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      {isLoading ? (
        <p className="text-sm text-brand-cocoa/60">Loading...</p>
      ) : orders.length === 0 ? (
        <p className="text-sm text-brand-cocoa/60">No orders found.</p>
      ) : (
        <div className="overflow-x-auto rounded-xl bg-white shadow-sm">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-brand-cocoa/10 text-xs uppercase text-brand-cocoa/50">
              <tr>
                <th className="px-4 py-2">Order</th>
                <th className="px-4 py-2">Customer</th>
                <th className="px-4 py-2">Status</th>
                <th className="px-4 py-2">Payment</th>
                <th className="px-4 py-2">Total</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((order) => (
                <tr key={order.id} className="border-b border-brand-cocoa/5 last:border-0">
                  <td className="px-4 py-2">
                    <Link href={`/admin/orders/${order.id}`} className="font-medium text-brand-pink">
                      {order.order_number}
                    </Link>
                  </td>
                  <td className="px-4 py-2">{order.customer_name}</td>
                  <td className="px-4 py-2 capitalize">
                    {order.status.replace(/_/g, " ")}
                    {order.refund_required && (
                      <span className="ml-1.5 rounded-full bg-brand-gold/20 px-2 py-0.5 text-xs font-semibold normal-case text-brand-cocoa">
                        Refund needed
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-2 capitalize">{order.payment_status}</td>
                  <td className="px-4 py-2">{formatSen(order.total_sen)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
