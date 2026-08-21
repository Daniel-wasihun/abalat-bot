import { defineStore } from "pinia";
import { ref, reactive, watch, computed } from "vue";
import apiClient from "@/api/apiClient";
import type { User, Pagination, UserFilters } from "@/types/user";
import { useLanguageStore } from "@/stores/languageStore";
import { useToastStore } from "@/stores/toast";
import echo, { isConnected } from "@/api/echo";
import { localize } from "@/utils/format";


export const useUserStore = defineStore(
    "user",
    () => {
        // State
        const users = ref<User[]>([]);
        const loading = ref(false);
        const roles = ref<any[]>([]);
        const allRoles = ref<any[]>([]);
        const userTypes = ref<Record<string, string>>({});
        const allPermissions = ref<any[]>([]);
        // --- Import Monitoring ---
        const activeImports = ref<Record<number, any>>({}); // Track background imports

        const pagination = ref<Pagination>({
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 10,
        });

        const showFilters = ref(
            sessionStorage.getItem("users_filter_visible") === "true",
        );



        // Cache
        const userCache = reactive<
            Record<string, { data: User[]; meta: Pagination }>
        >({});

        const metadataCache = reactive<
            Record<
                string,
                {
                    roles: any[];
                    userTypes: Record<string, string>;
                    allRoles: any[];
                    allPermissions: any[];
                }
            >
        >({});

        // Actions
        const toggleFilters = () => {
            showFilters.value = !showFilters.value;
            sessionStorage.setItem(
                "users_filter_visible",
                showFilters.value.toString(),
            );
        };

        const languageStore = useLanguageStore();

        let metadataPromise: Promise<void> | null = null;

        const fetchMetadata = async (force = false) => {
            const currentLang = languageStore.currentLanguage;

            // 1. If data exists in cache and we are not forcing, restore and return
            if (!force && metadataCache[currentLang]) {
                const cached = metadataCache[currentLang];
                roles.value = cached.roles;
                userTypes.value = cached.userTypes;
                allRoles.value = cached.allRoles;
                allPermissions.value = cached.allPermissions;
                return;
            }

            // 2. If a fetch is already in progress, join it
            if (metadataPromise) {
                return metadataPromise;
            }

            // 3. Start a new fetch sequence
            metadataPromise = (async () => {
                try {
                    const promises = [];

                    if (
                        force ||
                        roles.value.length === 0 ||
                        !metadataCache[currentLang]?.roles
                    ) {
                        promises.push(
                            apiClient
                                .get("/system/roles?per_page=100")
                                .then((res) => {
                                    if (res && res.data) {
                                        roles.value = res.data.data || [];
                                        allRoles.value = res.data.data || [];
                                    }
                                })
                                .catch((e) =>
                                    console.error("Roles fetch failed", e),
                                ),
                        );
                    }



                    if (
                        force ||
                        Object.keys(userTypes.value).length === 0 ||
                        !metadataCache[currentLang]?.userTypes
                    ) {
                        promises.push(
                            apiClient
                                .get("/system/users/options")
                                .then((res) => {
                                    if (res && res.data) {
                                        userTypes.value =
                                            res.data.user_types || {};
                                    }
                                })
                                .catch((e) =>
                                    console.error("Options fetch failed", e),
                                ),
                        );
                    }

                    if (
                        force ||
                        allPermissions.value.length === 0 ||
                        !metadataCache[currentLang]?.allPermissions
                    ) {
                        promises.push(
                            apiClient
                                .get("/system/permissions?per_page=1000")
                                .then((res) => {
                                    if (res && res.data) {
                                        allPermissions.value =
                                            res.data.data || res.data || [];
                                    }
                                })
                                .catch((e) =>
                                    console.error(
                                        "Permissions fetch failed",
                                        e,
                                    ),
                                ),
                        );
                    }



                    await Promise.all(promises);

                    // 4. Update the metadata cache
                    metadataCache[currentLang] = {
                        roles: roles.value,
                        userTypes: userTypes.value,
                        allRoles: allRoles.value,
                        allPermissions: allPermissions.value,
                    };
                } catch (error) {
                    console.error("Metadata fetch process error", error);
                } finally {
                    metadataPromise = null;
                }
            })();

            return metadataPromise;
        };

        const filters = reactive<UserFilters>({
            search: "",
            role: "",
            status: "",
            user_type: "",
            sort_by: "",
            sort_order: "desc",
        });

        const fetchUsers = async (page = 1, force = false, silent = false) => {
            if (loading.value && !silent) return;

            const currentLang = languageStore.currentLanguage;
            const params: any = {
                page,
                search: filters.search,
                role: filters.role,
                status: filters.status,
                user_type: filters.user_type,
                per_page: pagination.value.perPage,
            };

            // Enhanced Backend sorting mapping for 4 core attributes
            if (filters.sort_by === "created_at") {
                params.sort =
                    filters.sort_order === "desc" ? "newest" : "oldest";
            } else if (filters.sort_by === "name") {
                params.sort = `name_${filters.sort_order}`;
            } else if (filters.sort_by === "role") {
                params.sort = `role_${filters.sort_order}`;
            } else if (filters.sort_by === "user_type") {
                params.sort = `type_${filters.sort_order}`;
            } else {
                // Default fallback if no specific mapping
                params.sort_by = filters.sort_by;
                params.sort_order = filters.sort_order;
            }

            const cacheKey = `${currentLang}-${JSON.stringify(params)}`;

            if (!force && userCache[cacheKey]) {
                users.value = userCache[cacheKey].data;
                pagination.value = userCache[cacheKey].meta;
                return userCache[cacheKey];
            }

            if (!silent) loading.value = true;
            try {
                const response = await apiClient.get("/system/users", {
                    params,
                });
                const data = response.data;
                
                // Handle empty page fallback (if we deleted the last item on a page)
                if (data.data && data.data.length === 0 && page > 1) {
                    return fetchUsers(page - 1, true, silent);
                }

                users.value = data.data;

                if (data.meta) {
                    const meta = {
                        currentPage: data.meta.current_page,
                        lastPage: data.meta.last_page,
                        total: data.meta.total,
                        perPage: data.meta.per_page,
                    };
                    pagination.value = meta;
                    userCache[cacheKey] = {
                        data: data.data,
                        meta: meta,
                    };
                }
                return data;
            } catch (error) {
                console.error("Failed to fetch users", error);
            } finally {
                if (!silent) loading.value = false;
            }
        };

        const clearCache = () => {
            for (const key in userCache) delete userCache[key];
            for (const key in metadataCache) delete metadataCache[key];
        };

        const searchUsers = async (
            query: string,
            additionalParams: any = {},
        ) => {
            try {
                const response = await apiClient.get("/system/users", {
                    params: {
                        search: query,
                        per_page: 5,
                        ...additionalParams,
                    },
                });
                return response.data.data;
            } catch (error) {
                console.error("Search users failed", error);
                return [];
            }
        };

        const setFilter = (key: keyof UserFilters, value: any) => {
            filters[key] = value;
        };

        const handleSort = (key: string) => {
            if (filters.sort_by === key) {
                filters.sort_order = filters.sort_order === "asc" ? "desc" : "asc";
            } else {
                filters.sort_by = key;
                filters.sort_order = "desc";
            }
            fetchUsers(1, true);
        };

        const resetFilters = () => {
            const isDefault =
                filters.sort_by === "" &&
                filters.sort_order === "desc";

            filters.search = "";
            filters.role = "";
            filters.status = "";
            filters.user_type = "";
            filters.sort_by = "";
            filters.sort_order = "desc";

            if (
                isDefault &&
                pagination.value.currentPage === 1 &&
                users.value.length > 0
            ) {
                return;
            }

            fetchUsers(1, true);
        };

        const fetchUserById = async (id: number) => {
            try {
                const response = await apiClient.get(`/system/users/${id}`);
                const updatedUser = response.data.user;
                if (updatedUser) {
                    const index = users.value.findIndex((u) => u.id === id);
                    if (index !== -1) users.value[index] = updatedUser;
                    return updatedUser;
                }
            } catch (error) {
                console.error("Failed to fetch user", error);
            }
            return null;
        };

        const deleteUser = async (id: number): Promise<any> => {
            try {
                const response = await apiClient.delete(`/system/users/${id}`);
                // Optimistically remove from local state instead of refetching
                const index = users.value.findIndex((u) => u.id === id);
                if (index !== -1) {
                    users.value.splice(index, 1);
                    pagination.value.total--;
                }
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const toggleUserStatus = async (id: number): Promise<any> => {
            try {
                const response = await apiClient.patch(
                    `/system/users/${id}/toggle-status`,
                );
                await fetchUserById(id);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const bulkAction = async (
            ids: number[],
            action: "activate" | "deactivate" | "delete",
        ): Promise<any> => {
            try {
                const response = await apiClient.post(
                    "/system/users/bulk-action",
                    {
                        ids,
                        action,
                    },
                );
                // Optimistically update local state instead of refetching
                if (action === "delete") {
                    users.value = users.value.filter(
                        (u) => !ids.includes(u.id),
                    );
                    pagination.value.total -= ids.length;
                } else {
                    const isActive = action === "activate";
                    users.value.forEach((user) => {
                        if (ids.includes(user.id)) {
                            user.is_active = isActive;
                        }
                    });
                }
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const updateUser = async (id: number, data: any) => {
            try {
                let response;
                if (data instanceof FormData) {
                    response = await apiClient.post(
                        `/system/users/${id}`,
                        data,
                        {
                            headers: { "X-Is-FormData": "true" },
                        },
                    );
                } else {
                    response = await apiClient.put(`/system/users/${id}`, data);
                }

                await fetchUserById(id);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const assignRole = async (
            userId: number,
            roles: string[],
            startDate?: string,
            endDate?: string,
        ) => {
            try {
                const response = await apiClient.post(
                    `/system/users/${userId}/assign-role`,
                    {
                        roles,
                        start_date: startDate,
                        end_date: endDate,
                    },
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const syncPermissions = async (
            userId: number,
            permissions: Record<string, boolean>,
            startDate?: string,
            endDate?: string,
        ) => {
            try {
                const response = await apiClient.post(
                    `/system/users/${userId}/sync-permissions`,
                    {
                        permissions,
                        start_date: startDate,
                        end_date: endDate,
                    },
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const resetPermissionsToRoleDefault = async (userId: number) => {
            try {
                const response = await apiClient.post(
                    `/system/users/${userId}/reset-permissions`,
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const cancelScheduledRole = async (
            userId: number,
            assignmentId: number,
        ) => {
            try {
                const response = await apiClient.delete(
                    `/system/users/${userId}/cancel-scheduled-role/${assignmentId}`,
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const cancelScheduledPermission = async (
            userId: number,
            overrideId: number,
        ) => {
            try {
                const response = await apiClient.delete(
                    `/system/users/${userId}/cancel-scheduled-permission/${overrideId}`,
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const updateScheduledRole = async (
            userId: number,
            assignmentId: number,
            startDate?: string,
            endDate?: string,
        ) => {
            try {
                const response = await apiClient.patch(
                    `/system/users/${userId}/update-scheduled-role/${assignmentId}`,
                    {
                        start_date: startDate,
                        end_date: endDate,
                    },
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const updateScheduledPermission = async (
            userId: number,
            overrideId: number,
            startDate?: string,
            endDate?: string,
        ) => {
            try {
                const response = await apiClient.patch(
                    `/system/users/${userId}/update-scheduled-permission/${overrideId}`,
                    {
                        start_date: startDate,
                        end_date: endDate,
                    },
                );
                await fetchUserById(userId);
                clearCache();
                return response.data;
            } catch (error) {
                console.error(error);
                throw error;
            }
        };

        const createUser = async (data: any) => {
            try {
                let response;
                if (data instanceof FormData) {
                    response = await apiClient.post("/system/users", data, {
                        headers: { "X-Is-FormData": "true" },
                    });
                } else {
                    response = await apiClient.post("/system/users", data);
                }
                // Optimistically add to local state instead of refetching
                if (response.data.user) {
                    users.value.unshift(response.data.user);
                    pagination.value.total++;
                }
                clearCache();
                return response.data;
            } catch (error) {
                console.error("Create user failed", error);
                throw error;
            }
        };

        const importUsers = async (
            file: File,
            role?: string,
        ) => {
            try {
                const formData = new FormData();
                formData.append("file", file);
                if (role) formData.append("role", role);

                const response = await apiClient.post(
                    "/system/users/import",
                    formData,
                    {
                        timeout: 600000, // 10 minutes for slow uploads
                    },
                );

                // removed fetchUsers(1) call from here to prevent blocking
                clearCache();
                return response.data;
            } catch (error) {
                console.error("Import failed", error);
                throw error;
            }
        };

        const getImportStatus = async (id: number) => {
            try {
                const response = await apiClient.get(`/imports/${id}/status`);
                return response.data;
            } catch (error) {
                console.error("Failed to check import status", error);
                throw error;
            }
        };

        // Track currently watched and finished IDs to prevent duplicates
        const watchedIds = new Set<number>();
        const finishedImportIds = new Set<number>();
        let globalImportPoller: any = null;

        const watchImport = async (id: number) => {
            if (watchedIds.has(id) || finishedImportIds.has(id)) return;
            watchedIds.add(id);

            // Initialize state for this import only if it doesn't already exist in the store
            if (!activeImports.value[id]) {
                activeImports.value = {
                    ...activeImports.value,
                    [id]: {
                        status: "pending",
                        id,
                        processed: 0,
                        imported: 0,
                        total: 1,
                        errors: [],
                        success_log: [],
                    },
                };
            }

            // Fetch current status immediately to handle page refreshes or late joins
            try {
                const currentData = await getImportStatus(id);
                const wasAlreadyFinished =
                    activeImports.value[id]?.status === "completed" ||
                    activeImports.value[id]?.status === "failed";

                activeImports.value = {
                    ...activeImports.value,
                    [id]: {
                        ...activeImports.value[id],
                        ...currentData,
                    },
                };
                if (
                    currentData.status === "completed" ||
                    currentData.status === "failed"
                ) {
                    handleImportFinished(id, currentData, !wasAlreadyFinished);
                    return;
                }
            } catch (err) {
                console.error("Initial status fetch failed", err);
            }

            // Setup real-time listeners via Echo
            echo.channel(`import.${id}`).listen(
                ".progress.updated",
                async (e: any) => {
                    const statusData = e.data;
                    const lastStatus = activeImports.value[id]?.status;

                    // Merge real-time counters
                    activeImports.value = {
                        ...activeImports.value,
                        [id]: {
                            ...activeImports.value[id],
                            ...statusData,
                        },
                    };

                    // Only fetch full report (with logs) once when finished
                    if (
                        (statusData.status === "completed" ||
                            statusData.status === "failed") &&
                        lastStatus !== statusData.status
                    ) {
                        try {
                            const fullData = await getImportStatus(id);
                            activeImports.value = {
                                ...activeImports.value,
                                [id]: {
                                    ...activeImports.value[id],
                                    ...fullData,
                                },
                            };
                            handleImportFinished(id, fullData);
                        } catch (err) {
                            handleImportFinished(id, statusData);
                        }
                        echo.leave(`import.${id}`);
                        watchedIds.delete(id);
                    }
                },
            );
        };

        // Handle reconnection sync
        watch(isConnected, (connected) => {
            if (connected && watchedIds.size > 0) {
                watchedIds.forEach((id) => {
                    getImportStatus(id)
                        .then((data) => {
                            activeImports.value[id] = {
                                ...activeImports.value[id],
                                ...data,
                            };
                            if (
                                data.status === "completed" ||
                                data.status === "failed"
                            ) {
                                handleImportFinished(id, data);
                            }
                        })
                        .catch((e) =>
                            console.error("Sync failed on reconnect", e),
                        );
                });
            }
        });

        const handleImportFinished = (
            id: number,
            statusData: any,
            showToast: boolean = true,
        ) => {
            if (finishedImportIds.has(id)) return;
            finishedImportIds.add(id);
            watchedIds.delete(id);

            const lang = useLanguageStore();
            const toast = useToastStore();

            if (statusData.status === "completed") {
                if (showToast) {
                    if (statusData.imported > 0) {
                        toast.success(
                            lang.translate("import_finalized", {
                                count: statusData.imported,
                                item: lang.translate("nav.users"),
                            }),
                        );
                    } else {
                        toast.warning(lang.translate("import_no_new"));
                    }
                }
            } else if (showToast) {
                // Only show error toast if it's a genuine failure, not just a partial success with some row errors
                toast.error(lang.translate("import_interrupted"));
            }

            fetchUsers(1, true);
        };

        const clearImportStatus = (id: number) => {
            const newImports = { ...activeImports.value };
            delete newImports[id];
            activeImports.value = newImports;
            watchedIds.delete(id);
        };

        const clearAllImports = () => {
            // Leave all active channels to prevent memory leaks and redundant updates
            watchedIds.forEach((id) => {
                echo.leave(`import.${id}`);
            });

            activeImports.value = {};
            watchedIds.clear();
            finishedImportIds.clear();
        };

        const downloadTemplate = async () => {
            try {
                const response = await apiClient.get("/system/users/template", {
                    responseType: "blob",
                });
                const url = window.URL.createObjectURL(
                    new Blob([response.data]),
                );
                const link = document.createElement("a");
                link.href = url;
                link.setAttribute("download", "users_import_template.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                console.error("Template download failed", error);
                throw error;
            }
        };

        return {
            users,
            loading,
            roles,
            allRoles,
            userTypes,
            allPermissions,
            pagination,
            filters,
            showFilters,
            fetchMetadata,
            fetchUsers,
            fetchUserById,
            setFilter,
            resetFilters,
            toggleFilters,
            deleteUser,
            toggleUserStatus,
            bulkAction,
            updateUser,
            assignRole,
            syncPermissions,
            resetPermissionsToRoleDefault,
            cancelScheduledRole,
            cancelScheduledPermission,
            updateScheduledRole,
            updateScheduledPermission,
            createUser,
            importUsers,
            downloadTemplate,
            getImportStatus,
            watchImport,
            clearImportStatus,
            clearAllImports,
            searchUsers,
            activeImports,
            clearCache,
            handleSort,

        };
    },
    {
        persist: {
            paths: ["activeImports"],
        },
    },
);
