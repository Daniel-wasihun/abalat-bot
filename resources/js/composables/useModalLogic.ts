import { ref, reactive, watch, computed, toRaw } from "vue";
import { useValidation } from "./useValidation";
import { useToastStore } from "@/stores/toast";
import { useLanguageStore } from "@/stores/languageStore";
import { z } from "zod";

export interface ModalLogicOptions<T> {
    props: { show: boolean; [key: string]: any };
    emit: (event: any, ...args: any[]) => void;
    store?: any;
    schema?: z.ZodTypeAny | Record<string, z.ZodTypeAny>;
    itemKey?: string;
    // --- Legacy / Direct Save ---
    initialForm?: T;
    onSave?: (formData: T) => Promise<any>;
    onSync?: (item: any) => Partial<T>;
    // --- New Automated / Structured ---
    actions?: {
        create?: (data: any) => Promise<any>;
        update?: (data: any) => Promise<any>;
    };
    logic?: {
        getInitialForm: (item: any) => T;
        transformToPayload?: (form: T) => any;
        updateIdGetter?: (item: any) => any;
    };
    successMessage?: string | ((form: T) => string);
    closeOnSuccess?: boolean;
}

export function useModalLogic<T extends object>(options: ModalLogicOptions<T>) {
    const {
        props,
        emit,
        store,
        schema,
        itemKey = "item",
        initialForm: legacyInitialForm,
        onSave,
        onSync,
        actions,
        logic,
        successMessage,
        closeOnSuccess = true,
    } = options;

    const langStore = useLanguageStore();
    const toast = useToastStore();
    const {
        errors,
        validate,
        handleBackendErrors,
        clearErrors,
        clearFieldError,
    } = useValidation();

    const loading = ref(false);
    const deepClone = (obj: any) => JSON.parse(JSON.stringify(obj));

    const builtSchema = computed(() => {
        if (!schema) return null;
        if ("safeParse" in (schema as any)) return schema as z.ZodTypeAny;
        return z.object(schema as any).passthrough();
    });

    const getClonedInitial = (item: any) => {
        if (logic?.getInitialForm) return logic.getInitialForm(item);

        const fallback = deepClone(legacyInitialForm || {});
        if (item) {
            const extra = onSync ? onSync(item) : toRaw(item);
            return { ...fallback, ...extra };
        }
        return fallback;
    };

    const form = reactive(getClonedInitial(null)) as T;

    const isEdit = computed(() => !!props[itemKey]);

    const resetForm = () => {
        const defaults = getClonedInitial(null);
        Object.keys(form).forEach((key) => {
            (form as any)[key] = (defaults as any)[key];
        });
        clearErrors();
    };

    const sync = () => {
        const item = props[itemKey];
        if (item) {
            const mapped = getClonedInitial(item);
            Object.assign(form, mapped);
        } else {
            resetForm();
        }
        clearErrors();
    };

    const submit = async () => {
        clearErrors();
        loading.value = true;

        try {
            const dataToProcess = builtSchema.value
                ? validate(builtSchema.value as any, form)
                : form;
            if (!dataToProcess) {
                loading.value = false;
                return;
            }

            const payload = logic?.transformToPayload
                ? logic.transformToPayload(dataToProcess as T)
                : (dataToProcess as T);

            let result;
            if (onSave) {
                result = await onSave(form);
            } else if (actions) {
                const action = isEdit.value ? actions.update : actions.create;
                if (!action) throw new Error("Missing save action");
                result = await action(payload);
            } else {
                throw new Error("No save strategy provided");
            }

            // Success feedback
            const serverMsg = result?.message || result?.data?.message;
            if (serverMsg) {
                toast.success(serverMsg);
            } else if (successMessage) {
                const msg =
                    typeof successMessage === "function"
                        ? successMessage(form)
                        : successMessage;
                toast.success(langStore.translate(msg));
            }

            emit("saved", result);
            if (closeOnSuccess) emit("close");
        } catch (error: any) {
            handleBackendErrors(error);
        } finally {
            loading.value = false;
        }
    };

    watch(
        () => props.show,
        (show) => {
            if (show) sync();
        },
        { immediate: true },
    );
    watch(
        () => props[itemKey],
        (item) => {
            if (props.show && item) sync();
        },
        { deep: true },
    );

    return {
        form,
        loading,
        errors,
        isEdit,
        submit,
        resetForm,
        clearFieldError,
        clearErrors,
        sync,
    };
}
