<script setup lang="ts">
import {
 History,
 Clock,
 BookOpen,
 Upload as UploadIcon,
 ShieldCheck,
 Zap,
 Globe,
} from "lucide-vue-next";
import { useDashboardStore } from "@/stores/dashboardStore";
import { useLanguageStore } from "@/stores/languageStore";
import { computed, getCurrentInstance } from "vue";

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
const { localize } = useLanguageStore();

const dashboardStore = useDashboardStore();
const stats = computed(() => dashboardStore.stats);
const activities = computed(() => stats.value?.activity || []);

const getInitial = (name: any) => {
 const localizedName = typeof name === "string" ? name : localize(name);
 return localizedName ? localizedName.substring(0, 2).toUpperCase() : "SY";
};

const typeColors: Record<string, string> = {
 loan: "text-accent",
 material: "text-brand-green",
};

const typeIcons: Record<string, any> = {
 loan: BookOpen,
 material: UploadIcon,
};
</script>

<template>
 <div class="premium-card overflow-hidden">
 <template v-if="stats">
 <div
 class="px-6 py-5 border-b border-card-border flex items-center justify-between">
 <div class="flex items-center gap-3">
 <div
 class="w-10 h-10 rounded-xl bg-accent/3 flex items-center justify-center border border-accent/10">
 <History class="w-5 h-5 text-accent" />
 </div>
 <div>
 <h3
 class="text-sm font-normal text-main-text leading-none mb-1">
 {{ $tr("activity.node_feed") }}
 </h3>
 <p class="text-sm text-main-text/40 font-normal">
 {{ $tr("activity.audit_stream") }}
 </p>
 </div>
 </div>
 <div
 class="flex items-center gap-2 px-3 py-1 bg-brand-green/5 border border-brand-green/10 rounded-full">
 <span
 class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
 <span class="text-sm font-normal text-brand-green">{{
 $tr("activity.status_active", {
 count: activities.length,
 })
 }}</span>
 </div>
 </div>

 <div class="divide-y divide-card-border/50">
 <div
 v-if="activities.length === 0"
 class="px-6 py-12 text-center text-main-text/30 text-sm font-poppins font-normal">
 {{ $tr("common.no_data") }}
 </div>

 <div
 v-for="(activity, idx) in activities"
 :key="activity.id"
 class="px-6 py-4 flex items-start gap-5 border-b last:border-0 border-card-border/30 group hover:bg-main-text/[0.01] transition-colors">
 <!-- Status Pip -->
 <div class="mt-2.5">
 <div
 :class="[
 'w-1.5 h-1.5 rounded-full transition-all duration-500',
 typeColors[activity.type] || 'bg-main-text/20',
 ]"></div>
 </div>

 <div
 class="w-10 h-10 rounded-full bg-main-text/3 border border-card-border/50 flex items-center justify-center text-main-text/40 font-normal text-sm shrink-0">
 {{ getInitial(activity.user) }}
 </div>

 <!-- Log Content -->
 <div class="flex-1 min-w-0">
 <div class="flex items-start justify-between">
 <p class="text-base text-main-text leading-tight">
 <span class="font-normal">{{
 localize(activity.user)
 }}</span>
 <span class="text-main-text/40 ml-1.5">{{
 activity.action
 }}</span>
 </p>
 <span
 class="text-sm font-normal text-main-text/20 tabular-nums"
 >{{ activity.time }}</span
 >
 </div>

 <div class="flex items-center gap-4 mt-2">
 <div
 class="flex items-center gap-1.5 text-sm font-normal text-main-text/25">
 <component
 :is="typeIcons[activity.type] || Zap"
 class="w-3 h-3" />
 {{ activity.type }}
 </div>
 <div
 class="flex items-center gap-1.5 text-sm font-normal text-main-text/25">
 <Globe class="w-3 h-3" />
 {{ $tr("activity.origin") }}
 </div>
 </div>
 </div>

 <!-- Action Trace -->
 <div
 class="w-8 h-8 rounded-lg bg-main-text/2 border border-card-border/50 flex items-center justify-center text-main-text/20 group-hover:text-accent group-hover:border-accent/30 transition-all">
 <ShieldCheck class="w-4 h-4" />
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
 <div class="divide-y divide-card-border/50 animate-pulse">
 <div
 v-for="i in 5"
 :key="i"
 class="px-6 py-4 flex items-start gap-5">
 <div
 class="w-1.5 h-1.5 rounded-full mt-3 bg-main-text/10"></div>
 <div class="w-10 h-10 rounded-full bg-main-text/10"></div>
 <div class="flex-1 space-y-3">
 <div class="flex justify-between">
 <div
 class="h-4 w-1/2 bg-main-text/10 rounded"></div>
 <div class="h-3 w-16 bg-main-text/5 rounded"></div>
 </div>
 <div class="flex gap-4">
 <div class="h-3 w-20 bg-main-text/5 rounded"></div>
 <div class="h-3 w-20 bg-main-text/5 rounded"></div>
 </div>
 </div>
 <div class="w-8 h-8 rounded-lg bg-main-text/5"></div>
 </div>
 </div>
 </template>
 </div>
</template>
