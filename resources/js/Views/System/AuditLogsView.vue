<template>
  <div class="h-full flex flex-col p-4 md:p-6 lg:p-8 max-w-7xl mx-auto w-full">
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
    <div class="mt-6 flex flex-col sm:flex-row gap-3 mb-4">
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
    <div class="bg-card-bg border border-card-border/60 rounded-2xl overflow-hidden shadow-sm flex flex-col">

      <!-- Table when loaded -->
      <DataTable
        :items="logs"
        :loading="loading"
        :empty-title="$tr('audit.no_logs_title', 'No audit logs found')"
        :empty-desc="$tr('audit.no_logs_desc', 'There are no recorded actions matching your filters.')"
        class="border-0 rounded-none flex-1 custom-scrollbar"
      >
        <template #columns>
          <TableColumn :label="$tr('common.time', 'Time')" />
          <TableColumn :label="$tr('common.action', 'Action')" />
          <TableColumn :label="$tr('audit.causer', 'User')" />
          <TableColumn :label="$tr('audit.resource', 'Resource')" />
          <TableColumn :label="$tr('audit.changes', 'Changes')" />
          <TableColumn label="" align="right" />
        </template>

        <template #row="{ item }">
          <td class="px-5 py-3.5 whitespace-nowrap">
            <div class="text-sm font-medium text-main-text">{{ formatDate(item.created_at) }}</div>
            <div class="text-xs text-main-text/50 mt-0.5">{{ formatTime(item.created_at) }}</div>
          </td>

          <td class="px-5 py-3.5 whitespace-nowrap">
            <span :class="eventConfig(item.event).badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border">
              <component :is="eventConfig(item.event).icon" class="w-3 h-3" />
              {{ eventLabel(item.event) }}
            </span>
          </td>

          <td class="px-5 py-3.5 whitespace-nowrap text-sm text-main-text/80" :title="item.causer_name">
            {{ item.causer_name || '—' }}
          </td>

          <td class="px-5 py-3.5 whitespace-nowrap">
            <div class="flex items-center gap-2 min-w-0">
              <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="eventConfig(item.event).dot"></div>
              <div class="min-w-0">
                <div class="text-sm font-medium text-main-text truncate">{{ friendlyModel(item.model_type) }}</div>
                <div class="text-xs text-main-text/40 font-mono truncate max-w-[120px]" :title="item.model_id">
                  {{ isStringId(item.model_id) ? item.model_id : `#${item.model_id}` }}
                </div>
              </div>
            </div>
          </td>

          <td class="px-5 py-3.5 w-full max-w-[350px]">
            <div class="min-w-0 space-y-1 max-h-32 overflow-y-auto custom-scrollbar pr-1">
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
          </td>

          <td class="px-5 py-3.5 whitespace-nowrap text-right">
            <div class="flex justify-end gap-2">
              <Button
                variant="secondary"
                class="h-8 px-2.5 text-xs"
                :icon="Eye"
                @click="viewDetails(item)"
                title="View Details"
              />
              <Button
                v-if="item.event?.toLowerCase() !== 'created'"
                variant="soft-danger"
                class="h-8 px-3 text-xs"
                :icon="RotateCcw"
                @click="confirmRollback(item)"
                :loading="rollingBack === item.id"
              >
                {{ $tr('audit.rollback', 'Rollback') }}
              </Button>
            </div>
          </td>
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
    <Modal :show="showViewModal" max-width="3xl" @close="closeViewModal">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-main-text">Audit Details</h2>
          <Button variant="ghost" class="w-8 h-8 p-0" @click="closeViewModal">✕</Button>
        </div>
        
        <div v-if="viewItem" class="space-y-6">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-main-bg p-4 rounded-xl border border-card-border/60">
            <div>
              <div class="text-xs text-main-text/50 font-medium">Event</div>
              <div class="text-sm font-semibold mt-1" :class="eventConfig(viewItem.event).badge.split(' ')[1]">{{ eventLabel(viewItem.event) }}</div>
            </div>
            <div>
              <div class="text-xs text-main-text/50 font-medium">Causer</div>
              <div class="text-sm font-medium mt-1">{{ viewItem.causer_name || 'System' }}</div>
            </div>
            <div>
              <div class="text-xs text-main-text/50 font-medium">Resource</div>
              <div class="text-sm font-medium mt-1">{{ friendlyModel(viewItem.model_type) }} #{{ viewItem.model_id }}</div>
            </div>
            <div>
              <div class="text-xs text-main-text/50 font-medium">Date & Time</div>
              <div class="text-sm font-medium mt-1">{{ formatDate(viewItem.created_at) }} {{ formatTime(viewItem.created_at) }}</div>
            </div>
          </div>

          <div>
            <h3 class="text-sm font-bold text-main-text mb-3">Changes</h3>
            <div class="bg-card-bg border border-card-border/60 rounded-xl overflow-hidden">
              <div class="grid grid-cols-12 bg-main-bg/50 px-4 py-2 border-b border-card-border/60 text-xs font-semibold text-main-text/40">
                <div class="col-span-3">Property</div>
                <div class="col-span-4" v-if="viewItem.event?.toLowerCase() !== 'created'">Old Value</div>
                <div class="col-span-1" v-if="viewItem.event?.toLowerCase() === 'updated'"></div>
                <div class="col-span-4" v-if="viewItem.event?.toLowerCase() !== 'deleted'" :class="{'col-span-8': viewItem.event?.toLowerCase() === 'created'}">New Value</div>
              </div>
              <div class="divide-y divide-card-border/40 max-h-[400px] overflow-y-auto custom-scrollbar">
                <div v-for="(pair, i) in diffPairs(viewItem.old_values, viewItem.new_values)" :key="i" class="grid grid-cols-12 px-4 py-3 text-sm font-mono items-center hover:bg-main-bg/30">
                  <div class="col-span-3 text-main-text/60 font-semibold pr-2 break-all">{{ pair.key }}</div>
                  
                  <div class="col-span-4 break-all text-rose-500 bg-rose-500/5 p-2 rounded-lg border border-rose-500/10 max-h-48 overflow-y-auto custom-scrollbar" v-if="viewItem.event?.toLowerCase() !== 'created'">
                    {{ formatDiffValue(pair.old, true) }}
                  </div>
                  
                  <div class="col-span-1 flex justify-center text-main-text/20" v-if="viewItem.event?.toLowerCase() === 'updated'">→</div>
                  
                  <div class="col-span-4 break-all text-emerald-600 bg-emerald-500/5 p-2 rounded-lg border border-emerald-500/10 max-h-48 overflow-y-auto custom-scrollbar" v-if="viewItem.event?.toLowerCase() !== 'deleted'" :class="{'col-span-9': viewItem.event?.toLowerCase() === 'created'}">
                    {{ formatDiffValue(pair.new, true) }}
                  </div>
                </div>
                <div v-if="diffPairs(viewItem.old_values, viewItem.new_values).length === 0" class="px-4 py-8 text-center text-main-text/40 italic text-sm">
                  No property changes recorded.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted, computed } from 'vue';
