<template>
  <div class="h-full flex flex-col p-4 md:p-6 overflow-hidden bg-main-bg w-full">
    <!-- Header -->
    <div class="flex items-center justify-between shrink-0 pb-6 border-b border-card-border/60">
      <div>
        <h1 class="text-2xl font-bold text-main-text">{{ $tr('audit.title', 'Audit Logs') }}</h1>
        <p class="text-sm text-main-text/60 mt-1">{{ $tr('audit.description', 'Review system changes and rollback specific actions.') }}</p>
      </div>
      <Button variant="secondary" :icon="RefreshCcw" @click="fetchLogs(pagination.current_page)" :loading="loading">
        {{ $tr('common.refresh', 'Refresh') }}
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="mt-4 flex flex-col sm:flex-row gap-3 shrink-0">
      <FormSelect
        v-model="filters.event"
        :options="eventOptions"
        :placeholder="$tr('audit.filter_event', 'Filter by Event')"
        class="w-48"
      />
      <FormSelect
        v-model="filters.model_type"
        :options="modelOptions"
        :placeholder="$tr('audit.filter_type', 'Filter by Type')"
        class="w-56"
      />
    </div>

    <!-- Table container -->
    <div class="bg-card-bg border border-card-border/60 rounded-2xl overflow-hidden shadow-sm flex flex-col flex-1 min-h-0 mt-4">

      <!-- Table when loaded -->
      <DataTable
        :items="logs"
        :loading="loading"
        :columns="[
          { key: 'time', label: $tr('common.time', 'Time') },
          { key: 'action', label: $tr('common.action', 'Action') },
          { key: 'user', label: $tr('audit.causer', 'User') },
          { key: 'resource', label: $tr('audit.resource', 'Resource') },
          { key: 'changes', label: $tr('audit.changes', 'Changes') },
          { key: 'actions', label: $tr('common.actions', 'Actions'), align: 'right' }
        ]"
        :empty-title="$tr('audit.no_logs_title', 'No audit logs found')"
        :empty-desc="$tr('audit.no_logs_desc', 'There are no recorded actions matching your filters.')"
        class="border-0 rounded-none flex-1 custom-scrollbar"
      >

        <template #cell-time="{ item }">
          <div class="text-sm font-medium text-main-text">{{ formatDate(item.created_at) }}</div>
          <div class="text-xs text-main-text/50 mt-0.5">{{ formatTime(item.created_at) }}</div>
        </template>

        <template #cell-action="{ item }">
          <div class="flex flex-col items-start gap-1">
            <span :class="eventConfig(item.event).badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border">
              <component :is="eventConfig(item.event).icon" class="w-3 h-3" />
              {{ eventLabel(item.event) }}
            </span>
            <!-- Rolled-back indicator -->
            <span v-if="item.is_rolled_back" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-main-text/5 text-main-text/40 border border-main-text/10">
              <component :is="RotateCcw" class="w-2.5 h-2.5" />
              {{ $tr('audit.rolled_back', 'Rolled Back') }}
            </span>
            <!-- Rollback indicator badge -->
            <span v-if="item.is_rollback" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-500/10 text-purple-500 border border-purple-500/20">
              <component :is="RotateCcw" class="w-2.5 h-2.5" />
              {{ $tr('audit.is_rollback', 'Rollback Action') }}
            </span>
          </div>
        </template>

        <template #cell-user="{ item }">
          <div class="flex flex-col min-w-0">
            <span class="text-sm text-main-text/80 truncate font-medium" :title="item.causer_name">
              <router-link v-if="item.causer_id" :to="`/dashboard/system/user-management/users?search=${item.causer_registration_id || item.causer_name}`" class="hover:text-brand-blue hover:underline">
                {{ item.causer_name || '—' }}
              </router-link>
              <span v-else>{{ item.causer_name || '—' }}</span>
            </span>
            <span v-if="item.causer_registration_id" class="text-[11px] text-main-text/50 truncate">
              {{ item.causer_registration_id }}
            </span>
          </div>
        </template>

        <template #cell-resource="{ item }">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="eventConfig(item.event).dot"></div>
            <div class="min-w-0">
              <div class="text-sm font-medium text-main-text truncate">{{ friendlyModel(item.model_type) }}</div>
            </div>
          </div>
        </template>

        <template #cell-changes="{ item }">
          <div class="min-w-0 space-y-1 max-h-32 overflow-y-auto custom-scrollbar pr-1 w-full max-w-[350px]">
            <template v-if="item.event?.toLowerCase() === 'updated'">
              <div v-for="(pair, i) in diffPairs(item.old_values, item.new_values).slice(0, 3)" :key="i"
                class="flex items-center gap-1.5 text-xs font-mono overflow-hidden">
                <span class="text-main-text/40 shrink-0 truncate max-w-[80px]" :title="pair.key">{{ pair.key }}</span>
                <span class="shrink-0 text-main-text/20">→</span>
                <span class="truncate text-rose-500 bg-rose-500/5 px-1.5 py-0.5 rounded border border-rose-500/10 flex-1 max-w-[140px]" :title="String(pair.old)">{{ formatDiffValue(pair.old) }}</span>
                <span class="shrink-0 text-main-text/20">→</span>
                <span class="truncate text-emerald-600 bg-emerald-500/5 px-1.5 py-0.5 rounded border border-emerald-500/10 flex-1 max-w-[140px]" :title="String(pair.new)">{{ formatDiffValue(pair.new) }}</span>
              </div>
              <div v-if="diffPairs(item.old_values, item.new_values).length > 3" class="text-xs text-main-text/30 italic cursor-pointer hover:text-main-text/60" @click="viewDetails(item)">
                + {{ diffPairs(item.old_values, item.new_values).length - 3 }} more...
              </div>
              <div v-if="diffPairs(item.old_values, item.new_values).length === 0" class="text-xs text-main-text/30 italic">—</div>
            </template>
            <template v-else-if="item.event?.toLowerCase() === 'deleted'">
              <div v-for="(pair, i) in diffPairs(item.old_values, {}).slice(0, 3)" :key="i" class="flex items-center gap-1.5 text-xs font-mono overflow-hidden">
                <span class="text-main-text/40 shrink-0 truncate max-w-[80px]" :title="pair.key">{{ pair.key }}</span>
                <span class="shrink-0 text-main-text/20">→</span>
                <span class="truncate text-rose-500 bg-rose-500/5 px-1.5 py-0.5 rounded border border-rose-500/10 flex-1 max-w-[200px]" :title="String(pair.old)">{{ formatDiffValue(pair.old) }}</span>
              </div>
              <div v-if="diffPairs(item.old_values, {}).length > 3" class="text-xs text-main-text/30 italic cursor-pointer hover:text-main-text/60" @click="viewDetails(item)">
                + {{ diffPairs(item.old_values, {}).length - 3 }} more...
              </div>
            </template>
            <template v-else>
              <div v-for="(pair, i) in diffPairs({}, item.new_values).slice(0, 3)" :key="i" class="flex items-center gap-1.5 text-xs font-mono overflow-hidden">
                <span class="text-main-text/40 shrink-0 truncate max-w-[80px]" :title="pair.key">{{ pair.key }}</span>
                <span class="shrink-0 text-main-text/20">→</span>
                <span class="truncate text-emerald-600 bg-emerald-500/5 px-1.5 py-0.5 rounded border border-emerald-500/10 flex-1 max-w-[200px]" :title="String(pair.new)">{{ formatDiffValue(pair.new) }}</span>
              </div>
              <div v-if="diffPairs({}, item.new_values).length > 3" class="text-xs text-main-text/30 italic cursor-pointer hover:text-main-text/60" @click="viewDetails(item)">
                + {{ diffPairs({}, item.new_values).length - 3 }} more...
              </div>
            </template>
          </div>
        </template>

        <template #cell-actions="{ item }">
          <div class="flex justify-end p-1">
            <ActionDropdown
              :actions="getAuditActions(item)"
              :item="item"
              :index="0"
              :total="logs.length"
            />
          </div>
        </template>
      </DataTable>

      <!-- Pagination -->
      <TablePagination
        v-if="!loading && logs.length > 0"
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :per-page="pagination.per_page"
        @page-change="changePage"
        class="border-t border-card-border/60"
      />
    </div>

    <!-- Rollback Confirmation Modal -->
    <ConfirmDialog
      :show="showConfirm"
      :title="$tr('audit.rollback_title', 'Rollback Action')"
      :message="$tr('audit.rollback_desc', 'Are you sure you want to rollback this record? This will instantly revert the resource to the state it was in at the time of this audit.')"
      :confirm-text="$tr('audit.rollback_confirm', 'Yes, Rollback')"
      variant="danger"
      :loading="rollingBack !== null"
      @close="showConfirm = false"
      @confirm="executeRollback"
    />
    <!-- View Details Modal -->
    <Modal
      :show="showViewModal"
      size="xl"
      :title="$tr('audit.view_details_title', 'Audit Details')"
      @close="closeViewModal"
    >
      <div v-if="viewItem" class="space-y-4 pt-2">
        <!-- Meta info row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 bg-main-bg p-3 rounded-xl border border-card-border/60">
          <div>
            <div class="text-xs text-main-text/70 font-medium mb-1">{{ $tr('common.event', 'Event') }} <span class="text-[10px] text-main-text/60 font-mono ml-1">#{{ viewItem.id }}</span></div>
            <span :class="eventConfig(viewItem.event).badge" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold border">
              <component :is="eventConfig(viewItem.event).icon" class="w-3 h-3" />
              {{ eventLabel(viewItem.event) }}
            </span>
          </div>
          <div class="min-w-0">
            <div class="text-xs text-main-text/70 font-medium mb-1">{{ $tr('audit.causer', 'Causer') }}</div>
            <div class="flex flex-col min-w-0">
              <div class="text-sm font-medium text-main-text truncate" :title="viewItem.causer_name || $tr('common.system', 'System')">{{ viewItem.causer_name || $tr('common.system', 'System') }}</div>
              <div v-if="viewItem.causer_registration_id" class="text-[11px] text-main-text/40 font-mono truncate">{{ viewItem.causer_registration_id }}</div>
            </div>
          </div>
          <div class="min-w-0">
            <div class="text-xs text-main-text/70 font-medium mb-1">{{ $tr('audit.resource', 'Resource') }} <span class="text-[10px] text-main-text/60 font-mono ml-1">#{{ viewItem.model_id }}</span></div>
            <div class="flex flex-col min-w-0">
              <div class="text-sm font-medium text-main-text truncate">{{ friendlyModel(viewItem.model_type) }}</div>
              <div v-if="viewItem.resource_registration_id" class="text-[11px] text-main-text/40 font-mono truncate">{{ viewItem.resource_registration_id }}</div>
            </div>
          </div>
          <div>
            <div class="text-xs text-main-text/70 font-medium mb-1">{{ $tr('common.date_time', 'Date & Time') }}</div>
            <div class="text-sm font-medium text-main-text">{{ formatDate(viewItem.created_at) }} {{ formatTime(viewItem.created_at) }}</div>
          </div>
        </div>

        <!-- Changes table -->
        <div>
          <h3 class="text-sm font-bold text-main-text mb-2">{{ $tr('audit.changes', 'Changes') }}</h3>
          <div class="bg-card-bg border border-card-border/60 rounded-xl overflow-hidden">
            <div class="grid grid-cols-12 bg-main-bg/50 px-4 py-2 border-b border-card-border/60 text-xs font-semibold text-main-text/70 capitalize tracking-wide">
              <div class="col-span-3">{{ $tr('common.property', 'Property') }}</div>
              <div class="col-span-4" v-if="viewItem.event?.toLowerCase() !== 'created'">{{ $tr('audit.old_value', 'Old Value') }}</div>
              <div class="col-span-1" v-if="viewItem.event?.toLowerCase() === 'updated'"></div>
              <div
                v-if="viewItem.event?.toLowerCase() !== 'deleted'"
                :class="viewItem.event?.toLowerCase() === 'created' ? 'col-span-9' : 'col-span-4'"
              >{{ $tr('audit.new_value', 'New Value') }}</div>
            </div>
            <div class="divide-y divide-card-border/40 max-h-[50vh] overflow-y-auto custom-scrollbar">
              <div
                v-for="(pair, i) in diffPairs(viewItem.old_values, viewItem.new_values)"
                :key="i"
                class="grid grid-cols-12 px-4 py-2 text-sm font-mono items-center hover:bg-main-bg/30 gap-x-1"
              >
                <div class="col-span-3 text-main-text/80 font-semibold truncate text-xs" :title="formatPropertyKey(pair.key)">{{ formatPropertyKey(pair.key) }}</div>

                <div
                  v-if="viewItem.event?.toLowerCase() !== 'created'"
                  class="col-span-4 truncate text-rose-500 bg-rose-500/5 px-2 py-1 rounded border border-rose-500/10 text-xs"
                  :title="String(formatDiffValue(pair.old, true))"
                >
                  {{ formatDiffValue(pair.old, false) }}
                </div>

                <div class="col-span-1 flex justify-center text-main-text/20 text-xs" v-if="viewItem.event?.toLowerCase() === 'updated'">→</div>

                <div
                  v-if="viewItem.event?.toLowerCase() !== 'deleted'"
                  :class="viewItem.event?.toLowerCase() === 'created' ? 'col-span-9' : 'col-span-4'"
                  class="truncate text-emerald-600 bg-emerald-500/5 px-2 py-1 rounded border border-emerald-500/10 text-xs"
                  :title="String(formatDiffValue(pair.new, true))"
                >
                  {{ formatDiffValue(pair.new, false) }}
                </div>
              </div>
              <div v-if="diffPairs(viewItem.old_values, viewItem.new_values).length === 0" class="px-4 py-8 text-center text-main-text/40 italic text-sm">
                {{ $tr('audit.no_changes', 'No property changes recorded.') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted, onActivated, computed } from 'vue';
import { RefreshCcw, ArrowRight, RotateCcw, PlusCircle, PencilLine, Trash2, RefreshCw, ClipboardList, Eye } from 'lucide-vue-next';
import { useToastStore } from '@/stores/toast';
import apiClient from '@/api/apiClient';
import { formatDate as utilFormatDate, formatTime as utilFormatTime, localize } from '@/utils/format';

const formatPropertyKey = (key: string) => {
  const map: Record<string, string> = {
    'fine_paid': 'Fine Paid',
    'is_gift': 'Is Gift / Donation',
    'payment_method': 'Payment Method',
    'amount': 'Amount',
    'reference_number': 'Reference Number',
    'paid_at': 'Date Paid',
    'payer_name': 'Payer Name',
    'payment_period': 'Payment Period',
    'for_year': 'For Year',
    'for_month': 'For Month',
    'work_status': 'Work Status',
    'base_amount': 'Base Amount',
    'fine_amount': 'Fine Amount',
    'total_amount_due': 'Total Due',
    'amount_paid': 'Amount Paid',
    'status': 'Status',
    'member_name': 'Member Name',
    'donor_name': 'Donor Name',
    'created_at': 'Created At',
    'updated_at': 'Updated At',
    'deleted_at': 'Deleted At'
  };
  if (map[key]) return map[key];
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

import Button from '@/components/common/Button.vue';
import TablePagination from '@/components/common/TablePagination.vue';
import DataTable from '@/components/common/DataTable.vue';
import ActionDropdown from '@/components/common/ActionDropdown.vue';
import FormSelect from '@/components/common/FormSelect.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import Modal from '@/components/common/Modal.vue';
import { useLanguageStore } from '@/stores/languageStore';

const langStore = useLanguageStore();
const $tr = (key: string, defaultText = '', params?: Record<string, any>) => langStore.translate(key, params) || defaultText;

const toast = useToastStore();
const loading = ref(true);
const logs = ref<any[]>([]);
const rollingBack = ref<number | null>(null);

const showConfirm = ref(false);
const itemToRollback = ref<any>(null);

const showViewModal = ref(false);
const viewItem = ref<any>(null);

const viewDetails = (item: any) => {
  viewItem.value = item;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  setTimeout(() => { viewItem.value = null; }, 300);
};

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
});

