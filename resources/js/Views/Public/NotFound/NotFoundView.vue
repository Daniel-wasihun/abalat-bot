<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
 Home,
 ArrowLeft,
 SearchX,
 ShieldAlert,
 LayoutDashboard,
 Library,
 Compass,
 LifeBuoy,
 ExternalLink,
} from "lucide-vue-next";

const router = useRouter();
const route = useRoute();

const isUnauthorized = computed(() => route.query.type === "unauthorized");

const title = computed(() =>
 isUnauthorized.value ? "not_found.unauthorized_title" : "not_found.title",
);

const subtitle = computed(() =>
 isUnauthorized.value
 ? "not_found.unauthorized_subtitle"
 : "not_found.subtitle",
);

const goBack = () => {
 if (window.history.length > 1) {
 router.back();
 } else {
 router.push("/");
 }
};

const goHome = () => router.push("/");

const navigationCards = [
 {
 title: "not_found.portal",
 desc: "Access your personalized management dashboard.",
 icon: LayoutDashboard,
 to: "/dashboard",
 color: "from-blue-500/10 to-indigo-500/10",
 iconColor: "text-blue-600",
 },
 {
 title: "not_found.library",
 desc: "Browse our extensive digital and physical collections.",
 icon: Library,
 to: "/digital-library",
 color: "from-emerald-500/10 to-teal-500/10",
 iconColor: "text-emerald-600",
 },
 {
 title: "not_found.support",
 desc: "Get help from our technical assistance team.",
 icon: LifeBuoy,
 to: "/contact",
 color: "from-amber-500/10 to-orange-500/10",
 iconColor: "text-amber-600",
 },
];
</script>

<template>
 <div
 class="min-h-screen bg-(--bg-body) flex items-center justify-center p-4 md:p-8 lg:p-12 selection:bg-brand-blue/10">
 <!-- Background Ambient Effects -->
 <div class="fixed inset-0 overflow-hidden pointer-events-none">
 <div
 class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-brand-blue/5 blur-[120px] rounded-full animate-pulse"></div>
 <div
 class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-accent/5 blur-[120px] rounded-full animate-pulse delay-700"></div>
 </div>

 <div class="max-w-5xl w-full relative z-10 space-y-12">
 <!-- Header Section: Large Visual and Main Message -->
 <div class="text-center space-y-8">
 <!-- Visual Indicator -->
 <div class="relative flex justify-center mb-6">
 <div
 class="w-24 h-24 lg:w-32 lg:h-32 bg-card-bg rounded-[2rem] border border-card-border shadow-2xl flex items-center justify-center group transform transition-transform duration-700 hover:rotate-6">
 <SearchX
 class="w-10 h-10 lg:w-14 lg:h-14 transition-all duration-500 group-hover:scale-110 text-brand-blue" />
 </div>
 <!-- Glow effect -->
 <div
 class="absolute inset-0 blur-2xl opacity-20 -z-10 bg-brand-blue"></div>
 </div>

 <!-- Text Content -->
 <div class="space-y-4 max-w-2xl mx-auto">
 <div
 class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-main-text/5 border border-main-text/10 text-[10px] font-bold capitalize tracking-[0.2em] text-main-text/40 mb-2">
 <Compass class="w-3 h-3" />
 {{ isUnauthorized ? "403 Restricted" : "404 Error" }}
 </div>
 <h1
 class="text-4xl md:text-5xl lg:text-6xl font-black text-main-text tracking-tight animate-in fade-in slide-in-from-bottom-4 duration-700">
 {{ $tr(title) }}
 </h1>
 <p
 class="text-lg md:text-xl text-main-text/40 font-normal leading-relaxed animate-in fade-in slide-in-from-bottom-4 duration-700 delay-100">
 {{ $tr(subtitle) }}
 </p>
 </div>

 <!-- Primary Actions -->
 <div
 class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-6 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-200">
 <button
 @click="goBack"
 class="w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-card-bg text-main-text font-bold rounded-2xl border border-card-border hover:bg-card-hover transition-all active:scale-95 group md:text-lg">
 <ArrowLeft
 class="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
 {{ $tr("not_found.go_back") }}
 </button>
 <button
 @click="goHome"
 class="w-full sm:w-auto flex items-center justify-center gap-3 px-10 py-4 bg-brand-blue text-white font-bold rounded-2xl hover:bg-[#083a6e] transition-all active:scale-95 group md:text-lg">
 <Home
 class="w-5 h-5 group-hover:scale-110 transition-transform" />
 {{ $tr("not_found.return_home") }}
 </button>
 </div>
 </div>

 <!-- Restructured Navigation Grid -->
 <div class="space-y-6">
 <div class="flex items-center gap-4 px-2">
 <div class="h-px flex-1 bg-card-border/50"></div>
 <span
 class="text-xs font-bold capitalize tracking-widest text-main-text/20"
 >{{ $tr("not_found.links_title") }}</span
 >
 <div class="h-px flex-1 bg-card-border/50"></div>
 </div>

 <div
 class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6 animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-300">
 <div
 v-for="card in navigationCards"
 :key="card.to"
 @click="router.push(card.to)"
 class="group p-6 lg:p-8 rounded-[2rem] bg-card-bg border border-card-border hover:border-brand-blue/30 transition-all duration-500 cursor-pointer relative overflow-hidden flex flex-col items-start gap-4">
 <!-- Card Glow Backdrop -->
 <div
 :class="[
 'absolute top-0 right-0 w-32 h-32 bg-linear-to-bl blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700',
 card.color,
 ]"></div>

 <!-- Icon Circle -->
 <div
 class="w-12 h-12 rounded-2xl bg-main-text/5 flex items-center justify-center group-hover:scale-110 transition-transform duration-500 relative z-10">
 <component
 :is="card.icon"
 :class="['w-6 h-6', card.iconColor]" />
 </div>

 <div class="space-y-1 relative z-10">
 <h3
 class="text-xl font-bold text-main-text tracking-tight flex items-center gap-2">
 {{ $tr(card.title) }}
 <ExternalLink
 class="w-3 h-3 opacity-0 group-hover:opacity-40 transition-opacity" />
 </h3>
 <p
 class="text-sm text-main-text/30 leading-relaxed">
 {{ card.desc }}
 </p>
 </div>
 </div>
 </div>
 </div>

 <!-- Global Footer Info -->
 <div
 class="pt-8 flex flex-col md:flex-row items-center justify-between gap-6 border-t border-card-border/50 text-sm font-normal text-main-text/20 animate-in fade-in duration-1000 delay-500">
 <div class="flex items-center gap-6">
 <span
 >&copy; {{ new Date().getFullYear() }} Abugida LMS. All
 Reserved.</span
 >
 </div>
 <div class="flex items-center gap-8">
 <a
 href="mailto:support@abugida.pro"
 class="flex items-center gap-2 hover:text-accent transition-colors">
 <LifeBuoy class="w-4 h-4" />
 {{ $tr("not_found.support") }}
 </a>
 </div>
 </div>
 </div>
 </div>
</template>

<style scoped>
/* Custom shadow for high-end look */
.shadow-2xl {
 box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

@keyframes pulse {
 0%,
 100% {
 opacity: 0.05;
 transform: scale(1);
 }
 50% {
 opacity: 0.1;
 transform: scale(1.1);
 }
}

.animate-pulse {
 animation: pulse 8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
