<script setup lang="ts">
import { ref, watch, computed } from "vue";
import DeleteConfirmModal from "@/components/common/DeleteConfirmModal.vue";
import {
 Clock,
 Briefcase,
 ShieldCheck,
 Lock,
 History,
 ArrowRight,
 Calendar,
 UserCircle2,
 Shield,
 Plus,
 Pencil,
 Trash2,
 ArrowDownRight,
 ShieldAlert,
 Check,
 Timer,
 AlertCircle,
 Save,
 ChevronDown,
} from "lucide-vue-next";
import Button from "@/components/common/Button.vue";
import FormField from "@/components/common/FormField.vue";
import { useUserStore } from "@/stores/userStore";
import { localize } from "@/utils/format";

const props = defineProps<{
 user: any;
 selectedRole: string;
 roleStartDate: string;
 roleEndDate: string;
 errors: any;
}>();

const emit = defineEmits([
 "update:selectedRole",
 "update:roleStartDate",
 "update:roleEndDate",
 "open-confirm",
 "edit-schedule",
 "delete-schedule",
 "close",
]);

const userStore = useUserStore();

const onRoleSelect = (slug: string) => emit("update:selectedRole", slug);
const onStartDateChange = (val: any) => emit("update:roleStartDate", val);
const onEndDateChange = (val: any) => emit("update:roleEndDate", val);

const openConfirm = () => emit("open-confirm", "role");

const activeAssignment = computed(() => {
 return props.user?.assignments?.find(
 (a: any) => a.is_currently_valid && a.end_date,
 );
});

