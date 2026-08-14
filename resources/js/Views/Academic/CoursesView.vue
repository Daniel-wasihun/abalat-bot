<template>
  <div class="flex flex-col flex-1 min-w-0">
    <main class="grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

      <TableToolbar
        v-model="search"
        :placeholder="t('course.search_placeholder')"
        :show-filters="showFilters"
        :has-active-filters="filterStatus !== '' || filterSemester !== ''"
        :filter-label="t('common.filters')"
        :reset-label="t('common.reset')"
        :create-label="t('course.add')"
        :can-create="true"
        :loading="loading"
        @update:model-value="onSearch"
        @toggle-filters="showFilters = !showFilters"
        @reset="clearFilters"
        @create="openCreateModal"
      >
        <template #filters>
          <FormSelect
            v-model="filterStatus"
            :options="[
              { value: '',  label: t('course.all_statuses') },
              { value: '1', label: t('course.active') },
              { value: '0', label: t('course.inactive') },
            ]"
            :placeholder="t('course.all_statuses')"
            @change="fetchCourses(true)"
          />
          <FormSelect
            v-model="filterSemester"
            :options="[
              { value: '',  label: t('course.all_semesters') },
              { value: '1', label: t('course.semester_1') },
              { value: '2', label: t('course.semester_2') },
            ]"
            :placeholder="t('course.all_semesters')"
            @change="fetchCourses(true)"
          />
        </template>
      </TableToolbar>

      <DataTable
        :items="courses"
        :columns="columns"
        :loading="loading"
        :empty-title="t('course.no_data')"
        :empty-message="t('course.no_data')"
        :empty-desc="t('course.no_data_desc')"
        :empty-icon="GraduationCap"
        :pagination="{
          currentPage: pagination.current_page,
          lastPage: pagination.last_page || 1,
          total: pagination.total,
          perPage: pagination.per_page,
        }"
        @page-change="changePage"
        @per-page-change="changePerPage"
      >
        <!-- Course Name -->
        <template #cell-name="{ item }">
          <div>
            <p class="font-semibold text-main-text text-sm">{{ localize(item.name) }}</p>
            <p v-if="item.description" class="text-xs text-main-text/40 mt-0.5 truncate max-w-[220px]">{{ item.description }}</p>
          </div>
        </template>

        <!-- Code -->
        <template #cell-code="{ item }">
          <span class="font-mono text-sm text-main-text/70 bg-card-hover px-2 py-0.5 rounded-md">{{ item.code }}</span>
        </template>

        <!-- Credits -->
        <template #cell-credit_hours="{ item }">
          <span class="text-sm text-main-text/70">{{ item.credit_hours }}</span>
        </template>

        <!-- Offerings -->
        <template #cell-offerings="{ item }">
          <div class="flex flex-wrap gap-1 max-w-[220px]">
            <span
              v-for="offering in item.offerings"
              :key="offering.id"
              class="inline-flex items-center text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap"
              :class="offering.is_active
                ? 'bg-brand-blue/10 text-brand-blue ring-1 ring-brand-blue/20'
                : 'bg-main-text/5 text-main-text/40 ring-1 ring-card-border'"
            >
              {{ formatGrade(offering.senbet_class) }} · S{{ offering.semester }}
            </span>
            <span v-if="!item.offerings?.length" class="text-xs text-main-text/30 italic">{{ t('course.no_offerings') }}</span>
          </div>
        </template>

        <!-- Assigned Teacher -->
        <template #cell-teachers="{ item }">
          <div class="flex flex-wrap gap-1 items-center">
            <template v-for="offering in item.offerings" :key="'t-' + offering.id">
              <span
                v-for="teacher in offering.teachers"
                :key="teacher.id"
                :title="`${langStore.localize(teacher.name)} (${formatGrade(offering.senbet_class)})`"
                class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-blue/10 text-brand-blue text-xs font-semibold border-2 border-card-bg shrink-0"
              >
                {{ initials(teacher.name) }}
              </span>
            </template>
            <span
              v-if="item.offerings?.every((o: any) => !o.teachers?.length)"
              class="inline-flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400"
            >
              <AlertCircle class="w-3 h-3" /> {{ t('course.unassigned') }}
            </span>
          </div>
        </template>

        <!-- Status -->
        <template #cell-status="{ item }">
          <span
            class="badge text-xs"
            :class="item.is_active
              ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
              : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'"
          >
            <span class="w-1.5 h-1.5 rounded-full inline-block mr-1" :class="item.is_active ? 'bg-emerald-500' : 'bg-rose-500'" />
            {{ item.is_active ? t('course.active') : t('course.inactive') }}
          </span>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ item }">
          <ActionDropdown :item="item" :actions="getRowActions(item)" />
        </template>
      </DataTable>

    </main>

    <!-- ── Create / Edit Course Modal ── -->
    <Modal
      :show="showCourseModal"
      :title="editingCourse ? t('course.edit') : t('course.new')"
      :icon="BookOpen"
      icon-class="text-brand-blue"
      badge-class="bg-brand-blue/10"
      size="md"
      @close="closeCourseModal"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
        <!-- Course Name -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">
            {{ t('course.name_required') }}
          </label>
          <input v-model="form.name" type="text" :placeholder="t('course.name_placeholder')" class="premium-input w-full" />
          <p v-if="errors.name" class="mt-1 text-xs text-rose-500">{{ errors.name[0] }}</p>
        </div>

        <!-- Course Code -->
        <div>
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">
            {{ t('course.code_required') }}
          </label>
          <input v-model="form.code" type="text" :placeholder="t('course.course_code_placeholder')" class="premium-input w-full font-mono" />
          <p v-if="errors.code" class="mt-1 text-xs text-rose-500">{{ errors.code[0] }}</p>
        </div>

        <!-- Credit Hours -->
        <div>
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">
            {{ t('course.credit_hours_required') }}
          </label>
          <input v-model.number="form.credit_hours" type="number" min="1" max="10" class="premium-input w-full" />
          <p v-if="errors.credit_hours" class="mt-1 text-xs text-rose-500">{{ errors.credit_hours[0] }}</p>
        </div>

        <!-- Semester -->
        <div>
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">{{ t('course.semester_required') }}</label>
          <select v-model="form.semester" class="premium-input w-full">
            <option value="1">{{ t('course.semester_1') }}</option>
            <option value="2">{{ t('course.semester_2') }}</option>
          </select>
        </div>

        <!-- Active Toggle -->
        <div class="flex items-center gap-3 self-end pb-1">
          <button
            type="button"
            @click="form.is_active = !form.is_active"
            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none shrink-0"
            :class="form.is_active ? 'bg-brand-blue' : 'bg-card-border'"
          >
            <span
              class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow"
              :class="form.is_active ? 'translate-x-4' : 'translate-x-0.5'"
            />
          </button>
          <span class="text-sm text-main-text/70">{{ t('course.active') }}</span>
        </div>

        <!-- Grade Levels -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">{{ t('course.grade_levels') }}</label>
          <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5 p-3 border border-card-border rounded-xl bg-card-hover/40 max-h-36 overflow-y-auto custom-scrollbar">
            <label
              v-for="g in gradeLevelOptions"
              :key="g.value"
              class="flex items-center gap-1.5 text-xs cursor-pointer group"
            >
              <input
                type="checkbox"
                :value="g.value"
                v-model="form.grade_levels"
                class="rounded border-card-border text-brand-blue focus:ring-brand-blue/30 h-3.5 w-3.5 shrink-0"
              />
              <span class="text-main-text/70 group-hover:text-main-text transition-colors">{{ g.label }}</span>
            </label>
          </div>
          <p class="mt-1 text-[11px] text-main-text/40">{{ t('course.grade_levels_hint') }}</p>
        </div>

        <!-- Prerequisites -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">{{ t('course.prerequisites') }}</label>
          <div class="flex gap-2">
            <input
              v-model="prerequisiteInput"
              @keydown.enter.prevent="addPrerequisite"
              type="text"
              :placeholder="t('course.prerequisites_placeholder')"
              class="premium-input flex-1"
            />
            <button
              type="button"
              @click="addPrerequisite"
              class="px-3 h-11 bg-card-hover border border-card-border rounded-xl text-sm text-main-text/60 hover:text-brand-blue hover:border-brand-blue/30 transition-all shrink-0"
            >{{ t('common.add') || 'Add' }}</button>
          </div>
          <div class="flex flex-wrap gap-1.5 mt-2">
            <span
              v-for="(p, idx) in form.prerequisites"
              :key="idx"
              class="inline-flex items-center gap-1 text-xs bg-brand-blue/10 text-brand-blue px-2.5 py-1 rounded-full font-medium"
            >
              {{ p }}
              <button @click="form.prerequisites.splice(idx, 1)" class="text-brand-blue/50 hover:text-rose-500 ml-0.5 leading-none">×</button>
            </span>
          </div>
        </div>

        <!-- Description -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-main-text/50 uppercase tracking-wider mb-1.5">{{ t('course.description') }}</label>
          <textarea v-model="form.description" rows="3" :placeholder="t('course.description_placeholder')" class="premium-input w-full resize-none" />
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-card-border/40 bg-card-bg">
          <button
            @click="closeCourseModal"
            class="px-4 h-11 rounded-xl border border-card-border/60 text-main-text/60 hover:text-main-text hover:bg-card-hover text-sm font-bold transition-all active:scale-95"
          >{{ t('common.cancel') }}</button>
          <button
            @click="saveCourse"
            :disabled="saving"
            class="flex items-center gap-2 px-6 h-11 bg-brand-blue hover:bg-brand-blue-dark text-white rounded-xl text-sm font-bold transition-all active:scale-95 hover:-translate-y-0.5 disabled:opacity-50 disabled:pointer-events-none shadow-md shadow-brand-blue/20"
          >
            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
            {{ saving ? t('course.saving') : (editingCourse ? t('course.update') : t('course.create')) }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- ── Delete Confirm Modal ── -->
    <Modal
      :show="!!deletingCourse"
      :title="t('course.delete')"
      :icon="Trash2"
      icon-class="text-rose-500"
      badge-class="bg-rose-500/10"
      size="confirm"
      @close="deletingCourse = null"
    >
      <div class="py-2 space-y-4">
        <p class="text-sm text-main-text/70 leading-relaxed">
          {{ t('course.delete_desc', { name: localize(deletingCourse?.name) }) }}
        </p>
        <div class="p-3 rounded-xl bg-rose-500/5 border border-rose-500/20 text-xs text-rose-600 dark:text-rose-400">
          {{ t('course.delete_irreversible') }}
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-card-border/40 bg-card-bg">
          <button
            @click="deletingCourse = null"
            class="px-4 h-11 rounded-xl border border-card-border/60 text-main-text/60 hover:text-main-text hover:bg-card-hover text-sm font-bold transition-all active:scale-95"
          >{{ t('common.cancel') }}</button>
          <button
            @click="confirmDelete"
            :disabled="deleting"
            class="flex items-center gap-2 px-6 h-11 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-bold transition-all active:scale-95 hover:-translate-y-0.5 disabled:opacity-50 disabled:pointer-events-none shadow-md shadow-rose-500/20"
          >
            <Loader2 v-if="deleting" class="w-4 h-4 animate-spin" />
            {{ deleting ? t('course.deleting') : t('course.confirm_delete') }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Teacher Assignment Modal -->
    <TeacherAssignmentModal
      v-if="showTeacherModal && selectedCourse"
      :course="selectedCourse"
      @close="closeTeacherModal"
      @saved="fetchCourses"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import {
  GraduationCap, BookOpen, Pencil, Trash2, Loader2, AlertCircle, UserCheck,
} from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import { useLanguageStore } from '@/stores/languageStore';

import TableToolbar   from '@/components/common/TableToolbar.vue';
import DataTable      from '@/components/common/DataTable.vue';
import ActionDropdown from '@/components/common/ActionDropdown.vue';
import FormSelect     from '@/components/common/FormSelect.vue';
import Modal          from '@/components/common/Modal.vue';
import TeacherAssignmentModal from '@/components/academic/TeacherAssignmentModal.vue';

// ─── i18n ────────────────────────────────────────────────────────────────────

const langStore = useLanguageStore();
const t = (k: string, p?: any) => langStore.translate(k, p);

// ─── State ────────────────────────────────────────────────────────────────────

const courses     = ref<any[]>([]);
const loading     = ref(true);
const saving      = ref(false);
const deleting    = ref(false);
const errors      = ref<Record<string, string[]>>({});

const search         = ref('');
const showFilters    = ref(false);
const filterStatus   = ref('');
const filterSemester = ref('');

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
});

