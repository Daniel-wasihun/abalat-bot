<script setup lang="ts">
/**
 * FormRadioGroup — a group of radio buttons with a shared label and error support.
 * Works with v-model. Renders options in a horizontal row (or vertical column).
 *
 * @example
 * const genderOptions = [
 * { label: 'Male', value: 'male' },
 * { label: 'Female', value: 'female' },
 * ];
 * <FormRadioGroup label="Gender" v-model="form.gender" :options="genderOptions" required />
 */
interface Option {
 label: string;
 value: any;
 icon?: any;
 disabled?: boolean;
}

interface Props {
 modelValue: any;
 options: Option[];
 label?: string;
 error?: string;
 hint?: string;
 required?: boolean;
 /** Layout direction for the option list */
 direction?: "row" | "column";
}

const props = withDefaults(defineProps<Props>(), {
 required: false,
 direction: "row",
});

const emit = defineEmits<{
 (e: "update:modelValue", value: any): void;
 (e: "change", value: any): void;
}>();

function select(value: any, disabled?: boolean) {
 if (disabled) return;
 emit("update:modelValue", value);
 emit("change", value);
}
</script>

<template>
 <div class="form-radio-group w-full">
 <!-- Label -->
 <div v-if="label" class="flex items-center justify-between mb-3 px-1">
 <label class="text-[13px] font-normal text-main-text/70 capitalize">
 {{ label
 }}<span v-if="required" class="text-rose-500 ml-1">*</span>
 </label>
 </div>

 <!-- Options -->
 <div
 class="flex gap-3"
 :class="direction === 'column' ? 'flex-col' : 'flex-row flex-wrap'">
 <button
 v-for="option in options"
 :key="option.value"
 type="button"
 :disabled="option.disabled"
 class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border transition-all duration-200 text-[13px] font-medium cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
 :class="[
 modelValue === option.value
 ? 'bg-brand-blue/10 border-brand-blue text-brand-blue'
 : 'bg-main-bg border-input-border text-main-text/60 hover:border-brand-blue/30 hover:text-main-text',
 ]"
 @click="select(option.value, option.disabled)">
 <!-- Custom icon -->
 <component
 v-if="option.icon"
 :is="option.icon"
 class="w-4 h-4 shrink-0" />

 <!-- Radio circle -->
 <span
 class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-all"
 :class="
 modelValue === option.value
 ? 'border-brand-blue'
 : 'border-input-border'
 ">
 <span
 v-if="modelValue === option.value"
 class="w-2 h-2 rounded-full bg-brand-blue" />
 </span>

 {{ option.label }}
 </button>
 </div>

 <!-- Error -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 -translate-y-1"
 enter-to-class="opacity-100 translate-y-0">
 <p
 v-if="error"
 class="text-[11px] text-rose-500 mt-2 px-1 font-normal capitalize">
 {{ error }}
 </p>
 </Transition>

 <p
 v-if="hint && !error"
 class="text-[11px] text-main-text/40 mt-2 px-1">
 {{ hint }}
 </p>
 </div>
</template>
