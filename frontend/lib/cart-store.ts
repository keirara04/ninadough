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
}

export type FulfilmentMethod = "pickup" | "delivery";

interface CartState {
  items: CartItem[];
  preorderDate: string | null;
  fulfilmentMethod: FulfilmentMethod;
  addItem: (item: Omit<CartItem, "quantity">) => void;
  updateQuantity: (variantId: number, quantity: number) => void;
  removeItem: (variantId: number) => void;
  setPreorderDate: (date: string | null) => void;
  setFulfilmentMethod: (method: FulfilmentMethod) => void;
  clear: () => void;
}

export const useCartStore = create<CartState>()(
  persist(
    (set) => ({
      items: [],
      preorderDate: null,
      fulfilmentMethod: "pickup",

      addItem: (item) =>
        set((state) => {
          const existing = state.items.find((line) => line.variantId === item.variantId);

          if (existing) {
            return {
              items: state.items.map((line) =>
                line.variantId === item.variantId
                  ? { ...line, quantity: line.quantity + 1 }
                  : line,
              ),
            };
          }

          return { items: [...state.items, { ...item, quantity: 1 }] };
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

      setPreorderDate: (date) => set({ preorderDate: date }),
      setFulfilmentMethod: (method) => set({ fulfilmentMethod: method }),
      clear: () => set({ items: [], preorderDate: null, fulfilmentMethod: "pickup" }),
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
