<template>
  <div class="flex flex-col flex-1 min-w-0">
    <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

      <!-- Page header -->
      <PageTitle
        :title="t('users.title')"
        :subtitle="t('users.subtitle')"
        :icon="UserGroupIcon"
        icon-bg-class="bg-indigo-500/10"
        icon-color-class="text-indigo-500"
      />

      <!-- Search & Filter bar (LMS-matching TableToolbar) -->
      <TableToolbar
        v-model="search"
        :placeholder="t('users.searchPlaceholder')"
        :show-filters="showFilters"
        :has-active-filters="filterLang !== '' || filterStatus !== ''"
        :filter-label="t('common.filters')"
        :reset-label="t('common.reset')"
        :loading="loadingUsers"
        @update:model-value="debouncedFetch"
        @toggle-filters="showFilters = !showFilters"
        @reset="clearFilters"
      >
        <template #filters>
          <FormSelect
            v-model="filterLang"
            :options="[{ value: '', label: t('users.allLanguages') }, ...languageOptions]"
            :placeholder="t('users.allLanguages')"
            @change="fetchUsers"
          />
          <FormSelect
            v-model="filterStatus"
            :options="[{ value: '', label: t('users.allStatuses') }, { value: 'true', label: t('user.active') }, { value: 'false', label: t('user.banned') }]"
            :placeholder="t('users.allStatuses')"
            @change="fetchUsers"
          />
        </template>
      </TableToolbar>

      <!-- Users table -->
      <div class="card overflow-hidden">
        <DataTable
          :items="users"
          :columns="columns"
          :loading="loadingUsers"
          :empty-message="t('users.empty')"
          :empty-desc="t('users.emptyDesc')"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          :pagination="{
            currentPage: pagination.current_page,
            lastPage: pagination.last_page || 1,
            total: pagination.total,
            perPage: pagination.per_page,
          }"
          @sort="handleSort"
          @page-change="changePage"
          @per-page-change="changePerPage"
        >
          <!-- Telegram ID -->
          <template #cell-telegramId="{ item }">
            <span class="font-mono font-semibold text-slate-600 dark:text-slate-300 select-all">{{ item.telegramId }}</span>
          </template>

          <!-- User -->
          <template #cell-user="{ item }">
            <div class="font-semibold text-slate-800 dark:text-slate-200">
              {{ item.firstName }} {{ item.lastName }}
            </div>
            <a v-if="item.username" :href="`https://t.me/${item.username}`" target="_blank" class="text-[10px] text-slate-400 font-mono hover:underline hover:text-amber-500">
              @{{ item.username }}
            </a>
            <span v-else class="text-[10px] text-slate-400 font-mono">—</span>
          </template>

          <!-- Language -->
          <template #cell-language="{ item }">
            <span :class="getLanguageBadgeClass(item.preferredLanguage || item.language || 'am')" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full">
              <span>{{ getLanguageFlag(item.preferredLanguage || item.language || 'am') }}</span>
              {{ (item.preferredLanguage || item.language || 'am').toUpperCase() }}
            </span>
          </template>

          <!-- Status -->
          <template #cell-status="{ item }">
            <span class="badge select-none text-[10px]"
              :class="item.active === false
              ? 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800'
              : 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800'">
              {{ item.active === false ? t('user.banned') : t('user.active') }}
            </span>
          </template>

          <!-- Feedbacks -->
          <template #cell-feedbackCount="{ item }">
            <span class="font-semibold text-slate-600 dark:text-slate-400 font-mono">{{ item.feedbackCount || 0 }}</span>
          </template>

          <!-- Joined -->
          <template #cell-joinedAt="{ item }">
            <span class="text-xs text-slate-400 whitespace-nowrap">{{ formatDate(item.joinedAt) }}</span>
          </template>

          <!-- Actions -->
          <template #cell-actions="{ item }">
            <ActionDropdown :item="item" :actions="getRowActions(item)" />
          </template>
        </DataTable>
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
                  To: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ targetUser.firstName }}</span>
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
                          class="input-base text-xs w-full resize-none" />
              </div>
              <div class="flex justify-end gap-3">
                <Button type="button" variant="ghost" @click="targetUser = null">{{ t('common.cancel') }}</Button>
                <Button type="submit" variant="primary" :loading="sending">
                  {{ t('user.sendNotif') }}
                </Button>
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
              <Button variant="ghost" @click="banConfirmItem = null">{{ t('common.cancel') }}</Button>
              <Button
                :variant="banConfirmItem.active === false ? 'primary' : 'danger'"
                @click="executeToggleStatus"
              >
                {{ banConfirmItem.active === false ? t('user.unbanBtn') : t('user.banBtn') }}
              </Button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { useLanguageStore } from "@/stores/languageStore";
