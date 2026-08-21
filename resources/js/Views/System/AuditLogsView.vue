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

      <!-- Skeleton shimmer while loading -->
      <div v-if="loading" class="divide-y divide-card-border/40">
        <div class="px-5 py-3 bg-card-bg/80 grid grid-cols-6 gap-4 border-b border-card-border/60">
          <div v-for="i in 6" :key="i" class="h-3 rounded-full bg-main-text/5 animate-pulse" :class="i === 4 ? 'col-span-2' : ''"></div>
        </div>
        <div v-for="i in 8" :key="i" class="px-5 py-4 grid grid-cols-6 gap-4 items-center">
          <!-- time -->
          <div class="flex flex-col gap-1.5">
            <div class="h-3 w-20 rounded-full bg-main-text/8 animate-pulse"></div>
            <div class="h-2.5 w-14 rounded-full bg-main-text/5 animate-pulse"></div>
          </div>
          <!-- event badge -->
          <div class="h-5 w-16 rounded-full bg-main-text/8 animate-pulse"></div>
          <!-- causer -->
          <div class="h-3 w-24 rounded-full bg-main-text/8 animate-pulse"></div>
          <!-- resource -->
          <div class="col-span-2 h-3 w-32 rounded-full bg-main-text/8 animate-pulse"></div>
          <!-- action -->
          <div class="flex justify-end">
            <div class="h-7 w-20 rounded-lg bg-main-text/5 animate-pulse"></div>
          </div>
        </div>
      </div>

      <!-- Table when loaded -->
      <template v-else>
        <div class="overflow-x-auto min-w-full">
          <!-- Column headers -->
          <div class="px-5 py-3 bg-main-bg/50 grid grid-cols-[160px_120px_160px_1fr_220px_100px] min-w-[900px] gap-4 border-b border-card-border/60 text-xs font-semibold uppercase tracking-wider text-main-text/40">
            <div>{{ $tr('common.time', 'Time') }}</div>
            <div>{{ $tr('common.action', 'Action') }}</div>
            <div>{{ $tr('audit.causer', 'User') }}</div>
            <div>{{ $tr('audit.resource', 'Resource') }}</div>
            <div>{{ $tr('audit.changes', 'Changes') }}</div>
            <div class="text-right">{{ $tr('common.actions', 'Actions') }}</div>
          </div>

        <!-- Empty state — compact -->
        <div v-if="logs.length === 0" class="py-16 flex flex-col items-center justify-center gap-3">
          <div class="w-12 h-12 rounded-full bg-main-text/5 flex items-center justify-center">
            <ClipboardList class="w-6 h-6 text-main-text/25" />
          </div>
          <p class="text-sm font-medium text-main-text/50">{{ $tr('audit.no_logs_title', 'No audit logs found') }}</p>
          <p class="text-xs text-main-text/35 max-w-xs text-center">{{ $tr('audit.no_logs_desc', 'There are no recorded actions matching your filters.') }}</p>
        </div>

        <!-- Rows -->
        <div v-else class="divide-y divide-card-border/40 min-w-[900px]">
          <div
            v-for="item in logs"
            :key="item.id"
            class="px-5 py-3.5 grid grid-cols-[160px_120px_160px_1fr_220px_100px] min-w-[900px] gap-4 items-center hover:bg-main-bg/30 transition-colors duration-150"
          >
            <!-- Time -->
            <div>
              <div class="text-sm font-medium text-main-text">{{ formatDate(item.created_at) }}</div>
              <div class="text-xs text-main-text/50 mt-0.5">{{ formatTime(item.created_at) }}</div>
            </div>

            <!-- Event badge -->
            <div>
              <span :class="eventConfig(item.event).badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border">
                <component :is="eventConfig(item.event).icon" class="w-3 h-3" />
                {{ eventLabel(item.event) }}
              </span>
            </div>

            <!-- Causer -->
            <div class="text-sm text-main-text/80 truncate" :title="item.causer_name">
              {{ item.causer_name || '—' }}
            </div>

            <!-- Resource -->
            <div class="flex items-center gap-2 min-w-0">
              <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="eventConfig(item.event).dot"></div>
              <div class="min-w-0">
                <div class="text-sm font-medium text-main-text truncate">{{ friendlyModel(item.model_type) }}</div>
                <div class="text-xs text-main-text/40 font-mono truncate max-w-[120px]" :title="item.model_id">
                  {{ isStringId(item.model_id) ? item.model_id : `#${item.model_id}` }}
                </div>
              </div>
            </div>

            <!-- Changes -->
            <div class="min-w-0 space-y-1">
              <template v-if="item.event?.toLowerCase() === 'updated'">
                <div v-for="(pair, i) in diffPairs(item.old_values, item.new_values).slice(0, 2)" :key="i"
                  class="flex items-center gap-1.5 text-xs font-mono overflow-hidden">
                  <span class="text-main-text/40 shrink-0 truncate max-w-[60px]" :title="pair.key">{{ pair.key }}</span>
                  <span class="shrink-0 text-main-text/20">→</span>
                  <span class="truncate text-rose-500 bg-rose-500/5 px-1.5 py-0.5 rounded border border-rose-500/10 max-w-[55px]" :title="String(pair.old)">{{ String(pair.old ?? '∅') }}</span>
                  <span class="shrink-0 text-main-text/20">→</span>
                  <span class="truncate text-emerald-600 bg-emerald-500/5 px-1.5 py-0.5 rounded border border-emerald-500/10 max-w-[55px]" :title="String(pair.new)">{{ String(pair.new ?? '∅') }}</span>
                </div>
                <div v-if="diffPairs(item.old_values, item.new_values).length === 0" class="text-xs text-main-text/30 italic">—</div>
              </template>
              <template v-else-if="item.event?.toLowerCase() === 'deleted'">
                <div class="truncate text-xs font-mono text-rose-500/70 bg-rose-500/5 px-2 py-1 rounded-md border border-rose-500/10" :title="formatJSON(item.old_values)">
                  {{ formatJSON(item.old_values) || '∅' }}
                </div>
              </template>
              <template v-else>
                <div class="truncate text-xs font-mono text-emerald-600/80 bg-emerald-500/5 px-2 py-1 rounded-md border border-emerald-500/10" :title="formatJSON(item.new_values)">
                  {{ formatJSON(item.new_values) || '∅' }}
                </div>
              </template>
            </div>

            <!-- Action -->
            <div class="flex justify-end">
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
          </div>
        </div>
        </div> <!-- End overflow-x-auto wrapper -->
      </template>

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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted, computed } from 'vue';
import { RefreshCcw, ArrowRight, RotateCcw, PlusCircle, PencilLine, Trash2, RefreshCw, ClipboardList } from 'lucide-vue-next';
import { useToastStore } from '@/stores/toast';
import apiClient from '@/api/apiClient';

import Button from '@/components/common/Button.vue';
import TablePagination from '@/components/common/TablePagination.vue';
import FormSelect from '@/components/common/FormSelect.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import { useLanguageStore } from '@/stores/languageStore';

const langStore = useLanguageStore();
const $tr = (key: string, defaultText: string) => langStore.translate(key) || defaultText;

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
      toast.success($tr('audit.rollback_success', 'Record successfully rolled back.'));
      fetchLogs(pagination.current_page);
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

const formatDate = (dateString: string) =>
  new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

const formatTime = (dateString: string) =>
  new Date(dateString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

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
  })).filter(pair => pair.old !== pair.new);
};
</script>
