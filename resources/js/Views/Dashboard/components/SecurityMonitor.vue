<script setup lang="ts">
import { useDashboardStore } from "@/stores/dashboardStore";
import { useLanguageStore } from "@/stores/languageStore";
import { computed, getCurrentInstance } from "vue";
import { Shield, AlertTriangle, Ban, Radio } from "lucide-vue-next";

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
const { localize } = useLanguageStore();

const dashboardStore = useDashboardStore();
const sec = computed(() => dashboardStore.stats?.security);

const threatMax = computed(() => {
 if (!sec.value?.daily_trend?.length) return 1;
 return Math.max(...sec.value.daily_trend.map((d) => d.count), 1);
});

const severityLabels: Record<number, { label: string; color: string }> = {
 1: { label: "dashboard.threat_low", color: "bg-accent" },
 2: { label: "dashboard.threat_medium", color: "bg-brand-yellow" },
 3: { label: "dashboard.threat_high", color: "bg-brand-red" },
 4: { label: "dashboard.threat_high", color: "bg-brand-red" },
 5: { label: "dashboard.threat_high", color: "bg-brand-red" },
};

const sevMax = computed(() => {
 if (!sec.value?.by_severity?.length) return 1;
 return Math.max(...sec.value.by_severity.map((s) => s.count), 1);
});
</script>

