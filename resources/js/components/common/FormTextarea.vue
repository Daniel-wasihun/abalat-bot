<script setup lang="ts">
/**
 * FormTextarea — label + textarea + error/hint, matching FormField's feel.
 *
 * @example
 * <FormTextarea label="Notes" v-model="form.notes" :rows="4" :error="errors.notes" />
 */
import { computed, ref } from "vue";

interface Props {
 modelValue: string | null;
 label?: string;
 placeholder?: string;
 error?: string;
 hint?: string;
 rows?: number;
 disabled?: boolean;
 readonly?: boolean;
 required?: boolean;
 /** Whether the user can resize the textarea (default: none) */
 resizable?: boolean;
 maxlength?: number;
 icon?: any;
}

const props = withDefaults(defineProps<Props>(), {
 rows: 4,
 disabled: false,
 readonly: false,
 required: false,
 resizable: false,
});

const emit = defineEmits<{
 (e: "update:modelValue", value: string): void;
 (e: "input", event: Event): void;
 (e: "blur", event: FocusEvent): void;
 (e: "focus", event: FocusEvent): void;
}>();

const textareaRef = ref<HTMLTextAreaElement | null>(null);

const charCount = computed(() =>
 props.maxlength && props.modelValue
 ? String(props.modelValue).length
 : null,
);

const textareaClasses = computed(() => [
 "w-full bg-main-bg border border-input-border rounded-lg px-5 py-4 text-[14px] font-normal text-main-text outline-none transition-all duration-300 placeholder:text-main-text/40 custom-scrollbar",
 props.icon ? "pl-11" : "",
 props.resizable ? "resize-y" : "resize-none",
 props.error
 ? "border-rose-500/40 bg-rose-500/5 focus:border-rose-500"
 : "hover:border-brand-blue/30 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/5",
 props.disabled ? "opacity-50 cursor-not-allowed" : "cursor-text",
]);

defineExpose({ focus: () => textareaRef.value?.focus() });
</script>

<template>
 <div class="form-textarea w-full group">
 <!-- Label row -->
 <div v-if="label" class="flex items-center justify-between mb-1 px-1">
 <label class="text-[13px] font-normal capitalize text-main-text/70">
 {{ label
 }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
 </label>
 <slot name="label-right" />
 </div>

 <div class="relative flex">
 <!-- Left icon -->
 <div
 v-if="icon"
 class="absolute left-4 top-4 flex items-center justify-center pointer-events-none transition-colors"
 :class="
 error
 ? 'text-rose-500/60'
 : 'text-brand-blue/40 group-focus-within:text-brand-blue'
 ">
 <component :is="icon" class="w-4.5 h-4.5" />
 </div>

 <textarea
 ref="textareaRef"
 :value="modelValue ?? ''"
 :placeholder="placeholder"
 :disabled="disabled"
 :readonly="readonly"
 :rows="rows"
 :maxlength="maxlength"
 :class="textareaClasses"
 @input="
 emit(
 'update:modelValue',
 ($event.target as HTMLTextAreaElement).value,
 );
 emit('input', $event);
 "
 @blur="emit('blur', $event)"
 @focus="emit('focus', $event)"
 v-bind="$attrs" />
 </div>

 <!-- Footer row: error (or hint) left, char count right -->
 <div class="flex items-start justify-between mt-1.5 px-1">
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 -translate-y-1"
 enter-to-class="opacity-100 translate-y-0">
 <p
 v-if="error"
 class="text-[12px] text-rose-500 font-normal capitalize">
 {{ error }}
 </p>
 <p v-else-if="hint" class="text-[11px] text-main-text/40">
 {{ hint }}
 </p>
 </Transition>

 <span
 v-if="charCount !== null"
 class="text-[11px] text-main-text/30 ml-auto shrink-0">
 {{ charCount }} / {{ maxlength }}
 </span>
 </div>
 </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
 width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
 background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
 background: rgba(0, 0, 0, 0.06);
 border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
 background: rgba(255, 255, 255, 0.06);
}

textarea:-webkit-autofill,
textarea:-webkit-autofill:hover,
textarea:-webkit-autofill:focus {
 -webkit-text-fill-color: var(--main-text);
 -webkit-box-shadow: 0 0 0px 1000px transparent inset;
 transition: background-color 5000s ease-in-out 0s;
}
</style>
