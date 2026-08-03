import Link from "next/link";
import { PaymentProofUpload } from "@/components/PaymentProofUpload";
import { getOrderStatus } from "@/lib/api";

export const dynamic = "force-dynamic";

export default async function OrderStatusPage({
  params,
  searchParams,
}: {
  params: Promise<{ reference: string }>;
  searchParams: Promise<{ signature?: string; expires?: string; proof_signature?: string }>;
}) {
  const { reference } = await params;
  const { signature, expires, proof_signature: proofSignature } = await searchParams;

  let status: Awaited<ReturnType<typeof getOrderStatus>> | null = null;
  let error: string | null = null;

  if (!signature || !expires) {
    error = "This order link is invalid.";
  } else {
    try {
      status = await getOrderStatus(reference, { signature, expires });
    } catch {
      error = "We couldn't find that order.";
    }
  }

  return (
    <div className="mx-auto w-full max-w-lg px-4 py-10 text-center sm:px-6 lg:px-8">
      {error || !status ? (
        <>
          <h1 className="mb-2 font-display text-2xl font-bold text-brand-cocoa">
            Order not found
          </h1>
          <p className="mb-6 text-sm text-brand-cocoa/70">{error}</p>
        </>
      ) : (
        <>
          <h1 className="mb-2 font-display text-2xl font-bold text-brand-cocoa">
            Thank you!
          </h1>
          <p className="mb-1 text-sm text-brand-cocoa/70">Order {status.order_number}</p>
          <p className="mb-6 inline-block rounded-full bg-brand-gold/20 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-cocoa">
            {status.status.replace(/_/g, " ")}
          </p>

          {status.awaiting_proof && proofSignature && (
            <PaymentProofUpload
              reference={reference}
              signature={proofSignature}
              expires={expires!}
              totalSen={status.total_sen}
            />
          )}
        </>
      )}

      <Link
        href="/"
        className="mt-6 inline-block min-h-11 rounded-full bg-brand-pink px-6 py-2.5 text-sm font-semibold text-white"
      >
        Back to store
      </Link>
    </div>
  );
}
