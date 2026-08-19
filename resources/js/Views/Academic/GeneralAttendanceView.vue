<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-main-text">{{ $tr('attendance.general.title') }}</h1>
        <p class="text-sm mt-1 text-main-text/60">{{ $tr('attendance.general.subtitle') }}</p>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card-bg p-4 rounded-xl border border-card-border shadow-sm">
      <div class="flex flex-wrap items-center gap-4">
        <!-- Date Picker -->
        <div class="flex items-center gap-2">
          <Calendar class="w-4 h-4 text-main-text/40" />
          <input
            type="date"
            v-model="attendanceDate"
            @change="fetchAttendance"
            class="px-3 py-2 text-sm font-semibold border rounded-lg focus:ring-2 focus:ring-brand-blue shadow-sm"
            style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border); color-scheme: light;"
          />
        </div>

        <!-- Class Selector -->
        <div class="flex items-center gap-2">
          <GraduationCap class="w-4 h-4 text-main-text/40" />
          <select 
            v-model="selectedClass"
            @change="fetchAttendance"
            class="px-3 py-2 text-sm font-semibold border rounded-lg focus:ring-2 focus:ring-brand-blue shadow-sm"
            style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border); color-scheme: light;"
          >
            <option value="" disabled>{{ $tr('attendance.general.select_class') }}</option>
            <option v-for="c in availableClasses" :key="c" :value="c" style="background-color: var(--input-bg); color: var(--text-main);">
              {{ classLabel(c) }}
            </option>
          </select>
        </div>
        
        <!-- Section Selector -->
        <div class="flex items-center gap-2" v-if="selectedClass && availableSections.length > 0">
          <Layers class="w-4 h-4 text-main-text/40" />
          <select 
            v-model="selectedSection"
            @change="fetchAttendance"
            class="px-3 py-2 text-sm font-semibold border rounded-lg focus:ring-2 focus:ring-brand-blue shadow-sm"
            style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border); color-scheme: light;"
          >
            <option value="">{{ $tr('attendance.general.all_sections') }}</option>
            <option v-for="s in availableSections" :key="s" :value="s" style="background-color: var(--input-bg); color: var(--text-main);">
              {{ $tr('attendance.general.section') }}: {{ s }}
            </option>
          </select>
        </div>

        <!-- Status Badge -->
        <div v-if="selectedClass" class="flex items-center ml-2">
          <span v-if="isSaved" class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-600 text-white shadow-sm">
            <CheckCircle class="w-3.5 h-3.5" /> {{ $tr('attendance.general.saved') }}
          </span>
          <span v-else class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-amber-500 text-white shadow-sm">
            <AlertCircle class="w-3.5 h-3.5" /> {{ $tr('attendance.general.not_saved') }}
          </span>
        </div>
      </div>

      <button v-if="canManageAttendance" @click="saveAttendance" :disabled="saving || loading || !selectedClass"
        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-brand-blue hover:bg-brand-blue/90 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm transition-colors">
        <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        {{ isSaved ? $tr('attendance.general.update_btn') : $tr('attendance.general.save_btn') }}
      </button>
    </div>

    <!-- Data Table -->
    <div v-if="selectedClass" class="bg-card-bg rounded-xl border border-card-border overflow-hidden shadow-sm">
      <!-- Table Title -->
      <div class="px-5 py-4 border-b border-card-border/60">
        <h2 class="text-base font-semibold text-main-text">{{ $tr('attendance.general.table_title') }}</h2>
        <p class="text-xs text-main-text/50 mt-0.5">{{ classLabel(selectedClass) }} — {{ attendanceDate }}</p>
      </div>
      <DataTable
        :items="pagedStudents"
        :loading="loading"
        :empty-message="$tr('attendance.general.no_students')"
        :sort-by="sortBy"
        :sort-order="sortOrder"
        :pagination="pagination"
        @sort="handleSort"
        @page-change="onPageChange"
        @per-page-change="onPerPageChange"
        row-key="id"
      >
        <TableColumn field="student" :header="$tr('attendance.general.col_student')" align="left" sortable>
          <template #default="{ row: item }">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-brand-blue flex items-center justify-center text-white text-xs font-bold shrink-0 overflow-hidden">
                <span class="capitalize">{{ initials(item.name) }}</span>
              </div>
              <p class="text-sm font-medium text-main-text group-hover:text-accent transition-colors">{{ localize(item.name) }}</p>
            </div>
          </template>
        </TableColumn>

        <TableColumn field="registration_id" :header="$tr('attendance.general.col_reg_id')" align="left" sortable>
          <template #default="{ row: item }">
            <span class="px-3 py-1.5 rounded-xl bg-brand-blue/5 text-brand-blue text-[13px] font-normal border border-brand-blue/10 tracking-wide font-mono shrink-0 whitespace-nowrap">{{ item.registration_id || '—' }}</span>
          </template>
        </TableColumn>

        <TableColumn field="gender" :header="$tr('attendance.general.col_gender')" align="left" sortable>
          <template #default="{ row: item }">
            <span class="text-sm font-medium text-main-text/80 capitalize">{{ item.gender || '—' }}</span>
          </template>
        </TableColumn>

        <TableColumn field="status" :header="$tr('attendance.general.col_status')" align="left">
          <template #default="{ row: item }">
            <div class="flex items-center gap-1.5">
              <label class="inline-flex items-center" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                <input type="radio" :name="'att_'+item.id" value="present" v-model="item.status" :disabled="!canManageAttendance" class="sr-only peer" />
                <div class="px-3 py-1.5 text-xs font-semibold rounded-lg
                  bg-gray-100 text-gray-500 border border-gray-200
                  peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600
                  hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300
                  transition-all duration-150" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed'">
                  ✓ {{ $tr('attendance.general.present') }}
                </div>
              </label>
              <label class="inline-flex items-center" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                <input type="radio" :name="'att_'+item.id" value="permission" v-model="item.status" :disabled="!canManageAttendance" class="sr-only peer" />
                <div class="px-3 py-1.5 text-xs font-semibold rounded-lg
                  bg-gray-100 text-gray-500 border border-gray-200
                  peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500
                  hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300
                  transition-all duration-150" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed'">
                  ⚠ {{ $tr('attendance.general.permission') }}
                </div>
              </label>
              <label class="inline-flex items-center" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                <input type="radio" :name="'att_'+item.id" value="absent" v-model="item.status" :disabled="!canManageAttendance" class="sr-only peer" />
                <div class="px-3 py-1.5 text-xs font-semibold rounded-lg
                  bg-gray-100 text-gray-500 border border-gray-200
                  peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600
                  hover:bg-red-50 hover:text-red-700 hover:border-red-300
                  transition-all duration-150" :class="canManageAttendance ? 'cursor-pointer' : 'cursor-not-allowed'">
                  ✗ {{ $tr('attendance.general.absent') }}
                </div>
              </label>
            </div>
          </template>
        </TableColumn>

        <TableColumn field="notes" :header="$tr('attendance.general.col_notes')" align="left">
          <template #default="{ row: item }">
            <input
              type="text"
              v-model="item.notes"
              :placeholder="$tr('attendance.general.note_placeholder')"
              class="w-full px-2 py-1.5 text-sm bg-transparent border-none focus:ring-0 text-main-text placeholder-main-text/30"
            />
          </template>
        </TableColumn>
      </DataTable>
    </div>
    
    <div v-else class="flex flex-col items-center justify-center py-20 bg-card-bg rounded-xl border border-card-border shadow-sm">
      <div class="w-16 h-16 bg-brand-blue/10 rounded-full flex items-center justify-center mb-4">
        <GraduationCap class="w-8 h-8 text-brand-blue" />
      </div>
      <h3 class="text-lg font-bold text-main-text mb-2">{{ $tr('attendance.general.empty_title') }}</h3>
      <p class="text-main-text/50 text-center max-w-sm">{{ $tr('attendance.general.empty_desc') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, getCurrentInstance } from 'vue';
import { Calendar, Save, Loader2, CheckCircle, AlertCircle, GraduationCap, Layers } from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import DataTable from '@/components/common/DataTable.vue';
import TableColumn from '@/components/common/TableColumn.vue';
import { useLanguageStore } from '@/stores/languageStore';
import { useToastStore } from '@/stores/toast';
import { usePermissions } from '@/composables/usePermissions';
import { Modules } from '@/constants/permissions';

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const langStore = useLanguageStore();
const toast = useToastStore();
const { canManage: canManageAttendance } = usePermissions(Modules.ACADEMIC_CLASSES);

const localize = (obj: any) => {
  if (!obj) return '';
  if (typeof obj === 'string') return obj;
  return obj[langStore.currentLanguage] || obj['en'] || '';
};

const initials = (nameObj: any) => {
  const name = localize(nameObj);
  return name ? name.substring(0, 2) : 'U';
};

// State
const classData = ref<Record<string, { label: string; sections: string[] }>>({});
const availableClasses = computed(() =>
  Object.keys(classData.value).sort((a, b) => {
    const order = ['child', ...Array.from({length:12},(_,i)=>String(i+1)), 'post_12'];
    return (order.indexOf(a) ?? 99) - (order.indexOf(b) ?? 99);
  })
);
const availableSections = computed(() => selectedClass.value ? (classData.value[selectedClass.value]?.sections || []) : []);
const classLabel = (code: string) => classData.value[code]?.label || `${$tr('attendance.general.class')}: ${code}`;
const selectedClass = ref('');
const selectedSection = ref('');
const attendanceDate = ref(new Date().toISOString().split('T')[0]);

const loading = ref(false);
const saving = ref(false);
const isSaved = ref(false);
const currentSession = ref<any>(null);
const students = ref<any[]>([]);

// Sort state
const sortBy = ref('name');
const sortOrder = ref<'asc' | 'desc'>('asc');
const sortedStudents = computed(() => {
  const key = sortBy.value;
  return [...students.value].sort((a, b) => {
    let av = key === 'name' ? localize(a[key]) : a[key];
    let bv = key === 'name' ? localize(b[key]) : b[key];
    if (typeof av === 'string') av = av.toLowerCase();
    if (typeof bv === 'string') bv = bv.toLowerCase();
    if (av < bv) return sortOrder.value === 'asc' ? -1 : 1;
    if (av > bv) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});
function handleSort(key: string) {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortOrder.value = 'asc';
  }
}

// Pagination state
const page = ref(1);
const perPage = ref(10);
const pagedStudents = computed(() => {
  const start = (page.value - 1) * perPage.value;
  return sortedStudents.value.slice(start, start + perPage.value);
});
const pagination = computed(() => ({
  currentPage: page.value,
  lastPage: Math.max(1, Math.ceil(sortedStudents.value.length / perPage.value)),
  total: sortedStudents.value.length,
  perPage: perPage.value,
}));
function onPageChange(p: number) { page.value = p; }
function onPerPageChange(n: number) { perPage.value = n; page.value = 1; }

const columns = computed(() => [
  { key: 'student', label: $tr('attendance.general.col_student'), align: 'left' as const },
  { key: 'registration_id', label: $tr('attendance.general.col_reg_id'), align: 'left' as const },
  { key: 'gender', label: $tr('attendance.general.col_gender'), align: 'left' as const },
  { key: 'status', label: $tr('attendance.general.col_status'), align: 'left' as const },
  { key: 'notes', label: $tr('attendance.general.col_notes'), align: 'left' as const },
]);

onMounted(async () => {
  try {
    const { data } = await apiClient.get('/academic/general-attendance/classes');
    classData.value = data.classes || {};
  } catch (error) {
    console.error('Failed to load classes', error);
  }
});

// Reset section when class changes
watch(selectedClass, () => {
  selectedSection.value = '';
});

const fetchAttendance = async () => {
  if (!selectedClass.value || !attendanceDate.value) return;

  loading.value = true;
  try {
    const { data } = await apiClient.post('/academic/general-attendance/session', {
      date: attendanceDate.value,
      senbet_class: selectedClass.value,
      section: selectedSection.value || null,
    });
    
    currentSession.value = data.session;
    students.value = data.students || [];
    isSaved.value = data.is_saved;
  } catch (error) {
    console.error('Failed to load attendance', error);
  } finally {
    loading.value = false;
  }
};

const saveAttendance = async () => {
  if (!currentSession.value) return;

  saving.value = true;
  try {
    const payload = {
      records: students.value.map(s => ({
        student_id: s.id,
        status: s.status,
        notes: s.notes
      }))
    };

    await apiClient.post(`/academic/general-attendance/session/${currentSession.value.id}/records`, payload);
    isSaved.value = true;
  } catch (error) {
    console.error('Failed to save attendance', error);
  } finally {
    saving.value = false;
  }
};
</script>
