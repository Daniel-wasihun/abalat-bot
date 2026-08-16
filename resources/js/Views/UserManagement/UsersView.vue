<script setup lang="ts">
import { Modules } from "@/constants/permissions";
import {
  CheckCircle2,
  XCircle,
  Trash2,
  Contact,
} from "lucide-vue-next";
import { onMounted, computed, getCurrentInstance, ref, watch } from "vue";
import { useUserStore } from "@/stores/userStore";
import { useLanguageStore } from "@/stores/languageStore";
import { usePermissions } from "@/composables/usePermissions";
import { useSecurity } from "@/composables/useSecurity";
import { localize } from "@/utils/format";
import { defineAsyncComponent } from "vue";

// Components
const TableToolbar = defineAsyncComponent(() => import("@/components/common/TableToolbar.vue"));
const Button = defineAsyncComponent(() => import("@/components/common/Button.vue"));
const TablePagination = defineAsyncComponent(() => import("@/components/common/TablePagination.vue"));
const TableSelection = defineAsyncComponent(() => import("@/components/common/TableSelection.vue"));
const UserTable = defineAsyncComponent(() => import("./components/UserTable.vue"));
const UserModal = defineAsyncComponent(() => import("./components/UserModal.vue"));
const UserViewModal = defineAsyncComponent(() => import("./components/UserViewModal.vue"));
const IdCardGeneratorModal = defineAsyncComponent(() => import("./components/IdCardGeneratorModal.vue"));
const BulkActionConfirmModal = defineAsyncComponent(() => import("@/components/common/BulkActionConfirmModal.vue"));
const ConfirmDialog = defineAsyncComponent(() => import("@/components/common/ConfirmDialog.vue"));

// Composables
import { useTableState } from "@/composables/useTableState";

const userStore = useUserStore();
const languageStore = useLanguageStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

// Watch for language changes to refetch/use cache
watch(
  () => languageStore.currentLanguage,
  () => {
    userStore.fetchUsers(userStore.pagination.currentPage, true);
  },
);

const { isSuperAdmin } = useSecurity();
const {
  canCreate: _canCreate,
  canEdit: _canEdit,
  canDelete: _canDelete,
} = usePermissions(Modules.USERS);
const canCreate = computed(() => isSuperAdmin.value || _canCreate.value);
const canEdit = computed(() => isSuperAdmin.value || _canEdit.value);
const canDelete = computed(() => isSuperAdmin.value || _canDelete.value);

const {
  showModal,
  showViewModal,
  showDeleteConfirm,
  showFilters,
  selectedItem: selectedUser,
  deleting,
  selectedIds,
  openCreate,
  openEdit,
  openView,
  openDelete,
  closeDelete,
  confirmDelete,
  handleSort,
} = useTableState({
  store: userStore,
  deleteAction: (id: string | number) => userStore.deleteUser(id as number),
  refreshAction: userStore.fetchUsers,
});

const showIdCardModal = ref(false);
const idCardUsers = ref<any[]>([]);

function openGenerateId(user: any) {
  idCardUsers.value = [user];
  showIdCardModal.value = true;
}

function handleBulkGenerate() {
  idCardUsers.value = userStore.users.filter(u => selectedIds.value.includes(u.id));
  showIdCardModal.value = true;
}

// Simplified bulk action handlers for UserStore
const showBulkModal = ref(false);
const pendingBulkAction = ref("");

const openBulkConfirm = (action: string) => {
  pendingBulkAction.value = action;
  showBulkModal.value = true;
};

const handleBulkAction = async () => {
  if (!selectedIds.value.length || !pendingBulkAction.value) return;
  deleting.value = true;
  try {
    await userStore.bulkAction(pendingBulkAction.value as any, selectedIds.value);
    selectedIds.value = [];
    showBulkModal.value = false;
    userStore.fetchUsers(1, true);
  } finally {
    deleting.value = false;
  }
};

const hasActiveFilters = computed(() => userStore.filters.search !== "");

onMounted(() => {
  userStore.resetFilters();
  userStore.fetchUsers(1, true);
  userStore.fetchMetadata(); // pre-load roles for the create-user modal
});

const handleSearch = () => userStore.fetchUsers(1, true);
const handleReset = () => userStore.resetFilters();

