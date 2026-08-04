<script setup lang="ts">
/**
 * DataGrid — a responsive two-column (or single-column) grid of InfoRow-style pairs.
 * Great for member/book detail views where you want to display many fields cleanly.
 *
 * Just pass an array of { label, value } items – no need to manually write InfoRows.
 *
 * @example
 * <DataGrid :items="[
 * { label: 'ISBN', value: book.isbn },
 * { label: 'Publisher', value: book.publisher },
 * { label: 'Year', value: book.year },
 * { label: 'Edition', value: book.edition },
 * ]" />
 */
interface DataItem {
 label: string;
 value?: string | number | null;
 /** Lucide icon */
 icon?: any;
 /** Optional: span across full width */
 fullWidth?: boolean;
}

interface Props {
 items: DataItem[];
 /** How many columns (1 or 2) */
 columns?: 1 | 2;
}

const props = withDefaults(defineProps<Props>(), {
 columns: 2,
});
</script>

<template>
 <dl
 class="grid gap-x-8 gap-y-0"
 :class="columns === 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1'">
 <div
 v-for="(item, i) in items"
 :key="i"
 class="flex items-start gap-3 py-3 border-b border-card-border last:border-0"
 :class="item.fullWidth ? 'col-span-full' : ''">
 <!-- Icon -->
 <component
 v-if="item.icon"
 :is="item.icon"
 class="w-3.5 h-3.5 mt-0.5 shrink-0 text-brand-blue/40" />

 <div class="flex-1 min-w-0">
 <dt
 class="text-[11px] capitalize tracking-wide text-main-text/40 font-semibold mb-0.5">
 {{ item.label }}
 </dt>
 <dd
 class="text-[14px] text-main-text font-normal break-words leading-relaxed">
 <!-- Allow custom rendering via named slot -->
 <slot :name="`item-${i}`" :item="item">
 {{ item.value ?? "—" }}
 </slot>
 </dd>
 </div>
 </div>
 </dl>
</template>
