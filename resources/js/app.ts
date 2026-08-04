import { createApp } from "vue";
import piniaPersist from "pinia-plugin-persistedstate";
import { createPinia } from "pinia";

import App from "@/App.vue";
import router from "@/router";
import { useLanguageStore } from "@/stores/languageStore";
import { useThemeStore } from "@/stores/themeStore";

const app = createApp(App);
const pinia = createPinia();
pinia.use(piniaPersist);
app.use(pinia);
app.use(router);

const languageStore = useLanguageStore();
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const themeStore = useThemeStore();

const setupGlobals = (): void => {
    Object.defineProperty(app.config.globalProperties, "$lang", {
        get() {
            return languageStore.translations;
        },
    });

    app.config.globalProperties.$tr = (
        key: string,
        params: Record<string, any> = {},
    ): string => {
        return languageStore.translate(key, params);
    };
};

setupGlobals();
app.mount("#app");

if (Object.keys(languageStore.translations).length === 0) {
    languageStore.fetchFrontLanguages(true, true);
} else {
    languageStore.fetchFrontLanguages(false, false);
}
