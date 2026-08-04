/// <reference types="vite/client" />

interface ImportMeta {
    readonly env: ImportMetaEnv;
}

interface ImportMetaEnv {
    readonly BASE_URL: string;
    readonly MODE: string;
    readonly DEV: boolean;
    readonly PROD: boolean;
    readonly SSR: boolean;
    readonly VITE_API_URL?: string;
    readonly VITE_STORAGE_URL?: string;
    readonly VITE_APP_NAME?: string;
    [key: string]: string | boolean | undefined;
}
