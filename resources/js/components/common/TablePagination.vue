<script setup lang="ts">
import { computed } from "vue";
import {
 ChevronLeft,
 ChevronRight,
 MoreHorizontal,
 ChevronDown,
 Loader2,
} from "lucide-vue-next";

interface Props {
 currentPage: number;
 lastPage: number;
 total?: number;
 perPage?: number;
 loading?: boolean;
 summary?: string;
 showLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
 loading: false,
});

const emit = defineEmits(["page-change", "per-page-change"]);

// Pagination Logic
const pages = computed(() => {
 const current = props.currentPage;
 const last = props.lastPage;
 const delta = 2;
 const range: (number | string)[] = [];

 if (last <= 0) return [1];

 for (
 let i = Math.max(2, current - delta);
 i <= Math.min(last - 1, current + delta);
 i++
 ) {
 range.push(i);
 }

 if (current - delta > 2) range.unshift("...");
 if (current + delta < last - 1) range.push("...");

 range.unshift(1);
 if (last > 1) range.push(last);

 return range;
});

// Styles Helper — loading never affects visual active state; only hard boundary (first/last page) does
const navBtnClass = (active: boolean) => [
 "px-4 h-9 flex items-center justify-center gap-2 rounded-lg transition-colors duration-150 active:scale-[0.98] group select-none",
 active
 ? "bg-brand-blue text-white ring-1 ring-brand-blue/30 hover:brightness-110"
 : "bg-main-text/5 text-main-text/20 cursor-not-allowed opacity-50",
 // Block double-clicks when loading without changing appearance
 props.loading && active ? "pointer-events-none" : "",
];
</script>

<template>
 <div
 class="flex items-center justify-between w-full text-sm font-medium text-main-text/80 select-none px-1">
 <div class="flex items-center gap-4 sm:gap-6">
 <div
 v-if="summary || total !== undefined"
 class="text-main-text/55 font-bold text-[10px] capitalize tracking-[0.15em] hidden lg:block leading-none">
 {{ summary || $tr('common.index_summary', { total }) }}
 </div>

 <div v-if="perPage" class="flex items-center gap-2 sm:gap-3">
 <span
 class="text-main-text/60 text-xs capitalize font-normal tracking-wider hidden sm:inline-block">
 {{ showLabel || $tr("common.show") }}
 </span>
 <div class="relative group">
 <select
 :value="perPage"
 aria-label="Results per page"
 @change="
 (e) =>
 emit(
 'per-page-change',
 Number(
 (e.target as HTMLSelectElement).value,
 ),
 )
 "
 class="appearance-none bg-main-bg border border-card-border/60 hover:border-brand-blue/30 rounded-lg pl-3 pr-8 py-1.5 text-sm font-normal transition-all focus:outline-none focus:ring-4 focus:ring-brand-blue/5 cursor-pointer ">
 <option
 v-for="size in [10, 25, 50, 100]"
 :key="size"
 :value="size">
 {{ size }}
 </option>
 </select>
 <ChevronDown
 class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-main-text/30 pointer-events-none transition-colors group-hover:text-brand-blue" />
 </div>
 </div>
 </div>

 <!-- Navigation -->
 <nav class="flex items-center gap-1 sm:gap-2">
 <!-- Prev -->
 <button
 @click="emit('page-change', currentPage - 1)"
 :disabled="currentPage <= 1"
 :class="navBtnClass(currentPage > 1)">
 <Loader2
 v-if="loading && currentPage > 1"
 class="w-4 h-4 animate-spin shrink-0" />
 <ChevronLeft
 v-else
 class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" />
 <span
 class="text-sm font-normal hidden md:inline-block tracking-tight capitalize">
 {{ $tr("common.previous") }}
 </span>
 </button>

 <!-- Pages -->
 <div class="flex items-center gap-1.5 mx-1">
 <template v-for="(p, i) in pages" :key="i">
 <button
 v-if="p !== '...'"
 @click="emit('page-change', p)"
 class="min-w-[36px] h-9 flex items-center justify-center rounded-lg text-sm font-normal transition-all active:scale-95"
 :class="
 p === currentPage
 ? 'bg-brand-blue text-white ring-1 ring-brand-blue/20'
 : 'hover:bg-main-text/5 text-main-text/40 hover:text-main-text hover:border-card-border/60 border border-transparent'
 ">
 {{ p }}
 </button>
 <div
 v-else
 class="w-8 h-9 flex items-center justify-center text-main-text/20">
 <MoreHorizontal class="w-4 h-4" />
 </div>
 </template>
 </div>

 <!-- Next -->
 <button
 @click="emit('page-change', currentPage + 1)"
 :disabled="currentPage >= lastPage"
 :class="navBtnClass(currentPage < lastPage)">
 <span
 class="text-sm font-normal hidden md:inline-block tracking-tight capitalize">
 {{ $tr("common.next") }}
 </span>
 <Loader2
 v-if="loading && currentPage < lastPage"
 class="w-4 h-4 animate-spin shrink-0" />
 <ChevronRight
 v-else
 class="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
 </button>
 </nav>
 </div>
</template>

<style scoped>
select {
 appearance: none;
 -webkit-appearance: none;
 -moz-appearance: none;
}
</style>
