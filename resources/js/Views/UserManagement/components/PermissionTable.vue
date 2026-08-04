<script setup lang="ts">
import StatusBadge from "@/components/common/StatusBadge.vue";
import { ShieldCheck, Eye, Pencil, Trash2 } from "lucide-vue-next";
import { usePermissionStore } from "@/stores/permissionStore";
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

const emit = defineEmits(["view", "edit", "delete", "update:selectedIds"]);

const permissionStore = usePermissionStore();
const { permissions, loading, options } = storeToRefs(permissionStore);
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const { isSuperAdmin } = useSecurity();

const getPermissionActions = (p: any) => {
 const actions = [];
 const isSystem = !!p.is_system_level;
 const showEdit = props.canEdit || isSuperAdmin.value;
 const showDelete = props.canDelete || isSuperAdmin.value;

 actions.push({
 label: $tr("action.view"),
 icon: Eye,
 colorClass: "text-brand-blue",
 onClick: (item: any) => emit("view", item),
 });

 if (showEdit) {
 actions.push({
 label: $tr("action.edit"),
 icon: Pencil,
 colorClass: "text-amber-500",
 disabled: false,
 onClick: (item: any) => emit("edit", item),
 });
 }

 if (showDelete) {
 actions.push({
 label: $tr("action.delete"),
 icon: Trash2,
 colorClass: "text-rose-600",
 disabled: false,
 onClick: (item: any) => emit("delete", item),
 });
 }

 return actions;
};
</script>

<template>
 <DataTable
 :items="permissions"
 :loading="loading"
 :empty-icon="ShieldCheck"
 :empty-title="$tr('nav.permissions')"
 :empty-desc="$tr('user.no_matches')"
 selectable
 :selected="selectedIds"
 @update:selected="(val) => emit('update:selectedIds', val)"
 row-key="id">
 
 <TableColumn field="name" :header="$tr('common.name')" width="250px">
 <template #default="{ row: p }">
 <div class="flex flex-col py-1">
 <span
 class="text-sm md:text-base font-normal text-main-text leading-tight group-hover:text-accent transition-colors">
 {{ localize(p.name) }}
 </span>
 <span
 class="text-xs font-normal text-main-text/40 tracking-tight">
 #{{ p.slug }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="description" :header="$tr('common.description')" width="300px">
 <template #default="{ row: p }">
 <div class="flex flex-col max-w-xs font-sans py-1">
 <span
 class="text-sm font-normal text-main-text/60 leading-relaxed truncate whitespace-normal line-clamp-2">
 {{ localize(p.description) || "---" }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="type" :header="$tr('user.account_type')" align="center" width="140px">
 <template #default="{ row: p }">
 <div
 class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-normal tracking-widest border"
 :class="
 p.is_system_level
 ? 'bg-brand-blue/5 text-brand-blue border-brand-blue/20'
 : 'bg-card-hover/50 text-main-text/40 border-card-border/50'
 ">
 {{
 p.is_system_level ? $tr("role.system") : $tr("role.custom")
 }}
 </div>
 </template>
 </TableColumn>

 <TableColumn field="module" :header="$tr('common.module')" width="180px">
 <template #default="{ row: p }">
 <div
 class="inline-flex items-center px-4 py-1.5 rounded-xl bg-main-bg/5 border border-card-border/60 hover:bg-brand-blue/5 transition-colors group/mod">
 <span
 class="text-sm font-normal text-main-text/60 tracking-widest capitalize text-[10px]">
 {{ $tr("module." + (p.module || "other")) }}
 </span>
 </div>
 </template>
 </TableColumn>

 <TableColumn field="action" :header="$tr('common.action')" width="160px">
 <template #default="{ row: p }">
 <div
 class="inline-flex items-center px-3 py-1 rounded-lg border font-normal text-xs tracking-tight transition-all"
 :class="[
 p.action === 'view'
 ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 shadow-brand-green/5'
 : p.action === 'create'
 ? 'bg-brand-blue/10 text-brand-blue border-brand-blue/20'
 : p.action === 'edit'
 ? 'bg-amber-500/10 text-amber-600 border-amber-500/20 shadow-amber-500/5'
 : 'bg-rose-500/10 text-rose-600 border-rose-500/20',
 ]">
 <div
 class="w-1.5 h-1.5 rounded-full mr-2"
 :class="[
 p.action === 'view'
 ? 'bg-emerald-500'
 : p.action === 'create'
 ? 'bg-brand-blue'
 : p.action === 'edit'
 ? 'bg-amber-500'
 : 'bg-rose-500',
 ]"></div>
 {{ $tr("action." + (p.action || "view")) }}
 </div>
 </template>
 </TableColumn>

 <TableColumn field="is_active" :header="$tr('user.status')" align="center" width="120px">
 <template #default="{ row: p }">
 <StatusBadge :status="p.is_active ? 'active' : 'inactive'" />
 </template>
 </TableColumn>

 <TableColumn header="" align="right">
 <template #default="{ row: p, index }">
 <div class="flex justify-end p-1">
 <ActionDropdown
 :actions="getPermissionActions(p)"
 :item="p"
 :index="index"
 :total="permissions.length" />
 </div>
 </template>
 </TableColumn>

 <template #footer>
 <slot name="footer" />
 </template>
 </DataTable>
</template>
