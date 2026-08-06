import { ApiError } from "./api";
import type {
  AdminCategory,
  AdminDashboard,
  AdminOrder,
  AdminPreorderDate,
  AdminProduct,
  AdminTimeSlot,
} from "./admin-types";
import { useAdminStore } from "./admin-store";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

interface PaginatedCollection<T> {
  data: T[];
  meta: { current_page: number; last_page: number };
}

interface ApiResource<T> {
  data: T;
}

function authHeaders(): HeadersInit {
  const token = useAdminStore.getState().token;
  return token ? { Authorization: `Bearer ${token}` } : {};
}

async function handle<T>(response: Response): Promise<T> {
  if (response.status === 401) {
    useAdminStore.getState().clearSession();
  }

  if (!response.ok) {
    const body = await response.json().catch(() => null);
    throw new ApiError(body?.message ?? `Request failed with status ${response.status}`, response.status, body?.errors);
  }

  return response.json() as Promise<T>;
}

async function adminFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${API_URL}/admin${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...authHeaders(),
      ...init?.headers,
    },
  });

  return handle<T>(response);
}

async function adminFetchAllPages<T>(path: string): Promise<T[]> {
  const separator = path.includes("?") ? "&" : "?";
  const items: T[] = [];
  let page = 1;

  while (true) {
    const result = await adminFetch<PaginatedCollection<T>>(`${path}${separator}page=${page}`);
    items.push(...result.data);

    if (page >= result.meta.last_page) {
      break;
    }
    page += 1;
  }

  return items;
}

