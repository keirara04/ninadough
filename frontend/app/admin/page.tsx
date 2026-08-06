"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { getAdminDashboard } from "@/lib/admin-api";
import { formatSen } from "@/lib/format";
import type { AdminDashboard } from "@/lib/admin-types";

// Bucket per the design plan's proofing-bar signature: booked/total, not
// remaining/total — an inverted bucket is the easiest bug to reintroduce here.
function capacityBucket(used: number, limit: number): { label: string; colorClass: string } {
  if (limit <= 0) return { label: "No capacity set", colorClass: "bg-brand-cocoa/20" };
  const remaining = limit - used;
  const bookedRatio = used / limit;

  if (remaining <= 0) return { label: "Fully booked", colorClass: "bg-brand-cocoa/60" };
  if (bookedRatio >= 0.9 || remaining === 1) return { label: "Last batch", colorClass: "bg-brand-pink" };
  if (bookedRatio >= 0.6) return { label: "Proofing", colorClass: "bg-brand-gold" };
  return { label: "Rising", colorClass: "bg-brand-gold/60" };
}

export default function AdminDashboardPage() {
  const [dashboard, setDashboard] = useState<AdminDashboard | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    Promise.resolve()
      .then(() => setIsLoading(true))
      .then(() => getAdminDashboard())
      .then(setDashboard)
      .finally(() => setIsLoading(false));
  }, []);

  if (isLoading) {
    return <p className="text-sm text-brand-cocoa/60">Loading...</p>;
  }

  if (!dashboard) {
    return <p className="text-sm text-red-600">Could not load the dashboard.</p>;
  }

  return (
    <div className="flex flex-col gap-6">
      <h1 className="font-display text-xl font-bold text-brand-cocoa">Dashboard</h1>

      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <StatTile label="Today's orders" value={String(dashboard.todays_order_count)} />
        <StatTile
          label="Pending review"
          value={String(dashboard.pending_payment_review_count)}
          href="/admin/orders?status=payment_submitted"
          highlight={dashboard.pending_payment_review_count > 0}
        />
        <StatTile
          label="Needs attention"
          value={String(dashboard.orders_needing_attention)}
          highlight={dashboard.orders_needing_attention > 0}
        />
        <StatTile label="Revenue today" value={formatSen(dashboard.revenue_sen.today)} />
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <StatTile label="Revenue this week" value={formatSen(dashboard.revenue_sen.this_week)} />
        <StatTile label="Revenue all time" value={formatSen(dashboard.revenue_sen.all_time)} />
      </div>

      <div className="rounded-xl bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-sm font-semibold text-brand-cocoa">Upcoming preorder dates</h2>
        {dashboard.upcoming_preorder_dates.length === 0 ? (
          <p className="text-sm text-brand-cocoa/50">No upcoming dates configured.</p>
        ) : (
          <div className="flex flex-col gap-3">
            {dashboard.upcoming_preorder_dates.map((date) => {
              const bucket = capacityBucket(date.capacity_used, date.capacity_limit);
              const fillPercent = date.capacity_limit > 0
                ? Math.min(100, Math.round((date.capacity_used / date.capacity_limit) * 100))
                : 0;

              return (
                <div key={date.order_date}>
                  <div className="mb-1 flex items-center justify-between text-sm">
                    <span className="font-medium text-brand-cocoa">{date.order_date}</span>
                    <span className="text-xs text-brand-cocoa/60">
                      {bucket.label} · {date.capacity_used} of {date.capacity_limit} slots
                    </span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-brand-cream">
                    <div className={`h-full rounded-full ${bucket.colorClass}`} style={{ width: `${fillPercent}%` }} />
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

function StatTile({
  label,
  value,
  href,
  highlight,
}: {
  label: string;
  value: string;
  href?: string;
  highlight?: boolean;
}) {
  const content = (
    <div
      className={`rounded-xl p-4 shadow-sm ${
        highlight ? "bg-brand-gold/15" : "bg-white"
      }`}
    >
      <p className="text-xs text-brand-cocoa/60">{label}</p>
      <p className="mt-1 text-xl font-bold text-brand-cocoa">{value}</p>
    </div>
  );

  return href ? <Link href={href}>{content}</Link> : content;
}
