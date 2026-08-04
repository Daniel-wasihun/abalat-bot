<script setup lang="ts">
/**
 * FormToggle — a labeled toggle switch (on/off) styled to match the LMS.
 * Use in settings panels or inline within rows to toggle boolean record fields.
 *
 * @example
 * <FormToggle v-model="form.is_active" label="Active" />
 * <FormToggle v-model="settings.allow_renewals" label="Allow Renewals"
 * description="Members can extend their borrow period once." variant="success" />
 */
import { computed } from "vue";

interface Props {
 modelValue: boolean;
 label?: string;
 description?: string;
 error?: string;
 icon?: any;
 disabled?: boolean;
 variant?: "default" | "success" | "danger";
 /** Label position relative to the toggle */
 labelPosition?: "left" | "right" | "top";
}

const props = withDefaults(defineProps<Props>(), {
 disabled: false,
 variant: "default",
 labelPosition: "left",
});

const emit = defineEmits<{
 (e: "update:modelValue", value: boolean): void;
}>();

const TRACK_ACTIVE = {
 default: "bg-brand-blue",
 success: "bg-brand-green",
 danger: "bg-rose-500",
};

const trackClass = computed(() => [
 "relative w-10 h-5.5 rounded-full transition-all duration-300 shrink-0",
 props.modelValue ? TRACK_ACTIVE[props.variant] : "bg-main-text/15",
 props.disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer",
]);

function toggle() {
 if (props.disabled) return;
 emit("update:modelValue", !props.modelValue);
}
</script>

<template>
 <div class="form-toggle-wrapper w-full group">
 <!-- External Label -->
 <div v-if="label && labelPosition === 'top'" class="flex items-center justify-between mb-1 px-1">
 <label class="text-[13px] font-normal capitalize text-main-text/70">
 {{ label }}
 </label>
 </div>

 <div
 class="flex items-center h-11 bg-main-bg border border-input-border rounded-lg transition-all duration-300 px-5 relative"
 :class="[
 disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-brand-blue/30 group-focus-within:border-brand-blue'
 ]"
 @click="toggle">
 
 <div v-if="props.icon" class="flex items-center justify-center transition-colors shrink-0 mr-3 text-brand-blue/40 group-hover:text-brand-blue">
 <component :is="props.icon" class="w-5 h-5" :class="modelValue ? 'text-brand-blue/80' : ''" />
 </div>

 <div class="flex-1 min-w-0 flex items-center gap-4">
 <span
 v-if="description || (label && labelPosition !== 'top')"
 class="text-[14px] font-normal text-main-text/60 group-hover:text-main-text transition-colors"
 >{{ description || label }}</span
 >
 </div>

 <div
 class="relative inline-flex items-center transition-all ml-auto shrink-0">
 <div :class="trackClass">
 <div
 class="absolute top-[3px] w-4 h-4 bg-white rounded-full transition-all duration-300"
 :class="modelValue ? 'left-[calc(100%-19px)]' : 'left-[3px]'" />
 </div>
 </div>
 </div>

 <p v-if="error" class="text-[11px] text-rose-500 mt-1">
 {{ error }}
 </p>
 </div>
</template>
