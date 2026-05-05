export interface Shop {
    id: number;
    name: string;
    slug: string;
    description: string;
    rating: number;
    reviews_count: number;
    logo_url: string;
    cover_url?: string;
    status: string;
    status_class: string;
    is_active: boolean;
    is_verified: boolean;
    category_name: string;
    owner_name: string;
    owner_joined_at: string;
}