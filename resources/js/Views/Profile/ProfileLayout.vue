<script setup lang="ts">
import { useRoute } from "vue-router";
import { User, ShieldCheck, Building, Monitor } from "lucide-vue-next";

const route = useRoute();

const navigation = [
 {
 name: "profile.overview",
 to: "/dashboard/profile/overview",
 icon: Building,
 },
 { name: "profile.edit", to: "/dashboard/profile/edit", icon: User },
 {
 name: "profile.security",
 to: "/dashboard/profile/security",
 icon: ShieldCheck,
 },
 {
 name: "profile.devices",
 to: "/dashboard/profile/devices",
 icon: Monitor,
 },
];

const isActive = (path: string) => {
 return route.path === path;
};
</script>

<template>
 <div class="flex flex-col w-full relative">
 <!-- Clean Navigation Hub -->
 <div class="sticky -top-1 -mt-4 pt-4 z-40 w-full bg-main-bg border-b border-card-border">
 <div class="flex items-center gap-1 md:gap-2 overflow-x-auto no-scrollbar">
 <router-link
 v-for="item in navigation"
 :key="item.to"
 :to="item.to"
 class="relative px-6 py-5 text-[15px] font-normal transition-colors hover:text-brand-blue group whitespace-nowrap"
 :class="[isActive(item.to) ? 'text-brand-blue' : 'text-main-text/40']">
 
 <div class="flex items-center gap-2.5 relative z-10">
 <component :is="item.icon" class="w-4.5 h-4.5" />
 <span class="capitalize">{{ $tr(item.name) }}</span>
 </div>

 <!-- Active Indicator Baseline -->
 <div
 v-if="isActive(item.to)"
 class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-blue"></div>
 </router-link>
 </div>
 </div>

 <!-- Content Area -->
 <div class="w-full py-6 md:py-8">
 <router-view v-slot="{ Component }">
 <component :is="Component" />
 </router-view>
 </div>
 </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
 display: none;
}
</style>
