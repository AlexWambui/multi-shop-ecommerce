export interface Shop {
    id: number;
    name: string;
    slug: string;
    category_name: string;
    rating: number;
    reviews_count: number;
    logo_url: string;
    cover_url?: string;
    status: string;
    status_class: string;
    is_active: boolean;
    is_verified: boolean;
}