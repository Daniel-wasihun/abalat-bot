<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, getCurrentInstance } from "vue";
import { User, Search, X, Check, Loader2, Sparkles } from "lucide-vue-next";
import { useUserStore } from "@/stores/userStore";
import { localize } from "@/utils/format";

/**
 * FormUserSearch
 * A high-fidelity, premium user search component for the 'Elite' design system.
 * Features advanced positioning, smooth animations, and rich profile previews.
 */

const props = withDefaults(
 defineProps<{
 modelValue: number | null | string;
 searchQuery: string;
 label?: string;
 placeholder?: string;
 error?: string;
 userType?: string;
 required?: boolean;
 disabled?: boolean;
 }>(),
 {
 label: "",
 placeholder: "Search user...",
 error: "",
 userType: "",
 },
);

const emit = defineEmits<{
 (e: "update:modelValue", value: number | null): void;
 (e: "update:searchQuery", value: string): void;
 (e: "blur"): void;
}>();

const userStore = useUserStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const isSearching = ref(false);
const showDropdown = ref(false);
const searchResults = ref<any[]>([]);

const containerRef = ref<HTMLDivElement | null>(null);
const dropdownRef = ref<HTMLDivElement | null>(null);
const dropdownStyle = ref<Record<string, string>>({});

const updateDropdownPosition = () => {
 const el = containerRef.value;
 if (!el) return;
 const rect = el.getBoundingClientRect();
 const spaceBelow = window.innerHeight - rect.bottom;
 const spaceAbove = rect.top;

 const idealMaxHeight = 280;
 let styles: Record<string, string> = {
 position: "fixed",
 left: `${rect.left}px`,
 width: `${rect.width}px`,
 zIndex: "var(--z-dropdown)",
 display: "flex",
 flexDirection: "column",
 };

 if (spaceBelow < idealMaxHeight && spaceAbove > spaceBelow) {
 styles.bottom = `${window.innerHeight - rect.top + 8}px`;
 styles.maxHeight = `${Math.min(idealMaxHeight, spaceAbove - 20)}px`;
 } else {
 styles.top = `${rect.bottom + 8}px`;
 styles.maxHeight = `${Math.min(idealMaxHeight, spaceBelow - 20)}px`;
 }

 dropdownStyle.value = styles;
};

const handleClickOutside = (e: MouseEvent) => {
 const target = e.target as Node;
 if (showDropdown.value) {
 const insideInput = containerRef.value?.contains(target) ?? false;
 const insideDropdown = dropdownRef.value?.contains(target) ?? false;
 if (!insideInput && !insideDropdown) {
 showDropdown.value = false;
 }
 }
};

onMounted(() => {
 document.addEventListener("mousedown", handleClickOutside);
 window.addEventListener("scroll", updateDropdownPosition, true);
 window.addEventListener("resize", updateDropdownPosition);
});

onUnmounted(() => {
 document.removeEventListener("mousedown", handleClickOutside);
 window.removeEventListener("scroll", updateDropdownPosition, true);
 window.removeEventListener("resize", updateDropdownPosition);
});

const localQuery = ref(props.searchQuery);

watch(
 () => props.searchQuery,
 (newVal) => {
 if (newVal !== localQuery.value) {
 localQuery.value = newVal;
 }
 },
);

let searchTimeout: any = null;

const onInput = (e: Event) => {
 const val = (e.target as HTMLInputElement).value;
 localQuery.value = val;
 emit("update:searchQuery", val);

 if (props.modelValue && val !== props.searchQuery) {
 emit("update:modelValue", null);
 }

 if (val.length < 2) {
 searchResults.value = [];
 return;
 }

 if (searchTimeout) clearTimeout(searchTimeout);
 searchTimeout = setTimeout(async () => {
 isSearching.value = true;
 showDropdown.value = true;
 updateDropdownPosition();
 try {
 const extraParams: any = {};
 if (props.userType) extraParams.user_type = props.userType;
 const results = await userStore.searchUsers(val, extraParams);
 searchResults.value = results;
 } catch (err) {
 console.error(err);
 } finally {
 isSearching.value = false;
 }
 }, 300);
};

const selectUser = (user: any) => {
 const name = localize(user.raw_name || user.name);
 localQuery.value = name;
 emit("update:searchQuery", name);
 emit("update:modelValue", user.id);
 showDropdown.value = false;
};

const clearUser = () => {
 localQuery.value = "";
 emit("update:searchQuery", "");
 emit("update:modelValue", null);
 searchResults.value = [];
 showDropdown.value = false;
};
</script>

