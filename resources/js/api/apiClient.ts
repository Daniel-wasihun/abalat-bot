import axios, { type AxiosError, type InternalAxiosRequestConfig } from "axios";
import Cookies from "js-cookie";
import { useLanguageStore } from "@/stores/languageStore";
import { useToastStore } from "@/stores/toast";
import router from "@/router";

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL || "/api",
    timeout: 30000,
    headers: { Accept: "application/json" },
});

let isServerOffline = false;

// Inject authentication token and language headers into every request
apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
    const token = Cookies.get("access_token");
    const lang = Cookies.get("lang") || "en";

    if (token) config.headers.set("Authorization", `Bearer ${token}`);
    config.headers.set("lang", lang);
    config.headers.set("Accept-Language", lang);

    // Remove Content-Type for FormData to let the browser set the boundary
    if (
        config.data instanceof FormData ||
        config.headers.get("X-Is-FormData")
    ) {
        config.headers.delete("X-Is-FormData");
        config.headers.delete("Content-Type");
    }
    return config;
});

// Format technical errors into localized, professional user-friendly messages
const formatErrorMessage = (error: AxiosError<any>): string => {
    const status = error.response?.status;
    const backendMessage = error.response?.data?.message;

    // Static store reference
    const langStore = useLanguageStore();

    // Helper function to safely get translation or fallback to static text
    const safeTranslate = (
        key: string,
        en: string,
        am: string,
        params: any = {},
    ) => {
        const val = langStore.translate(key, params);
        if (val && val !== key) return val;

        const isAm = Cookies.get("lang") === "am";
        let text = isAm ? am : en;

        // Simple placeholder replacement for static fallbacks
        Object.entries(params).forEach(([k, v]) => {
            text = text
                .replace(`:${k}`, String(v))
                .replace(`{${k}}`, String(v));
        });
        return text;
    };

    if (backendMessage)
        return typeof backendMessage === "object"
            ? backendMessage.message || JSON.stringify(backendMessage)
            : backendMessage;

    if (!error.response && error.code === "ERR_NETWORK") {
        isServerOffline = true;
        return safeTranslate(
            "error.connection_lost",
            "Connection Lost: Unable to reach the server. Please check your internet.",
            "የግንኙነት መቆራረጥ፡ ከአገልጋዩ ጋር መገናኘት አልተቻለም። እባክዎ ኢንተርኔትዎን ያረጋግጡ።",
        );
    }
    if (error.code === "ECONNABORTED") {
        return safeTranslate(
            "error.request_timeout",
            "Request Timed Out: The server is taking too long to respond.",
            "የግንኙነት ጊዜ አልፏል፡ አገልጋዩ ምላሽ ለመስጠት ረጅም ጊዜ እየወሰደ ነው።",
        );
    }
    if (status && status >= 500) {
        if (status === 503) {
            return safeTranslate(
                "error.system_maintenance",
                "System Maintenance: We're performing updates. Please check back soon.",
                "የጥገና ሥራ፡ የማሻሻያ ሥራዎች እየተከናወኑ ነው። እባክዎ በቅርቡ ተመልሰው ይሞክሩ።",
            );
        }
        return safeTranslate(
            "error.server_error",
            `Service Error (${status}): The server encountered an issue. Please try again.`,
            `የአገልጋይ ስህተት (${status})፡ አገልጋዩ ችግር አጋጥሞታል። እባክዎ በኋላ ይሞክሩ።`,
            { status },
        );
    }

    return safeTranslate(
        "error.default_error",
        "Something went wrong. Please try again.",
        "ችግር ተከስቷል። እባክዎ በኋላ ይሞክሩ።",
    );
};

// Handle responses and manage automatic data recovery events
apiClient.interceptors.response.use(
    (response) => {
        if (isServerOffline) {
            isServerOffline = false;
            window.dispatchEvent(new CustomEvent("server-recovered"));
        }

        // Show success toast for data modifications
        const isMutation = ["post", "put", "patch", "delete"].includes(
            response.config.method || "",
        );
        const skipSuccessToast = (response.config as any)?.skipSuccessToast;

        if (
            isMutation &&
            response.data?.message &&
            response.request?.responseType !== "blob" &&
            !skipSuccessToast
        ) {
            useToastStore().success(response.data.message);
        }
        return response;
    },
    async (error: AxiosError<any>) => {
        const status = error.response?.status;

        // Convert error blobs to JSON if applicable
        if (
            error.response?.data instanceof Blob &&
            error.response.data.type === "application/json"
        ) {
            try {
                error.response.data = JSON.parse(
                    await error.response.data.text(),
                );
            } catch (e) {}
        }

        // Handle session expiration (401 Unauthorized)
        if (
            status === 401 &&
            !error.config?.url?.includes("/login") &&
            window.location.pathname !== "/login"
        ) {
            Cookies.remove("access_token", { path: "/" });
            const langStore = useLanguageStore();

            const isAm = Cookies.get("lang") === "am";
            const k = "error.session_expired";
            let defaultMsg = langStore.translate(k);
            if (!defaultMsg || defaultMsg === k) {
                defaultMsg = isAm
                    ? "የቆይታ ጊዜ አልፏል፡ እባክዎ እንደገና ይግቡ።"
                    : "Session expired. Please log in again.";
            }

            useToastStore().warning(
                error.response?.data?.message || defaultMsg,
            );

            if (
                router.currentRoute.value.matched.some(
                    (r) => r.meta.requiresAuth,
                )
            )
                router.replace("/login");
            else window.location.reload();
            return Promise.reject(error);
        }

        // Show formatted error notification for non-auth errors
        const errorMessage = formatErrorMessage(error);
        const skipErrorToast = (error.config as any)?.skipErrorToast;

        if (
            status !== 401 &&
            !error.config?.url?.includes("/login") &&
            !skipErrorToast
        ) {
            useToastStore().error(errorMessage);
        }

        error.message = errorMessage;
        return Promise.reject(error);
    },
);

export default apiClient;
