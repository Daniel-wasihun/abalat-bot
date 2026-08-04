import { LanguageOption } from "@/stores/languageStore";

declare module "@vue/runtime-core" {
    interface ComponentCustomProperties {
        /**
         * The current language translations object.
         */
        $lang: Record<string, string>;

        /**
         * Translate a key with optional dynamic parameters.
         * @param key The translation key
         * @param params Key-value pairs for placeholders like :name or {name}
         */
        $tr: (
            key: string,
            params?: string | number | Record<string, any> | null,
        ) => string;
    }
}

export {};
