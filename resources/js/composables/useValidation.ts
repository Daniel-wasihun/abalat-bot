import { ref } from "vue";
import { z } from "zod";
import { useLanguageStore } from "@/stores/languageStore";

export function useValidation() {
    const lang = useLanguageStore();
    const errors = ref<Record<string, string>>({});

    /**
     * Clear all errors
     */
    const clearErrors = () => {
        errors.value = {};
    };

    /**
     * Set a specific error for a field
     */
    const setError = (field: string, message: string) => {
        errors.value[field] = message;
    };

    /**
     * Clear error for a specific field
     */
    const clearFieldError = (field: string) => {
        if (errors.value[field]) {
            delete errors.value[field];
        }
    };

    /**
     * Validate data against a Zod schema
     */
    const validate = <T>(schema: z.ZodSchema<T>, data: any): T | null => {
        const result = schema.safeParse(data);

        if (!result.success) {
            result.error.issues.forEach((issue) => {
                const path = issue.path.join(".");

                // 1. Start with default params from Zod issue
                let params: Record<string, any> = {
                    count:
                        (issue as any).minimum ??
                        (issue as any).maximum ??
                        (issue as any).limit ??
                        (issue as any).expected,
                };

                let messageKey = issue.message;

                // 2. Support explicit param passing: "key|param1=val1,param2=val2"
                if (messageKey.includes("|")) {
                    const [key, paramStr] = messageKey.split("|");
                    messageKey = key;
                    paramStr.split(",").forEach((p) => {
                        const [pk, pv] = p.split("=");
                        if (pk && pv) params[pk.trim()] = pv.trim();
                    });
                }

                // 3. Add :attribute param
                if (!params["attribute"]) {
                    // Try to translate field name, fallback to path
                    let fieldKey = `field.${path}`;
                    let fieldName = lang.translate(fieldKey);

                    // If not found in 'field.', try 'user.form.' as a fallback
                    if (fieldName === fieldKey) {
                        let userFormKey = `user.form.${path}`;
                        let userFormName = lang.translate(userFormKey);
                        if (userFormName !== userFormKey) {
                            fieldName = userFormName;
                        }
                    }

                    // Handle nested localized fields (e.g. name.en -> campus_name if we specify it)
                    if (fieldName === fieldKey && path.includes(".")) {
                        const parts = path.split(".");
                        const base = parts[0];
                        const suffix = parts[parts.length - 1];

                        // If it ends in .en or .am, try mapping to a more descriptive key if path matches
                        if (["en", "am"].includes(suffix)) {
                            // This is a generic way to handle name.en -> campus_name mapping if we wanted
                            // But for now, let's just support explicit attribute passing better
                        }
                    }

                    params["attribute"] =
                        fieldName !== fieldKey
                            ? fieldName
                            : path.replace(/_/g, " ").replace(/\./g, " ");
                } else if (
                    typeof params["attribute"] === "string" &&
                    params["attribute"].startsWith("field.")
                ) {
                    // Recursive translation if attribute is a key
                    params["attribute"] = lang.translate(params["attribute"]);
                }

                // 4. Translate with combined params
                const message = messageKey.includes(".")
                    ? lang.translate(messageKey, params)
                    : messageKey;

                if (!errors.value[path]) {
                    errors.value[path] = message;
                }
            });
            return null;
        }

        return result.data;
    };

    /**
     * Helper to pass parameters to translation keys within Zod schemas
     */
    const withParams = (key: string, params: Record<string, any>) => {
        const p = Object.entries(params)
            .map(([k, v]) => `${k}=${v}`)
            .join(",");
        return `${key}|${p}`;
    };

    /**
     * Handle backend validation errors (422)
     */
    const handleBackendErrors = (err: any) => {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            const backendErrors = err.response.data.errors;
            Object.keys(backendErrors).forEach((field) => {
                // Laravel typically returns an array of messages per field
                const messages = backendErrors[field];
                errors.value[field] = Array.isArray(messages)
                    ? messages[0]
                    : messages;
            });
            return true;
        }
        return false;
    };

    return {
        errors,
        validate,
        withParams,
        setError,
        clearFieldError,
        clearErrors,
        handleBackendErrors,
    };
}
