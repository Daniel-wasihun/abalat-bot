<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col gap-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-main-text truncate">
          {{ localize(offering?.course?.name) || 'Loading Class Hub...' }}
        </h1>
        <div v-if="!loading" class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-blue text-white">
            {{ formatGrade(offering?.senbet_class) }}
          </span>
          <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-600 text-white shadow-sm">
            Semester {{ offering?.semester }}
          </span>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-semibold transition-colors whitespace-nowrap bg-transparent',
              activeTab === tab.id
                ? 'border-brand-blue text-brand-blue'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-brand-blue dark:hover:text-brand-blue hover:border-brand-blue/50',
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            {{ tab.name }}
          </button>
        </nav>
      </div>
    </div>

    <!-- ─── GLOBAL FILTERS ─────────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white dark:bg-card-bg p-4 rounded-xl border border-gray-200 dark:border-card-border shadow-sm">
      <!-- Search Bar -->
      <div class="relative w-full sm:max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Search students by name or ID..." 
          class="w-full pl-9 pr-4 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-brand-blue outline-none transition-colors shadow-sm"
          style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border);"
        />
      </div>
      
      <!-- Filter Option -->
      <div class="flex items-center gap-2 w-full sm:w-auto">
        <Filter class="w-4 h-4 text-gray-400" />
        <select 
          v-model="genderFilter"
          class="w-full sm:w-auto px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-brand-blue outline-none cursor-pointer shadow-sm"
          style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border); color-scheme: light;"
        >
          <option value="" style="background-color: var(--input-bg); color: var(--text-main);">All Genders</option>
          <option value="male" style="background-color: var(--input-bg); color: var(--text-main);">Male</option>
          <option value="female" style="background-color: var(--input-bg); color: var(--text-main);">Female</option>
        </select>
      </div>
    </div>

    <!-- ─── ASSESSMENT TAB ─────────────────────────────────────────────────── -->
    <div v-if="activeTab === 'assessment'" class="space-y-4">
      <div class="flex items-center justify-end">
        <div class="flex items-center gap-3">
          <span v-if="isFinalized" class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 rounded-full ring-1 ring-amber-200 dark:ring-amber-700">
            <Lock class="w-3.5 h-3.5" /> Results Finalized
          </span>
          <button @click="saveAllMarks" :disabled="savingMarks || isFinalized"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-brand-blue hover:bg-brand-blue/90 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm transition-colors">
            <Loader2 v-if="savingMarks" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            {{ savingMarks ? 'Saving...' : 'Save All' }}
          </button>
        </div>
      </div>

      <DataTable
        class="[&_td]:border [&_td]:border-gray-200 dark:[&_td]:border-card-border/50 [&_th]:border [&_th]:border-gray-200 dark:[&_th]:border-card-border/50"
        :items="filteredStudents"
        :columns="assessmentColumns"
        :loading="studentsLoading || loading"
        empty-message="No students enrolled in this offering."
        row-key="id"
      >
        <template #cell-student="{ item }">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-brand-blue flex items-center justify-center text-white text-xs font-bold shrink-0 overflow-hidden">
              <img v-if="item.info?.profile_picture" :src="getProfileUrl(item.info.profile_picture)" class="w-full h-full object-cover" />
              <span v-else>{{ initials(item.name) }}</span>
            </div>
            <p class="text-sm font-medium text-main-text">{{ localize(item.name) }}</p>
          </div>
        </template>

        <template #cell-registration_id="{ item }">
          <span class="text-sm font-mono text-main-text/80">{{ item.info?.registration_id || '—' }}</span>
        </template>

        <template #cell-gender="{ item }">
          <span class="text-sm font-medium text-main-text/80 capitalize">{{ item.info?.gender || '—' }}</span>
        </template>

        <!-- Dynamic Score Inputs -->
        <template v-for="comp in componentInfo" :key="comp.key" #[`cell-${comp.key}`]="{ item }">
          <input
            type="number"
            :min="0"
            :max="comp.max"
            step="0.5"
            :disabled="isFinalized"
            v-model.number="draftMarks[item.id][comp.key]"
            @input="recomputeLocal(item.id)"
            @keydown="['e', 'E', '+', '-'].includes($event.key) && $event.preventDefault()"
            class="w-full h-full min-w-[80px] px-2 py-3.5 text-center text-sm border-transparent bg-transparent rounded focus:ring-2 focus:ring-brand-blue disabled:opacity-50 outline-none"
            :class="{'text-red-500 font-semibold': draftMarks[item.id][comp.key] > comp.max}"
          />
        </template>

        <!-- Total -->
        <template #cell-total="{ item }">
          <span class="text-sm font-bold"
            :class="localTotals[item.id] == null ? 'text-gray-300 dark:text-gray-600' :
                    localTotals[item.id] >= 60 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'">
            {{ localTotals[item.id] != null ? localTotals[item.id].toFixed(1) : '—' }}
          </span>
        </template>

        <!-- Grade -->
        <template #cell-grade="{ item }">
          <span v-if="localGrades[item.id]"
            class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold"
            :class="gradeColor(localGrades[item.id])">
            {{ localGrades[item.id] }}
          </span>
          <span v-else class="text-gray-300 dark:text-gray-600 text-sm">—</span>
        </template>

        <!-- Remarks -->
        <template #cell-remarks="{ item }">
          <input
            type="text"
            :disabled="isFinalized"
            v-model="draftRemarks[item.id]"
            placeholder="Remarks…"
            class="w-32 lg:w-48 px-2 py-1 text-sm border-transparent bg-transparent rounded focus:ring-2 focus:ring-brand-blue disabled:opacity-50 outline-none text-main-text"
          />
        </template>
      </DataTable>
    </div>

    <!-- ─── ATTENDANCE TAB ─────────────────────────────────────────────────── -->
    <div v-else-if="activeTab === 'attendance'" class="space-y-4">
      <!-- Toolbar -->
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
          <input
            type="date"
            v-model="attendanceDate"
            @change="loadAttendanceForDate"
            class="px-3 py-2 text-sm font-semibold border rounded-lg focus:ring-2 focus:ring-brand-blue shadow-sm"
            style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border); color-scheme: light;"
          />
          <!-- Saved indicator -->
          <span v-if="attendanceSaved" class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-600 text-white shadow-sm">
            <CheckCircle class="w-3.5 h-3.5" /> Saved
          </span>
          <span v-else class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-red-600 text-white shadow-sm">
            <AlertCircle class="w-3.5 h-3.5" /> Not saved yet
          </span>
        </div>
        <button @click="openSaveConfirm" :disabled="savingAttendance || attendanceLoading"
          class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-brand-blue hover:bg-brand-blue/90 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm transition-colors">
          <Loader2 v-if="savingAttendance" class="w-4 h-4 animate-spin" />
          <Save v-else class="w-4 h-4" />
          {{ attendanceSaved ? 'Update Attendance' : 'Save Attendance' }}
        </button>
      </div>

      <DataTable
        class="[&_td]:border [&_td]:border-gray-200 dark:[&_td]:border-card-border/50 [&_th]:border [&_th]:border-gray-200 dark:[&_th]:border-card-border/50"
        :items="filteredStudents"
        :columns="attendanceColumns"
        :loading="studentsLoading || loading || attendanceLoading"
        empty-message="No students enrolled in this offering."
        row-key="id"
      >
        <template #cell-student="{ item }">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-brand-blue flex items-center justify-center text-white text-xs font-bold shrink-0 overflow-hidden">
              <img v-if="item.info?.profile_picture" :src="getProfileUrl(item.info.profile_picture)" class="w-full h-full object-cover" />
              <span v-else>{{ initials(item.name) }}</span>
            </div>
            <p class="text-sm font-medium text-main-text">{{ localize(item.name) }}</p>
          </div>
        </template>

        <template #cell-registration_id="{ item }">
          <span class="text-sm font-mono text-main-text/80">{{ item.info?.registration_id || '—' }}</span>
        </template>

        <template #cell-gender="{ item }">
          <span class="text-sm text-main-text/80 capitalize">{{ item.info?.gender || '—' }}</span>
        </template>

        <template #cell-status="{ item }">
          <div class="flex items-center gap-1.5">
            <label class="inline-flex items-center cursor-pointer">
              <input type="radio" :name="'att_'+item.id" value="present" v-model="attendanceStatus[item.id]" class="sr-only peer" />
              <div class="px-3 py-1.5 text-xs font-semibold rounded-lg cursor-pointer
                bg-gray-100 text-gray-500 border border-gray-200
                dark:bg-white/5 dark:text-gray-500 dark:border-white/10
                peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600
                hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300
                dark:hover:bg-emerald-600/20 dark:hover:text-emerald-400
                transition-all duration-150">
                ✓ Present
              </div>
            </label>
            <label class="inline-flex items-center cursor-pointer">
              <input type="radio" :name="'att_'+item.id" value="permission" v-model="attendanceStatus[item.id]" class="sr-only peer" />
              <div class="px-3 py-1.5 text-xs font-semibold rounded-lg cursor-pointer
                bg-gray-100 text-gray-500 border border-gray-200
                dark:bg-white/5 dark:text-gray-500 dark:border-white/10
                peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500
                hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300
                dark:hover:bg-amber-500/20 dark:hover:text-amber-400
                transition-all duration-150">
                ◐ Permission
              </div>
            </label>
            <label class="inline-flex items-center cursor-pointer">
              <input type="radio" :name="'att_'+item.id" value="absent" v-model="attendanceStatus[item.id]" class="sr-only peer" />
              <div class="px-3 py-1.5 text-xs font-semibold rounded-lg cursor-pointer
                bg-gray-100 text-gray-500 border border-gray-200
                dark:bg-white/5 dark:text-gray-500 dark:border-white/10
                peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600
                hover:bg-rose-50 hover:text-rose-700 hover:border-rose-300
                dark:hover:bg-rose-600/20 dark:hover:text-rose-400
                transition-all duration-150">
                ✕ Absent
              </div>
            </label>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- ─── ATTENDANCE CONFIRM MODAL ──────────────────────────────────────── -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showAttendanceConfirm" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showAttendanceConfirm = false" />

          <!-- Modal -->
          <div class="relative bg-white dark:bg-card-bg rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-card-border animate-in zoom-in-95 duration-200">
            <div class="p-6 space-y-5">
            <!-- Icon + title -->
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                :class="attendanceSaved ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-brand-blue/10'">
                <component :is="attendanceSaved ? EditIcon : CalendarCheck" class="w-6 h-6"
                  :class="attendanceSaved ? 'text-amber-600 dark:text-amber-400' : 'text-brand-blue'" />
              </div>
              <div>
                <h3 class="text-base font-bold text-main-text">
                  {{ attendanceSaved ? 'Update Attendance' : 'Confirm Save Attendance' }}
                </h3>
                <p class="text-sm text-main-text/60 mt-1">
                  {{ attendanceSaved
                    ? 'You are editing already-saved attendance for this date. Changes will overwrite existing records.'
                    : 'You are about to save attendance for the selected date.' }}
                </p>
              </div>
            </div>

            <!-- Summary stats -->
            <div class="grid grid-cols-3 gap-3">
              <div class="text-center p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ attendanceSummary.present }}</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-500 font-medium mt-0.5">Present</p>
              </div>
              <div class="text-center p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ attendanceSummary.permission }}</p>
                <p class="text-xs text-amber-600 dark:text-amber-500 font-medium mt-0.5">Permission</p>
              </div>
              <div class="text-center p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ attendanceSummary.absent }}</p>
                <p class="text-xs text-rose-600 dark:text-rose-500 font-medium mt-0.5">Absent</p>
              </div>
            </div>

            <!-- Date info -->
            <div class="flex items-center gap-2 text-sm text-main-text/70 bg-gray-50 dark:bg-white/5 rounded-lg px-4 py-2.5">
              <Calendar class="w-4 h-4 shrink-0 text-brand-blue" />
              <span>Date: <strong class="text-main-text">{{ attendanceDate }}</strong></span>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-1">
              <button @click="showAttendanceConfirm = false"
                class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-main-text/70 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">
                Cancel
              </button>
              <button @click="confirmSaveAttendance" :disabled="savingAttendance"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-colors"
                :class="attendanceSaved ? 'bg-amber-500 hover:bg-amber-600' : 'bg-brand-blue hover:bg-brand-blue/90'">
                <Loader2 v-if="savingAttendance" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                {{ attendanceSaved ? 'Update' : 'Save' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { ClipboardEdit, Calendar, Save, Loader2, Lock, CheckCircle, AlertCircle, CalendarCheck, Edit as EditIcon, Search, Filter } from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import DataTable from '@/components/common/DataTable.vue';

// ─── Route ────────────────────────────────────────────────────────────────────
const route      = useRoute();
const offeringId = Number(route.params.id);

// ─── Tabs ─────────────────────────────────────────────────────────────────────
const tabs = [
  { id: 'assessment', name: 'Assessment', icon: ClipboardEdit },
  { id: 'attendance', name: 'Attendance', icon: Calendar },
];
const activeTab = ref('assessment');

// ─── State ────────────────────────────────────────────────────────────────────
const loading          = ref(true);
const studentsLoading  = ref(false);
const searchQuery      = ref('');
const genderFilter     = ref('');
const savingMarks      = ref(false);
const savingAttendance = ref(false);
const attendanceLoading = ref(false);

const offering         = ref<any>(null);
const students         = ref<any[]>([]);

const filteredStudents = computed(() => {
  let list = [...students.value];
  
  // Sort ascending by name initially
  list.sort((a, b) => {
    const nameA = localize(a.name).toLowerCase();
    const nameB = localize(b.name).toLowerCase();
    return nameA.localeCompare(nameB);
  });

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(s => {
      const name = localize(s.name).toLowerCase();
      const id = s.info?.registration_id?.toLowerCase() || '';
      return name.includes(q) || id.includes(q);
    });
  }
  
  if (genderFilter.value) {
    list = list.filter(s => s.info?.gender === genderFilter.value);
  }

  return list;
});
const results          = ref<any[]>([]);
const componentInfo    = ref<{ key: string; label: string; max: number }[]>([]);
const isFinalized      = ref(false);

// Draft state for inline gradebook editing
const draftMarks   = reactive<Record<number, Record<string, number | null>>>({});
const draftRemarks = reactive<Record<number, string>>({});
const localTotals  = reactive<Record<number, number | null>>({});
const localGrades  = reactive<Record<number, string | null>>({});

// ─── Attendance State ─────────────────────────────────────────────────────────
const attendanceDate    = ref(new Date().toISOString().split('T')[0]);
const attendanceStatus  = reactive<Record<number, string>>({});
const currentSessionId  = ref<number | null>(null);
const attendanceSaved   = ref(false);
const showAttendanceConfirm = ref(false);

// ─── Computed: attendance summary for the confirm modal ───────────────────────
const attendanceSummary = computed(() => {
  const vals = Object.values(attendanceStatus);
  return {
    present:    vals.filter(v => v === 'present').length,
    permission: vals.filter(v => v === 'permission').length,
    absent:     vals.filter(v => v === 'absent').length,
  };
});

// ─── Columns configuration ────────────────────────────────────────────────────
const assessmentColumns = computed(() => {
  const cols: any[] = [
    { key: 'student', label: 'Student', align: 'left' },
    { key: 'registration_id', label: 'Reg. ID', align: 'left' },
    { key: 'gender', label: 'Gender', align: 'center' }
  ];
  componentInfo.value.forEach(comp => {
    cols.push({ key: comp.key, label: `${comp.label} (/${comp.max})`, align: 'center' });
  });
  cols.push({ key: 'total', label: 'Total (/100)', align: 'center' });
  cols.push({ key: 'grade', label: 'Grade', align: 'center' });
  cols.push({ key: 'remarks', label: 'Remarks', align: 'left' });
  return cols;
});

const attendanceColumns: any[] = [
  { key: 'student', label: 'Student', align: 'left' },
  { key: 'registration_id', label: 'Reg. ID', align: 'left' },
  { key: 'gender', label: 'Gender', align: 'left' },
  { key: 'status', label: 'Attendance', align: 'left' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
const localize = (val: any) => {
  if (!val) return '';
  return typeof val === 'object' ? (val.en || val.am || val.or || '') : val;
};

const formatGrade = (val: string) => {
  if (!val) return '';
  if (val === 'child')   return 'Child/KG';
  if (val === 'post_12') return 'Post-12';
  return `Grade ${val}`;
};

const initials = (name: any) => {
  const n = localize(name);
  return n.split(' ').map((p: string) => p[0]).join('').substring(0, 2).toUpperCase();
};

const getProfileUrl = (path: string) => {
  if (!path) return undefined;
  if (path.startsWith('http')) return path;
  return `${import.meta.env.VITE_STORAGE_URL || '/storage'}/${path}`;
};

const getResult = (studentId: number) => results.value.find(r => r.student_id === studentId);

const gradeColor = (grade: string | null | undefined) => {
  if (!grade) return 'bg-gray-100 text-gray-400 dark:bg-gray-700';
  const map: Record<string, string> = {
    A: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    B: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    C: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    D: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    F: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  };
  return map[grade] ?? 'bg-gray-100 text-gray-500';
};

// ─── Local grade computation ──────────────────────────────────────────────────
const computeGrade = (total: number | null): string | null => {
  if (total == null) return null;
  if (total >= 90) return 'A';
  if (total >= 80) return 'B';
  if (total >= 70) return 'C';
  if (total >= 60) return 'D';
  return 'F';
};

const recomputeLocal = (studentId: number) => {
  const dm = draftMarks[studentId];
  if (!dm) return;
  const vals = Object.values(dm).filter(v => v !== null && v !== undefined && (v as any) !== '');
  if (vals.length === 0) {
    localTotals[studentId] = null;
    localGrades[studentId]  = null;
    return;
  }
  const total = vals.reduce((a, b) => Number(a) + Number(b), 0) as number;
  localTotals[studentId] = Math.min(parseFloat(total.toFixed(2)), 100);
  localGrades[studentId]  = computeGrade(localTotals[studentId]);
};

// ─── Data Fetching ────────────────────────────────────────────────────────────
const fetchOffering = async () => {
  loading.value = true;
  try {
    const r = await apiClient.get(`/academic/offerings/${offeringId}/results`);
    offering.value      = r.data.offering;
    componentInfo.value = r.data.component_info ?? [];
    results.value       = r.data.results ?? [];
    isFinalized.value   = results.value.some((res: any) => res.is_finalized);
  } catch (err) {
    console.error('Failed to load offering data', err);
  } finally {
    loading.value = false;
  }
};

const fetchStudents = async () => {
  studentsLoading.value = true;
  try {
    const res = await apiClient.get(`/academic/offerings/${offeringId}/students`);
    const rawStudents = res.data.data ?? res.data;
    students.value = rawStudents;

    students.value.forEach((s: any) => {
      const result = getResult(s.id);
      draftMarks[s.id] = {
        quiz_ca_score:    result?.quiz_ca_score    ?? null,
        midterm_score:    result?.midterm_score    ?? null,
        final_exam_score: result?.final_exam_score ?? null,
      };
      draftRemarks[s.id] = result?.remarks ?? '';
      recomputeLocal(s.id);
      // Default all to present
      attendanceStatus[s.id] = 'present';
    });
  } catch (err) {
    console.error('Failed to load students', err);
  } finally {
    studentsLoading.value = false;
  }
};

/**
 * Load (or initialize) attendance for the selected date.
 * Calls /by-date to get/create the session and pre-populate the form.
 */
const loadAttendanceForDate = async () => {
  attendanceLoading.value = true;
  currentSessionId.value  = null;
  attendanceSaved.value   = false;

  // Reset all to present
  students.value.forEach(s => { attendanceStatus[s.id] = 'present'; });

  try {
    const res = await apiClient.post(`/academic/offerings/${offeringId}/attendance/by-date`, {
      date: attendanceDate.value
    });
    const { session, records, is_saved } = res.data;
    currentSessionId.value = session.id;
    attendanceSaved.value  = is_saved;

    if (is_saved && records?.length) {
      records.forEach((rec: any) => {
        attendanceStatus[rec.student_id] = rec.status;
      });
    }
  } catch (err) {
    console.error('Failed to load attendance for date', err);
  } finally {
    attendanceLoading.value = false;
  }
};

// ─── Actions ──────────────────────────────────────────────────────────────────
const saveAllMarks = async () => {
  savingMarks.value = true;
  try {
    const requests = students.value.map(s =>
      apiClient.put(`/academic/offerings/${offeringId}/results/${s.id}`, {
        quiz_ca_score:    draftMarks[s.id].quiz_ca_score,
        midterm_score:    draftMarks[s.id].midterm_score,
        final_exam_score: draftMarks[s.id].final_exam_score,
        remarks:          draftRemarks[s.id] || null,
      })
    );
    await Promise.all(requests);

    const r = await apiClient.get(`/academic/offerings/${offeringId}/results`);
    results.value = r.data.results ?? [];
    results.value.forEach((res: any) => {
      localTotals[res.student_id] = res.total_score;
      localGrades[res.student_id] = res.letter_grade;
    });
  } catch (err) {
    console.error('Failed to save marks', err);
  } finally {
    savingMarks.value = false;
  }
};

const openSaveConfirm = async () => {
  // If no session loaded yet, load first
  if (!currentSessionId.value) {
    await loadAttendanceForDate();
  }
  showAttendanceConfirm.value = true;
};

const confirmSaveAttendance = async () => {
  savingAttendance.value = true;
  try {
    // Ensure we have a session
    if (!currentSessionId.value) {
      const res = await apiClient.post(`/academic/offerings/${offeringId}/attendance/by-date`, {
        date: attendanceDate.value
      });
      currentSessionId.value = res.data.session.id;
    }

    const records = students.value.map(s => ({
      student_id: s.id,
      status:     attendanceStatus[s.id] || 'present',
    }));

    await apiClient.post(
      `/academic/offerings/${offeringId}/attendance/${currentSessionId.value}/records`,
      { records }
    );

    attendanceSaved.value = true;
    showAttendanceConfirm.value = false;
  } catch (err) {
    console.error('Failed to save attendance', err);
  } finally {
    savingAttendance.value = false;
  }
};

// Reload attendance when tab switches to attendance
watch(activeTab, async (tab) => {
  if (tab === 'attendance' && students.value.length > 0) {
    await loadAttendanceForDate();
  }
});

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  await fetchOffering();
  await fetchStudents();
});
</script>
