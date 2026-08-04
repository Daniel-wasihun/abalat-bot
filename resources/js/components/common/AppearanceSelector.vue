<script setup lang="ts">
/**
 * AppearanceSelector — Reusable book condition/damage picker.
 * Used in all circulation return flows across QuickScanModal,
 * ReturnBookModal, and ReturnProcessModal.
 *
 * @example
 * <AppearanceSelector v-model:severity="damageSeverity" v-model:is-lost="isLost" />
 */
import { CheckCircle2, Info, AlertTriangle } from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps<{
 severity: "none" | "minor" | "major" | "critical";
 isLost: boolean;
 /** "chips" = compact horizontal row. "cards" = 2×2 big cards (legacy) */
 layout?: "chips" | "cards";
 /** Label shown above the selector */
 label?: string;
}>();

const emit = defineEmits<{
 "update:severity": [value: "none" | "minor" | "major" | "critical"];
 "update:isLost": [value: boolean];
}>();

const options = computed(() => [
 {
 value: "none" as const,
 label: "No Damage",
 icon: CheckCircle2,
 color: "emerald",
 },
 {
 value: "minor" as const,
 label: "Minor Damage",
 icon: Info,
 color: "amber",
 },
 {
 value: "major" as const,
 label: "Major Damage",
 icon: AlertTriangle,
 color: "orange",
 },
 {
 value: "critical" as const,
 label: "Critical Damage",
 icon: AlertTriangle,
 color: "rose",
 },
]);

const selectSeverity = (val: "none" | "minor" | "major" | "critical") => {
 emit("update:severity", val);
 emit("update:isLost", false);
};

const toggleLost = (checked: boolean) => {
 emit("update:isLost", checked);
 if (checked) emit("update:severity", "none");
};

const isActive = (val: string) =>
 props.severity === val && !props.isLost;
</script>

<template>
 <div class="space-y-2">
 <!-- Label -->
 <p v-if="label" class="text-[13px] font-semibold text-main-text/50 tracking-tight capitalize">
 {{ label }}
 </p>

 <!-- Condition Chips (2×2 grid) -->
 <div class="grid grid-cols-2 gap-2">
 <button
 v-for="opt in options"
 :key="opt.value"
 type="button"
 @click="selectSeverity(opt.value)"
 class="flex items-center gap-2 px-3 h-11 rounded-xl border-2 text-left transition-all duration-200"
 :class="[
 isActive(opt.value)
 ? 'bg-amber-500/10 border-amber-500'
 : 'bg-main-bg border-card-border/60 hover:border-amber-500/30',
 ]">
 <component
 :is="opt.icon"
 class="w-4 h-4 shrink-0"
 :class="isActive(opt.value) ? 'text-amber-600' : 'text-main-text/40'" />
 <span
 class="text-[13px] font-semibold capitalize leading-tight"
 :class="isActive(opt.value) ? 'text-amber-700' : 'text-main-text/60'">
 {{ opt.label }}
 </span>
 </button>
 </div>

 <!-- Lost Asset Toggle -->
 <label
 class="flex items-center gap-3 px-3 h-11 rounded-xl border border-rose-500/15 bg-rose-500/5 cursor-pointer hover:bg-rose-500/10 transition-all">
 <AlertTriangle class="w-4 h-4 text-rose-500 shrink-0" />
 <div class="flex-1 min-w-0">
 <p class="text-[13px] font-semibold text-rose-700 leading-tight">
 Mark as Permanent Asset Loss
 </p>
 <p class="text-[10px] text-rose-500/70 mt-0.5">
 This will deactivate the barcode and trigger loss penalties.
 </p>
 </div>
 <input
 type="checkbox"
 :checked="isLost"
 @change="toggleLost(($event.target as HTMLInputElement).checked)"
 class="w-5 h-5 rounded-md text-rose-600 border-rose-400/40 focus:ring-rose-500 shrink-0" />
 </label>
 </div>
</template>
