<script setup lang="ts">
/**
 * EmptyState — shown when a table/list has no data to display.
 * Supports a title, description, icon, and an optional call-to-action button.
 *
 * @example
 * <EmptyState
 * title="No books found"
 * description="Try adjusting your search or filters."
 * :icon="BookOpen"
 * >
 * <template #action>
 * <Button @click="openAddModal">Add Book</Button>
 * </template>
 * </EmptyState>
 */
import { computed } from "vue";
import { Inbox } from "lucide-vue-next";

interface Props {
 title?: string;
 description?: string;
 /** Lucide component or any renderable icon */
 icon?: any;
 /** Pre-defined visual style */
 variant?: "default" | "search" | "error";
}

const props = withDefaults(defineProps<Props>(), {
 variant: "default",
});

const ICON_BG: Record<string, string> = {
 default: "bg-brand-blue/8",
 search: "bg-brand-yellow/8",
 error: "bg-rose-500/8",
};
const ICON_COLOR: Record<string, string> = {
 default: "text-brand-blue/40",
 search: "text-brand-yellow/60",
 error: "text-rose-400",
};

const iconBg = computed(() => ICON_BG[props.variant]);
const iconColor = computed(() => ICON_COLOR[props.variant]);
const displayIcon = computed(() => props.icon ?? Inbox);
</script>

<template>
 <div
 class="flex flex-col items-center justify-center py-16 px-6 text-center">
 <!-- Icon circle -->
 <div
 class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5"
 :class="iconBg">
 <component :is="displayIcon" class="w-8 h-8" :class="iconColor" />
 </div>

 <!-- Title -->
 <h3
 class="text-[15px] font-semibold text-main-text tracking-tight mb-1">
 <slot name="title">{{ title || "Nothing here yet" }}</slot>
 </h3>

 <!-- Description -->
 <p class="text-[13px] text-main-text/40 max-w-xs leading-relaxed">
 <slot name="description">{{ description }}</slot>
 </p>

 <!-- Optional action (e.g. "Add first item" button) -->
 <div v-if="$slots.action" class="mt-6">
 <slot name="action" />
 </div>
 </div>
</template>
