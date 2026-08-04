<script setup lang="ts">
/**
 * Divider — a horizontal (or vertical) rule with an optional centred label.
 * Used to separate form sections or groups of related fields.
 *
 * @example
 * <Divider />
 * <Divider label="Or continue with" />
 * <Divider label="Contact Details" align="left" />
 */
interface Props {
 label?: string;
 align?: "left" | "center" | "right";
 /** Remove vertical margin when inside a tightly packed layout */
 compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 align: "center",
 compact: false,
});
</script>

<template>
 <div
 class="flex items-center gap-3 w-full"
 :class="compact ? 'my-3' : 'my-6'">
 <!-- Left line (always present) -->
 <div
 class="flex-1 h-px bg-card-border"
 :class="align === 'right' ? 'flex-[3]' : ''" />

 <!-- Label -->
 <span
 v-if="label"
 class="text-[11px] font-medium text-main-text/40 capitalize tracking-wider whitespace-nowrap shrink-0">
 {{ label }}
 </span>

 <!-- Right line (only when there's a label) -->
 <div
 v-if="label"
 class="flex-1 h-px bg-card-border"
 :class="align === 'left' ? 'flex-[3]' : ''" />
 </div>
</template>
