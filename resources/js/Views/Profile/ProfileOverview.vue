<script setup lang="ts">
import { computed } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useProfile } from "@/composables/useProfile";
import { useLanguageStore } from "@/stores/languageStore";
import {
 Phone,
 IdCard,
 Calendar,
 MapPin,
 ShieldCheck,
 Pencil,
 GraduationCap,
 Hash,
 AtSign,
 UserCircle,
 Copy,
} from "lucide-vue-next";

const authStore = useAuthStore();
const languageStore = useLanguageStore();

const localize = (val: any) => {
 if (!val) return "";
 return typeof val === "object"
 ? val[languageStore.currentLanguage] || val["en"] || ""
 : val;
};
const { userInitial, getProfileImage } = useProfile();

const profileData = computed(() => {
 return [
 { label: "auth.email", value: authStore.user?.email, icon: AtSign },
 {
 label: "user.phone_number",
 value: authStore.user?.info?.phone_number
 ? "+251 " + authStore.user.info.phone_number
 : null,
 icon: Phone,
 },
 {
 label: "user.university_id",
 value: authStore.user?.info?.user_university_id,
 icon: Hash,
 },
 {
 label: "user.date_of_birth",
 value: authStore.user?.info?.date_of_birth
 ? new Date(
 authStore.user.info.date_of_birth,
 ).toLocaleDateString()
 : null,
 icon: Calendar,
 },
 {
 label: "user.address",
 value: authStore.user?.info?.address,
 icon: MapPin,
 },
 {
 label: "profile.status",
 value: authStore.user?.info?.user_type,
 icon: ShieldCheck,
 isBadge: true,
 },
 ].filter((item) => item.value !== null && item.value !== undefined);
});
</script>

<template>
 <div class="space-y-6 pb-12 font-sans">
 <!-- Profile Identity Header -->
    <div class="premium-card bg-card-bg border border-card-border p-8 md:p-12 flex flex-col md:flex-row items-center gap-8 md:gap-20">
 <!-- Avatar Unit -->
 <div class="shrink-0 relative">
 <div class="w-32 h-32 md:w-40 md:h-40 rounded-3xl border border-card-border p-1 overflow-hidden bg-card-bg">
 <div class="w-full h-full rounded-2xl overflow-hidden bg-main-text/3 flex items-center justify-center">
 <img
 v-if="getProfileImage(authStore.user?.info?.profile_picture)"
 :src="getProfileImage(authStore.user?.info?.profile_picture)"
 class="w-full h-full object-cover" />
 <div
 v-else
 class="text-5xl text-main-text/10 font-light tracking-widest">
 {{ userInitial }}
 </div>
 </div>
 </div>
 </div>

 <!-- Identity Details -->
 <div class="flex-1 text-center md:text-left space-y-5">
 <div class="space-y-1">
 <h1 class="text-3xl md:text-4xl font-normal text-main-text tracking-tight capitalize">
 {{ localize(authStore.user?.name) }}
 </h1>
 <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
 <span class="text-brand-blue font-normal text-[15px] capitalize">
 {{ localize(authStore.user?.role?.name) || "Member" }}
 </span>
 <span class="w-1 h-1 rounded-full bg-main-text/10"></span>
 <span class="text-main-text/40 font-normal text-[15px] capitalize">
 {{ authStore.user?.info?.user_type }}
 </span>
 </div>
 </div>
 </div>

 <div class="pt-4 md:pt-0">
 <router-link
 to="/dashboard/profile/edit"
 class="px-8 py-3.5 bg-brand-blue text-white rounded-xl text-base font-normal transition-opacity hover:opacity-90 active:scale-95 flex items-center gap-3">
 <Pencil class="w-4 h-4" />
 <span class="capitalize">{{ $tr("profile.edit") }}</span>
 </router-link>
 </div>
 </div>

 <!-- Registry Details Section -->
 <div class="premium-card bg-card-bg border border-card-border p-8 md:p-12">
 <div class="flex items-center justify-between mb-10 pb-6 border-b border-card-border">
 <div class="flex items-center gap-4">
 <div class="w-11 h-11 rounded-xl bg-main-text/3 flex items-center justify-center border border-card-border">
 <UserCircle class="w-5 h-5 text-main-text/30" />
 </div>
 <h2 class="text-xl font-normal text-main-text tracking-tight capitalize">Personal information</h2>
 </div>
 </div>

 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-10 gap-x-12">
 <div
 v-for="(item, key) in profileData"
 :key="key"
 class="flex gap-5 group items-start">
 <div class="w-12 h-12 shrink-0 rounded-xl bg-card-bg border border-card-border flex items-center justify-center">
 <component
 :is="item.icon"
 class="w-5 h-5 text-main-text/20 group-hover:text-brand-blue transition-colors" />
 </div>

 <div class="space-y-1.5 flex-1 min-w-0 pt-0.5">
 <p class="text-[11px] capitalize text-main-text/30 font-normal">
 {{ $tr(item.label) }}
 </p>

 <div class="flex items-center justify-between group/val">
 <template v-if="item.isBadge">
 <span class="text-sm font-normal text-brand-blue capitalize">
 {{ item.value }}
 </span>
 </template>
 <template v-else>
 <p class="text-[15px] text-main-text font-normal truncate pr-4">
 {{ item.value }}
 </p>
 <Copy class="w-3.5 h-3.5 text-main-text/0 group-hover/val:text-main-text/20 cursor-pointer transition-all hover:text-brand-blue" />
 </template>
 </div>
 </div>
 </div>
 </div>

 <div class="mt-20 pt-8 border-t border-card-border border-dashed">
 <p class="text-xs text-main-text/20 font-normal text-center capitalize tracking-wide">
 All identity information is synchronized with institutional registry standards.
 </p>
 </div>
 </div>
 </div>
</template>

<style scoped>
.premium-card {
 border-radius: 1.5rem;
}

h1,
h2 {
 letter-spacing: -0.01em;
}
</style>