const filters = reactive({
  event: '',
  model_type: '',
});

const eventOptions = computed(() => [
  { value: '', label: $tr('audit.events.all', 'All Events') },
  { value: 'created', label: $tr('audit.events.created', 'Created') },
  { value: 'updated', label: $tr('audit.events.updated', 'Updated') },
  { value: 'deleted', label: $tr('audit.events.deleted', 'Deleted') },
  { value: 'restored', label: $tr('audit.events.restored', 'Restored') },
]);

const modelOptions = computed(() => [
  { value: '', label: $tr('audit.resources.all', 'All Resources') },
  { value: 'User', label: $tr('audit.resources.user', 'User Profile') },
  { value: 'Role', label: $tr('audit.resources.role', 'Role & Permissions') },
  { value: 'GeneralAttendanceRecord', label: $tr('audit.resources.gen_attendance', 'General Attendance') },
  { value: 'GeneralAttendanceSession', label: $tr('audit.resources.gen_session', 'General Attendance Session') },
  { value: 'AttendanceRecord', label: $tr('audit.resources.course_attendance', 'Course Attendance') },
  { value: 'AttendanceSession', label: $tr('audit.resources.course_session', 'Course Attendance Session') },
  { value: 'StudentMark', label: $tr('audit.resources.student_mark', 'Student Mark') },
  { value: 'StudentResult', label: $tr('audit.resources.student_result', 'Student Final Result') },
  { value: 'Payment', label: $tr('audit.resources.payment', 'Payment') },
  { value: 'PaymentTransaction', label: $tr('audit.resources.transaction', 'Payment Transaction') },
  { value: 'SenbetClass', label: $tr('audit.resources.class', 'Class Configuration') },
  { value: 'Course', label: $tr('audit.resources.course', 'Course Configuration') },
  { value: 'CourseOffering', label: $tr('audit.resources.offering', 'Course Offering') },
  { value: 'AssessmentType', label: $tr('audit.resources.assessment', 'Assessment Configuration') },
  { value: 'Setting', label: $tr('audit.resources.setting', 'System Settings') },
]);

