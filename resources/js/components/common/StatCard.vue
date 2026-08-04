<script setup lang="ts">
/**
 * StatCard — a compact metric display card used in dashboards and summaries.
 * Shows a label, a large number/value, an icon badge, and an optional trend line.
 *
 * @example
 * <StatCard label="Total Books" :value="1284" :icon="BookOpen" />
 * <StatCard label="Overdue" :value="12" :icon="AlertTriangle" variant="danger" :trend="-3" />
 */
import { computed } from "vue";

interface Props {
 label: string;
 value: string | number;
 /** Lucide component */
 icon?: any;
 /** Colour theme for the icon badge and accents */
 variant?: "blue" | "green" | "yellow" | "red" | "purple";
 /** Positive = good (green arrow up), negative = bad (red arrow down) */
 trend?: number;
 /** Small text beneath the trend (e.g. "vs last month") */
 trendLabel?: string;
 loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 variant: "blue",
 loading: false,
});

const VARIANT = {
 blue: {
 bg: "bg-brand-blue/10",
 icon: "text-brand-blue",
 border: "border-brand-blue/10",
 },
 green: {
 bg: "bg-brand-green/10",
 icon: "text-brand-green",
 border: "border-brand-green/10",
 },
 yellow: {
 bg: "bg-brand-yellow/10",
 icon: "text-brand-yellow",
 border: "border-brand-yellow/10",
 },
 red: {
 bg: "bg-rose-500/10",
 icon: "text-rose-500",
 border: "border-rose-500/10",
 },
 purple: {
 bg: "bg-purple-500/10",
 icon: "text-purple-500",
 border: "border-purple-500/10",
 },
};

const v = computed(() => VARIANT[props.variant]);

const trendUp = computed(() => props.trend !== undefined && props.trend > 0);
const trendDown = computed(() => props.trend !== undefined && props.trend < 0);
const trendText = computed(() => {
 if (props.trend === undefined) return "";
 return `${props.trend > 0 ? "+" : ""}${props.trend}`;
});
</script>

<template>
 <div
 class="flex items-center gap-4 bg-card-bg border border-card-border rounded-xl p-5 transition-all hover:shadow-sm">
 <!-- Icon badge -->
 <div
 v-if="icon"
 class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
 :class="v.bg">
 <component :is="icon" class="w-6 h-6" :class="v.icon" />
 </div>

 <!-- Value + label -->
 <div class="flex-1 min-w-0">
 <!-- Skeleton while loading -->
 <div v-if="loading">
 <div
 class="h-7 w-20 bg-main-text/5 rounded-lg animate-pulse mb-1.5" />
 <div class="h-3.5 w-28 bg-main-text/5 rounded animate-pulse" />
 </div>

 <template v-else>
 <div class="flex items-baseline gap-2">
 <span
 class="text-[26px] font-bold text-main-text leading-none tracking-tight">
 {{ value }}
 </span>

 <!-- Trend chip -->
 <span
 v-if="trend !== undefined"
 class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full"
 :class="
 trendUp
 ? 'bg-brand-green/10 text-brand-green'
 : trendDown
 ? 'bg-rose-500/10 text-rose-500'
 : 'bg-main-text/5 text-main-text/40'
 ">
 <span v-if="trendUp">↑</span>
 <span v-else-if="trendDown">↓</span>
 {{ trendText }}
 </span>
 </div>

 <p
 class="text-[13px] text-main-text/50 mt-1 font-normal leading-relaxed truncate">
 {{ label }}
 </p>

 <p
 v-if="trendLabel"
 class="text-[11px] text-main-text/30 mt-0.5">
 {{ trendLabel }}
 </p>
 </template>
 </div>

 <!-- Right slot (e.g. mini spark-line or extra action) -->
 <div v-if="$slots.right" class="shrink-0">
 <slot name="right" />
 </div>
 </div>
</template>