const revertsTo = computed(() => {
 if (!activeAssignment.value || !props.user?.assignments) return null;

 // Find the most recent "permanent" role (no end date) that isn't the current temporal one
 // and started before or during the current assignment.
 const baselineRoles = props.user.assignments
 .filter((a: any) => {
 const isPermanent = !a.end_date;
 const isNotCurrent = a.id !== activeAssignment.value.id;
 const activeStart = activeAssignment.value.start_date
 ? new Date(activeAssignment.value.start_date).getTime()
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

 // Sort by start_date descending - include ALL roles that have started (including active)
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
const showLifecycle = ref(false);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref<{ id: number; type: string } | null>(null);

const initiateDelete = (id: number, type: string) => {
 itemToDelete.value = { id, type };
 isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
 if (itemToDelete.value) {
 emit("delete-schedule", itemToDelete.value.id, itemToDelete.value.type);
 closeDeleteModal();
 }
};

const closeDeleteModal = () => {
 isDeleteModalOpen.value = false;
 itemToDelete.value = null;
};
</script>

<template>
 <div class="space-y-4 pb-4">
 <!-- Status Banner for Scheduled Changes -->
 <div
 v-if="
 activeAssignment ||
 (user.scheduled_roles && user.scheduled_roles.length > 0)
 "
 class="relative overflow-hidden bg-brand-blue/3 border border-brand-blue/10 rounded-xl p-3 mb-3">
 <div class="flex items-center gap-3">
 <div
 class="p-2 rounded-lg bg-brand-blue/10 border border-brand-blue/20">
 <History class="w-5 h-5 text-brand-blue" />
 </div>
 <div class="flex flex-col">
 <h5
 class="text-base font-semibold text-main-text tracking-tight">
 {{ $tr("user.scheduled_changes_detected") }}
 </h5>
 <p class="text-xs font-normal text-main-text/50">
 <span class="font-normal text-brand-blue">{{
 activeAssignment ? 1 : 0
 }}</span>
 {{ $tr("user.active_overrides") }}
 {{ $tr("common.and") }}
 <span class="font-normal text-indigo-500">{{
 user.scheduled_roles?.length || 0
 }}</span>
 {{ $tr("user.upcoming_overrides") }}
 {{ $tr("user.overrides_in_pipeline") }}
 </p>
 </div>
 </div>
 </div>

 <!-- Role Selection - Dynamic System Modules -->
 <div class="space-y-3">
 <div class="flex items-center gap-2 px-1 text-left">
 <div class="w-1 h-4 bg-brand-blue rounded-full"></div>
 <h3
 class="text-sm font-medium text-main-text/60 capitalize tracking-wide">
 {{ $tr("user.select_authority_tier") }}
 </h3>
 </div>

 <div class="flex flex-wrap gap-1 pt-0">
 <button
 v-for="role in userStore.allRoles"
 :key="role.slug"
 @click="onRoleSelect(role.slug)"
 :class="[
 'group/card relative px-3 py-1 rounded-full border transition-all text-left cursor-pointer flex items-center gap-2 min-h-[36px]',
 selectedRole === role.slug
 ? 'bg-brand-blue text-white border-brand-blue translate-y-[-1px]'
 : 'bg-main-bg border-card-border hover:border-accent/40 text-main-text/70',
 ]">
 <span
 :class="[
 'text-sm font-medium tracking-tight transition-colors truncate',
 selectedRole === role.slug
 ? 'text-white'
 : 'text-main-text/60 group-hover/card:text-accent',
 ]">
 {{ localize(role.name) || role.slug }}
 </span>

 <div
 v-if="selectedRole === role.slug"
 class="w-4 h-4 rounded-full bg-white/20 flex items-center justify-center">
 <Check class="w-3 h-3 text-white" />
 </div>
 </button>
 </div>
 </div>

 <div
 v-if="errors.role"
 class="text-[13px] text-rose-500 font-medium px-1 text-left">
 {{ errors.role }}
 </div>

 <!-- Validity Configuration Console -->
 <div
 class="relative mt-4 bg-main-bg rounded-xl border border-card-border p-4 overflow-hidden group/console">
 <div
 class="relative flex flex-col md:flex-row md:items-center justify-between gap-4 text-left">
 <div class="space-y-1">
 <div class="flex items-center gap-2">
 <div
 class="p-2 rounded-lg bg-brand-blue/10 border border-brand-blue/20">
 <Timer class="w-4 h-4 text-brand-blue" />
 </div>
 <h5
 class="text-lg font-semibold tracking-tight text-main-text">
 {{ $tr("user.validity_configuration") }}
 </h5>
 </div>
 <p class="text-xs font-normal text-main-text/60 ml-8">
 {{ $tr("user.configure_temporal_access_window") }}
 </p>
 </div>

 <div
 v-if="!roleStartDate && !roleEndDate"
 class="flex items-center gap-2 px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-full">
 <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
 <span
 class="text-sm font-medium text-emerald-600 capitalize tracking-wide"
 >{{ $tr("status.standard") }}</span
 >
 </div>
 </div>

 <div
 class="relative grid grid-cols-1 md:grid-cols-2 gap-3 mt-4 text-left">
 <!-- Start Date Control -->
 <div class="group/input space-y-2">
 <FormField
 :modelValue="roleStartDate"
 @update:modelValue="onStartDateChange"
 type="date"
 :label="$tr('user.activation_sequence')"
 :icon="Calendar"
 :placeholder="
 roleStartDate
 ? $tr('user.scheduled')
 : $tr('user.system_default')
 " />
 <p
 class="text-xs font-normal text-main-text/50 px-1 leading-relaxed">
 {{ $tr("user.leave_blank_immediate") }}
 </p>
 </div>

 <!-- End Date Control -->
 <div class="group/input space-y-2">
 <FormField
 :modelValue="roleEndDate"
 @update:modelValue="onEndDateChange"
 type="date"
 :label="$tr('user.termination_threshold')"
 :icon="Clock"
 :placeholder="
 roleEndDate
 ? $tr('status.expiration_set')
 : $tr('user.infinite_access')
 " />
 <p
 class="text-xs font-normal text-main-text/50 px-1 leading-relaxed">
 {{ $tr("user.optional_revocation") }}
 </p>
 </div>
 </div>
 </div>

 <!-- Role Lifecycle Management - Professional Grid -->
 <div class="mt-8 space-y-6">
 <div
 @click="showLifecycle = !showLifecycle"
 class="flex items-center justify-between px-2 cursor-pointer group/header">
 <div class="flex items-center gap-4">
 <div class="flex items-center gap-2">
 <div
 class="w-1.5 h-6 bg-brand-blue/50 rounded-full group-hover/header:bg-brand-blue transition-colors"></div>
 <h4
 class="text-lg font-semibold tracking-tight text-main-text/80 capitalize group-hover/header:text-main-text transition-colors">
 {{ $tr("user.role_lifecycle_management") }}
 </h4>
 </div>
 </div>

 <div
 class="flex items-center gap-2 px-3 py-1 rounded-lg bg-main-text/3 border border-card-border/60 group-hover/header:border-brand-blue/30 transition-all">
 <span
 class="text-[10px] font-medium text-main-text/40 capitalize tracking-widest"
 >{{
 showLifecycle
 ? $tr("common.collapse")
 : $tr("common.expand")
 }}</span
 >
 <ChevronDown
 :class="[
 'w-4 h-4 text-main-text/40 transition-transform duration-300',
 showLifecycle ? 'rotate-180' : '',
 ]" />
 </div>
 </div>

 <p
 v-if="!showLifecycle"
 class="text-xs font-normal text-main-text/30 ml-5 -mt-4 animate-in fade-in slide-in-from-left-2 transition-all">
 {{ $tr("user.click_to_view_temporal_roles") }}
 </p>

 <div
 v-show="showLifecycle"
 class="space-y-12 animate-in fade-in zoom-in-95 duration-300">
 <div class="px-2">
 <p
 class="text-sm font-normal text-main-text/50 leading-relaxed">
 {{ $tr("user.manage_temporal_role_states") }}
 </p>
 </div>

 <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
 <!-- Section 1: Active Authority Status (Expiry) -->
 <div class="xl:col-span-12 space-y-6">
 <div class="flex items-center justify-between px-2">
 <div class="flex items-center gap-3">
 <span
 class="text-sm font-normal text-main-text/50 capitalize tracking-wide"
 >{{ $tr("user.have_deadline_role") }}</span
 >
 <div class="h-px w-10 bg-card-border"></div>
 </div>
 </div>

 <div
 v-if="activeAssignment"
 class="relative overflow-hidden bg-main-bg border border-brand-blue/20 rounded-2xl p-6 transition-all hover:shadow-xl hover:shadow-accent/5 group">
 <!-- Premium Background Glow -->
 <div
 class="absolute -top-20 -right-20 w-64 h-64 bg-brand-blue/5 rounded-full blur-3xl pointer-events-none group-hover:bg-accent/10 transition-all"></div>

 <div
 class="relative flex flex-col lg:flex-row lg:items-center gap-10">
 <!-- Left: Identity & Primary Info -->
 <div class="flex-1 space-y-8">
 <div
 class="flex items-start justify-between">
 <div class="space-y-2">
 <div
 class="flex items-center gap-2 px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-full w-fit">
 <div
 class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
 <span
 class="text-[10px] font-bold text-amber-600 capitalize tracking-widest"
 >{{
 $tr("common.active")
 }}</span
 >
 </div>
 <h3
 class="text-2xl font-semibold text-main-text tracking-tight leading-none">
 {{ activeAssignment.role_name }}
 </h3>
 </div>

 <div
 class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
 <Button
 variant="secondary"
 size="sm"
 @click="
 emit(
 'edit-schedule',
 activeAssignment,
 'role',
 )
 "
 class="w-10 h-10 p-0 rounded-xl"
 :icon="Pencil" />
 <Button
 variant="secondary"
 size="sm"
 @click="
 initiateDelete(
 activeAssignment.id,
 'role',
 )
 "
 class="w-10 h-10 p-0 rounded-xl hover:text-rose-500 hover:border-rose-500"
 :icon="Trash2" />
 </div>
 </div>

 <div class="flex flex-wrap gap-8">
 <div class="flex items-center gap-4">
 <div
 class="w-12 h-12 rounded-xl bg-main-text/3 border border-card-border flex items-center justify-center overflow-hidden">
 <img
 v-if="
 activeAssignment.assigned_by_avatar
 "
 :src="
 activeAssignment.assigned_by_avatar
 "
 class="w-full h-full object-cover" />
 <UserCircle2
 v-else
 class="w-6 h-6 text-main-text/20" />
 </div>
 <div class="flex flex-col">
 <span
 class="text-xs font-medium text-main-text/30 capitalize tracking-widest leading-none"
 >{{
 $tr(
 "user.authorized_by",
 )
 }}</span
 >
 <span
 class="text-base font-semibold text-main-text mt-1"
 >{{
 activeAssignment.assigned_by ||
 "System"
 }}</span
 >
 </div>
 </div>

 <div class="flex items-center gap-4">
 <div
 class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-500">
 <Clock class="w-6 h-6" />
 </div>
 <div class="flex flex-col">
 <span
 class="text-xs font-medium text-rose-600/50 capitalize tracking-widest"
 >{{
 $tr("user.end_date")
 }}</span
 >
 <span
 class="text-base font-semibold text-rose-600 mt-1"
 >{{
 activeAssignment.end_date
 }}</span
 >
 </div>
 </div>
 </div>

 <div
 v-if="revertsTo"
 class="pt-5 border-t border-card-border/60">
 <div
 class="flex items-center gap-3 text-sm font-normal text-main-text/50 bg-main-text/2 p-3 rounded-xl border border-card-border/40">
 <History
 class="w-4 h-4 text-indigo-500/50" />
 <span
 >{{
 $tr("user.will_revert_to")
 }}
 <span
 class="text-indigo-600 font-semibold"
 >{{
 revertsTo.role_name
 }}</span
 ></span
 >
 </div>
 </div>
 </div>

 <!-- Right: Lifecycle Timeline Visualizer -->
 <div
 class="bg-main-text/2 border border-card-border/40 rounded-2xl p-8 min-w-[320px] flex flex-col gap-8">
 <div class="relative">
 <!-- Vertical Connect Line -->
 <div
 class="absolute left-[21px] top-6 bottom-6 w-px bg-card-border/40"></div>

 <div class="space-y-12">
 <div class="relative pl-12">
 <div
 class="absolute left-0 top-0 w-11 h-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 z-10 shadow-brand-green/10">
 <Check class="w-5 h-5" />
 </div>
 <div class="flex flex-col">
 <span
 class="text-xs font-medium text-main-text/30 capitalize tracking-widest leading-none"
 >{{
 $tr(
 "user.activated_on",
 )
 }}</span
 >
 <span
 class="text-base font-semibold text-main-text mt-1.5"
 >{{
 activeAssignment.start_date ||
 "Immediate"
 }}</span
 >
 </div>
 </div>

 <div class="relative pl-12">
 <div
 class="absolute left-0 top-0 w-11 h-11 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 z-10 shadow-amber-500/10">
 <Calendar class="w-5 h-5" />
 </div>
 <div class="flex flex-col">
 <span
 class="text-xs font-medium text-main-text/30 capitalize tracking-widest leading-none"
 >{{
 $tr(
 "user.scheduled_expiry",
 )
 }}</span
 >
 <span
 class="text-base font-semibold text-main-text mt-1.5"
 >{{
 activeAssignment.end_date
 }}</span
 >
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <div
 v-else
 class="bg-main-bg border border-card-border rounded-2xl p-10 flex flex-col items-center justify-center text-center space-y-4">
 <div
 class="w-16 h-16 rounded-3xl bg-main-text/3 border border-card-border flex items-center justify-center text-main-text/10">
 <ShieldCheck class="w-8 h-8" />
 </div>
 <div class="space-y-1">
 <h5
 class="text-base font-semibold text-main-text/40">
 {{ $tr("user.no_active_expiring_role") }}
 </h5>
 <p
 class="text-xs font-normal text-main-text/20 max-w-sm">
 {{ $tr("user.no_expiry_policy_desc") }}
 </p>
 </div>
 </div>
 </div>

 <!-- Section 2: Future Access Pipeline (Scheduled) -->
 <div class="xl:col-span-12 space-y-6">
 <div class="flex items-center justify-between px-2">
 <div class="flex items-center gap-3">
 <span
 class="text-sm font-normal text-main-text/50 capitalize tracking-wide"
 >{{
 $tr("user.future_access_pipeline")
 }}</span
 >
 <div class="h-px w-10 bg-card-border"></div>
 </div>
 </div>

 <div
 v-if="user.scheduled_roles?.length > 0"
 class="grid grid-cols-1 md:grid-cols-2 gap-6">
 <div
 v-for="item in user.scheduled_roles"
 :key="item.id"
 class="group relative bg-main-bg border border-card-border rounded-2xl p-5 transition-all hover:bg-indigo-500/[0.01] hover:border-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/3">
 <div class="flex flex-col gap-6">
 <div
 class="flex justify-between items-start">
 <div class="space-y-1">
 <div
 class="flex items-center gap-2">
 <span
 class="px-2.5 py-0.5 rounded-lg bg-indigo-600 text-white text-[10px] font-bold capitalize tracking-widest shadow-lg shadow-indigo-600/20">
 {{ $tr("common.grant") }}
 </span>
 <span
 class="text-xs font-medium text-main-text/40 tracking-wide"
 >•
 {{
 $tr("user.scheduled")
 }}</span
 >
 </div>
 <h4
 class="text-xl font-semibold text-main-text tracking-tight mt-1">
 {{ localize(item.role_name) }}
 </h4>
 </div>
 <div
 class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
 <Button
 variant="secondary"
 size="sm"
 @click="
 emit(
 'edit-schedule',
 item,
 'role',
 )
 "
 class="w-10 h-10 p-0 rounded-xl"
 :icon="Pencil" />
 <Button
 variant="secondary"
 size="sm"
 @click="
 initiateDelete(
 item.id,
 'role',
 )
 "
 class="w-10 h-10 p-0 rounded-xl hover:text-rose-500 hover:border-rose-500"
 :icon="Trash2" />
 </div>
 </div>

 <div
 class="grid grid-cols-1 sm:grid-cols-2 gap-4">
 <div
 class="p-4 rounded-xl bg-main-text/3 border border-card-border/60">
 <span
 class="text-xs font-medium text-main-text/30 capitalize tracking-widest block mb-2"
 >{{
 $tr("user.grant_scheduled")
 }}</span
 >
 <div
 class="flex items-center gap-2 text-sm font-semibold text-main-text">
 <Calendar
 class="w-4 h-4 text-indigo-500/40" />
 <span>{{
 item.start_date
 }}</span>
 </div>
 </div>
 <div
 class="p-4 rounded-xl bg-main-text/3 border border-card-border/60">
 <span
 class="text-xs font-medium text-main-text/30 capitalize tracking-widest block mb-2"
 >{{
 $tr("user.end_date")
 }}</span
 >
 <div
 class="flex items-center gap-2 text-sm font-semibold text-main-text">
 <Clock
 class="w-4 h-4 text-rose-500/40" />
 <span class="truncate">{{
 item.end_date ||
 $tr("user.no_expiration")
 }}</span>
 </div>
 </div>
 </div>

 <div
 class="flex items-center justify-between pt-4 border-t border-card-border/40">
 <div class="flex items-center gap-3">
 <div
 class="w-10 h-10 rounded-xl bg-main-text/3 border border-card-border/60 flex items-center justify-center overflow-hidden">
 <img
 v-if="
 item.assigned_by_avatar
 "
 :src="
 item.assigned_by_avatar
 "
 class="w-full h-full object-cover" />
 <UserCircle2
 v-else
 class="w-5 h-5 text-main-text/20" />
 </div>
 <div class="flex flex-col min-w-0">
 <span
 class="text-[10px] font-bold text-main-text/30 capitalize tracking-widest leading-none"
 >{{
 $tr("user.authorizer")
 }}</span
 >
 <span
 class="text-sm font-semibold text-main-text/50 truncate mt-1"
 >{{
 item.assigned_by ||
 "System"
 }}</span
 >
 </div>
 </div>
 <div class="flex flex-col items-end">
 <span
 class="text-[10px] font-bold text-main-text/30 capitalize tracking-widest leading-none"
 >{{
 $tr("user.countdown")
 }}</span
 >
 <span
 class="text-xs font-bold text-indigo-600 capitalize mt-1"
 >{{
 $tr(
 "user.pending_activation",
 )
 }}</span
 >
 </div>
 </div>
 </div>
 </div>
 </div>

 <div
 v-else
 class="bg-main-bg border border-dotted border-card-border rounded-2xl p-10 flex flex-col items-center justify-center text-center space-y-4">
 <div
 class="w-12 h-12 rounded-xl bg-indigo-500/3 border border-indigo-500/10 flex items-center justify-center text-indigo-500/20">
 <Plus class="w-6 h-6" />
 </div>
 <div class="space-y-1">
 <h5
 class="text-base font-semibold text-main-text/30">
 {{ $tr("user.no_scheduled_overrides") }}
 </h5>
 <p
 class="text-xs font-normal text-main-text/15 max-w-xs">
 {{ $tr("user.no_scheduled_desc") }}
 </p>
 </div>
 </div>
 </div>

 <!-- Section 3: Administrative Audit Trail -->
 <div
 v-if="historyAssignments.length > 0"
 class="xl:col-span-12 space-y-6">
 <div class="flex items-center justify-between px-2">
 <div class="flex items-center gap-3">
 <span
 class="text-sm font-normal text-main-text/50 capitalize tracking-wide"
 >{{
 $tr("user.administrative_audit_trail")
 }}</span
 >
 <div class="h-px w-10 bg-card-border"></div>
 </div>
 <div
 class="px-3 py-1 rounded-full bg-main-text/3 border border-card-border/60">
 <span
 class="text-[10px] font-bold text-main-text/40 capitalize tracking-widest">
 {{ historyAssignments.length }}
 {{ $tr("common.records") }}
 </span>
 </div>
 </div>

 <div class="relative space-y-4">
 <!-- Vertical Timeline Line -->
 <div
 class="absolute left-8 top-4 bottom-4 w-px bg-card-border/40 hidden sm:block"></div>

 <div
 v-for="item in historyAssignments"
 :key="item.id"
 class="group relative bg-main-bg border border-card-border/60 rounded-2xl p-5 transition-all hover:bg-main-text/[0.01] hover:border-card-border">
 <div
 class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative z-10">
 <div class="flex items-center gap-5">
 <!-- Timeline Dot for Desktop -->
 <div
 class="hidden sm:flex w-3 h-3 rounded-full bg-main-bg border-3 border-card-border group-hover:border-main-text/20 transition-colors z-20 -ml-[1.85rem]"></div>

 <div
 class="w-14 h-14 rounded-xl bg-main-text/3 flex items-center justify-center border border-card-border group-hover:border-main-text/10 transition-colors">
 <History
 class="w-8 h-8 text-main-text/10 group-hover/icon:text-accent/30 transition-colors duration-500" />
 </div>

 <div class="space-y-1.5">
 <div
 class="flex items-center gap-2">
 <h4
 class="text-base font-bold text-main-text tracking-tight">
 {{
 localize(item.role_name)
 }}
 </h4>
 <span
 v-if="!item.end_date"
 class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-500/10 text-amber-600 capitalize tracking-widest">
 {{
 $tr(
 "common.was_permanent",
 ) || "Permanent"
 }}
 </span>
 </div>
 <div
 class="flex flex-wrap items-center gap-3 text-xs font-medium text-main-text/40">
 <div
 class="flex items-center gap-1.5">
 <Calendar
 class="w-3.5 h-3.5 opacity-60" />
 <span>{{
 item.start_date ||
 "System"
 }}</span>
 </div>
 <ArrowRight
 class="w-3.5 h-3.5 opacity-30" />
 <div
 class="flex items-center gap-1.5">
 <Clock
 class="w-3.5 h-3.5 opacity-60" />
 <span>{{
 item.end_date ||
 $tr("common.revoked") ||
 "Revoked"
 }}</span>
 </div>
 </div>
 </div>
 </div>

 <div
 class="flex items-center gap-6 self-end sm:self-center">
 <div
 class="flex items-center gap-3 py-2 px-4 rounded-xl bg-main-text/3 border border-transparent group-hover:border-card-border/40 transition-all">
 <div
 class="w-10 h-10 rounded-xl bg-main-text/3 border border-card-border/60 flex items-center justify-center overflow-hidden">
 <img
 v-if="
 item.assigned_by_avatar
 "
 :src="
 item.assigned_by_avatar
 "
 class="w-full h-full object-cover grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition-all" />
 <UserCircle2
 v-else
 class="w-5 h-5 text-main-text/20" />
 </div>
 <div class="flex flex-col">
 <span
 class="text-[10px] font-bold text-main-text/30 capitalize tracking-widest"
 >{{
 $tr("user.authorizer")
 }}</span
 >
 <span
 class="text-sm font-bold text-main-text/50 group-hover:text-main-text/70 transition-colors">
 {{
 item.assigned_by ||
 "System"
 }}
 </span>
 </div>
 </div>

 <div
 v-if="
 item.revoked_by &&
 item.revoked_by !== 'System'
 "
 class="flex items-center gap-3 py-2 px-4 rounded-xl bg-rose-500/2 border border-transparent group-hover:border-rose-500/10 transition-all">
 <div
 class="w-10 h-10 rounded-xl bg-rose-500/5 border border-rose-500/10 flex items-center justify-center overflow-hidden">
 <img
 v-if="
 item.revoked_by_avatar
 "
 :src="
 item.revoked_by_avatar
 "
 class="w-full h-full object-cover grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition-all" />
 <ShieldAlert
 v-else
 class="w-5 h-5 text-rose-500/30" />
 </div>
 <div class="flex flex-col">
 <span
 class="text-[10px] font-bold text-rose-600/30 capitalize tracking-widest"
 >{{
 $tr(
 "user.deactivator",
 ) || "Deactivator"
 }}</span
 >
 <span
 class="text-sm font-bold text-rose-600/50 group-hover:text-rose-600/70 transition-colors">
 {{ item.revoked_by }}
 </span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 <div class="flex items-center justify-end gap-3 pt-6 border-t border-card-border/60">
 <Button
 variant="ghost"
 @click="emit('close')"
 class="hover:bg-main-text/5">
 {{ $tr('action.cancel') }}
 </Button>
 <Button
 variant="primary"
 :icon="Save"
 class=""
 @click="openConfirm">
 {{ $tr('user.update_role') }}
 </Button>
 </div>

 <DeleteConfirmModal
 :show="isDeleteModalOpen"
 :title="
 $tr('user.confirm_delete_role_title') ||
 'Confirm Role Reversion'
 "
 :description="
 $tr('user.confirm_delete_role_desc') ||
 'Are you sure you want to remove this scheduled role assignment? This action will immediately revert the user to their baseline authority or next logical assignment.'
 "
 @close="closeDeleteModal"
 @confirm="confirmDelete" />
 </div>
</template>
