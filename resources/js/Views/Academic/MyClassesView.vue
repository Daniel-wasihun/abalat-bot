<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-main-text">{{ $tr('my_courses.title') }}</h1>
        <p class="text-sm mt-1 text-main-text/60">{{ $tr('my_courses.subtitle') }}</p>
      </div>
    </div>
    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="i in 3" :key="i"
        class="bg-card-bg rounded-xl border border-card-border p-6 space-y-4 animate-pulse">
        <div class="flex justify-between items-center">
          <div class="skeleton h-5 w-20 rounded-full"></div>
          <div class="skeleton h-4 w-12 rounded"></div>
        </div>
        <div class="skeleton h-6 w-3/4 rounded mt-2"></div>
        <div class="skeleton h-4 w-1/3 rounded"></div>
        <div class="skeleton h-px w-full rounded mt-4"></div>
        <div class="skeleton h-4 w-1/2 rounded"></div>
      </div>
    </div>

    <!-- Empty -->
    <div
      v-else-if="offerings.length === 0"
      class="flex flex-col items-center justify-center py-20 bg-card-bg rounded-xl shadow-sm border border-card-border"
    >
      <BookOpen class="h-12 w-12 text-main-text/20 mb-4" />
      <h3 class="text-base font-semibold text-main-text">{{ $tr('my_courses.no_courses') }}</h3>
      <p class="mt-1 text-sm text-main-text/50 text-center max-w-xs">
        <template v-if="isTeacher">{{ $tr('my_courses.no_courses_teacher') }}</template>
        <template v-else>{{ $tr('my_courses.no_courses_student') }}</template>
      </p>
    </div>

    <!-- Cards -->
    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="offering in offerings"
        :key="offering.id"
        @click="handleCardClick(offering)"
        class="group bg-card-bg rounded-xl shadow-sm border border-card-border p-6 hover:shadow-md transition-shadow cursor-pointer flex flex-col gap-4"
      >
        <!-- Top row: class badge + semester -->
        <div class="flex items-center justify-between">
          <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-blue text-white">
            {{ formatGrade(offering.senbet_class) }}
          </span>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-500 text-white shadow-sm">{{ offering.academic_year || '2026/2027' }}</span>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-600 text-white shadow-sm">
              Sem {{ offering.semester }}
            </span>
          </div>
        </div>

        <!-- Course name + code -->
        <div>
          <h3 class="text-lg font-semibold text-main-text truncate">
            {{ localize(offering.course?.name) }}
          </h3>
          <p class="mt-0.5 text-sm font-mono text-gray-500 dark:text-gray-400">{{ offering.course?.code }}</p>
          <p v-if="offering.course?.description" class="mt-1 text-xs text-gray-400 line-clamp-2">{{ offering.course?.description }}</p>
        </div>

        <!-- Stats row -->
        <div class="flex items-center gap-4 text-sm text-main-text/50">
          <div v-if="isTeacher" class="flex items-center gap-1.5">
            <Users class="w-4 h-4" />
            <span>{{ offering.students_count ?? 0 }} {{ $tr('my_courses.students') }}</span>
          </div>
          <div class="flex items-center gap-1.5">
            <BookOpen class="w-4 h-4" />
            <span>{{ offering.course?.credit_hours ?? '—' }} {{ $tr('my_courses.credits') }}</span>
          </div>
          <!-- Student: show result badge if available -->
          <div v-if="!isTeacher && studentResults[offering.id]" class="flex items-center gap-1.5 ml-auto">
            <Award class="w-4 h-4 text-emerald-500" />
            <span class="font-semibold text-emerald-600 dark:text-emerald-400">
              {{ studentResults[offering.id].total_score ?? '—' }}%
            </span>
          </div>
        </div>

        <!-- Footer -->
        <div class="pt-4 border-t border-card-border mt-auto">
          <button
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-brand-blue hover:bg-brand-blue/90 text-white font-medium text-sm transition-colors shadow-sm"
          >
            <template v-if="isTeacher">{{ $tr('my_courses.manage_class') }} <ArrowRight class="w-4 h-4" /></template>
            <template v-else>{{ $tr('my_courses.view_results') }} <ArrowRight class="w-4 h-4" /></template>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Student Result Modal ─────────────────────────────────────────────── -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="showResultModal && selectedOffering"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeResultModal" />
        <div class="relative bg-white dark:bg-card-bg rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <!-- Modal Header -->
          <div class="flex items-center gap-3 px-6 py-5 border-b border-card-border">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
              <Award class="w-5 h-5 text-blue-600" />
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="text-base font-bold text-main-text truncate">
                {{ localize(selectedOffering.course?.name) }}
              </h2>
              <p class="text-xs font-medium text-main-text/60">
                {{ formatGrade(selectedOffering.senbet_class) }} · Semester {{ selectedOffering.semester }}
              </p>
            </div>
            <button @click="closeResultModal" class="text-main-text/30 hover:text-main-text transition-colors">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-5 space-y-4">
            <div v-if="resultLoading" class="space-y-3">
              <div class="skeleton h-16 w-full rounded-xl" />
              <div class="skeleton h-12 w-full rounded-xl" />
              <div class="skeleton h-12 w-full rounded-xl" />
            </div>

            <div v-else-if="!currentResult" class="flex flex-col items-center py-8 gap-3 text-center">
              <div class="w-14 h-14 rounded-2xl bg-card-bg border border-card-border flex items-center justify-center">
                <BookOpen class="w-7 h-7 text-main-text/30" />
              </div>
              <p class="text-sm font-medium text-main-text">{{ $tr('my_courses.no_results_yet') }}</p>
              <p class="text-xs text-main-text/40">{{ $tr('my_courses.no_results_desc') }}</p>
            </div>

            <div v-else class="space-y-3">
              <!-- Overall score card -->
              <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/10 border border-blue-200 dark:border-blue-700/40">
                <div>
                  <p class="text-xs font-semibold text-blue-600 capitalize tracking-wider">{{ $tr('my_courses.overall_score') }}</p>
                  <p class="text-3xl font-bold text-blue-700 dark:text-blue-300 mt-0.5">
                    {{ currentResult.total_score ?? '—' }}<span class="text-lg text-blue-500">%</span>
                  </p>
                </div>
                <div class="text-right">
                  <div
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold"
                    :class="gradeClass(currentResult.grade)"
                  >
                    {{ currentResult.grade ?? '—' }}
                  </div>
                  <p class="text-xs text-main-text/50 mt-1">
                    {{ currentResult.is_finalized ? $tr('my_courses.finalized') : $tr('my_courses.pending_review') }}
                  </p>
                </div>
              </div>

              <!-- Component breakdown -->
              <div v-if="currentResult.components && Object.keys(currentResult.components).length > 0" class="space-y-2">
                <p class="text-xs font-semibold text-main-text/40 capitalize tracking-wider">{{ $tr('my_courses.breakdown') }}</p>
                <div
                  v-for="(score, comp) in currentResult.components"
                  :key="comp"
                  class="flex items-center justify-between px-4 py-3 rounded-xl bg-card-bg border border-card-border"
                >
                  <span class="text-sm font-medium text-main-text capitalize">{{ comp }}</span>
                  <span class="text-sm font-bold text-main-text">{{ score ?? '—' }}%</span>
                </div>
              </div>

              <!-- Remarks -->
              <div v-if="currentResult.remarks" class="px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-700/30">
                <p class="text-xs font-semibold text-amber-600 capitalize tracking-wider mb-1">{{ $tr('my_courses.remarks') }}</p>
                <p class="text-sm text-main-text/70">{{ currentResult.remarks }}</p>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex justify-end px-6 py-4 border-t border-card-border">
            <button
              @click="closeResultModal"
              class="px-6 h-10 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition-all active:scale-95"
            >
              {{ $tr('my_courses.close') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { BookOpen, Users, ArrowRight, Award, X } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import apiClient from '@/api/apiClient';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();
const router    = useRouter();

// ─── State ────────────────────────────────────────────────────────────────────

const offerings       = ref<any[]>([]);
const loading         = ref(true);
const showResultModal = ref(false);
const selectedOffering = ref<any>(null);
const currentResult   = ref<any>(null);
const resultLoading   = ref(false);
const studentResults  = ref<Record<number, any>>({});

// ─── Computed ─────────────────────────────────────────────────────────────────

const isTeacher = computed(() => authStore.isTeacher);

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

const gradeClass = (grade: string) => {
  if (!grade) return 'bg-gray-100 text-gray-600';
  const g = grade.toUpperCase();
  if (['A+', 'A', 'A-'].includes(g)) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (['B+', 'B', 'B-'].includes(g)) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
  if (['C+', 'C', 'C-'].includes(g)) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
  if (['D+', 'D'].includes(g))        return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
  return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
};

// ─── Data Fetch ───────────────────────────────────────────────────────────────

const fetchMyOfferings = async () => {
  loading.value = true;
  try {
    // isTeacher may not be ready until /me resolves, so wait for user
    await authStore.fetchUser().catch(() => {});

    const endpoint = authStore.isTeacher
      ? '/academic/my-offerings'
      : '/academic/my-student-courses';

    const res = await apiClient.get(endpoint);
    const raw = res.data.data ?? res.data;
    offerings.value = Array.isArray(raw) ? raw.filter((o: any) => o?.course) : [];
  } catch (err) {
    console.error('Failed to fetch my courses', err);
  } finally {
    loading.value = false;
  }
};

// ─── Navigation & Modal ───────────────────────────────────────────────────────

const handleCardClick = (offering: any) => {
  if (isTeacher.value) {
    router.push(`/dashboard/academic/offerings/${offering.id}`);
  } else {
    openResultModal(offering);
  }
};

const openResultModal = async (offering: any) => {
  selectedOffering.value = offering;
  showResultModal.value  = true;
  currentResult.value    = null;
  resultLoading.value    = true;

  try {
    const res = await apiClient.get(`/academic/offerings/${offering.id}/my-result`);
    currentResult.value = res.data.result ?? null;

    // Cache for badge display
    if (currentResult.value) {
      studentResults.value[offering.id] = currentResult.value;
    }
  } catch (err) {
    console.error('Failed to fetch result', err);
  } finally {
    resultLoading.value = false;
  }
};

const closeResultModal = () => {
  showResultModal.value  = false;
  selectedOffering.value = null;
  currentResult.value    = null;
};

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(fetchMyOfferings);
</script>
