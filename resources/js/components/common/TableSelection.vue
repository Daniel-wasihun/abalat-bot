<script setup lang="ts">
import { X, CheckSquare } from "lucide-vue-next";

/**
 * TableSelection — Re-constructed standardized Selection Management Bar.
 * Exact visual parity with BaseSelectionBar legacy styling but updated for Premium UX.
 * Optimized for right-aligned bulk action buttons.
 */

interface Props {
 count: number;
 label?: string;
 description?: string;
 clearLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
 count: 0,
 label: "Selected",
 description: "Bulk actions are available for these items",
 clearLabel: "Clear Selection",
});

const emit = defineEmits(["clear"]);
</script>

<template>
 <Transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="transform -translate-y-2 opacity-0"
 enter-to-class="transform translate-y-0 opacity-100"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="transform translate-y-0 opacity-100"
 leave-to-class="transform -translate-y-2 opacity-0">
 <div
 v-if="count > 0"
 class="z-45 bg-card-bg px-6 py-2.5 flex flex-nowrap items-center justify-between gap-6 border-b border-card-border/60 sticky top-0 overflow-visible">
 <!-- Info Section (Legacy Visual Parity) -->
 <div class="flex items-center gap-6 shrink-0">
 <div class="flex flex-col">
 <span
 class="text-base font-normal text-main-text leading-tight">
 {{ count }} {{ label }}
 </span>
 <span
 class="text-[11px] font-normal text-main-text/40 tracking-tight leading-none mt-1">
 {{ description }}
 </span>
 </div>

 <!-- Professional Separator -->
 <div class="w-px h-8 bg-card-border/60 hidden md:block"></div>
 </div>

 <!-- Action Spacer (Drives buttons to the right edge) -->
 <div class="flex-1"></div>

 <!-- Actions Container (Custom Buttons) -->
 <div
 class="flex items-center gap-3 md:gap-4 overflow-x-auto custom-scrollbar-hide py-1 shrink-0">
 <slot />
 </div>

 <!-- Clear Action -->
 <div class="pl-4 shrink-0">
 <button
 @click="emit('clear')"
 class="group p-2 rounded-lg bg-main-text/5 hover:bg-rose-500/10 text-main-text/30 hover:text-rose-500 transition-all duration-300 active:scale-95"
 :title="clearLabel">
 <X class="w-4 h-4" />
 </button>
 </div>
 </div>
 </Transition>
</template>

<style scoped>
/* Hidden scrollbar but keeps scrollability for many buttons */
.custom-scrollbar-hide::-webkit-scrollbar {
 display: none;
}
.custom-scrollbar-hide {
 -ms-overflow-style: none;
 scrollbar-width: none;
}
</style>
