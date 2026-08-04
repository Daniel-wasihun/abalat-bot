<script setup lang="ts">
import { useDashboardStore } from "@/stores/dashboardStore";
import { useLanguageStore } from "@/stores/languageStore";
import { computed, getCurrentInstance } from "vue";
import { Users, UserCheck, UserX, Shield, PieChart } from "lucide-vue-next";

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
const { localize } = useLanguageStore();

const dashboardStore = useDashboardStore();
const demo = computed(() => dashboardStore.stats?.users);

const roleMax = computed(() => {
 if (!demo.value?.by_role?.length) return 1;
 return Math.max(...demo.value.by_role.map((r: any) => r.count), 1);
});

const regMax = computed(() => {
 if (!demo.value?.registration_trend?.length) return 1;
 return Math.max(
 ...demo.value.registration_trend.map((r: any) => r.count),
 1,
 );
});

const genderColors: Record<string, string> = {
 Male: "bg-accent",
 Female: "bg-brand-yellow",
 Other: "bg-brand-red",
};

const typeColors: Record<string, string> = {
 Student: "bg-accent",
 Teacher: "bg-brand-green",
 Staff: "bg-brand-yellow",
 Faculty: "bg-brand-red",
};
</script>

<template>
 <div class="premium-card overflow-hidden">
 <template v-if="demo">
 <div
 class="px-6 py-5 border-b border-card-border flex items-center justify-between flex-wrap gap-4">
 <div class="flex items-center gap-3">
 <div
 class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center border border-accent/20">
 <Users class="w-5 h-5 text-accent" />
 </div>
 <div>
 <h3
 class="text-sm font-medium text-main-text leading-none mb-1">
 {{ $tr("dashboard.metrics.active_users") }}
 </h3>
 <p class="text-xs text-main-text/40 font-normal">
 {{ $tr("dashboard.user_activity_report") }}
 </p>
 </div>
 </div>

 <!-- Status badges -->
 <div class="flex items-center gap-3 text-xs">
 <span
 class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-accent/10">
 <UserCheck class="w-3 h-3 text-accent" />
 <span class="font-medium text-accent">{{
 demo.active
 }}</span>
 </span>
 <span
 class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-red/10">
 <UserX class="w-3 h-3 text-brand-red" />
 <span class="font-medium text-brand-red">{{
 demo.inactive
 }}</span>
 </span>
 </div>
 </div>

 <div
 class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-card-border">
 <!-- Role Distribution -->
 <div class="p-6">
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-4 flex items-center gap-1.5">
 <Shield class="w-3 h-3" /> {{ $tr("common.roles") }}
 </p>
 <div class="space-y-3">
 <div
 v-for="role in demo.by_role"
 :key="String(role.name)"
 class="group cursor-pointer">
 <div
 class="flex items-center justify-between text-xs mb-1">
 <span
 class="text-main-text/60 font-normal capitalize group-hover:text-main-text transition-colors">
 {{ localize(role.name) }}
 </span>
 <span
 class="font-medium text-main-text tabular-nums"
 >{{ role.count }}</span
 >
 </div>
 <div
 class="h-1 w-full bg-main-text/[0.04] rounded-full overflow-hidden">
 <div
 class="h-full bg-accent rounded-full transition-all duration-700 group-hover:bg-accent-hover"
 :style="{
 width: `${(role.count / roleMax) * 100}%`,
 }"></div>
 </div>
 </div>
 </div>
 </div>

 <!-- Gender & Type -->
 <div class="p-6 space-y-6">
 <!-- Gender donut -->
 <div>
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-3">
 Gender Split
 </p>
 <div class="flex items-center gap-4">
 <div class="relative w-20 h-20 shrink-0">
 <svg
 viewBox="0 0 36 36"
 class="w-full h-full -rotate-90">
 <circle
 cx="18"
 cy="18"
 r="14"
 fill="none"
 stroke="currentColor"
 class="text-main-text/[0.04]"
 stroke-width="4" />
 <circle
 v-for="(g, idx) in demo.by_gender"
 :key="idx"
 cx="18"
 cy="18"
 r="14"
 fill="none"
 :stroke="
 g.label === 'Male'
 ? 'var(--accent)'
 : 'var(--brand-yellow)'
 "
 stroke-width="4"
 :stroke-dasharray="`${(g.count / demo.total) * 88} ${88 - (g.count / demo.total) * 88}`"
 :stroke-dashoffset="`${idx === 0 ? 0 : -(demo.by_gender[0].count / demo.total) * 88}`"
 stroke-linecap="round" />
 </svg>
 </div>
 <div class="space-y-2">
 <div
 v-for="g in demo.by_gender"
 :key="g.label"
 class="flex items-center gap-2 text-xs">
 <span
 :class="[
 genderColors[g.label] ||
 'bg-gray-500',
 'w-2 h-2 rounded-full',
 ]"></span>
 <span class="text-main-text/50">{{
 g.label
 }}</span>
 <span
 class="font-semibold text-main-text ml-auto tabular-nums"
 >{{ g.count }}</span
 >
 </div>
 </div>
 </div>
 </div>

 <!-- Account Type -->
 <div>
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-3">
 {{ $tr("dashboard.account_security") }}
 </p>
 <div class="flex gap-2 flex-wrap">
 <div
 v-for="t in demo.by_type"
 :key="t.label"
 class="px-3 py-2 rounded-lg border border-card-border bg-main-text/2 text-center flex-1 min-w-[80px]">
 <p
 class="text-sm font-semibold text-main-text tabular-nums">
 {{ t.count }}
 </p>
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest mt-0.5">
 {{ t.label }}
 </p>
 </div>
 </div>
 </div>
 </div>

 <!-- Registration Trend -->
 <div class="p-6">
 <p
 class="text-xs text-main-text/40 capitalize tracking-widest font-medium mb-4">
 {{ $tr("dashboard.registration_trend") }}
 </p>
 <div class="flex items-end gap-2 h-28">
 <div
 v-for="(point, idx) in demo.registration_trend"
 :key="idx"
 class="flex flex-col items-center gap-2 flex-1 group">
 <div
 class="bg-main-text text-main-bg px-2 py-1 rounded text-xs font-semibold opacity-0 group-hover:opacity-100 transition-all pointer-events-none">
 {{ point.count }}
 </div>
 <div
 class="w-full max-w-[12px] h-20 bg-accent/10 rounded-full relative overflow-hidden">
 <div
 class="absolute bottom-0 w-full bg-accent rounded-full transition-all duration-700"
 :style="{
 height: `${(point.count / regMax) * 100}%`,
 }"></div>
 </div>
 <span
 class="text-xs text-main-text/25 group-hover:text-main-text/50 transition-colors">
 {{ point.month }}
 </span>
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
 <div class="h-3 w-40 bg-main-text/5 rounded"></div>
 </div>
 </div>
 </div>
 <div
 class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-card-border animate-pulse">
 <div class="p-6 space-y-4">
 <div class="h-3 w-20 bg-main-text/5 rounded mb-4"></div>
 <div v-for="i in 5" :key="i" class="space-y-2">
 <div class="flex justify-between">
 <div class="h-2 w-16 bg-main-text/5 rounded"></div>
 <div class="h-2 w-6 bg-main-text/5 rounded"></div>
 </div>
 <div class="h-1 w-full bg-main-text/5 rounded"></div>
 </div>
 </div>
 <div class="p-6 space-y-6">
 <div class="flex items-center gap-4">
 <div
 class="w-16 h-16 rounded-full border-4 border-main-text/5"></div>
 <div class="space-y-2">
 <div
 v-for="i in 2"
 :key="i"
 class="h-2 w-20 bg-main-text/5 rounded"></div>
 </div>
 </div>
 <div class="grid grid-cols-2 gap-2">
 <div
 v-for="i in 2"
 :key="i"
 class="h-10 bg-main-text/5 rounded-lg border border-card-border"></div>
 </div>
 </div>
 <div class="p-6">
 <div class="h-3 w-32 bg-main-text/5 rounded mb-8"></div>
 <div class="flex items-end gap-2 h-24 pt-4">
 <div
 v-for="i in 6"
 :key="i"
 class="flex-1 bg-main-text/10 rounded-full h-16"></div>
 </div>
 </div>
 </div>
 </template>
 </div>
</template>
