<script setup lang="ts">
import StatusBadge from "@/components/common/StatusBadge.vue";
import {
 Pencil,
 Eye,
 Trash2,
 UserCircle2,
 ShieldCheck,
 Building2,
 Library,
 Minus,
} from "lucide-vue-next";
import { useUserStore } from "@/stores/userStore";
import { useAuthStore } from "@/stores/authStore";
import { useLanguageStore } from "@/stores/languageStore";
import { storeToRefs } from "pinia";
import { computed, getCurrentInstance } from "vue";
import DataTable from "@/components/common/DataTable.vue";
import TableColumn from "@/components/common/TableColumn.vue";
import ActionDropdown from "@/components/common/ActionDropdown.vue";
import { localize, formatDate, formatTime, capitalize } from "@/utils/format";
import { useSecurity } from "@/composables/useSecurity";

const props = defineProps<{
 canEdit: boolean;
 canDelete: boolean;
 selectedIds: number[];
}>();

const emit = defineEmits([
 "update:selectedIds",
 "view",
 "edit",
 "delete",
 "sort",
]);

const userStore = useUserStore();
const authStore = useAuthStore();
const languageStore = useLanguageStore();
const { users, loading } = storeToRefs(userStore);
const { user: currentUser } = storeToRefs(authStore);
const { currentLanguage } = storeToRefs(languageStore);

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const getLibraryColor = (libraryId: number | null) => {
 if (!libraryId)
 return "bg-indigo-500/10 text-indigo-600 border-indigo-500/20";

 const colors = [
 "bg-emerald-500/10 text-emerald-600 border-emerald-500/20 shadow-brand-green/5",
 "bg-brand-blue/10 text-brand-blue border-brand-blue/20",
 "bg-amber-500/10 text-amber-600 border-amber-500/20 shadow-amber-500/5",
 "bg-rose-500/10 text-rose-600 border-rose-500/20",
 "bg-violet-500/10 text-violet-600 border-violet-500/20 shadow-violet-500/5",
 "bg-orange-500/10 text-orange-600 border-orange-500/20 shadow-orange-500/5",
 "bg-cyan-500/10 text-cyan-600 border-cyan-500/20 shadow-cyan-500/5",
 "bg-fuchsia-500/10 text-fuchsia-600 border-fuchsia-500/20 shadow-fuchsia-500/5",
 ];

 return colors[libraryId % colors.length];
};

const { canModifyUser } = useSecurity();

const getUserActions = (user: any, canEdit: boolean, canDelete: boolean) => {
 const actions = [];
 const isSelf = user.id === currentUser.value?.id;
 const canModify = canModifyUser(user);

 // View action (Updated to brand-blue)
 actions.push({
 label: $tr("action.view"),
 icon: Eye,
 colorClass: "text-brand-blue",
 onClick: (u: any) => emit("view", u),
 });

 if (canEdit && canModify) {
 actions.push({
 label: $tr("action.edit"),
 icon: Pencil,
 colorClass: "text-amber-500",
 onClick: (u: any) => emit("edit", u),
 });
 }

 if (canDelete && canModify) {
 actions.push({
 label: $tr("user.remove"),
 icon: Trash2,
 colorClass: "text-rose-600",
 onClick: (u: any) => emit("delete", u),
 });
 }
 return actions;
};

// Dynamic Role Styling - High Contrast Palette
const getRoleStyles = (role: any) => {
 if (!role)
 return "bg-brand-blue/10 text-brand-blue border-brand-blue/20";

 const slug = role.slug?.toLowerCase() || "";

 // Admins -> Blue
 if (slug.includes("admin") || slug.includes("super"))
 return "bg-brand-blue/10 text-brand-blue border-brand-blue/30";

 // Librarians -> Orange/Red
 if (slug.includes("lib"))
 return "bg-rose-500/10 text-rose-600 border-rose-500/30 dark:text-rose-400";

 // Teachers/Staff -> Yellow
 if (
 slug.includes("teach") ||
 slug.includes("staff") ||
 slug.includes("inst")
 )
 return "bg-amber-500/10 text-amber-600 border-amber-500/30 shadow-amber-500/5 dark:text-amber-400";

 // Students -> Green
 if (slug.includes("std") || slug.includes("stud"))
 return "bg-emerald-500/10 text-emerald-600 border-emerald-500/30 shadow-brand-green/5 dark:text-emerald-400";

 // Others -> Default Blue
 return "bg-brand-blue/10 text-brand-blue border-brand-blue/30";
};
</script>

