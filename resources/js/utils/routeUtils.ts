/**
 * Priority-ordered list of fallback routes.
 * When a user navigates to a page they don't have permission for,
 * the guard redirects them to the FIRST route in this list they CAN access.
 */
export const FALLBACK_ROUTE_PRIORITY: { name: string; permission?: string }[] =
    [
        // Dashboard is always the first choice
        { name: "dashboard", permission: "dashboard.view" },
        // Bot management routes
        { name: "bot-feedback", permission: "bot.view" },
        { name: "bot-users", permission: "bot.manage" },
        { name: "bot-notifications", permission: "bot.notify" },
        { name: "bot-settings", permission: "bot.manage" },
        // System / admin routes
        { name: "roles", permission: "roles.view" },
        { name: "permissions", permission: "permissions.view" },
        // Always-available fallback: profile page
        { name: "profile-overview" },
    ];

/**
 * Finds the first route from FALLBACK_ROUTE_PRIORITY that the user has access to.
 */
export function findFirstAccessibleRoute(
    hasPermission: (permission: string) => boolean,
): string {
    for (const route of FALLBACK_ROUTE_PRIORITY) {
        if (!route.permission || hasPermission(route.permission)) {
            return route.name;
        }
    }
    return "profile-overview"; // ultimate safety net
}
