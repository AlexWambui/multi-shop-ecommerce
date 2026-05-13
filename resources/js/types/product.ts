export interface Product {
    id: number;
    name: string;
    sku: string;
    slug: string;
    cost_price: number;
    price: number;
    thumbnail_url: string;
    category_name: string | null;
    shop_name: string;
    has_discount: boolean;
    discount_display: {
        saved_amount: number,
        value: number,
        type: string
    };
    discounted_price: number | null;
    discount_pct: number | null;
    discount_starts_at: string | null;
    discount_expires_at: string | null;
    current_stock: number;
    low_stock_threshold: number;
    track_inventory: boolean;
    stock_status: string;
    stock_badge_class: string;
}