<template>
  <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-6 overflow-y-auto">

        <!-- Welcome Banner -->
        <div class="card p-6 bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white shadow-lg relative overflow-hidden">
          <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2L2 22h20L12 2zm0 3.8L18.5 19H5.5L12 5.8z"/>
            </svg>
          </div>
          <div class="relative z-10 space-y-1">
            <span class="text-xs uppercase font-bold tracking-widest text-amber-200">{{ t('dashboard.welcome') }}</span>
            <h1 class="text-2xl font-bold tracking-tight">{{ t('nav.signedInAs') }} Admin</h1>
            <p class="text-xs text-amber-100/90 max-w-xl font-medium leading-relaxed">
              Overview of student, teacher, and parent inquiries, feedback responses, and communication broadcasts.
            </p>
          </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="space-y-6 animate-pulse">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <div v-for="i in 6" :key="i" class="h-28 rounded-2xl skeleton" />
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="h-80 lg:col-span-2 rounded-2xl skeleton" />
            <div class="h-80 rounded-2xl skeleton" />
          </div>
        </div>

        <!-- Dashboard content -->
        <div v-else class="space-y-6 animate-slide-up">

          <!-- 6 Stat Cards Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <div
              v-for="card in statCards"
              :key="card.label"
              class="stat-card group hover:scale-[1.02] transition-transform duration-200"
            >
              <div class="space-y-1">
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block truncate">
                  {{ card.label }}
                </span>
                <p class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 font-mono tracking-tight">{{ card.value }}</p>
                <div class="flex items-center gap-1 text-[11px] font-semibold" :class="card.subColor">
                  <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="card.dotColor" v-if="card.dot" />
                  <span class="truncate">{{ card.sub }}</span>
                </div>
              </div>
              <div class="p-3 rounded-xl shrink-0 group-hover:rotate-6 transition-transform duration-200" :class="card.iconBg">
                <component :is="card.icon" class="w-5 h-5" :class="card.iconColor" />
              </div>
            </div>
          </div>

          <!-- Language distribution & Quick Stats row -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="lang in languageCards" :key="lang.code" class="card p-4 flex items-center justify-between bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
              <div class="flex items-center gap-3">
                <span class="text-2xl">{{ lang.flag }}</span>
                <div>
                  <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ lang.name }}</h4>
                  <p class="text-[10px] text-slate-400 font-medium">{{ lang.sub }}</p>
                </div>
              </div>
              <div class="text-right">
                <span class="text-lg font-bold font-mono text-amber-600 dark:text-amber-400">{{ lang.count }}</span>
                <span class="text-[10px] text-slate-400 block">users</span>
              </div>
            </div>
          </div>

          <!-- Chart + Activity Feed -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- SVG Line Chart -->
            <div class="card p-6 lg:col-span-2 flex flex-col gap-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">{{ t('dashboard.trends') }}</h2>
                  <p class="text-xs text-slate-400 mt-0.5">{{ t('dashboard.past7days') }}</p>
                </div>
                <span class="px-2.5 py-1 text-[10px] font-bold text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400 rounded-lg border border-amber-200/60 dark:border-amber-800/40">
                  {{ t('dashboard.weeklyData') }}
                </span>
              </div>

              <div class="flex-1 h-60 min-h-[220px]">
                <svg viewBox="0 0 580 180" class="w-full h-full overflow-visible">
                  <!-- Grid lines -->
                  <line v-for="y in [20,60,100,140]" :key="y" x1="40" :y1="y" x2="560" :y2="y"
                        stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-dasharray="4 2" />
                  <line x1="40" y1="150" x2="560" y2="150" stroke="currentColor" class="text-slate-200 dark:text-slate-700" />

                  <!-- Area under Feedback Line -->
                  <path :d="`M 60 150 ` + getChartPath(chartData.feedback) + ` L 540 150 Z`"
                        fill="url(#grad-feedback)" opacity="0.12" />

                  <!-- Area under Users Line -->
                  <path :d="`M 60 150 ` + getChartPath(chartData.users) + ` L 540 150 Z`"
                        fill="url(#grad-users)" opacity="0.12" />

                  <!-- Feedback Path -->
                  <path :d="getChartPath(chartData.feedback)" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" />
                  <circle v-for="(val, i) in chartData.feedback" :key="`f-${i}`"
                    :cx="60 + i * 80"
                    :cy="150 - (Math.max(...chartData.feedback, 1) ? (val / Math.max(...chartData.feedback, 1)) * 120 : 0)"
                    r="4" fill="#ffffff" stroke="#f59e0b" stroke-width="2" />

                  <!-- Users Path -->
                  <path :d="getChartPath(chartData.users)" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" />
                  <circle v-for="(val, i) in chartData.users" :key="`u-${i}`"
                    :cx="60 + i * 80"
                    :cy="150 - (Math.max(...chartData.users, 1) ? (val / Math.max(...chartData.users, 1)) * 120 : 0)"
                    r="4" fill="#ffffff" stroke="#10b981" stroke-width="2" />

                  <!-- X-Axis Labels -->
                  <text v-for="(label, i) in chartData.labels" :key="`l-${i}`"
                        :x="60 + i * 80" y="170" text-anchor="middle"
                        class="text-[9px] font-bold fill-slate-400 dark:fill-slate-500 font-mono">
                    {{ label }}
                  </text>

                  <!-- Gradients definition -->
                  <defs>
                    <linearGradient id="grad-feedback" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#f59e0b" />
                      <stop offset="100%" stop-color="#f59e0b" stop-opacity="0" />
                    </linearGradient>
                    <linearGradient id="grad-users" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#10b981" />
                      <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                </svg>
              </div>

              <!-- Legends -->
              <div class="flex gap-4 justify-end text-[10px] text-slate-500 font-semibold pr-3">
                <div class="flex items-center gap-1.5">
                  <span class="w-3 h-1 rounded bg-amber-500 inline-block" />
                  {{ t('dashboard.feedbackLine') }}
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="w-3 h-1 rounded bg-emerald-500 inline-block" />
                  {{ t('dashboard.usersLine') }}
                </div>
              </div>
            </div>

            <!-- Recent Activity Feed -->
            <div class="card p-6 flex flex-col gap-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
              <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">{{ t('dashboard.recentActivity') }}</h2>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
              </div>

              <!-- Activity skeleton -->
              <div v-if="loading" class="space-y-3 animate-pulse">
                <div v-for="i in 5" :key="i" class="flex gap-3 p-2.5">
                  <div class="w-7 h-7 skeleton rounded-lg shrink-0 mt-0.5" />
                  <div class="flex-1 space-y-1.5">
                    <div class="h-3 skeleton rounded w-3/4" />
                    <div class="h-2.5 skeleton rounded w-full" />
                    <div class="h-2 skeleton rounded w-16 mt-1" />
                  </div>
                </div>
              </div>

              <div v-else-if="!activities.length" class="flex-1 flex flex-col items-center justify-center gap-2 text-center py-8">
                <div class="w-11 h-11 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                  <ClockIcon class="w-5 h-5 text-slate-400" />
                </div>
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ t('dashboard.noActivity') }}</p>
                <p class="text-xs text-slate-400">{{ t('dashboard.noActivitySub') }}</p>
              </div>

              <div v-else class="space-y-3 overflow-y-auto max-h-72 flex-1 pr-1">
                <div v-for="act in activities" :key="act.id" class="flex gap-3 text-xs p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                  <div
                    class="mt-0.5 w-7 h-7 rounded-lg shrink-0 flex items-center justify-center"
                    :class="act.type === 'feedback'
                      ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400'
                      : 'bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400'"
                  >
                    <component :is="act.type === 'feedback' ? ChatIcon : UserIcon" class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 dark:text-slate-200 leading-snug">{{ act.title }}</p>
                    <p class="text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ act.description }}</p>
                    <span class="text-[10px] text-slate-400 block mt-1 font-mono">{{ formatTime(act.time) }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
  </main>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios   from 'axios';
import { useI18n } from '../i18n.js';
import {
  UsersIcon,
  ChatBubbleLeftRightIcon as ChatIcon,
  ClipboardDocumentListIcon,
  MegaphoneIcon,
  UserIcon,
  ClockIcon,
  CheckCircleIcon,
  EnvelopeOpenIcon,
} from '@heroicons/vue/24/outline';
import { subscribeToCollection } from '../firebase';

const { t } = useI18n();

const loading     = ref(true);
const activities  = ref([]);
const chartData   = ref({ labels: [], feedback: [], users: [] });

const stats = ref({
  totalUsers: 0,
  activeUsers: 0,
  totalFeedback: 0,
  newFeedback: 0,
  unreadFeedback: 0,
  repliedFeedback: 0,
  closedFeedback: 0,
  broadcastsSent: 0,
  failedDeliveries: 0,
  languageDistribution: { am: 0, en: 0, om: 0 },
});

const statCards = computed(() => [
  {
    label:     t('dashboard.statSubscribers'),
    value:     stats.value.totalUsers,
    sub:       t('dashboard.statActive', { count: stats.value.activeUsers }),
    subColor:  'text-slate-500',
    icon:      UsersIcon,
    iconColor: 'text-blue-600 dark:text-blue-400',
    iconBg:    'bg-blue-50 dark:bg-blue-950/40',
  },
  {
    label:     t('dashboard.statFeedback'),
    value:     stats.value.totalFeedback,
    sub:       t('dashboard.statFeedbackNew', { count: stats.value.newFeedback }),
    subColor:  stats.value.newFeedback > 0 ? 'text-amber-600' : 'text-slate-400',
    dot:       stats.value.newFeedback > 0,
    dotColor:  'bg-amber-500',
    icon:      ChatIcon,
    iconColor: 'text-amber-600 dark:text-amber-400',
    iconBg:    'bg-amber-50 dark:bg-amber-950/40',
  },
  {
    label:     t('dashboard.statUnread'),
    value:     stats.value.unreadFeedback,
    sub:       stats.value.unreadFeedback > 0 ? t('dashboard.statActionRequired') : t('dashboard.statAllRead'),
    subColor:  stats.value.unreadFeedback > 0 ? 'text-red-500' : 'text-slate-400',
    dot:       stats.value.unreadFeedback > 0,
    dotColor:  'bg-red-500',
    icon:      ClipboardDocumentListIcon,
    iconColor: 'text-red-600 dark:text-red-400',
    iconBg:    'bg-red-50 dark:bg-red-950/40',
  },
  {
    label:     t('dashboard.statReplies'),
    value:     stats.value.repliedFeedback,
    sub:       t('dashboard.statRepliesSub'),
    subColor:  'text-slate-400',
    icon:      EnvelopeOpenIcon,
    iconColor: 'text-purple-600 dark:text-purple-400',
    iconBg:    'bg-purple-50 dark:bg-purple-950/40',
  },
  {
    label:     t('dashboard.statBroadcasts'),
    value:     stats.value.broadcastsSent,
    sub:       t('dashboard.statBroadcastsSub'),
    subColor:  'text-slate-400',
    icon:      MegaphoneIcon,
    iconColor: 'text-indigo-600 dark:text-indigo-400',
    iconBg:    'bg-indigo-50 dark:bg-indigo-950/40',
  },
  {
    label:     t('dashboard.statResolved'),
    value:     stats.value.closedFeedback,
    sub:       t('dashboard.statResolvedSub'),
    subColor:  'text-slate-400',
    icon:      CheckCircleIcon,
    iconColor: 'text-emerald-600 dark:text-emerald-400',
    iconBg:    'bg-emerald-50 dark:bg-emerald-950/40',
  },
]);

const languageCards = computed(() => [
  { code: 'am', name: 'Amharic', flag: '🇪🇹', count: stats.value.languageDistribution.am || 0, sub: 'አማርኛ' },
  { code: 'en', name: 'English', flag: '🇺🇸', count: stats.value.languageDistribution.en || 0, sub: 'English' },
  { code: 'om', name: 'Oromifa', flag: '🇪🇹', count: stats.value.languageDistribution.om || 0, sub: 'Afaan Oromoo' },
]);

const getChartPath = (data) => {
  if (!data || !data.length) return '';
  const max = Math.max(...data, 1);
  return data.map((v, i) => `${i === 0 ? 'M' : 'L'} ${60 + i * 80} ${150 - (v / max) * 120}`).join(' ');
};

const formatTime = (t) => t ? new Date(t).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

// Helper to apply full dashboard API response to reactive state
const applyDashboardData = (data) => {
  if (data?.widgets) {
    stats.value = data.widgets;
  }
  if (data?.charts) {
    chartData.value = {
      labels:   data.charts.feedbackOverTime?.labels                  || [],
      feedback: data.charts.feedbackOverTime?.datasets?.[0]?.data     || [],
      users:    data.charts.userGrowth?.datasets?.[0]?.data           || [],
    };
  }
  if (data?.recentActivity) {
    activities.value = data.recentActivity.slice(0, 10);
  }
  loading.value = false;
};

let statsUnsubscribe    = null;
let activityUnsubscribe = null;
let safetyTimer         = null;

onMounted(() => {
  // ── PRIMARY: always fetch from REST API immediately ─────────────────
  // This guarantees the dashboard loads even when Firebase is active but
  // Firestore collections are empty (which caused loading to hang forever)
  axios.get('/dashboard')
    .then(res  => applyDashboardData(res.data))
    .catch(()  => { loading.value = false; }); // stop skeleton on error too

  // ── REALTIME LAYER: Firestore subscriptions for live updates ─────────
  // These are only supplementary; they update the data if Firestore has docs.
  statsUnsubscribe = subscribeToCollection('stats', '/dashboard', (docs) => {
    if (Array.isArray(docs)) {
      // Firestore path: docs is array of collection documents
      if (docs.length) {
        stats.value = docs[0];
        loading.value = false;
      }
      // If empty array from Firestore, REST already handled loading above
    } else if (docs?.widgets) {
      // REST polling path: full dashboard response object
      applyDashboardData(docs);
    }
  });

  activityUnsubscribe = subscribeToCollection('activities', '/dashboard', (docs) => {
    if (Array.isArray(docs) && docs.length) {
      activities.value = docs.slice(0, 10);
    } else if (docs?.recentActivity) {
      activities.value = docs.recentActivity.slice(0, 10);
    }
  });

  // ── SAFETY TIMEOUT: guarantee skeleton is removed within 5s ─────────
  safetyTimer = setTimeout(() => { loading.value = false; }, 5000);
});

onUnmounted(() => {
  if (statsUnsubscribe)    statsUnsubscribe();
  if (activityUnsubscribe) activityUnsubscribe();
  if (safetyTimer)         clearTimeout(safetyTimer);
});

</script>
