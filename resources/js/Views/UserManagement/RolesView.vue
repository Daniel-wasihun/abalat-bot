<script setup lang="ts">
import { Modules } from "@/constants/permissions";
import {
 ShieldCheck,
 AlertCircle,
 Trash2,
 CheckCircle2,
 XCircle,
 Power,
 Plus,
} from "lucide-vue-next";
import { onMounted, computed, getCurrentInstance, ref, watch } from "vue";
import { useRoleStore } from "@/stores/roleStore";
import { usePermissionStore } from "@/stores/permissionStore";
import { useLanguageStore } from "@/stores/languageStore";
import { useToastStore } from "@/stores/toast";
import { usePermissions } from "@/composables/usePermissions";
import { useSecurity } from "@/composables/useSecurity";
import { localize } from "@/utils/format";

import { defineAsyncComponent } from "vue";

// Components
const TableToolbar = defineAsyncComponent(() => import("@/components/common/TableToolbar.vue"));
const Button = defineAsyncComponent(() => import("@/components/common/Button.vue"));
const TablePagination = defineAsyncComponent(() => import("@/components/common/TablePagination.vue"));
const TableSelection = defineAsyncComponent(() => import("@/components/common/TableSelection.vue"));
const RoleTable = defineAsyncComponent(() => import("./components/RoleTable.vue"));
const RoleModal = defineAsyncComponent(() => import("./components/RoleModal.vue"));
const RoleViewModal = defineAsyncComponent(() => import("./components/RoleViewModal.vue"));
const BulkActionConfirmModal = defineAsyncComponent(() => import("@/components/common/BulkActionConfirmModal.vue"));
const ConfirmDialog = defineAsyncComponent(() => import("@/components/common/ConfirmDialog.vue"));


// Composables
import { useTableState } from "@/composables/useTableState";
import { useBulkActions } from "@/composables/useBulkActions";

const roleStore = useRoleStore();
const permissionStore = usePermissionStore();
const languageStore = useLanguageStore();
const toastStore = useToastStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

// Watch for language changes to refetch/use cache
watch(
 () => languageStore.currentLanguage,
 () => {
 roleStore.fetchRoles(roleStore.pagination.currentPage, true);
 },
);

const { isSuperAdmin } = useSecurity();
const {
 canCreate: _canCreate,
 canEdit: _canEdit,
 canDelete: _canDelete,
} = usePermissions(Modules.ROLES);
const canCreate = computed(() => isSuperAdmin.value || _canCreate.value);
const canEdit = computed(() => isSuperAdmin.value || _canEdit.value);
const canDelete = computed(() => isSuperAdmin.value || _canDelete.value);

const forceDelete = ref(false);

const {
 showModal,
 showViewModal,
 showDeleteConfirm,
 showFilters,
 selectedItem: selectedRole,
 deleting,
 deleteError,
 selectedIds,
 openCreate,
 openEdit,
 openView,
 openDelete,
 closeDelete,
 confirmDelete,
 handleSort,
} = useTableState({
 store: roleStore,
 deleteAction: (slugOrId: string | number) =>
 roleStore.deleteRole(slugOrId as string, forceDelete.value),
 refreshAction: roleStore.fetchRoles,
});

const { showBulkModal, pendingBulkAction, openBulkConfirm, handleBulkAction } =
 useBulkActions({
 store: roleStore,
 selectedIds,
 $tr,
 onSuccess: roleStore.fetchRoles,
 skipRefresh: true,
 });

const translatedAction = computed(() => {
 if (!pendingBulkAction.value) return "";
 return $tr(`common.${pendingBulkAction.value}`);
});

const hasActiveFilters = computed(() => roleStore.filters.search !== "");

onMounted(() => {
 roleStore.resetFilters();
});

const handleSearch = () => roleStore.fetchRoles(1, true);
const handleReset = () => roleStore.resetFilters();

const refreshRoles = async (page?: number) => {
 const currentPage = page || roleStore.pagination.currentPage;
 await roleStore.fetchRoles(currentPage, true);
};

