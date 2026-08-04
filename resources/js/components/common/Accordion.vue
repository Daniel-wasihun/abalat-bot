<script setup lang="ts">
/**
 * Accordion — a collapsible panel for hiding/showing less critical information.
 * Supports a title, optional icon, and an open/close toggle.
 *
 * @example
 * <Accordion title="Advanced Settings" :icon="Settings">
 * <FormToggle v-model="form.setting1" label="Setting 1" />
 * </Accordion>
 */
import { ref, watch } from "vue";
import { ChevronDown } from "lucide-vue-next";

interface Props {
 title: string;
 /** Lucide icon */
 icon?: any;
 /** Default state on mount */
 defaultOpen?: boolean;
 /** Pass in an external modelValue to control the open state from the parent */
 modelValue?: boolean;
 /** Removes the border around the accordion */
 noBorder?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 defaultOpen: false,
 modelValue: undefined,
 noBorder: false,
});

const emit = defineEmits<{
 (e: "update:modelValue", value: boolean): void;
 (e: "toggle", value: boolean): void;
}>();

// Internal state if uncontrolled
const internalOpen = ref(props.defaultOpen);

// Use controlled modelValue if provided, otherwise internal state
const isOpen = () =>
 props.modelValue !== undefined ? props.modelValue : internalOpen.value;

function toggle() {
 const newVal = !isOpen();
 internalOpen.value = newVal;
 emit("update:modelValue", newVal);
 emit("toggle", newVal);
}

// Keep internal state synced if parent forces a change
watch(
 () => props.modelValue,
 (val) => {
 if (val !== undefined) internalOpen.value = val;
 },
);
</script>

<template>
 <div
 class="rounded-xl bg-card-bg transition-all overflow-hidden"
 :class="[
 noBorder ? '' : 'border border-card-border',
 isOpen() && !noBorder ? 'shadow-sm' : '',
 ]">
 <button
 type="button"
 class="w-full flex items-center justify-between gap-4 px-6 py-4 text-left hover:bg-main-text/5 transition-colors focus:outline-none"
 @click="toggle">
 <div class="flex items-center gap-3">
 <component
 v-if="icon"
 :is="icon"
 class="w-4.5 h-4.5 text-main-text/50" />
 <span
 class="text-[14px] font-semibold text-main-text tracking-tight">
 {{ title }}
 </span>
 </div>

 <ChevronDown
 class="w-4.5 h-4.5 text-main-text/40 transition-transform duration-300"
 :class="isOpen() ? 'rotate-180' : ''" />
 </button>

 <!-- Collapsible content -->
 <div
 class="grid transition-all duration-300 ease-in-out"
 :class="
 isOpen()
 ? 'grid-rows-[1fr] opacity-100'
 : 'grid-rows-[0fr] opacity-0'
 ">
 <div class="overflow-hidden">
 <div class="px-6 pb-5 pt-1">
 <slot />
 </div>
 </div>
 </div>
 </div>
</template>
