<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-x-full"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-full"
    >
      <div class="fixed inset-0 z-50 flex justify-end">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>

        <!-- Slide-over panel -->
        <div class="relative w-full max-w-md bg-white dark:bg-card-bg shadow-2xl border-l border-gray-200 dark:border-card-border flex flex-col max-h-screen">

          <!-- Header -->
          <div class="flex items-start justify-between px-6 py-5 border-b border-gray-200 dark:border-card-border shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white text-sm font-bold shrink-0 overflow-hidden">
                <img v-if="student.profile_picture" :src="getProfileUrl(student.profile_picture)" class="w-full h-full object-cover" />
                <span v-else>{{ initials(student.name) }}</span>
              </div>
              <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ localize(student.name) }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ student.registration_id ?? student.email }}</p>
              </div>
            </div>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors mt-0.5">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Body -->
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-6">

            <!-- Finalized notice -->
            <div v-if="isFinalized" class="flex items-center gap-2.5 text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 rounded-xl ring-1 ring-amber-200 dark:ring-amber-700">
              <Lock class="w-4 h-4 shrink-0" />
              Results for this offering have been finalized and are read-only.
            </div>

            <!-- Score inputs -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Assessment Scores</h3>

              <div v-for="comp in componentInfo" :key="comp.key" class="space-y-1">
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ comp.label }}</label>
                  <span class="text-xs text-gray-400">max {{ comp.max }}</span>
                </div>
                <div class="relative">
                  <input
                    type="number"
                    :min="0"
                    :max="comp.max"
                    step="0.5"
                    :disabled="isFinalized"
                    v-model.number="localForm.scores[comp.key]"
                    @input="recompute"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    :class="{ 'border-red-300 focus:ring-red-500': localForm.scores[comp.key] != null && localForm.scores[comp.key] > comp.max }"
                    :placeholder="`Enter score (0–${comp.max})`"
                  />
                  <span v-if="localForm.scores[comp.key] != null && localForm.scores[comp.key] > comp.max"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-red-500">
                    Max {{ comp.max }}
                  </span>
                </div>
              </div>

              <!-- Remarks -->
              <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                <textarea
                  v-model="localForm.remarks"
                  :disabled="isFinalized"
                  rows="2"
                  placeholder="Optional notes about this student's performance…"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 resize-none transition-colors"
                ></textarea>
              </div>

              <!-- Change reason -->
              <div v-if="hasExistingResult && !isFinalized" class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Change Reason <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input
                  v-model="localForm.change_reason"
                  type="text"
                  placeholder="Why is this being changed?"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                />
              </div>
            </div>

            <!-- Computed result preview -->
            <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 p-4 space-y-3">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Computed Result</h3>
              <div class="flex items-center justify-between">
                <div class="text-center">
                  <p class="text-2xl font-bold" :class="computedTotal != null ? (computedTotal >= 60 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400') : 'text-gray-300 dark:text-gray-600'">
                    {{ computedTotal != null ? computedTotal.toFixed(1) : '—' }}
                  </p>
                  <p class="text-xs text-gray-400 mt-0.5">Total / 100</p>
                </div>
                <div class="text-center">
                  <span class="inline-flex items-center justify-center w-14 h-14 rounded-full text-2xl font-bold border-2"
                    :class="computedGrade ? gradeColor(computedGrade) + ' border-current' : 'text-gray-300 dark:text-gray-600 border-gray-200 dark:border-gray-600'">
                    {{ computedGrade ?? '—' }}
                  </span>
                  <p class="text-xs text-gray-400 mt-1">Letter Grade</p>
                </div>
                <div class="text-center">
                  <p class="text-sm font-semibold" :class="computedTotal != null ? (computedTotal >= 60 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400') : 'text-gray-400'">
                    {{ computedTotal != null ? (computedTotal >= 60 ? 'Pass' : 'Fail') : '—' }}
                  </p>
                  <p class="text-xs text-gray-400 mt-0.5">Status</p>
                </div>
              </div>
            </div>

            <!-- Edit history -->
            <div v-if="history.length" class="space-y-3">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Edit History</h3>
              <div class="space-y-2 max-h-52 overflow-y-auto">
                <div v-for="entry in history" :key="entry.id"
                  class="text-xs bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2.5 space-y-1">
                  <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ entry.changed_by }}</span>
                    <span class="text-gray-400">{{ formatDateTime(entry.created_at) }}</span>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="(val, key) in entry.new_values" :key="key"
                      class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                      <span class="font-medium">{{ friendlyKey(String(key)) }}</span>
                      <span class="text-gray-400">{{ entry.old_values?.[key] ?? '—' }} → {{ val }}</span>
                    </span>
                  </div>
                  <p v-if="entry.change_reason" class="text-gray-400 italic">{{ entry.change_reason }}</p>
                </div>
              </div>
            </div>

          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-card-border shrink-0">
            <button @click="loadHistory" :disabled="historyLoading" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex items-center gap-1 transition-colors disabled:opacity-50">
              <History class="w-3.5 h-3.5" />
              {{ historyLoading ? 'Loading…' : (history.length ? 'Refresh History' : 'Load History') }}
            </button>
            <div class="flex items-center gap-3">
              <button @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                Cancel
              </button>
              <button
                v-if="!isFinalized"
                @click="saveResult"
                :disabled="saving || hasValidationErrors"
                class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center gap-2"
              >
                <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                {{ saving ? 'Saving…' : 'Save Result' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { X, Lock, Loader2, History } from 'lucide-vue-next';
import apiClient from '@/api/apiClient';

// ─── Props & Emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  offeringId:    number;
  student:       any;
  result?:       any;
  componentInfo: { key: string; label: string; max: number }[];
  isFinalized:   boolean;
}>();

const emit = defineEmits(['close', 'saved']);

// ─── State ────────────────────────────────────────────────────────────────────

const saving        = ref(false);
const historyLoading = ref(false);
const history       = ref<any[]>([]);

const localForm = reactive<Record<string, any>>({
  scores: Object.fromEntries(
    props.componentInfo.map(c => [c.key, props.result?.scores?.[c.key] ?? null])
  ),
  remarks:       props.result?.remarks ?? '',
  change_reason: '',
});

const hasExistingResult = computed(() => !!props.result?.id);

// ─── Computed grade preview ────────────────────────────────────────────────────

const computedTotal = computed<number | null>(() => {
  const scores = localForm.scores as Record<string, any>;
  const vals = props.componentInfo.map(c => scores[c.key]).filter(v => v !== null && v !== undefined && !isNaN(v));
  if (!vals.length) return null;
  const sum = vals.reduce((a: number, b: any) => a + parseFloat(b), 0);
  return Math.min(parseFloat(sum.toFixed(2)), 100);
});

const computedGrade = computed<string | null>(() => {
  const t = computedTotal.value;
  if (t == null) return null;
  if (t >= 90) return 'A';
  if (t >= 80) return 'B';
  if (t >= 70) return 'C';
  if (t >= 60) return 'D';
  return 'F';
});

const hasValidationErrors = computed(() =>
  props.componentInfo.some(c => localForm.scores[c.key] != null && localForm.scores[c.key] > c.max)
);

// ─── Helpers ──────────────────────────────────────────────────────────────────

const localize = (val: any) => {
  if (!val) return '';
  return typeof val === 'object' ? (val.en || val.am || val.or || '') : val;
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

const gradeColor = (grade: string | null | undefined) => {
  const map: Record<string, string> = {
    A: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    B: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    C: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    D: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    F: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  };
  return map[grade ?? ''] ?? 'bg-gray-100 text-gray-500';
};

const formatDateTime = (d: string) =>
  new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const friendlyKey = (key: string) => {
  const map: Record<string, string> = {
    quiz_ca_score:    'Quiz/CA',
    midterm_score:    'Midterm',
    final_exam_score: 'Final',
    total_score:      'Total',
    letter_grade:     'Grade',
    remarks:          'Remarks',
  };
  return map[key] ?? key;
};

const recompute = () => {}; // Reactivity handles it via computed

// ─── API Actions ──────────────────────────────────────────────────────────────

const saveResult = async () => {
  saving.value = true;
  try {
    const res = await apiClient.put(
      `/academic/offerings/${props.offeringId}/results/${props.student.id}`,
      {
        scores:        localForm.scores,
        remarks:       localForm.remarks || null,
        change_reason: localForm.change_reason || null,
      }
    );
    emit('saved', res.data.data?.result ?? res.data.result);
    emit('close');
  } catch (err: any) {
    console.error('Failed to save result', err);
    const msg = err.response?.data?.message ?? 'Failed to save. Please try again.';
    alert(msg);
  } finally {
    saving.value = false;
  }
};

const loadHistory = async () => {
  historyLoading.value = true;
  try {
    const res = await apiClient.get(
      `/academic/offerings/${props.offeringId}/results/${props.student.id}/history`
    );
    history.value = res.data;
  } catch (err) {
    console.error('Failed to load history', err);
  } finally {
    historyLoading.value = false;
  }
};

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  if (hasExistingResult.value) {
    loadHistory();
  }
});
</script>
