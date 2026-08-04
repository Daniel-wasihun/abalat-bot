<script setup lang="ts">
/**
 * FormCheckbox — a single labeled checkbox matching the LMS look-and-feel.
 * Works with v-model (boolean) and supports an error state.
 *
 * @example
 * <FormCheckbox v-model="form.is_active" label="Active" />
 * <FormCheckbox v-model="form.agree" label="I agree to the terms" required :error="errors.agree" />
 */
interface Props {
 modelValue: boolean;
 label?: string;
 description?: string;
 error?: string;
 disabled?: boolean;
 required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 disabled: false,
 required: false,
});

const emit = defineEmits<{
 (e: "update:modelValue", value: boolean): void;
 (e: "change", value: boolean): void;
}>();

function toggle() {
 if (props.disabled) return;
 emit("update:modelValue", !props.modelValue);
 emit("change", !props.modelValue);
}
</script>

<template>
 <div
 class="form-checkbox flex items-start gap-3 group"
 :class="disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
 @click="toggle">
 <!-- Hidden native checkbox (accessibility) -->
 <input
 type="checkbox"
 class="sr-only"
 :checked="modelValue"
 :disabled="disabled"
 @change="toggle" />

 <!-- Visual box -->
 <div
 class="w-5 h-5 rounded-md border-2 flex items-center justify-center shrink-0 mt-0.5 transition-all duration-200"
 :class="[
 modelValue
 ? 'bg-brand-blue border-brand-blue'
 : error
 ? 'bg-rose-500/5 border-rose-500'
 : 'bg-main-bg border-input-border group-hover:border-brand-blue/40',
 disabled ? 'cursor-not-allowed' : 'cursor-pointer',
 ]">
 <svg
 v-if="modelValue"
 class="w-3 h-3 text-white"
 fill="none"
 viewBox="0 0 24 24"
 stroke="currentColor"
 stroke-width="3.5">
 <path
 stroke-linecap="round"
 stroke-linejoin="round"
 d="M5 13l4 4L19 7" />
 </svg>
 </div>

 <!-- Label + description -->
 <div
 class="flex-1 min-w-0"
 :class="disabled ? 'cursor-not-allowed' : 'cursor-pointer'">
 <span
 v-if="label"
 class="text-[14px] font-normal text-main-text select-none">
 {{ label
 }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
 </span>
 <p
 v-if="description"
 class="text-[12px] text-main-text/40 mt-0.5 leading-relaxed select-none">
 {{ description }}
 </p>
 <p v-if="error" class="text-[11px] text-rose-500 mt-1 font-normal">
 {{ error }}
 </p>
 </div>
 </div>
</template>
