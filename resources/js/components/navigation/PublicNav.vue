<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { BookOpen, Menu, X, ChevronDown } from "lucide-vue-next";
import Button from "@/components/common/Button.vue";

import LanguageSwitcher from "@/components/navigation/LanguageSwitcher.vue";
import ThemeToggle from "@/components/navigation/ThemeToggle.vue";
import { useAuthStore } from "@/stores/authStore";

const authStore = useAuthStore();
const router = useRouter();
const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);
const navRef = ref<HTMLElement | null>(null);

const handleScroll = () => {
 isScrolled.value = window.scrollY > 20;
};

const handleClickOutside = (event: MouseEvent) => {
 if (
 isMobileMenuOpen.value &&
 navRef.value &&
 !navRef.value.contains(event.target as Node)
 ) {
 isMobileMenuOpen.value = false;
 }
};

onMounted(() => {
 window.addEventListener("scroll", handleScroll);
 window.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
 window.removeEventListener("scroll", handleScroll);
 window.removeEventListener("click", handleClickOutside);
});

const navLinks = [
 { name: "nav.home", to: "/" },
 { name: "nav.about_us", to: "/about" },
 { name: "nav.digital_library", to: "/digital-library" },
 {
 name: "nav.explore",
 children: [
 { name: "nav.research", to: "/research" },
 { name: "nav.resources", to: "/resources" },
 { name: "nav.events", to: "/events" },
 { name: "nav.alumni", to: "/alumni" },
 ],
 },
 { name: "nav.contact", to: "/contact" },
];
</script>

