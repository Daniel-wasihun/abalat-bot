<script setup lang="ts">
/**
 * SearchInput — a standalone search bar with a leading search icon and optional
 * clear button. Designed to match the high-fidelity administrative design system.
 * Centralized reusable component for all data table toolbars.
 */
import { computed, ref } from "vue";
import { Search, X } from "lucide-vue-next";

interface Props {
 modelValue: string;
 placeholder?: string;
 disabled?: boolean;
 /** Show a loading spinner instead of the search icon */
 loading?: boolean;
 /** Lucide component or any renderable icon (defaults to Search) */
 icon?: any;
 /** Input type (e.g. search, number, text) */
 type?: string;
}

const props = withDefaults(defineProps<Props>(), {
 placeholder: "Search…",
 disabled: false,
 loading: false,
 type: "text",
});

const emit = defineEmits<{
 (e: "update:modelValue", value: string): void;
 (e: "search", value: string): void;
 (e: "clear"): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const isFocused = ref(false);
const hasValue = computed(() => props.modelValue.length > 0);

function onInput(e: Event) {
 const val = (e.target as HTMLInputElement).value;
 emit("update:modelValue", val);
}

function onKeydown(e: KeyboardEvent) {
 if (e.key === "Enter") emit("search", props.modelValue);
}

function onClear() {
 emit("update:modelValue", "");
 emit("clear");
 inputRef.value?.focus();
}

defineExpose({ focus: () => inputRef.value?.focus() });
</script>

<template>
 <div 
 class="relative flex-1 min-w-0 max-w-lg group transition-all duration-300"
 >
 <!-- Leading icon / spinner -->
 <div
 class="absolute left-4 md:left-5 top-1/2 -translate-y-1/2 flex items-center pointer-events-none transition-colors duration-300"
 :class="isFocused ? 'text-brand-blue' : 'text-main-text/40'">
 <svg
 v-if="loading"
 class="w-4.5 h-4.5 animate-spin"
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
 <component :is="icon || Search" v-else class="w-4.5 h-4.5" />
 </div>

 <input
 ref="inputRef"
 :type="type"
 :value="modelValue"
 :placeholder="placeholder"
 :disabled="disabled"
 @focus="isFocused = true"
 @blur="isFocused = false"
 class="w-full h-11 pl-11 md:pl-14 pr-11 bg-card-bg/50 hover:bg-card-hover border border-input-border focus:border-brand-blue/30 rounded-xl outline-none text-base font-normal placeholder:text-main-text/40 placeholder:opacity-50 transition-all duration-300 font-sans"
 @input="onInput"
 @keydown="onKeydown" />

 <!-- Clear button -->
 <Transition
 enter-active-class="transition duration-150 ease-out"
 enter-from-class="opacity-0 scale-75"
 enter-to-class="opacity-100 scale-100"
 leave-active-class="transition duration-100 ease-in"
 leave-from-class="opacity-100 scale-100"
 leave-to-class="opacity-0 scale-75">
 <button
 v-if="hasValue"
 type="button"
 class="absolute right-3.5 p-1 rounded-full hover:bg-rose-500/10 hover:text-rose-500 text-main-text/20 transition-all duration-200"
 @click="onClear"
 tabindex="-1">
 <X class="w-3.5 h-3.5" />
 </button>
 </Transition>
 </div>
</template>

<style scoped>
/* Remove the native browser clear button for search inputs */
input[type="search"]::-webkit-search-decoration,
input[type="search"]::-webkit-search-cancel-button,
input[type="search"]::-webkit-search-results-button,
input[type="search"]::-webkit-search-results-decoration {
 display: none;
}
</style>
