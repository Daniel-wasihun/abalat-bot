/**
 * Bot Application Permission Modules
 * Only Bot, Auth, and System permissions are retained.
 */
export const Modules = {
    DASHBOARD: "dashboard",
    USERS: "users",
    ROLES: "roles",
    PERMISSIONS: "permissions",
    SECURITY: "security",
    BOT: "bot",
} as const;

export type Module = (typeof Modules)[keyof typeof Modules];

export const Actions = {
    VIEW: "view",
    CREATE: "create",
    EDIT: "edit",
    DELETE: "delete",
    MANAGE: "manage",
    NOTIFY: "notify",
    EXPORT: "export",
} as const;

export type Action = (typeof Actions)[keyof typeof Actions];
