<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, provide } from "vue";
import { useRoute, useRouter } from "vue-router";
import Sidebar from "@/components/dashboard/Sidebar.vue";
import Header from "@/components/dashboard/Header.vue";
import {
 PanelLeftOpen,
 PanelLeftClose,
 ArrowLeft,
 ChevronRight,
} from "lucide-vue-next";

const isSidebarManuallyCollapsed = ref(
 localStorage.getItem("sidebarCollapsed") === "true",
);
const isSidebarHovered = ref(false);
const sidebarWidth = ref(Number(localStorage.getItem("sidebarWidth")) || 288);
const isResizing = ref(false);
const windowWidth = ref(
 typeof window !== "undefined" ? window.innerWidth : 1200,
);

const isMobileSidebarOpen = ref(false);

const toggleSidebar = () => {
 if (isMobile.value) {
 isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
 } else {
 isSidebarManuallyCollapsed.value = !isSidebarManuallyCollapsed.value;
 }
};

const handleSidebarHover = (state: boolean) => {
 if (isSidebarManuallyCollapsed.value && !isMobile.value) {
 isSidebarHovered.value = state;
 }
};

const handleSidebarResize = (width: number) => {
 sidebarWidth.value = width;
 localStorage.setItem("sidebarWidth", width.toString());
};

const handleSidebarResizing = (state: boolean) => {
 isResizing.value = state;
};

watch(isSidebarManuallyCollapsed, (newValue) => {
 localStorage.setItem("sidebarCollapsed", newValue.toString());
});

const isMobile = computed(() => windowWidth.value < 1024);

const updateWidth = () => {
 windowWidth.value = window.innerWidth;
 if (windowWidth.value >= 1024) {
 isMobileSidebarOpen.value = false;
 }
};

const route = useRoute();
const router = useRouter();

const breadcrumbs = computed(() => {
 const matched = route.matched.filter(
 (record) => record.meta && record.meta.title,
 );
 return matched.map((record) => String(record.meta.title));
});

const handleBack = () => {
 router.back();
};

// --- GLOBAL SCROLL MANAGEMENT ---
const isHeaderVisible = ref(true);
let lastScrollTop = 0;

const handleContentScroll = (e: Event) => {
 const el = e.target as HTMLElement;
 const scrollTop = el.scrollTop;

 // Show header if near top, otherwise hide on scroll down
 if (scrollTop < 50) {
 isHeaderVisible.value = true;
 } else if (scrollTop > lastScrollTop && scrollTop > 100) {
 isHeaderVisible.value = false;
 } else if (scrollTop < lastScrollTop) {
 isHeaderVisible.value = true;
 }

 lastScrollTop = scrollTop;
};

// Expose state to all children (FilterBar, DataTable, etc.)
provide("isHeaderVisible", isHeaderVisible);

const mainContentRef = ref<HTMLElement | null>(null);

// Reset state on navigation
watch(
 () => route.path,
 () => {
 isHeaderVisible.value = true;
 lastScrollTop = 0;
  if (mainContentRef.value) {
    mainContentRef.value.scrollTop = 0;
  }
 },
);

onMounted(() => {
 window.addEventListener("resize", updateWidth);
});

onUnmounted(() => {
 window.removeEventListener("resize", updateWidth);
});
</script>

