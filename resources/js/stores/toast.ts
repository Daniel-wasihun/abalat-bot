import { defineStore } from "pinia";
import { ref } from "vue";

type ToastType = "success" | "error" | "info" | "warning";

interface Toast {
    id: number;
    message: string;
    type: ToastType;
}

export const useToastStore = defineStore("toast", () => {
    const toasts = ref<Toast[]>([]);
    let nextId = 1;

    const addToast = (message: string, type: ToastType = "info") => {
        // Prevent duplicate toasts within a short period
        const isDuplicate = toasts.value.some(
            (t) => t.message === message && t.type === type,
        );
        if (isDuplicate) return;

        const id = nextId++;
        toasts.value.push({ id, message, type });

        // Auto remove after 5 seconds
        setTimeout(() => {
            removeToast(id);
        }, 5000);
    };

    const removeToast = (id: number) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message: string) => addToast(message, "success");
    const error = (message: string) => addToast(message, "error");
    const info = (message: string) => addToast(message, "info");
    const warning = (message: string) => addToast(message, "warning");

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
        warning,
    };
});
