<script setup lang="ts">
/**
 * PaginationBar — Simple UI logic to render Next, Previous, and numbered pages.
 * Highly reusable. Just attach standard handlers to your API response logic.
 *
 * @example
 * <PaginationBar
 * :current-page="3"
 * :last-page="10"
 * :total="150"
 * @change="fetchPage"
 * />
 */
import { computed } from "vue";
import { ChevronLeft, ChevronRight, MoreHorizontal } from "lucide-vue-next";

const props = defineProps<{
 currentPage: number;
 lastPage: number;
 total?: number;
}>();

const emit = defineEmits<{
 (e: "change", page: number): void;
}>();

// Generate an intelligent array of pages to show (e.g. [1, "...", 4, 5, 6, "...", 10])
const pagesArray = computed(() => {
 const current = props.currentPage;
 const last = props.lastPage;

 if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);

 if (current <= 4) return [1, 2, 3, 4, 5, "...", last];
 if (current >= last - 3)
 return [1, "...", last - 4, last - 3, last - 2, last - 1, last];

 return [1, "...", current - 1, current, current + 1, "...", last];
});

function goTo(page: number | string) {
 if (typeof page === "number" && page !== props.currentPage) {
 emit("change", page);
 }
}
</script>

<template>
 <div class="flex items-center justify-between w-full">
 <!-- Total context -->
 <div class="text-[12px] text-main-text/50">
 <span v-if="total !== undefined"
 >Total <strong>{{ total }}</strong> records</span
 >
 </div>

 <!-- Controls -->
 <div v-if="lastPage > 1" class="flex items-center gap-1">
 <button
 type="button"
 :disabled="currentPage === 1"
 class="w-8 h-8 flex items-center justify-center rounded border border-card-border text-main-text/60 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-main-text/5 hover:text-main-text transition-colors"
 @click="goTo(currentPage - 1)">
 <ChevronLeft class="w-4 h-4" />
 </button>

 <button
 v-for="(page, i) in pagesArray"
 :key="i"
 type="button"
 class="min-w-[32px] h-8 px-2 flex items-center justify-center rounded text-[12px] font-medium transition-colors"
 :class="[
 page === '...'
 ? 'text-main-text/30 cursor-default cursor-not-allowed'
 : page === currentPage
 ? 'bg-brand-blue text-white border border-brand-blue'
 : 'border border-card-border text-main-text/60 hover:bg-main-text/5 hover:text-main-text cursor-pointer',
 ]"
 :disabled="page === '...'"
 @click="goTo(page)">
 <MoreHorizontal v-if="page === '...'" class="w-4 h-4" />
 <span v-else>{{ page }}</span>
 </button>

 <button
 type="button"
 :disabled="currentPage === lastPage"
 class="w-8 h-8 flex items-center justify-center rounded border border-card-border text-main-text/60 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-main-text/5 hover:text-main-text transition-colors"
 @click="goTo(currentPage + 1)">
 <ChevronRight class="w-4 h-4" />
 </button>
 </div>
 </div>
</template>