const refreshUsers = async (page?: number) => {
  const currentPage = page || userStore.pagination.currentPage;
  await userStore.fetchUsers(currentPage, true);
};

const handlePerPageChange = (val: number) => {
  userStore.pagination.perPage = val;
  refreshUsers(1);
};
</script>

<template>
  <div class="flex flex-col flex-1 min-h-0 space-y-4 relative font-sans animate-in fade-in duration-500">
    <!-- Main Content Card -->
    <div class="bg-card-bg border border-card-border/60 rounded-2xl flex flex-col min-h-0 shrink overflow-hidden">
      <div class="flex flex-col min-h-0 shrink">
        <TableToolbar
          v-model="userStore.filters.search"
          :placeholder="$tr('user.search_placeholder', 'Search users...')"
          :show-filters="showFilters"
          :has-active-filters="hasActiveFilters"
          :can-create="canCreate"
          :create-label="$tr('action.create', 'Create')"
          @toggle-filters="showFilters = !showFilters"
          @reset="handleReset"
          @create="openCreate"
          @update:model-value="handleSearch"
        />

        <!-- Selection Management Bar -->
        <TableSelection
          v-if="selectedIds.length > 0"
          :count="selectedIds.length"
          :label="$tr('common.selected', 'Selected')"
          @clear="selectedIds = []"
        >
          <div class="flex items-center gap-2">
            <Button
              v-if="canEdit"
              variant="soft-primary"
              size="sm"
              :icon="Contact"
              @click="handleBulkGenerate"
            >
              {{ $tr("user.generate_ids", "Generate IDs") }}
            </Button>
            <Button
              v-if="canEdit"
              variant="soft-success"
              size="sm"
              :icon="CheckCircle2"
              @click="openBulkConfirm('activate')"
            >
              {{ $tr("common.activate", "Activate") }}
            </Button>
            <Button
              v-if="canEdit"
              variant="soft-warning"
              size="sm"
              :icon="XCircle"
              @click="openBulkConfirm('deactivate')"
            >
              {{ $tr("common.deactivate", "Deactivate") }}
            </Button>
            <Button
              v-if="canDelete"
              variant="soft-danger"
              size="sm"
              :icon="Trash2"
              @click="openBulkConfirm('delete')"
            >
              {{ $tr("common.delete", "Delete") }}
            </Button>
          </div>
        </TableSelection>

        <!-- User Table -->
        <div class="flex-1 min-h-0 flex flex-col">
          <UserTable
            v-model:selected-ids="selectedIds"
            :can-edit="canEdit"
            :can-delete="canDelete"
            @view="openView"
            @edit="openEdit"
            @generate-id="openGenerateId"
            @delete="openDelete"
            @sort="handleSort"
          >
            <template #footer>
              <TablePagination
                :current-page="userStore.pagination.currentPage"
                :last-page="userStore.pagination.lastPage"
                :total="userStore.pagination.total"
                :per-page="userStore.pagination.perPage"
                :loading="userStore.loading"
                @page-change="userStore.fetchUsers"
                @per-page-change="handlePerPageChange"
              />
            </template>
          </UserTable>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <UserModal
      v-if="showModal"
      :show="showModal"
      :user="selectedUser"
      @close="showModal = false"
    />

    <UserViewModal
      v-if="showViewModal"
      :show="showViewModal"
      :user="selectedUser"
      @close="showViewModal = false"
      @edit="openEdit(selectedUser)"
      @delete="openDelete(selectedUser)"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      :title="$tr('user.user_deletion', 'Delete User')"
      :message="
        selectedUser
          ? $tr('user.delete_warning_desc', {
              name: `<span class='text-rose-500 font-medium'>${localize(selectedUser.name)}</span>`,
            })
          : ''
      "
      variant="danger"
      :loading="userStore.loading"
      @close="closeDelete"
      @confirm="confirmDelete"
    />

    <!-- Bulk Action Modal -->
    <BulkActionConfirmModal
      :show="showBulkModal"
      :action="pendingBulkAction"
      :items-count="selectedIds.length"
      :item-name="$tr('user.users', 'Users')"
      :loading="deleting"
      @close="showBulkModal = false"
      @confirm="handleBulkAction"
    />

    <IdCardGeneratorModal
      :show="showIdCardModal"
      :users="idCardUsers"
      @close="showIdCardModal = false"
    />
  </div>
</template>
