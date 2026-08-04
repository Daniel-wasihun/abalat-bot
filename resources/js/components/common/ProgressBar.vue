<script setup lang="ts">
/**
 * ProgressBar — a horizontal progress bar for showing percentages, quotas, or
 * completion state (e.g. "shelf capacity", "fine payment progress").
 *
 * @example
 * <ProgressBar :value="75" label="Shelf Capacity" />
 * <ProgressBar :value="finesPaid" :max="totalFines" variant="green" :showPercent="true" />
 */
import { computed } from "vue";

interface Props {
 /** Current value */
 value: number;
 /** Maximum value (defaults to 100, so value becomes percent directly) */
 max?: number;
 label?: string;
 variant?: "blue" | "green" | "yellow" | "red";
 /** Height in pixels */
 height?: number;
 showPercent?: boolean;
 /** Animate on mount */
 animate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 max: 100,
 variant: "blue",
 height: 8,
 showPercent: false,
 animate: true,
});

const TRACK = "bg-main-text/5 rounded-full overflow-hidden";
const FILL = {
 blue: "bg-brand-blue",
 green: "bg-brand-green",
 yellow: "bg-brand-yellow",
 red: "bg-rose-500",
};

const percent = computed(() =>
 Math.min(100, Math.max(0, (props.value / props.max) * 100)),
);
const fillStyle = computed(() => ({ width: `${percent.value}%` }));
</script>

<template>
 <div class="w-full">
 <!-- Header -->
 <div
 v-if="label || showPercent"
 class="flex items-center justify-between mb-1.5 px-0.5">
 <span
 v-if="label"
 class="text-[12px] text-main-text/50 font-normal"
 >{{ label }}</span
 >
 <span
 v-if="showPercent"
 class="text-[12px] font-semibold text-main-text ml-auto">
 {{ Math.round(percent) }}%
 </span>
 </div>

 <!-- Track -->
 <div :class="TRACK" :style="{ height: `${height}px` }">
 <div
 :class="[
 FILL[variant],
 animate ? 'transition-all duration-700 ease-out' : '',
 ]"
 class="h-full rounded-full"
 :style="fillStyle" />
 </div>
 </div>
</template>
