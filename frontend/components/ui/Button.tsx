"use client";

import { forwardRef } from "react";

export type ButtonVariant = "primary" | "secondary" | "danger" | "success" | "ghost";
export type ButtonSize = "default" | "sm";

const BASE =
  "inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-transform active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2 disabled:opacity-40 disabled:pointer-events-none";

const VARIANTS: Record<ButtonVariant, string> = {
  primary: "bg-brand-pink text-white",
  secondary: "border border-brand-cocoa/20 text-brand-cocoa bg-white hover:bg-brand-cream",
  danger: "bg-red-600 text-white",
  success: "bg-green-600 text-white",
  ghost: "text-brand-cocoa hover:bg-brand-cream/60",
};

const SIZES: Record<ButtonSize, string> = {
  default: "min-h-11 px-5 text-sm",
  sm: "min-h-9 px-4 text-sm",
};

export function buttonClassName({
  variant = "primary",
  size = "default",
  className = "",
}: {
  variant?: ButtonVariant;
  size?: ButtonSize;
  className?: string;
} = {}): string {
  return `${BASE} ${VARIANTS[variant]} ${SIZES[size]} ${className}`.trim();
}

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  isLoading?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant = "primary", size = "default", isLoading = false, disabled, className = "", children, ...props },
  ref,
) {
  return (
    <button
      ref={ref}
      type={props.type ?? "button"}
      disabled={disabled || isLoading}
      className={buttonClassName({ variant, size, className })}
      {...props}
    >
      {isLoading && (
        <svg viewBox="0 0 24 24" fill="none" className="h-4 w-4 animate-spin">
          <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth={3} opacity={0.25} />
          <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" strokeWidth={3} strokeLinecap="round" />
        </svg>
      )}
      {children}
    </button>
  );
});
