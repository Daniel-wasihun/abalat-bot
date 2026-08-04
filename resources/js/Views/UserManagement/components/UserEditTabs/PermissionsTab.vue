<script setup lang="ts">
import {
 Check,
 Calendar,
 AlertCircle,
 Pencil,
 Trash2,
 ShieldCheck,
 UserCircle2,
 History,
 Save,
 ArrowRight,
 Lock,
 Plus,
 Clock,
 Timer,
 ChevronDown,
} from "lucide-vue-next";
import { groupPermissionsByModule } from "@/utils/permissionUtils";
import { computed, ref } from "vue";
import DeleteConfirmModal from "@/components/common/DeleteConfirmModal.vue";
import Button from "@/components/common/Button.vue";
import FormField from "@/components/common/FormField.vue";
import { useUserStore } from "@/stores/userStore";
import { localize } from "@/utils/format";

const props = defineProps<{
 user: any;
 selectedPermissions: string[];
 permStartDate: string;
 permEndDate: string;
}>();

const emit = defineEmits([
 "update:selectedPermissions",
 "update:permStartDate",
 "update:permEndDate",
 "toggle-permission",
 "open-confirm",
 "edit-schedule",
 "delete-schedule",
 "close",
]);

const userStore = useUserStore();

const groupedPermissions = computed(() =>
 groupPermissionsByModule(userStore.allPermissions),
);

const isSelected = (slug: string) => props.selectedPermissions.includes(slug);

const togglePermission = (slug: string) => emit("toggle-permission", slug);
const onStartDateChange = (val: any) => emit("update:permStartDate", val);
const onEndDateChange = (val: any) => emit("update:permEndDate", val);
const openConfirm = () => emit("open-confirm", "permissions");

const activeOverridenPermissions = computed(() => {
 const now = new Date();
 return (
 props.user?.pending_permissions?.filter((p: any) => {
 // Highly robust check: Use backend flag if available, otherwise fallback to date math
 if (p.is_currently_active !== undefined) {
 return p.is_currently_active && p.end_date;
 }

 const start = p.start_date ? new Date(p.start_date) : null;
 const end = p.end_date ? new Date(p.end_date) : null;
 return (!start || start <= now) && end;
 }) || []
 );
});

const futurePendingPermissions = computed(() => {
 const now = new Date();
 return (
 props.user?.pending_permissions?.filter((p: any) => {
 // If backend flag exists, use it to exclude currently active ones
 if (p.is_currently_active !== undefined) {
 return !p.is_currently_active;
 }

 const start = p.start_date ? new Date(p.start_date) : null;
 return start && start > now;
 }) || []
 );
});
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

const showLifecycle = ref(false);
</script>