import { RefreshCcw, ArrowRight, RotateCcw, PlusCircle, PencilLine, Trash2, RefreshCw, ClipboardList, Eye } from 'lucide-vue-next';
import { useToastStore } from '@/stores/toast';
import apiClient from '@/api/apiClient';
import { formatDate as utilFormatDate, formatTime as utilFormatTime, localize } from '@/utils/format';

import Button from '@/components/common/Button.vue';
import TablePagination from '@/components/common/TablePagination.vue';
import DataTable from '@/components/common/DataTable.vue';
import TableColumn from '@/components/common/TableColumn.vue';
import FormSelect from '@/components/common/FormSelect.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import Modal from '@/components/common/Modal.vue';
import { useLanguageStore } from '@/stores/languageStore';

const langStore = useLanguageStore();
const $tr = (key: string, defaultText: string) => langStore.translate(key) || defaultText;

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
    // If objects, compare stringified to catch inner changes
    if (typeof pair.old === 'object' || typeof pair.new === 'object') {
      return JSON.stringify(pair.old) !== JSON.stringify(pair.new);
    }
    return pair.old !== pair.new;
  });
};

const formatDiffValue = (val: any, multiline = false) => {
  if (val === null || val === undefined) return '∅';
  
  // If it's a string, see if it is JSON we can parse and localize (like name)
  if (typeof val === 'string' && val.trim().startsWith('{')) {
    try {
      const parsed = JSON.parse(val);
      if (typeof parsed === 'object' && parsed !== null) {
        // If it looks like a translation object {en, am, or}
        if (parsed.en || parsed.am || parsed.or) {
          return localize(parsed, langStore.currentLanguage);
        }
        return multiline ? JSON.stringify(parsed, null, 2) : JSON.stringify(parsed);
      }
    } catch (e) {
      // not json, return raw
    }
  }

  // If it's an object/array directly
  if (typeof val === 'object') {
    return multiline ? JSON.stringify(val, null, 2) : JSON.stringify(val);
  }

  return String(val);
};
</script>
