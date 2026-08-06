"use client";

import { useEffect, useState } from "react";
import { useAdminStore } from "@/lib/admin-store";

interface AuthenticatedImageProps {
  src: string;
  alt: string;
  className?: string;
}

/**
 * Proof images live behind Sanctum auth (no public URL) — a plain <img src>
 * sends no Authorization header and 401s. Fetch with the bearer token and
 * render the response as a blob URL instead.
 */
export function AuthenticatedImage({ src, alt, className }: AuthenticatedImageProps) {
  const token = useAdminStore((state) => state.token);
  const [objectUrl, setObjectUrl] = useState<string | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    let cancelled = false;
    let currentUrl: string | null = null;

    Promise.resolve()
      .then(() => {
        setError(false);
        setObjectUrl(null);
      })
      .then(() => fetch(src, { headers: token ? { Authorization: `Bearer ${token}` } : {} }))
      .then((response) => {
        if (!response.ok) throw new Error("Failed to load image");
        return response.blob();
      })
      .then((blob) => {
        if (cancelled) return;
        currentUrl = URL.createObjectURL(blob);
        setObjectUrl(currentUrl);
      })
      .catch(() => {
        if (!cancelled) setError(true);
      });

    return () => {
      cancelled = true;
      if (currentUrl) URL.revokeObjectURL(currentUrl);
    };
  }, [src, token]);

  if (error) {
    return <p className="text-xs text-red-600">Couldn&apos;t load this image.</p>;
  }

  if (!objectUrl) {
    return <div className={`animate-pulse rounded-lg bg-brand-cocoa/10 ${className ?? "h-48 w-full"}`} />;
  }

  return (
    <button
      type="button"
      onClick={() => window.open(objectUrl, "_blank", "noopener,noreferrer")}
      className="block cursor-zoom-in"
    >
      {/* eslint-disable-next-line @next/next/no-img-element -- blob: URL, next/image can't optimize it */}
      <img src={objectUrl} alt={alt} className={className} />
    </button>
  );
}