const fetchLogs = async (page = 1, silent = false) => {
  if (!silent) loading.value = true;
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
    if (!silent) loading.value = false;
  }
};

const changePage = (page: number) => {
  fetchLogs(page);
};

watch(filters, () => {
  fetchLogs(1);
});

onMounted(() => {
  if (logs.value.length === 0) {
    fetchLogs();
  }
});

onActivated(() => {
  if (logs.value.length === 0) {
    fetchLogs();
  }
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
      toast.success($tr('audit.rollback_success', 'Record successfully rolled back.'));
      // Gentle silent refresh so table updates instantly without skeleton
      fetchLogs(pagination.current_page, true);
    } else {
      toast.error(res.data.message || $tr('audit.rollback_failed', 'Rollback failed.'));
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message || $tr('audit.rollback_failed', 'Rollback failed.'));
  } finally {
    rollingBack.value = null;
    showConfirm.value = false;
    itemToRollback.value = null;
  }
};

const getAuditActions = (item: any) => {
  const actions: any[] = [
    {
      label: $tr('common.view_details', 'View Details'),
      icon: Eye,
      onClick: (log: any) => viewDetails(log),
    }
  ];

  // Use the server-side can_rollback flag — only show when reversible
  if (item.can_rollback) {
    actions.push({
      label: $tr('audit.rollback', 'Rollback'),
      icon: RotateCcw,
      colorClass: 'text-rose-500',
      hoverClass: 'hover:!bg-rose-500 hover:!text-white',
      onClick: (log: any) => confirmRollback(log),
    });
  }

  return actions;
};

