import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface CartItem {
  productId: string;
  variantId: number;
  productName: string;
  variantName: string;
  unitPriceSen: number;
  quantity: number;
  capacityUnitsEach: number;
  minLeadTimeDays: number;
}

export type FulfilmentMethod = "pickup" | "delivery";

export interface DeliveryQuote {
  postcode: string;
  zoneName: string;
  deliveryFeeSen: number;
}

interface CartState {
  items: CartItem[];
  preorderDate: string | null;
  fulfilmentMethod: FulfilmentMethod;
  timeSlotId: number | null;
  postcode: string | null;
  deliveryQuote: DeliveryQuote | null;
  idempotencyKey: string;
  isCartCollapsed: boolean;
  setCartCollapsed: (collapsed: boolean) => void;
  addItem: (item: Omit<CartItem, "quantity">) => void;
  updateQuantity: (variantId: number, quantity: number) => void;
  removeItem: (variantId: number) => void;
  setPreorderDate: (date: string | null) => void;
  setFulfilmentMethod: (method: FulfilmentMethod) => void;
  setTimeSlotId: (timeSlotId: number | null) => void;
  setPostcode: (postcode: string | null) => void;
  setDeliveryQuote: (quote: DeliveryQuote | null) => void;
  clear: () => void;
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      items: [],
      preorderDate: null,
      fulfilmentMethod: "pickup",
      timeSlotId: null,
      postcode: null,
      deliveryQuote: null,
      idempotencyKey: crypto.randomUUID(),
      isCartCollapsed: false,
      setCartCollapsed: (collapsed) => set({ isCartCollapsed: collapsed }),

      addItem: (item) =>
        set((state) => {
          const existing = state.items.find((line) => line.variantId === item.variantId);

          if (existing) {
            return {
              isCartCollapsed: false,
              items: state.items.map((line) =>
                line.variantId === item.variantId
                  ? { ...line, quantity: line.quantity + 1 }
                  : line,
              ),
            };
          }

          return { items: [...state.items, { ...item, quantity: 1 }], isCartCollapsed: false };
        }),

      updateQuantity: (variantId, quantity) =>
        set((state) => ({
          items:
            quantity <= 0
              ? state.items.filter((line) => line.variantId !== variantId)
              : state.items.map((line) =>
                  line.variantId === variantId ? { ...line, quantity } : line,
                ),
        })),

      removeItem: (variantId) =>
        set((state) => ({
          items: state.items.filter((line) => line.variantId !== variantId),
        })),

      // Atomic resets: each setter clears its dependents in the same set()
      // call, so no render ever observes a stale/incompatible combination
      // (e.g. a delivery-only slot still selected after switching to pickup).
      setPreorderDate: (date) => set({ preorderDate: date, timeSlotId: null }),

      setFulfilmentMethod: (method) =>
        set({
          fulfilmentMethod: method,
          timeSlotId: null,
          postcode: method === "pickup" ? null : get().postcode,
          deliveryQuote: method === "pickup" ? null : get().deliveryQuote,
        }),

      setTimeSlotId: (timeSlotId) => set({ timeSlotId }),

      setPostcode: (postcode) => set({ postcode, deliveryQuote: null }),

      setDeliveryQuote: (deliveryQuote) => set({ deliveryQuote }),

      clear: () =>
        set({
          items: [],
          preorderDate: null,
          fulfilmentMethod: "pickup",
          timeSlotId: null,
          postcode: null,
          deliveryQuote: null,
          idempotencyKey: crypto.randomUUID(),
        }),
    }),
    { name: "ninadough-cart" },
  ),
);

export function getItemCount(items: CartItem[]): number {
  return items.reduce((total, line) => total + line.quantity, 0);
}

export function getSubtotalSen(items: CartItem[]): number {
  return items.reduce((total, line) => total + line.unitPriceSen * line.quantity, 0);
}

export function getMaxLeadTimeDays(items: CartItem[]): number {
  return items.reduce((max, line) => Math.max(max, Number(line.minLeadTimeDays) || 0), 0);
}
