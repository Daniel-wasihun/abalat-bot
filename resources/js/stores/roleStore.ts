import { defineStore } from "pinia";
import { ref, reactive } from "vue";
import apiClient from "@/api/apiClient";
import { useLanguageStore } from "@/stores/languageStore";

export interface Role {
    id: number;
    name: string | Record<string, string>;
    slug: string;
    description: string | Record<string, string>;
    hierarchy_level: number;
    is_system_level: boolean;
    is_active: boolean;
    permissions?: any[];
}

export interface RoleFilters {
    search: string;
    sort_by: string;
    sort_order: "asc" | "desc";
}

export const useRoleStore = defineStore("role", () => {
    // State
    const roles = ref<Role[]>([]);
    const loading = ref(false);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 10,
    });

    const filters = reactive<RoleFilters>({
        search: "",
        sort_by: "hierarchy_level",
        sort_order: "desc",
    });

    // Cache
    const roleCache = reactive<Record<string, { data: Role[]; meta: any }>>({});

    // Import language store
    const languageStore = useLanguageStore();

    // Actions
    const fetchRoles = async (page = 1, force = false) => {
        if (loading.value) return;

        try {
            const currentLang = languageStore.currentLanguage;
            const params = {
                page,
                search: filters.search,
                sort_by: filters.sort_by,
                sort_order: filters.sort_order,
                per_page: pagination.value.perPage,
            };

            const cacheKey = `${currentLang}-${JSON.stringify(params)}`;

            if (!force && roleCache[cacheKey]) {
                roles.value = roleCache[cacheKey].data;
                pagination.value = roleCache[cacheKey].meta;
                return;
            }

            loading.value = true;
            const response = await apiClient.get("/system/roles", { params });
            const data = response.data;
            roles.value = data.data;

            if (data.meta) {
                const meta = {
                    currentPage: data.meta.current_page,
                    lastPage: data.meta.last_page,
                    total: data.meta.total,
                    perPage: data.meta.per_page,
                };
                pagination.value = meta;

                roleCache[cacheKey] = {
                    data: data.data,
                    meta: meta,
                };
            }
        } catch (error) {
            console.error("Failed to fetch roles", error);
        } finally {
            loading.value = false;
        }
    };

    const fetchRoleBySlug = async (slug: string) => {
        try {
            const response = await apiClient.get(`/system/roles/${slug}`);
            return response.data.role;
        } catch (error) {
            console.error("Failed to fetch role", error);
            throw error;
        }
    };

    const clearCache = () => {
        for (const key in roleCache) delete roleCache[key];
    };

    const createRole = async (data: any) => {
        try {
            const response = await apiClient.post("/system/roles", data);
            clearCache();
            // Optimistically add to local state instead of refetching
            if (response.data.role) {
                roles.value.unshift(response.data.role);
                pagination.value.total++;
            }
            return response.data;
        } catch (error) {
            console.error("Create role failed", error);
            throw error;
        }
    };

    const updateRole = async (slug: string, data: any) => {
        try {
            const response = await apiClient.put(`/system/roles/${slug}`, data);
            clearCache();
            // Optimistically update local state instead of refetching
            if (response.data.role) {
                const index = roles.value.findIndex((r) => r.slug === slug);
                if (index !== -1) {
                    roles.value[index] = response.data.role;
                }
            }
            return response.data;
        } catch (error) {
            console.error("Update role failed", error);
            throw error;
        }
    };

    const deleteRole = async (slug: string, confirm = false) => {
        try {
            const response = await apiClient.delete(`/system/roles/${slug}`, {
                params: { confirm: confirm ? "true" : "false" },
            });
            clearCache();
            // Remove from local state instead of refetching
            const index = roles.value.findIndex((r) => r.slug === slug);
            if (index !== -1) {
                roles.value.splice(index, 1);
                pagination.value.total--;
            }
            return response.data;
        } catch (error) {
            console.error("Delete role failed", error);
            throw error;
        }
    };

    const toggleRoleStatus = async (slug: string) => {
        try {
            const response = await apiClient.patch(
                `/system/roles/${slug}/toggle`,
            );
            clearCache();
            // Update local state instead of refetching
            const role = roles.value.find((r) => r.slug === slug);
            if (role && response.data.role) {
                role.is_active = response.data.role.is_active;
            }
            return response.data;
        } catch (error) {
            console.error("Toggle role status failed", error);
            throw error;
        }
    };

    const bulkToggleStatus = async (ids: number[], active: boolean) => {
        try {
            const response = await apiClient.patch(
                "/system/roles/bulk-toggle",
                {
                    ids,
                    active,
                },
            );
            clearCache();
            // Update local state instead of refetching
            roles.value.forEach((role) => {
                if (ids.includes(role.id)) {
                    role.is_active = active;
                }
            });
            return response.data;
        } catch (error) {
            console.error("Bulk toggle role status failed", error);
            throw error;
        }
    };

    const bulkDeleteRoles = async (ids: number[]) => {
        try {
            const response = await apiClient.post("/system/roles/bulk-delete", {
                ids,
            });
            clearCache();
            // Remove from local state instead of refetching
            roles.value = roles.value.filter((r) => !ids.includes(r.id));
            pagination.value.total -= ids.length;
            return response.data;
        } catch (error) {
            console.error("Bulk delete roles failed", error);
            throw error;
        }
    };

    const resetFilters = () => {
        const isDefault = filters.search === "";

        filters.search = "";

        if (
            isDefault &&
            pagination.value.currentPage === 1 &&
            roles.value.length > 0
        ) {
            return;
        }

        fetchRoles(1, true);
    };

    return {
        roles,
        loading,
        pagination,
        filters,
        fetchRoles,
        fetchRoleBySlug,
        createRole,
        updateRole,
        deleteRole,
        toggleRoleStatus,
        bulkToggleStatus,
        bulkDeleteRoles,
        resetFilters,
    };
}, {
    persist: true
});
