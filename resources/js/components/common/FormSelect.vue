<script setup lang="ts">
/**
 * FormSelect — label + searchable dropdown + error/hint, all in one.
 * High-fidelity selection component with premium glass aesthetics.
 *
 * @example
 * <FormSelect
 * label="Branch"
 * v-model="form.branch_id"
 * :options="branchOptions"
 * :error="errors.branch_id"
 * required
 * />
 */
import {
 ref,
 watch,
 computed,
 onMounted,
 onUnmounted,
 nextTick,
 type CSSProperties,
} from "vue";
import { ChevronDown, Check, X } from "lucide-vue-next";

interface Option {
 label: string;
 value: any;
 icon?: any;
}

const props = withDefaults(
 defineProps<{
 modelValue: any;
 options: Option[];
 label?: string;
 placeholder?: string;
 error?: string;
 hint?: string;
 icon?: any;
 disabled?: boolean;
 required?: boolean;
 /** Show a × button to clear the selection */
 clearable?: boolean;
 /** Enable client-side option search */
 searchable?: boolean;
 loading?: boolean;
 }>(),
 {
 disabled: false,
 required: false,
 clearable: false,
 searchable: false,
 loading: false,
 },
);

const emit = defineEmits<{
 (e: "update:modelValue", value: any): void;
 (e: "change", value: any): void;
 (e: "clear"): void;
}>();

// ─── Dropdown state ──────────────────────────────────────────────────────────
const instanceId = Math.random().toString(36).slice(2, 9);
const isOpen = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref<CSSProperties>({});
const openUpward = ref(false);
const searchQuery = ref("");

