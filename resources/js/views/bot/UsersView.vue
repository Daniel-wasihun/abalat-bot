<template>
  <div class="flex flex-col flex-1 min-w-0">
  <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ t('users.title') }}</h2>
          <p class="text-xs text-slate-400 mt-0.5">{{ t('users.subtitle') }}</p>
        </div>

        <!-- Search & Filter bar -->
        <div class="card p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
          <div class="relative sm:col-span-2">
            <input v-model="search" @input="debouncedFetch" type="text"
                   :placeholder="t('users.searchPlaceholder')"
                   class="input-base pl-9 text-xs" />
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          
          <select v-model="filterLang" @change="fetchUsers" class="input-base text-xs">
            <option value="">{{ t('users.allLanguages') }}</option>
            <option v-for="opt in languageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <!-- Users table -->
        <div class="card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50 text-slate-400 font-semibold uppercase tracking-wide">
                  <th class="px-4 py-3">{{ t('users.telegramId') }}</th>
                  <th class="px-4 py-3">{{ t('users.name') }}</th>
                  <th class="px-4 py-3">{{ t('users.username') }}</th>
                  <th class="px-4 py-3">{{ t('users.language') }}</th>
                  <th class="px-4 py-3">{{ t('users.status') }}</th>
                  <th class="px-4 py-3 font-mono">{{ t('users.feedbacks') }}</th>
                  <th class="px-4 py-3">{{ t('users.joined') }}</th>
                  <th class="px-4 py-3 text-right">{{ t('users.actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <!-- Table Skeleton Loader -->
                <template v-if="loadingUsers">
                  <tr v-for="i in 5" :key="`skel-${i}`" class="animate-pulse border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <td class="px-4 py-4"><div class="h-3.5 w-20 bg-slate-200 dark:bg-slate-800 rounded" /></td>
                    <td class="px-4 py-4"><div class="h-3.5 w-28 bg-slate-200 dark:bg-slate-800 rounded" /></td>
                    <td class="px-4 py-4"><div class="h-3.5 w-20 bg-slate-200 dark:bg-slate-800 rounded" /></td>
                    <td class="px-4 py-4"><div class="h-5 w-14 bg-slate-200 dark:bg-slate-800 rounded-full" /></td>
                    <td class="px-4 py-4"><div class="h-5 w-16 bg-slate-200 dark:bg-slate-800 rounded-full" /></td>
                    <td class="px-4 py-4"><div class="h-3.5 w-8 bg-slate-200 dark:bg-slate-800 rounded mx-auto" /></td>
                    <td class="px-4 py-4"><div class="h-3.5 w-20 bg-slate-200 dark:bg-slate-800 rounded" /></td>
                    <td class="px-4 py-4 text-right">
                      <div class="flex justify-end gap-1.5">
                        <div class="h-7 w-20 bg-slate-200 dark:bg-slate-800 rounded-lg" />
                        <div class="h-7 w-20 bg-slate-200 dark:bg-slate-800 rounded-lg" />
                        <div class="h-7 w-12 bg-slate-200 dark:bg-slate-800 rounded-lg" />
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-else-if="!users.length">
                  <td colspan="8" class="px-4 py-10 text-center text-slate-400">{{ t('users.empty') }}</td>
                </tr>

                <tr v-else v-for="user in users" :key="user.id" class="table-row-base">
                  <td class="px-4 py-3 font-mono font-semibold text-slate-600 dark:text-slate-300 select-all">{{ user.telegramId }}</td>
                  <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">
                    {{ user.firstName }} {{ user.lastName }}
                  </td>
                  <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-mono">
                    <a v-if="user.username" :href="`https://t.me/${user.username}`" target="_blank" class="hover:underline hover:text-amber-500">
                      @{{ user.username }}
                    </a>
                    <span v-else>—</span>
                  </td>
                  <td class="px-4 py-3">
                    <span :class="getLanguageBadgeClass(user.preferredLanguage || user.language || 'am')">
                      <span>{{ getLanguageFlag(user.preferredLanguage || user.language || 'am') }}</span>
                      {{ (user.preferredLanguage || user.language || 'am').toUpperCase() }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="badge select-none"
                      :class="user.active === false
                      ? 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800'
                      : 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800'">
                      {{ user.active === false ? t('user.banned') : t('user.active') }}
                    </span>
                  </td>
                  <td class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-400 font-mono">{{ user.feedbackCount || 0 }}</td>
                  <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ formatDate(user.joinedAt) }}</td>
                  <td class="px-4 py-3 text-right relative">
                    <!-- Three vertical dots action trigger button -->
                    <button
                      @click.stop="toggleDropdown(user.id, $event)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                      title="Actions"
                    >
                      <EllipsisVerticalIcon class="w-5 h-5" />
                    </button>

                    <!-- Row actions context dropdown overlay menu -->
                    <div
                      v-if="activeDropdownId === user.id"
                      class="absolute right-4 top-10 mt-1 w-44 rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/60 py-1.5 z-40 text-left"
                    >
                      <button
                        @click="$router.push(`/users/${user.id}`); closeDropdown()"
                        class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                      >
                        <UserIcon class="w-4 h-4 text-slate-400" />
                        {{ t('users.viewProfile') }}
                      </button>
                      <button
                        @click="openDM(user); closeDropdown()"
                        class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                      >
                        <PaperAirplaneIcon class="w-4 h-4 text-slate-400" />
                        {{ t('user.sendNotif') }}
                      </button>
                      <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                      <button
                        @click="toggleStatus(user); closeDropdown()"
                        class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold transition-colors"
                        :class="user.active === false
                          ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/20'
                          : 'text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20'"
                      >
                        <component :is="user.active === false ? CheckCircleIcon : NoSymbolIcon" class="w-4 h-4" />
                        {{ user.active === false ? t('user.unban') : t('user.ban') }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination details -->
          <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500">
            <div class="flex items-center gap-4">
              <span>{{ t('feedback.pageOf', { current: pagination.current_page, total: pagination.last_page || 1 }) }}</span>
              <div class="flex items-center gap-2">
                <span class="text-slate-400 whitespace-nowrap">Per page:</span>
                <select v-model="pagination.per_page" @change="changePerPage" class="text-xs py-1 px-2 pr-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-0 focus:border-slate-200 dark:focus:border-slate-700 cursor-pointer">
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                      class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 transition-colors">
                {{ t('feedback.prevPage') }}
              </button>
              <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                      class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 transition-colors">
                {{ t('feedback.nextPage') }}
              </button>
            </div>
          </div>
        </div>
      </main>

    <!-- Direct Message Modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="targetUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ t('user.sendNotif') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">
                  To: <span class="font-semibold text-slate-600 dark:text-slate-300 font-ethiopic">{{ targetUser.firstName }}</span>
                  (ID: {{ targetUser.telegramId }})
                </p>
              </div>
              <button @click="targetUser = null" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Close">
                <XMarkIcon class="w-7 h-7" />
              </button>
            </div>
            
            <form @submit.prevent="sendDM" class="space-y-4">
              <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('feedback.message') }}</label>
                <textarea v-model="dmText" rows="4" required
                          placeholder="Write your message to this subscriber…"
                          class="input-base text-xs resize-none" />
              </div>
              <div class="flex justify-end gap-3">
                <AppButton type="button" variant="ghost" size="sm" @click="targetUser = null">{{ t('common.cancel') }}</AppButton>
                <AppButton type="submit" variant="primary" size="sm" :loading="sending">
                  {{ t('user.sendNotif') }}
                </AppButton>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Custom Block/Ban Confirmation Modal -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="banConfirmItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-4"
                 :class="banConfirmItem.active === false ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500'">
              <ExclamationTriangleIcon class="w-6 h-6 shrink-0" v-if="banConfirmItem.active !== false" />
              <CheckCircleIcon class="w-6 h-6 shrink-0" v-else />
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                {{ banConfirmItem.active === false ? t('user.unbanTitle') : t('user.banTitle') }}
              </h3>
            </div>
            
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
              {{ banConfirmItem.active === false ? t('user.unbanConfirmText') : t('user.banConfirmText') }}
            </p>
            
            <div class="flex justify-end gap-3">
              <AppButton variant="ghost" size="sm" @click="banConfirmItem = null">{{ t('common.cancel') }}</AppButton>
              <AppButton
                :variant="banConfirmItem.active === false ? 'success' : 'danger'"
                size="sm"
                @click="executeToggleStatus"
              >
                {{ banConfirmItem.active === false ? t('user.unbanBtn') : t('user.banBtn') }}
              </AppButton>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { useLanguageStore } from "@/stores/languageStore";
import { useEnumI18n } from '@/bot_enums';
import { ref, onMounted, onUnmounted, inject } from 'vue';
import AppButton from '@/components/AppButton.vue';
import axios   from 'axios';

import { XMarkIcon, ExclamationTriangleIcon, CheckCircleIcon, EllipsisVerticalIcon, UserIcon, PaperAirplaneIcon, NoSymbolIcon } from '@heroicons/vue/24/outline';

const languageStore = useLanguageStore();
const t = (k, p) => languageStore.translate(k, p);
const { languageOptions } = useEnumI18n();

const users       = ref([]);
const search      = ref('');
const filterLang  = ref('');
const targetUser  = ref(null);
const dmText      = ref('');
const sending     = ref(false);
const loadingUsers = ref(false);
const showToast   = inject('showToast');

const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

// Dropdown state
const activeDropdownId = ref(null);

const toggleDropdown = (id, event) => {
  if (activeDropdownId.value === id) {
    activeDropdownId.value = null;
  } else {
    activeDropdownId.value = id;
  }
};

const closeDropdown = () => {
  activeDropdownId.value = null;
};

// Global click handler to close dropdown when clicking outside
const handleClickOutside = () => {
  if (activeDropdownId.value) {
    activeDropdownId.value = null;
  }
};

// Modal ban confirmation tracking
const banConfirmItem = ref(null);

const fetchUsers = async () => {
  loadingUsers.value = true;
  try {
    const params = {
      search: search.value,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    };
    if (filterLang.value) {
      params.language = filterLang.value;
    }
    const res = await axios.get('/users', { params });
    users.value = res.data.data || res.data;
    if (res.data.meta) {
      pagination.value = res.data.meta;
    }
  } catch (err) {
    showToast('Failed to load subscribers', 'error');
  } finally {
    loadingUsers.value = false;
  }
};

let timer;
const debouncedFetch = () => {
  clearTimeout(timer);
  pagination.value.current_page = 1;
  timer = setTimeout(fetchUsers, 350);
};

const changePage = (page) => {
  pagination.value.current_page = page;
  fetchUsers();
};

const changePerPage = () => {
  pagination.value.current_page = 1;
  fetchUsers();
};

const toggleStatus = (user) => {
  banConfirmItem.value = user;
};

const executeToggleStatus = async () => {
  if (!banConfirmItem.value) return;
  const user = banConfirmItem.value;
  banConfirmItem.value = null;
  try {
    await axios.post(`/users/${user.id}/toggle-status`);
    showToast(t('user.statusUpdated'));
    fetchUsers();
  } catch {
    showToast(t('user.statusUpdateFailed'), 'error');
  }
};

const openDM = (user) => { targetUser.value = user; dmText.value = ''; };

const sendDM = async () => {
  sending.value = true;
  try {
    await axios.post(`/users/${targetUser.value.id}/message`, { message: dmText.value });
    showToast(t('user.messageSent'));
    targetUser.value = null;
  } catch { 
    showToast(t('user.messageSendFailed'), 'error'); 
  } finally { 
    sending.value = false; 
  }
};

const getLanguageBadgeClass = (lang) => {
  switch (lang) {
    case 'am': return 'lang-badge-am';
    case 'en': return 'lang-badge-en';
    case 'om': return 'lang-badge-om';
    default: return 'lang-badge-en';
  }
};

const getLanguageFlag = (lang) => {
  switch (lang) {
    case 'am': return '🇪🇹';
    case 'om': return '🇪🇹';
    case 'en': return '🇺🇸';
    default: return '🌐';
  }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';

onMounted(() => {
  fetchUsers();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
