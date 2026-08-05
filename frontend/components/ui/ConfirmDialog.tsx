"use client";

import { createContext, useCallback, useContext, useEffect, useRef, useState } from "react";
import { Button } from "./Button";

interface PendingConfirm {
  message: string;
  resolve: (confirmed: boolean) => void;
}

type ConfirmFn = (message: string) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);

export function ConfirmProvider({ children }: { children: React.ReactNode }) {
  const [pending, setPending] = useState<PendingConfirm | null>(null);
  const closeButtonRef = useRef<HTMLButtonElement>(null);

  const confirm = useCallback<ConfirmFn>((message) => {
    return new Promise((resolve) => {
      setPending({ message, resolve });
    });
  }, []);

  function settle(result: boolean) {
    pending?.resolve(result);
    setPending(null);
  }

  useEffect(() => {
    if (!pending) return;

    closeButtonRef.current?.focus();

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") {
        settle(false);
      }
    }
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pending]);

  return (
    <ConfirmContext.Provider value={confirm}>
      {children}

      {pending && (
        <div className="animate-fade-in fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
          <div className="animate-pop-in w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <p className="mb-4 text-sm text-brand-cocoa">{pending.message}</p>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" size="sm" onClick={() => settle(false)}>
                Cancel
              </Button>
              <Button ref={closeButtonRef} variant="danger" size="sm" onClick={() => settle(true)}>
                Confirm
              </Button>
            </div>
          </div>
        </div>
      )}
    </ConfirmContext.Provider>
  );
}

export function useConfirm(): ConfirmFn {
  const context = useContext(ConfirmContext);
  if (!context) {
    throw new Error("useConfirm must be used within a ConfirmProvider");
  }
  return context;
}