const filteredOptions = computed(() => {
 if (!props.searchable || !searchQuery.value) return props.options;
 const q = searchQuery.value.toLowerCase();
 return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

const selectedOption = computed(
 () => props.options.find((o) => o.value === props.modelValue) ?? null,
);

// ─── Position calculation ─────────────────────────────────────────────────────
function calculatePosition() {
 if (!triggerRef.value) return;
 const rect = triggerRef.value.getBoundingClientRect();
 const vw = window.innerWidth;
 const vh = window.innerHeight;
 const estimatedH = Math.min(filteredOptions.value.length * 48 + 56, 320);
 const spaceBelow = vh - rect.bottom;

 openUpward.value = spaceBelow < estimatedH && rect.top > spaceBelow;

 const top = openUpward.value ? rect.top - 8 : rect.bottom + 8;
 let left = rect.left;
 const width = rect.width;

 if (left + width > vw - 16) left = vw - width - 16;
 if (left < 16) left = 16;

 dropdownStyle.value = {
 top: openUpward.value ? "auto" : `${top}px`,
 bottom: openUpward.value ? `${vh - rect.top + 8}px` : "auto",
 left: `${left}px`,
 width: `${width}px`,
 };
}

async function toggle() {
 if (props.disabled || props.loading) return;
 if (!isOpen.value) {
 isOpen.value = true;
 searchQuery.value = "";
 window.dispatchEvent(
 new CustomEvent("form-select:open", { detail: instanceId }),
 );
 await nextTick();
 calculatePosition();
 } else {
 isOpen.value = false;
 }
}

function close() {
 isOpen.value = false;
}

function select(option: Option) {
 emit("update:modelValue", option.value);
 emit("change", option.value);
 isOpen.value = false;
}

function clear(e: MouseEvent) {
 e.stopPropagation();
 emit("update:modelValue", null);
 emit("clear");
}

// ─── Click-outside & others-open listeners ───────────────────────────────────
function onClickOutside(e: MouseEvent) {
 if (!triggerRef.value?.contains(e.target as Node) && isOpen.value) {
 const dropdown = document.querySelector(
 `[data-form-select-id="${instanceId}"]`,
 );
 if (!dropdown?.contains(e.target as Node)) close();
 }
}

function onOtherOpen(e: Event) {
 const id = (e as CustomEvent<string>).detail;
 if (id !== instanceId) close();
}

const onLayout = () => {
 if (isOpen.value) calculatePosition();
};

onMounted(() => {
 window.addEventListener("click", onClickOutside, true);
 window.addEventListener("scroll", onLayout, { passive: true });
 window.addEventListener("resize", onLayout);
 window.addEventListener("form-select:open", onOtherOpen);
});

onUnmounted(() => {
 window.removeEventListener("click", onClickOutside, true);
 window.removeEventListener("scroll", onLayout);
 window.removeEventListener("resize", onLayout);
 window.removeEventListener("form-select:open", onOtherOpen);
});
</script>

<template>
  <div class="form-select w-full" ref="triggerRef">
  <!-- Label -->
  <div v-if="label" class="flex items-center justify-between mb-1 px-1 text-left">
  <label :id="`label-${instanceId}`" class="text-[13px] font-normal capitalize text-main-text/70">
  {{ label
  }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
  </label>
  <slot name="label-right" />
  </div>

  <!-- Trigger button -->
  <button
  type="button"
  :id="`btn-${instanceId}`"
  :aria-labelledby="label ? `label-${instanceId}` : undefined"
  :disabled="disabled"
  @click.stop="toggle"
 :class="[
 'w-full h-11 flex items-center gap-3 px-5 bg-main-bg border border-input-border rounded-lg transition-all duration-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:border-brand-blue/30',
 isOpen ? 'border-brand-blue ring-4 ring-brand-blue/5' : '',
 error ? 'border-rose-500/40 bg-rose-500/5' : '',
 ]">
 <!-- Leading icon (from prop or selected option) -->
 <component
 v-if="selectedOption?.icon || icon"
 :is="selectedOption?.icon || icon"
 class="w-4.5 h-4.5 shrink-0 transition-colors"
 :class="
 modelValue != null
 ? 'text-brand-blue/60'
 : 'text-brand-blue/30'
 " />

 <span
 class="flex-1 text-[14px] font-normal text-left truncate"
 :class="
 selectedOption ? 'text-main-text' : 'text-main-text/40'
 ">
 {{
 selectedOption
 ? selectedOption.label
 : placeholder || $tr?.("common.select") || "Select…"
 }}
 </span>

 <!-- Loading spinner -->
 <svg
 v-if="loading"
 class="w-4 h-4 shrink-0 animate-spin text-brand-blue/50"
 fill="none"
 viewBox="0 0 24 24">
 <circle
 class="opacity-25"
 cx="12"
 cy="12"
 r="10"
 stroke="currentColor"
 stroke-width="4" />
 <path
 class="opacity-75"
 fill="currentColor"
 d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
 </svg>

 <!-- Clear button -->
 <button
 v-else-if="clearable && selectedOption"
 type="button"
 class="shrink-0 text-main-text/30 hover:text-main-text/70 transition-colors"
 @click="clear">
 <X class="w-3.5 h-3.5" />
 </button>

 <!-- Chevron -->
 <ChevronDown
 v-else
 class="w-4 h-4 shrink-0 text-main-text/40 transition-transform duration-300"
 :class="{ 'rotate-180': isOpen }" />
 </button>

 <!-- Dropdown (teleported to body to avoid overflow clipping) -->
 <Teleport to="body">
 <Transition
 enter-active-class="transition duration-200 ease-out"
 :enter-from-class="
 openUpward
 ? 'opacity-0 translate-y-2 scale-[0.98]'
 : 'opacity-0 -translate-y-2 scale-[0.98]'
 "
 enter-to-class="opacity-100 translate-y-0 scale-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100 translate-y-0 scale-100"
 :leave-to-class="
 openUpward
 ? 'opacity-0 translate-y-2 scale-[0.98]'
 : 'opacity-0 -translate-y-2 scale-[0.98]'
 ">
  <div
  v-if="isOpen"
  :data-form-select-id="instanceId"
  class="fixed z-(--z-dropdown) bg-card-bg border border-card-border rounded-lg shadow-[0_32px_64px_rgba(0,0,0,0.2)] overflow-hidden flex flex-col p-2"
  :style="dropdownStyle">
 <!-- Search box -->
 <div v-if="searchable" class="px-1 pb-2">
 <input
 v-model="searchQuery"
 type="text"
 :placeholder="$tr?.('common.search') || 'Search…'"
 class="w-full h-9 px-3 bg-main-bg border border-input-border rounded-md text-[13px] text-main-text placeholder:text-main-text/40 outline-none focus:border-brand-blue transition-colors"
 @click.stop />
 </div>

 <!-- Options list -->
 <div
 class="overflow-y-auto max-h-[288px] space-y-0.5 custom-scrollbar">
 <p
 v-if="filteredOptions.length === 0"
 class="text-[13px] text-main-text/40 text-center py-6">
 {{ $tr?.("common.no_results") || "No results" }}
 </p>

 <button
 v-for="option in filteredOptions"
 :key="option.value"
 type="button"
 @click="select(option)"
 class="w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-lg transition-colors duration-200 cursor-pointer text-left"
 :class="[
 modelValue === option.value
 ? 'bg-brand-blue/10 text-brand-blue'
 : 'text-main-text hover:bg-main-bg',
 ]">
 <div class="flex items-center gap-3 min-w-0">
 <component
 v-if="option.icon"
 :is="option.icon"
 class="w-4 h-4 shrink-0"
 :class="
 modelValue === option.value
 ? 'text-brand-blue'
 : 'text-brand-blue/40'
 " />
 <span
 class="text-[14px] font-normal truncate"
 >{{ option.label }}</span
 >
 </div>
 <Check
 v-if="modelValue === option.value"
 class="w-4 h-4 shrink-0 text-brand-blue" />
 </button>
 </div>
 </div>
 </Transition>
 </Teleport>

 <!-- Error -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 -translate-y-1"
 enter-to-class="opacity-100 translate-y-0">
 <p
 v-if="error"
 class="text-[12px] text-rose-500 mt-1.5 px-1 font-normal capitalize">
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
