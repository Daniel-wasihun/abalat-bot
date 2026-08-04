<template>
  <div class="flex flex-col flex-1 min-w-0 font-sans">
    <main class="grow p-4 md:p-6 lg:p-8 space-y-6 overflow-y-auto">

      <!-- Page Header -->
      <PageTitle
        :title="t('settings.title')"
        :subtitle="t('settings.subtitle')"
        :icon="Settings"
        icon-bg-class="bg-brand-blue/10"
        icon-color-class="text-brand-blue"
      />

      <!-- Loading Skeleton -->
      <div v-if="loadingSettings" class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-pulse">
        <div v-for="i in 2" :key="i" class="bg-card-bg border border-card-border rounded-2xl p-6 space-y-5">
          <div class="flex items-center gap-3">
            <div class="skeleton w-10 h-10 rounded-xl opacity-30" />
            <div class="space-y-1.5">
              <div class="skeleton h-4 w-32 rounded opacity-25" />
              <div class="skeleton h-3 w-48 rounded opacity-15" />
            </div>
          </div>
          <div class="space-y-3">
            <div class="skeleton h-3 w-20 rounded opacity-20" />
            <div class="skeleton h-11 w-full rounded-xl opacity-15" />
          </div>
          <div class="space-y-3">
            <div class="skeleton h-3 w-28 rounded opacity-20" />
            <div class="skeleton h-24 w-full rounded-xl opacity-15" />
          </div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ── Bot Credentials Card ─────────────────────────── -->
        <div class="bg-card-bg border border-card-border rounded-2xl overflow-hidden flex flex-col">
          <!-- Card Header -->
          <div class="px-6 pt-5 pb-4 border-b border-card-border/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-blue/10 flex items-center justify-center shrink-0">
              <Bot class="w-5 h-5 text-brand-blue" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-main-text">{{ t('settings.botParams') }}</h3>
              <p class="text-xs text-main-text/50 mt-0.5">{{ t('settings.botParamsDesc') }}</p>
            </div>
          </div>
          <!-- Card Body -->
          <form @submit.prevent="saveBotSettings" class="flex flex-col flex-1 p-6 space-y-5">
            <FormField
              v-model="settings.bot_token"
              type="password"
              :label="t('settings.apiToken')"
              placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
              :hint="t('settings.apiTokenHint')"
            />
            <FormField
              v-model="settings.welcome_message"
              type="textarea"
              :label="t('settings.welcomeMsg')"
              :placeholder="t('settings.welcomeMsgPh')"
              :rows="5"
              :hint="t('settings.welcomeMsgHint')"
            />
            <div class="flex justify-end pt-1">
              <Button type="submit" variant="primary" :loading="saving" :icon="Save">
                {{ saving ? t('common.saving') : t('settings.saveConfig') }}
              </Button>
            </div>
          </form>
        </div>

        <!-- ── Right Column ─────────────────────────── -->
        <div class="flex flex-col gap-6">

          <!-- Webhook Config Card -->
          <div class="bg-card-bg border border-card-border rounded-2xl overflow-hidden">
            <!-- Card Header -->
            <div class="px-6 pt-5 pb-4 border-b border-card-border/50 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                <Link2 class="w-5 h-5 text-emerald-500" />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-main-text">{{ t('settings.webhookConfig') }}</h3>
                <p class="text-xs text-main-text/50 mt-0.5">{{ t('settings.webhookConfigDesc') }}</p>
              </div>
              <!-- Live status badge -->
              <span
                class="flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border"
                :class="webhookHealth && !webhookHealth.last_error_date
                  ? 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800'
                  : 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800'"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="webhookHealth && !webhookHealth.last_error_date ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"
                />
                {{ webhookHealth && !webhookHealth.last_error_date ? t('settings.webhookActive') : t('settings.webhookInactive') }}
              </span>
            </div>

            <div class="p-6 space-y-5">
              <!-- URL + Register -->
              <div class="flex items-end gap-3">
                <div class="flex-1 min-w-0">
                  <FormField
                    v-model="settings.webhook_url"
                    type="url"
                    :label="t('settings.webhookUrl')"
                    :placeholder="t('settings.webhookUrlPh')"
                  />
                </div>
                <Button @click="registerWebhook" :loading="webhookSaving" variant="soft-success" class="shrink-0 mb-[1px]">
                  <template #icon><Zap class="w-4 h-4" /></template>
                  {{ webhookSaving ? t('settings.registering') : t('settings.register') }}
                </Button>
              </div>

              <!-- Webhook Health Panel -->
              <div class="rounded-xl bg-main-bg border border-card-border/60 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-card-border/40">
                  <span class="text-xs font-semibold text-main-text/70 uppercase tracking-wider">{{ t('settings.webhookStatus') }}</span>
                  <button
                    @click="checkWebhookHealth"
                    class="flex items-center gap-1.5 text-[11px] font-semibold text-brand-blue hover:text-brand-blue/70 transition-colors"
                  >
                    <RefreshCcw class="w-3 h-3" :class="webhookLoading ? 'animate-spin' : ''" />
                    {{ t('settings.refreshHealth') }}
                  </button>
                </div>

                <div v-if="webhookLoading" class="flex justify-center items-center py-8">
                  <Loader2 class="w-5 h-5 animate-spin text-brand-blue" />
                </div>

                <div v-else-if="webhookHealth" class="divide-y divide-card-border/40">
                  <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-xs text-main-text/50">{{ t('settings.webhookUrl2') }}</span>
                    <span class="text-xs font-medium text-main-text break-all text-right max-w-[200px] truncate" :title="webhookHealth.url">
                      {{ webhookHealth.url || '—' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-xs text-main-text/50">{{ t('settings.pendingUpdates') }}</span>
                    <span
                      class="text-xs font-bold px-2 py-0.5 rounded-md"
                      :class="webhookHealth.pending_update_count > 0
                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                        : 'bg-nav-accent/5 text-main-text/50'"
                    >
                      {{ webhookHealth.pending_update_count }}
                    </span>
                  </div>
                  <div v-if="webhookHealth.last_error_date" class="px-4 py-3 bg-rose-50/50 dark:bg-rose-900/10">
                    <p class="text-[10px] text-rose-500 font-bold uppercase tracking-widest mb-1">{{ t('settings.lastError') }}</p>
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium leading-relaxed">{{ webhookHealth.last_error_message }}</p>
                    <p class="text-[10px] text-rose-400/70 mt-0.5">{{ formatDate(webhookHealth.last_error_date) }}</p>
                  </div>
                </div>

                <div v-else class="flex items-center justify-center py-8 text-xs text-main-text/30">
                  {{ t('settings.webhookNoData') }}
                </div>
              </div>
            </div>
          </div>

          <!-- Feedback Categories Card -->
          <div class="bg-card-bg border border-card-border rounded-2xl overflow-hidden">
            <div class="px-6 pt-5 pb-4 border-b border-card-border/50 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                <Tag class="w-5 h-5 text-amber-500" />
              </div>
              <div>
                <h3 class="text-sm font-semibold text-main-text">{{ t('settings.feedbackCategories') }}</h3>
                <p class="text-xs text-main-text/50 mt-0.5">{{ t('settings.feedbackCategoriesDesc') }}</p>
              </div>
            </div>

            <div class="p-6 space-y-4">
              <!-- Category tags -->
              <div class="flex flex-wrap gap-2 min-h-[40px]">
                <transition-group name="tag" tag="div" class="flex flex-wrap gap-2">
                  <span
                    v-for="cat in settings.feedback_categories"
                    :key="cat"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-main-bg border border-card-border text-xs font-medium text-main-text group transition-all duration-150 hover:border-rose-300 hover:bg-rose-50 dark:hover:bg-rose-900/10"
                  >
                    {{ cat }}
                    <button
                      @click="removeCategory(cat)"
                      class="text-main-text/30 hover:text-rose-500 transition-colors focus:outline-none"
                      :aria-label="`Remove ${cat}`"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </span>
                </transition-group>
                <span v-if="!settings.feedback_categories?.length" class="text-xs text-main-text/30 italic py-1">
                  {{ t('settings.noCategoriesYet') }}
                </span>
              </div>

              <!-- Add new category -->
              <div class="flex items-end gap-3 pt-1 border-t border-card-border/40">
                <div class="flex-1 min-w-0">
                  <FormField
                    v-model="newCategory"
                    :placeholder="t('settings.newCategoryPh')"
                    @keyup.enter="addCategory"
                  />
                </div>
                <Button @click="addCategory" variant="soft-primary" class="shrink-0 mb-[1px]" :icon="Plus">
                  {{ t('settings.add') }}
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useLanguageStore } from "@/stores/languageStore";
import { ref, onMounted, inject } from 'vue';
import axios from 'axios';

// UI Components
import PageTitle from '@/components/common/PageTitle.vue';
import FormField from '@/components/common/FormField.vue';
import Button from '@/components/common/Button.vue';

// Icons (lucide-vue-next)
import {
  Settings,
  Bot,
  Link2,
  Tag,
  Save,
  Plus,
  X,
  Zap,
  RefreshCcw,
  Loader2,
} from 'lucide-vue-next';

const languageStore = useLanguageStore();
const t = (k, p) => languageStore.translate(k, p);
const showToast = inject('showToast');

const loadingSettings = ref(true);

const settings = ref({
  bot_token: '',
  welcome_message: '',
  webhook_url: '',
  feedback_categories: []
});

const newCategory = ref('');
const saving = ref(false);

const webhookLoading = ref(false);
const webhookSaving = ref(false);
const webhookHealth = ref(null);

const fetchSettings = async () => {
  loadingSettings.value = true;
  try {
    const res = await axios.get('/settings');
    settings.value = res.data;
  } catch (err) {
    showToast(t('settings.loadFailed'), 'error');
  } finally {
    loadingSettings.value = false;
  }
};

const saveBotSettings = async () => {
  saving.value = true;
  try {
    await axios.put('/settings', settings.value);
    showToast(t('settings.saved'));
    fetchSettings();
  } catch (err) {
    showToast(t('settings.saveFailed'), 'error');
  } finally {
    saving.value = false;
  }
};

const checkWebhookHealth = async () => {
  webhookLoading.value = true;
  try {
    const res = await axios.get('/settings/webhook');
    webhookHealth.value = res.data;
  } catch (err) {
    showToast(t('settings.webhookFetchFailed'), 'error');
  } finally {
    webhookLoading.value = false;
  }
};

const registerWebhook = async () => {
  webhookSaving.value = true;
  try {
    await axios.post('/settings/webhook', { webhook_url: settings.value.webhook_url });
    showToast(t('settings.webhookRegistered'));
    checkWebhookHealth();
  } catch (err) {
    showToast(t('settings.webhookFailed'), 'error');
  } finally {
    webhookSaving.value = false;
  }
};

const addCategory = async () => {
  if (!newCategory.value.trim()) return;
  const cats = [...(settings.value.feedback_categories || [])];
  if (cats.includes(newCategory.value.trim())) {
    showToast(t('settings.categoryExists'), 'warning');
    return;
  }
  cats.push(newCategory.value.trim());
  try {
    await axios.put('/settings', { feedback_categories: cats });
    showToast(t('settings.categoryAdded'));
    newCategory.value = '';
    fetchSettings();
  } catch (err) {
    showToast(t('settings.categoryAddFailed'), 'error');
  }
};

const removeCategory = async (cat) => {
  const cats = (settings.value.feedback_categories || []).filter(c => c !== cat);
  try {
    await axios.put('/settings', { feedback_categories: cats });
    showToast(t('settings.categoryRemoved'));
    fetchSettings();
  } catch (err) {
    showToast(t('settings.categoryRemoveFailed'), 'error');
  }
};

const formatDate = (timestamp) => {
  if (!timestamp) return t('common.noData');
  const date = new Date(timestamp * 1000);
  return date.toLocaleString();
};

onMounted(() => {
  fetchSettings();
  checkWebhookHealth();
});
</script>

<style scoped>
.tag-enter-active,
.tag-leave-active {
  transition: all 0.2s ease;
}
.tag-enter-from,
.tag-leave-to {
  opacity: 0;
  transform: scale(0.85);
}
</style>
