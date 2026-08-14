<template>
  <Modal
    :show="true"
    :title="t('course.assign_teachers') + ' — ' + localize(course.name)"
    :icon="UserCheck"
    icon-class="text-brand-blue"
    badge-class="bg-brand-blue/10"
    size="lg"
    @close="$emit('close')"
  >
    <!-- ── Body ── -->
    <div class="space-y-2 py-1">

      <!-- Loading skeleton -->
      <div v-if="loadingOfferings" class="space-y-4 py-4">
        <div v-for="i in 3" :key="i" class="rounded-2xl border border-card-border/60 overflow-hidden">
          <div class="h-14 skeleton rounded-none" />
          <div class="p-4 space-y-2">
            <div class="skeleton h-4 w-1/3 rounded-full" />
            <div class="skeleton h-10 w-full rounded-xl" />
          </div>
        </div>
      </div>

      <!-- No offerings state -->
      <div v-else-if="offerings.length === 0" class="flex flex-col items-center justify-center py-16 gap-3">
        <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center">
          <AlertCircle class="w-7 h-7 text-amber-500" />
        </div>
        <h4 class="text-sm font-semibold text-main-text">{{ t('course.no_offerings_title') }}</h4>
        <p class="text-xs text-main-text/50 text-center max-w-xs">
          {{ t('course.no_offerings_desc') }}
        </p>
      </div>

      <!-- Offering cards -->
      <div v-else class="space-y-4">

        <!-- Course info banner -->
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-blue/5 border border-brand-blue/15">
          <div class="w-8 h-8 rounded-lg bg-brand-blue/10 flex items-center justify-center shrink-0">
            <BookOpen class="w-4 h-4 text-brand-blue" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-main-text truncate">{{ localize(course.name) }}</p>
            <p class="text-xs text-main-text/50">{{ t('course.teacher_modal_subtitle', { name: offerings.length + ' ' + (offerings.length !== 1 ? t('course.offerings_count_plural', { count: '' }).replace(':count', '').trim() : t('course.offerings_count', { count: '' }).replace(':count', '').trim()) }) }}</p>
          </div>
          <span class="ml-auto shrink-0 text-xs font-mono bg-card-hover px-2 py-1 rounded-lg text-main-text/60 border border-card-border">{{ course.code }}</span>
        </div>

        <!-- Individual offering panels -->
        <div
          v-for="offering in offerings"
          :key="offering.id"
          class="rounded-2xl border border-card-border/60 overflow-hidden transition-all hover:border-brand-blue/30 hover:shadow-sm"
        >
          <!-- Offering Header -->
          <div class="flex items-center gap-3 px-4 py-3 bg-card-hover/60 border-b border-card-border/40">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-brand-blue/10 text-brand-blue ring-1 ring-brand-blue/20">
              <GraduationCap class="w-3 h-3" />
              {{ formatGrade(offering.senbet_class) }}
            </span>
            <span class="text-xs text-main-text/50 font-medium">Semester {{ offering.semester }}</span>
            <div class="ml-auto flex items-center gap-2">
              <span class="inline-flex items-center gap-1 text-xs text-main-text/40">
                <Users class="w-3.5 h-3.5" />
                {{ offering.students_count ?? 0 }} students
              </span>
              <span
                class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                :class="offering.is_active
                  ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                  : 'bg-rose-500/10 text-rose-500'"
              >
                {{ offering.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>

          <!-- Assigned Teacher Chip -->
          <div class="px-4 pt-3 pb-2">
            <p class="text-[10px] font-bold text-main-text/40 uppercase tracking-wider mb-2">Assigned Teacher</p>
            <div class="flex flex-wrap gap-2 min-h-[2.25rem] items-center">
              <div
                v-if="offeringTeacher[offering.id]"
                class="group inline-flex items-center gap-2 text-xs bg-brand-blue/8 dark:bg-brand-blue/10 text-brand-blue pl-1 pr-2 py-1 rounded-full border border-brand-blue/20 transition-all hover:border-rose-400/40 hover:bg-rose-50 dark:hover:bg-rose-900/10"
              >
                <span class="w-5 h-5 flex items-center justify-center rounded-full bg-brand-blue/15 text-[10px] font-bold shrink-0 group-hover:bg-rose-200 dark:group-hover:bg-rose-800 transition-colors">
                  {{ initials(offeringTeacher[offering.id].name) }}
                </span>
                <span class="font-medium group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">{{ localize(offeringTeacher[offering.id].name) }}</span>
                <button
                  @click="removeTeacher(offering.id)"
                  class="text-brand-blue/50 hover:text-rose-500 transition-colors ml-0.5 rounded-full"
                  title="Remove"
                >
                  <X class="w-3 h-3" />
                </button>
              </div>
              <span v-else class="text-xs text-main-text/30 italic">
                {{ t('course.no_teachers') }}
              </span>
            </div>
          </div>

          <!-- Search & Add Teacher -->
          <div v-if="!offeringTeacher[offering.id]" class="px-4 pb-4 pt-2">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-main-text/30 pointer-events-none" />
              <input
                v-model="searchQueries[offering.id]"
                @input="searchTeachers(offering.id)"
                @blur="scheduleClose(offering.id)"
                @focus="searchTeachers(offering.id)"
                type="text"
                :placeholder="t('course.search_teachers')"
                class="w-full pl-9 pr-3 h-10 text-sm premium-input"
              />
            </div>

            <!-- Search Results -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-1"
            >
              <div
                v-if="searchResults[offering.id]?.length"
                class="mt-1.5 border border-card-border rounded-xl bg-card-bg shadow-xl shadow-black/10 overflow-hidden"
              >
                <button
                  v-for="teacher in searchResults[offering.id]"
                  :key="teacher.id"
                  @mousedown.prevent="addTeacher(offering.id, teacher)"
                  class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-main-text hover:bg-brand-blue/5 hover:text-brand-blue transition-colors text-left border-b border-card-border/30 last:border-0"
                >
                  <span class="w-7 h-7 flex items-center justify-center rounded-full bg-card-hover text-[11px] font-bold text-main-text/60 shrink-0 border border-card-border">
                    {{ initials(teacher.name) }}
                  </span>
                  <div class="min-w-0 flex-1">
                    <div class="font-medium truncate text-main-text">{{ localize(teacher.name) }}</div>
                    <div class="text-[11px] text-main-text/40 truncate">{{ teacher.email }}</div>
                  </div>
                  <div class="shrink-0 flex items-center gap-1 text-xs text-brand-blue font-semibold opacity-0 group-hover:opacity-100">
                    <UserPlus class="w-3.5 h-3.5" />
                  </div>
                </button>
              </div>
            </Transition>

            <!-- Empty search result hint -->
            <p
              v-if="searchQueries[offering.id]?.trim() && !searchResults[offering.id]?.length"
              class="text-xs text-main-text/40 mt-1.5 pl-1"
            >
              {{ t('course.no_teacher_match') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Footer ── -->
    <template #footer>
      <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-card-border/40 bg-card-bg">
        <!-- Assignment summary -->
        <div class="text-xs text-main-text/40">
          <span v-if="totalAssigned > 0" class="text-brand-blue font-semibold">{{ t(totalAssigned !== 1 ? 'course.assignments_summary_plural' : 'course.assignments_summary', { count: totalAssigned, offerings: offerings.length }) }}</span>
          <span v-else>{{ t('course.no_teachers') }}</span>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="$emit('close')"
            class="px-4 h-11 rounded-xl border border-card-border/60 text-main-text/60 hover:text-main-text hover:bg-card-hover text-sm font-bold transition-all active:scale-95"
          >{{ t('common.cancel') }}</button>
          <button
            @click="saveAll"
            :disabled="saving || loadingOfferings || offerings.length === 0"
            class="flex items-center gap-2 px-6 h-11 bg-brand-blue hover:bg-brand-blue-dark text-white rounded-xl text-sm font-bold transition-all active:scale-95 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-brand-blue/20"
          >
            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
            <UserCheck v-else class="w-4 h-4" />
            {{ saving ? t('course.saving_assignments') : t('course.save_assignments') }}
          </button>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { X, Search, UserPlus, UserCheck, Loader2, AlertCircle, GraduationCap, BookOpen, Users } from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import Modal from '@/components/common/Modal.vue';
import { useLanguageStore } from '@/stores/languageStore';

const langStore = useLanguageStore();
const t = (k: string, p?: any) => langStore.translate(k, p);

// ─── Props & Emits ────────────────────────────────────────────────────────────

const props = defineProps<{ course: any }>();
const emit  = defineEmits(['close', 'saved']);

// ─── State ────────────────────────────────────────────────────────────────────

const offerings        = ref<any[]>([]);
const loadingOfferings = ref(true);
const saving           = ref(false);

// offeringId → current teacher object or null
const offeringTeacher = reactive<Record<number, any | null>>({});
// offeringId → pending teacher_ids to sync
const pendingSync      = reactive<Record<number, number[]>>({});
// offeringId → search query
const searchQueries    = reactive<Record<number, string>>({});
// offeringId → filtered results
const searchResults    = reactive<Record<number, any[]>>({});

// ─── Computed ─────────────────────────────────────────────────────────────────

const totalAssigned = computed(() =>
  Object.values(offeringTeacher).filter(t => t !== null).length
);

// ─── Helpers ──────────────────────────────────────────────────────────────────

const localize = (val: any) => langStore.localize(val);

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

// Delay close so mousedown addTeacher fires before blur
const scheduleClose = (offeringId: number) => {
  setTimeout(() => {
    searchResults[offeringId] = [];
  }, 200);
};

const extractList = (res: any): any[] => {
  if (!res) return [];
  const body = res.data !== undefined ? res.data : res;
  if (Array.isArray(body)) return body;
  if (body && Array.isArray(body.data)) return body.data;
  if (body && body.data && Array.isArray(body.data.data)) return body.data.data;
  return [];
};

// ─── Load Offerings + Teachers ────────────────────────────────────────────────

const fetchOfferings = async () => {
  loadingOfferings.value = true;
  try {
    const offeringsRes = await apiClient.get(`/academic/courses/${props.course.id}/offerings`);

    offerings.value = extractList(offeringsRes);

    offerings.value.forEach((o: any) => {
      const teachers = o.teachers ?? [];
      offeringTeacher[o.id] = teachers.length > 0 ? teachers[0] : null;
      pendingSync[o.id]      = teachers.length > 0 ? [teachers[0].id] : [];
      searchQueries[o.id]    = '';
      searchResults[o.id]    = [];
    });
  } catch (err) {
    console.error('Failed to load offering data', err);
  } finally {
    loadingOfferings.value = false;
  }
};

// ─── Search (live, teacher-role only) ────────────────────────────────────────

const searchDebounceTimers: Record<number, ReturnType<typeof setTimeout>> = {};

const searchTeachers = (offeringId: number) => {
  clearTimeout(searchDebounceTimers[offeringId]);
  const query = (searchQueries[offeringId] ?? '').trim();

  if (!query) {
    searchResults[offeringId] = [];
    return;
  }

  searchDebounceTimers[offeringId] = setTimeout(async () => {
    try {
      const res = await apiClient.get('/academic/teacher-search', {
        params: { search: query },
      });
      const list = res.data?.data ?? res.data ?? [];
      const assignedId = offeringTeacher[offeringId]?.id;
      searchResults[offeringId] = list
        .filter((t: any) => t.id !== assignedId)
        .slice(0, 7);
    } catch {
      searchResults[offeringId] = [];
    }
  }, 250);
};

// ─── Add / Remove ─────────────────────────────────────────────────────────────

const addTeacher = (offeringId: number, teacher: any) => {
  offeringTeacher[offeringId] = teacher;
  pendingSync[offeringId] = [teacher.id];
  searchQueries[offeringId] = '';
  searchResults[offeringId] = [];
};

const removeTeacher = (offeringId: number) => {
  offeringTeacher[offeringId] = null;
  pendingSync[offeringId] = [];
};

// ─── Save ─────────────────────────────────────────────────────────────────────

const saveAll = async () => {
  saving.value = true;
  try {
    await Promise.all(
      offerings.value.map((o: any) =>
        apiClient.post(`/academic/offerings/${o.id}/teachers/sync`, {
          teacher_ids: pendingSync[o.id] ?? [],
        })
      )
    );
    emit('saved');
    emit('close');
  } catch (err) {
    console.error('Failed to save teacher assignments', err);
  } finally {
    saving.value = false;
  }
};

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(fetchOfferings);
</script>