const handlePerPageChange = (val: number) => {
 roleStore.pagination.perPage = val;
 refreshRoles(1);
};
</script>

<template>
 <div class="flex flex-col flex-1 min-h-0 space-y-4 relative font-sans animate-in fade-in duration-500">
 <!-- Main Content Card -->
 <div
 class="bg-card-bg border border-card-border/60 rounded-2xl flex flex-col min-h-0 shrink overflow-hidden">
 <div class="flex flex-col min-h-0 shrink">
 <TableToolbar
 v-model="roleStore.filters.search"
 :placeholder="$tr('user.search_placeholder')"
 :show-filters="showFilters"
 :has-active-filters="hasActiveFilters"
 :can-create="canCreate"
 :create-label="$tr('action.create')"
 @toggle-filters="showFilters = !showFilters"
 @reset="handleReset"
 @create="openCreate"
 @update:model-value="handleSearch"
 />

 <!-- Selection Management Bar -->
 <TableSelection
 v-if="selectedIds.length > 0"
 :count="selectedIds.length"
 :label="$tr('common.selected')"
 @clear="selectedIds = []"
 >
 <div class="flex items-center gap-2">
 <Button
 v-if="canEdit"
 variant="soft-success"
 size="sm"
 :icon="CheckCircle2"
 @click="openBulkConfirm('activate')"
 >
 {{ $tr("common.activate") }}
 </Button>

 <Button
 v-if="canEdit"
 variant="soft-warning"
 size="sm"
 :icon="XCircle"
 @click="openBulkConfirm('deactivate')"
 >
 {{ $tr("common.deactivate") }}
 </Button>

 <Button
 v-if="canDelete"
 variant="soft-danger"
 size="sm"
 :icon="Trash2"
 @click="openBulkConfirm('delete')"
 >
 {{ $tr("common.delete") }}
 </Button>
 </div>
 </TableSelection>

 <!-- Role Table -->
 <div class="flex-1 min-h-0 flex flex-col">
 <RoleTable
 v-model:selected-ids="selectedIds"
 :can-edit="canEdit"
 :can-delete="canDelete"
 @view="openView"
 @edit="openEdit"
 @delete="openDelete"
 @sort="handleSort">
 <template #footer>
 <TablePagination
 :current-page="roleStore.pagination.currentPage"
 :last-page="roleStore.pagination.lastPage"
 :total="roleStore.pagination.total"
 :per-page="roleStore.pagination.perPage"
 :loading="roleStore.loading"
 @page-change="roleStore.fetchRoles"
 @per-page-change="handlePerPageChange" />
 </template>
 </RoleTable>
 </div>
 </div>
 </div>

 <!-- Modals -->
 <RoleModal
 v-if="showModal"
 :show="showModal"
 :role="selectedRole"
 @close="showModal = false" />

 <RoleViewModal
 v-if="showViewModal"
 :show="showViewModal"
 :role="selectedRole"
 @close="showViewModal = false"
 @edit="openEdit(selectedRole)"
 @delete="openDelete(selectedRole)" />

 <!-- Delete Confirmation Modal -->
 <ConfirmDialog
  :show="showDeleteConfirm"
  :title="$tr('role.role_deletion')"
  :message="
  selectedRole
  ? $tr('role.delete_warning_desc', {
  name: `<span class='text-rose-500 font-medium'>${localize(selectedRole.name)}</span>`,
  })
  : ''
  "
  variant="danger"
  :loading="roleStore.loading"
  @close="closeDelete"
  @confirm="confirmDelete(undefined, undefined, true)" />

 <!-- Bulk Action Modal -->
 <BulkActionConfirmModal
 :show="showBulkModal"
 :action="pendingBulkAction"
 :items-count="selectedIds.length"
 :item-name="$tr('role.roles')"
 :loading="deleting"
 @close="showBulkModal = false"
 @confirm="
 handleBulkAction(
 roleStore.bulkDeleteRoles,
 roleStore.bulkToggleStatus,
 )
 " />
 </div>
</template>