<template>
 <div class="premium-card overflow-hidden border-brand-red/10">
 <template v-if="sec">
 <div
 class="px-6 py-5 border-b border-brand-red/10 bg-brand-red/2 flex items-center justify-between flex-wrap gap-4">
 <div class="flex items-center gap-3">
 <div
 class="w-10 h-10 rounded-xl bg-brand-red/10 flex items-center justify-center border border-brand-red/20 relative">
 <Shield class="w-5 h-5 text-brand-red" />
 <span
 v-if="sec.today > 0"
 class="absolute -top-1 -right-1 w-4 h-4 bg-brand-red rounded-full flex items-center justify-center text-xs text-white font-semibold animate-pulse">
 {{ sec.today }}
 </span>
 </div>
 <div>
 <h3
 class="text-sm font-medium text-main-text leading-none mb-1">
 {{ $tr("dashboard.security.title") }}
 </h3>
 <p class="text-xs text-main-text/40 font-normal">
 {{ $tr("dashboard.security_alerts") }}
 </p>
 </div>
 </div>

 <!-- KPI badges -->
 <div class="flex items-center gap-2">
 <span
 class="px-2.5 py-1 rounded-full bg-brand-red/10 text-brand-red text-xs font-semibold flex items-center gap-1">
 <AlertTriangle class="w-3 h-3" />
 {{ sec.total_threats }}
 {{ $tr("common.total") || "total" }}
 </span>
 <span
 class="px-2.5 py-1 rounded-full bg-brand-yellow/10 text-brand-yellow text-xs font-semibold">
 {{ sec.critical }} {{ $tr("dashboard.threat_high") }}
 </span>
 </div>
 </div>

 <div
 class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-card-border transition-all duration-500">
 <!-- Left: Trends & Severity -->
 <div class="p-6 space-y-6">
 <!-- 7-day trend -->
 <div>
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-3 flex items-center gap-1.5">
 <Radio class="w-3 h-3 text-brand-red" />
 {{ $tr("dashboard.daily_traffic") }}
 </p>
 <div class="flex items-end gap-2 h-20">
 <div
 v-for="(d, idx) in sec.daily_trend"
 :key="idx"
 class="flex flex-col items-center gap-1.5 flex-1 group">
 <div
 class="bg-main-text text-main-bg px-2 py-0.5 rounded text-xs font-semibold opacity-0 group-hover:opacity-100 transition-all pointer-events-none">
 {{ d.count }}
 </div>
 <div
 class="w-full max-w-[12px] h-14 bg-brand-red/10 rounded-full relative overflow-hidden">
 <div
 class="absolute bottom-0 w-full bg-brand-red rounded-full transition-all duration-700"
 :style="{
 height: `${(d.count / threatMax) * 100}%`,
 }"></div>
 </div>
 <span
 class="text-xs text-main-text/25 group-hover:text-main-text/50 transition-colors">
 {{ d.day }}
 </span>
 </div>
 </div>
 </div>

 <!-- Severity Breakdown -->
 <div>
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-3">
 {{ $tr("dashboard.threat_level") }}
 </p>
 <div class="space-y-2.5">
 <div
 v-for="sev in sec.by_severity"
 :key="sev.level"
 class="group cursor-pointer">
 <div
 class="flex items-center justify-between text-xs mb-1">
 <span
 class="flex items-center gap-1.5 font-medium">
 <span
 :class="[
 severityLabels[sev.level]
 ?.color || 'bg-accent',
 'w-2 h-2 rounded-full',
 ]"></span>
 <span class="text-main-text/60">{{
 $tr(
 severityLabels[sev.level]
 ?.label,
 ) || `Level ${sev.level}`
 }}</span>
 </span>
 <span
 class="text-main-text font-semibold tabular-nums"
 >{{ sev.count }}</span
 >
 </div>
 <div
 class="h-1 w-full bg-main-text/[0.04] rounded-full overflow-hidden">
 <div
 :class="[
 severityLabels[sev.level]?.color ||
 'bg-accent',
 'h-full rounded-full transition-all duration-700',
 ]"
 :style="{
 width: `${(sev.count / sevMax) * 100}%`,
 }"></div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <!-- Right: Recent Events & Attack Types -->
 <div class="p-6 space-y-6">
 <!-- Attack Types -->
 <div v-if="sec.by_type?.length">
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-3">
 {{ $tr("dashboard.quick_actions") }}
 </p>
 <div class="space-y-2">
 <div
 v-for="t in sec.by_type"
 :key="t.type"
 class="flex items-center justify-between text-xs px-3 py-2 rounded-lg border border-card-border bg-main-text/[0.01]">
 <span
 class="text-main-text/60 capitalize font-normal"
 >{{ t.type }}</span
 >
 <span
 class="font-semibold text-main-text tabular-nums"
 >{{ t.count }}</span
 >
 </div>
 </div>
 </div>

 <!-- Recent Events -->
 <div>
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-3">
 {{ $tr("dashboard.recent_activity") }}
 </p>
 <div class="space-y-2">
 <div
 v-for="ev in sec.recent_events"
 :key="ev.id"
 class="flex items-start gap-3 text-xs group cursor-pointer">
 <span
 :class="[
 'w-1.5 h-1.5 rounded-full mt-1 shrink-0',
 ev.severity >= 4
 ? 'bg-brand-red'
 : ev.severity >= 3
 ? 'bg-brand-yellow'
 : 'bg-accent',
 ]"></span>
 <div class="flex-1 min-w-0">
 <p
 class="text-main-text/60 font-normal truncate group-hover:text-main-text transition-colors capitalize">
 {{ ev.type.replace(/_/g, " ") }}
 </p>
 <p class="text-main-text/25 text-xs">
 {{ ev.ip }} · {{ ev.time }}
 </p>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </template>

 <!-- Skeleton State -->
 <template v-else>
 <div
 class="px-6 py-5 border-b border-card-border flex items-center justify-between animate-pulse">
 <div class="flex items-center gap-3">
 <div
 class="w-10 h-10 rounded-xl bg-main-text/10 border border-card-border"></div>
 <div>
 <div
 class="h-4 w-32 bg-main-text/10 rounded mb-2"></div>
 <div class="h-3 w-24 bg-main-text/5 rounded"></div>
 </div>
 </div>
 </div>
 <div
 class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-card-border animate-pulse">
 <div class="p-6 space-y-8">
 <div class="space-y-4">
 <div class="h-3 w-24 bg-main-text/5 rounded"></div>
 <div class="flex items-end gap-2 h-16 pt-2">
 <div
 v-for="i in 7"
 :key="i"
 class="flex-1 bg-main-text/10 rounded-full h-12"></div>
 </div>
 </div>
 <div class="space-y-4">
 <div class="h-3 w-28 bg-main-text/5 rounded"></div>
 <div v-for="i in 3" :key="i" class="space-y-2">
 <div
 class="h-3 w-full bg-main-text/5 rounded"></div>
 <div
 class="h-1 w-full bg-main-text/5 rounded"></div>
 </div>
 </div>
 </div>
 <div class="p-6 space-y-8">
 <div class="space-y-2">
 <div
 v-for="i in 3"
 :key="i"
 class="h-8 w-full bg-main-text/5 rounded-lg border border-card-border"></div>
 </div>
 <div class="space-y-4">
 <div v-for="i in 3" :key="i" class="flex gap-3">
 <div
 class="w-2 h-2 rounded-full bg-main-text/10 mt-1"></div>
 <div class="flex-1 space-y-2">
 <div
 class="h-3 w-2/3 bg-main-text/5 rounded"></div>
 <div
 class="h-2 w-1/3 bg-main-text/5 rounded"></div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </template>
 </div>
</template>