const showCourseModal   = ref(false);
const showTeacherModal  = ref(false);
const editingCourse     = ref<any>(null);
const deletingCourse    = ref<any>(null);
const selectedCourse    = ref<any>(null);
const prerequisiteInput = ref('');
const form = ref(defaultForm());

// ─── Grade Level Options ──────────────────────────────────────────────────────

const gradeLevelOptions = [
  { value: 'child',   label: 'KG' },
  ...Array.from({ length: 12 }, (_, i) => ({ value: String(i + 1), label: `G${i + 1}` })),
  { value: 'post_12', label: 'P12' },
];

// ─── Column Definitions ───────────────────────────────────────────────────────

const columns = [
  { key: 'name',         label: t('course.name'),         width: '220px', sortable: false },
  { key: 'code',         label: t('course.code'),         width: '110px', sortable: false },
  { key: 'credit_hours', label: t('course.credits'),      width: '80px',  sortable: false },
  { key: 'offerings',   label: t('course.offerings'),     width: '220px', sortable: false },
  { key: 'teachers',    label: t('course.teachers'),      width: '140px', sortable: false },
  { key: 'status',      label: t('course.status'),        width: '100px', sortable: false },
  { key: 'actions',     label: '',                        width: '60px',  align: 'right' as const },
];

// ─── Row Actions ──────────────────────────────────────────────────────────────