<template>
 <div class="min-h-screen bg-(--bg-body)">
 <!-- Sidebar Navigation (Desktop) -->
 <Sidebar
 :is-collapsed="isSidebarManuallyCollapsed && !isSidebarHovered"
 :is-manually-collapsed="isSidebarManuallyCollapsed"
 :is-mobile="false"
 @toggle="toggleSidebar"
 @hover="handleSidebarHover"
 @resize="handleSidebarResize"
 @resizing="handleSidebarResizing"
 class="hidden lg:flex" />

 <!-- Mobile Sidebar Overlay -->
 <transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0"
 enter-to-class="opacity-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100"
 leave-to-class="opacity-0">
 <div
 v-if="isMobileSidebarOpen && isMobile"
 class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-110 lg:hidden"
 @click="isMobileSidebarOpen = false"></div>
 </transition>

 <!-- Mobile Sidebar -->
 <transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="-translate-x-full"
 enter-to-class="translate-x-0"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="translate-x-0"
 leave-to-class="-translate-x-full">
 <Sidebar
 v-if="isMobileSidebarOpen && isMobile"
 :is-collapsed="false"
 :is-manually-collapsed="false"
 :is-mobile="true"
 @toggle="isMobileSidebarOpen = false"
 @hover="handleSidebarHover"
 @resize="handleSidebarResize"
 @resizing="handleSidebarResizing"
 class="lg:hidden" />
 </transition>

 <!-- Main Content Area -->
 <div
 :class="[
 'h-screen flex flex-col overflow-hidden',
 !isResizing ? 'layout-transition' : '',
 ]"
 :style="
 isMobile
 ? { paddingLeft: '0' }
 : isSidebarManuallyCollapsed && !isSidebarHovered
 ? { paddingLeft: '80px' }
 : { paddingLeft: sidebarWidth + 'px' }
 ">
 <!-- Top Header -->
 <Header
 :is-collapsed="
 isMobile
 ? !isMobileSidebarOpen
 : isSidebarManuallyCollapsed && !isSidebarHovered
 "
 :is-mobile="isMobile"
 @toggle="toggleSidebar" />

 <!-- Mobile Toggler Below Header -->
 <div
 v-if="isMobile"
 class="fixed top-22 left-4 z-90 animate-in fade-in slide-in-from-top-2 duration-300">
 <button
 @click="toggleSidebar"
 class="p-2 rounded-xl bg-card-bg border border-card-border shadow-lg text-main-text hover:text-accent transition-all active:scale-95">
 <component
 :is="
 isMobileSidebarOpen ? PanelLeftClose : PanelLeftOpen
 "
 class="w-5 h-5" />
 </button>
 </div>

 <div
 class="shrink-0 px-4 md:px-6 lg:px-8 bg-transparent">
 <div class="max-w-full mx-auto">
 <div
 class="flex items-center justify-between flex-wrap gap-6 py-4">
 <!-- Left: Back Button -->
 <button
 @click="handleBack"
 class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-main-text/60 hover:text-accent hover:bg-accent/5 transition-all duration-300 group cursor-pointer font-medium active:scale-95"
  aria-label="Go Back">
 <ArrowLeft
 class="w-5 h-5 lg:w-[22px] lg:h-[22px] group-hover:-translate-x-1 transition-transform"
 :stroke-width="1.5" />
 <span
 class="text-sm lg:text-base tracking-tight select-none"
 >{{ $tr("common.back") || "Back" }}</span
 >
 </button>

 <!-- Right: Path Text (Breadcrumbs) -->
 <div class="flex items-center gap-2.5">
 <template
 v-for="(crumb, index) in breadcrumbs"
 :key="index">
 <ChevronRight
 v-if="index > 0"
 class="w-3.5 h-3.5 lg:w-4 lg:h-4 text-main-text/10 shrink-0" />
 <span
 :class="[
 'truncate text-sm lg:text-base tracking-tight select-none',
 index === breadcrumbs.length - 1
 ? 'text-accent font-medium'
 : 'text-main-text/55 font-medium',
 ]"
 >{{ $tr(crumb) }}</span
 >
 </template>
 </div>
 </div>
 </div>
 </div>

 <!-- Scrollable Content Area -->
 <main
 ref="mainContentRef"
 @scroll="handleContentScroll"
 class="flex-1 overflow-y-auto custom-scrollbar px-4 md:px-6 lg:px-8 pb-6 pt-4 flex flex-col min-h-0">
 <div
 class="max-w-full mx-auto flex-1 flex flex-col w-full min-h-0">
 <router-view v-slot="{ Component, route }">
 <transition name="page" mode="out-in">
 <keep-alive>
 <component :is="Component" :key="route.name || route.fullPath" />
 </keep-alive>
 </transition>
 </router-view>
 </div>
 </main>
 </div>
 </div>
</template>

<style>
/* Ultra-fast Page transition styles */
.page-enter-active {
  transition: opacity 0.1s ease-out;
}

.page-leave-active {
  transition: opacity 0.05s ease-in;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
}

.layout-transition {
  transition: padding-left 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
