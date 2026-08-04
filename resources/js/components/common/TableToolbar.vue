<script setup lang="ts">
/**
 * TableToolbar — The Command Center of the DataTable module.
 * Unified design system implementation for filtering, search, and primary actions.
 * Optimized for performance and sleek, modern layout consistency.
 *
 * Uses the centralized SearchInput component for consistent "Elite" interaction.
 */
import SearchInput from "./SearchInput.vue";
import { Filter, RotateCcw, Plus } from "lucide-vue-next";

interface Props {
 modelValue: string;
 placeholder?: string;
 showFilters?: boolean;
 hasActiveFilters?: boolean;
 createLabel?: string;
 canCreate?: boolean;
 filterLabel?: string;
 resetLabel?: string;
 loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 modelValue: "",
 placeholder: "Search...",
 showFilters: false,
 hasActiveFilters: false,
 canCreate: false,
 filterLabel: "Filters",
 resetLabel: "Reset",
 loading: false,
});

const emit = defineEmits([
 "update:modelValue",
 "toggle-filters",
 "reset",
 "create",
 "search"
]);
</script>

<template>
 <div
 class="flex flex-col gap-2.5 p-4 bg-card-bg border-b border-card-border/60 overflow-visible rounded-t-2xl">
 <!-- Row 1: Search & Primary Actions (Command Center Layout) -->
 <div class="flex flex-wrap items-center gap-4">
 <!-- Centralized reusable SearchInput (The "Elite" Interaction) -->
 <div class="flex-1 min-w-[300px] max-w-lg">
 <SearchInput
 :model-value="modelValue"
 @update:model-value="val => emit('update:modelValue', val)"
 @search="val => emit('search', val)"
 :placeholder="placeholder"
 :loading="loading"
 />
 </div>

 <!-- Action Button Group -->
 <div class="flex items-center gap-3 ml-auto">
 <!-- Filter Toggle (Active States) -->
 <button
 @click="emit('toggle-filters')"
 class="flex items-center gap-2.5 px-5 h-11 rounded-xl border transition-all text-sm font-bold active:scale-95 hover:-translate-y-0.5"
 :class="
 showFilters
 ? 'bg-brand-blue text-white border-brand-blue '
 : 'bg-main-bg border-card-border/60 text-main-text/60 hover:border-brand-blue/30 hover:bg-brand-blue/5 hover:text-brand-blue font-sans'
 ">
 <Filter class="w-4.5 h-4.5" />
 <span class="hidden sm:inline capitalize tracking-wider text-xs">{{ filterLabel }}</span>
 <div
 v-if="hasActiveFilters"
 class="w-2 h-2 rounded-full bg-orange-500 ring-2 ring-white/20" />
 </button>

 <!-- Reset Button -->
 <button
 v-if="hasActiveFilters"
 @click="emit('reset')"
 class="h-11 w-11 flex items-center justify-center rounded-xl border border-card-border/60 text-main-text/40 hover:text-rose-500 hover:bg-rose-500/5 hover:border-rose-500/20 transition-all active:scale-95 hover:-translate-y-0.5 "
 :title="resetLabel">
 <RotateCcw class="w-4.5 h-4.5" />
 </button>

 <!-- Custom Slot Actions -->
 <slot name="actions" />

 <!-- Global CTA (Primary) -->
 <button
 v-if="canCreate"
 @click="emit('create')"
 class="flex items-center gap-2 px-6 h-11 bg-brand-blue text-white rounded-md font-bold text-sm hover:bg-brand-blue-dark hover:-translate-y-0.5 transition-all active:scale-95 font-sans">
 <Plus class="w-5 h-5 stroke-[2.5]" />
 <span class="hidden md:inline capitalize tracking-widest text-xs">{{ createLabel }}</span>
 </button>
 </div>
 </div>

 <!-- Row 2: Extendable Filters Panel (Fluent Animation) -->
 <Transition
 enter-active-class="transition duration-400 ease-[cubic-bezier(0.22,1,0.36,1)]"
 enter-from-class="opacity-0 -translate-y-6 scale-98"
 enter-to-class="opacity-100 translate-y-0 scale-100"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0 scale-100"
 leave-to-class="opacity-0 -translate-y-4 scale-95">
 <div
 v-if="showFilters"
 class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-x-6 gap-y-4 pt-5 border-t border-card-border/40 overflow-visible">
 <slot name="filters" />
 </div>
 </Transition>
 </div>
</template>
