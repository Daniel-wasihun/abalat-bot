<template>
  <div class="flex flex-col flex-1 min-w-0">
  <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ t('settings.title') }}</h2>
          <p class="text-xs text-slate-400 mt-0.5">{{ t('settings.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- Left column parameters panels -->
          <div class="lg:col-span-2 space-y-5">

            <!-- Bot credentials skeleton -->
            <div v-if="loadingSettings" class="card p-6 space-y-4 animate-pulse">
              <div class="h-4 skeleton rounded w-48" />
              <div class="space-y-3">
                <div class="h-2.5 skeleton rounded w-24" />
                <div class="h-10 skeleton rounded w-full" />
              </div>
              <div class="space-y-3">
                <div class="h-2.5 skeleton rounded w-36" />
                <div class="h-24 skeleton rounded w-full" />
              </div>
              <div class="flex justify-end">
                <div class="h-9 w-28 skeleton rounded-xl" />
              </div>
            </div>

            <!-- Bot credentials -->
            <div v-else class="card p-6 space-y-4">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ t('settings.botParams') }}</h3>
              <form @submit.prevent="saveBotSettings" class="space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.apiToken') }}</label>
                  <input v-model="settings.bot_token" type="password"
                         placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                         class="input-base" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.welcomeMsg') }}</label>
                  <textarea v-model="settings.welcome_message" rows="4"
                            :placeholder="t('settings.welcomeMsgPh')"
                            class="input-base resize-none" />
                </div>
                <div class="flex justify-end">
                  <button type="submit" :disabled="saving" class="btn-primary">
                    {{ saving ? t('common.saving') : t('settings.saveConfig') }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Webhook config -->
            <div class="card p-6 space-y-4">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ t('settings.webhookConfig') }}</h3>
              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.webhookUrl') }}</label>
                  <div class="flex gap-2">
                    <input v-model="settings.webhook_url" type="url"
                           :placeholder="t('settings.webhookUrlPh')"
                           class="input-base" />
                    <button @click="registerWebhook" :disabled="webhookSaving"
                            class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 rounded-xl shadow-md shadow-emerald-600/10 transition-colors shrink-0">
                      {{ webhookSaving ? t('settings.registering') : t('settings.register') }}
                    </button>
                  </div>
                </div>

                <!-- Webhook Checker Details -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ t('settings.webhookStatus') }}</span>
                    <button @click="checkWebhookHealth" class="text-[10px] font-bold text-primary-500 hover:underline">
                      {{ t('settings.refreshHealth') }}
                    </button>
                  </div>

                  <div v-if="webhookLoading" class="flex justify-center py-4">
                    <svg class="w-5 h-5 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                  </div>

                  <div v-else-if="webhookHealth" class="space-y-2 text-xs font-mono">
                    <div class="flex justify-between gap-4">
                      <span class="text-slate-400">{{ t('settings.webhookUrl2') }}</span>
                      <span class="text-slate-600 dark:text-slate-300 break-all text-right max-w-xs">{{ webhookHealth.url || 'None set' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">{{ t('settings.customCert') }}</span>
                      <span class="text-slate-600 dark:text-slate-300">{{ webhookHealth.has_custom_certificate ? t('settings.certYes') : t('settings.certNo') }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">{{ t('settings.pendingUpdates') }}</span>
                      <span class="font-bold text-slate-600 dark:text-slate-300"
                            :class="webhookHealth.pending_update_count > 0 ? 'text-amber-500' : 'text-slate-500'">
                        {{ webhookHealth.pending_update_count }}
                      </span>
                    </div>
                    <div class="flex justify-between gap-4 pt-1.5 border-t border-slate-200/50 dark:border-slate-800/50" v-if="webhookHealth.last_error_date">
                      <span class="text-red-500 font-semibold">{{ t('settings.lastError') }}</span>
                      <span class="text-red-400 text-right max-w-xs">
                        {{ webhookHealth.last_error_message }} ({{ formatDate(webhookHealth.last_error_date) }})
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Category list -->
            <div class="card p-6 space-y-4">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ t('settings.feedbackCategories') }}</h3>
              <div class="space-y-3">
                <div class="flex flex-wrap gap-2">
                  <span v-for="cat in settings.feedback_categories" :key="cat"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/50 dark:border-slate-700/50 text-xs text-slate-700 dark:text-slate-300">
                    <span>{{ cat }}</span>
                    <button @click="removeCategory(cat)" class="text-slate-400 hover:text-red-500 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </span>
                </div>

                <div class="flex gap-2">
                  <input v-model="newCategory" type="text" :placeholder="t('settings.newCategoryPh')" @keyup.enter="addCategory" class="input-base" />
                  <button @click="addCategory" class="btn-primary shrink-0">{{ t('settings.add') }}</button>
                </div>
              </div>
            </div>

          </div>

          <!-- Operators list panel -->
          <div class="card p-6 space-y-4 flex flex-col h-fit">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ t('settings.operators') }}</h3>
              <button v-if="authStore.isAdmin" @click="openAdminModal" class="btn-primary px-2.5 py-1 text-[11px] font-bold">
                {{ t('settings.addOperator') }}
              </button>
            </div>

            <!-- Skeleton loader for operators -->
            <div v-if="loadingOperators" class="space-y-3 animate-pulse">
              <div v-for="i in 3" :key="i" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 flex-1">
                  <div class="w-9 h-9 skeleton rounded-full shrink-0" />
                  <div class="space-y-1.5 flex-1">
                    <div class="h-3 skeleton rounded w-24" />
                    <div class="h-2.5 skeleton rounded w-32" />
                    <div class="h-4 w-16 skeleton rounded-full mt-1" />
                  </div>
                </div>
                <div class="flex gap-1 shrink-0">
                  <div class="w-7 h-7 skeleton rounded-lg" />
                  <div class="w-7 h-7 skeleton rounded-lg" />
                </div>
              </div>
            </div>

            <div v-else class="space-y-3">
              <div v-for="op in operators" :key="op.id"
                   class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                  <img :src="op.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${op.email}`"
                       class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 dark:border-slate-800 object-cover shrink-0" />
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ op.name }}</p>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">{{ op.email }}</p>
                    <span class="badge mt-1" :class="op.role === 'Super Admin'
                      ? 'bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-800'
                      : op.role === 'Admin'
                        ? 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800'
                        : 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'">
                      {{ op.role }}
                    </span>
                  </div>
                </div>

                <div class="flex gap-1 shrink-0" v-if="authStore.isSuperAdmin">
                  <button @click="editAdmin(op)" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="promptDeleteAdmin(op)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>

    <!-- Admin Form Modal Dialog -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showAdminModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">
              {{ adminForm.id ? t('settings.editOperator') : t('settings.addOperatorTitle') }}
            </h3>

            <form @submit.prevent="saveAdmin" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.operatorName') }}</label>
                <input v-model="adminForm.name" type="text" required class="input-base" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.operatorEmail') }}</label>
                <input v-model="adminForm.email" type="email" required class="input-base" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.operatorRole') }}</label>
                <select v-model="adminForm.role" required class="input-base">
                  <option value="Viewer">{{ t('settings.roleViewer') }}</option>
                  <option value="Admin">{{ t('settings.roleAdmin') }}</option>
                  <option value="Super Admin">{{ t('settings.roleSuperAdmin') }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('settings.operatorPassword') }}</label>
                <input v-model="adminForm.password" type="password"
                       :placeholder="adminForm.id ? t('settings.passwordPh') : '••••••••'"
                       :required="!adminForm.id" class="input-base" />
              </div>

              <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="showAdminModal = false" class="btn-ghost">{{ t('common.cancel') }}</button>
                <button type="submit" class="btn-primary">{{ t('settings.saveOperator') }}</button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Delete Operator Confirmation Modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="deleteConfirmOp" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/40 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ t('settings.confirmDeleteOp') }}</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ deleteConfirmOp.name }}</p>
              </div>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ t('settings.confirmDeleteOpMsg') }}</p>
            <div class="flex justify-end gap-3 pt-1">
              <button @click="deleteConfirmOp = null" class="btn-ghost text-sm">{{ t('common.cancel') }}</button>
              <button @click="confirmDeleteAdmin" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-500 rounded-xl transition-colors">
                {{ t('settings.deleteOperator') }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../i18n.js';
import axios from 'axios';

const { t } = useI18n();
const authStore = useAuthStore();
const showToast = inject('showToast');

const loadingSettings = ref(true);
const loadingOperators = ref(true);

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

const operators = ref([]);
const showAdminModal = ref(false);
const adminForm = ref({ id: '', name: '', email: '', role: 'Viewer', password: '' });
const deleteConfirmOp = ref(null);

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

const fetchOperators = async () => {
  if (!authStore.isAdmin) { loadingOperators.value = false; return; }
  loadingOperators.value = true;
  try {
    const res = await axios.get('/admins');
    operators.value = res.data;
  } catch (err) {
    console.error('Failed to load admin operators:', err);
  } finally {
    loadingOperators.value = false;
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
  const cats = [...settings.value.feedback_categories];
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
  const cats = settings.value.feedback_categories.filter(c => c !== cat);
  try {
    await axios.put('/settings', { feedback_categories: cats });
    showToast(t('settings.categoryRemoved'));
    fetchSettings();
  } catch (err) {
    showToast(t('settings.categoryRemoveFailed'), 'error');
  }
};

const openAdminModal = () => {
  adminForm.value = { id: '', name: '', email: '', role: 'Viewer', password: '' };
  showAdminModal.value = true;
};

const editAdmin = (op) => {
  adminForm.value = { id: op.id, name: op.name, email: op.email, role: op.role, password: '' };
  showAdminModal.value = true;
};

const promptDeleteAdmin = (op) => {
  if (op.id === authStore.admin?.id) {
    showToast(t('settings.cannotDeleteSelf'), 'error');
    return;
  }
  deleteConfirmOp.value = op;
};

const confirmDeleteAdmin = async () => {
  const op = deleteConfirmOp.value;
  deleteConfirmOp.value = null;
  try {
    await axios.delete(`/admins/${op.id}`);
    showToast(t('settings.operatorDeleted'));
    fetchOperators();
  } catch (err) {
    showToast(t('settings.operatorDeleteFailed'), 'error');
  }
};

const saveAdmin = async () => {
  try {
    if (adminForm.value.id) {
      await axios.put(`/admins/${adminForm.value.id}`, adminForm.value);
      showToast(t('settings.operatorUpdated'));
    } else {
      await axios.post('/admins', adminForm.value);
      showToast(t('settings.operatorCreated'));
    }
    showAdminModal.value = false;
    fetchOperators();
  } catch (err) {
    showToast(err.response?.data?.errors?.email?.[0] || t('settings.operatorSaveFailed'), 'error');
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
  fetchOperators();
});
</script>
