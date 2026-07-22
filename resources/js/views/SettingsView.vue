<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">System Settings</h2>
          <p class="text-xs text-slate-400 mt-0.5">Configure Telegram Bot, Webhooks, Categories and system operators</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- Left column parameters panels -->
          <div class="lg:col-span-2 space-y-5">

            <!-- Bot credentials -->
            <div class="card p-6 space-y-4">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Telegram Bot Parameters</h3>
              <form @submit.prevent="saveBotSettings" class="space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">API Token</label>
                  <input v-model="settings.bot_token" type="password"
                         placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                         class="input-base" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Welcome Message</label>
                  <textarea v-model="settings.welcome_message" rows="4"
                            placeholder="Message sent to new chatbot users…"
                            class="input-base resize-none" />
                </div>
                <div class="flex justify-end">
                  <button type="submit" :disabled="saving" class="btn-primary">
                    {{ saving ? 'Saving…' : 'Save Config' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Webhook config -->
            <div class="card p-6 space-y-4">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Webhook Configuration</h3>
              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Webhook URL Endpoint</label>
                  <div class="flex gap-2">
                    <input v-model="settings.webhook_url" type="url"
                           placeholder="https://yourdomain.com/api/telegram/webhook"
                           class="input-base" />
                    <button @click="registerWebhook" :disabled="webhookSaving"
                            class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 rounded-xl shadow-md shadow-emerald-600/10 transition-colors shrink-0">
                      {{ webhookSaving ? 'Registering…' : 'Register' }}
                    </button>
                  </div>
                </div>

                <!-- Webhook Checker Details -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Webhook Status Checker</span>
                    <button @click="checkWebhookHealth" class="text-[10px] font-bold text-primary-500 hover:underline">
                      Refresh Health
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
                      <span class="text-slate-400">URL:</span>
                      <span class="text-slate-600 dark:text-slate-300 break-all text-right max-w-xs">{{ webhookHealth.url || 'None set' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">Custom Cert:</span>
                      <span class="text-slate-600 dark:text-slate-300">{{ webhookHealth.has_custom_certificate ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-400">Pending Updates:</span>
                      <span class="font-bold text-slate-600 dark:text-slate-300"
                            :class="webhookHealth.pending_update_count > 0 ? 'text-amber-500' : 'text-slate-500'">
                        {{ webhookHealth.pending_update_count }}
                      </span>
                    </div>
                    <div class="flex justify-between gap-4 pt-1.5 border-t border-slate-200/50 dark:border-slate-800/50" v-if="webhookHealth.last_error_date">
                      <span class="text-red-500 font-semibold">Last Error:</span>
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
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Feedback Categories</h3>
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
                  <input v-model="newCategory" type="text" placeholder="Type new category name…" @keyup.enter="addCategory" class="input-base" />
                  <button @click="addCategory" class="btn-primary shrink-0">Add</button>
                </div>
              </div>
            </div>

          </div>

          <!-- Operators list panel -->
          <div class="card p-6 space-y-4 flex flex-col h-fit">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">System Operators</h3>
              <button v-if="authStore.isAdmin" @click="openAdminModal" class="btn-primary px-2.5 py-1 text-[11px] font-bold">
                Add
              </button>
            </div>

            <div class="space-y-3">
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
                  <button @click="deleteAdmin(op)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
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
    </div>

    <!-- Admin Form Modal Dialog -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showAdminModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">
              {{ adminForm.id ? 'Edit Operator' : 'Add System Operator' }}
            </h3>

            <form @submit.prevent="saveAdmin" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Name</label>
                <input v-model="adminForm.name" type="text" required class="input-base" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Email Address</label>
                <input v-model="adminForm.email" type="email" required class="input-base" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Operator Role</label>
                <select v-model="adminForm.role" required class="input-base">
                  <option value="Viewer">Viewer (Read-only)</option>
                  <option value="Admin">Admin (Manage boxes & announcements)</option>
                  <option value="Super Admin">Super Admin (All actions & operators)</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Password</label>
                <input v-model="adminForm.password" type="password"
                       :placeholder="adminForm.id ? 'Leave blank to retain password' : '••••••••'"
                       :required="!adminForm.id" class="input-base" />
              </div>

              <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="showAdminModal = false" class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary">Save Operator</button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </teleport>

  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import Navbar from '../components/Navbar.vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const sidebarOpen = ref(false);
const authStore = useAuthStore();
const showToast = inject('showToast');

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

const fetchSettings = async () => {
  try {
    const res = await axios.get('/settings');
    settings.value = res.data;
  } catch (err) {
    showToast('Failed to load system settings', 'error');
  }
};

const fetchOperators = async () => {
  if (!authStore.isAdmin) return;
  try {
    const res = await axios.get('/admins');
    operators.value = res.data;
  } catch (err) {
    console.error('Failed to load admin operators:', err);
  }
};

const saveBotSettings = async () => {
  saving.value = true;
  try {
    await axios.put('/settings', settings.value);
    showToast('Telegram Bot settings saved successfully');
    fetchSettings();
  } catch (err) {
    showToast('Failed to save settings', 'error');
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
    showToast('Failed to fetch webhook info from Telegram', 'error');
  } finally {
    webhookLoading.value = false;
  }
};

const registerWebhook = async () => {
  webhookSaving.value = true;
  try {
    await axios.post('/settings/webhook', { webhook_url: settings.value.webhook_url });
    showToast('Telegram webhook URL registered successfully');
    checkWebhookHealth();
  } catch (err) {
    showToast('Failed to set webhook URL', 'error');
  } finally {
    webhookSaving.value = false;
  }
};

const addCategory = async () => {
  if (!newCategory.value.trim()) return;
  const cats = [...settings.value.feedback_categories];
  if (cats.includes(newCategory.value.trim())) {
    showToast('Category already exists!', 'warning');
    return;
  }
  cats.push(newCategory.value.trim());
  try {
    await axios.put('/settings', { feedback_categories: cats });
    showToast('Category added successfully');
    newCategory.value = '';
    fetchSettings();
  } catch (err) {
    showToast('Failed to add category', 'error');
  }
};

const removeCategory = async (cat) => {
  const cats = settings.value.feedback_categories.filter(c => c !== cat);
  try {
    await axios.put('/settings', { feedback_categories: cats });
    showToast('Category removed successfully');
    fetchSettings();
  } catch (err) {
    showToast('Failed to delete category', 'error');
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

const deleteAdmin = async (op) => {
  if (op.id === authStore.admin?.id) {
    showToast('You cannot delete your own account!', 'error');
    return;
  }
  if (!confirm('Are you sure you want to delete this operator?')) return;
  try {
    await axios.delete(`/admins/${op.id}`);
    showToast('Operator deleted successfully');
    fetchOperators();
  } catch (err) {
    showToast('Failed to delete operator', 'error');
  }
};

const saveAdmin = async () => {
  try {
    if (adminForm.value.id) {
      await axios.put(`/admins/${adminForm.value.id}`, adminForm.value);
      showToast('Operator updated successfully');
    } else {
      await axios.post('/admins', adminForm.value);
      showToast('New operator created successfully');
    }
    showAdminModal.value = false;
    fetchOperators();
  } catch (err) {
    showToast(err.response?.data?.errors?.email?.[0] || 'Failed to save operator details', 'error');
  }
};

const formatDate = (timestamp) => {
  if (!timestamp) return 'N/A';
  const date = new Date(timestamp * 1000);
  return date.toLocaleString();
};

onMounted(() => {
  fetchSettings();
  checkWebhookHealth();
  fetchOperators();
});
</script>
