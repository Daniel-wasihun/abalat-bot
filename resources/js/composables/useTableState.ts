import { ref } from "vue";
import { useToastStore } from "@/stores/toast";

/**
 * ENHANCED TABLE STATE COMPOSABLE
 * Standardizes common UI states and actions for management views.
 */
/**
 * ENHANCED TABLE STATE COMPOSABLE
 * Standardizes common UI states and actions for management views.
 */
export function useTableState(config?: {
    store?: any;
    deleteAction?: (id: any) => Promise<any>;
    previewAction?: (id: any) => Promise<string>;
    refreshAction?: (page?: number, force?: boolean, silent?: boolean) => Promise<any> | void;
    onDeleteSuccess?: () => void;
}) {
    const toast = useToastStore();

    // ─── Modal Flags ──────────────────────────────────────────────────────────
    const showModal = ref(false);
    const showViewModal = ref(false);
    const showDeleteConfirm = ref(false);
    const showFilters = ref(false);

    // ─── Data State ────────────────────────────────────────────────────────────
    const selectedItem = ref<any>(null);
    const selectedIds = ref<number[]>([]);
    const deleting = ref(false);
    const deleteError = ref("");

    // ─── Methods ───────────────────────────────────────────────────────────────
    const isPreviewing = ref(false);
    const previewUrl = ref("");

    const openCreate = () => {
        selectedItem.value = null;
        deleteError.value = "";
        showModal.value = true;
    };

    const openEdit = (item: any) => {
        selectedItem.value = item;
        deleteError.value = "";
        showModal.value = true;
    };

    const openView = (item: any) => {
        selectedItem.value = item;
        showViewModal.value = true;
    };

    const openDelete = (item: any) => {
        selectedItem.value = item;
        deleteError.value = "";
        showDeleteConfirm.value = true;
    };

    const closeDelete = () => {
        showDeleteConfirm.value = false;
        selectedItem.value = null;
        deleteError.value = "";
    };

    /**
     * Standardized delete execution
     */
    const confirmDelete = async (
        deleteFn?: (id: number) => Promise<any>,
        onSuccess?: () => void,
        skipRefresh = false,
    ) => {
        if (!selectedItem.value) return;

        const finalDeleteFn = deleteFn || config?.deleteAction;
        if (!finalDeleteFn) {
            console.error("No delete action provided to useTableState");
            return;
        }

        deleting.value = true;
        deleteError.value = "";
        try {
            const response = await finalDeleteFn(
                selectedItem.value.slug || selectedItem.value.id,
            );

            // Show success feedback
            const successMsg = response?.message || response?.data?.message;
            if (successMsg) {
                toast.success(successMsg);
            }

            const finalOnSuccess =
                onSuccess ||
                config?.onDeleteSuccess ||
                (() => {
                    if (config?.refreshAction && !skipRefresh) {
                        // For stores that have a pagination state (like userStore), 
                        // we can pass the current page, force = true, silent = true
                        const storePage = config.store?.pagination?.currentPage || config.store?.pagination?.current_page || 1;
                        config.refreshAction(storePage, true, true);
                    }
                });

            finalOnSuccess();
            closeDelete();
        } catch (error: any) {
            deleteError.value =
                error.response?.data?.message || "Action failed";
            toast.error(deleteError.value);
        } finally {
            deleting.value = false;
        }
    };

    /**
     * Standardized sort handler
     */
    const handleSort = (
        key: string | any,
        possibleKey?: string,
        possibleCallback?: () => void,
    ) => {
        // Support both (key, callback) and (filters, key, callback)
        let filters = config?.store?.filters;
        let finalKey = key;
        let callback =
            possibleCallback ||
            (() => {
                if (config?.refreshAction) config.refreshAction(1);
            });

        if (typeof key === "object") {
            filters = key;
            finalKey = possibleKey;
            callback = possibleCallback!;
        }

        if (!filters || !finalKey) return;

        if (filters.sort_by === finalKey) {
            filters.sort_order = filters.sort_order === "asc" ? "desc" : "asc";
        } else {
            filters.sort_by = finalKey;
            filters.sort_order = "asc";
        }

        if (callback) callback();
    };

    /**
     * Standardized preview handling
     */
    const handlePreview = async (
        fetchUrlFn?: (id?: any) => Promise<string>,
    ) => {
        const finalFetchFn = fetchUrlFn || config?.previewAction;
        if (!finalFetchFn) {
            console.error("No preview action provided");
            return;
        }

        try {
            const url = await finalFetchFn(
                selectedItem.value?.id || selectedItem.value?.slug,
            );
            previewUrl.value = url;
            isPreviewing.value = true;
            showViewModal.value = false;
        } catch (error: any) {
            toast.error(error?.message || "Preview failed");
        }
    };

    const closePreview = () => {
        if (previewUrl.value && previewUrl.value.startsWith("blob:")) {
            window.URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = "";
        isPreviewing.value = false;
    };

    return {
        // Flags
        showModal,
        showViewModal,
        showDeleteConfirm,
        showFilters,
        isPreviewing,

        // Selection / State
        selectedItem,
        selectedIds,
        deleting,
        deleteError,
        previewUrl,

        // Core Actions
        openCreate,
        openEdit,
        openView,
        openDelete,
        closeDelete,
        confirmDelete,
        handleSort,
        handlePreview,
        closePreview,
    };
}
