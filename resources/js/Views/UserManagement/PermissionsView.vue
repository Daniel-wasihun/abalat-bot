<script setup lang="ts">
import { onMounted, computed, getCurrentInstance, ref, watch } from "vue";
import { usePermissionStore } from "@/stores/permissionStore";
import { useLanguageStore } from "@/stores/languageStore";
import { useToastStore } from "@/stores/toast";
import { storeToRefs } from "pinia";
import { usePermissions } from "@/composables/usePermissions";
import { useSecurity } from "@/composables/useSecurity";
import { Modules } from "@/constants/permissions";
import {
  Trash2,
  CheckCircle2,
  XCircle,
  Layers,
  Puzzle,
  Zap,
} from "lucide-vue-next";

// Components
import TableToolbar from "@/components/common/TableToolbar.vue";
import FormSelect from "@/components/common/FormSelect.vue";
import Button from "@/components/common/Button.vue";
import TableSelection from "@/components/common/TableSelection.vue";
import TablePagination from "@/components/common/TablePagination.vue";
import PermissionTable from "./components/PermissionTable.vue";
import PermissionModal from "./components/PermissionModal.vue";
import PermissionViewModal from "./components/PermissionViewModal.vue";
import BulkActionConfirmModal from "@/components/common/BulkActionConfirmModal.vue";
import { localize } from "@/utils/format";
import ConfirmDialog from "@/components/common/ConfirmDialog.vue";

// Composables
import { useTableState } from "@/composables/useTableState";
import { useBulkActions } from "@/composables/useBulkActions";

// Stores
const permissionStore = usePermissionStore();
const { permissions, loading, options, filters } = storeToRefs(permissionStore);
const languageStore = useLanguageStore();
const toastStore = useToastStore();

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

// Watch for language changes to refetch/use cache
watch(
  () => languageStore.currentLanguage,
  () => {
    permissionStore.fetchPermissions(
      permissionStore.pagination.currentPage,
      true,
    );
    permissionStore.fetchOptions();
  },
);

const { isSuperAdmin } = useSecurity();
const {
  canCreate: _canCreate,
  canEdit: _canEdit,
  canDelete: _canDelete,
} = usePermissions(Modules.PERMISSIONS);
const { canEdit: canEditRole, canDelete: canDeleteRole } = usePermissions(
  Modules.ROLES,
);

const canCreate = computed(() => isSuperAdmin.value || _canCreate.value);
const canEdit = computed(
  () => isSuperAdmin.value || _canEdit.value || canEditRole.value,
);
const canDelete = computed(
  () => isSuperAdmin.value || _canDelete.value || canDeleteRole.value,
);

const {
  showModal,
  showViewModal,
  showDeleteConfirm,
  showFilters,
  selectedItem: selectedPermission,
  deleting,
  deleteError,
  selectedIds,
  openCreate,
  openEdit,
  openView,
  openDelete,
  confirmDelete,
  closeDelete,
  handleSort,
} = useTableState({
  store: permissionStore,
  deleteAction: permissionStore.deletePermission,
  refreshAction: permissionStore.fetchPermissions,
});

const { showBulkModal, pendingBulkAction, openBulkConfirm, handleBulkAction } =
  useBulkActions({
    store: permissionStore,
    selectedIds,
    $tr,
  });

const hasActiveFilters = computed(
  () =>
    permissionStore.filters.module !== "" ||
    permissionStore.filters.action !== "",
);

const moduleOptions = computed(() => [
  {
    label: String($tr("filter.all_modules") || "All Modules"),
    value: "",
    icon: Layers,
  },
  ...Object.entries(permissionStore.options.modules || {}).map(
    ([value, label]) => ({
      label: label as string,
      value,
      icon: Puzzle,
    }),
  ),
]);

const actionOptions = computed(() => [
  {
    label: String($tr("filter.all_actions") || "All Actions"),
    value: "",
    icon: Layers,
  },
  ...Object.entries(permissionStore.options.actions || {}).map(
    ([value, label]) => ({
      label: label as string,
      value,
      icon: Zap,
    }),
  ),
]);

