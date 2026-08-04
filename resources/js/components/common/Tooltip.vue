<script setup lang="ts">
/**
 * Tooltip — Clean, simple, and high-visibility primitive.
 * Breaks through container overflow using Teleport + Fixed positioning.
 */
import { ref, onMounted, onUnmounted, computed } from "vue";

interface Props {
 text: string;
 position?: "top" | "bottom" | "left" | "right";
 width?: string;
}

const props = withDefaults(defineProps<Props>(), {
 position: "bottom",
 width: "max-w-[300px]",
});

const triggerRef = ref<HTMLElement | null>(null);
const isVisible = ref(false);
const coords = ref({ top: 0, left: 0 });

const updatePosition = () => {
 if (!triggerRef.value) return;
 const rect = triggerRef.value.getBoundingClientRect();
 
 // Simplified fixed coordinate calculation
 const offset = 8;
 const pos = {
 top: { x: rect.left + rect.width / 2, y: rect.top - offset },
 bottom: { x: rect.left + rect.width / 2, y: rect.bottom + offset },
 left: { x: rect.left - offset, y: rect.top + rect.height / 2 },
 right: { x: rect.right + offset, y: rect.top + rect.height / 2 }
 }[props.position];

 coords.value = { top: pos.y, left: pos.x };
};

const handleHover = (show: boolean) => {
 if (show) updatePosition();
 isVisible.value = show;
};

// Update on scroll/resize to stay attached to trigger
onMounted(() => window.addEventListener("scroll", updatePosition, { capture: true, passive: true }));
onUnmounted(() => window.removeEventListener("scroll", updatePosition, { capture: true }));
</script>

<template>
 <div 
 ref="triggerRef" 
 class="relative inline-flex items-center cursor-help"
 @mouseenter="handleHover(true)"
 @mouseleave="handleHover(false)"
 >
 <slot />

 <Teleport to="body">
 <transition name="fade">
 <div 
 v-if="isVisible" 
 class="fixed z-[100000] pointer-events-none"
 :class="[
 props.width,
 {
 '-translate-x-1/2 -translate-y-full': position === 'top',
 '-translate-x-1/2': position === 'bottom',
 '-translate-x-full -translate-y-1/2': position === 'left',
 '-translate-y-1/2': position === 'right',
 }
 ]"
 :style="{ top: `${coords.top}px`, left: `${coords.left}px` }"
 >
 <div class="bg-slate-900/98 backdrop-blur-md text-white text-xs font-normal px-3 py-1.5 rounded-lg border border-white/10 shadow-xl relative text-center">
 {{ text }}
 <!-- Tiny Arrow -->
 <div 
 class="absolute w-2 h-2 bg-slate-900/98 rotate-45"
 :class="{
 'bottom-[-4px] left-1/2 -translate-x-1/2': position === 'top',
 'top-[-4px] left-1/2 -translate-x-1/2': position === 'bottom',
 'right-[-4px] top-1/2 -translate-y-1/2': position === 'left',
 'left-[-4px] top-1/2 -translate-y-1/2': position === 'right'
 }"
 />
 </div>
 </div>
 </transition>
 </Teleport>
 </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s linear; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