const eventConfig = (event: string) => {
  switch (event?.toLowerCase()) {
    case 'created':  return { badge: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20', icon: PlusCircle,  dot: 'bg-emerald-500' };
    case 'updated':  return { badge: 'bg-blue-500/10 text-blue-500 border-blue-500/20',         icon: PencilLine,  dot: 'bg-blue-500' };
    case 'deleted':  return { badge: 'bg-rose-500/10 text-rose-600 border-rose-500/20',          icon: Trash2,      dot: 'bg-rose-500' };
    case 'restored': return { badge: 'bg-amber-500/10 text-amber-600 border-amber-500/20',       icon: RefreshCw,   dot: 'bg-amber-500' };
    default:         return { badge: 'bg-main-text/5 text-main-text/50 border-main-text/10',     icon: ClipboardList, dot: 'bg-main-text/30' };
  }
};

const eventLabel = (event: string) => {
  switch (event?.toLowerCase()) {
    case 'created':  return $tr('audit.events.created', 'Created');
    case 'updated':  return $tr('audit.events.updated', 'Updated');
    case 'deleted':  return $tr('audit.events.deleted', 'Deleted');
    case 'restored': return $tr('audit.events.restored', 'Restored');
    default:         return event;
  }
};

// Map fully-qualified model class names to friendly labels
const MODEL_LABELS: Record<string, string> = {
  User: 'user', Role: 'role',
  GeneralAttendanceRecord: 'gen_attendance', GeneralAttendanceSession: 'gen_session',
  AttendanceRecord: 'course_attendance', AttendanceSession: 'course_session',
  StudentMark: 'student_mark', StudentResult: 'student_result',
  Payment: 'payment', PaymentTransaction: 'transaction',
  SenbetClass: 'class', Course: 'course',
  CourseOffering: 'offering', AssessmentType: 'assessment', Setting: 'setting',
};

const friendlyModel = (fqn: string) => {
  const name = fqn?.split('\\').pop() ?? fqn;
  const key = MODEL_LABELS[name];
  return key ? $tr(`audit.resources.${key}`, name) : name;
};

const formatDate = (dateString: string) => utilFormatDate(dateString, langStore.currentLanguage);
const formatTime = (dateString: string) => utilFormatTime(dateString, langStore.currentLanguage);

const formatJSON = (val: any) => {
  if (!val) return '';
  try {
    const keys = Object.keys(val);
    if (keys.length === 0) return '';
    return `{ ${keys.slice(0, 3).join(', ')}${keys.length > 3 ? '…' : ''} }`;
  } catch {
    return String(val);
  }
};

const isStringId = (id: any) => typeof id === 'string' && isNaN(Number(id));

const diffPairs = (oldValues: any, newValues: any) => {
  const oldV = oldValues || {};
  const newV = newValues || {};
  const keys = Array.from(new Set([...Object.keys(oldV), ...Object.keys(newV)]));
  
  return keys.map(key => ({
    key,
    old: oldV[key],
    new: newV[key]
  })).filter(pair => {
    if (pair.key === 'id') return false;
    // If objects, compare stringified to catch inner changes
    if (typeof pair.old === 'object' || typeof pair.new === 'object') {
      return JSON.stringify(pair.old) !== JSON.stringify(pair.new);
    }
    return pair.old !== pair.new;
  });
};

const formatDiffValue = (val: any, multiline = false) => {
  if (val === null || val === undefined) return '∅';
  
  let currentVal = val;
  // If it's a string, try to parse it recursively in case of double-encoded JSON (like name in audit DB)
  if (typeof currentVal === 'string') {
    let attempts = 0;
    while (typeof currentVal === 'string' && currentVal.trim().startsWith('{') && attempts < 3) {
      try {
        currentVal = JSON.parse(currentVal);
        attempts++;
      } catch (e) {
        break;
      }
    }
  }

  if (typeof currentVal === 'object' && currentVal !== null) {
    // If it looks like a translation object {en, am, or}
    if (currentVal.en || currentVal.am || currentVal.or || currentVal.om) {
      return localize(currentVal, langStore.currentLanguage);
    }
    return multiline ? JSON.stringify(currentVal, null, 2) : JSON.stringify(currentVal);
  }

  return String(currentVal);
};
</script>
