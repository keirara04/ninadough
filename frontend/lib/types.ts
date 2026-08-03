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

export interface ProductCategory {
  id: number;
  name: string;
  slug: string;
  sort_order: number;
}

export interface Product {
  id: string;
  name: string;
  slug: string;
  short_description: string | null;
  description: string | null;
  base_price_sen: number;
  allergen_information: string | null;
  is_featured: boolean;
  category: ProductCategory | null;
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

export interface DeliveryZone {
  id: number;
  name: string;
  description: string | null;
  delivery_fee_sen: number;
  minimum_order_sen: number | null;
}

export interface CartQuoteLine {
  product_id: number;
  product_variant_id: number | null;
  product_name: string;
  variant_name: string | null;
  unit_price_sen: number;
  quantity: number;
  capacity_units_each: number;
  line_total_sen: number;
  option_values: { option_group_name: string; option_value_name: string }[];
}

export interface CartQuote {
  lines: CartQuoteLine[];
  subtotal_sen: number;
  delivery_fee_sen: number;
  total_sen: number;
  total_capacity_units: number;
  preorder_date: {
    order_date: string;
    remaining_capacity: number;
    cutoff_at: string;
  };
}

export interface DeliveryAddressInput {
  recipient_name: string;
  recipient_phone_e164: string;
  line_1: string;
  line_2?: string;
  city: string;
  state: string;
  postcode: string;
}

export interface CheckoutItemInput {
  product_variant_id: number;
  quantity: number;
}

export interface CheckoutQuoteInput {
  items: CheckoutItemInput[];
  preorder_date: string;
  fulfilment_method: "pickup" | "delivery";
  delivery_zone_id?: number | null;
}

export interface CreateOrderInput extends CheckoutQuoteInput {
  checkout_channel: "website" | "whatsapp";
  delivery_address?: DeliveryAddressInput | null;
  idempotency_key: string;
  customer: {
    name: string;
    phone_e164: string;
    email?: string | null;
  };
}

export interface Order {
  id: string;
  order_number: string;
  status: string;
  payment_status: string;
  checkout_channel: string;
  fulfilment_method: string;
  subtotal_sen: number;
  delivery_fee_sen: number;
  total_sen: number;
  expires_at: string | null;
  whatsapp_url: string | null;
  status_url: string;
  payment_proof_url: string;
}

export interface OrderStatus {
  order_number: string;
  status: string;
  payment_status: string;
  total_sen: number;
  awaiting_proof: boolean;
  updated_at: string;
}
