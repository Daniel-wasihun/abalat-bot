<script setup lang="ts">
import { useLanguageStore } from "@/stores/languageStore";
import { Languages, ChevronDown, Check } from "lucide-vue-next";
import { ref, onMounted, onUnmounted } from "vue";

const langStore = useLanguageStore();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => {
 isOpen.value = !isOpen.value;
};

const selectLanguage = async (code: string) => {
 await langStore.setLanguage(code);
 isOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
 if (
 dropdownRef.value &&
 !dropdownRef.value.contains(event.target as Node)
 ) {
 isOpen.value = false;
 }
};

onMounted(() => {
 window.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
 window.removeEventListener("click", handleClickOutside);
});
</script>

<template>
 <div class="relative" ref="dropdownRef">
 <button
 @click="toggleDropdown"
 class="relative flex items-center gap-2 px-3 py-2.5 rounded-xl transition-all duration-300 hover:bg-accent/10 text-main-text group cursor-pointer overflow-hidden border border-transparent hover:border-accent/20"
 :class="{ 'bg-accent/10 border-accent/20': isOpen }">
 <Languages
 class="w-5 h-5 text-nav-accent opacity-80 group-hover:opacity-100 transition-opacity" />
 <span class="text-sm font-normal text-main-text inline">{{
 $tr("lang." + langStore.currentLanguage)
 }}</span>
 </button>

 <!-- Dropdown Menu -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 scale-95 translate-y-1"
 enter-to-class="opacity-100 scale-100 translate-y-0"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100 scale-100 translate-y-0"
 leave-to-class="opacity-0 scale-95 translate-y-1">
 <div
 v-if="isOpen"
 class="absolute right-0 mt-2 w-48 bg-card-bg border border-card-border rounded-xl shadow-2xl p-2 z-110 backdrop-blur-xl bg-card-bg/95">
 <div
 class="px-3 py-2 text-sm font-normal text-slate-400 border-b border-card-border mb-1">
 {{ $tr("nav.select_language") }}
 </div>

 <button
 v-for="lang in langStore.availableLanguages"
 :key="lang.key"
 @click="selectLanguage(lang.key)"
 class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl hover:bg-card-hover transition-all group cursor-pointer"
 :class="{
 'bg-accent/5 text-accent':
 langStore.currentLanguage === lang.key,
 'text-main-text hover:text-accent':
 langStore.currentLanguage !== lang.key,
 }">
 <div
 class="flex flex-col items-start translate-x-0 group-hover:translate-x-1 transition-transform">
 <span class="text-sm font-normal">{{ lang.name }}</span>
 <span class="text-sm opacity-60 font-normal">{{
 $tr("lang." + lang.key)
 }}</span>
 </div>
 <Check
 v-if="langStore.currentLanguage === lang.key"
 class="w-3.5 h-3.5" />
 </button>
 </div>
 </Transition>
 </div>
</template>
