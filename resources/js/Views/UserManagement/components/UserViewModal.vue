<script setup lang="ts">
import {
 ShieldCheck,
 Key,
 Calendar,
 UserCircle2,
 Mail,
 Phone,
 MapPin,
 Cake,
 Clock,
 User as UserIcon,
 Fingerprint,
 CalendarClock,
 BadgeCheck,
 ArrowRight,
 Briefcase,
 CalendarDays,
 Hash,
 MoreHorizontal,
 Share2,
 Contact,
 ShieldAlert,
 TrendingUp,
 Hourglass,
 Timer,
 Unlock,
 LayoutGrid,
 GraduationCap,
 ChevronDown,
 ChevronUp,
 Minus,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import { localize, formatDate, formatTime } from "@/utils/format";
import { History } from "lucide-vue-next";
import { useUserStore } from "@/stores/userStore";
import { useLanguageStore } from "@/stores/languageStore";
import { storeToRefs } from "pinia";

const props = defineProps<{
 show: boolean;
 user: any;
}>();

const emit = defineEmits<{
 (e: "close"): void;
}>();

const showPermissions = ref(false);
const togglePermissions = () =>
 (showPermissions.value = !showPermissions.value);

const userStore = useUserStore();
const { currentLanguage } = storeToRefs(useLanguageStore());



const formatPhoneNumber = (phone: string | null | undefined) => {
 if (!phone) return null;
 return "+251" + phone;
};

const getItemTheme = (item: any, section: "elevation" | "sunset") => {
 // 1. Intent Detection Logic
 // action determines if we are adding ('grant') or removing ('revoke') access.
 const isExplicitRevoke =
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0";

 // In Sunset section: Focus on the "Expiring" or "Revoking" state of an item already in effect.
 if (section === "sunset") {
 if (isExplicitRevoke) {
 return {
 card: "bg-transparent border-rose-500/30 hover:border-rose-500/50",
 icon: "bg-transparent text-rose-600 border-rose-500/20",
 badge: "bg-rose-600",
 text: "text-rose-600",
 subText: "text-rose-500/70",
 badgeLabel: "common.revoke",
 intentLabel: "common.revoke",
 };
 }
 // Grant expiring
 return {
 card: "bg-transparent border-emerald-500/30 hover:border-emerald-500/50",
 icon: "bg-transparent text-emerald-600 border-emerald-500/20",
 badge: "bg-emerald-600",
 text: "text-emerald-600",
 subText: "text-emerald-500/70",
 badgeLabel: "common.grant",
 intentLabel: "user.expiring",
 };
 }

 // In Elevation section: Focus on the pure transition type (Scheduled Grant vs Scheduled Revoke)
 if (isExplicitRevoke) {
 return {
 card: "bg-transparent border-rose-500/30 hover:border-rose-500/50",
 icon: "bg-transparent text-rose-600 border-rose-500/20",
 badge: "bg-rose-600",
 text: "text-rose-600",
 subText: "text-rose-500/70",
 badgeLabel: "common.revoke",
 intentLabel: "user.revoke_scheduled",
 };
 }

 // Default: Grant (Positive Change)
 return {
 card: "bg-transparent border-emerald-500/30 hover:border-emerald-500/50",
 icon: "bg-transparent text-emerald-600 border-emerald-500/20",
 badge: "bg-emerald-600",
 text: "text-emerald-600",
 subText: "text-emerald-500/70",
 badgeLabel: "common.grant",
 intentLabel: "user.grant_scheduled",
 };
};

// --- Temporal State Synchronization ENGINE ---
// Matches the logic in PermissionsTab.vue exactly

const futurePermissions = computed(() => {
 return (
 props.user?.pending_permissions?.filter((p: any) => {
 if (p.is_currently_active !== undefined)
 return !p.is_currently_active;
 const start = p.start_date ? new Date(p.start_date) : null;
 return start && start > new Date();
 }) || []
 );
});

const activeOverrides = computed(() => {
 return (
 props.user?.pending_permissions?.filter((p: any) => {
 if (p.is_currently_active !== undefined)
 return p.is_currently_active && p.end_date;
 const end = p.end_date ? new Date(p.end_date) : null;
 return end && end > new Date();
 }) || []
 );
});

const futureRoles = computed(() => props.user?.scheduled_roles || []);

const activeExpiringRole = computed(() => {
 return props.user?.assignments?.find(
 (a: any) => a.is_currently_valid && a.end_date,
 );
});

const revertsTo = computed(() => {
 if (!activeExpiringRole.value || !props.user?.assignments) return null;

 // Find the most recent "permanent" role (no end date) that isn't the current temporal one
 // and started before or during the current assignment.
 const baselineRoles = props.user.assignments
 .filter((a: any) => {
 const isPermanent = !a.end_date;
 const isNotCurrent = a.id !== activeExpiringRole.value.id;
 const activeStart = activeExpiringRole.value.start_date
 ? new Date(activeExpiringRole.value.start_date).getTime()
 : 0;
 const aStart = a.start_date ? new Date(a.start_date).getTime() : 0;
 return isPermanent && isNotCurrent && aStart <= activeStart;
 })
 .sort((a: any, b: any) => {
 const dateA = a.start_date ? new Date(a.start_date).getTime() : 0;
 const dateB = b.start_date ? new Date(b.start_date).getTime() : 0;
 return dateB - dateA; // Most recent first
 });

 return baselineRoles[0] || null;
});

const historyAssignments = computed(() => {
 if (!props.user?.assignments) return [];

 return [...props.user.assignments]
 .filter((a: any) => {
 const startDate = a.start_date
 ? new Date(a.start_date)
 : new Date(0);
 return startDate <= new Date();
 })
 .sort((a: any, b: any) => {
 const dateA = a.start_date ? new Date(a.start_date).getTime() : 0;
 const dateB = b.start_date ? new Date(b.start_date).getTime() : 0;
 return dateB - dateA;
 });
});

const hasGrantingAccess = computed(
 () => futureRoles.value.length > 0 || futurePermissions.value.length > 0,
);
const hasExpiringAccess = computed(
 () => activeOverrides.value.length > 0 || activeExpiringRole.value,
);
</script>

<template>
 <Modal
 :show="show"
 @close="emit('close')"
 size="xl"
 :title="$tr('user.profile_details')"
 :icon="UserCircle2"
 noPadding>
 <div
 v-if="user"
 class="p-6 pt-6 font-sans text-main-text h-auto max-h-[85vh] flex flex-col overflow-hidden">
 <!-- Non-scrolling Top Sections -->
 <div class="space-y-4 shrink-0 mb-4">
 <!-- 1. Identity Header Card (Professional) -->
                <div
                    class="flex items-center gap-4 p-3 rounded-lg bg-transparent border border-card-border group hover:border-accent/30 transition-all">
 <div class="relative shrink-0">
 <div
 class="w-20 h-20 rounded-lg overflow-hidden border-2 border-main-bg shadow-md bg-main-bg flex items-center justify-center">
  <img
  v-if="user.profile_picture"
  :src="user.profile_picture"
  :alt="localize(user.name, currentLanguage)"
  class="w-full h-full object-cover" />
 <div
 v-else
 class="w-full h-full bg-brand-blue/5 flex items-center justify-center">
 <span
 class="text-3xl font-normal text-brand-blue capitalize"
 >{{
 localize(
 user.name,
 currentLanguage,
 ).charAt(0)
 }}</span
 >
 </div>
 </div>
 </div>

 <div class="flex-1 min-w-0 text-left space-y-1.5">
 <div class="flex items-center gap-4">
 <h2
 class="text-lg font-normal text-main-text leading-tight">
 {{ localize(user.name, currentLanguage).split(' ')[0] }}
 {{ user.info?.father_name ? localize(user.info.father_name, currentLanguage).split(' ')[0] : "" }}
 {{ user.info?.grandfather_name ? localize(user.info.grandfather_name, currentLanguage).split(' ')[0] : "" }}
 </h2>
 <span
 class="px-2 py-0.5 border border-brand-blue text-brand-blue text-[14px] font-normal capitalize tracking-wide rounded">
 {{
 localize(
 user.role?.name || $tr("user.no_role"),
 currentLanguage,
 )
 }}
 </span>
 </div>

 <div
 class="flex flex-wrap items-center gap-x-6 gap-y-1 text-[16px] text-main-text/60">
 <div class="flex items-center gap-2 min-w-0">
 <Mail class="w-4 h-4 text-brand-blue/70" />
 <span class="truncate">{{ user.email }}</span>
 </div>

 </div>
 </div>

 <!-- Auto-detecting Status Color -->
 <div
 class="shrink-0 hidden lg:flex items-center px-3 py-1.5 rounded-lg text-[16px] font-normal border"
 :class="
 user.is_active
 ? 'border-emerald-600 text-emerald-600'
 : 'border-rose-600 text-rose-600'
 ">
 {{
 user.is_active
 ? $tr("user.active")
 : $tr("user.inactive")
 }}
 </div>
 </div>

 <!-- 2. Core Stats Grid (Professional Data Cards) -->
 <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
 <div
 v-for="(val, label) in {
 'user.registration_no': {
 value:
 user.info?.registration_id ||
 user.info?.id_number ||
 user.registration_id,
 icon: Hash,
 },
 'user.gender': {
 value: user.info?.gender
 ? $tr('user.' + user.info.gender)
 : null,
 icon: UserCircle2,
 },
 'user.phone_number': {
 value: formatPhoneNumber(
 user.info?.phone_number,
 ),
 icon: Phone,
 },
 }"
 :key="label"
 class="p-3 rounded-xl bg-card-bg border border-card-border shadow-soft flex flex-col text-left hover:border-brand-blue/30 transition-all group overflow-hidden relative">
 <!-- Deco Blur -->
 <div
 class="absolute -right-4 -top-4 w-16 h-16 bg-brand-blue/5 rounded-full blur-xl group-hover:bg-brand-blue/10 transition-colors"></div>

 <div class="flex items-center gap-2 mb-2 z-10">
 <component
 :is="val.icon"
 class="w-3.5 h-3.5 text-brand-blue/60 group-hover:text-brand-blue transition-colors" />
 <span
 class="text-[16px] font-medium text-main-text/50 capitalize tracking-wide truncate"
 >{{ $tr(label) }}</span
 >
 </div>
 <span
 class="text-[16px] font-medium text-main-text truncate z-10 tracking-tight"
 >{{ val.value || $tr("user.not_specified") }}</span
 >
 </div>
 </div>
 </div>

 <!-- Content Split: 2 Columns for maximal visibility -->
 <div
 class="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1 min-h-0 pt-6 border-t border-card-border/30 overflow-hidden">
 <!-- Left Column: Context & Timeline -->
 <div
 class="space-y-6 overflow-y-auto custom-scrollbar pr-2 pb-4 flex flex-col">
 <!-- 3. Identity Context (Dates & Address) -->
 <div
 class="p-4 rounded-xl bg-card-bg border border-card-border shadow-soft flex flex-col text-left hover:border-accent/10 transition-all relative overflow-hidden shrink-0">
 <div
 class="absolute -left-6 -bottom-6 w-24 h-24 bg-brand-blue/5 rounded-full blur-2xl"></div>
 <div class="flex flex-col gap-4 relative z-10">
 <span
 class="text-[16px] font-medium text-main-text/40 capitalize tracking-wide"
 >{{ $tr("user.identity_context") }}</span
 >
 <div class="grid grid-cols-2 gap-4">
 <div
 v-for="(val, label) in {
 'user.date_of_birth':
 user.info?.date_of_birth,
 'user.joined_date': formatDate(
 user.created_at,
 currentLanguage,
 ),
 'user.address': user.info?.address,
 }"
 :key="label"
 class="flex flex-col gap-1.5"
 :class="{
 'col-span-2': label === 'user.address',
 }">
 <span
 class="text-[16px] font-medium text-main-text/30 capitalize tracking-wide"
 >{{ $tr(label) }}</span
 >
 <span
 class="text-[16px] font-normal text-main-text/80 leading-snug"
 >{{ val || "-" }}</span
 >
 </div>
 </div>
 </div>
 </div>

 <!-- Access Elevation (Auto-detecting Color per Item) -->
 <!-- 4. Pipeline Transitions -->
 <div v-if="hasGrantingAccess" class="space-y-4">
 <!-- Header -->
 <div class="flex items-center gap-3 px-1">
 <div
 class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-brand-green/20"></div>
 <h4
 class="text-base font-normal text-main-text capitalize">
 {{ $tr("user.access_elevation") }}
 </h4>
 </div>

 <!-- Roles Group -->
 <div v-if="futureRoles.length > 0" class="space-y-3">
 <div class="flex items-center gap-2 px-1">
 <ShieldCheck class="w-4 h-4 text-emerald-600" />
 <span
 class="text-[14px] font-normal text-main-text/40 tracking-wide"
 >{{ $tr("user.scheduled_roles") }}</span
 >
 </div>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div
 v-for="role in futureRoles"
 :key="role.id"
 :class="[
 getItemTheme(role, 'elevation').card,
 'p-4 rounded-lg border flex items-center gap-4 group transition-all hover:shadow-md min-h-[100px]',
 ]">
 <div
 :class="[
 getItemTheme(role, 'elevation')
 .icon,
 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
 ]">
 <ShieldCheck class="w-5 h-5" />
 </div>
 <div
 class="flex-1 min-w-0 flex flex-col justify-center gap-0.5">
 <div
 class="flex items-center justify-between gap-2">
 <span
 class="text-base font-normal text-main-text truncate"
 >{{
 localize(
 role.role_name,
 currentLanguage,
 )
 }}</span
 >
 <span
 :class="[
 getItemTheme(
 role,
 'elevation',
 ).badge,
 'px-2 py-0.5 text-white text-[14px] font-medium capitalize rounded shrink-0',
 ]">
 {{
 $tr(
 getItemTheme(
 role,
 "elevation",
 ).badgeLabel,
 )
 }}
 </span>
 </div>
 <div class="flex flex-col gap-1.5">
 <div
 class="flex items-center gap-1.5 text-[16px] text-main-text/60">
 <Calendar
 class="w-3.5 h-3.5 opacity-40" />
 <span class="truncate"
 >{{
 $tr(
 "user.commencement",
 )
 }}:
 {{
 formatDate(
 role.start_date,
 currentLanguage,
 )
 }}</span
 >
 </div>
 <div
 v-if="role.end_date"
 class="flex items-center gap-1.5 text-[16px] text-main-text/60">
 <CalendarClock
 class="w-3.5 h-3.5 opacity-40" />
 <span class="truncate"
 >{{
 $tr("user.termination")
 }}:
 {{
 formatDate(
 role.end_date,
 currentLanguage,
 )
 }}</span
 >
 </div>
 <span
 :class="[
 getItemTheme(
 role,
 'elevation',
 ).subText,
 'text-[14px] font-normal capitalize opacity-80',
 ]">
 {{
 $tr(
 getItemTheme(
 role,
 'elevation',
 ).intentLabel,
 )
 }}
 </span>
 </div>
 </div>
 </div>
 </div>
 </div>

 <!-- Permissions Group -->
 <div
 v-if="futurePermissions.length > 0"
 class="space-y-3">
 <div class="flex items-center gap-2 px-1">
 <Key class="w-4 h-4 text-emerald-600" />
 <span
 class="text-[16px] font-normal text-main-text/40 capitalize tracking-wide"
 >{{
 $tr("user.scheduled_permissions")
 }}</span
 >
 </div>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div
 v-for="perm in futurePermissions"
 :key="perm.id"
 :class="[
 getItemTheme(perm, 'elevation').card,
 'p-4 rounded-lg border flex items-center gap-4 group transition-all hover:shadow-md min-h-[110px]',
 ]">
 <div
 :class="[
 getItemTheme(perm, 'elevation')
 .icon,
 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
 ]">
 <Key class="w-5 h-5" />
 </div>
 <div
 class="flex-1 min-w-0 flex flex-col justify-center gap-2">
 <div
 class="flex items-center justify-between gap-2">
 <span
 class="text-base font-normal text-main-text truncate"
 >{{
 localize(
 perm.permission_name,
 currentLanguage,
 )
 }}</span
 >
 <span
 :class="[
 getItemTheme(
 perm,
 'elevation',
 ).badge,
 'px-2 py-0.5 text-white text-[14px] font-medium capitalize rounded shrink-0',
 ]">
 {{
 $tr(
 getItemTheme(
 perm,
 "elevation",
 ).badgeLabel,
 )
 }}
 </span>
 </div>
 <div class="flex flex-col gap-1.5">
 <div
 class="flex items-center gap-1.5 text-[16px] text-main-text/60">
 <Calendar
 class="w-3.5 h-3.5 opacity-40" />
 <span class="truncate"
 >{{
 $tr(
 "user.commencement",
 )
 }}:
 {{
 formatDate(
 perm.start_date,
 currentLanguage,
 )
 }}</span
 >
 </div>
 <div
 v-if="perm.end_date"
 class="flex items-center gap-1.5 text-[16px] text-main-text/60">
 <CalendarClock
 class="w-3.5 h-3.5 opacity-40" />
 <span class="truncate"
 >{{
 $tr("user.termination")
 }}:
 {{
 formatDate(
 perm.end_date,
 currentLanguage,
 )
 }}</span
 >
 </div>
 <span
 :class="[
 getItemTheme(
 perm,
 'elevation',
 ).subText,
 'text-[14px] font-normal capitalize opacity-80',
 ]">
 {{
 $tr(
 getItemTheme(
 perm,
 'elevation',
 ).intentLabel,
 )
 }}
 </span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <!-- Sunset Phase -->
 <div v-if="hasExpiringAccess" class="space-y-4">
 <!-- Header -->
 <div class="flex items-center gap-3 px-1">
 <div
 class="w-1.5 h-6 bg-rose-500 rounded-full"></div>
 <h4
 class="text-base font-normal text-main-text capitalize">
 {{ $tr("user.sunset_phase") }}
 </h4>
 </div>

 <!-- Roles Group -->
 <div v-if="activeExpiringRole" class="space-y-3">
 <div class="flex items-center gap-2 px-1">
 <Timer class="w-4 h-4 text-rose-600" />
 <span
 class="text-[14px] font-normal text-main-text/40 tracking-wide"
 >{{ $tr("user.expiring_roles") }}</span
 >
 </div>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div
 :class="[
 getItemTheme(
 activeExpiringRole,
 'sunset',
 ).card,
 'p-4 rounded-lg border flex flex-col gap-4 group transition-all hover:shadow-md relative overflow-hidden min-h-[120px]',
 ]">
 <!-- Main Row -->
 <div class="flex items-center gap-4">
 <div
 :class="[
 getItemTheme(
 activeExpiringRole,
 'sunset',
 ).icon,
 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
 ]">
 <Timer class="w-5 h-5" />
 </div>
 <div
 class="flex-1 min-w-0 flex flex-col justify-center gap-2">
 <div
 class="flex items-center justify-between gap-2">
 <span
 class="text-base font-normal text-main-text truncate"
 >{{
 localize(
 activeExpiringRole.role_name,
 currentLanguage,
 )
 }}</span
 >
 <span
 :class="[
 getItemTheme(
 activeExpiringRole,
 'sunset',
 ).badge,
 'px-2 py-0.5 text-white text-[14px] font-medium capitalize rounded shrink-0',
 ]">
 {{
 $tr(
 getItemTheme(
 activeExpiringRole,
 "sunset",
 ).badgeLabel,
 )
 }}
 </span>
 </div>
 <div class="flex flex-col gap-1.5">
 <div
 class="flex items-center gap-1.5 text-[16px] text-main-text/60">
 <CalendarClock
 class="w-3.5 h-3.5 opacity-40" />
 <span class="truncate"
 >{{
 $tr(
 "user.termination",
 )
 }}:
 {{
 formatDate(
 activeExpiringRole.end_date,
 currentLanguage,
 )
 }}</span
 >
 </div>
 <span
 :class="[
 getItemTheme(
 activeExpiringRole,
 'sunset',
 ).subText,
 'text-[14px] font-normal capitalize opacity-80',
 ]">
 {{
 $tr(
 getItemTheme(
 activeExpiringRole,
 "sunset",
 ).intentLabel,
 )
 }}
 </span>
 </div>
 </div>
 </div>

 <!-- Reversion Row (Integrated) -->
 <div
 v-if="revertsTo"
 class="pt-2 border-t border-rose-500/10 flex items-center gap-2">
 <History
 class="w-3.5 h-3.5 text-rose-500/40" />
 <span
 class="text-[14px] font-normal text-rose-500/70 truncate">
 {{ $tr("user.will_revert_to") }}
 <span
 class="text-rose-600 font-medium"
 >{{
 localize(
 revertsTo.role_name,
 currentLanguage,
 )
 }}</span
 >
 </span>
 </div>
 </div>
 </div>
 </div>

 <!-- Overrides Group -->
 <div
 v-if="activeOverrides.length > 0"
 class="space-y-3">
 <div class="flex items-center gap-2 px-1">
 <ShieldAlert class="w-4 h-4 text-rose-600" />
 <span
 class="text-[14px] font-normal text-main-text/40 tracking-wide"
 >{{ $tr("user.active_overrides") }}</span
 >
 </div>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div
 v-for="over in activeOverrides"
 :key="over.id"
 :class="[
 getItemTheme(over, 'sunset').card,
 'p-4 rounded-lg border flex items-center gap-4 group transition-all hover:shadow-md min-h-[110px]',
 ]">
 <div
 :class="[
 getItemTheme(over, 'sunset').icon,
 'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
 ]">
 <ShieldAlert class="w-5 h-5" />
 </div>
 <div
 class="flex-1 min-w-0 flex flex-col justify-center gap-2">
 <div
 class="flex items-center justify-between gap-2">
 <span
 class="text-base font-normal text-main-text truncate"
 >{{
 localize(
 over.permission_name,
 currentLanguage,
 )
 }}</span
 >
 <span
 :class="[
 getItemTheme(over, 'sunset')
 .badge,
 'px-2 py-0.5 text-white text-[14px] font-medium capitalize rounded shrink-0',
 ]">
 {{
 $tr(
 getItemTheme(
 over,
 "sunset",
 ).badgeLabel,
 )
 }}
 </span>
 </div>
 <div class="flex flex-col gap-1.5">
 <div
 class="flex items-center gap-1.5 text-[16px] text-main-text/60">
 <CalendarClock
 class="w-3.5 h-3.5 opacity-40" />
 <span class="truncate"
 >{{
 $tr("user.termination")
 }}:
 {{
 formatDate(
 over.end_date,
 currentLanguage,
 )
 }}</span
 >
 </div>
 <span
 :class="[
 getItemTheme(over, 'sunset')
 .subText,
 'text-[14px] font-normal capitalize opacity-80',
 ]">
 {{
 $tr(
 getItemTheme(
 over,
 "sunset",
 ).intentLabel,
 )
 }}
 </span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <!-- 5. History Timeline (Graph/Root View) -->
 <div v-if="historyAssignments.length > 0" class="space-y-4">
 <div class="flex items-center gap-3 px-1">
 <div
 class="w-1.5 h-6 bg-brand-blue rounded-full"></div>
 <h4
 class="text-base font-normal text-main-text capitalize">
 {{ $tr("user.assignment_history") }}
 </h4>
 </div>

 <div
 class="relative pl-8 space-y-4 before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-[2px] before:bg-card-border/40 before:rounded-full">
 <div
 v-for="item in historyAssignments"
 :key="item.id"
 class="relative group">
 <!-- History Connector Node -->
 <div
 :class="[
 'absolute -left-[35px] top-4 w-7 h-7 rounded-full border-2 bg-main-bg z-10 flex items-center justify-center transition-all duration-500 group-hover:scale-110',
 item.is_currently_valid
 ? 'border-brand-blue ring-4 ring-brand-blue/5'
 : 'border-card-border',
 ]">
 <Briefcase
 :class="[
 'w-3.5 h-3.5',
 item.is_currently_valid
 ? 'text-brand-blue'
 : 'text-main-text/30',
 ]" />
 </div>

 <div
 class="bg-transparent border border-card-border/60 rounded-lg p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 group-hover:border-accent/30 transition-all duration-300 group-hover:shadow-md min-h-[88px]">
 <div class="space-y-2 flex-1 min-w-0">
 <div class="flex items-center gap-3">
 <h4
 :class="[
 'text-base font-normal tracking-tight truncate',
 item.is_currently_valid
 ? 'text-brand-blue'
 : 'text-main-text',
 ]">
 {{
 localize(
 item.role_name,
 currentLanguage,
 )
 }}
 </h4>
 <span
 v-if="item.is_currently_valid"
 class="px-2 py-0.5 border border-brand-blue text-brand-blue text-[14px] font-medium capitalize tracking-wide rounded shrink-0">
 {{ $tr("common.active") }}
 </span>
 </div>
 <div
 class="flex flex-wrap items-center gap-y-1 gap-x-4 text-[14px] text-main-text/60">
 <span
 class="flex items-center gap-1.5">
 <UserCircle2
 class="w-3.5 h-3.5 opacity-40" />
 <span
 >{{
 $tr(
 "user.authorized_by",
 )
 }}:</span
 >
 <span
 class="text-main-text/80 font-normal"
 >{{
 localize(
 item.assigned_by,
 currentLanguage,
 )
 }}</span
 >
 </span>
 <span
 v-if="
 item.revoked_by &&
 item.revoked_by !==
 'System' &&
 !item.is_currently_valid
 "
 class="flex items-center gap-1.5">
 <ShieldAlert
 class="w-3.5 h-3.5 text-rose-500/60" />
 <span
 >{{
 $tr(
 "user.deactivated_by",
 )
 }}:</span
 >
 <span
 class="text-rose-600/80 font-normal"
 >{{
 localize(
 item.revoked_by,
 currentLanguage,
 )
 }}</span
 >
 </span>
 <span
 class="flex items-center gap-1.5">
 <Calendar
 class="w-3.5 h-3.5 opacity-40" />
 <span>{{
 formatDate(
 item.start_date,
 currentLanguage,
 )
 }}</span>
 </span>
 </div>
 </div>

 <div
 class="shrink-0 flex items-center md:items-end md:flex-col gap-2 md:gap-0.5">
 <div
 v-if="item.end_date"
 class="text-[16px] font-normal text-main-text/60 flex items-center gap-1.5">
 <span
 class="text-[14px] tracking-wide opacity-60"
 >{{ $tr("common.until") }}</span
 >
 <span class="font-medium">{{
 formatDate(
 item.end_date,
 currentLanguage,
 )
 }}</span>
 </div>
 <div
 v-else
 class="text-[14px] font-medium text-brand-blue/50 capitalize tracking-wide">
 {{ $tr("user.permanent") }}
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 <!-- End Left Column -->

 <!-- Right Column: Permanent Permissions -->
 <div
 class="overflow-y-auto custom-scrollbar pr-2 pb-4 flex flex-col flex-1">
 <div
 class="p-6 rounded-xl bg-card-bg border border-card-border shadow-soft h-full flex flex-col">
 <div
 class="flex items-center gap-3 mb-6 shrink-0 pb-4 border-b border-card-border/30">
 <div
 class="w-10 h-10 rounded-lg bg-brand-blue/10 flex items-center justify-center text-brand-blue">
 <ShieldCheck class="w-5 h-5" />
 </div>
 <div class="flex flex-col">
 <h5
 class="text-[14px] font-semibold text-main-text capitalize tracking-wide">
 {{ $tr("user.authorized_permissions") }}
 </h5>
 <span
 class="text-[14px] font-normal text-main-text/40 font-poppins"
 >{{ $tr("user.effective_rights") }}</span
 >
 </div>
 <div
 class="ml-auto bg-brand-blue text-white px-3 py-1 rounded-full text-[16px] font-medium">
 {{ user.permissions?.length || 0 }}
 </div>
 </div>

 <div
 class="grid grid-cols-1 xl:grid-cols-2 gap-3 flex-1 content-start">
 <div
 v-for="perm in user.permissions"
 :key="perm.slug"
 class="flex items-center gap-2 p-2.5 rounded-lg bg-main-bg border border-card-border/40 text-[14px] text-main-text/80 shadow-xs hover:border-brand-blue/30 hover:bg-brand-blue/2 transition-all">
 <div
 class="w-1.5 h-1.5 rounded-full bg-brand-blue shrink-0"></div>
 <span class="truncate font-medium">{{
 localize(perm.name, currentLanguage)
 }}</span>
 </div>

 <div
 v-if="!user.permissions?.length"
 class="text-center py-6 col-span-full">
 <span
 class="text-[16px] font-normal text-main-text/40 font-poppins"
 >{{
 $tr("user.no_custom_permissions")
 }}</span
 >
 </div>
 </div>
 </div>
 </div>
 </div>

  </div>
  <template #footer>
    <footer class="flex items-center justify-end gap-3 px-8 py-5 border-t border-card-border/60 bg-card-bg/20 shrink-0">
      <Button 
        variant="secondary" 
        class="h-11 px-6 font-bold tracking-tight capitalize border-card-border/60"
        @click="emit('close')"
      >
        {{ $tr('common.close') }}
      </Button>
    </footer>
  </template>
 </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
 width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
 background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
 background: var(--card-border);
 border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
 background: var(--brand-blue);
}
</style>
