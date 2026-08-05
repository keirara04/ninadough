"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { useToast } from "@/components/ui/ToastProvider";
import { ApiError } from "@/lib/api";
import {
  createAdminProduct,
  deleteAdminProduct,
  getAdminCategories,
  getAdminProducts,
  updateAdminProduct,
} from "@/lib/admin-api";
import { formatSen } from "@/lib/format";
import type { AdminCategory, AdminProduct } from "@/lib/admin-types";

export default function AdminProductsPage() {
  const toast = useToast();
  const confirm = useConfirm();
  const [products, setProducts] = useState<AdminProduct[]>([]);
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [name, setName] = useState("");
  const [priceRinggit, setPriceRinggit] = useState("");
  const [categoryId, setCategoryId] = useState<string>("");
  const [error, setError] = useState<string | null>(null);
  const [isCreating, setIsCreating] = useState(false);
  const [busyProductId, setBusyProductId] = useState<string | null>(null);

  function reload() {
    return Promise.all([getAdminProducts(), getAdminCategories()]).then(([productsList, categoriesList]) => {
      setProducts(productsList);
      setCategories(categoriesList);
    });
  }

  useEffect(() => {
    reload();
  }, []);

  async function handleCreate(event: React.FormEvent) {
    event.preventDefault();
    setError(null);
    setIsCreating(true);
    try {
      await createAdminProduct({
        name,
        base_price_sen: Math.round(parseFloat(priceRinggit) * 100),
        category_id: categoryId ? Number(categoryId) : null,
        is_active: true,
      });
      setName("");
      setPriceRinggit("");
      setCategoryId("");
      await reload();
      toast.show("Product added");
    } catch {
      setError("Could not create product.");
    } finally {
      setIsCreating(false);
    }
  }

  async function toggleActive(product: AdminProduct) {
    setBusyProductId(product.id);
    try {
      await updateAdminProduct(product.id, { is_active: !product.is_active });
      await reload();
    } catch (toggleError) {
      toast.show(toggleError instanceof ApiError ? toggleError.message : "Could not update product.", "error");
    } finally {
      setBusyProductId(null);
    }
  }

  async function toggleFeatured(product: AdminProduct) {
    setBusyProductId(product.id);
    try {
      await updateAdminProduct(product.id, { is_featured: !product.is_featured });
      await reload();
    } catch (toggleError) {
      toast.show(toggleError instanceof ApiError ? toggleError.message : "Could not update product.", "error");
    } finally {
      setBusyProductId(null);
    }
  }

  async function handleDelete(product: AdminProduct) {
    const confirmed = await confirm(`Delete "${product.name}"? This can't be undone.`);
    if (!confirmed) return;

    try {
      await deleteAdminProduct(product.id);
      await reload();
      toast.show("Product deleted");
    } catch (deleteError) {
      toast.show(deleteError instanceof ApiError ? deleteError.message : "Could not delete product.", "error");
    }
  }

  return (
    <div>
      <h1 className="mb-4 font-display text-xl font-bold text-brand-cocoa">Products</h1>
      <p className="mb-4 text-xs text-brand-cocoa/50">
        New products need variants/images added separately before they can be sold — this form covers core details only.
      </p>

      <form onSubmit={handleCreate} className="mb-6 flex flex-wrap items-end gap-2 rounded-xl bg-white p-4 shadow-sm">
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Name</label>
          <input
            required
            value={name}
            onChange={(event) => setName(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Price (RM)</label>
          <input
            required
            type="number"
            step="0.01"
            value={priceRinggit}
            onChange={(event) => setPriceRinggit(event.target.value)}
            className="min-h-9 w-24 rounded-lg border border-brand-cocoa/15 px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Category</label>
          <select
            value={categoryId}
            onChange={(event) => setCategoryId(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2"
          >
            <option value="">None</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </div>
        <Button type="submit" size="sm" isLoading={isCreating}>
          Add product
        </Button>
        {error && <p className="text-sm text-red-600">{error}</p>}
      </form>

      <div className="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-brand-cocoa/10 text-xs uppercase text-brand-cocoa/50">
            <tr>
              <th className="px-4 py-2">Name</th>
              <th className="px-4 py-2">Category</th>
              <th className="px-4 py-2">Price</th>
              <th className="px-4 py-2">Active</th>
              <th className="px-4 py-2">Featured</th>
              <th className="px-4 py-2" />
            </tr>
          </thead>
          <tbody>
            {products.map((product) => (
              <tr key={product.id} className="border-b border-brand-cocoa/5 last:border-0">
                <td className="px-4 py-2">{product.name}</td>
                <td className="px-4 py-2">{product.category?.name ?? "—"}</td>
                <td className="px-4 py-2">{formatSen(product.base_price_sen)}</td>
                <td className="px-4 py-2">
                  <input
                    type="checkbox"
                    checked={product.is_active}
                    disabled={busyProductId === product.id}
                    onChange={() => toggleActive(product)}
                  />
                </td>
                <td className="px-4 py-2">
                  <input
                    type="checkbox"
                    checked={product.is_featured}
                    disabled={busyProductId === product.id}
                    onChange={() => toggleFeatured(product)}
                  />
                </td>
                <td className="px-4 py-2">
                  <Button variant="danger" size="sm" onClick={() => handleDelete(product)}>
                    Delete
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