<template>
 <div class="space-y-6 pb-6">
 <!-- Super Admin Bypass Banner -->
 <div
 v-if="user?.is_super_admin"
 class="relative overflow-hidden bg-rose-500/3 border border-rose-500/10 rounded-2xl p-4 mb-4">
 <div class="flex items-center gap-4 text-left">
 <div
 class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20">
 <ShieldCheck class="w-6 h-6 text-rose-500" />
 </div>
 <div class="flex flex-col">
 <h5
 class="text-lg font-semibold text-rose-600 tracking-tight">
 {{ $tr("user.super_admin_bypass_active") }}
 </h5>
 <p class="text-xs font-normal text-rose-500/70 max-w-xl">
 {{ $tr("user.super_admin_bypass_desc") }}
 </p>
 </div>
 </div>
 </div>
 <!-- Status Banner for Scheduled Changes -->
 <div
 v-if="
 activeOverridenPermissions.length > 0 ||
 futurePendingPermissions.length > 0
 "
 class="relative overflow-hidden bg-brand-blue/3 border border-brand-blue/10 rounded-2xl p-4 mb-4">
 <div class="flex items-center gap-4">
 <div
 class="p-3 rounded-xl bg-brand-blue/10 border border-brand-blue/20">
 <History class="w-6 h-6 text-brand-blue" />
 </div>
 <div class="flex flex-col">
 <h5
 class="text-lg font-semibold text-main-text tracking-tight">
 {{ $tr("user.scheduled_changes_detected") }}
 </h5>
 <p class="text-sm font-normal text-main-text/50">
 <span class="font-bold text-brand-blue">{{
 activeOverridenPermissions.length
 }}</span>
 {{ $tr("user.active_overrides") }}
 {{ $tr("common.and") }}
 <span class="text-indigo-500 font-normal">{{
 futurePendingPermissions.length
 }}</span>
 {{ $tr("user.upcoming_overrides") }}
 {{ $tr("user.overrides_in_pipeline") }}
 </p>
 </div>
 </div>
 </div>
 <!-- Temporal Settings Console (Shared design with RoleTab) -->
 <div
 class="relative mt-2 bg-main-bg rounded-xl border border-card-border p-4 overflow-hidden group/console">
 <div
 class="relative flex flex-col md:flex-row md:items-center justify-between gap-4 border-card-border/40 pb-0 text-left">
 <div class="space-y-1">
 <div class="flex items-center gap-2">
 <div
 class="p-2 rounded-lg bg-brand-blue/10 border border-brand-blue/20">
 <Timer class="w-4 h-4 text-brand-blue" />
 </div>
 <h5
 class="text-lg font-semibold tracking-tight text-main-text">
 {{ $tr("user.temporal_policy_override") }}
 </h5>
 </div>
 <p class="text-xs font-normal text-main-text/60 ml-8">
 {{ $tr("user.apply_overrides_to_selected") }}
 </p>
 </div>

 <div
 v-if="!permStartDate && !permEndDate"
 class="flex items-center gap-2 px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-full">
 <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
 <span
 class="text-sm font-medium text-emerald-600 capitalize tracking-wide"
 >{{ $tr("status.standard") }}</span
 >
 </div>
 </div>

 <div class="relative grid grid-cols-1 md:grid-cols-2 gap-3 mt-4 text-left">
 <div class="group/input space-y-2">
 <FormField
 :modelValue="permStartDate"
 @update:modelValue="onStartDateChange"
 type="date"
 :label="$tr('user.override_activation')"
 :icon="Calendar"
 :placeholder="permStartDate ? $tr('user.scheduled') : $tr('user.system_default')" />
 <p
 class="text-xs font-normal text-main-text/50 px-1 leading-relaxed">
 {{ $tr("user.leave_blank_immediate") }}
 </p>
 </div>

 <div class="group/input space-y-2">
 <FormField
 :modelValue="permEndDate"
 @update:modelValue="onEndDateChange"
 type="date"
 :label="$tr('user.override_termination')"
 :icon="Clock"
 :placeholder="permEndDate ? $tr('status.expiration_set') : $tr('user.infinite_access')" />
 <p
 class="text-xs font-normal text-main-text/50 px-1 leading-relaxed">
 {{ $tr("user.optional_revocation") }}
 </p>
 </div>
 </div>
 </div>

 <div
 v-for="(perms, module) in groupedPermissions"
 :key="module"
 class="space-y-4">
 <div class="flex items-center gap-4 px-2">
 <div class="flex items-center gap-2">
 <div class="w-1 h-5 bg-brand-blue/50 rounded-full"></div>
 <h4
 class="text-sm font-medium tracking-wide text-main-text/60 capitalize">
 {{ $tr("module." + module.toLowerCase()) }}
 </h4>
 </div>
 <div class="flex-1 h-px bg-card-border/60"></div>
 </div>

 <div
 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
 <button
 v-for="perm in perms"
 :key="perm.id"
 @click="togglePermission(perm.slug)"
 :class="[
 'group/perm relative flex items-center justify-between px-4 py-2 rounded-xl border transition-all text-left cursor-pointer h-12',
 isSelected(perm.slug)
 ? 'bg-brand-blue/[0.04] border-brand-blue'
 : 'bg-main-bg border-card-border hover:border-accent/30',
 ]">
 <div class="flex flex-col min-w-0 pr-4">
 <span
 :class="[
 'text-sm font-medium tracking-tight transition-colors truncate',
 isSelected(perm.slug)
 ? 'text-brand-blue'
 : 'text-main-text/90',
 ]"
 >{{ localize(perm.name) }}</span
 >
 <span
 class="text-xs font-medium text-main-text/30 capitalize tracking-wide truncate"
 >{{ perm.slug }}</span
 >
 </div>

 <!-- Sleek Toggle Switch -->
 <div
 :class="[
 'w-8 h-4 rounded-full relative transition-all duration-300 p-0.5 shrink-0',
 isSelected(perm.slug)
 ? 'bg-brand-blue'
 : 'bg-main-text/15',
 ]">
 <div
 :class="[
 'w-3 h-3 rounded-full bg-white transition-all duration-300 transform',
 isSelected(perm.slug)
 ? 'translate-x-4'
 : 'translate-x-0',
 ]"></div>
 </div>
 </button>
 </div>
 </div>

 <!-- Permission Lifecycle Management - Professional Grid -->
 <div class="mt-8 space-y-6">
 <div
 @click="showLifecycle = !showLifecycle"
 class="flex items-center justify-between px-2 cursor-pointer group/header">
 <div class="flex items-center gap-4">
 <div class="flex items-center gap-2">
 <div
 class="w-1.5 h-6 bg-indigo-500/50 rounded-full group-hover/header:bg-indigo-500 transition-colors"></div>
 <h4
 class="text-lg font-semibold tracking-tight text-main-text/80 capitalize group-hover/header:text-main-text transition-colors">
 {{ $tr("user.permission_lifecycle_management") }}
 </h4>
 </div>
 </div>

 <div
 class="flex items-center gap-2 px-3 py-1 rounded-lg bg-main-text/3 border border-card-border/60 group-hover/header:border-indigo-500/30 transition-all">
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
 {{ $tr("user.click_to_view_temporal_overrides") }}
 </p>

 <div
 v-show="showLifecycle"
 class="space-y-10 animate-in fade-in zoom-in-95 duration-300">
 <div class="px-2">
 <p class="text-sm font-normal text-main-text/50 leading-relaxed">
 {{ $tr("user.manage_temporal_role_states") }}
 </p>
 </div>

 <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
 <!-- Section 1: Active Policy Overrides (Expiry) -->
 <div class="xl:col-span-12 space-y-6">
 <div class="flex items-center justify-between px-2">
 <div class="flex items-center gap-3">
 <span
 class="text-sm font-normal text-main-text/50 capitalize tracking-wide"
 >{{ $tr("user.active_overrides") }}</span
 >
 <div class="h-px w-10 bg-card-border"></div>
 </div>
 </div>

 <div
 v-if="activeOverridenPermissions.length > 0"
 class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 <div
 v-for="item in activeOverridenPermissions"
 :key="item.id"
 class="relative overflow-hidden bg-main-bg border border-brand-blue/20 rounded-2xl p-5 transition-all hover:shadow-xl hover:shadow-accent/5 group">
 <div class="flex flex-col gap-6">
 <div class="flex justify-between items-start">
 <div class="space-y-1">
 <div class="flex items-center gap-2">
 <div
 :class="[
 'px-2.5 py-0.5 rounded-lg text-xs font-medium capitalize tracking-wide',
 item.action === 'revoke' ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === '0'
 ? 'border border-rose-600 text-rose-600'
 : 'border border-emerald-600 text-emerald-600',
 ]">
 {{
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0"
 ? $tr("common.revoke")
 : $tr("common.grant")
 }}
 </div>
 <span
 class="text-[10px] font-bold text-amber-600 capitalize tracking-widest"
 >{{ $tr("common.active") }}</span
 >
 </div>
 <h4
 class="text-xl font-semibold text-main-text tracking-tight mt-1">
 {{ localize(item.permission_name) }}
 </h4>
 </div>

 <div
 class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
 <Button
 variant="secondary"
 size="sm"
 class="w-10 h-10 p-0 rounded-xl"
 @click="
 emit(
 'edit-schedule',
 item,
 'permission',
 )
 "
 :icon="Pencil" />
 <Button
 variant="secondary"
 size="sm"
 class="w-10 h-10 p-0 rounded-xl hover:text-rose-500 hover:border-rose-500"
 @click="
 initiateDelete(
 item.id,
 'permission',
 )
 "
 :icon="Trash2" />
 </div>
 </div>

 <div class="grid grid-cols-2 gap-4">
 <div
 class="p-4 rounded-xl bg-main-text/3 border border-card-border/60">
 <span
 class="text-xs font-medium text-main-text/40 capitalize tracking-wide block mb-1.5"
 >{{
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0"
 ? $tr(
 "user.revoke_scheduled",
 )
 : $tr(
 "user.grant_scheduled",
 )
 }}</span
 >
 <div
 class="flex items-center gap-2 text-sm font-semibold text-main-text">
 <Check
 class="w-4 h-4 text-emerald-500/40" />
 <span>{{
 item.start_date || "Immediate"
 }}</span>
 </div>
 </div>
 <div
 class="p-4 rounded-xl bg-main-text/3 border border-card-border/60">
 <span
 class="text-xs font-normal text-rose-600/70 capitalize tracking-wide block mb-1.5"
 >{{ $tr("user.end_date") }}</span
 >
 <div
 class="flex items-center gap-2 text-sm font-normal text-rose-600">
 <Clock class="w-4 h-4" />
 <span>{{ item.end_date }}</span>
 </div>
 </div>
 </div>

 <div
 class="flex items-center justify-between pt-4 border-t border-card-border/40">
 <div class="flex items-center gap-4">
 <div
 class="w-10 h-10 rounded-xl bg-main-text/3 border border-card-border/60 flex items-center justify-center overflow-hidden">
 <img
 v-if="item.assigned_by_avatar"
 :src="item.assigned_by_avatar"
 class="w-full h-full object-cover" />
 <UserCircle2
 v-else
 class="w-5 h-5 text-main-text/20" />
 </div>
 <div class="flex flex-col min-w-0">
 <span
 class="text-xs font-normal text-main-text/40 capitalize tracking-wide leading-none"
 >{{
 $tr("user.authorized_by")
 }}</span
 >
 <span
 class="text-xs font-medium text-main-text/50 truncate mt-0.5"
 >{{
 item.assigned_by || "System"
 }}</span
 >
 </div>
 </div>
 <div class="flex flex-col items-end">
 <span
 class="text-xs font-normal text-main-text/40 capitalize tracking-wide leading-none"
 >{{ $tr("common.status") }}</span
 >
 <span
 class="text-xs font-normal text-amber-600 mt-0.5"
 >{{ $tr("status.active") }}</span
 >
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
 <h5 class="text-base font-semibold text-main-text/40">
 {{ $tr("user.no_active_permission_overrides") }}
 </h5>
 <p
 class="text-xs font-normal text-main-text/20 max-w-sm">
 {{ $tr("user.no_perm_expiry_desc") }}
 </p>
 </div>
 </div>
 </div>

 <!-- Section 2: Future Overrides Pipeline (Scheduled) -->
 <div class="xl:col-span-12 space-y-6">
 <div class="flex items-center justify-between px-2">
 <div class="flex items-center gap-3">
 <span
 class="text-sm font-normal text-main-text/50 capitalize tracking-wide"
 >{{ $tr("user.future_overrides_pipeline") }}</span
 >
 <div class="h-px w-10 bg-card-border"></div>
 </div>
 </div>

 <div
 v-if="futurePendingPermissions.length > 0"
 class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 <div
 v-for="item in futurePendingPermissions"
 :key="item.id"
 class="group relative bg-main-bg border border-card-border rounded-2xl p-5 transition-all hover:bg-indigo-500/[0.01] hover:border-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/3">
 <div class="flex flex-col gap-6">
 <div class="flex justify-between items-start">
 <div class="space-y-1">
 <div class="flex items-center gap-2">
 <div
 :class="[
 'px-2.5 py-0.5 rounded-lg text-xs font-medium capitalize tracking-wide',
 item.action === 'revoke' ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === '0'
 ? 'border border-rose-600 text-rose-600'
 : 'border border-emerald-600 text-emerald-600',
 ]">
 {{
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0"
 ? $tr(
 "user.revoke_scheduled",
 )
 : $tr(
 "user.grant_scheduled",
 )
 }}
 </div>
 <span
 class="text-xs font-normal text-main-text/40 capitalize tracking-wide truncate"
 >{{ $tr("user.revocation_badge") }}</span
 >
 </div>
 <h4
 class="text-xl font-semibold text-main-text tracking-tight mt-1">
 {{ localize(item.permission_name) }}
 </h4>
 </div>

 <div
 class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
 <Button
 variant="secondary"
 size="sm"
 class="w-10 h-10 p-0 rounded-xl"
 @click="
 emit(
 'edit-schedule',
 item,
 'permission',
 )
 "
 :icon="Pencil" />
 <Button
 variant="secondary"
 size="sm"
 class="w-10 h-10 p-0 rounded-xl hover:text-rose-500 hover:border-rose-500"
 @click="
 initiateDelete(
 item.id,
 'permission',
 )
 "
 :icon="Trash2" />
 </div>
 </div>

 <div class="grid grid-cols-2 gap-4">
 <div
 class="p-4 rounded-xl bg-main-text/3 border border-card-border/60">
 <span
 class="text-xs font-medium text-main-text/30 tracking-wide block mb-1.5"
 >{{
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0"
 ? $tr(
 "user.revoke_scheduled",
 )
 : $tr(
 "user.grant_scheduled",
 )
 }}</span
 >
 <div
 class="flex items-center gap-2 text-sm font-semibold text-main-text">
 <Calendar
 class="w-4 h-4 text-indigo-500/40" />
 <span>{{ item.start_date }}</span>
 </div>
 </div>
 <div
 class="p-4 rounded-xl bg-main-text/3 border border-card-border/60">
 <span
 class="text-xs font-medium text-main-text/30 tracking-wide block mb-1.5"
 >{{ $tr("user.end_date") }}</span
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
 <div class="flex items-center gap-4">
 <div
 class="w-10 h-10 rounded-xl bg-main-text/3 border border-card-border/60 flex items-center justify-center overflow-hidden">
 <img
 v-if="item.assigned_by_avatar"
 :src="item.assigned_by_avatar"
 class="w-full h-full object-cover" />
 <UserCircle2
 v-else
 class="w-5 h-5 text-main-text/20" />
 </div>
 <div class="flex flex-col min-w-0">
 <span
 class="text-xs font-medium text-main-text/30 tracking-wide leading-none"
 >
 {{
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0"
 ? $tr("user.revoker")
 : $tr("user.authorizer")
 }}
 </span>
 <span
 class="text-xs font-medium text-main-text/50 truncate mt-0.5"
 >{{
 item.assigned_by || "System"
 }}</span>
 </div>
 </div>
 <div class="flex flex-col items-end">
 <span
 class="text-xs font-medium text-main-text/30 tracking-wide leading-none"
 >{{ $tr("user.countdown") }}</span
 >
 <span
 :class="[
 'text-xs font-medium mt-0.5',
 item.action === 'revoke' ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === '0'
 ? 'text-rose-600'
 : 'text-indigo-600',
 ]">
 {{
 item.action === "revoke" ||
 item.is_grant === false ||
 item.is_grant === 0 ||
 item.is_grant === "0"
 ? $tr(
 "user.pending_revocation",
 )
 : $tr(
 "user.pending_activation",
 )
 }}
 </span>
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
 {{ $tr("user.no_scheduled_perm_desc") }}
 </p>
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
 {{ $tr('user.update_permissions') }}
 </Button>
 </div>

 <DeleteConfirmModal
 :show="isDeleteModalOpen"
 :title="$tr('user.confirm_delete_title') || 'Confirm Removal'"
 :description="
 $tr('user.confirm_delete_desc') ||
 'Are you sure you want to remove this scheduled override? This action will immediately revert the user to the default role-based policy for this permission.'
 "
 @close="closeDeleteModal"
 @confirm="confirmDelete" />
 </div>
</template>
