import { useAuthStore } from "@/stores/authStore";
import { storeToRefs } from "pinia";

import { computed } from "vue";

export function useSecurity() {
    const authStore = useAuthStore();
    const { user: currentUser } = storeToRefs(authStore);

    const isSuperAdmin = computed(() => {
        const u = currentUser.value;
        if (!u) return false;
        if (u.is_super_admin) return true;
        const slug = (u.role?.slug || "").toLowerCase().replace(/[\s_]/g, "-");
        return ["super-admin", "superadmin"].includes(slug);
    });

    /**
     * Frontend mirror of backend hierarchical security check.
     */
    const canModifyUser = (targetUser: any) => {
        if (!targetUser || !currentUser.value) return false;

        // Cannot modify self through administrative actions
        if (currentUser.value.id === targetUser.id) return false;

        // Super Admin Bypass
        if (isSuperAdmin.value) return true;

        // Library Isolation Strategy (Mirroring Backend)
        // Users restricted to a library cannot modify STAFF in other explicitly named branches.
        // They CAN modify Teachers and Students regardless of library (global resources).
        if (currentUser.value.library_id && !authStore.canManageAllLibraries) {
            const targetRoleSlug = targetUser.role?.slug?.toLowerCase() || "";
            const isGlobalRole =
                targetRoleSlug.includes("teacher") ||
                targetRoleSlug.includes("student");

            if (
                !isGlobalRole &&
                targetUser.library_id &&
                targetUser.library_id !== currentUser.value.library_id
            ) {
                return false;
            }
        }

        // Hierarchy Level Check
        const targetLevel =
            targetUser.hierarchy_level || targetUser.role?.hierarchy_level || 0;
        const myLevel = currentUser.value.hierarchy_level || 0;

        // Seniority Check
        if (myLevel > targetLevel) return true;

        // Peer-to-Peer Ownership Check (Simplification)
        if (myLevel === targetLevel) {
            const currentAssignment = targetUser.assignments?.find(
                (a: any) => a.is_currently_valid,
            );
            return currentAssignment?.assigned_by_id === currentUser.value.id;
        }

        return false;
    };

    /**
     * Check if current user can modify a specific role.
     */
    const canModifyRole = (role: any) => {
        if (!currentUser.value) return false;
        if (!role) return false;

        // Super Admin Bypass — full unrestricted access
        if (isSuperAdmin.value) return true;

        // User is authorized (caller checks roles.edit permission)
        // so we allow full modification of the role item.
        return true;
    };

    return {
        canModifyUser,
        canModifyRole,
        isSuperAdmin,
    };
}
