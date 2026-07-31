export interface ProductImage {
  url: string | null;
  alt_text: string | null;
  is_primary: boolean;
}

export interface ProductOptionValue {
  name: string;
  value_code: string;
}

export interface ProductOptionGroup {
  name: string;
  selection_type: string;
  is_required: boolean;
  values: ProductOptionValue[];
}

export interface ProductVariant {
  id: number;
  name: string;
  price_sen: number;
  capacity_units: number;
  option_values: string[];
}

export interface Product {
  id: string;
  name: string;
  slug: string;
  short_description: string | null;
  description: string | null;
  base_price_sen: number;
  allergen_information: string | null;
  images: ProductImage[];
  option_groups: ProductOptionGroup[];
  variants: ProductVariant[];
}

export interface PreorderDate {
  order_date: string;
  status: "open" | "closed" | "full";
  cutoff_at: string;
  remaining_capacity: number;
  pickup_enabled: boolean;
  delivery_enabled: boolean;
}
