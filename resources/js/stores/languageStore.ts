import { defineStore } from "pinia";
import { ref, computed, watch } from "vue";
import Cookies from "js-cookie";
import apiClient from "@/api/apiClient";

// Bump this number whenever new translation keys are added.
// It forces all clients to discard their cached translations and re-fetch.
const TRANSLATION_VERSION = 14;

export interface LanguageOption {
    key: string;
    name: string;
    icon: string;
}

export const useLanguageStore = defineStore(
    "lang",
    () => {
        const isLoading = ref(false);
        const availableLanguages = ref<LanguageOption[]>([]);
        const translations = ref<Record<string, string>>({});
        const translationCache = ref<Record<string, Record<string, string>>>(
            {},
        );
        const translationVersion = ref<number>(0);
        const isInitialized = ref(true);
        const currentLanguage = ref<string>(Cookies.get("lang") || "en");
        const activeTitleKey = ref<string>("app.name");

        // If the stored version is outdated, wipe the cache so fresh translations are fetched.
        if (translationVersion.value !== TRANSLATION_VERSION) {
            translationCache.value = {};
            translations.value = {};
            translationVersion.value = TRANSLATION_VERSION;
        }

        // Synchronize page language and direction attributes with the selected language
        watch(
            currentLanguage,
            (newLang) => {
                document.documentElement.setAttribute("lang", newLang);
                document.documentElement.setAttribute(
                    "dir",
                    newLang === "ar" ? "rtl" : "ltr",
                );
                updateDocumentTitle();
            },
            { immediate: true },
        );

        // Automatically refetch missing translations when the server or network recovers
        if (typeof window !== "undefined") {
            const handleRecovery = () => {
                if (
                    !Object.keys(translations.value).length ||
                    !availableLanguages.value.length
                )
                    fetchFrontLanguages(false);
            };
            window.addEventListener("server-recovered", handleRecovery);
            window.addEventListener("online", handleRecovery);
        }

        watch(translations, () => updateDocumentTitle());

        function updateDocumentTitle() {
            const appName = translate("app.name");
            const translated = translate(activeTitleKey.value);
            if (
                activeTitleKey.value.includes(".") &&
                activeTitleKey.value !== "app.name"
            ) {
                document.title =
                    translated !== activeTitleKey.value
                        ? `${translated} | ${appName}`
                        : translated;
            } else {
                document.title = translated;
            }
        }

        function setTitle(key: string) {
            activeTitleKey.value = key;
            updateDocumentTitle();
        }

        let fetchPromise: Promise<void> | null = null;

        // Fetch frontend translations and de-duplicate simultaneous requests
        async function fetchFrontLanguages(
            isNeedLoading = true,
            force = false,
        ) {
            if (fetchPromise && !force) return fetchPromise;

            fetchPromise = (async () => {
                const langCode = currentLanguage.value;
                if (
                    !force &&
                    translationCache.value[langCode] &&
                    availableLanguages.value.length
                ) {
                    translations.value = translationCache.value[langCode];
                    return;
                }

                if (isNeedLoading) isLoading.value = true;
                // Clear stale cache when forcing refresh
                if (force) {
                    translationCache.value = {};
                }
                try {
                    const { data } = await apiClient.get(
                        `front-language?_t=${Date.now()}`,
                    );
                    availableLanguages.value = Array.isArray(
                        data.available_languages,
                    )
                        ? data.available_languages
                        : Object.values(data.available_languages || {});
                    translations.value = data.translations || {};
                    if (data.current_language)
                        translationCache.value[data.current_language] =
                            data.translations || {};

                    if (
                        data.current_language &&
                        data.current_language !== currentLanguage.value
                    ) {
                        currentLanguage.value = data.current_language;
                        Cookies.set("lang", data.current_language, {
                            expires: 365,
                            path: "/",
                        });
                    }
                } catch (error) {
                    console.error("Translation fetch failed", error);
                } finally {
                    isLoading.value = false;
                }
            })();

            try {
                return await fetchPromise;
            } finally {
                fetchPromise = null;
            }
        }

        async function setLanguage(lang: string) {
            if (lang === currentLanguage.value) return;
            currentLanguage.value = lang;
            Cookies.set("lang", lang, { expires: 365, path: "/" });
            apiClient.defaults.headers.common["lang"] = lang;
            apiClient.defaults.headers.common["Accept-Language"] = lang;
            await fetchFrontLanguages(false, true);
        }

        const getCurrentLang = computed(() => {
            if (!availableLanguages.value.length)
                return {
                    key: currentLanguage.value,
                    name: currentLanguage.value.toUpperCase(),
                    icon: "",
                };
            return (
                availableLanguages.value.find(
                    (l) => l.key === currentLanguage.value,
                ) || availableLanguages.value[0]
            );
        });

        // Replace placeholders and handle pluralization/optional blocks in localized strings
        function translate(key: string, params: any = {}): string {
            if (typeof key !== "string" || !key) {
                // Return a safe string representation for non-string or empty keys
                return String(key ?? "");
            }

            let text: any = translations.value[key];

            // Handle nested translation keys (dot notation)
            if (text === undefined && key.includes(".")) {
                text = translations.value;
                const segments = key.split(".");
                for (const segment of segments) {
                    if (text && typeof text === "object" && segment in text) {
                        text = text[segment];
                    } else {
                        text = undefined;
                        break;
                    }
                }
            }

            text = text || key;
            text = String(text);

            if (params !== null && typeof params !== "object") {
                const firstMatch = String(text).match(
                    /[:{](\w+)(\|[^}\]]+)?}?/,
                );
                if (firstMatch) params = { [firstMatch[1]]: params };
            }
            if (params && typeof params === "object") {
                Object.entries(params).forEach(([k, v]) => {
                    const val =
                        v !== null && v !== undefined && v !== ""
                            ? String(v)
                            : null;
                    if (val !== null)
                        text = text.replace(
                            new RegExp(`[:{]${k}(\\|[^}\\]]+)?}?`, "g"),
                            val,
                        );
                });
            }
            return text
                .replace(/[:{]\w+\|([^}\]]+)}?/g, "$1")
                .replace(/\[[^\]]*?[:{][a-zA-Z0-9_]+[^\]]*?\]/g, "")
                .replace(/[\[\]]/g, "")
                .replace(/\s?[:{][a-zA-Z0-9_]+}?/g, "")
                .trim();
        }

        function localize(data: any): string {
            if (!data) return "";
            if (typeof data === "string") return data;
            if (typeof data === "object") {
                return (
                    data[currentLanguage.value] ||
                    data["en"] ||
                    Object.values(data)[0] ||
                    ""
                );
            }
            return String(data);
        }

        return {
            availableLanguages,
            currentLanguage,
            getCurrentLang,
            translations,
            translationCache,
            translationVersion,
            isLoading,
            isInitialized,
            setLanguage,
            fetchFrontLanguages,
            setIsInitialized: (v: boolean) => (isInitialized.value = v),
            translate,
            localize,
            setTitle,
        };
    },
    {
        persist: {
            paths: ["currentLanguage", "availableLanguages", "translations", "translationCache", "translationVersion"],
        },
    },
);
