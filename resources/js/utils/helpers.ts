import { useToastStore } from "@/stores/toast";

export function displayMessage(
    type: "success" | "error" | "warning" | "info",
    message: string,
) {
    const toastStore = useToastStore();
    switch (type) {
        case "success":
            toastStore.success(message);
            break;
        case "error":
            toastStore.error(message);
            break;
        case "warning":
            toastStore.warning(message);
            break;
        case "info":
        default:
            toastStore.info(message);
            break;
    }
}
