import type {
  CartQuote,
  CheckoutQuoteInput,
  CreateOrderInput,
  DeliveryZone,
  Order,
  OrderStatus,
  PreorderDate,
  Product,
  ProductCategory,
} from "./types";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

interface ApiCollection<T> {
  data: T[];
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

async function handleResponse<T>(response: Response): Promise<T> {
  if (!response.ok) {
    const body = await response.json().catch(() => null);
    throw new ApiError(
      body?.message ?? `Request failed with status ${response.status}`,
      response.status,
      body?.errors,
    );
  }

  return response.json() as Promise<T>;
}

async function fetchJson<T>(path: string): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    headers: { Accept: "application/json" },
  });

  return handleResponse<T>(response);
}

async function postJson<T>(path: string, body: unknown): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    method: "POST",
    headers: { Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  return handleResponse<T>(response);
}

export async function getProducts(params?: { q?: string; category?: string }): Promise<Product[]> {
  const query = new URLSearchParams();
  if (params?.q) query.set("q", params.q);
  if (params?.category) query.set("category", params.category);
  const suffix = query.toString() ? `?${query.toString()}` : "";

  const { data } = await fetchJson<ApiCollection<Product>>(`/products${suffix}`);
  return data;
}

export async function getProduct(slug: string): Promise<Product> {
  const { data } = await fetchJson<ApiResource<Product>>(`/products/${slug}`);
  return data;
}

export async function getPreorderDates(): Promise<PreorderDate[]> {
  const { data } = await fetchJson<ApiCollection<PreorderDate>>("/preorder-dates");
  return data;
}

export async function getCategories(): Promise<ProductCategory[]> {
  const { data } = await fetchJson<ApiCollection<ProductCategory>>("/categories");
  return data;
}

export async function getDeliveryZones(): Promise<DeliveryZone[]> {
  const { data } = await fetchJson<ApiCollection<DeliveryZone>>("/delivery-zones");
  return data;
}

interface ApiResource<T> {
  data: T;
}

export async function postCheckoutQuote(input: CheckoutQuoteInput): Promise<CartQuote> {
  const { data } = await postJson<ApiResource<CartQuote>>("/checkout/quote", input);
  return data;
}

export async function postOrder(input: CreateOrderInput): Promise<Order> {
  const { data } = await postJson<ApiResource<Order>>("/orders", input);
  return data;
}

export async function postPaymentProof(
  reference: string,
  signatureParams: { signature: string; expires: string },
  file: File,
): Promise<OrderStatus> {
  const query = new URLSearchParams(signatureParams).toString();
  const formData = new FormData();
  formData.append("proof", file);

  const response = await fetch(`${API_URL}/orders/${reference}/payment-proof?${query}`, {
    method: "POST",
    headers: { Accept: "application/json" },
    body: formData,
  });

  const { data } = await handleResponse<ApiResource<OrderStatus>>(response);
  return data;
}

export async function getOrderStatus(
  reference: string,
  signatureParams: { signature: string; expires: string },
): Promise<OrderStatus> {
  const query = new URLSearchParams(signatureParams).toString();
  const { data } = await fetchJson<ApiResource<OrderStatus>>(`/orders/${reference}/status?${query}`);
  return data;
}
