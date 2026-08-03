"use client";

import { useEffect, useState } from "react";
import {
  createAdminCategory,
  deleteAdminCategory,
  getAdminCategories,
  updateAdminCategory,
} from "@/lib/admin-api";
import type { AdminCategory } from "@/lib/admin-types";

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [name, setName] = useState("");
  const [slug, setSlug] = useState("");
  const [error, setError] = useState<string | null>(null);

  function reload() {
    return getAdminCategories().then(setCategories);
  }

  useEffect(() => {
    reload();
  }, []);

  async function handleCreate(event: React.FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await createAdminCategory({ name, slug: slug || name.toLowerCase().replace(/\s+/g, "-") });
      setName("");
      setSlug("");
      await reload();
    } catch {
      setError("Could not create category — slug may already be in use.");
    }
  }

  async function handleDelete(id: number) {
    await deleteAdminCategory(id);
    await reload();
  }

  return (
    <div>
      <h1 className="mb-4 font-display text-xl font-bold text-brand-cocoa">Categories</h1>

      <form onSubmit={handleCreate} className="mb-6 flex flex-wrap items-end gap-2 rounded-xl bg-white p-4 shadow-sm">
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Name</label>
          <input
            required
            value={name}
            onChange={(event) => setName(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Slug (optional)</label>
          <input
            value={slug}
            onChange={(event) => setSlug(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm"
          />
        </div>
        <button type="submit" className="min-h-9 rounded-full bg-brand-pink px-4 text-sm font-semibold text-white">
          Add category
        </button>
        {error && <p className="text-sm text-red-600">{error}</p>}
      </form>

      <div className="rounded-xl bg-white p-4 shadow-sm">
        {categories.map((category) => (
          <div key={category.id} className="flex items-center justify-between border-b border-brand-cocoa/5 py-2 last:border-0">
            <div className="flex items-center gap-3">
              <input
                defaultValue={category.name}
                onBlur={(event) => updateAdminCategory(category.id, { name: event.target.value }).then(reload)}
                className="rounded border border-transparent px-1 text-sm hover:border-brand-cocoa/15 focus:border-brand-cocoa/30"
              />
              <span className="text-xs text-brand-cocoa/40">/{category.slug}</span>
            </div>
            <button
              type="button"
              onClick={() => handleDelete(category.id)}
              className="text-sm font-medium text-red-600"
            >
              Delete
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}
