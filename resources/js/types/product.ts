export interface Product {
    id: number;
    name: string;
    sku: string;
    slug: string;
    price: number;
    thumbnail_url: string;
    category_name: string | null;
    stock_qty: number;
    discounted_price: number | null;
    discount_pct: number | null;
}