"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { ApiError, postPaymentProof } from "@/lib/api";

const WHATSAPP_NUMBER = process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "";

export function PaymentProofUpload({
  reference,
  signature,
  expires,
  totalSen,
}: {
  reference: string;
  signature: string;
  expires: string;
  totalSen: number;
}) {
  const router = useRouter();
  const [file, setFile] = useState<File | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (!file) {
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await postPaymentProof(reference, { signature, expires }, file);
      router.refresh();
    } catch (submitError) {
      setError(
        submitError instanceof ApiError
          ? submitError.message
          : "Could not upload proof. Please try again.",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="mt-6 rounded-2xl bg-white p-4 text-left shadow-sm">
      <h2 className="mb-2 font-display text-sm font-semibold text-brand-cocoa">
        Complete your payment
      </h2>
      <p className="mb-1 text-sm text-brand-cocoa/70">
        Transfer to Maybank 1234567890, amount RM{(totalSen / 100).toFixed(2)}.
      </p>
      <p className="mb-3 text-sm text-brand-cocoa/70">
        Then upload your proof of payment below{WHATSAPP_NUMBER ? " (or send it via WhatsApp instead)" : ""}.
      </p>

      <form onSubmit={handleSubmit} className="flex flex-col gap-3">
        <label className="flex min-h-11 cursor-pointer items-center justify-between gap-2 rounded-xl border border-dashed border-brand-cocoa/25 bg-brand-cream/40 px-4 py-3 text-sm text-brand-cocoa transition active:scale-[0.98]">
          <span className="truncate">{file ? file.name : "Choose receipt photo or PDF"}</span>
          <span className="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-brand-pink shadow-sm">
            {file ? "Change" : "Browse"}
          </span>
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp,application/pdf"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
            className="sr-only"
          />
        </label>

        {error && <p className="text-sm text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={!file || isSubmitting}
          className="min-h-11 rounded-full bg-brand-pink text-sm font-semibold text-white transition-transform active:scale-[0.98] disabled:opacity-40"
        >
          {isSubmitting ? "Uploading..." : "Upload proof of payment"}
        </button>

        {WHATSAPP_NUMBER && (
          <a
            href={`https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(`Payment proof for order ${reference}`)}`}
            target="_blank"
            rel="noreferrer"
            className="text-center text-sm font-medium text-brand-pink"
          >
            Send proof via WhatsApp instead
          </a>
        )}
      </form>
    </div>
  );
}
