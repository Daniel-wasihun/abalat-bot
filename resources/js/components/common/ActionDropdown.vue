<script lang="ts">
import { ref as sharedRef } from "vue";
// Shared across all instances to ensure mutual exclusion
const globalActiveDropdownId = sharedRef<string | null>(null);

export default {
 name: "ActionDropdown"
}
</script>

<script setup lang="ts">
import { MoreVertical } from "lucide-vue-next";
import {
 ref,
 onMounted,
 onUnmounted,
 nextTick,
 watch,
 type CSSProperties,
} from "vue";
import Tooltip from "./Tooltip.vue";

interface Action {
 label: string;
 icon: any;
 colorClass?: string;
 hoverClass?: string;
 onClick: (item: any) => void;
 disabled?: boolean;
 tooltip?: string;
}

const props = defineProps<{
 actions: Action[];
 item: any;
}>();

// Unique identifier for this instance
const instanceId = Math.random().toString(36).substring(2, 9);

const isOpen = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref<CSSProperties>({
 top: "0px",
 left: "0px",
 minWidth: "320px",
});
const openUpward = ref(false);

const toggle = async () => {
 if (!isOpen.value) {
 globalActiveDropdownId.value = instanceId;
 isOpen.value = true;

 await nextTick();
 calculatePosition();
 } else {
 close();
 }
};

const close = () => {
 isOpen.value = false;
 if (globalActiveDropdownId.value === instanceId) {
 globalActiveDropdownId.value = null;
 }
};

const calculatePosition = () => {
 if (!triggerRef.value) return;

 const rect = triggerRef.value.getBoundingClientRect();
 const viewportHeight = window.innerHeight;
 const viewportWidth = window.innerWidth;

 const estimatedDropdownHeight = 320;
 const dropdownWidth = 320; // w-80 = 20rem = 320px

 const spaceBelow = viewportHeight - rect.bottom;
 const spaceAbove = rect.top;

 // Determine vertical direction
 if (spaceBelow >= estimatedDropdownHeight) {
 openUpward.value = false;
 } else if (spaceAbove > spaceBelow) {
 openUpward.value = true;
 } else {
 openUpward.value = false;
 }

 // Calculate absolute coordinates
 let top = 0;
 let left = rect.right - dropdownWidth; // Align to the right of the button

 if (openUpward.value) {
 top = rect.top - 8; // 8px Gap
 } else {
 top = rect.bottom + 8; // 8px Gap
 }

 // Clamp left to prevent overflow
 if (left < 10) left = 10;
 if (left + dropdownWidth > viewportWidth - 10) {
 left = viewportWidth - dropdownWidth - 10;
 }

 dropdownStyle.value = {
 top: openUpward.value ? "auto" : `${top}px`,
 bottom: openUpward.value
 ? `${viewportHeight - rect.top + 8}px`
 : "auto",
 left: `${left}px`,
 minWidth: `${dropdownWidth}px`,
 };
};

const handleClickOutside = (event: MouseEvent) => {
 if (
 triggerRef.value &&
 !triggerRef.value.contains(event.target as Node) &&
 isOpen.value
 ) {
 const dropdownElement = document.querySelector(
 `[data-dropdown-id="${instanceId.toString()}"]`,
 );
 if (
 dropdownElement &&
 !dropdownElement.contains(event.target as Node)
 ) {
 close();
 }
 }
};

// Update position on scroll/resize only if open
const handleLayoutChange = () => {
 if (isOpen.value) {
 calculatePosition();
 }
};

watch(globalActiveDropdownId, (newId) => {
 if (newId !== instanceId && isOpen.value) {
 isOpen.value = false;
 }
});

onMounted(() => {
 window.addEventListener("click", handleClickOutside);
 window.addEventListener("scroll", handleLayoutChange, { passive: true });
 window.addEventListener("resize", handleLayoutChange);
});

onUnmounted(() => {
 window.removeEventListener("click", handleClickOutside);
 window.removeEventListener("scroll", handleLayoutChange);
 window.removeEventListener("resize", handleLayoutChange);
});
</script>

<template>
 <div class="relative" ref="triggerRef">
 <!-- Single Action Button -->
 <template v-if="actions.length === 1">
 <Tooltip :text="actions[0].tooltip || actions[0].label" position="top">
 <button
 @click="actions[0].onClick(item)"
 class="p-2 rounded-lg transition-all flex items-center justify-start gap-2 border border-card-border hover:bg-main-bg/5 active:scale-95 group/btn cursor-pointer"
 :class="[
 actions[0].colorClass || 'text-main-text/70',
 {
 'opacity-50 cursor-not-allowed grayscale pointer-events-none':
 actions[0].disabled,
 },
 ]"
 :disabled="actions[0].disabled">
 <component :is="actions[0].icon" class="w-4.5 h-4.5" />
 <span class="text-sm font-normal tracking-tight">{{
 actions[0].label
 }}</span>
 </button>
 </Tooltip>
 </template>

 <!-- Multiple Actions Dropdown -->
 <template v-else-if="actions.length > 1">
 <button
 @click.stop="toggle"
 class="w-10 h-10 flex items-center justify-center text-main-text/50 hover:text-accent bg-card-bg rounded-xl transition-all border border-card-border active:scale-90 group-hover:border-accent/30 cursor-pointer"
 aria-label="Actions"
 :class="{
 'border-brand-blue/50 ring-2 ring-brand-blue/10': isOpen,
 }">
 <MoreVertical class="w-5 h-5" />
 </button>

 <Teleport to="body">
 <transition
 enter-active-class="transition duration-200 ease-out"
 :enter-from-class="
 openUpward
 ? 'opacity-0 scale-95 translate-y-2'
 : 'opacity-0 scale-95 -translate-y-2'
 "
 enter-to-class="opacity-100 scale-100 translate-y-0"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100 scale-100 translate-y-0"
 :leave-to-class="
 openUpward
 ? 'opacity-0 scale-95 translate-y-2'
 : 'opacity-0 scale-95 -translate-y-2'
 ">
 <div
 v-if="isOpen"
 :data-dropdown-id="instanceId.toString()"
 class="fixed z-[var(--z-dropdown)] w-80 bg-card-bg/95 backdrop-blur-xl rounded-xl shadow-[0_20px_40px_rgba(0,0,0,0.2)] overflow-y-auto custom-scrollbar border border-brand-blue/30 p-2 space-y-1 max-h-[300px]"
 :style="dropdownStyle">
 <Tooltip
 v-for="(action, idx) in actions"
 :key="idx"
 :text="action.tooltip || action.label"
 position="left"
 class="w-full">
 <button
 @click="
 if (!action.disabled) {
 action.onClick(item);
 close();
 }
 "
 class="w-full flex items-center justify-start gap-3 px-4 py-3 text-sm font-normal rounded-xl transition-all hover:bg-card-hover group/action cursor-pointer whitespace-nowrap"
 :class="[
 action.colorClass || 'text-main-text/70',
 action.hoverClass || '',
 {
 'opacity-50 cursor-not-allowed grayscale pointer-events-none':
 action.disabled,
 },
 ]"
 :disabled="action.disabled">
 <component
 :is="action.icon"
 class="w-4 h-4 group-hover/action:scale-110 transition-transform" />
 <span class="leading-none">{{ action.label }}</span>
 </button>
 </Tooltip>
 </div>
 </transition>
 </Teleport>
 </template>
 </div>
</template>
