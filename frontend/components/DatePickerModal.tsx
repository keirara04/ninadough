"use client";

import type { PreorderDate } from "@/lib/types";

function formatDate(dateString: string): string {
  return new Date(`${dateString}T00:00:00`).toLocaleDateString("en-MY", {
    weekday: "short",
    day: "numeric",
    month: "short",
  });
}

function formatCutoff(cutoffAt: string): string {
  return new Date(cutoffAt).toLocaleString("en-MY", {
    day: "numeric",
    month: "short",
    hour: "numeric",
    minute: "2-digit",
  });
}

export function DatePickerModal({
  dates,
  onSelect,
  onClose,
}: {
  dates: PreorderDate[];
  onSelect: (date: PreorderDate) => void;
  onClose: () => void;
}) {
  return (
    <div className="animate-fade-in fixed inset-0 z-30 flex items-end bg-black/40 sm:items-center sm:justify-center">
      <div className="animate-slide-up max-h-[80vh] w-full overflow-y-auto rounded-t-2xl bg-white p-4 sm:max-w-sm sm:rounded-2xl">

        <div className="mb-3 flex items-center justify-between">
          <h2 className="font-display text-lg font-semibold text-brand-cocoa">
            Choose order date
          </h2>
          <button
            type="button"
            aria-label="Close"
            onClick={onClose}
            className="flex h-9 w-9 items-center justify-center rounded-full text-brand-cocoa/60"
          >
            &times;
          </button>
        </div>

        {dates.length === 0 && (
          <p className="py-6 text-center text-sm text-brand-cocoa/60">
            No upcoming preorder dates yet — check back soon.
          </p>
        )}

        <ul className="flex flex-col gap-2">
          {dates.map((date) => {
            const isOrderable = date.status === "open";

            return (
              <li key={date.order_date}>
                <button
                  type="button"
                  disabled={!isOrderable}
                  onClick={() => onSelect(date)}
                  className={`flex w-full min-h-11 items-center justify-between rounded-xl border px-3 py-2 text-left transition active:scale-[0.98] ${
                    isOrderable
                      ? "border-brand-cocoa/15 text-brand-cocoa hover:border-brand-pink/40 hover:bg-brand-cream/50"
                      : "border-brand-cocoa/10 text-brand-cocoa/40"
                  }`}
                >
                  <span>
                    <span className="block text-sm font-medium">{formatDate(date.order_date)}</span>
                    {isOrderable && (
                      <span className="block text-xs text-brand-cocoa/60">
                        Order by {formatCutoff(date.cutoff_at)}
                      </span>
                    )}
                  </span>
                  <span className="text-xs font-medium uppercase tracking-wide">
                    {date.status === "open" ? `${date.remaining_capacity} left` : date.status}
                  </span>
                </button>
              </li>
            );
          })}
        </ul>
      </div>
    </div>
  );
}
