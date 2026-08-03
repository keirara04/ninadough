export interface AdminOrderItem {
  product_name: string;
  variant_name: string | null;
  quantity: number;
  line_total_sen: number;
}

export interface AdminPaymentProof {
  id: number;
  url: string;
  uploaded_at: string;
  reviewed_at: string | null;
}

export interface AdminPayment {
  id: number;
  status: string;
  amount_sen: number;
  proofs: AdminPaymentProof[];
}

export interface AdminOrder {
  id: number;
  order_number: string;
  status: string;
  payment_status: string;
  checkout_channel: string;
  fulfilment_method: string;
  customer_name: string;
  customer_phone: string;
  total_sen: number;
  created_at: string;
  items?: AdminOrderItem[];
  payments?: AdminPayment[];
}

export interface AdminProduct {
  id: string;
  name: string;
  slug: string;
  short_description: string | null;
  description: string | null;
  base_price_sen: number;
  allergen_information: string | null;
  is_active: boolean;
  is_featured: boolean;
  category: { id: number; name: string; slug: string } | null;
}

export interface AdminCategory {
  id: number;
  name: string;
  slug: string;
  sort_order: number;
}
