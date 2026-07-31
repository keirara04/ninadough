import type { PreorderDate, Product } from "./types";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

interface ApiCollection<T> {
  data: T[];
}

async function fetchJson<T>(path: string): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    headers: { Accept: "application/json" },
  });

  if (!response.ok) {
    throw new Error(`Request to ${path} failed with status ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export async function getProducts(): Promise<Product[]> {
  const { data } = await fetchJson<ApiCollection<Product>>("/products");
  return data;
}

export async function getPreorderDates(): Promise<PreorderDate[]> {
  const { data } = await fetchJson<ApiCollection<PreorderDate>>("/preorder-dates");
  return data;
}
