<template>
  <div class="flex flex-col flex-1 min-w-0">
  <main class="grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ t('notifications.title') }}</h2>
          <p class="text-xs text-slate-400 mt-0.5">{{ t('notifications.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- ── Wizard composer ───────────────────────────────── -->
          <div class="card p-6 lg:col-span-2 space-y-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ t('notifications.newCampaign') }}</h3>
              <!-- Step indicator pills -->
              <div class="flex items-center gap-1.5">
                <span
                  v-for="n in 3"
                  :key="n"
                  class="w-6 h-1.5 rounded-full transition-all duration-300"
                  :class="step >= n ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700'"
                />
              </div>
            </div>

            <!-- Step labels -->
            <div class="flex items-center text-xs text-slate-400 gap-2">
              <span :class="step === 1 ? 'font-bold text-amber-600 dark:text-amber-400' : ''">{{ t('notifications.step1') }}</span>
              <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              <span :class="step === 2 ? 'font-bold text-amber-600 dark:text-amber-400' : ''">{{ t('notifications.step2') }}</span>
              <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              <span :class="step === 3 ? 'font-bold text-amber-600 dark:text-amber-400' : ''">{{ t('notifications.step3') }}</span>
            </div>

            <!-- Step 1: Compose -->
            <div v-if="step === 1" class="space-y-4 tab-content-enter-active">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('notifications.campaignTitle') }}</label>
                <input v-model="form.title" type="text" :placeholder="t('notifications.campaignTitlePh')" class="input-base text-xs" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('notifications.messageContent') }}</label>
                <textarea v-model="form.message" rows="6"
                          :placeholder="t('notifications.messagePh')"
                          class="input-base text-xs resize-none" />
              </div>
              <div class="flex justify-end">
                <button @click="step = 2" :disabled="!form.title.trim() || !form.message.trim()" class="btn-primary text-xs">
                  {{ t('notifications.continueAudience') }}
                </button>
              </div>
            </div>

            <!-- Step 2: Audience -->
            <div v-if="step === 2" class="space-y-4 tab-content-enter-active">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label
                  v-for="seg in segments"
                  :key="seg.value"
                  class="flex flex-col gap-1.5 p-4 rounded-xl border cursor-pointer transition-all duration-150"
                  :class="form.targetType === seg.value
                    ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/20'
                    : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                >
                  <div class="flex items-center gap-2">
                    <input type="radio" v-model="form.targetType" :value="seg.value" class="text-amber-600 focus:ring-amber-500" />
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ seg.label }}</span>
                  </div>
                  <p class="text-[10px] text-slate-400 pl-5">{{ seg.description }}</p>
                </label>
              </div>

              <!-- Segment parameter: Language -->
              <div v-if="form.targetType === 'language'" class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('notifications.prefLanguage') }}</label>
                <select v-model="form.targetValue" class="input-base text-xs">
                  <option value="">{{ t('notifications.selectLang') }}</option>
                  <option value="am">🇪🇹 አማርኛ (Amharic)</option>
                  <option value="en">🇺🇸 English</option>
                  <option value="om">🇪🇹 Afaan Oromoo</option>
                </select>
              </div>

              <!-- Segment parameter: Category -->
              <div v-if="form.targetType === 'category'" class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('notifications.catLabel') }}</label>
                <select v-model="form.targetValue" class="input-base text-xs">
                  <option value="">{{ t('notifications.selectCategory') }}</option>
                  <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>

              <!-- Segment parameter: Selected Users list -->
              <div v-if="form.targetType === 'selected'" class="space-y-3">
                <div class="flex gap-2">
                  <input v-model="subscriberSearch" type="text" :placeholder="t('notifications.searchSubscribers')" class="input-base flex-1 text-xs" />
                </div>
                <div class="max-h-48 overflow-y-auto border border-slate-100 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800">
                  <label
                    v-for="s in filteredSubscribers"
                    :key="s.id"
                    class="flex items-center justify-between p-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 cursor-pointer text-xs"
                  >
                    <div class="flex items-center gap-2">
                      <input type="checkbox" :value="s.telegramId" v-model="selectedSubscribers" class="rounded text-amber-600 focus:ring-amber-500" />
                      <div>
                        <p class="font-bold text-slate-800 dark:text-slate-200">{{ s.firstName }} {{ s.lastName }}</p>
                        <span class="text-[10px] text-slate-400">@{{ s.username || 'no-username' }}</span>
                      </div>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                      {{ (s.preferredLanguage || 'am').toUpperCase() }}
                    </span>
                  </label>
                </div>
                <p class="text-[10px] text-slate-400 font-semibold">
                  {{ t('notifications.selectedCount', { count: selectedSubscribers.length }) }}
                </p>
              </div>

              <div class="flex justify-between">
                <AppButton variant="ghost" size="sm" @click="step = 1">{{ t('notifications.back') }}</AppButton>
                <AppButton variant="primary" size="sm" @click="estimateAudience">
                  {{ t('notifications.continueConfirm') }}
                </AppButton>
              </div>
            </div>

            <!-- Step 3: Confirm -->
            <div v-if="step === 3" class="space-y-4 tab-content-enter-active">
              <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/20 dark:border-amber-900/40 dark:bg-amber-950/10 space-y-3">
                <h4 class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ t('notifications.reviewTitle') }}</h4>
                <div class="grid grid-cols-2 gap-2 text-xs">
                  <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ t('notifications.labelTitle') }}</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ form.title }}</span>
                  </div>
                  <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ t('notifications.labelTarget') }}</span>
                    <span class="font-semibold text-amber-700 dark:text-amber-400 uppercase">{{ form.targetType }}</span>
                  </div>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ t('notifications.labelReach') }}</span>
                  <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                    {{ estimate !== null ? t('notifications.usersCount', { count: estimate }) : t('notifications.calculating') }}
                  </span>
                </div>
                <div class="border-t border-amber-200/60 dark:border-amber-900/30 pt-2">
                  <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1">{{ t('notifications.labelPreview') }}</span>
                  <p class="text-xs text-slate-600 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">{{ form.message }}</p>
                </div>
              </div>

              <div class="flex justify-between">
                <AppButton variant="ghost" size="sm" @click="step = 2">{{ t('notifications.back') }}</AppButton>
                <AppButton
                  variant="primary"
                  size="sm"
                  :loading="sending"
                  :disabled="sending || estimate === 0"
                  @click="dispatchBroadcast"
                >
                  {{ sending ? t('notifications.sending') : t('notifications.sendNow') }}
                </AppButton>
              </div>
            </div>
          </div>

          <!-- History panel -->
          <div class="card p-6 space-y-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ t('notifications.history') }}</h3>

            <!-- Skeleton loader -->
            <div v-if="loadingCampaigns" class="space-y-3 animate-pulse">
              <div v-for="i in 4" :key="i" class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 space-y-2">
                <div class="flex justify-between">
                  <div class="space-y-1.5 flex-1">
                    <div class="h-3 skeleton rounded w-2/3" />
                    <div class="h-2.5 skeleton rounded w-1/2" />
                  </div>
                  <div class="h-5 w-16 skeleton rounded-full ml-2 shrink-0" />
                </div>
                <div class="flex justify-between">
                  <div class="h-2.5 skeleton rounded w-20" />
                  <div class="h-2.5 skeleton rounded w-28" />
                </div>
                <div class="flex justify-end">
                  <div class="h-3 skeleton rounded w-24" />
                </div>
              </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="!campaigns.length" class="text-center py-10 text-xs text-slate-400">
              {{ t('notifications.noCampaigns') }}
            </div>

            <!-- Campaign list -->
            <div v-else class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
              <div v-for="c in campaigns" :key="c.id" class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20 text-xs space-y-2">
                <div class="flex justify-between items-start">
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-slate-200">{{ c.title }}</h4>
                    <span class="text-[10px] text-slate-400">{{ t('notifications.byAdmin', { name: c.sentBy || 'Admin' }) }} • {{ formatDate(c.createdAt) }}</span>
                  </div>
                  <span class="badge" :class="campaignStatusClasses(c.status?.toLowerCase())">{{ c.status }}</span>
                </div>

                <div class="text-[10px] text-slate-500 dark:text-slate-400 flex justify-between font-mono">
                  <span>{{ t('notifications.recipients', { count: c.totalRecipients || 0 }) }}</span>
                  <span>{{ t('notifications.sent', { sent: c.sentCount || 0 }) }} / {{ t('notifications.failed', { failed: c.failedCount || 0 }) }}</span>
                </div>

                <div class="flex justify-end">
                  <button @click="viewLogs(c)" class="text-[11px] font-semibold text-amber-600 hover:underline">
                    {{ t('notifications.viewLogs') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>

    <!-- Logs Modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="selectedCampaign" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6 max-h-[85vh] flex flex-col">
            <div class="flex justify-between items-center mb-4 shrink-0">
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ t('notifications.logsTitle', { title: selectedCampaign.title }) }}</h3>
              <button @click="selectedCampaign = null" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                <XMarkIcon class="w-7 h-7" />
              </button>
            </div>

            <div class="flex-1 overflow-y-auto space-y-2 text-xs pr-1">
              <div v-if="!logs.length" class="text-center py-8 text-slate-400">{{ t('notifications.noLogs') }}</div>
              <div v-for="log in logs" :key="log.id" class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <div>
                  <span class="font-mono font-bold text-slate-700 dark:text-slate-300">ID: {{ log.userId || log.telegramId }}</span>
                  <span class="block text-[10px] text-slate-400">{{ formatDate(log.sentAt) }}</span>
                </div>
                <span class="badge" :class="log.status === 'Success' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                  {{ log.status }}
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
import { ref, computed, onMounted, inject } from 'vue';
import AppButton from '../components/AppButton.vue';
import axios from 'axios';
import { useI18n, useEnumI18n } from '../i18n.js';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const { categoryOptions } = useEnumI18n();