<template>
 <nav
 ref="navRef"
 class="fixed top-0 inset-x-0 z-[100] transition-all duration-500 ease-in-out"
 :class="
 isScrolled || isMobileMenuOpen
 ? 'py-1.5 bg-card-bg shadow-lg'
 : 'py-2.5 bg-transparent'
 ">
 <div
 class="public-container flex items-center justify-between relative">
 <!-- Brand (Left) -->
 <router-link to="/" class="flex items-center gap-3 group z-10">
        <img
          src="/logo.webp"
          alt="Logo"
          class="w-14 h-14 md:w-[72px] md:h-[72px] object-contain group-hover:scale-105 transition-transform duration-300" />
 <div class="font-bold text-xl md:text-2xl tracking-tighter">
 <span class="text-brand-blue">{{ $tr("app.name") }}</span>
 </div>
 </router-link>

 <!-- Desktop Navigation (Middle) -->
 <div
 class="hidden lg:flex flex-1 justify-center items-center gap-8 px-4">
 <template v-for="link in navLinks" :key="link.name">
 <!-- Regular Link -->
 <router-link
 v-if="!link.children"
 :to="link.to"
 class="relative py-2 text-sm font-normal text-main-text hover:text-accent transition-colors duration-300 group cursor-pointer"
 active-class="text-accent after:absolute after:-bottom-2 after:left-0 after:right-0 after:h-0.5 after:bg-accent after:content-['']">
 {{ $tr(link.name) }}
 </router-link>

 <!-- Dropdown Menu -->
 <div v-else class="relative group/dropdown">
 <button
 class="flex items-center gap-1 py-2 text-base font-normal text-main-text bg-transparent hover:text-accent transition-colors duration-300 cursor-pointer group-hover/dropdown:text-accent">
 {{ $tr(link.name) }}
 <ChevronDown
 class="w-4 h-4 transition-transform group-hover/dropdown:rotate-180"
 :stroke-width="1.5" />
 </button>

 <!-- Dropdown Content -->
 <div
 class="absolute top-full left-0 pt-4 opacity-0 invisible group-hover/dropdown:opacity-100 group-hover/dropdown:visible transition-all duration-200 transform translate-y-2 group-hover/dropdown:translate-y-0 w-40">
 <div
 class="bg-card-bg border border-card-border rounded-xl shadow-xl overflow-hidden p-1">
 <router-link
 v-for="child in link.children"
 :key="child.name"
 :to="child.to"
 class="block px-4 py-2.5 text-sm text-main-text hover:bg-accent/5 hover:text-accent rounded-lg transition-colors duration-300 text-left">
 {{ $tr(child.name) }}
 </router-link>
 </div>
 </div>
 </div>
 </template>
 </div>

 <!-- Header Actions (Right) -->
 <div class="hidden lg:flex items-center gap-5 z-10">
 <template v-if="!authStore.isAuthenticated">
 <router-link to="/login">
 <Button
 variant="primary"
 size="md"
 class="!min-w-[120px] !rounded-xl !h-10">
 {{ $tr("auth.sign_in") }}
 </Button>
 </router-link>
 </template>
 <template v-else>
 <router-link to="/dashboard">
 <Button
 variant="primary"
 size="md"
 class="!min-w-[120px] !rounded-xl !h-10">
 {{ $tr("to_dashboard") }}
 </Button>
 </router-link>
 </template>

 <div
 class="flex items-center gap-3 ml-2 pl-5 border-l border-card-border">
 <LanguageSwitcher />
 <ThemeToggle />
 </div>
 </div>

 <!-- Mobile Menu Toggle -->
 <button
 @click.stop="isMobileMenuOpen = !isMobileMenuOpen"
 class="lg:hidden p-3 text-main-text/60 hover:text-main-text hover:bg-card-bg rounded-xl border border-card-border transition-all"
 aria-label="Toggle Menu">
 <Menu
 v-if="!isMobileMenuOpen"
 class="w-5 h-5"
 :stroke-width="1.5" />
 <X v-else class="w-5 h-5" :stroke-width="1.5" />
 </button>
 </div>

 <!-- Mobile Side Menu Overlay -->
 <Transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="opacity-0 -translate-y-4"
 enter-to-class="opacity-100 translate-y-0"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0"
 leave-to-class="opacity-0 -translate-y-4">
 <div
 v-if="isMobileMenuOpen"
 class="lg:hidden absolute top-full inset-x-0 p-6 flex flex-col gap-4 bg-card-bg border-b border-card-border shadow-xl max-h-[85vh] overflow-y-auto custom-scrollbar">
 <!-- Nav Links -->
 <template v-for="link in navLinks" :key="link.name">
 <router-link
 v-if="!link.children"
 :to="link.to"
 class="px-4 py-3 text-lg font-normal text-main-text hover:bg-card-hover rounded-xl transition-colors"
 @click="isMobileMenuOpen = false">
 {{ $tr(link.name) }}
 </router-link>
 <div v-else class="space-y-1">
 <div
 class="px-4 py-2 text-sm font-medium text-main-text/40 tracking-wider">
 {{ $tr(link.name) }}
 </div>
 <router-link
 v-for="child in link.children"
 :key="child.name"
 :to="child.to"
 class="block px-8 py-3 text-lg font-normal text-main-text hover:bg-card-hover rounded-xl transition-colors"
 @click="isMobileMenuOpen = false">
 {{ $tr(child.name) }}
 </router-link>
 </div>
 </template>

 <hr class="border-card-border" />

 <!-- Settings & Auth -->
 <div class="flex flex-col gap-3 mt-2">
 <div
 class="flex items-center justify-between px-4 py-3 hover:bg-card-hover rounded-xl transition-colors cursor-pointer"
 @click.stop>
 <span class="text-base font-normal text-main-text">{{
 $tr("nav.theme")
 }}</span>
 <ThemeToggle />
 </div>

 <div
 class="flex items-center justify-between px-4 py-3 hover:bg-card-hover rounded-xl transition-colors cursor-pointer"
 @click.stop>
 <span class="text-base font-normal text-main-text">{{
 $tr("nav.language")
 }}</span>
 <LanguageSwitcher />
 </div>

 <div class="mt-2">
 <Button
 @click="
 router.push('/login');
 isMobileMenuOpen = false;
 "
 class="w-full !rounded-2xl h-12 capitalize font-black tracking-widest">
 {{ $tr("auth.sign_in") }}
 </Button>
 </div>
 </div>
 </div>
 </Transition>
 </nav>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
 width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
 background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
 background: rgba(0, 0, 0, 0.1);
 border-radius: 10px;
}
</style>