export async function adminLogin(email: string, password: string) {
  const response = await fetch(`${API_URL}/admin/login`, {
    method: "POST",
    headers: { Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify({ email, password }),
  });

  const { data } = await handle<ApiResource<{ token: string; user: { name: string; email: string; role: string } }>>(
    response,
  );
  return data;
}

export async function adminLogout() {
  await adminFetch("/logout", { method: "POST" }).catch(() => {});
}

export async function getAdminOrders(status?: string): Promise<AdminOrder[]> {
  const suffix = status ? `?status=${encodeURIComponent(status)}` : "";
  return adminFetchAllPages<AdminOrder>(`/orders${suffix}`);
}

export async function getAdminOrder(id: number): Promise<AdminOrder> {
  const { data } = await adminFetch<ApiResource<AdminOrder>>(`/orders/${id}`);
  return data;
}

export async function updateAdminOrderStatus(id: number, status: string): Promise<AdminOrder> {
  const { data } = await adminFetch<ApiResource<AdminOrder>>(`/orders/${id}/status`, {
    method: "PATCH",
    body: JSON.stringify({ status }),
  });
  return data;
}

export async function reviewAdminPayment(
  paymentId: number,
  action: "approve" | "reject",
  options?: { note?: string; rejectionMessage?: string },
): Promise<AdminOrder> {
  const { data } = await adminFetch<ApiResource<AdminOrder>>(`/payments/${paymentId}/review`, {
    method: "PATCH",
    body: JSON.stringify({
      action,
      note: options?.note,
      rejection_message: options?.rejectionMessage,
    }),
  });
  return data;
}

export async function getAdminCategories(): Promise<AdminCategory[]> {
  return adminFetchAllPages<AdminCategory>("/categories");
}

export async function createAdminCategory(input: { name: string; slug: string }): Promise<AdminCategory> {
  const { data } = await adminFetch<ApiResource<AdminCategory>>("/categories", {
    method: "POST",
    body: JSON.stringify(input),
  });
  return data;
}

export async function updateAdminCategory(
  id: number,
  input: Partial<{ name: string; slug: string; sort_order: number }>,
): Promise<AdminCategory> {
  const { data } = await adminFetch<ApiResource<AdminCategory>>(`/categories/${id}`, {
    method: "PATCH",
    body: JSON.stringify(input),
  });
  return data;
}

export async function deleteAdminCategory(id: number): Promise<void> {
  await adminFetch(`/categories/${id}`, { method: "DELETE" });
}

export async function getAdminProducts(): Promise<AdminProduct[]> {
  return adminFetchAllPages<AdminProduct>("/products");
}

export interface AdminProductInput {
  name: string;
  slug?: string;
  short_description?: string;
  description?: string;
  base_price_sen: number;
  category_id?: number | null;
  is_active?: boolean;
  is_featured?: boolean;
}

export async function createAdminProduct(input: AdminProductInput): Promise<AdminProduct> {
  const { data } = await adminFetch<ApiResource<AdminProduct>>("/products", {
    method: "POST",
    body: JSON.stringify(input),
  });
  return data;
}

export async function updateAdminProduct(id: string, input: Partial<AdminProductInput>): Promise<AdminProduct> {
  const { data } = await adminFetch<ApiResource<AdminProduct>>(`/products/${id}`, {
    method: "PATCH",
    body: JSON.stringify(input),
  });
  return data;
}

export async function deleteAdminProduct(id: string): Promise<void> {
  await adminFetch(`/products/${id}`, { method: "DELETE" });
}

export interface AdminProductImage {
  id: number;
  url: string;
  alt_text: string | null;
  sort_order: number;
  is_primary: boolean;
}

export async function uploadAdminProductImage(productId: string, file: File): Promise<AdminProductImage> {
  const formData = new FormData();
  formData.append("image", file);

  const response = await fetch(`${API_URL}/admin/products/${productId}/images`, {
    method: "POST",
    headers: { Accept: "application/json", ...authHeaders() },
    body: formData,
  });

  const { data } = await handle<ApiResource<AdminProductImage>>(response);
  return data;
}

export async function deleteAdminProductImage(productId: string, imageId: number): Promise<void> {
  await adminFetch(`/products/${productId}/images/${imageId}`, { method: "DELETE" });
}

export async function setAdminProductImagePrimary(productId: string, imageId: number): Promise<AdminProductImage> {
  const { data } = await adminFetch<ApiResource<AdminProductImage>>(
    `/products/${productId}/images/${imageId}/primary`,
    { method: "PATCH" },
  );
  return data;
}

export async function getAdminPreorderDates(): Promise<AdminPreorderDate[]> {
  return adminFetchAllPages<AdminPreorderDate>("/preorder-dates");
}

export interface AdminPreorderDateInput {
  order_date?: string;
  cutoff_at: string;
  capacity_limit: number;
  pickup_enabled?: boolean;
  delivery_enabled?: boolean;
  status?: "open" | "closed" | "full";
  note_internal?: string | null;
}

export async function createAdminPreorderDate(input: AdminPreorderDateInput): Promise<AdminPreorderDate> {
  const { data } = await adminFetch<ApiResource<AdminPreorderDate>>("/preorder-dates", {
    method: "POST",
    body: JSON.stringify(input),
  });
  return data;
}

export async function updateAdminPreorderDate(
  id: number,
  input: Partial<AdminPreorderDateInput>,
): Promise<AdminPreorderDate> {
  const { data } = await adminFetch<ApiResource<AdminPreorderDate>>(`/preorder-dates/${id}`, {
    method: "PATCH",
    body: JSON.stringify(input),
  });
  return data;
}

export async function getAdminTimeSlots(preorderDateId: number): Promise<AdminTimeSlot[]> {
  const { data } = await adminFetch<{ data: AdminTimeSlot[] }>(`/preorder-dates/${preorderDateId}/time-slots`);
  return data;
}

export interface AdminTimeSlotInput {
  label: string;
  starts_at: string;
  ends_at: string;
  fulfilment_method: "pickup" | "delivery" | "both";
  capacity_limit: number;
  is_active?: boolean;
}

export async function createAdminTimeSlot(
  preorderDateId: number,
  input: AdminTimeSlotInput,
): Promise<AdminTimeSlot> {
  const { data } = await adminFetch<ApiResource<AdminTimeSlot>>(`/preorder-dates/${preorderDateId}/time-slots`, {
    method: "POST",
    body: JSON.stringify(input),
  });
  return data;
}

export async function updateAdminTimeSlot(
  preorderDateId: number,
  timeSlotId: number,
  input: Partial<AdminTimeSlotInput>,
): Promise<AdminTimeSlot> {
  const { data } = await adminFetch<ApiResource<AdminTimeSlot>>(
    `/preorder-dates/${preorderDateId}/time-slots/${timeSlotId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
  return data;
}

export async function deactivateAdminTimeSlot(preorderDateId: number, timeSlotId: number): Promise<void> {
  await adminFetch(`/preorder-dates/${preorderDateId}/time-slots/${timeSlotId}`, { method: "DELETE" });
}

export async function getAdminDashboard(): Promise<AdminDashboard> {
  const { data } = await adminFetch<ApiResource<AdminDashboard>>("/dashboard");
  return data;
}