const translatedAction = computed(() => {
  if (!pendingBulkAction.value) return "";
  return $tr(`common.${pendingBulkAction.value}`);
});

onMounted(async () => {
  await permissionStore.fetchOptions();
  permissionStore.resetFilters();
});

const handleSearch = () => permissionStore.fetchPermissions(1, true);
const handleFilter = () => permissionStore.fetchPermissions(1, true);
const handleReset = () => permissionStore.resetFilters();

const refreshPermissions = async (page?: number) => {
  const currentPage = page || permissionStore.pagination.currentPage;
  await permissionStore.fetchPermissions(currentPage, true);
};

const handlePerPageChange = (val: number) => {
  permissionStore.pagination.perPage = val;
  refreshPermissions(1);
};
</script>

<template>
  <div class="flex flex-col flex-1 min-h-0 space-y-4 relative font-sans animate-in fade-in duration-500">
    <!-- Main Content Card -->
    <div
      class="bg-card-bg border border-card-border/60 rounded-2xl flex flex-col min-h-0 shrink overflow-hidden">
      <div class="flex flex-col min-h-0 shrink">
        <TableToolbar
          v-model="permissionStore.filters.search"
          :placeholder="$tr('user.search_placeholder')"
          :show-filters="showFilters"
          :has-active-filters="hasActiveFilters"
          :can-create="canCreate"
          :create-label="$tr('action.create')"
          @toggle-filters="showFilters = !showFilters"
          @reset="handleReset"
          @create="openCreate"
          @update:model-value="handleSearch"
        >
          <template #filters>
            <!-- Module Filter -->
            <div class="min-w-[180px] flex-1">
              <FormSelect
                v-model="permissionStore.filters.module"
                :options="moduleOptions"
                :label="$tr('common.module')"
                @change="handleFilter" />
            </div>

            <!-- Action Filter -->
            <div class="min-w-[180px] flex-1">
              <FormSelect
                v-model="permissionStore.filters.action"
                :options="actionOptions"
                :label="$tr('common.action')"
                @change="handleFilter" />
            </div>
          </template>
        </TableToolbar>

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

        <!-- Permission Table -->
        <div class="flex-1 min-h-0 flex flex-col">
          <PermissionTable
            v-model:selected-ids="selectedIds"
            :can-edit="canEdit"
            :can-delete="canDelete"
            @view="openView"
            @edit="openEdit"
            @delete="openDelete">
            <template #footer>
              <TablePagination
                :current-page="permissionStore.pagination.currentPage"
                :last-page="permissionStore.pagination.lastPage"
                :total="permissionStore.pagination.total"
                :per-page="permissionStore.pagination.perPage"
                :loading="permissionStore.loading"
                @page-change="permissionStore.fetchPermissions"
                @per-page-change="handlePerPageChange" />
            </template>
          </PermissionTable>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <PermissionModal
      v-if="showModal"
      :show="showModal"
      :permission="selectedPermission"
      @close="showModal = false" />

    <PermissionViewModal
      v-if="showViewModal"
      :show="showViewModal"
      :permission="selectedPermission"
      @close="showViewModal = false"
      @edit="openEdit(selectedPermission)"
      @delete="openDelete(selectedPermission)" />

    <!-- Unified Delete Confirmation Modal -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      :title="$tr('permission.deletion_title')"
      :message="
        selectedPermission
          ? $tr('permission.delete_warning_desc', {
            name: `<span class='text-rose-500 font-medium'>${localize(selectedPermission.name)}</span>`,
          })
          : ''
      "
      :confirm-text="$tr('action.delete')"
      variant="danger"
      :loading="deleting"
      @close="closeDelete"
      @confirm="confirmDelete(undefined, undefined, true)" />

    <!-- Bulk Action Modal -->
    <BulkActionConfirmModal
      :show="showBulkModal"
      :action="pendingBulkAction"
      :items-count="selectedIds.length"
      :item-name="$tr('nav.permissions')"
      :loading="deleting"
      @close="showBulkModal = false"
      @confirm="
        handleBulkAction(
          permissionStore.bulkDeletePermissions,
          permissionStore.bulkToggleStatus,
        )
      " />
  </div>
</template>