const step = ref(1);
const campaigns = ref([]);
const loadingCampaigns = ref(true);
const subscribers = ref([]);
const selectedSubscribers = ref([]);
const subscriberSearch = ref('');
const estimate = ref(null);
const sending = ref(false);
const selectedCampaign = ref(null);
const logs = ref([]);
const showToast = inject('showToast');

const form = ref({
  title: '',
  message: '',
  targetType: 'all',
  targetValue: ''
});

// Reactively translate target segment cards
const segments = computed(() => [
  { value: 'all',      label: t('notifications.targetAll'),      description: t('notifications.segDescAll') },
  { value: 'active',   label: t('notifications.targetActive'),   description: t('notifications.segDescActive') },
  { value: 'language', label: t('notifications.targetLanguage'), description: t('notifications.segDescLanguage') },
  { value: 'category', label: t('notifications.targetCategory'), description: t('notifications.segDescCategory') },
  { value: 'selected', label: t('notifications.targetSelected'), description: t('notifications.segDescSelected') },
]);

const filteredSubscribers = computed(() => {
  if (!subscriberSearch.value.trim()) return subscribers.value;
  const q = subscriberSearch.value.toLowerCase();
  return subscribers.value.filter(s => {
    return (s.firstName ?? '').toLowerCase().includes(q) ||
           (s.lastName ?? '').toLowerCase().includes(q) ||
           (s.username ?? '').toLowerCase().includes(q) ||
           String(s.telegramId).includes(q);
  });
});

