<script setup lang="ts">
/**
 * StatusBadge — a small colored pill that communicates record status at a glance.
 * Covers the common LMS statuses: active, inactive, pending, overdue, returned, lost, etc.
 *
 * @example
 * <StatusBadge status="active" />
 * <StatusBadge status="overdue" :pulse="true" />
 * <StatusBadge label="Custom" color="blue" />
 */
import { computed, getCurrentInstance } from "vue";

// Pre-defined status → colour mapping used everywhere in the LMS
const STATUS_MAP: Record<string, { base: string; dot: string; label: string }> =
 {
 active: {
 base: "bg-brand-green/10 text-brand-green border-brand-green/20",
 dot: "bg-brand-green",
 label: "Active",
 },
 inactive: {
 base: "bg-main-text/5 text-main-text/50 border-main-text/10",
 dot: "bg-main-text/30",
 label: "Inactive",
 },
 pending: {
 base: "bg-brand-yellow/10 text-brand-yellow border-brand-yellow/20",
 dot: "bg-brand-yellow",
 label: "Pending",
 },
 overdue: {
 base: "bg-rose-500/10 text-rose-500 border-rose-500/20",
 dot: "bg-rose-500",
 label: "Overdue",
 },
 borrowed: {
 base: "bg-brand-blue/10 text-brand-blue border-brand-blue/20",
 dot: "bg-brand-blue",
 label: "Borrowed",
 },
 returned: {
 base: "bg-brand-green/10 text-brand-green border-brand-green/20",
 dot: "bg-brand-green",
 label: "Returned",
 },
 lost: {
 base: "bg-rose-500/10 text-rose-500 border-rose-500/20",
 dot: "bg-rose-500",
 label: "Lost",
 },
 reserved: {
 base: "bg-purple-500/10 text-purple-500 border-purple-500/20",
 dot: "bg-purple-500",
 label: "Reserved",
 },
 available: {
 base: "bg-brand-green/10 text-brand-green border-brand-green/20",
 dot: "bg-brand-green",
 label: "Available",
 },
 damaged: {
 base: "bg-brand-yellow/10 text-brand-yellow border-brand-yellow/20",
 dot: "bg-brand-yellow",
 label: "Damaged",
 },
 cancelled: {
 base: "bg-main-text/5 text-main-text/50 border-main-text/10",
 dot: "bg-main-text/30",
 label: "Cancelled",
 },
 perfect: {
 base: "bg-emerald-500/10 text-emerald-600 border-emerald-500/20",
 dot: "bg-emerald-500",
 label: "Perfect",
 },
 minor: {
 base: "bg-amber-500/10 text-amber-600 border-amber-500/20",
 dot: "bg-amber-500",
 label: "Minor Damage",
 },
 major: {
 base: "bg-orange-500/10 text-orange-600 border-orange-500/20",
 dot: "bg-orange-500",
 label: "Major Damage",
 },
 critical: {
 base: "bg-rose-500/10 text-rose-500 border-rose-500/20",
 dot: "bg-rose-500",
 label: "Critical Damage",
 },
 in_library: {
 base: "bg-emerald-500/10 text-emerald-600 border-emerald-500/20",
 dot: "bg-emerald-500",
 label: "In Library",
 },
 on_loan: {
 base: "bg-amber-500/10 text-amber-600 border-amber-500/20",
 dot: "bg-amber-500",
 label: "On Loan",
 },
 borrowable: {
 base: "bg-emerald-500/10 text-emerald-600 border-emerald-500/20",
 dot: "bg-emerald-500",
 label: "Borrowable",
 },
 library_only: {
 base: "bg-amber-500/10 text-amber-600 border-amber-500/20",
 dot: "bg-amber-500",
 label: "Library Only",
 },
 requested: {
 base: "bg-amber-500/10 text-amber-600 border-amber-500/20",
 dot: "bg-amber-500",
 label: "Requested",
 },
 rejected: {
 base: "bg-slate-500/10 text-slate-500 border-slate-500/20",
 dot: "bg-slate-500",
 label: "Rejected",
 },
 expired: {
 base: "bg-slate-500/5 text-slate-400 border-slate-500/10",
 dot: "bg-slate-300",
 label: "Expired",
 },
 };

interface Props {
 /** A pre-defined status key (see STATUS_MAP above) */
 status?: string;
 /** Override the displayed text */
 label?: string;
 /** Custom Tailwind colour token (e.g. "sky-400") – used when status is not in STATUS_MAP */
 color?: string;
 /** Show a pulsing animated dot – useful for live/overdue statuses */
 pulse?: boolean;
 /** Visual size */
 size?: "sm" | "md";
 /** Dot only, no text label */
 dotOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 size: "md",
 pulse: false,
 dotOnly: false,
});

const { proxy } = getCurrentInstance() as any;
const $tr = proxy?.$tr;

const config = computed(() => {
 if (props.status && STATUS_MAP[props.status])
 return STATUS_MAP[props.status];
 // Fallback for unknown statuses — neutral gray
 return {
 base: "bg-main-text/5 text-main-text/50 border-main-text/10",
 dot: "bg-main-text/40",
 label: props.label ?? props.status ?? "—",
 };
});

const displayLabel = computed(() => {
 if (props.label) return props.label;
 if (!props.status || !STATUS_MAP[props.status]) return config.value.label;

 // Use a robust mapping for common status translation keys
 const translationKeyMap: Record<string, string> = {
 active: "user.active",
 inactive: "user.inactive",
 pending: "common.pending",
 overdue: "borrow.status.overdue",
 borrowed: "borrow.status.borrowed",
 returned: "borrow.status.returned", // Fixed in backend files too
 lost: "borrow.status.lost",
 reserved: "library.status_reserved",
 available: "borrow.status.available",
 damaged: "borrow.status.damaged",
 requested: "borrow.status.requested",
 expired: "borrow.status.expired",
 in_library: "library.status_available", // Using standard backend key
 on_loan: "library.status_borrowed", // Using standard backend key
 perfect: "library.perfect",
 minor: "library.minor_damage",
 major: "library.major_damage",
 critical: "library.critical_damage",
 };

 const key = translationKeyMap[props.status];
 if (key && $tr) {
 return $tr(key, STATUS_MAP[props.status].label);
 }

 return STATUS_MAP[props.status].label;
});

const badgeClasses = computed(() => [
 "inline-flex items-center gap-1.5 font-normal border rounded-full text-xs!",
 config.value.base,
 props.size === "sm" ? "text-[10px] px-2 py-0.5" : "text-[11px] px-2.5 py-1",
]);
</script>

<template>
 <span :class="badgeClasses">
 <!-- Status dot -->
 <span
 class="shrink-0 rounded-full"
 :class="[
 config.dot,
 pulse ? 'animate-pulse' : '',
 size === 'sm' ? 'w-1.5 h-1.5' : 'w-2 h-2',
 ]" />
 <span v-if="!dotOnly">{{ displayLabel }}</span>
 </span>
</template>