const getRowActions = (item: any) => [
  {
    label:      t('course.assign_teachers'),
    icon:       UserCheck,
    colorClass: 'text-brand-blue',
    onClick:    (row: any) => openTeacherModal(row),
  },
  {
    label:      t('course.edit'),
    icon:       Pencil,
    onClick:    (row: any) => openEditModal(row),
  },
  {
    label:      t('course.delete'),
    icon:       Trash2,
    colorClass: 'text-rose-500',
    onClick:    (row: any) => { deletingCourse.value = row; },
  },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

function defaultForm() {
  return {
    name:          '',
    code:          '',
    credit_hours:  2,
    semester:      '1',
    grade_levels:  [] as string[],
    prerequisites: [] as string[],
    description:   '',
    is_active:     true,
  };
}

const localize = (val: any) => langStore.localize(val);

const formatGrade = (val: string) => {
  if (!val) return '';
  if (val === 'child')   return 'KG';
  if (val === 'post_12') return 'Post-12';
  return `G${val}`;
};

const initials = (name: any) => {
  const n = localize(name);
  return n.split(' ').map((p: string) => p[0]).join('').substring(0, 2).toUpperCase();
};

let debounceTimer: ReturnType<typeof setTimeout>;
const onSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchCourses(true), 350);
};

const clearFilters = () => {
  search.value         = '';
  filterStatus.value   = '';
  filterSemester.value = '';
  fetchCourses(true);
};

