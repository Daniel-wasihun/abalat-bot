<template>
  <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-6 overflow-y-auto font-sans">

    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-blue via-brand-blue/90 to-brand-blue-dark p-6 md:p-8 text-white shadow-lg">
      <!-- Decorative vector art -->
      <div class="absolute -right-6 -bottom-10 opacity-10 text-white pointer-events-none select-none">
        <svg class="w-72 h-72" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2L2 22h20L12 2zm0 3.8L18.5 19H5.5L12 5.8z"/>
        </svg>
      </div>

      <div class="relative z-10 space-y-2">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white/10 text-xs font-bold tracking-widest text-blue-200 uppercase backdrop-blur-sm">
          ⛪ {{ t('dashboard.welcome') }}
        </span>
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
          {{ t('nav.signedInAs') }} {{ localize(authStore.user?.name) || 'Admin' }}
        </h1>
        <p class="text-xs md:text-sm text-blue-100/90 max-w-2xl font-medium leading-relaxed">
          Overview of student, teacher, and parent inquiries, feedback responses, and communication broadcasts for the Senbet School LMS.
        </p>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div v-for="i in 6" :key="i" class="h-28 rounded-3xl skeleton bg-main-text/5 border border-card-border" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="h-80 lg:col-span-2 rounded-3xl skeleton bg-main-text/5 border border-card-border" />
        <div class="h-80 rounded-3xl skeleton bg-main-text/5 border border-card-border" />
      </div>
    </div>

    <!-- Dashboard content -->
    <div v-else class="space-y-6 animate-slide-up">

      <!-- 6 Stat Cards Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="premium-card p-5 flex flex-col gap-4 relative overflow-hidden cursor-pointer transition-all duration-300 hover:border-brand-blue/30 group"
        >
          <!-- Icon Bubble -->
          <div
            class="w-10 h-10 rounded-xl flex items-center justify-center border transition-all duration-300 shrink-0"
            :class="[card.iconBg, card.iconColor]"
          >
            <component :is="card.icon" class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" :stroke-width="1.5" />
          </div>

          <!-- Value / Label -->
          <div class="space-y-1">
            <p class="text-2xl font-black text-main-text font-mono tracking-tight leading-none">
              {{ card.value }}
            </p>
            <span class="text-[10px] font-bold text-main-text/40 uppercase tracking-widest block truncate">
              {{ card.label }}
            </span>
          </div>

          <!-- Sub Info -->
          <div class="flex items-center gap-1.5 text-[11px] font-semibold mt-auto" :class="card.subColor">
            <span class="w-1.5 h-1.5 rounded-full" :class="card.dotColor || 'bg-main-text/20'" />
            <span class="truncate">{{ card.sub }}</span>
          </div>
        </div>
      </div>

      <!-- Language distribution row -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          v-for="lang in languageCards"
          :key="lang.code"
          class="premium-card p-4 flex items-center justify-between transition-all duration-300 hover:border-brand-blue/20"
        >
          <div class="flex items-center gap-3">
            <span class="text-3xl filter drop-shadow-sm select-none">{{ lang.flag }}</span>
            <div>
              <h4 class="text-xs font-bold text-main-text">{{ lang.name }}</h4>
              <p class="text-[10px] text-main-text/40 font-medium font-mono">{{ lang.sub }}</p>
            </div>
          </div>
          <div class="text-right">
            <span class="text-xl font-bold font-mono text-brand-blue dark:text-blue-400">{{ lang.count }}</span>
            <span class="text-[10px] text-main-text/30 block uppercase tracking-wider font-semibold">users</span>
          </div>
        </div>
      </div>

      <!-- Chart + Activity Feed -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- SVG Line Chart Card -->
        <div class="premium-card p-6 lg:col-span-2 flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-base font-bold text-main-text">{{ t('dashboard.trends') }}</h2>
              <p class="text-xs text-main-text/40 mt-0.5">{{ t('dashboard.past7days') }}</p>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-bold text-brand-blue bg-brand-blue/5 dark:bg-brand-blue/10 dark:text-blue-400 rounded-lg border border-brand-blue/20 dark:border-brand-blue/30">
              {{ t('dashboard.weeklyData') }}
            </span>
          </div>

          <!-- SVG Chart Area -->
          <div class="flex-1 h-60 min-h-[220px]">
            <svg viewBox="0 0 580 180" class="w-full h-full overflow-visible">
              <!-- Grid lines -->
              <line v-for="y in [20,60,100,140]" :key="y" x1="40" :y1="y" x2="560" :y2="y"
                    stroke="currentColor" class="text-slate-100 dark:text-slate-800/50" stroke-dasharray="4 2" />
              <line x1="40" y1="150" x2="560" y2="150" stroke="currentColor" class="text-slate-200 dark:text-slate-700" />

              <!-- Area under Feedback Line -->
              <path :d="`M 60 150 ` + getChartPath(chartData.feedback) + ` L 540 150 Z`"
                    fill="url(#grad-feedback)" opacity="0.1" />

              <!-- Area under Users Line -->
              <path :d="`M 60 150 ` + getChartPath(chartData.users) + ` L 540 150 Z`"
                    fill="url(#grad-users)" opacity="0.1" />

              <!-- Feedback Path -->
              <path :d="getChartPath(chartData.feedback)" fill="none" stroke="#0b529c" stroke-width="3" stroke-linecap="round" />
              <circle v-for="(val, i) in chartData.feedback" :key="`f-${i}`"
                :cx="60 + i * 80"
                :cy="150 - (Math.max(...chartData.feedback, 1) ? (val / Math.max(...chartData.feedback, 1)) * 120 : 0)"
                r="4" fill="#ffffff" stroke="#0b529c" stroke-width="2.5" />

              <!-- Users Path -->
              <path :d="getChartPath(chartData.users)" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" />
              <circle v-for="(val, i) in chartData.users" :key="`u-${i}`"
                :cx="60 + i * 80"
                :cy="150 - (Math.max(...chartData.users, 1) ? (val / Math.max(...chartData.users, 1)) * 120 : 0)"
                r="4" fill="#ffffff" stroke="#10b981" stroke-width="2.5" />

              <!-- X-Axis Labels -->
              <text v-for="(label, i) in chartData.labels" :key="`l-${i}`"
                    :x="60 + i * 80" y="170" text-anchor="middle"
                    class="text-[9px] font-bold fill-main-text/40 font-mono">
                {{ label }}
              </text>

              <!-- Gradients definition -->
              <defs>
                <linearGradient id="grad-feedback" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#0b529c" />
                  <stop offset="100%" stop-color="#0b529c" stop-opacity="0" />
                </linearGradient>
                <linearGradient id="grad-users" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#10b981" />
                  <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                </linearGradient>
              </defs>
            </svg>
          </div>

          <!-- Chart Legends -->
          <div class="flex gap-4 justify-end text-[10px] text-main-text/50 font-semibold pr-3 pt-2 border-t border-card-border/40">
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-1.5 rounded-full bg-brand-blue inline-block" />
              {{ t('dashboard.feedbackLine') }}
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-1.5 rounded-full bg-emerald-500 inline-block" />
              {{ t('dashboard.usersLine') }}
            </div>
          </div>
        </div>

        <!-- Recent Activity Feed Card -->
        <div class="premium-card p-6 flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-main-text">{{ t('dashboard.recentActivity') }}</h2>
            <span class="flex h-2 w-2 relative">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
          </div>

          <!-- Empty state -->
          <div v-if="!activities.length" class="flex-grow flex flex-col items-center justify-center gap-2.5 text-center py-12">
            <div class="w-12 h-12 rounded-2xl bg-nav-accent/5 flex items-center justify-center">
              <Clock class="w-5 h-5 text-main-text/30" />
            </div>
            <div>
              <p class="text-sm font-semibold text-main-text/60">{{ t('dashboard.noActivity') }}</p>
              <p class="text-xs text-main-text/30 mt-0.5">{{ t('dashboard.noActivitySub') }}</p>
            </div>
          </div>

          <!-- Activity items -->
          <div v-else class="space-y-3 overflow-y-auto max-h-72 flex-grow pr-1 custom-scrollbar">
            <div
              v-for="act in activities"
              :key="act.id"
              class="flex gap-3 text-xs p-3 rounded-2xl bg-main-bg/30 border border-card-border/20 hover:bg-nav-accent/5 transition-all duration-200"
            >
              <!-- Icon bubble -->
              <div
                class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center mt-0.5"
                :class="act.type === 'feedback'
                  ? 'bg-brand-blue/10 text-brand-blue'
                  : 'bg-brand-blue/10 text-brand-blue'"
              >
                <component :is="act.type === 'feedback' ? MessageSquare : User" class="w-4 h-4" />
              </div>

              <!-- Message body -->
              <div class="flex-1 min-w-0">
                <p class="font-bold text-main-text leading-snug truncate">{{ act.title }}</p>
                <p class="text-main-text/50 truncate mt-0.5">{{ act.description }}</p>
                <span class="text-[10px] text-main-text/30 block mt-1 font-mono font-medium">{{ formatTime(act.time) }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { useLanguageStore } from "@/stores/languageStore";
import { useAuthStore } from "@/stores/authStore";
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Lucide Icons (matching LMS premium cards)
import {
  Users,
  MessageSquare,
  ClipboardList,
  Bell,
  MailOpen,
  CheckCircle2,
  Clock,
  User,
} from 'lucide-vue-next';

const languageStore = useLanguageStore();
const authStore = useAuthStore();

const t = (k: string, p?: Record<string, any>) => languageStore.translate(k, p);

const localize = (val: any) => {
  if (!val) return "";
  return typeof val === "object"
    ? val[languageStore.currentLanguage] || val["en"] || ""
    : val;
};

const loading    = ref(true);
const activities = ref<any[]>([]);
const chartData  = ref<{ labels: string[]; feedback: number[]; users: number[] }>({
  labels: [], feedback: [], users: [],
});

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
    subColor:  'text-main-text/50',
    icon:      Users,
    iconColor: 'text-blue-600 dark:text-blue-400',
    iconBg:    'bg-blue-500/10 border-blue-500/20',
  },
  {
    label:     t('dashboard.statFeedback'),
    value:     stats.value.totalFeedback,
    sub:       t('dashboard.statFeedbackNew', { count: stats.value.newFeedback }),
    subColor:  stats.value.newFeedback > 0 ? 'text-brand-blue' : 'text-main-text/40',
    dot:       stats.value.newFeedback > 0,
    dotColor:  'bg-brand-blue',
    icon:      MessageSquare,
    iconColor: 'text-brand-blue dark:text-blue-400',
    iconBg:    'bg-brand-blue/10 border-brand-blue/20',
  },
  {
    label:     t('dashboard.statUnread'),
    value:     stats.value.unreadFeedback,
    sub:       stats.value.unreadFeedback > 0 ? t('dashboard.statActionRequired') : t('dashboard.statAllRead'),
    subColor:  stats.value.unreadFeedback > 0 ? 'text-rose-500' : 'text-main-text/40',
    dot:       stats.value.unreadFeedback > 0,
    dotColor:  'bg-rose-500',
    icon:      ClipboardList,
    iconColor: 'text-rose-600 dark:text-rose-400',
    iconBg:    'bg-rose-500/10 border-rose-500/20',
  },
  {
    label:     t('dashboard.statReplies'),
    value:     stats.value.repliedFeedback,
    sub:       t('dashboard.statRepliesSub'),
    subColor:  'text-main-text/40',
    icon:      MailOpen,
    iconColor: 'text-purple-600 dark:text-purple-400',
    iconBg:    'bg-purple-500/10 border-purple-500/20',
  },
  {
    label:     t('dashboard.statBroadcasts'),
    value:     stats.value.broadcastsSent,
    sub:       t('dashboard.statBroadcastsSub'),
    subColor:  'text-main-text/40',
    icon:      Bell,
    iconColor: 'text-indigo-600 dark:text-indigo-400',
    iconBg:    'bg-indigo-500/10 border-indigo-500/20',
  },
  {
    label:     t('dashboard.statResolved'),
    value:     stats.value.closedFeedback,
    sub:       t('dashboard.statResolvedSub'),
    subColor:  'text-main-text/40',
    icon:      CheckCircle2,
    iconColor: 'text-emerald-600 dark:text-emerald-400',
    iconBg:    'bg-emerald-500/10 border-emerald-500/20',
  },
]);

