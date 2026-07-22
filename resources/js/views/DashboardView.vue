<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="flex-grow p-4 md:p-6 lg:p-8 overflow-y-auto">
        <!-- Loading skeleton -->
        <div v-if="loading" class="space-y-6 animate-pulse">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-28 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-800" />
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="h-80 lg:col-span-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-800" />
            <div class="h-80 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-800" />
          </div>
        </div>

        <!-- Dashboard content -->
        <div v-else class="space-y-6">

          <!-- Stat cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
              v-for="card in statCards"
              :key="card.label"
              class="card p-5 flex items-center justify-between group hover:shadow-md transition-shadow duration-200"
            >
              <div class="space-y-1.5">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                  {{ card.label }}
                </span>
                <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ card.value }}</p>
                <div class="flex items-center gap-1.5 text-xs font-medium" :class="card.subColor">
                  <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="card.dotColor" v-if="card.dot" />
                  <span>{{ card.sub }}</span>
                </div>
              </div>
              <div class="p-3.5 rounded-xl shrink-0 group-hover:scale-110 transition-transform duration-200" :class="card.iconBg">
                <component :is="card.icon" class="w-6 h-6" :class="card.iconColor" />
              </div>
            </div>
          </div>

          <!-- Chart + Activity -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- SVG Line Chart -->
            <div class="card p-6 lg:col-span-2 flex flex-col gap-4">
              <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">Feedback & Growth Trends</h2>
                <p class="text-xs text-slate-400 mt-0.5">Weekly activity overview</p>
              </div>

              <div class="flex-1 h-56">
                <svg viewBox="0 0 580 180" class="w-full h-full overflow-visible">
                  <!-- Grid -->
                  <line v-for="y in [20,60,100,140]" :key="y" x1="40" :y1="y" x2="560" :y2="y"
                        stroke="currentColor" class="text-slate-100 dark:text-slate-800" stroke-dasharray="4 2" />
                  <!-- Y-axis baseline -->
                  <line x1="40" y1="150" x2="560" y2="150" stroke="currentColor" class="text-slate-200 dark:text-slate-700" />

                  <!-- X-axis labels -->
                  <text
                    v-for="(label, i) in chartData.labels"
                    :key="i"
                    :x="60 + i * 80" y="168"
                    text-anchor="middle" font-size="9" fill="currentColor"
                    class="text-slate-400 dark:text-slate-500"
                  >{{ label }}</text>

                  <!-- Feedback line (blue) -->
                  <path v-if="feedbackPath" :d="feedbackPath" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                  <!-- Users line (emerald) -->
                  <path v-if="usersPath" :d="usersPath" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                  <!-- Dots for feedback -->
                  <circle
                    v-for="(val, i) in chartData.feedback"
                    :key="`fb-${i}`"
                    :cx="60 + i * 80"
                    :cy="150 - (Math.max(...chartData.feedback, 1) ? (val / Math.max(...chartData.feedback, 1)) * 120 : 0)"
                    r="3" fill="#3b82f6"
                  />
                  <!-- Dots for users -->
                  <circle
                    v-for="(val, i) in chartData.users"
                    :key="`us-${i}`"
                    :cx="60 + i * 80"
                    :cy="150 - (Math.max(...chartData.users, 1) ? (val / Math.max(...chartData.users, 1)) * 120 : 0)"
                    r="3" fill="#10b981"
                  />
                </svg>
              </div>

              <!-- Legend -->
              <div class="flex items-center gap-6 pt-3 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                  <span class="w-3 h-0.5 rounded bg-blue-500 inline-block" />
                  Feedback Volume
                </div>
                <div class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                  <span class="w-3 h-0.5 rounded bg-emerald-500 inline-block" />
                  New Subscribers
                </div>
              </div>
            </div>

            <!-- Recent Activity Feed -->
            <div class="card p-6 flex flex-col gap-4">
              <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">Recent Activity</h2>

              <div v-if="!activities.length" class="flex-1 flex flex-col items-center justify-center gap-2 text-center py-8">
                <div class="w-11 h-11 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                  <ClockIcon class="w-5 h-5 text-slate-400" />
                </div>
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No activity yet</p>
                <p class="text-xs text-slate-400">Everything is running smoothly</p>
              </div>

              <div v-else class="space-y-3 overflow-y-auto max-h-64 flex-1 pr-1">
                <div v-for="act in activities" :key="act.id" class="flex gap-3 text-xs">
                  <div
                    class="mt-0.5 w-6 h-6 rounded-lg shrink-0 flex items-center justify-center"
                    :class="act.type === 'feedback'
                      ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400'
                      : 'bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400'"
                  >
                    <component :is="act.type === 'feedback' ? ChatIcon : UserIcon" class="w-3.5 h-3.5" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-700 dark:text-slate-200 leading-snug">{{ act.title }}</p>
                    <p class="text-slate-400 truncate mt-0.5">{{ act.description }}</p>
                    <span class="text-[10px] text-slate-400 block mt-1">{{ formatTime(act.time) }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import Navbar  from '../components/Navbar.vue';
import axios   from 'axios';
import {
  UsersIcon,
  ChatBubbleLeftRightIcon as ChatIcon,
  ClipboardDocumentListIcon,
  MegaphoneIcon,
  UserIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline';
import { subscribeToCollection } from '../firebase';

const sidebarOpen = ref(false);
const loading     = ref(true);
const activities  = ref([]);
const chartData   = ref({ labels: [], feedback: [], users: [] });

const stats = ref({
  totalUsers: 0, activeUsers: 0,
  totalFeedback: 0, newFeedback: 0, closedFeedback: 0,
  broadcastsSent: 0, failedDeliveries: 0,
});

const statCards = computed(() => [
  {
    label: 'Total Subscribers', value: stats.value.totalUsers,
    sub: `${stats.value.activeUsers} active`, subColor: 'text-emerald-500',
    dot: true, dotColor: 'bg-emerald-500',
    icon: UsersIcon,
    iconBg: 'bg-blue-50 dark:bg-blue-950/40', iconColor: 'text-blue-600 dark:text-blue-400',
  },
  {
    label: 'New Feedbacks', value: stats.value.newFeedback,
    sub: 'Awaiting response', subColor: 'text-amber-500',
    dot: false,
    icon: ChatIcon,
    iconBg: 'bg-amber-50 dark:bg-amber-950/40', iconColor: 'text-amber-600 dark:text-amber-400',
  },
  {
    label: 'Total Feedbacks', value: stats.value.totalFeedback,
    sub: `${stats.value.closedFeedback} closed`, subColor: 'text-slate-400',
    dot: false,
    icon: ClipboardDocumentListIcon,
    iconBg: 'bg-purple-50 dark:bg-purple-950/40', iconColor: 'text-purple-600 dark:text-purple-400',
  },
  {
    label: 'Broadcasts Sent', value: stats.value.broadcastsSent,
    sub: stats.value.failedDeliveries > 0 ? `${stats.value.failedDeliveries} failed` : '100% success rate',
    subColor: stats.value.failedDeliveries > 0 ? 'text-red-500' : 'text-emerald-500',
    dot: false,
    icon: MegaphoneIcon,
    iconBg: 'bg-emerald-50 dark:bg-emerald-950/40', iconColor: 'text-emerald-600 dark:text-emerald-400',
  },
]);

// ── SVG chart paths ───────────────────────────────────────
const makePath = (data) => {
  if (!data?.length) return '';
  const max = Math.max(...data, 1);
  return data.map((v, i) => `${i === 0 ? 'M' : 'L'} ${60 + i * 80} ${150 - (v / max) * 120}`).join(' ');
};

const feedbackPath = computed(() => makePath(chartData.value.feedback));
const usersPath    = computed(() => makePath(chartData.value.users));

// ── Data fetching ─────────────────────────────────────────
const fetchDashboard = async () => {
  try {
    const res = await axios.get('/dashboard');
    stats.value      = res.data.widgets;
    activities.value = res.data.recentActivity ?? [];
    const c = res.data.charts;
    chartData.value  = {
      labels:   c.feedbackOverTime.labels,
      feedback: c.feedbackOverTime.datasets[0].data,
      users:    c.userGrowth.datasets[0].data,
    };
  } catch (e) {
    console.error('Dashboard fetch error:', e);
  } finally {
    loading.value = false;
  }
};

const formatTime = (t) => t ? new Date(t).toLocaleString() : '';

let unsubscribe = null;
onMounted(() => {
  fetchDashboard();
  unsubscribe = subscribeToCollection('feedback', 'dashboard', () => fetchDashboard());
});
onUnmounted(() => unsubscribe?.());
</script>
