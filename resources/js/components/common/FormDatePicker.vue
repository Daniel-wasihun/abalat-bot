<script setup lang="ts">
/**
 * FormDatePicker — a labeled date (or datetime) input matching the LMS form style.
 * Thin wrapper around <input type="date|datetime-local"> with label, error, and hint.
 *
 * @example
 * <FormDatePicker label="Due Date" v-model="form.due_date" :error="errors.due_date" required />
 * <FormDatePicker label="Issued At" type="datetime-local" v-model="form.issued_at" />
 */
import { computed } from "vue";
import { Calendar } from "lucide-vue-next";

interface Props {
 modelValue: string | null;
 label?: string;
 type?: "date" | "datetime-local" | "month" | "week" | "time";
 error?: string;
 hint?: string;
 disabled?: boolean;
 required?: boolean;
 min?: string;
 max?: string;
}

const props = withDefaults(defineProps<Props>(), {
 type: "date",
 disabled: false,
 required: false,
});

const emit = defineEmits<{
 (e: "update:modelValue", value: string | null): void;
 (e: "change", value: string | null): void;
}>();

const inputClasses = computed(() => [
 "w-full h-11 bg-main-bg border border-input-border rounded-lg pl-11 pr-5 text-[14px] font-normal text-main-text outline-none transition-all duration-300",
 props.error
 ? "border-rose-500/40 bg-rose-500/5 focus:border-rose-500"
 : "hover:border-brand-blue/30 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/5",
 props.disabled ? "opacity-50 cursor-not-allowed" : "cursor-text",
 // Ensure the native calendar icon is hidden so our custom icon shows
 "[color-scheme:light] dark:[color-scheme:dark]",
]);

function onChange(e: Event) {
 const val = (e.target as HTMLInputElement).value || null;
 emit("update:modelValue", val);
 emit("change", val);
}
</script>

<template>
 <div class="form-date-picker w-full">
 <!-- Label -->
 <div v-if="label" class="flex items-center justify-between mb-2 px-1">
 <label class="text-[14px] font-normal text-main-text">
 {{ label
 }}<span v-if="required" class="text-rose-500 ml-1">*</span>
 </label>
 <slot name="label-right" />
 </div>

 <div class="relative flex items-center">
 <!-- Calendar icon -->
 <div
 class="absolute left-4 pointer-events-none transition-colors text-brand-blue/40">
 <Calendar class="w-4 h-4" />
 </div>

 <input
 :type="type"
 :value="modelValue ?? ''"
 :disabled="disabled"
 :min="min"
 :max="max"
 :class="inputClasses"
 @change="onChange"
 v-bind="$attrs" />
 </div>

 <!-- Error -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 -translate-y-1"
 enter-to-class="opacity-100 translate-y-0">
 <p
 v-if="error"
 class="text-[11px] text-rose-500 mt-1.5 px-1 font-normal capitalize">
 {{ error }}
 </p>
 </Transition>

 <!-- Hint -->
 <p
 v-if="hint && !error"
 class="text-[11px] text-main-text/40 mt-1.5 px-1">
 {{ hint }}
 </p>
 </div>
</template>
