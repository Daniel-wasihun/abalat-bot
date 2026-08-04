import { computed } from "vue";
import { useAuthStore } from "@/stores/authStore";

export function useProfile() {
    const authStore = useAuthStore();

    /**
     * Computed property to generate user initials from their name.
     */
    const userInitial = computed(() => {
        const rawName = authStore.user?.name;
        // Simple localization for initials
        const name =
            typeof rawName === "object"
                ? rawName["en"] || Object.values(rawName)[0] || ""
                : rawName || "";
        const parts = name.trim().split(/\s+/) || [];
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        }
        return name.charAt(0).toUpperCase() || "U";
    });

    /**
     * Utility function to resolve the absolute URL for profile images.
     */
    const getProfileImage = (
        path: string | null | undefined,
    ): string | undefined => {
        if (!path) return undefined;
        if (path.startsWith("http")) return path;
        const baseUrl = import.meta.env.VITE_STORAGE_URL || "/storage";
        return `${baseUrl}/${path}`;
    };

    return {
        userInitial,
        getProfileImage,
    };
}
