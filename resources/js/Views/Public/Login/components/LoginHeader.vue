<script setup lang="ts">
import { ArrowLeft } from "lucide-vue-next";
import LanguageSwitcher from "@/components/navigation/LanguageSwitcher.vue";
import ThemeToggle from "@/components/navigation/ThemeToggle.vue";
</script>

<template>
 <div
 class="fixed top-8 right-8 z-[100] flex items-center gap-1.5 bg-card-bg/40 backdrop-blur-md p-1.5 rounded-xl border border-card-border/50 hover:shadow-md transition-shadow duration-300">
 <router-link
 to="/"
 class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-card-hover transition-all duration-300 group text-main-text">
 <ArrowLeft class="w-4 h-4" />
 <span class="text-sm font-normal tracking-wider">{{
 $tr("nav.home")
 }}</span>
 </router-link>
 <div class="w-px h-4 bg-card-border/50"></div>
 <LanguageSwitcher />
 <div class="w-px h-4 bg-card-border/50"></div>
 <ThemeToggle />
 </div>
</template>
