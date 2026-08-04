/**
 * useModalForm
 *
 * Provides the shared error-management utilities used by every LMS modal:
 *   - `errors`           — reactive flat error map { fieldKey: message }
 *   - `clearErrors()`    — wipe all errors
 *   - `setError()`       — set a single field error
 *   - `clearFieldError()`— clear a single field error (call from @input handlers)
 *   - `mapApiErrors()`   — normalise Laravel 422 dotted-key errors into the flat
 *                          map, e.g. "title.en" → "title_en"
 *   - `handleApiError()` — full error-response handler; calls toast.error()
 *
 * Usage:
 *   const { errors, clearErrors, clearFieldError, mapApiErrors, handleApiError } =
 *     useModalForm()
 */
import { reactive } from "vue";
import { useToastStore } from "@/stores/toast";

/** Map of dotted Laravel keys → flat UI error keys */
const DOT_KEY_MAP: Record<string, string> = {
    "title.en": "title_en",
    "title.am": "title_am",
    "authors.en": "authors_en",
    "authors.am": "authors_am",
    "edition.en": "edition_en",
    "edition.am": "edition_am",
    "description.en": "description_en",
    "description.am": "description_am",
    "name.en": "name_en",
    "name.am": "name_am",
};

export function useModalForm() {
    const toastStore = useToastStore();
    const errors = reactive<Record<string, string>>({});

    const clearErrors = () => {
        Object.keys(errors).forEach((k) => delete errors[k]);
    };

    const setError = (field: string, message: string) => {
        errors[field] = message;
    };

    const clearFieldError = (field: string) => {
        if (errors[field]) delete errors[field];
    };

    /**
     * Normalise Laravel 422 { "title.en": ["msg", ...], ... } into the flat
     * error map.  Keys in DOT_KEY_MAP are translated; everything else is kept
     * as-is (e.g. "course_slug", "file").
     */
    const mapApiErrors = (apiErrors: Record<string, string | string[]>) => {
        for (const key in apiErrors) {
            const raw = apiErrors[key];
            const message = Array.isArray(raw) ? raw[0] : raw;
            const uiKey = DOT_KEY_MAP[key] ?? key;
            errors[uiKey] = message;
        }
    };

    /**
     * Full API error handler for catch blocks.
     * - 422: maps field errors and shows "validation error" toast
     * - Other: shows the server message or a fallback toast
     */
    const handleApiError = (
        error: any,
        fallbackMessage: string,
        validationToastMsg: string = "Validation error — please check the form.",
    ) => {
        if (error.response?.status === 422 && error.response?.data?.errors) {
            mapApiErrors(error.response.data.errors);
            toastStore.error(validationToastMsg);
        } else {
            toastStore.error(error.response?.data?.message || fallbackMessage);
        }
    };

    return {
        errors,
        clearErrors,
        setError,
        clearFieldError,
        mapApiErrors,
        handleApiError,
    };
}
