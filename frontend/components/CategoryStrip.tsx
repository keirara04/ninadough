import type { ProductCategory } from "@/lib/types";

export function CategoryStrip({
  categories,
  activeCategoryId,
  onSelect,
}: {
  categories: ProductCategory[];
  activeCategoryId: number | null;
  onSelect: (categoryId: number | null) => void;
}) {
  if (categories.length === 0) {
    return null;
  }

  return (
    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div className="scrollbar-none flex gap-2 overflow-x-auto pb-1 lg:flex-wrap">
        <Pill label="All" active={activeCategoryId === null} onClick={() => onSelect(null)} />
        {categories.map((category) => (
          <Pill
            key={category.id}
            label={category.name}
            active={activeCategoryId === category.id}
            onClick={() => onSelect(category.id)}
          />
        ))}
      </div>
    </div>
  );
}

function Pill({ label, active, onClick }: { label: string; active: boolean; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`min-h-9 shrink-0 whitespace-nowrap rounded-full border px-4 text-sm font-medium transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-pink/50 focus-visible:ring-offset-2 ${
        active
          ? "border-brand-cocoa bg-brand-cocoa text-white"
          : "border-brand-cocoa/20 text-brand-cocoa/70"
      }`}
    >
      {label}
    </button>
  );
}
