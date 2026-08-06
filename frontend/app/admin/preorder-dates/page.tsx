"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { ApiError } from "@/lib/api";
import { createAdminPreorderDate, getAdminPreorderDates } from "@/lib/admin-api";
import type { AdminPreorderDate } from "@/lib/admin-types";

export default function AdminPreorderDatesPage() {
  const [dates, setDates] = useState<AdminPreorderDate[]>([]);
  const [orderDate, setOrderDate] = useState("");
  const [cutoffAt, setCutoffAt] = useState("");
  const [capacityLimit, setCapacityLimit] = useState(10);
  const [error, setError] = useState<string | null>(null);
  const [isCreating, setIsCreating] = useState(false);

  function reload() {
    return getAdminPreorderDates().then(setDates);
  }

  useEffect(() => {
    reload();
  }, []);

  async function handleCreate(event: React.FormEvent) {
    event.preventDefault();
    setError(null);
    setIsCreating(true);
    try {
      await createAdminPreorderDate({
        order_date: orderDate,
        cutoff_at: new Date(cutoffAt).toISOString(),
        capacity_limit: capacityLimit,
      });
      setOrderDate("");
      setCutoffAt("");
      setCapacityLimit(10);
      await reload();
    } catch (createError) {
      setError(createError instanceof ApiError ? createError.message : "Could not create this date.");
    } finally {
      setIsCreating(false);
    }
  }

  return (
    <div>
      <h1 className="mb-4 font-display text-xl font-bold text-brand-cocoa">Preorder dates</h1>

      <form onSubmit={handleCreate} className="mb-6 flex flex-wrap items-end gap-2 rounded-xl bg-white p-4 shadow-sm">
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Order date</label>
          <input
            required
            type="date"
            value={orderDate}
            onChange={(event) => setOrderDate(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Cutoff</label>
          <input
            required
            type="datetime-local"
            value={cutoffAt}
            onChange={(event) => setCutoffAt(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Capacity</label>
          <input
            required
            type="number"
            min={1}
            value={capacityLimit}
            onChange={(event) => setCapacityLimit(Number(event.target.value))}
            className="min-h-9 w-24 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
          />
        </div>
        <Button type="submit" size="sm" isLoading={isCreating}>
          Add date
        </Button>
        {error && <p className="text-sm text-red-600">{error}</p>}
      </form>

      <div className="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-brand-cocoa/10 text-xs uppercase text-brand-cocoa/50">
            <tr>
              <th className="px-4 py-2">Date</th>
              <th className="px-4 py-2">Status</th>
              <th className="px-4 py-2">Capacity</th>
              <th className="px-4 py-2">Pickup / Delivery</th>
              <th className="px-4 py-2" />
            </tr>
          </thead>
          <tbody>
            {dates.map((date) => (
              <tr key={date.id} className="border-b border-brand-cocoa/5 last:border-0">
                <td className="px-4 py-2 font-medium">{date.order_date}</td>
                <td className="px-4 py-2 capitalize">{date.status}</td>
                <td className="px-4 py-2">
                  {date.reserved_capacity} / {date.capacity_limit}
                </td>
                <td className="px-4 py-2">
                  {date.pickup_enabled ? "Pickup" : ""}
                  {date.pickup_enabled && date.delivery_enabled ? " · " : ""}
                  {date.delivery_enabled ? "Delivery" : ""}
                </td>
                <td className="px-4 py-2">
                  <Link href={`/admin/preorder-dates/${date.id}`} className="font-medium text-brand-pink">
                    Manage
                  </Link>
                </td>
              </tr>
            ))}
            {dates.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-sm text-brand-cocoa/50">
                  No preorder dates yet.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