<template>
 <div class="relative w-full flex flex-col group/search" ref="containerRef">
 <!-- Label Layer -->
 <label
 v-if="label"
 class="text-[13px] font-semibold text-main-text tracking-tight mb-2 px-1 block flex items-center gap-1.5 transition-colors group-focus-within/search:text-brand-blue">
 {{ label }}
 <span v-if="required" class="text-rose-500 font-bold">*</span>
 </label>

 <!-- Input Wrapper -->
 <div class="relative group/field">
 <!-- Icon Attachment -->
 <div
 class="absolute left-4 top-1/2 -translate-y-1/2 text-main-text/30 group-focus-within/search:text-brand-blue group-focus-within/search:scale-110 transition-all z-10">
 <User class="w-5 h-5" />
 </div>

 <input
 :value="localQuery"
 type="text"
 :disabled="disabled"
 @input="onInput"
 @focus="
 () => {
 if (disabled) return;
 showDropdown = true;
 updateDropdownPosition();
 }
 "
 @blur="$emit('blur')"
 class="w-full h-12 bg-card-bg border-2 rounded-xl pl-12 pr-12 text-sm font-semibold text-main-text focus:border-brand-blue/50 outline-none transition-all placeholder:text-main-text/30"
 :class="[
 error
 ? 'border-rose-500 bg-rose-500/[0.03]'
 : 'border-card-border/60 hover:border-brand-blue/30 group-hover/field:bg-card-hover/20',
 disabled
 ? 'cursor-not-allowed opacity-50 grayscale'
 : 'focus:shadow-lg focus:',
 ]"
 :placeholder="placeholder" />

 <!-- Loading Spinner -->
 <div
 v-if="isSearching"
 class="absolute right-12 top-1/2 -translate-y-1/2 z-10">
 <Loader2 class="w-4 h-4 text-brand-blue animate-spin" />
 </div>

 <!-- Action Area -->
 <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 z-20">
 <button
 v-if="localQuery && !disabled"
 @click="clearUser"
 type="button"
 class="w-8 h-8 rounded-lg bg-main-text/5 text-main-text/30 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all transform active:scale-90">
 <X class="w-4 h-4" />
 </button>
 <div
 v-else
 class="w-8 h-8 flex items-center justify-center text-main-text/20">
 <Search class="w-4 h-4" />
 </div>
 </div>
 </div>

 <!-- Error Message -->
 <transition name="slide-up">
 <p v-if="error" class="text-[11px] font-bold text-rose-500 mt-2 px-1 flex items-center gap-1 capitalize tracking-wide">
 <X class="w-3 h-3" />
 {{ error }}
 </p>
 </transition>

 <!-- Dropdown Portal -->
 <Teleport to="body">
 <transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="transform scale-95 opacity-0 -translate-y-4"
 enter-to-class="transform scale-100 opacity-100 translate-y-0"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="transform scale-100 opacity-100 translate-y-0"
 leave-to-class="transform scale-95 opacity-0 -translate-y-4">
 <div
 v-if="showDropdown && localQuery.length >= 2"
 ref="dropdownRef"
 :style="dropdownStyle"
 class="bg-card-bg border-2 border-card-border rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] overflow-hidden backdrop-blur-3xl ring-1 ring-black/5">
 
 <!-- Dropdown Header -->
 <div class="px-4 py-3 bg-card-bg/50 border-b border-card-border/60 flex items-center justify-between">
 <span class="text-[10px] font-bold text-main-text/40 capitalize tracking-widest">{{ $tr('common.search_results') || 'Search Results' }}</span>
 <Sparkles v-if="searchResults.length > 0" class="w-3.5 h-3.5 text-brand-blue/50" />
 </div>

 <div class="overflow-y-auto custom-scrollbar p-2 max-h-full">
 <!-- Empty State -->
 <div
 v-if="!isSearching && searchResults.length === 0"
 class="py-10 text-center flex flex-col items-center gap-3">
 <div class="w-12 h-12 rounded-full bg-main-text/5 flex items-center justify-center text-main-text/20">
 <Search class="w-6 h-6" />
 </div>
 <p class="text-sm font-semibold text-main-text/40">
 {{ $tr("common.no_results_found") || "No user found" }}
 </p>
 </div>

 <!-- Results List -->
 <div class="space-y-1">
 <button
 v-for="user in searchResults"
 :key="user.id"
 type="button"
 @click="selectUser(user)"
 class="w-full px-4 py-3 flex items-center gap-4 hover:bg-brand-blue/[0.04] transition-all text-left group rounded-xl relative overflow-hidden h-[72px]">
 <!-- Selection Indicator -->
 <div 
 v-if="modelValue === user.id"
 class="absolute left-0 top-0 bottom-0 w-1 bg-brand-blue shadow-[0_0_12px_rgba(var(--brand-blue-rgb),0.5)]">
 </div>

 <!-- Avatar Wrapper -->
 <div
 class="w-11 h-11 rounded-2xl bg-brand-blue/5 flex items-center justify-center group-hover:bg-brand-blue/10 transition-all shrink-0 overflow-hidden border-2 border-card-border group-hover:border-brand-blue/30 relative">
 <img
 v-if="user.avatar"
 :src="user.avatar"
 class="w-full h-full object-cover" />
 <span
 v-else
 class="text-sm font-bold text-brand-blue"
 >{{ localize(user.raw_name || user.name)?.charAt(0) || "U" }}</span
 >
 </div>

 <!-- Profile Data -->
 <div class="flex flex-col flex-1 min-w-0">
 <span
 class="text-sm font-bold text-main-text leading-tight group-hover:text-brand-blue transition-colors truncate"
 >{{ localize(user.raw_name || user.name) }}</span
 >
 <div class="flex items-center gap-2 mt-1 min-w-0">
 <span
 class="text-[10px] font-bold text-brand-blue bg-brand-blue/10 px-2 py-0.5 rounded-lg tracking-widest shrink-0"
 >#{{ user.university_id || user.info?.user_university_id || user.id_number || user.employee_id || "N/A" }}</span>
 <span class="text-[11px] font-medium text-main-text/30 truncate">{{ user.email }}</span>
 </div>
 </div>

 <!-- Selection Check -->
 <div 
 v-if="modelValue === user.id"
 class="w-6 h-6 rounded-full bg-brand-blue text-white flex items-center justify-center shrink-0">
 <Check class="w-4 h-4 stroke-[3px]" />
 </div>
 </button>
 </div>
 </div>
 </div>
 </transition>
 </Teleport>
 </div>
</template>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active {
 transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
 opacity: 0;
 transform: translateY(-4px);
}
</style>
