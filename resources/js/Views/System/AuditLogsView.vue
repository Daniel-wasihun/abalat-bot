<template>
  <div class="h-full flex flex-col p-4 md:p-6 lg:p-8 max-w-7xl mx-auto w-full">
    <!-- Header -->
    <div class="flex items-center justify-between shrink-0 pb-6 border-b border-card-border/60">
      <div>
        <h1 class="text-2xl font-bold text-main-text">Audit Logs</h1>
        <p class="text-sm text-main-text/60 mt-1">Review system changes and rollback specific actions.</p>
      </div>
      <Button variant="secondary" :icon="RefreshCcw" @click="fetchLogs(pagination.current_page)" :loading="loading">
        Refresh
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="mt-6 flex flex-col sm:flex-row gap-4 mb-4">
      <FormSelect
        v-model="filters.event"
        :options="eventOptions"
        placeholder="Filter by Event"
        class="w-48"
      />
      <FormSelect
        v-model="filters.model_type"
        :options="modelOptions"
        placeholder="Filter by Type"
        class="w-56"
      />
    </div>

    <!-- Table container -->
    <div class="flex-1 min-h-0 bg-card-bg border border-card-border/60 rounded-2xl overflow-hidden shadow-sm flex flex-col">
      <DataTable
        :items="logs"
        :loading="loading"
        empty-title="No audit logs found"
        empty-desc="There are no recorded actions matching your filters."
        class="border-0 rounded-none flex-1 custom-scrollbar"
      >
        <template #columns>
          <TableColumn label="Time" />
          <TableColumn label="Action" />
          <TableColumn label="User (Causer)" />
          <TableColumn label="Resource" />
          <TableColumn label="Changes" />
          <TableColumn label="" align="right" />
        </template>

        <template #row="{ item }">
          <td class="px-5 py-3.5 whitespace-nowrap">
            <div class="text-sm font-medium text-main-text">{{ formatDate(item.created_at) }}</div>
            <div class="text-xs text-main-text/50">{{ formatTime(item.created_at) }}</div>
          </td>
          <td class="px-5 py-3.5 whitespace-nowrap">
            <span :class="eventClass(item.event)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border">
              {{ item.event }}
            </span>
          </td>
          <td class="px-5 py-3.5 whitespace-nowrap text-sm text-main-text/80">
            {{ item.causer_name }}
          </td>
          <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-main-text">
            {{ item.model_type }} #{{ item.model_id }}
          </td>
          <td class="px-5 py-3.5 w-full max-w-[300px]">
            <div v-if="item.event === 'Updated'" class="flex items-center gap-2 overflow-hidden">
              <div class="flex-1 truncate text-xs font-mono text-rose-500 bg-rose-500/5 px-2 py-1 rounded">
                {{ formatJSON(item.old_values) }}
              </div>
              <ArrowRight class="w-3 h-3 text-main-text/40 shrink-0" />
              <div class="flex-1 truncate text-xs font-mono text-emerald-500 bg-emerald-500/5 px-2 py-1 rounded">
                {{ formatJSON(item.new_values) }}
              </div>
            </div>
            <div v-else class="truncate text-xs font-mono text-main-text/60 bg-main-bg px-2 py-1 rounded border border-card-border/60">
              {{ formatJSON(item.new_values) || formatJSON(item.old_values) || '{}' }}
            </div>
          </td>
          <td class="px-5 py-3.5 whitespace-nowrap text-right">
            <Button
              v-if="item.event !== 'Created'"
              variant="danger"
              class="h-8 px-3 text-xs"
              :icon="RotateCcw"
              @click="confirmRollback(item)"
              :loading="rollingBack === item.id"
            >
              Rollback
            </Button>
          </td>
        </template>
      </DataTable>

      <!-- Pagination -->
      <TablePagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :per-page="pagination.per_page"
        @page-change="changePage"
      />
    </div>

    <!-- Rollback Confirmation Modal -->
    <ConfirmDialog
      :show="showConfirm"
      title="Rollback Action"
      message="Are you sure you want to rollback this record? This will instantly revert the resource to the state it was in at the time of this audit."
      confirm-text="Yes, Rollback"
      variant="danger"
      :loading="rollingBack !== null"
      @close="showConfirm = false"
      @confirm="executeRollback"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue';
import { RefreshCcw, ArrowRight, RotateCcw } from 'lucide-vue-next';
import { useToastStore } from '@/stores/toast';
import apiClient from '@/api/apiClient';

import Button from '@/components/common/Button.vue';
import DataTable from '@/components/common/DataTable.vue';
import TableColumn from '@/components/common/TableColumn.vue';
import TablePagination from '@/components/common/TablePagination.vue';
import FormSelect from '@/components/common/FormSelect.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';

const toast = useToastStore();
const loading = ref(true);
const logs = ref<any[]>([]);
const rollingBack = ref<number | null>(null);

const showConfirm = ref(false);
const itemToRollback = ref<any>(null);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});

const filters = reactive({
  event: '',
  model_type: '',
});

const eventOptions = [
  { value: '', label: 'All Events' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
];

const modelOptions = [
  { value: '', label: 'All Resources' },
  { value: 'GeneralAttendanceRecord', label: 'Attendance' },
  { value: 'AttendanceRecord', label: 'Course Attendance' },
  { value: 'Payment', label: 'Payment' },
  { value: 'User', label: 'User' },
  { value: 'Role', label: 'Role' },
];

const fetchLogs = async (page = 1) => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.append('page', page.toString());
    if (filters.event) params.append('event', filters.event);
    if (filters.model_type) params.append('auditable_type', `App\\Models\\${filters.model_type}`);

    const res = await apiClient.get(`/audit-logs?${params.toString()}`);
    logs.value = res.data.data || [];
    pagination.current_page = res.data.current_page;
    pagination.last_page = res.data.last_page;
    pagination.total = res.data.total;
    pagination.per_page = res.data.per_page;
  } catch {
    toast.error('Failed to load audit logs.');
  } finally {
    loading.value = false;
  }
};

const changePage = (page: number) => {
  fetchLogs(page);
};

watch(filters, () => {
  fetchLogs(1);
});

onMounted(() => {
  fetchLogs();
});

const confirmRollback = (item: any) => {
  itemToRollback.value = item;
  showConfirm.value = true;
};

const executeRollback = async () => {
  if (!itemToRollback.value) return;
  const auditId = itemToRollback.value.id;
  rollingBack.value = auditId;
  try {
    const res = await apiClient.post(`/audit-logs/${auditId}/rollback`);
    if (res.data.status === 'success') {
      toast.success('Record successfully rolled back.');
      fetchLogs(pagination.current_page);
    } else {
      toast.error(res.data.message || 'Failed to rollback.');
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Rollback failed.');
  } finally {
    rollingBack.value = null;
    showConfirm.value = false;
    itemToRollback.value = null;
  }
};

const eventClass = (event: string) => {
  switch (event.toLowerCase()) {
    case 'created': return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    case 'updated': return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
    case 'deleted': return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
    default:        return 'bg-main-text/5 text-main-text/50 border-main-text/10';
  }
};

const formatDate = (dateString: string) =>
  new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

const formatTime = (dateString: string) =>
  new Date(dateString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

const formatJSON = (val: any) => {
  if (!val) return '';
  try {
    const keys = Object.keys(val);
    if (keys.length === 0) return '';
    return `{ ${keys.slice(0, 3).join(', ')}${keys.length > 3 ? '...' : ''} }`;
  } catch {
    return String(val);
  }
};
</script>
