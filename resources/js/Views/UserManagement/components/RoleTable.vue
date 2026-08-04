<script setup lang="ts">
import StatusBadge from "@/components/common/StatusBadge.vue";
import { Shield, Fingerprint, Eye, Pencil, Trash2 } from "lucide-vue-next";
import { useRoleStore } from "@/stores/roleStore";
import { storeToRefs } from "pinia";
import { computed, getCurrentInstance } from "vue";
import DataTable from "@/components/common/DataTable.vue";
import TableColumn from "@/components/common/TableColumn.vue";
import ActionDropdown from "@/components/common/ActionDropdown.vue";
import { localize } from "@/utils/format";
import { useSecurity } from "@/composables/useSecurity";

const props = defineProps<{
 canEdit: boolean;
 canDelete: boolean;
 selectedIds: number[];
}>();

const emit = defineEmits([
 "view",
 "edit",
 "delete",
 "update:selectedIds",
 "sort",
]);

const roleStore = useRoleStore();
const { roles, loading } = storeToRefs(roleStore);
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
const { isSuperAdmin, canModifyRole } = useSecurity();
const canEditItem = (role: any) => isSuperAdmin.value || canModifyRole(role);

const getRoleActions = (role: any) => {
 const actions = [];
 const isSystem = !!role.is_system_level;
 const canModify = isSuperAdmin.value || canModifyRole(role);
 const showEdit = props.canEdit || isSuperAdmin.value;
 const showDelete = props.canDelete || isSuperAdmin.value;

 actions.push({
 label: $tr("action.view"),
 icon: Eye,
 colorClass: "text-brand-blue",
 onClick: (r: any) => emit("view", r),
 });

 // Edit Action: always show for super admin, else show only if canEdit prop is true
 if (showEdit) {
 actions.push({
 label: $tr("action.edit"),
 icon: Pencil,
 colorClass: "text-amber-500",
 disabled: !canModify,
 onClick: (r: any) => emit("edit", r),
 });
 }

 // Delete Action: always show for super admin, else show only if canDelete prop is true
 if (showDelete) {
 actions.push({
 label: $tr("action.delete"),
 icon: Trash2,
 colorClass: "text-rose-600",
 disabled: !canModify,
 onClick: (r: any) => emit("delete", r),
 });
 }

 return actions;
};
</script>

<template>
 <DataTable
 :items="roles"
 :loading="loading"
 :empty-icon="Shield"
 :empty-title="$tr('role.no_roles')"
 :empty-desc="$tr('role.no_roles_desc')"
 selectable
 :selected="selectedIds"
 :sort-by="roleStore.filters.sort_by"
 :sort-order="roleStore.filters.sort_order"
 @sort="(key) => emit('sort', key)"
 @update:selected="(val) => emit('update:selectedIds', val)"
 row-key="id">
 
 <TableColumn field="name" :header="$tr('common.name')" width="250px" sortable>
 <template #default="{ row: role }">
 <div class="flex flex-col text-left font-sans py-1">
 <span
 class="text-sm md:text-base font-normal text-main-text leading-tight group-hover:text-accent transition-colors">
 {{ localize(role.name) }}
 </span>
 <span
 class="text-xs font-normal text-main-text/60 tracking-tight">
 #{{ role.slug }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="description" :header="$tr('common.description')" width="300px">
 <template #default="{ row: role }">
 <div class="flex flex-col max-w-xs font-sans py-1">
 <span
 class="text-sm font-normal text-main-text/60 leading-relaxed truncate whitespace-normal line-clamp-2">
 {{ localize(role.description) || "---" }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="type" :header="$tr('user.account_type')" align="center" width="140px">
 <template #default="{ row: role }">
 <div
 class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-normal tracking-widest border"
 :class="
 role.is_system_level
 ? 'bg-brand-blue/5 text-brand-blue border-brand-blue/20'
 : 'bg-card-hover/50 text-main-text/40 border-card-border/50'
 ">
 {{
 role.is_system_level
 ? $tr("role.system")
 : $tr("role.custom")
 }}
 </div>
 </template>
 </TableColumn>

 <TableColumn field="hierarchy" :header="$tr('role.hierarchy')" align="center" width="160px" sortable>
 <template #default="{ row: role }">
 <div
 class="inline-flex items-center px-3 py-1.5 rounded-xl bg-main-bg/5 border border-card-border/60">
 <div
 class="w-2 h-2 rounded-full mr-2"
 :class="[
 role.hierarchy_level <= 1
 ? 'bg-rose-500'
 : role.hierarchy_level <= 3
 ? 'bg-amber-500'
 : 'bg-emerald-500 font-normal',
 ]"></div>
 <span class="text-xs font-normal text-main-text/60">
 Lvl {{ role.hierarchy_level }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="permissions" :header="$tr('user.permissions')" align="center" width="160px">
 <template #default="{ row: role }">
 <div
 class="inline-flex items-center px-3 py-1.5 rounded-xl bg-main-bg/5 border border-card-border/60 transition-colors font-sans group-hover:border-accent/30">
 <Fingerprint class="w-4 h-4 mr-2 text-accent" />
 <span class="text-sm font-normal text-accent">
 {{ role.permissions?.length || 0 }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="is_active" :header="$tr('user.status')" align="center" width="120px">
 <template #default="{ row: role }">
 <StatusBadge :status="role.is_active ? 'active' : 'inactive'" />
 </template>
 </TableColumn>

 <TableColumn header="" align="right">
 <template #default="{ row: role, index }">
 <div class="flex justify-end p-1">
 <ActionDropdown
 :actions="getRoleActions(role)"
 :item="role"
 :index="index"
 :total="roles.length" />
 </div>
 </template>
 </TableColumn>

 <template #footer>
 <slot name="footer" />
 </template>
 </DataTable>
</template>
