<script setup lang="ts">
import { BookOpen, ChevronRight } from "lucide-vue-next";
const currentYear = new Date().getFullYear();

const organizationLinks = [
 { name: "footer.organization.campus", to: "/campus" },
 { name: "footer.organization.college", to: "/college" },
 { name: "footer.organization.school", to: "/school" },
 { name: "footer.organization.department", to: "/department" },
];

const academicLinks = [
 { name: "footer.academic.courses", to: "/courses" },
 { name: "footer.academic.resources", to: "/resources" },
 { name: "nav.events", to: "/events" },
 { name: "footer.academic.research", to: "/research" },
];

const pagesLinks = [
 { name: "footer.pages.home", to: "/" },
 { name: "footer.pages.about", to: "/about" },
 { name: "footer.pages.digital_library", to: "/digital-library" },
 { name: "footer.pages.contact", to: "/contact" },
];

const legalLinks = [
 { name: "footer.legal.privacy", to: "/privacy" },
 { name: "footer.legal.terms", to: "/terms" },
 { name: "footer.legal.cookies", to: "/cookies" },
];
</script>

<template>
 <footer
 class="bg-card-bg overflow-hidden relative transition-colors duration-300 mt-32 pt-16 pb-8 shadow-[0_-8px_30px_rgb(0,0,0,0.04)]">
 <div class="public-container">
 <!-- Main Footer Content -->
 <div
 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-6">
 <!-- Brand Section -->
 <div class="space-y-4">
 <router-link
 to="/"
 class="flex items-center gap-2 md:gap-3 group">
                        <img
                            src="/logo.webp"
                            alt="Logo"
                            class="w-16 h-16 md:w-20 md:h-20 object-contain group-hover:scale-105 transition-transform duration-300" />
 <div
 class="font-bold text-2xl md:text-3xl tracking-tighter">
 <span class="text-brand-blue">{{
 $tr("app.name")
 }}</span>
 </div>
 </router-link>
 <p
 class="text-sm text-main-text leading-relaxed max-w-xs font-normal">
 {{ $tr("footer.brand.description") }}
 </p>
 </div>

 <!-- Pages Column (First) -->
 <div class="space-y-4">
 <h3
 class="text-xs font-normal capitalize tracking-widest text-main-text/60">
 {{ $tr("footer.pages.title") }}
 </h3>
 <ul class="space-y-3">
 <li v-for="link in pagesLinks" :key="link.name">
 <router-link
 :to="link.to"
 class="relative flex items-center text-sm font-normal text-main-text hover:text-accent transition-all duration-300 group hover:pl-6 pl-1">
 <ChevronRight
 class="w-4 h-4 absolute left-0 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"
 :stroke-width="1.5" />
 <span class="transition-all duration-300">{{
 $tr(link.name)
 }}</span>
 </router-link>
 </li>
 </ul>
 </div>

 <!-- Organization Column (Second) -->
 <div class="space-y-4">
 <h3
 class="text-xs font-normal capitalize tracking-widest text-main-text/60">
 {{ $tr("footer.organization.title") }}
 </h3>
 <ul class="space-y-3">
 <li v-for="link in organizationLinks" :key="link.name">
 <router-link
 :to="link.to"
 class="relative flex items-center text-sm font-normal text-main-text hover:text-accent transition-all duration-300 group hover:pl-6 pl-1">
 <ChevronRight
 class="w-4 h-4 absolute left-0 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"
 :stroke-width="1.5" />
 <span class="transition-all duration-300">{{
 $tr(link.name)
 }}</span>
 </router-link>
 </li>
 </ul>
 </div>

 <!-- Academic Column (Third) -->
 <div class="space-y-4">
 <h3
 class="text-xs font-normal capitalize tracking-widest text-main-text/60">
 {{ $tr("footer.academic.title") }}
 </h3>
 <ul class="space-y-3">
 <li v-for="link in academicLinks" :key="link.name">
 <router-link
 :to="link.to"
 class="relative flex items-center text-sm font-normal text-main-text hover:text-accent transition-all duration-300 group hover:pl-6 pl-1">
 <ChevronRight
 class="w-4 h-4 absolute left-0 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"
 :stroke-width="1.5" />
 <span class="transition-all duration-300">{{
 $tr(link.name)
 }}</span>
 </router-link>
 </li>
 </ul>
 </div>
 </div>

 <!-- Bottom Section -->
 <div
 class="pt-6 border-t border-card-border flex flex-col md:flex-row items-center justify-between gap-4">
 <!-- Copyright -->
 <p class="text-sm font-normal text-main-text/60 tracking-tight">
 © {{ currentYear }}
 <span class="text-brand-blue font-bold">{{
 $tr("app.name")
 }}</span
 >.
 {{ $tr("footer.rights_reserved") }}
 </p>

 <!-- Legal Links -->
 <div class="flex flex-wrap items-center gap-6">
 <router-link
 v-for="link in legalLinks"
 :key="link.name"
 :to="link.to"
 class="text-sm font-normal text-main-text/60 hover:text-accent transition-colors capitalize">
 {{ $tr(link.name) }}
 </router-link>
 </div>
 </div>
 </div>
 </footer>
</template>
