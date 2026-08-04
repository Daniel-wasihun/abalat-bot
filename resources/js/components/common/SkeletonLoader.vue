<script setup lang="ts">
/**
 * SkeletonLoader — animated placeholder that mimics the shape of content
 * while real data is being fetched. Avoids jarring layout shifts.
 *
 * @example
 * <!-- Single line -->
 * <SkeletonLoader width="60%" height="16px" />
 *
 * <!-- Table-like block -->
 * <SkeletonLoader v-for="i in 5" :key="i" height="48px" class="mb-2" />
 *
 * <!-- Avatar + text combo -->
 * <div class="flex gap-3 items-center">
 * <SkeletonLoader width="40px" height="40px" rounded="full" />
 * <div class="flex-1">
 * <SkeletonLoader width="60%" height="14px" class="mb-1.5" />
 * <SkeletonLoader width="40%" height="12px" />
 * </div>
 * </div>
 */
interface Props {
 width?: string;
 height?: string;
 rounded?: "none" | "sm" | "md" | "lg" | "xl" | "full";
}

const props = withDefaults(defineProps<Props>(), {
 width: "100%",
 height: "16px",
 rounded: "md",
});

const ROUND = {
 none: "rounded-none",
 sm: "rounded-sm",
 md: "rounded-md",
 lg: "rounded-lg",
 xl: "rounded-xl",
 full: "rounded-full",
};
</script>

<template>
 <div
 class="bg-main-text/5 animate-pulse shrink-0"
 :class="ROUND[rounded]"
 :style="{ width, height }"
 aria-hidden="true" />
</template>
