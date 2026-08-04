import { ref, type Ref } from "vue";
import { useToastStore } from "@/stores/toast";

/**
 * BULK ACTIONS COMPOSABLE
 * Standardizes bulk selection, status toggling, and deletion logic.
 */
export function useBulkActions(
    configOrStore: any,
    selectedIdsRef?: Ref<number[]>,
    refreshFn?: (page?: number) => Promise<void> | void,
) {
    const toast = useToastStore();

    // Use config object if provided, otherwise fallback to positional arguments
    const isConfig =
        configOrStore &&
        typeof configOrStore === "object" &&
        ("store" in configOrStore ||
            "itemStore" in configOrStore ||
            "selectedIds" in configOrStore);

    const store = isConfig
        ? configOrStore.store || configOrStore.itemStore
        : configOrStore;

    const selectedIds = isConfig
        ? configOrStore.selectedIds || ref([])
        : selectedIdsRef || ref([]);

    const refresh = isConfig ? configOrStore.onSuccess : refreshFn;

    const isBulkProcessing = ref(false);
    const showBulkModal = ref(false);
    const pendingBulkAction = ref<"activate" | "deactivate" | "delete" | null>(
        null,
    );

    const openBulkConfirm = (action: "activate" | "deactivate" | "delete") => {
        if (selectedIds.value.length === 0) return;
        pendingBulkAction.value = action;
        showBulkModal.value = true;
    };

    const handleBulkAction = async (
        bulkDeleteFn: (ids: number[]) => Promise<any>,
        bulkToggleFn?: (ids: number[], active: boolean) => Promise<any>,
        customIds?: number[],
    ) => {
        if (
            !pendingBulkAction.value ||
            (customIds || selectedIds.value).length === 0
        )
            return;
        const ids = customIds || selectedIds.value;

        isBulkProcessing.value = true;
        try {
            let response;
            if (pendingBulkAction.value === "delete") {
                response = await bulkDeleteFn(ids);
                if (refresh && !configOrStore.skipRefresh)
                    await refresh("delete");
            } else if (bulkToggleFn) {
                const active = pendingBulkAction.value === "activate";
                response = await bulkToggleFn(ids, active);
                if (refresh && !configOrStore.skipRefresh)
                    await refresh("status");
            }

            selectedIds.value = [];
            showBulkModal.value = false;
        } catch (error: any) {
            console.error("Bulk action failed", error);
            toast.error(
                error.response?.data?.message || "Bulk operation failed",
            );
        } finally {
            isBulkProcessing.value = false;
        }
    };

    return {
        selectedIds,
        showBulkModal,
        pendingBulkAction,
        isBulkProcessing,
        openBulkConfirm,
        handleBulkAction,
    };
}
