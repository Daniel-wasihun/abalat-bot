import { defineStore } from "pinia";
import { ref, computed } from "vue";
import echo from "@/api/echo";
import apiClient from "@/api/apiClient";
import { useAuthStore } from "@/stores/authStore";
import { useToastStore } from "@/stores/toast";
import Cookies from "js-cookie";
import { useLanguageStore } from "@/stores/languageStore";

export interface Notification {
    id: string;
    title: string;
    message: string;
    title_key?: string;
    message_key?: string;
    params?: any;
    type: "security" | "info" | "success" | "warning" | "error";
    time: string;
    read: boolean;
    is_trashed?: boolean;
    link?: string | null;
    data?: any;
}

export const useNotificationStore = defineStore("notifications", () => {
    const authStore = useAuthStore();
    const toast = useToastStore();
    const languageStore = useLanguageStore();
    const notifications = ref<Notification[]>([]);
    const trashedNotifications = ref<Notification[]>([]);
    const loading = ref(false);
    const expandedId = ref<string | null>(null);
    const lastSessionUpdate = ref<any>(null);
    const lastBorrowUpdate = ref<any>(null);

    const unreadCount = computed(() => {
        return notifications.value.filter((n) => !n.read).length;
    });

    const mapNotification = (n: any): Notification => {
        const data = n.data || n;
        return {
            id: n.id,
            title: data.title || n.title || "Notification",
            message: data.message || n.message || "",
            title_key: data.title_key || n.title_key,
            message_key: data.message_key || n.message_key,
            params:
                data.message_params ||
                n.message_params ||
                data.params ||
                n.params,
            type: data.type || n.type || "info",
            time: n.created_at || n.time,
            read: !!n.read_at || !!n.read,
            is_trashed: !!n.is_trashed,
            link: data.link || n.link || null,
            data: data,
        };
    };

    /**
     * Returns true if the notification is a new-device security event.
     */
    const isSecurityEvent = (n: any) => {
        const data = n.data || n;
        const type = String(n.type || data.type || data.data?.type || "").toLowerCase();
        return type.includes("security") || type.includes("newdevicenotification");
    };

    /**
     * Returns true if this security notification was triggered by the current session
     * (i.e. this is the device that just logged in — it must not see its own alert).
     */
    const isSelfSecurity = (n: any) => {
        if (!isSecurityEvent(n)) return false;

        const data = n.data || n;
        const incomingSessionId = data.session_id || n.session_id || data.data?.session_id;

        const currentSessionId = String(
            authStore.currentSessionId || Cookies.get("current_session_id") || ""
        ).trim();
        const targetSessionId = String(incomingSessionId || "").trim();

        if (!targetSessionId || !currentSessionId) return false;

        return targetSessionId === currentSessionId;
    };

    const fetchNotifications = async () => {
        if (!authStore.user) return;
        loading.value = true;
        try {
            const response = await apiClient.get("/notifications");
            const data = response.data.data || response.data;
            notifications.value = data.map(mapNotification);
        } catch (error) {
            console.error("Failed to fetch notifications:", error);
        } finally {
            loading.value = false;
        }
    };

    const fetchTrashedNotifications = async () => {
        if (!authStore.user) return;
        try {
            const response = await apiClient.get("/notifications/trashed");
            const data = response.data.data || response.data;
            trashedNotifications.value = data.map(mapNotification);
        } catch (error) {
            console.error("Failed to fetch trashed notifications:", error);
        }
    };

    const addNotification = (notifBody: any) => {
        // 🛡️ SECURITY: Filter out notifications triggered by THIS session
        // (e.g., if I just logged in, I don't want to see a toast for my own login)
        if (isSelfSecurity(notifBody)) return;

        const notifId = notifBody.id || notifBody.data?.id;

        // Deduplicate notifications
        if (notifId && notifications.value.some((n) => n.id === notifId)) return;

        const data = notifBody.data || notifBody;
        const newNotif = mapNotification({
            ...data,
            id: notifId || Math.random().toString(36).substr(2, 9),
            read_at: null,
            is_trashed: false,
            created_at: new Date().toISOString(),
        });

        notifications.value.unshift(newNotif);

        const msgKey = data.short_message_key || data.message_key || "notifications.new_message";
        const params = data.message_params || data.params || {};
        const toastMsg = languageStore.translate(msgKey, params);

        if (isSecurityEvent(notifBody)) {
            toast.warning(toastMsg || languageStore.translate("notifications.new_device_login_title") || "Security Alert");
        } else {
            // Avoid duplicating a toast that apiClient may have already shown
            const isDuplicate = toast.toasts.some((t) => {
                const a = t.message.toLowerCase();
                const b = toastMsg.toLowerCase();
                return a.includes(b) || b.includes(a);
            });
            if (!isDuplicate) toast.info(toastMsg || "New notification received");
        }
    };

    const toggleExpand = (id: string) => {
        if (expandedId.value === id) {
            expandedId.value = null;
        } else {
            expandedId.value = id;
            markAsRead(id);
        }
    };

    const markAsRead = async (id: string) => {
        const notif = notifications.value.find((n) => n.id === id);
        if (notif && !notif.read) {
            try {
                await apiClient.post(`/notifications/${id}/read`, {}, {
                    skipSuccessToast: true,
                } as any);
                notif.read = true;
            } catch (error) {
                console.error("Failed to mark notification as read:", error);
            }
        }
    };

    const markAllAsRead = async () => {
        if (unreadCount.value === 0) return;
        try {
            await apiClient.post("/notifications/mark-all-read", {}, {
                skipSuccessToast: true,
            } as any);
            notifications.value.forEach((n) => (n.read = true));
        } catch (error) {
            console.error("Failed to mark all as read:", error);
        }
    };

    const trashNotification = async (id: string) => {
        try {
            await apiClient.post(`/notifications/${id}/trash`);
            const notif = notifications.value.find((n) => n.id === id);
            if (notif) {
                notif.is_trashed = true;
                trashedNotifications.value.unshift(notif);
                notifications.value = notifications.value.filter(
                    (n) => n.id !== id,
                );
            }
            if (expandedId.value === id) expandedId.value = null;
        } catch (error) {
            console.error("Failed to trash notification:", error);
        }
    };

    const restoreNotification = async (id: string) => {
        try {
            await apiClient.post(`/notifications/${id}/restore`);
            const notif = trashedNotifications.value.find((n) => n.id === id);
            if (notif) {
                notif.is_trashed = false;
                notifications.value.unshift(notif);
                notifications.value.sort(
                    (a, b) =>
                        new Date(b.time).getTime() - new Date(a.time).getTime(),
                );
                trashedNotifications.value = trashedNotifications.value.filter(
                    (n) => n.id !== id,
                );
            }
        } catch (error) {
            console.error("Failed to restore notification:", error);
        }
    };

    const deletePermanently = async (id: string) => {
        try {
            await apiClient.delete(`/notifications/${id}`);
            trashedNotifications.value = trashedNotifications.value.filter(
                (n) => n.id !== id,
            );
            notifications.value = notifications.value.filter(
                (n) => n.id !== id,
            );
        } catch (error) {
            console.error("Failed to delete notification permanently:", error);
        }
    };

    const trashAll = async () => {
        try {
            await apiClient.post("/notifications/trash-all");
            notifications.value.forEach((n) => {
                n.is_trashed = true;
                trashedNotifications.value.unshift(n);
            });
            notifications.value = [];
            if (expandedId.value) expandedId.value = null;
        } catch (error) {
            console.error("Failed to trash all notifications:", error);
        }
    };

    const emptyTrash = async () => {
        try {
            await apiClient.delete("/notifications/trash");
            trashedNotifications.value = [];
        } catch (error) {
            console.error("Failed to empty trash:", error);
        }
    };

    const clearAll = async () => {
        try {
            await apiClient.delete("/notifications/all");
            notifications.value = [];
            trashedNotifications.value = [];
        } catch (error) {
            console.error("Failed to clear all notifications:", error);
        }
    };

    let activeIndividualChannel: string | null = null;
    let activeUserChannel: string | null = null;

    const setupNotificationListener = () => {
        if (!authStore.user) return;

        // Clean up any existing listeners first to prevent duplicates or stale channels
        teardownNotificationListener();

        const userId = authStore.user.id;
        activeIndividualChannel = `App.Models.User.${userId}`;
        activeUserChannel = `user.${userId}`;

        const channelRef = echo.private(activeIndividualChannel);
        channelRef.notification((notification: any) =>
            addNotification(notification),
        );

        const userChannelRef = echo.private(activeUserChannel);
        
        // Listen for notifications specifically sent to the 'user.{id}' channel
        userChannelRef.notification((notification: any) =>
            addNotification(notification),
        );

        userChannelRef.listen(".session.updated", (data: any) => {
            lastSessionUpdate.value = {
                ...data.session,
                timestamp: Date.now(),
            };
        });

        userChannelRef.listen(".session.terminated", (data: any) => {
            const currentSessionId = authStore.currentSessionId || Cookies.get("current_session_id");
            if (data.sessionId === currentSessionId) {
                // ⚠️ EMERGENCY LOGOUT: This session was terminated by another device
                authStore.logout();
                toast.error("Your session was terminated for security reasons. Please log in again.");
            } else {
                // Just refresh the sessions list if another session was terminated
                lastSessionUpdate.value = { 
                    refreshOnly: true, 
                    timestamp: Date.now() 
                };
            }
        });

        userChannelRef.listen(".BorrowStatusUpdated", (data: any) => {
            lastBorrowUpdate.value = { ...data, timestamp: Date.now() };
        });
    };

    const teardownNotificationListener = () => {
        if (activeIndividualChannel) {
            echo.leave(activeIndividualChannel);
            activeIndividualChannel = null;
        }
        if (activeUserChannel) {
            echo.leave(activeUserChannel);
            activeUserChannel = null;
        }
    };

    return {
        notifications,
        trashedNotifications,
        unreadCount,
        loading,
        expandedId,
        fetchNotifications,
        fetchTrashedNotifications,
        addNotification,
        toggleExpand,
        markAsRead,
        markAllAsRead,
        trashNotification,
        restoreNotification,
        deletePermanently,
        trashAll,
        emptyTrash,
        clearAll,
        setupNotificationListener,
        teardownNotificationListener,
        lastSessionUpdate,
        lastBorrowUpdate,
    };
});
