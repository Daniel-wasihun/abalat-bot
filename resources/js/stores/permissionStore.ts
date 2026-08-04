import { defineStore } from "pinia";
import { ref, reactive } from "vue";
import apiClient from "@/api/apiClient";
import { useLanguageStore } from "@/stores/languageStore";

export interface Permission {
    id: number;
    name: string | Record<string, string>;
    slug: string;
    description: string | Record<string, string>;
    module: string;
    action: string;
    is_system_level: boolean;
    is_active: boolean;
}

export interface PermissionFilters {
    search: string;
    module: string;
    action: string;
}

export const usePermissionStore = defineStore("permission", () => {
    // State
    const permissions = ref<Permission[]>([]);
    const loading = ref(false);
    const options = ref({
        modules: {} as Record<string, string>,
        actions: {} as Record<string, string>,
    });
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 50,
    });

    const filters = reactive<PermissionFilters>({
        search: "",
        module: "",
        action: "",
    });

    // Cache
    const permissionCache = reactive<
        Record<string, { data: Permission[]; meta: any }>
    >({});
    const optionsCache = reactive<Record<string, any>>({});

    // Import language store
    const languageStore = useLanguageStore();

    // Actions
    const fetchPermissions = async (page = 1, force = false) => {
        if (loading.value) return;

        try {
            const currentLang = languageStore.currentLanguage;
            const params = {
                page,
                search: filters.search,
                module: filters.module,
                action: filters.action,
                per_page: pagination.value.perPage,
            };

            // Create a unique cache key based on language and params
            const cacheKey = `${currentLang}-${JSON.stringify(params)}`;

            // Check cache
            if (!force && permissionCache[cacheKey]) {
                permissions.value = permissionCache[cacheKey].data;
                pagination.value = permissionCache[cacheKey].meta;
                return;
            }

            loading.value = true;
            const response = await apiClient.get("/system/permissions", {
                params,
            });
            const data = response.data;
            permissions.value = data.data;

            if (data.meta) {
                const meta = {
                    currentPage: data.meta.current_page,
                    lastPage: data.meta.last_page,
                    total: data.meta.total,
                    perPage: data.meta.per_page,
                };
                pagination.value = meta;

                // Save to cache
                permissionCache[cacheKey] = {
                    data: data.data,
                    meta: meta,
                };
            }
        } catch (error) {
            console.error("Failed to fetch permissions", error);
        } finally {
            loading.value = false;
        }
    };

    const fetchPermissionById = async (id: number) => {
        try {
            const response = await apiClient.get(`/system/permissions/${id}`);
            return response.data.permission;
        } catch (error) {
            console.error("Failed to fetch permission", error);
            throw error;
        }
    };

    const fetchOptions = async () => {
        try {
            const currentLang = languageStore.currentLanguage;
            if (optionsCache[currentLang]) {
                options.value = optionsCache[currentLang];
                return;
            }

            const response = await apiClient.get("/system/permissions/options");
            options.value = response.data;
            optionsCache[currentLang] = response.data;
        } catch (error) {
            console.error("Failed to fetch permission options", error);
        }
    };

    const clearCache = () => {
        for (const key in permissionCache) delete permissionCache[key];
        // We generally don't need to clear options cache unless modules/actions change dynamically, which is rare.
    };

    const createPermission = async (data: any) => {
        try {
            const response = await apiClient.post("/system/permissions", data);
            clearCache(); // Invalidate cache
            // Optimistically add to local state instead of refetching
            if (response.data.permission) {
                permissions.value.unshift(response.data.permission);
                pagination.value.total++;
            }
            return response.data;
        } catch (error) {
            console.error("Create permission failed", error);
            throw error;
        }
    };

    const updatePermission = async (id: number, data: any) => {
        try {
            const response = await apiClient.put(
                `/system/permissions/${id}`,
                data,
            );
            clearCache(); // Invalidate cache
            // Optimistically update local state instead of refetching
            if (response.data.permission) {
                const index = permissions.value.findIndex((p) => p.id === id);
                if (index !== -1) {
                    permissions.value[index] = response.data.permission;
                }
            }
            return response.data;
        } catch (error) {
            console.error("Update permission failed", error);
            throw error;
        }
    };

    const deletePermission = async (id: number) => {
        try {
            const response = await apiClient.delete(
                `/system/permissions/${id}`,
            );
            clearCache();
            // Remove from local state instead of refetching
            const index = permissions.value.findIndex((p) => p.id === id);
            if (index !== -1) {
                permissions.value.splice(index, 1);
                pagination.value.total--;
            }
            return response.data;
        } catch (error) {
            console.error("Delete permission failed", error);
            throw error;
        }
    };

    const togglePermissionStatus = async (id: number) => {
        try {
            const response = await apiClient.patch(
                `/system/permissions/${id}/toggle`,
            );
            clearCache();
            // Update local state instead of refetching
            const permission = permissions.value.find((p) => p.id === id);
            if (permission && response.data.permission) {
                permission.is_active = response.data.permission.is_active;
            }
            return response.data;
        } catch (error) {
            console.error("Toggle permission status failed", error);
            throw error;
        }
    };

    const bulkToggleStatus = async (ids: number[], active: boolean) => {
        try {
            const response = await apiClient.patch(
                "/system/permissions/bulk-toggle",
                {
                    ids,
                    active,
                },
            );
            clearCache();
            // Update local state instead of refetching
            permissions.value.forEach((permission) => {
                if (ids.includes(permission.id)) {
                    permission.is_active = active;
                }
            });
            return response.data;
        } catch (error) {
            console.error("Bulk toggle permission status failed", error);
            throw error;
        }
    };

    const bulkDeletePermissions = async (ids: number[]) => {
        try {
            const response = await apiClient.post(
                "/system/permissions/bulk-delete",
                {
                    ids,
                },
            );
            clearCache();
            // Remove from local state instead of refetching
            permissions.value = permissions.value.filter(
                (p) => !ids.includes(p.id),
            );
            pagination.value.total -= ids.length;
            return response.data;
        } catch (error) {
            console.error("Bulk delete permissions failed", error);
            throw error;
        }
    };

    const fetchAllPermissions = async () => {
        try {
            const currentLang = languageStore.currentLanguage;
            if (permissionCache[`all-${currentLang}`]) {
                permissions.value = permissionCache[`all-${currentLang}`].data;
                return;
            }

            loading.value = true;
            const response = await apiClient.get("/system/permissions", {
                params: { per_page: 1000 },
            });
            const data = response.data;
            permissions.value = data.data;

            permissionCache[`all-${currentLang}`] = {
                data: data.data,
                meta: data.meta,
            };
        } catch (error) {
            console.error("Failed to fetch all permissions", error);
        } finally {
            loading.value = false;
        }
    };

    const resetFilters = () => {
        const isDefault =
            filters.search === "" &&
            filters.module === "" &&
            filters.action === "";

        filters.search = "";
        filters.module = "";
        filters.action = "";

        if (
            isDefault &&
            pagination.value.currentPage === 1 &&
            permissions.value.length > 0
        ) {
            return;
        }

        fetchPermissions(1, true);
    };

    return {
        permissions,
        loading,
        options,
        pagination,
        filters,
        fetchPermissions,
        fetchPermissionById,
        fetchAllPermissions,
        fetchOptions,
        createPermission,
        updatePermission,
        deletePermission,
        togglePermissionStatus,
        bulkToggleStatus,
        bulkDeletePermissions,
        resetFilters,
    };
}, {
    persist: true
});