import { useEnumI18n } from '@/bot_enums';
import { ref, computed, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/apiClient';

// Components
import PageTitle from '@/components/common/PageTitle.vue';
import TableToolbar from '@/components/common/TableToolbar.vue';
import FormSelect from '@/components/common/FormSelect.vue';
import DataTable from '@/components/common/DataTable.vue';
import ActionDropdown from '@/components/common/ActionDropdown.vue';
import Button from '@/components/common/Button.vue';

import { 
  UserGroupIcon, XMarkIcon, ExclamationTriangleIcon, 
  CheckCircleIcon, UserIcon, PaperAirplaneIcon, NoSymbolIcon 
} from '@heroicons/vue/24/outline';

const languageStore = useLanguageStore();
const t = (k: string, p?: any) => languageStore.translate(k, p);
const { languageOptions } = useEnumI18n();
const router = useRouter();

const users = ref<any[]>([]);
const search = ref('');
const filterLang = ref('');
const filterStatus = ref('');
const sortBy = ref('');
const sortOrder = ref<'asc' | 'desc'>('desc');
const showFilters = ref(sessionStorage.getItem('users_bot_filters_visible') === 'true');
const targetUser = ref<any>(null);
const dmText = ref('');
const sending = ref(false);
const loadingUsers = ref(false);
const showToast = inject('showToast') as any;

const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

const banConfirmItem = ref<any>(null);

const activeFilterCount = computed(() => {
  let c = 0;
  if (search.value) c++;
  if (filterLang.value) c++;
  if (filterStatus.value) c++;
  return c;
});

const clearFilters = () => {
  search.value = '';
  filterLang.value = '';
  filterStatus.value = '';
  pagination.value.current_page = 1;
  fetchUsers();
};

const columns = computed(() => [
  { key: 'telegramId', label: t('users.telegramId'), width: '120px', sortable: true  },
  { key: 'user',       label: t('users.name'),       width: '200px', sortable: true  },
  { key: 'language',   label: t('users.language'),   width: '100px', sortable: true  },
  { key: 'status',     label: t('users.status'),     width: '100px', sortable: true  },
  { key: 'feedbackCount', label: t('users.feedbacks'), width: '100px', sortable: false },
  { key: 'joinedAt',   label: t('users.joined'),     width: '120px', sortable: true  },
  { key: 'actions',    label: t('users.actions'),    width: '60px',  align: 'right' as const },
]);

const handleSort = (key: string) => {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortOrder.value = 'desc';
  }
  pagination.value.current_page = 1;
  fetchUsers();
};

const getRowActions = (user: any) => [
  {
    label: t('users.viewProfile'),
    icon: UserIcon,
    onClick: () => router.push(`/users/${user.id}`),
  },
  {
    label: t('user.sendNotif'),
    icon: PaperAirplaneIcon,
    onClick: () => openDM(user),
  },
  {
    label: user.active === false ? t('user.unban') : t('user.ban'),
    icon: user.active === false ? CheckCircleIcon : NoSymbolIcon,
    colorClass: user.active === false ? 'text-emerald-500' : 'text-red-500',
    onClick: () => toggleStatus(user),
  }
];

const fetchUsers = async () => {
  loadingUsers.value = true;
  try {
    const params: any = {
      search: search.value,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    };
    if (filterLang.value) params.language = filterLang.value;
    if (filterStatus.value) params.active = filterStatus.value;
    if (sortBy.value) {
      params.sort_by = sortBy.value;
      params.sort_order = sortOrder.value;
    }

    const res = await apiClient.get('/bot/users', { params });
    users.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
    if (res.data.meta) {
      pagination.value = res.data.meta;
    }
  } catch (err) {
    showToast?.('Failed to load users', 'error');
  } finally {
    loadingUsers.value = false;
  }
};

let timer: ReturnType<typeof setTimeout>;
const debouncedFetch = () => {
  clearTimeout(timer);
  pagination.value.current_page = 1;
  timer = setTimeout(fetchUsers, 350);
};

const changePage = (page: number) => {
  pagination.value.current_page = page;
  fetchUsers();
};

const changePerPage = (size: number) => {
  pagination.value.per_page = size;
  pagination.value.current_page = 1;
  fetchUsers();
};

const toggleStatus = (user: any) => {
  banConfirmItem.value = user;
};

const executeToggleStatus = async () => {
  if (!banConfirmItem.value) return;
  const user = banConfirmItem.value;
  banConfirmItem.value = null;
  try {
    await apiClient.post(`/bot/users/${user.id}/toggle-status`);
    showToast?.(t('user.statusUpdated'));
    fetchUsers();
  } catch {
    showToast?.(t('user.statusUpdateFailed'), 'error');
  }
};

const openDM = (user: any) => { targetUser.value = user; dmText.value = ''; };

const sendDM = async () => {
  sending.value = true;
  try {
    await apiClient.post(`/bot/users/${targetUser.value.id}/message`, { message: dmText.value });
    showToast?.(t('user.messageSent'));
    targetUser.value = null;
  } catch { 
    showToast?.(t('user.messageSendFailed'), 'error'); 
  } finally { 
    sending.value = false; 
  }
};

const getLanguageBadgeClass = (lang: string) => {
  switch (lang) {
    case 'am': return 'lang-badge-am bg-amber-50 text-amber-600 border border-amber-200';
    case 'en': return 'lang-badge-en bg-blue-50 text-blue-600 border border-blue-200';
    case 'om': return 'lang-badge-om bg-emerald-50 text-emerald-600 border border-emerald-200';
    default: return 'lang-badge-en bg-slate-50 text-slate-600 border border-slate-200';
  }
};

const getLanguageFlag = (lang: string) => {
  switch (lang) {
    case 'am': return '🇪🇹';
    case 'om': return '🇪🇹';
    case 'en': return '🇺🇸';
    default: return '🌐';
  }
};

const formatDate = (d: string) => d ? new Date(d).toLocaleDateString() : '—';

onMounted(() => {
  fetchUsers();
});
</script>