const fetchCampaigns = async () => {
  loadingCampaigns.value = true;
  try {
    const res = await axios.get('/notifications');
    campaigns.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (_) {}
  finally { loadingCampaigns.value = false; }
};

const fetchSubscribers = async () => {
  try {
    // Fetch all users (paginated with high limit) for the 'selected' audience targeting
    const res = await axios.get('/users', { params: { per_page: 1000 } });
    subscribers.value = res.data?.data || res.data || [];
  } catch (_) {}
};

const estimateAudience = async () => {
  estimate.value = null;
  step.value = 3;
  try {
    const res = await axios.post('/notifications/estimate', {
      targetType: form.value.targetType,
      targetValue: form.value.targetType === 'selected' ? selectedSubscribers.value : form.value.targetValue
    });
    estimate.value = res.data.count;
  } catch {
    estimate.value = 0;
  }
};

const dispatchBroadcast = async () => {
  sending.value = true;
  try {
    await axios.post('/notifications', {
      title: form.value.title,
      message: form.value.message,
      targetType: form.value.targetType,
      targetValue: form.value.targetType === 'selected' ? selectedSubscribers.value : form.value.targetValue
    });
    showToast(t('notifications.dispatched'));
    form.value = { title: '', message: '', targetType: 'all', targetValue: '' };
    selectedSubscribers.value = [];
    step.value = 1;
    fetchCampaigns();
  } catch {
    showToast(t('notifications.dispatchFailed'), 'error');
  } finally {
    sending.value = false;
  }
};

const viewLogs = async (c) => {
  selectedCampaign.value = c;
  try {
    const res = await axios.get(`/notifications/${c.id}/logs`);
    logs.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch {
    logs.value = [];
  }
};

const campaignStatusClasses = (status) => {
  switch (status) {
    case 'completed': return 'bg-emerald-50 text-emerald-600 border border-emerald-200';
    case 'failed':    return 'bg-red-50 text-red-600 border border-red-200';
    case 'sending':   return 'bg-blue-50 text-blue-600 border border-blue-200 animate-pulse';
    default:          return 'bg-slate-100 text-slate-500 border border-slate-200';
  }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '';

onMounted(() => {
  fetchCampaigns();
  fetchSubscribers();
});
</script>
