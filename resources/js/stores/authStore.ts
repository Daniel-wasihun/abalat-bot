import { defineStore } from "pinia";
import { ref, computed } from "vue";
import Cookies from "js-cookie";
import apiClient from "@/api/apiClient";
import router from "@/router";
import echo from "@/api/echo";
import { useLanguageStore } from "@/stores/languageStore";
import { useNotificationStore } from "@/stores/notificationStore";

export const useAuthStore = defineStore("auth", () => {
    const user = ref<any>(null);
    const token = ref(Cookies.get("access_token") || null);
    const loading = ref(false);
    const currentSessionId = ref<string | null>(
        Cookies.get("current_session_id") || null,
    );

    const isAuthenticated = computed(() => !!token.value);
    const permissions = computed(() => user.value?.permissions || []);

    const hasPermission = (permission: string) => {
        const u = user.value;
        if (!u) return false;

        // Super Admin Bypass (Flag or Role Slug)
        const roleSlug = (u.role?.slug || "")
            .toLowerCase()
            .replace(/[\s_]/g, "-");
        if (
            u.is_super_admin ||
            ["super-admin", "superadmin"].includes(roleSlug)
        )
            return true;

        const perms = u.permissions || [];
        const check = (perm: string) =>
            perms.some((p: any) =>
                typeof p === "string" ? p === perm : p.slug === perm,
            );

        if (check(permission)) return true;

        // Implicit view permission (Backend parity)
        const parts = permission.split(".");
        if (parts.length === 2 && parts[1] === "view") {
            const module = parts[0];
            if (
                check(`${module}.create`) ||
                check(`${module}.edit`) ||
                check(`${module}.delete`) ||
                check(`${module}.*`)
            ) {
                return true;
            }
        }

        return false;
    };

    const canManageAllLibraries = computed(() => {
        return hasPermission("libraries.manage_all");
    });

    const isStaff = computed(() => {
        const level = user.value?.hierarchy_level || 0;
        return level >= 40;
    });

    const isTeacher = computed(() => {
        const u = user.value;
        if (!u) return false;
        // Check primary role slug
        const slug = (u.role?.slug || "").toLowerCase();
        if (slug === "teacher") return true;
        // Also check all roles array (user may have multiple roles)
        const roles: any[] = u.roles || [];
        if (roles.some((r: any) => (r.slug || "").toLowerCase() === "teacher")) return true;
        // Fallback: user has teacher assignments flagged by backend
        return !!u.has_teacher_assignment;
    });

    const isStudent = computed(() => {
        const u = user.value;
        if (!u) return false;
        const slug = (u.role?.slug || "").toLowerCase();
        if (slug === "student") return true;
        const roles: any[] = u.roles || [];
        return roles.some((r: any) => (r.slug || "").toLowerCase() === "student");
    });

    const setAuth = (newToken: string, userData: any, sessionId?: string) => {
        token.value = newToken;
        Cookies.set("access_token", newToken, { expires: 30, sameSite: "Lax", path: "/" });

        // ⚡ CRITICAL: Set session ID BEFORE Echo reconnects.
        // Echo's channel subscription triggers POST /broadcasting/auth, which runs
        // TrackUserDevice and may immediately broadcast a new-device notification.
        // isSelfSecurity() must find the cookie already set at that moment, otherwise
        // the new device sees its own security alert.
        if (sessionId) {
            currentSessionId.value = sessionId;
            Cookies.set("current_session_id", sessionId, {
                expires: 30,
                sameSite: "Lax",
                path: "/"
            });
        } else if (userData?.sessions) {
            const current = userData.sessions.find((s: any) => s.is_current);
            if (current) {
                currentSessionId.value = current.session_id;
                Cookies.set("current_session_id", current.session_id, {
                    expires: 30,
                    sameSite: "Lax",
                    path: "/"
                });
            }
        }

        // Reconnect Echo with the new auth token (after the cookie is written)
        if (
            newToken &&
            typeof echo.disconnect === "function" &&
            typeof echo.connect === "function"
        ) {
            echo.disconnect();
            echo.connect();
        }

        // Trigger user-dependent watchers last to ensure Echo is ready
        user.value = userData;
    };

    const login = async (credentials: { email: string; password: string }) => {
        loading.value = true;
        try {
            const response = await apiClient.post("/login", credentials, {
                skipSuccessToast: true,
            } as any);

            if (response.data.requires_2fa) {
                return { requires_2fa: true, message: response.data.message };
            }

            // Laravel JsonResource structure: response.data.user and response.data.access_token
            const { user: userData, access_token, session_id } = response.data;
            setAuth(access_token, userData, session_id);

            // Sync missing translations immediately after a successful login
            try {
                const langStore = useLanguageStore();
                if (!Object.keys(langStore.translations).length)
                    langStore.fetchFrontLanguages(false);
            } catch (e) {
                console.warn("Recovery failed", e);
            }

            return response.data;
        } finally {
            loading.value = false;
        }
    };

    const verify2faLogin = async (data: { email: string; password: string; code: string }) => {
        loading.value = true;
        try {
            const response = await apiClient.post("/login/2fa", data, {
                skipSuccessToast: true,
            } as any);

            const { user: userData, access_token, session_id } = response.data;
            setAuth(access_token, userData, session_id);

            try {
                const langStore = useLanguageStore();
                if (!Object.keys(langStore.translations).length)
                    langStore.fetchFrontLanguages(false);
            } catch (e) {
                console.warn("Recovery failed", e);
            }

            return response.data;
        } finally {
            loading.value = false;
        }
    };

    let fetchPromise: Promise<any> | null = null;

    // Fetch the authenticated user's profile and handle session expiry
    const fetchUser = async () => {
        if (fetchPromise) return fetchPromise;
        fetchPromise = (async () => {
            try {
                const response = await apiClient.get("/me");
                const userData = response.data.user;

                // Merge top-level flags (like has_teacher_assignment) into the user object
                // so computed properties like isTeacher can access them.
                if (response.data.has_teacher_assignment !== undefined) {
                    userData.has_teacher_assignment = response.data.has_teacher_assignment;
                }

                user.value = userData;

                // Update current session ID if available
                if (userData?.sessions) {
                    const current = userData.sessions.find(
                        (s: any) => s.is_current,
                    );
                    if (current) {
                        currentSessionId.value = current.session_id;
                        Cookies.set("current_session_id", current.session_id, {
                            expires: 30,
                            sameSite: "Lax",
                            path: "/"
                        });
                    }
                }

                return userData;
            } catch (error) {
                logout();
                throw error;
            } finally {
                fetchPromise = null;
            }
        })();
        return fetchPromise;
    };

    const logout = async () => {
        try {
            const notificationStore = useNotificationStore();
            notificationStore.teardownNotificationListener();

            await apiClient.post("/logout");
        } catch (e) {
            console.error("Logout request failed", e);
        } finally {
            token.value = null;
            user.value = null;
            currentSessionId.value = null;
            Cookies.remove("access_token", { path: "/" });
            Cookies.remove("current_session_id", { path: "/" });

            if (typeof echo.disconnect === "function") {
                echo.disconnect();
            }

            router.replace("/login");
        }
    };

    const sendOtp = async (data: {
        email: string;
        _hp_email_verification?: string;
        _hp_timestamp?: string;
    }) => {
        return apiClient.post("/forgot-password/send-otp", data);
    };

    const verifyOtp = async (data: {
        email: string;
        otp: string;
        _hp_email_verification?: string;
        _hp_timestamp?: string;
    }) => {
        return apiClient.post("/forgot-password/verify-otp", data);
    };

    const resetPassword = async (data: any) => {
        return apiClient.post("/forgot-password/reset", data);
    };

    const updateProfile = async (formData: FormData) => {
        loading.value = true;
        try {
            // Laravel PATCH/PUT can be tricky with FormData, but we have a POST spoofing if needed.
            // However, our route is PUT /api/profile. For FormData with PUT, we often use POST with _method=PUT.
            formData.append("_method", "PUT");
            const response = await apiClient.post("/profile", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            user.value = response.data.user;
            return response.data;
        } finally {
            loading.value = false;
        }
    };

    const changePassword = async (data: any) => {
        loading.value = true;
        try {
            const response = await apiClient.post("/change-password", data);
            return response.data;
        } finally {
            loading.value = false;
        }
    };

    // Session Management
    const sessions = ref<any[]>([]);
    const sessionsLoading = ref(false);

    const fetchSessions = async () => {
        sessionsLoading.value = true;
        try {
            const response = await apiClient.get("/sessions");
            sessions.value = response.data.user?.sessions || [];
            return sessions.value;
        } catch (error) {
            sessions.value = [];
            throw error;
        } finally {
            sessionsLoading.value = false;
        }
    };

    const logoutSession = async (sessionId: number) => {
        const response = await apiClient.post(`/sessions/${sessionId}/logout`);
        // Refresh sessions list after logout
        await fetchSessions();
        return response.data;
    };

    const logoutAllOtherSessions = async () => {
        const response = await apiClient.post("/sessions/logout-other");
        await fetchSessions();
        return response.data;
    };

    return {
        user,
        token,
        loading,
        currentSessionId,
        isAuthenticated,
        permissions,
        hasPermission,
        canManageAllLibraries,
        isStaff,
        isTeacher,
        isStudent,
        login,
        fetchUser,
        logout,
        sendOtp,
        verifyOtp,
        resetPassword,
        updateProfile,
        changePassword,
        sessions,
        sessionsLoading,
        fetchSessions,
        logoutSession,
        logoutAllOtherSessions,
    };
});
