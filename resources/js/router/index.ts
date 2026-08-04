import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from "vue-router";
import { h, resolveComponent } from "vue";
import Cookies from "js-cookie";
import { useAuthStore } from "@/stores/authStore";
import { useLanguageStore } from "@/stores/languageStore";

/**
 * User Management Routes
 */
const userManagementRoutes: RouteRecordRaw[] = [
    {
        path: "users",
        name: "users",
        meta: { title: "nav.users", permission: "users.view" },
        component: () => import("@/Views/UserManagement/UsersView.vue"),
    },
    {
        path: "roles",
        name: "roles",
        meta: { title: "nav.roles", permission: "roles.view" },
        component: () => import("@/Views/UserManagement/RolesView.vue"),
    },
    {
        path: "permissions",
        name: "permissions",
        meta: { title: "nav.permissions", permission: "permissions.view" },
        component: () => import("@/Views/UserManagement/PermissionsView.vue"),
    },
];

/**
 * Main Application Routes
 */
const routes: RouteRecordRaw[] = [
    {
        path: "/",
        redirect: "/login"
    },
    {
        path: "/login",
        name: "login",
        meta: { title: "auth.login" },
        component: () => import("@/Views/Public/Login/LoginView.vue"),
    },

    // Protected Routes
    {
        path: "/dashboard",
        meta: { requiresAuth: true },
        component: () => import("@/layouts/DashboardLayout.vue"),
        children: [
            {
                path: "",
                name: "dashboard",
                meta: { title: "dashboard", permission: "dashboard.view" },
                component: () => import("@/views/bot/DashboardView.vue"),
            },
            {
                path: "profile",
                meta: { title: "profile" },
                component: () => import("@/Views/Profile/ProfileLayout.vue"),
                children: [
                    {
                        path: "",
                        redirect: { name: "profile-overview" },
                    },
                    {
                        path: "overview",
                        name: "profile-overview",
                        meta: { title: "profile.overview" },
                        component: () =>
                            import("@/Views/Profile/ProfileOverview.vue"),
                    },
                    {
                        path: "edit",
                        name: "profile-edit",
                        meta: { title: "profile.edit" },
                        component: () =>
                            import("@/Views/Profile/ProfileEdit.vue"),
                    },
                    {
                        path: "security",
                        name: "profile-security",
                        meta: { title: "profile.security" },
                        component: () =>
                            import("@/Views/Profile/ProfileSecurity.vue"),
                    },
                    {
                        path: "devices",
                        name: "profile-devices",
                        meta: { title: "profile.devices" },
                        component: () =>
                            import("@/Views/Profile/ProfileDevices.vue"),
                    },
                ],
            },
            {
                path: "system",
                meta: { title: "nav.system" },
                component: { render: () => h(resolveComponent("router-view")) },
                children: [
                    {
                        path: "user-management",
                        meta: { title: "nav.user_management" },
                        component: {
                            render: () => h(resolveComponent("router-view")),
                        },
                        children: userManagementRoutes,
                    },
                ],
            },
            {
                path: "telegram-bot",
                meta: { title: "nav.telegram_bot" },
                component: { render: () => h(resolveComponent("router-view")) },
                children: [
                    {
                        path: "feedback",
                        name: "bot-feedback",
                        meta: { title: "nav.feedback", permission: "bot.view" },
                        component: () => import("@/views/bot/FeedbackView.vue"),
                    },
                    {
                        path: "settings",
                        name: "bot-settings",
                        meta: { title: "nav.bot_settings", permission: "bot.manage" },
                        component: () => import("@/views/bot/SettingsView.vue"),
                    },
                    {
                        path: "notifications",
                        name: "bot-notifications",
                        meta: { title: "nav.notifications", permission: "bot.notify" },
                        component: () => import("@/views/bot/NotificationsView.vue"),
                    },
                    {
                        path: "users",
                        name: "bot-users",
                        meta: { title: "nav.bot_users", permission: "bot.manage" },
                        component: () => import("@/views/bot/UsersView.vue"),
                    },
                    {
                        path: "users/:id",
                        name: "bot-user-profile",
                        meta: { title: "nav.bot_users", permission: "bot.manage" },
                        component: () => import("@/views/bot/UserProfileView.vue"),
                    }
                ],
            },
        ],
    },
    // 404 Route
    {
        path: "/:pathMatch(.*)*",
        name: "not-found",
        meta: { title: "404 - Not Found" },
        component: () => import("@/Views/Public/NotFound/NotFoundView.vue"),
    },
];

import {
    FALLBACK_ROUTE_PRIORITY,
    findFirstAccessibleRoute,
} from "@/utils/routeUtils";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior: () => ({ top: 0, behavior: "smooth" }),
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const token = Cookies.get("access_token");
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

    if (requiresAuth && !token) {
        return next({ name: "login", query: { redirect: to.fullPath } });
    }

    if (to.name === "login" && token) {
        if (!authStore.user) {
            authStore.fetchUser();
        }
        return next({
            name: findFirstAccessibleRoute(authStore.hasPermission) || "dashboard",
        });
    }

    if (token && !authStore.user) {
        try {
            await authStore.fetchUser();
        } catch {
            if (requiresAuth) return next({ name: "login" });
        }
    }

    const requiredPermission = to.meta.permission as string | undefined;
    if (requiredPermission) {
        if (authStore.user) {
            if (!authStore.hasPermission(requiredPermission)) {
                if (authStore.user.is_super_admin) return next();

                const fallback = findFirstAccessibleRoute(authStore.hasPermission);
                if (to.name === fallback) {
                    return next({
                        name: "not-found",
                        query: { type: "unauthorized" },
                        replace: true,
                    });
                }
                return next({ name: fallback || "not-found", replace: true });
            }
        } else if (token) {
            return next();
        } else {
            return next({ name: "login" });
        }
    }

    next();
});

router.afterEach((to) => {
    const languageStore = useLanguageStore();
    const titleKey = (to.meta.title as string) || "app.name";
    languageStore.setTitle(titleKey);
});

export default router;
