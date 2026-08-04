export interface Role {
    id: number;
    name: string | Record<string, string>;
    slug: string;
    hierarchy_level?: number;
}

export interface User {
    id: number;
    name: string | Record<string, string>;
    email: string;
    is_active: boolean;
    user_type?: string;
    role?: Role;
    permissions?: any[];
    profile_picture?: string;
    avatar?: string;
    hierarchy_level?: number;
    created_at: string;
    updated_at: string;
}

export interface Pagination {
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
}

export interface UserFilters {
    search: string;
    role: string;
    status: string;
    user_type: string;
    sort_by: string;
    sort_order: "asc" | "desc";
}
