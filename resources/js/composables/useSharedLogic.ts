/**
 * useSharedLogic
 *
 * Provides common utility helpers shared across UserManagement composables:
 *   - `handleApiError()`       — handles API error responses, calling toast on failure
 *   - `handleImageFileChange()`— reads an image File input and returns file + preview URL
 *   - `toggleItemInList()`     — toggles an item in a reactive array (add/remove)
 */
import { useToastStore } from "@/stores/toast";

export function useSharedLogic() {
    const toast = useToastStore();

    /**
     * Unified API error handler.
     * Shows field-level errors for 422, otherwise shows the server message / fallback.
     */
    const handleApiError = (
        error: any,
        fallbackMessage: string = "An unexpected error occurred.",
    ) => {
        const status = error?.response?.status;
        const data = error?.response?.data;

        if (status === 422 && data?.errors) {
            // Return the error map so callers can surface field-level feedback
            return data.errors as Record<string, string[]>;
        }

        const message = data?.message || fallbackMessage;
        toast.error(message);
        return null;
    };

    /**
     * Reads an image file from a DOM input event.
     * Returns { file: File | null, preview: string | null }.
     */
    const handleImageFileChange = (
        event: Event,
    ): { file: File | null; preview: string | null } => {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        if (!file) return { file: null, preview: null };

        const preview = URL.createObjectURL(file);
        return { file, preview };
    };

    /**
     * Toggles a value in a reactive array.
     * If the value exists it is removed; otherwise it is appended.
     */
    const toggleItemInList = (list: any[], item: any): void => {
        const index = list.indexOf(item);
        if (index === -1) {
            list.push(item);
        } else {
            list.splice(index, 1);
        }
    };

    return {
        handleApiError,
        handleImageFileChange,
        toggleItemInList,
    };
}
