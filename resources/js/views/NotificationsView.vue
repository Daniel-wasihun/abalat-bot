<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Broadcast Campaigns</h2>
          <p class="text-xs text-slate-400 mt-0.5">Compose and dispatch bulk messages to Telegram subscribers</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- ── Wizard composer ───────────────────────────────── -->
          <div class="card p-6 lg:col-span-2 space-y-5">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">New Campaign</h3>
              <!-- Step indicator pills -->
              <div class="flex items-center gap-1.5">
                <span
                  v-for="n in 3"
                  :key="n"
                  class="w-6 h-1.5 rounded-full transition-all duration-300"
                  :class="step >= n ? 'bg-primary-500' : 'bg-slate-200 dark:bg-slate-700'"
                />
              </div>
            </div>

            <!-- Step labels -->
            <div class="flex items-center text-xs text-slate-400 gap-2">
              <span :class="step === 1 ? 'font-bold text-primary-600 dark:text-primary-400' : ''">1. Compose</span>
              <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              <span :class="step === 2 ? 'font-bold text-primary-600 dark:text-primary-400' : ''">2. Audience</span>
              <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              <span :class="step === 3 ? 'font-bold text-primary-600 dark:text-primary-400' : ''">3. Confirm</span>
            </div>

            <!-- Step 1: Compose -->
            <div v-if="step === 1" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Campaign Title</label>
                <input v-model="form.title" type="text" placeholder="Internal reference name…" class="input-base" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Message Content</label>
                <textarea v-model="form.message" rows="6"
                          placeholder="Write your announcement. Supports Telegram Markdown (*bold*, _italic_, `code`)."
                          class="input-base resize-none" />
              </div>
              <div class="flex justify-end">
                <button @click="step = 2" :disabled="!form.title.trim() || !form.message.trim()" class="btn-primary">
                  Continue →
                </button>
              </div>
            </div>

            <!-- Step 2: Audience -->
            <div v-if="step === 2" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label
                  v-for="seg in segments"
                  :key="seg.value"
                  class="flex flex-col gap-1.5 p-4 rounded-xl border cursor-pointer transition-all duration-150"
                  :class="form.targetType === seg.value
                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20'
                    : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                >
                  <div class="flex items-center gap-2">
                    <input type="radio" v-model="form.targetType" :value="seg.value" class="text-primary-600" />
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ seg.label }}</span>
                  </div>
                  <p class="text-[10px] text-slate-400 pl-5">{{ seg.description }}</p>
                </label>
              </div>

              <!-- Segment parameters -->
              <div v-if="form.targetType === 'category'" class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Feedback Group</label>
                <select v-model="form.targetValue" class="input-base">
                  <option value="">Select group…</option>
                  <option value="Spiritual Education">Spiritual Education</option>
                  <option value="Choir & Hymns">Choir & Hymns</option>
                  <option value="Liturgy & Service">Liturgy & Service</option>
                  <option value="General Inquiry">General Inquiry</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <!-- Slicing the selector for target type selected subscribers -->
              <div v-if="form.targetType === 'selected'" class="space-y-3">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Select Target Subscribers</label>
                  <span class="text-xs font-bold text-primary-600 dark:text-primary-400">
                    {{ selectedSubscribers.length }} subscriber{{ selectedSubscribers.length !== 1 ? 's' : '' }} selected
                  </span>
                </div>

                <!-- Simple internal list wrapper -->
                <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                  
                  <!-- Filter field inside list -->
                  <div class="p-2 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex gap-2">
                    <input v-model="subscriberSearch" type="text" placeholder="Search by name or username…"
                           class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:border-primary-500 dark:border-slate-700 dark:bg-slate-950 text-slate-700 dark:text-slate-200" />
                    <button v-if="selectedSubscribers.length" @click="clearSelectedSubscribers" type="button"
                            class="px-2.5 py-1.5 text-[10px] font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors">
                      Clear Selection
                    </button>
                  </div>

                  <!-- Subscribers selection check grid -->
                  <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-if="!filteredSubscribers.length" class="p-4 text-center text-xs text-slate-400">
                      No subscribers match search
                    </div>
                    <label v-for="sub in filteredSubscribers" :key="sub.id"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-950 cursor-pointer transition-colors">
                      <input type="checkbox" :value="sub.telegramId" v-model="selectedSubscribers" @change="syncTargetValue"
                             class="rounded text-primary-600 focus:ring-primary-500/20 w-4 h-4" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                          {{ sub.firstName }} {{ sub.lastName }}
                        </p>
                        <p class="text-[10px] text-slate-400 truncate">
                          @{{ sub.username || '—' }} (ID: {{ sub.telegramId }})
                        </p>
                      </div>
                    </label>
                  </div>

                </div>
              </div>

              <div class="flex justify-between pt-2">
                <button @click="step = 1" class="btn-ghost">← Back</button>
                <button @click="estimateDelivery"
                        :disabled="(form.targetType === 'category' && !form.targetValue) || (form.targetType === 'selected' && !selectedSubscribers.length)"
                        class="btn-primary">
                  Estimate Recipients →
                </button>
              </div>
            </div>

            <!-- Step 3: Confirm & dispatch -->
            <div v-if="step === 3" class="space-y-5">
              <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Campaign Summary</p>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-xs text-slate-400 mb-0.5">Recipients</p>
                    <p class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ estimate.count.toLocaleString() }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400 mb-0.5">Est. Duration</p>
                    <p class="text-xl font-bold text-slate-800 dark:text-slate-100">~{{ estimate.duration }}s</p>
                  </div>
                </div>

                <div class="flex gap-2.5 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 text-xs text-amber-800 dark:text-amber-300">
                  <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <span><strong>Rate limited</strong> to 30 messages/sec to prevent Telegram API throttling.</span>
                </div>
              </div>

              <div class="flex justify-between">
                <button @click="step = 2" class="btn-ghost">← Back</button>
                <button @click="dispatchBroadcast" :disabled="sending"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md shadow-emerald-600/20 disabled:opacity-50 transition-all">
                  <svg v-if="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                  </svg>
                  {{ sending ? 'Dispatching…' : 'Dispatch Campaign' }}
                </button>
              </div>
            </div>
          </div>

          <!-- ── Tips panel ─────────────────────────────────── -->
          <div class="card p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Deliverability Guide</h3>
            <div class="space-y-4 text-xs">
              <div v-for="tip in tips" :key="tip.title">
                <p class="font-semibold text-slate-700 dark:text-slate-300 mb-0.5">{{ tip.title }}</p>
                <p class="text-slate-400 leading-relaxed">{{ tip.body }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Campaign history table ─────────────────────── -->
        <div class="card overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Campaign History</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50 text-slate-400 font-semibold uppercase tracking-wide">
                  <th class="px-4 py-3">Campaign</th>
                  <th class="px-4 py-3">Segment</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Recipients</th>
                  <th class="px-4 py-3 min-w-35">Progress</th>
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3 text-right">Logs</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-if="!campaigns.length">
                  <td colspan="7" class="px-4 py-10 text-center text-slate-400">No broadcast history yet</td>
                </tr>
                <tr v-else v-for="camp in campaigns" :key="camp.id" class="table-row-base">
                  <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">{{ camp.title }}</td>
                  <td class="px-4 py-3 uppercase font-bold text-slate-400">{{ camp.targetType }}</td>
                  <td class="px-4 py-3">
                    <span class="badge" :class="campaignStatusClasses(camp.status)">{{ camp.status }}</span>
                  </td>
                  <td class="px-4 py-3 font-mono font-semibold text-slate-600 dark:text-slate-300">{{ camp.totalRecipients }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full transition-all duration-500"
                             :style="{ width: progress(camp) + '%' }" />
                      </div>
                      <span class="text-[10px] font-mono shrink-0 text-slate-400">{{ progress(camp) }}%</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ formatDate(camp.createdAt) }}</td>
                  <td class="px-4 py-3 text-right">
                    <button @click="viewLogs(camp)"
                            class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                      View Logs
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>

    <!-- Logs modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="selectedCampaign" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-lg max-h-[75vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Logs — {{ selectedCampaign.title }}</h3>
              <button @click="selectedCampaign = null" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-slate-50 dark:bg-slate-950">
              <p v-if="!logs.length" class="text-xs text-slate-400 text-center py-6">No logs available</p>
              <div v-else v-for="log in logs" :key="log.id"
                   class="flex justify-between text-xs font-mono p-2 rounded border border-slate-200/60 dark:border-slate-800/60">
                <span class="text-slate-400">{{ log.chatId }}</span>
                <span :class="log.status === 'success' ? 'text-emerald-500' : 'text-red-500'">
                  {{ log.status === 'success' ? '✓ Delivered' : '✗ ' + log.errorMessage }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, inject, computed } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import Navbar  from '../components/Navbar.vue';
import axios   from 'axios';

const sidebarOpen      = ref(false);
const step             = ref(1);
const sending          = ref(false);
const campaigns        = ref([]);
const logs             = ref([]);
const selectedCampaign = ref(null);
const showToast        = inject('showToast');

const form = ref({ title: '', message: '', targetType: 'all', targetValue: '' });
const estimate = ref({ count: 0, duration: 0 });

// Selection states for target specific users
const subscribers         = ref([]);
const subscriberSearch    = ref('');
const selectedSubscribers = ref([]);

const segments = [
  { value: 'all',      label: 'All Subscribers',   description: 'Send to the entire chatbot network' },
  { value: 'active',   label: 'Active Contacts',    description: 'Only recently active subscribers' },
  { value: 'category', label: 'Feedback Segment',   description: 'Target a specific feedback category group' },
  { value: 'selected', label: 'Specific Users',     description: 'Select target users from a searchable list' },
];

const tips = [
  { title: 'Markdown Support', body: 'Use *bold*, _italic_, and `code` to format your message.' },
  { title: 'Targeting Strategy', body: 'Segmented messages achieve up to 4× higher engagement than mass blasts.' },
  { title: 'Queue Processing', body: 'Campaigns run asynchronously in the background via Laravel queues.' },
];

// Computed to filter subscribers list in selection UI
const filteredSubscribers = computed(() => {
  if (!subscriberSearch.value.trim()) return subscribers.value;
  const q = subscriberSearch.value.toLowerCase();
  return subscribers.value.filter(s => {
    return (s.firstName ?? '').toLowerCase().includes(q) ||
           (s.lastName ?? '').toLowerCase().includes(q) ||
           (s.username ?? '').toLowerCase().includes(q) ||
           String(s.telegramId ?? '').includes(q);
  });
});

const fetchCampaigns = async () => {
  try {
    const res = await axios.get('/notifications');
    campaigns.value = res.data;
  } catch { showToast('Failed to load campaign history', 'error'); }
};

const fetchSubscribers = async () => {
  try {
    const res = await axios.get('/users');
    subscribers.value = res.data;
  } catch {
    console.error('Failed to fetch subscribers for wizard');
  }
};

const syncTargetValue = () => {
  form.value.targetValue = selectedSubscribers.value.join(',');
};

const clearSelectedSubscribers = () => {
  selectedSubscribers.value = [];
  form.value.targetValue = '';
};

const estimateDelivery = async () => {
  try {
    const res = await axios.post('/notifications/estimate', {
      targetType: form.value.targetType, targetValue: form.value.targetValue,
    });
    estimate.value = res.data;
    step.value = 3;
  } catch { showToast('Failed to estimate recipients', 'error'); }
};

const dispatchBroadcast = async () => {
  sending.value = true;
  try {
    await axios.post('/notifications', form.value);
    showToast('Campaign dispatched to queue!');
    form.value = { title: '', message: '', targetType: 'all', targetValue: '' };
    selectedSubscribers.value = [];
    step.value = 1;
    fetchCampaigns();
  } catch { showToast('Failed to dispatch campaign', 'error'); }
  finally { sending.value = false; }
};

const viewLogs = async (camp) => {
  selectedCampaign.value = camp;
  try {
    const res = await axios.get(`/notifications/${camp.id}/logs`);
    logs.value = res.data.logs;
  } catch { showToast('Failed to load logs', 'error'); }
};

const progress = (c) => c.totalRecipients === 0 ? 100 : Math.round((c.processedRecipients / c.totalRecipients) * 100);

const campaignStatusClasses = (s) => ({
  completed: 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  sending:   'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800 animate-pulse',
  failed:    'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800',
  pending:   'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
}[s] ?? 'bg-slate-100 text-slate-500');

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';

let pollTimer;
onMounted(() => {
  fetchCampaigns();
  fetchSubscribers();
  pollTimer = setInterval(fetchCampaigns, 10000);
});
onUnmounted(() => clearInterval(pollTimer));
</script>
