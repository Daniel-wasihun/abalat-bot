<script setup lang="ts">
/**
 * Modal — Core self-contained wrapper handling dark backdrops, teleporting,
 * scroll-locks, and enter/leave animations.
 *
 * @example
 * <Modal :show="isVisible" @close="isVisible = false" title="My Modal" />
 */
import { watch, onUnmounted } from "vue";
import { X } from "lucide-vue-next";

// Teleport is the root node — Vue cannot auto-inherit attrs on it.
// Disable attr inheritance to prevent the "Extraneous non-props attributes" warning.
defineOptions({ inheritAttrs: false });

interface Props {
 show: boolean;
 title?: string;
 icon?: any;
 iconClass?: string;
 badgeClass?: string;
 size?: "sm" | "confirm" | "md" | "lg" | "xl" | "full" | "2xl";
 /** Removes padding from the inner scroll container */
 noPadding?: boolean;
 /** Hide the default header entirely */
 hideHeader?: boolean;
 /** Enable full 98vh height without border radius */
 fullHeight?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 title: "",
 size: "md",
 noPadding: false,
 hideHeader: false,
 fullHeight: false,
 iconClass: "text-brand-blue",
 badgeClass: "bg-brand-blue/10",
});

const emit = defineEmits<{ (e: "close"): void }>();

const SIZES = {
 sm: "max-w-lg",
 confirm: "max-w-lg md:max-w-xl",
 md: "max-w-3xl",
 lg: "max-w-5xl",
 xl: "max-w-[90rem]",
 "2xl": "max-w-[94vw]",
 full: "max-w-[98vw]",
};

// Lock body scrolling when modal opens, restore when it closes.
watch(
 () => props.show,
 (newVal) => {
 if (newVal) {
 document.body.style.overflow = "hidden";
 } else {
 document.body.style.overflow = "";
 }
 },
 { immediate: true },
);

onUnmounted(() => {
 document.body.style.overflow = "";
});
</script>

<template>
 <teleport to="body">
 <Transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="opacity-0 scale-95"
 enter-to-class="opacity-100 scale-100"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="opacity-100 scale-100"
 leave-to-class="opacity-0 scale-95">
 <!-- Container -->
 <div
 v-if="show"
 class="fixed inset-0 z-(--z-modal) flex items-center justify-center p-4 sm:p-8"
 style="position: fixed; top: 0; left: 0; right: 0; bottom: 0;"
 role="dialog"
 aria-modal="true">
 <!-- Backdrop -->
 <div
 class="absolute inset-0 bg-gray-900/40 dark:bg-black/50 backdrop-blur-md transition-opacity"
 @click="emit('close')"
 aria-hidden="true" />

 <!-- Modal Box -->
 <div
 class="relative w-full bg-card-bg shadow-[0_20px_50px_rgba(0,0,0,0.3)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-card-border dark:border-white/5 transition-all"
 :class="[
            SIZES[size],
            fullHeight ? 'h-[94vh] rounded-lg' : 'max-h-[92vh] rounded-lg',
            'overflow-hidden flex flex-col'
        ]"
 @click.stop>
 <!-- Header -->
 <div
 v-if="!hideHeader"
 class="px-6 py-4 flex items-center justify-between shrink-0 bg-card-bg z-20 border-b border-card-border/40">
 <slot name="header">
 <div class="flex items-center gap-2 flex-1 min-w-0">
 <div
 v-if="icon"
 class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
 :class="badgeClass">
 <component
 :is="icon"
 class="w-4.5 h-4.5"
 :class="iconClass" />
 </div>
 <h3
 class="text-[18px] font-semibold text-main-text tracking-tight truncate">
 {{ title }}
 </h3>
 </div>
 </slot>

 <button
 type="button"
 class="w-9 h-9 flex shrink-0 items-center justify-center rounded-xl bg-main-bg text-main-text/50 hover:text-rose-500 hover:bg-rose-500/10 border border-card-border/30 hover:border-rose-500/20 transition-all duration-300 focus:outline-none hover:shadow-md relative z-10"
 @click="emit('close')"
 aria-label="Close modal">
 <X class="w-4 h-4 transition-transform duration-300 hover:scale-110" />
 </button>
 </div>

 <!-- Body -->
 <div
 class="flex-1 overflow-y-auto custom-scrollbar relative z-10"
 :class="[
 noPadding ? 'p-0' : 'px-6 pb-6 pt-2',
 fullHeight ? 'h-full flex flex-col' : '',
 ]">
 <slot />
 </div>

 <!-- Footer -->
 <div v-if="$slots.footer" class="shrink-0 z-20">
 <slot name="footer" />
 </div>
 </div>
 </div>
 </Transition>
 </teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
 width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
 background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
 background: rgba(0, 0, 0, 0.1);
 border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
 background: rgba(255, 255, 255, 0.1);
}
</style>