const languageCards = computed(() => [
  { code: 'am', name: 'Amharic', flag: '🇪🇹', count: stats.value.languageDistribution.am || 0, sub: 'አማርኛ' },
  { code: 'en', name: 'English', flag: '🇺🇸', count: stats.value.languageDistribution.en || 0, sub: 'English' },
  { code: 'om', name: 'Oromifa', flag: '🇪🇹', count: stats.value.languageDistribution.om || 0, sub: 'Afaan Oromoo' },
]);

const getChartPath = (data: number[]) => {
  if (!data || !data.length) return '';
  const max = Math.max(...data, 1);
  return data.map((v, i) => `${i === 0 ? 'M' : 'L'} ${60 + i * 80} ${150 - (v / max) * 120}`).join(' ');
};

const formatTime = (time: string) =>
  time ? new Date(time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

const applyDashboardData = (data: any) => {
  if (data?.widgets)  stats.value    = data.widgets;
  if (data?.charts) {
    chartData.value = {
      labels:   data.charts.feedbackOverTime?.labels               || [],
      feedback: data.charts.feedbackOverTime?.datasets?.[0]?.data  || [],
      users:    data.charts.userGrowth?.datasets?.[0]?.data         || [],
    };
  }
  if (data?.recentActivity) activities.value = data.recentActivity.slice(0, 10);
  loading.value = false;
};

let safetyTimer: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
  axios.get('/bot/dashboard')
    .then(res  => applyDashboardData(res.data))
    .catch(() => { loading.value = false; });

  safetyTimer = setTimeout(() => { loading.value = false; }, 5000);
});

onUnmounted(() => {
  if (safetyTimer) clearTimeout(safetyTimer);
});
</script>