<template>
 <DataTable
 :items="users"
 :loading="loading"
 :empty-icon="UserCircle2"
 :empty-title="$tr('user.directory_empty')"
 :empty-desc="$tr('user.no_matches')"
 selectable
 :selected="selectedIds"
 :sort-by="userStore.filters.sort_by"
 :sort-order="userStore.filters.sort_order"
 @sort="(key) => emit('sort', key)"
 @update:selected="emit('update:selectedIds', $event)"
 row-key="id">
 
 <TableColumn field="registration_id" :header="$tr('user.info.registration_no') || 'Reg. No.'" width="160px" sortable>
 <template #default="{ row: user }">
 <span
 class="px-3 py-1.5 rounded-xl bg-brand-blue/5 text-brand-blue text-[13px] font-normal border border-brand-blue/10 tracking-wide font-mono shrink-0 whitespace-nowrap">
 #{{
 user.info?.registration_id ||
 user.id_number ||
 "N/A"
 }}
 </span>
 </template>
 </TableColumn>

 <TableColumn field="name" :header="$tr('user.member')" width="280px" sortable>
 <template #default="{ row: user }">
 <div class="flex items-center gap-3 py-1">
 <!-- Profile Picture or Avatar -->
 <img
 v-if="user.profile_picture"
 :src="user.profile_picture"
 class="w-9 h-9 rounded-full object-cover bg-main-text/5 border border-card-border shrink-0"
 alt="" />
 <div
 v-else
 class="w-9 h-9 rounded-full bg-brand-blue/10 text-brand-blue border border-brand-blue/20 flex items-center justify-center shrink-0">
 <span class="text-sm font-normal">{{
 localize(user.name).substring(0, 1)
 }}</span>
 </div>

 <!-- Name and Email -->
 <div class="flex flex-col text-left font-sans min-w-0">
 <div class="flex items-center gap-2">
 <span
 class="text-sm md:text-base font-normal text-main-text group-hover:text-accent transition-colors truncate">
 {{ capitalize(localize(user.name, currentLanguage).split(' ')[0]) }}
 {{ user.info?.father_name ? capitalize(localize(user.info.father_name, currentLanguage).split(' ')[0]) : "" }}
 {{ user.info?.grandfather_name ? capitalize(localize(user.info.grandfather_name, currentLanguage).split(' ')[0]) : "" }}
 </span>
 <span
 v-if="user.id === currentUser?.id"
 class="px-2 py-0.5 rounded-xl bg-brand-blue/10 text-brand-blue text-[10px] font-normal border border-brand-blue/20 tracking-wide">
 {{ $tr("user.you_badge") }}
 </span>
 </div>
 <div
 class="text-xs font-normal text-main-text/30 group-hover:text-accent-hover transition-all duration-300 tracking-tight truncate">
 {{ user.email }}
 </div>
 </div>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="role" :header="$tr('role.role')" align="center" width="160px" sortable>
 <template #default="{ row: user }">
 <div
 v-if="user.role"
 :class="[
 'premium-badge shadow-xs transition-all hover:scale-105 hover:cursor-default tracking-tight px-3 py-1.5 inline-flex items-center',
 getRoleStyles(user.role),
 ]">
 <ShieldCheck class="w-3.5 h-3.5 mr-1.5" />
 {{ capitalize(localize(user.role.name, currentLanguage)) }}
 </div>
 <span
 v-else
 class="text-xs text-main-text/20 font-normal tracking-tight"
 >{{ $tr("user.no_role") }}</span
 >
 </template>
 </TableColumn>




 <TableColumn field="is_active" :header="$tr('user.status')" align="center" width="120px" sortable>
 <template #default="{ row: user }">
 <StatusBadge :status="user.is_active ? 'active' : 'inactive'" />
 </template>
 </TableColumn>

 <TableColumn field="updated_at" :header="$tr('user.updated_at')" align="center" width="180px" sortable>
 <template #default="{ row: user }">
 <div class="flex flex-col items-center">
 <span
 class="text-sm md:text-base font-normal text-main-text/80 group-hover:text-accent transition-colors"
 >{{ formatDate(user.updated_at, currentLanguage) }}</span
 >
 <span
 class="text-xs text-main-text/40 font-normal group-hover:text-accent/60 transition-colors"
 >{{ formatTime(user.updated_at, currentLanguage) }}</span
 >
 </div>
 </template>
 </TableColumn>

 <TableColumn header="" align="right">
 <template #default="{ row: user, index }">
 <div class="flex justify-end p-1">
 <ActionDropdown
 :actions="getUserActions(user, canEdit, canDelete)"
 :item="user"
 :index="index"
 :total="users.length" />
 </div>
 </template>
 </TableColumn>

 <template #footer>
 <slot name="footer" />
 </template>
 </DataTable>
</template>
