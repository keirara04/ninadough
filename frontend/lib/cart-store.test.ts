import { beforeEach, describe, expect, it } from "vitest";
import { useCartStore } from "./cart-store";

// Covers the atomic-reset rules from MVP_FRONTEND_PLAN.md §0b: each setter
// must clear its dependents in the same set() call so no render (or test)
// ever observes a stale/incompatible combination.

const ITEM = {
  productId: "p1",
  variantId: 1,
  productName: "Cake",
  variantName: "Regular",
  unitPriceSen: 1000,
  capacityUnitsEach: 1,
  minLeadTimeDays: 0,
};

function resetStore() {
  useCartStore.setState({
    items: [],
    preorderDate: null,
    fulfilmentMethod: "pickup",
    timeSlotId: null,
    postcode: null,
    deliveryQuote: null,
  });
}

beforeEach(() => {
  resetStore();
});

describe("setPreorderDate", () => {
  it("clears the selected time slot", () => {
    useCartStore.setState({ timeSlotId: 42 });

    useCartStore.getState().setPreorderDate("2026-09-01");

    expect(useCartStore.getState().preorderDate).toBe("2026-09-01");
    expect(useCartStore.getState().timeSlotId).toBeNull();
  });
});

describe("setFulfilmentMethod", () => {
  it("clears the time slot when switching pickup -> delivery", () => {
    useCartStore.setState({ fulfilmentMethod: "pickup", timeSlotId: 7 });

    useCartStore.getState().setFulfilmentMethod("delivery");

    expect(useCartStore.getState().timeSlotId).toBeNull();
  });

  it("clears postcode and delivery quote when switching delivery -> pickup", () => {
    useCartStore.setState({
      fulfilmentMethod: "delivery",
      postcode: "40000",
      deliveryQuote: { postcode: "40000", zoneName: "Klang Valley", deliveryFeeSen: 800 },
      timeSlotId: 3,
    });

    useCartStore.getState().setFulfilmentMethod("pickup");

    const state = useCartStore.getState();
    expect(state.postcode).toBeNull();
    expect(state.deliveryQuote).toBeNull();
    expect(state.timeSlotId).toBeNull();
  });

  it("keeps postcode and delivery quote when switching delivery -> delivery (no-op)", () => {
    useCartStore.setState({
      fulfilmentMethod: "delivery",
      postcode: "40000",
      deliveryQuote: { postcode: "40000", zoneName: "Klang Valley", deliveryFeeSen: 800 },
    });

    useCartStore.getState().setFulfilmentMethod("delivery");

    const state = useCartStore.getState();
    expect(state.postcode).toBe("40000");
    expect(state.deliveryQuote).not.toBeNull();
  });

  it("does not resurrect a postcode after pickup -> delivery -> pickup -> delivery", () => {
    useCartStore.setState({ fulfilmentMethod: "delivery", postcode: "40000" });

    useCartStore.getState().setFulfilmentMethod("pickup");
    useCartStore.getState().setFulfilmentMethod("delivery");

    expect(useCartStore.getState().postcode).toBeNull();
  });
});

describe("setPostcode", () => {
  it("clears the stale delivery quote for the previous postcode", () => {
    useCartStore.setState({
      postcode: "40000",
      deliveryQuote: { postcode: "40000", zoneName: "Klang Valley", deliveryFeeSen: 800 },
    });

    useCartStore.getState().setPostcode("50000");

    const state = useCartStore.getState();
    expect(state.postcode).toBe("50000");
    expect(state.deliveryQuote).toBeNull();
  });
});

describe("clear", () => {
  it("resets cart, fulfilment, slot, and delivery state together", () => {
    useCartStore.setState({
      items: [{ ...ITEM, quantity: 1 }],
      preorderDate: "2026-09-01",
      fulfilmentMethod: "delivery",
      timeSlotId: 5,
      postcode: "40000",
      deliveryQuote: { postcode: "40000", zoneName: "Klang Valley", deliveryFeeSen: 800 },
    });

    useCartStore.getState().clear();

    const state = useCartStore.getState();
    expect(state.items).toEqual([]);
    expect(state.preorderDate).toBeNull();
    expect(state.fulfilmentMethod).toBe("pickup");
    expect(state.timeSlotId).toBeNull();
    expect(state.postcode).toBeNull();
    expect(state.deliveryQuote).toBeNull();
  });
});
