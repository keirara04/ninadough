"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { useToast } from "@/components/ui/ToastProvider";
import { ApiError } from "@/lib/api";
import {
  createAdminCategory,
  deleteAdminCategory,
  getAdminCategories,
  updateAdminCategory,
} from "@/lib/admin-api";
import type { AdminCategory } from "@/lib/admin-types";

export default function AdminCategoriesPage() {
  const toast = useToast();
  const confirm = useConfirm();
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [name, setName] = useState("");
  const [slug, setSlug] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isCreating, setIsCreating] = useState(false);

  function reload() {
    return getAdminCategories().then(setCategories);
  }

  useEffect(() => {
    reload();
  }, []);

  async function handleCreate(event: React.FormEvent) {
    event.preventDefault();
    setError(null);
    setIsCreating(true);
    try {
      await createAdminCategory({ name, slug: slug || name.toLowerCase().replace(/\s+/g, "-") });
      setName("");
      setSlug("");
      await reload();
      toast.show("Category added");
    } catch {
      setError("Could not create category — slug may already be in use.");
    } finally {
      setIsCreating(false);
    }
  }

  async function handleRename(category: AdminCategory, newName: string) {
    if (newName === category.name) return;
    try {
      await updateAdminCategory(category.id, { name: newName });
      await reload();
      toast.show("Category updated");
    } catch (renameError) {
      toast.show(renameError instanceof ApiError ? renameError.message : "Could not rename category.", "error");
      await reload();
    }
  }

  async function handleDelete(category: AdminCategory) {
    const confirmed = await confirm(`Delete category "${category.name}"? This can't be undone.`);
    if (!confirmed) return;

    try {
      await deleteAdminCategory(category.id);
      await reload();
      toast.show("Category deleted");
    } catch (deleteError) {
      toast.show(deleteError instanceof ApiError ? deleteError.message : "Could not delete category.", "error");
    }
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
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2"
          />
        </div>
        <div className="flex flex-col gap-1">
          <label className="text-xs text-brand-cocoa/60">Slug (optional)</label>
          <input
            value={slug}
            onChange={(event) => setSlug(event.target.value)}
            className="min-h-9 rounded-lg border border-brand-cocoa/15 px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2"
          />
        </div>
        <Button type="submit" size="sm" isLoading={isCreating}>
          Add category
        </Button>
        {error && <p className="text-sm text-red-600">{error}</p>}
      </form>

      <div className="rounded-xl bg-white p-4 shadow-sm">
        {categories.map((category) => (
          <div key={category.id} className="flex items-center justify-between border-b border-brand-cocoa/5 py-2 last:border-0">
            <div className="flex items-center gap-3">
              <input
                defaultValue={category.name}
                onBlur={(event) => handleRename(category, event.target.value)}
                className="rounded border border-transparent px-1 text-sm hover:border-brand-cocoa/15 focus:border-brand-cocoa/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50"
              />
              <span className="text-xs text-brand-cocoa/40">/{category.slug}</span>
            </div>
            <Button variant="danger" size="sm" onClick={() => handleDelete(category)}>
              Delete
            </Button>
          </div>
        ))}
      </div>
    </div>
  );
}
