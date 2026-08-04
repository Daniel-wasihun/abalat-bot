<script setup lang="ts">
import { Moon, Sun } from "lucide-vue-next";
import { useThemeStore } from "@/stores/themeStore";

const themeStore = useThemeStore();
</script>

<template>
 <button
 @click="themeStore.toggleTheme"
 class="relative p-2.5 rounded-xl transition-all duration-300 hover:bg-accent/10 text-main-text group cursor-pointer overflow-hidden border border-transparent hover:border-accent/20"
 aria-label="Toggle Theme">
 <div class="relative w-5 h-5 flex items-center justify-center">
 <Transition
 enter-active-class="transition-all duration-400 ease-out"
 enter-from-class="opacity-0 rotate-90 scale-0"
 enter-to-class="opacity-100 rotate-0 scale-100"
 leave-active-class="transition-all duration-400 ease-in absolute"
 leave-from-class="opacity-100 rotate-0 scale-100"
 leave-to-class="opacity-0 -rotate-90 scale-0">
 <Sun
 v-if="themeStore.isDark"
 key="sun"
 class="w-5 h-5 text-nav-accent" />
 <Moon v-else key="moon" class="w-5 h-5 text-nav-accent" />
 </Transition>
 </div>
 </button>
</template>