// ─── Data Fetching ────────────────────────────────────────────────────────────

const fetchCourses = async (resetPage = false) => {
  if (resetPage) pagination.value.current_page = 1;
  loading.value = true;
  try {
    const params: Record<string, any> = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    };
    if (search.value.trim())  params.search    = search.value.trim();
    if (filterStatus.value)   params.is_active = filterStatus.value;
    if (filterSemester.value) params.semester  = filterSemester.value;

    const res = await apiClient.get('/academic/courses', { params });
    const raw = res.data?.data ?? res.data;

    if (Array.isArray(raw)) {
      courses.value = raw;
      pagination.value = { current_page: 1, last_page: 1, total: raw.length, per_page: raw.length || 10 };
    } else if (raw && Array.isArray(raw.data)) {
      courses.value = raw.data;
      pagination.value = {
        current_page: raw.current_page || 1,
        last_page: raw.last_page || 1,
        total: raw.total || 0,
        per_page: raw.per_page || 10,
      };
    } else {
      courses.value = [];
    }
  } catch (err) {
    console.error('Failed to fetch courses', err);
  } finally {
    loading.value = false;
  }
};

const changePage    = (p: number) => { pagination.value.current_page = p; fetchCourses(); };
const changePerPage = (s: number) => { pagination.value.per_page = s; fetchCourses(true); };

// ─── Modal Controls ───────────────────────────────────────────────────────────

const openCreateModal = () => { editingCourse.value = null; form.value = defaultForm(); errors.value = {}; showCourseModal.value = true; };

const openEditModal = (course: any) => {
  editingCourse.value = course;
  form.value = {
    name:          localize(course.name),
    code:          course.code,
    credit_hours:  course.credit_hours,
    semester:      String(course.semester),
    grade_levels:  course.offerings?.map((o: any) => o.senbet_class) ?? [],
    prerequisites: course.prerequisites ?? [],
    description:   course.description ?? '',
    is_active:     course.is_active,
  };
  errors.value = {};
  showCourseModal.value = true;
};

const closeCourseModal  = () => { showCourseModal.value = false; editingCourse.value = null; };
const openTeacherModal  = (course: any) => { selectedCourse.value = course; showTeacherModal.value = true; };
const closeTeacherModal = () => { showTeacherModal.value = false; selectedCourse.value = null; };

// ─── Form Actions ─────────────────────────────────────────────────────────────

const addPrerequisite = () => {
  const val = prerequisiteInput.value.trim().toUpperCase();
  if (val && !form.value.prerequisites.includes(val)) form.value.prerequisites.push(val);
  prerequisiteInput.value = '';
};

const saveCourse = async () => {
  saving.value = true; errors.value = {};
  try {
    if (editingCourse.value) {
      await apiClient.put(`/academic/courses/${editingCourse.value.id}`, form.value);
    } else {
      await apiClient.post('/academic/courses', form.value);
    }
    closeCourseModal();
    fetchCourses();
  } catch (err: any) {
    if (err.response?.data?.errors) errors.value = err.response.data.errors;
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async () => {
  if (!deletingCourse.value) return;
  deleting.value = true;
  try {
    await apiClient.delete(`/academic/courses/${deletingCourse.value.id}`);
    deletingCourse.value = null;
    fetchCourses();
  } catch (_) {} finally {
    deleting.value = false;
  }
};

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(fetchCourses);
</script>
