"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { useToast } from "@/components/ui/ToastProvider";
import { ApiError } from "@/lib/api";
import {
  createAdminTimeSlot,
  deactivateAdminTimeSlot,
  getAdminPreorderDates,
  getAdminTimeSlots,
  updateAdminPreorderDate,
} from "@/lib/admin-api";
import type { AdminPreorderDate, AdminTimeSlot } from "@/lib/admin-types";

export default function AdminPreorderDateDetailPage() {
  const params = useParams<{ id: string }>();
  const dateId = Number(params.id);
  const toast = useToast();
  const confirm = useConfirm();

  const [date, setDate] = useState<AdminPreorderDate | null>(null);
  const [slots, setSlots] = useState<AdminTimeSlot[]>([]);
  const [isBusy, setIsBusy] = useState(false);

  const [label, setLabel] = useState("");
  const [startsAt, setStartsAt] = useState("");
  const [endsAt, setEndsAt] = useState("");
  const [slotMethod, setSlotMethod] = useState<"pickup" | "delivery" | "both">("both");
  const [slotCapacity, setSlotCapacity] = useState(5);
  const [slotError, setSlotError] = useState<string | null>(null);

  function reload() {
    return Promise.all([
      getAdminPreorderDates().then((dates) => setDate(dates.find((d) => d.id === dateId) ?? null)),
      getAdminTimeSlots(dateId).then(setSlots),
    ]);
  }

  useEffect(() => {
    reload();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dateId]);

  async function handleDateFieldChange(input: Parameters<typeof updateAdminPreorderDate>[1]) {
    if (!date) return;
    setIsBusy(true);
    try {
      const updated = await updateAdminPreorderDate(date.id, input);
      setDate(updated);
      toast.show("Date updated");
    } catch (error) {
      toast.show(error instanceof ApiError ? error.message : "Could not update this date.", "error");
    } finally {
      setIsBusy(false);
    }
  }

  async function handleCreateSlot(event: React.FormEvent) {
    event.preventDefault();
    setSlotError(null);
    setIsBusy(true);
    try {
      await createAdminTimeSlot(dateId, {
        label,
        starts_at: startsAt,
        ends_at: endsAt,
        fulfilment_method: slotMethod,
        capacity_limit: slotCapacity,
      });
      setLabel("");
      setStartsAt("");
      setEndsAt("");
      setSlotCapacity(5);
      await reload();
      toast.show("Time slot added");
    } catch (error) {
      setSlotError(error instanceof ApiError ? error.message : "Could not create this time slot.");
    } finally {
      setIsBusy(false);
    }
  }

  async function handleDeactivateSlot(slot: AdminTimeSlot) {
    const confirmed = await confirm(`Deactivate "${slot.label}"? It'll stop appearing for new orders.`);
    if (!confirmed) return;

    try {
      await deactivateAdminTimeSlot(dateId, slot.id);
      await reload();
      toast.show("Time slot deactivated");
    } catch (error) {
      toast.show(error instanceof ApiError ? error.message : "Could not deactivate this slot.", "error");
    }
  }

  if (!date) {
    return <p className="text-sm text-brand-cocoa/60">Loading...</p>;
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="rounded-xl bg-white p-4 shadow-sm">
        <h1 className="mb-3 font-display text-xl font-bold text-brand-cocoa">{date.order_date}</h1>
        <div className="flex flex-wrap items-end gap-3">
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Capacity</label>
            <input
              type="number"
              min={1}
              disabled={isBusy}
              defaultValue={date.capacity_limit}
              onBlur={(event) => {
                const value = Number(event.target.value);
                if (value !== date.capacity_limit) handleDateFieldChange({ capacity_limit: value });
              }}
              className="min-h-9 w-24 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Status</label>
            <select
              disabled={isBusy}
              value={date.status}
              onChange={(event) =>
                handleDateFieldChange({ status: event.target.value as AdminPreorderDate["status"] })
              }
              className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm capitalize"
            >
              <option value="open">Open</option>
              <option value="closed">Closed</option>
              <option value="full">Full</option>
            </select>
          </div>
          <label className="flex min-h-9 items-center gap-2 text-sm text-brand-cocoa">
            <input
              type="checkbox"
              checked={date.pickup_enabled}
              disabled={isBusy}
              onChange={(event) => handleDateFieldChange({ pickup_enabled: event.target.checked })}
              className="h-4 w-4 rounded border-brand-cocoa/30"
            />
            Pickup
          </label>
          <label className="flex min-h-9 items-center gap-2 text-sm text-brand-cocoa">
            <input
              type="checkbox"
              checked={date.delivery_enabled}
              disabled={isBusy}
              onChange={(event) => handleDateFieldChange({ delivery_enabled: event.target.checked })}
              className="h-4 w-4 rounded border-brand-cocoa/30"
            />
            Delivery
          </label>
        </div>
        <p className="mt-2 text-xs text-brand-cocoa/50">
          {date.reserved_capacity} of {date.capacity_limit} reserved
        </p>
      </div>

      <div className="rounded-xl bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-sm font-semibold text-brand-cocoa">Time slots</h2>

        <form onSubmit={handleCreateSlot} className="mb-4 flex flex-wrap items-end gap-2">
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Label</label>
            <input
              required
              value={label}
              onChange={(event) => setLabel(event.target.value)}
              className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Starts</label>
            <input
              required
              type="time"
              value={startsAt}
              onChange={(event) => setStartsAt(event.target.value)}
              className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Ends</label>
            <input
              required
              type="time"
              value={endsAt}
              onChange={(event) => setEndsAt(event.target.value)}
              className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Method</label>
            <select
              value={slotMethod}
              onChange={(event) => setSlotMethod(event.target.value as typeof slotMethod)}
              className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm capitalize"
            >
              <option value="both">Both</option>
              <option value="pickup">Pickup</option>
              <option value="delivery">Delivery</option>
            </select>
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs text-brand-cocoa/60">Capacity</label>
            <input
              required
              type="number"
              min={1}
              value={slotCapacity}
              onChange={(event) => setSlotCapacity(Number(event.target.value))}
              className="min-h-9 w-20 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
            />
          </div>
          <Button type="submit" size="sm" isLoading={isBusy}>
            Add slot
          </Button>
          {slotError && <p className="text-sm text-red-600">{slotError}</p>}
        </form>

        <div className="flex flex-col gap-2">
          {slots.map((slot) => (
            <div
              key={slot.id}
              className={`flex items-center justify-between rounded-lg border border-brand-cocoa/10 px-3 py-2 text-sm ${
                slot.is_active ? "" : "opacity-50"
              }`}
            >
              <span>
                {slot.label} &middot; {slot.starts_at}-{slot.ends_at} &middot;{" "}
                <span className="capitalize">{slot.fulfilment_method}</span> &middot;{" "}
                {slot.capacity_limit - slot.remaining_capacity}/{slot.capacity_limit} booked
                {!slot.is_active && " · Inactive"}
              </span>
              {slot.is_active && (
                <Button variant="danger" size="sm" onClick={() => handleDeactivateSlot(slot)}>
                  Deactivate
                </Button>
              )}
            </div>
          ))}
          {slots.length === 0 && (
            <p className="text-sm text-brand-cocoa/50">No time slots for this date yet.</p>
          )}
        </div>
      </div>
    </div>
  );
}
