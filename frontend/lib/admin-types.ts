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

export interface AdminOrderTimeSlot {
  label: string;
  starts_at: string;
  ends_at: string;
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
  rejection_message: string | null;
  refund_required: boolean;
  refund_note: string | null;
  notes: string | null;
  card_message: string | null;
  allergies_note: string | null;
  hide_price_on_package: boolean;
  time_slot: AdminOrderTimeSlot | null;
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
  images: { id: number; url: string | null; alt_text: string | null; is_primary: boolean }[];
}

export interface AdminCategory {
  id: number;
  name: string;
  slug: string;
  sort_order: number;
}

export interface AdminPreorderDate {
  id: number;
  order_date: string;
  cutoff_at: string;
  capacity_limit: number;
  reserved_capacity: number;
  pickup_enabled: boolean;
  delivery_enabled: boolean;
  status: "open" | "closed" | "full";
  note_internal: string | null;
}

export interface AdminDashboard {
  todays_order_count: number;
  pending_payment_review_count: number;
  revenue_sen: {
    today: number;
    this_week: number;
    all_time: number;
  };
  upcoming_preorder_dates: {
    order_date: string;
    capacity_used: number;
    capacity_limit: number;
    status: string;
  }[];
  orders_needing_attention: number;
}

export interface AdminTimeSlot {
  id: number;
  label: string;
  starts_at: string;
  ends_at: string;
  fulfilment_method: "pickup" | "delivery" | "both";
  capacity_limit: number;
  remaining_capacity: number;
  is_active: boolean;
}
