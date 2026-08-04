import { computed } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { type Module, Actions } from "@/constants/permissions";

/**
 * Composable for checking permissions for a specific module.
 *
 * @param module The module name permissions are being checked for.
 * @returns Computed refs for standard actions and a generic helper.
 */
export function usePermissions(module: Module) {
    const authStore = useAuthStore();

    const can = (action: string) => {
        if (authStore.user?.is_super_admin) return true;
        const permission = `${module}.${action}`;
        return authStore.hasPermission(permission);
    };

    const canView    = computed(() => can(Actions.VIEW));
    const canCreate  = computed(() => can(Actions.CREATE));
    const canEdit    = computed(() => can(Actions.EDIT));
    const canDelete  = computed(() => can(Actions.DELETE));
    const canManage  = computed(() => can(Actions.MANAGE));
    const canNotify  = computed(() => can(Actions.NOTIFY));
    const canExport  = computed(() => can(Actions.EXPORT));

    return {
        canView,
        canCreate,
        canEdit,
        canUpdate: canEdit, // alias
        canDelete,
        canManage,
        canNotify,
        canExport,
        can,
    };
}
