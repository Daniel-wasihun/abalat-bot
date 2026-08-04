<script setup lang="ts">
/**
 * FilterBar — a horizontal strip that holds search, selects, and action buttons
 * for filtering data tables. Collapses gracefully on small screens.
 *
 * Layout (left to right):
 * [#filters slot] | [#actions slot]
 *
 * The component itself adds no filters — it only provides consistent layout,
 * spacing, and the "Filters active" indicator pill.
 *
 * @example
 * <FilterBar :active-filters="activeFilterCount" @clear="clearFilters">
 * <template #filters>
 * <SearchInput v-model="query" />
 * <FormSelect v-model="status" :options="statuses" placeholder="Status" />
 * </template>
 * <template #actions>
 * <Button :icon="Plus" @click="openAdd">Add</Button>
 * </template>
 * </FilterBar>
 */
interface Props {
 /** Number of currently active filters – shows a clear button when > 0 */
 activeFilters?: number;
}

const props = withDefaults(defineProps<Props>(), {
 activeFilters: 0,
});

const emit = defineEmits<{
 (e: "clear"): void;
}>();
</script>

<template>
 <div class="flex items-center gap-3 flex-wrap">
 <!-- Filters (search inputs, selects, date pickers, …) -->
 <div class="flex items-center gap-3 flex-1 flex-wrap min-w-0">
 <slot name="filters" />

 <!-- Active-filter indicator -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 scale-90"
 enter-to-class="opacity-100 scale-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100 scale-100"
 leave-to-class="opacity-0 scale-90">
 <button
 v-if="activeFilters > 0"
 type="button"
 class="inline-flex items-center gap-1.5 h-9 px-3 bg-brand-blue/10 text-brand-blue border border-brand-blue/20 rounded-lg text-[12px] font-medium hover:bg-brand-blue/20 transition-colors shrink-0"
 @click="emit('clear')">
 <span
 >{{ activeFilters }} filter{{
 activeFilters > 1 ? "s" : ""
 }}</span
 >
 <svg
 xmlns="http://www.w3.org/2000/svg"
 class="w-3 h-3"
 viewBox="0 0 24 24"
 fill="none"
 stroke="currentColor"
 stroke-width="3"
 stroke-linecap="round"
 stroke-linejoin="round">
 <line x1="18" y1="6" x2="6" y2="18" />
 <line x1="6" y1="6" x2="18" y2="18" />
 </svg>
 </button>
 </Transition>
 </div>

 <!-- Action buttons (add, export, import, …) -->
 <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
 <slot name="actions" />
 </div>
 </div>
</template>
