<template>
  <div class="flex flex-col flex-1 min-w-0">
  <main class="grow p-4 md:p-6 lg:p-8 space-y-6 overflow-y-auto">
        <!-- Breadcrumb / Back button -->
        <div class="flex items-center gap-3">
          <router-link to="/users" class="icon-btn flex items-center justify-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm" :title="t('user.backToList')">
            <svg class="w-4.5 h-4.5 text-slate-600 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </router-link>
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ t('user.profile') }}</h2>
            <p class="text-xs text-slate-400 font-medium">{{ t('user.subtitle') }}</p>
          </div>
        </div>

        <!-- Pulse Skeleton Loader for User Profile -->
        <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-pulse">
          <!-- Column 1 Profile Info skeleton -->
          <div class="card p-6 space-y-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col items-center">
            <div class="w-24 h-24 rounded-full bg-slate-200 dark:bg-slate-800 mb-4" />
            <div class="h-4.5 w-40 bg-slate-200 dark:bg-slate-800 rounded mb-2" />
            <div class="h-3 w-28 bg-slate-200 dark:bg-slate-800 rounded mb-6" />
            
            <div class="w-full space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
              <div class="flex justify-between" v-for="i in 5" :key="i">
                <div class="h-3.5 w-20 bg-slate-200 dark:bg-slate-800 rounded" />
                <div class="h-3.5 w-24 bg-slate-200 dark:bg-slate-800 rounded" />
              </div>
            </div>
          </div>
          <!-- Column 2 Tabs skeleton -->
          <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-3 gap-4">
              <div v-for="i in 3" :key="i" class="card p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-center space-y-2">
                <div class="h-5 w-8 bg-slate-200 dark:bg-slate-800 rounded mx-auto" />
                <div class="h-3.5 w-20 bg-slate-200 dark:bg-slate-800 rounded mx-auto" />
              </div>
            </div>
            <div class="card p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4">
              <div class="h-4.5 w-32 bg-slate-200 dark:bg-slate-800 rounded" />
              <div class="space-y-3 pt-2">
                <div class="h-14 w-full bg-slate-200 dark:bg-slate-800 rounded-xl" v-for="i in 3" :key="i" />
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="error" class="card p-8 text-center max-w-lg mx-auto space-y-4">
          <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-950/30 flex items-center justify-center mx-auto text-red-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ t('user.failedLoad') }}</h3>
          <p class="text-sm text-slate-400">{{ t('user.failedLoadSub') }}</p>
          <router-link to="/users" class="btn-primary inline-block">{{ t('user.backToList') }}</router-link>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
          
          <!-- Column 1: Info Card -->
          <div class="card p-6 space-y-6 relative overflow-hidden bg-white dark:bg-slate-900 shadow-sm border border-slate-200/80 dark:border-slate-800/60">
            <div class="absolute top-0 left-0 right-0 h-20 bg-gradient-to-r from-amber-500/20 to-amber-600/10 dark:from-amber-500/10 dark:to-amber-950/20" />
            
            <div class="relative pt-6 flex flex-col items-center text-center">
              <img
                :src="profile.user.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${profile.user.telegramId}`"
                class="w-24 h-24 rounded-full border-4 border-white dark:border-slate-900 bg-slate-100 shadow-md object-cover mb-4 scale-100 hover:scale-105 transition-transform duration-200"
                alt="Avatar"
              />
              <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                {{ profile.user.firstName }} {{ profile.user.lastName }}
              </h3>
              <p class="text-xs text-slate-400 font-medium">@{{ profile.user.username || 'No Username' }}</p>
              
              <!-- Language badge -->
              <div class="mt-3">
                <span :class="getLanguageBadgeClass(profile.user.preferredLanguage || profile.user.language || 'am')">
                  <span class="text-xs">{{ getLanguageFlag(profile.user.preferredLanguage || profile.user.language || 'am') }}</span>
                  {{ getLanguageName(profile.user.preferredLanguage || profile.user.language || 'am') }}
                </span>
              </div>
            </div>

            <div class="divider" />

            <!-- Profile Info Fields -->
            <div class="space-y-4 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-semibold uppercase tracking-wider">{{ t('user.telegramId') }}</span>
                <span class="font-mono font-bold text-slate-700 dark:text-slate-300 select-all">{{ profile.user.telegramId }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-semibold uppercase tracking-wider">{{ t('user.chatId') }}</span>
                <span class="font-mono text-slate-600 dark:text-slate-400">{{ profile.user.chatId || 'N/A' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-semibold uppercase tracking-wider">{{ t('user.joined') }}</span>
                <span class="text-slate-600 dark:text-slate-400">{{ formatDate(profile.user.joinedAt) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-semibold uppercase tracking-wider">{{ t('user.lastActive') }}</span>
                <span class="text-slate-600 dark:text-slate-400">{{ formatDate(profile.user.lastActivity) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 font-semibold uppercase tracking-wider">{{ t('user.status') }}</span>
                <span class="badge" :class="(profile.user.active !== false)
                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800'
                  : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-800'">
                  {{ (profile.user.active !== false) ? t('user.active') : t('user.banned') }}
                </span>
              </div>
            </div>

            <div class="divider" />

            <!-- Profile actions -->
            <div class="flex flex-col gap-2">
              <button @click="showDirectNotifModal = true" class="btn-primary w-full flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                <span>{{ t('user.sendNotif') }}</span>
              </button>
              <button @click="toggleStatus"
                      class="px-4 py-2 text-sm font-semibold rounded-xl border transition-all duration-150 flex items-center justify-center gap-2"
                      :class="(profile.user.active !== false)
                        ? 'border-red-200 hover:bg-red-50 text-red-600 dark:border-red-900/40 dark:hover:bg-red-950/20 dark:text-red-400 dark:bg-slate-900'
                        : 'border-emerald-200 hover:bg-emerald-50 text-emerald-600 dark:border-emerald-900/40 dark:hover:bg-emerald-950/20 dark:text-emerald-400 dark:bg-slate-900'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <span>{{ (profile.user.active !== false) ? t('user.ban') : t('user.unban') }}</span>
              </button>
            </div>
          </div>

          <!-- Column 2: Stats & Tabs History -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
              <div class="card p-4 text-center bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60 hover:shadow-md transition-shadow">
                <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 font-mono">{{ profile.feedbacks.length }}</span>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ t('user.totalFeedback') }}</span>
              </div>
              <div class="card p-4 text-center bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60 hover:shadow-md transition-shadow">
                <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 font-mono">{{ profile.notifications.length }}</span>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ t('user.notifications') }}</span>
              </div>
              <div class="card p-4 text-center bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60 hover:shadow-md transition-shadow">
                <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 font-mono">{{ profile.replies.length }}</span>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ t('user.replies') }}</span>
              </div>
            </div>

            <!-- History Tabs -->
            <div class="card overflow-hidden bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
              <!-- Tabs Navigation -->
              <div class="flex border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20">
                <button
                  v-for="tab in ['feedbacks', 'notifications', 'replies']"
                  :key="tab"
                  @click="activeTab = tab"
                  class="px-5 py-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all duration-200"
                  :class="activeTab === tab
                    ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-900'
                    : 'border-transparent text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                >
                  {{ tab === 'feedbacks' ? t('user.tabFeedbacks') : (tab === 'notifications' ? t('user.tabNotifications') : t('user.tabReplies')) }}
                </button>
              </div>

              <!-- Tab Contents -->
              <div class="p-6">
                <!-- Feedbacks Tab -->
                <div v-if="activeTab === 'feedbacks'" class="space-y-4 tab-content-enter-active">
                  <div v-if="!profile.feedbacks.length" class="text-center py-10 text-slate-400 text-xs">
                    {{ t('user.noFeedbacks') }}
                  </div>
                  <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                      <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider pb-2">
                          <th class="pb-3">{{ t('feedback.category') }}</th>
                          <th class="pb-3">{{ t('feedback.message') }}</th>
                          <th class="pb-3">{{ t('feedback.status') }}</th>
                          <th class="pb-3">{{ t('feedback.date') }}</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <tr v-for="fb in profile.feedbacks" :key="fb.id" class="table-row-base">
                          <td class="py-3 pr-2 font-medium text-slate-800 dark:text-slate-200">
                            {{ fb.category }}
                          </td>
                          <td class="py-3 pr-2 max-w-xs truncate text-slate-600 dark:text-slate-300">
                            {{ fb.message }}
                          </td>
                          <td class="py-3 pr-2">
                            <span class="badge" :class="getStatusClasses(fb.status)">{{ fb.status }}</span>
                          </td>
                          <td class="py-3 text-slate-400 whitespace-nowrap">
                            {{ formatDate(fb.createdAt) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Notifications Tab -->
                <div v-if="activeTab === 'notifications'" class="space-y-4 tab-content-enter-active">
                  <div v-if="!profile.notifications.length" class="text-center py-10 text-slate-400 text-xs">
                    {{ t('user.noNotifications') }}
                  </div>
                  <div v-else class="space-y-3">
                    <div v-for="notif in profile.notifications" :key="notif.id" class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20 text-xs">
                      <div class="flex justify-between items-center mb-1">
                        <h4 class="font-bold text-slate-800 dark:text-slate-200">{{ notif.title }}</h4>
                        <span class="text-[10px] font-mono text-slate-400">{{ formatDate(notif.sentAt) }}</span>
                      </div>
                      <p class="text-slate-600 dark:text-slate-300 leading-relaxed">{{ notif.message }}</p>
                      <div class="mt-2 flex justify-between items-center text-[10px] text-slate-400 font-medium">
                        <span>Campaign ID: {{ notif.notificationId }}</span>
                        <span class="badge" :class="notif.status === 'Success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400'">
                          {{ notif.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Replies Tab -->
                <div v-if="activeTab === 'replies'" class="space-y-4 tab-content-enter-active">
                  <div v-if="!profile.replies.length" class="text-center py-10 text-slate-400 text-xs">
                    {{ t('user.noReplies') }}
                  </div>
                  <div v-else class="space-y-3">
                    <div v-for="reply in profile.replies" :key="reply.id" class="p-3.5 rounded-xl border border-amber-100 dark:border-amber-900/30 bg-amber-50/20 dark:bg-amber-950/5 text-xs">
                      <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-amber-700 dark:text-amber-400">Admin Response (by {{ reply.author }})</span>
                        <span class="text-[10px] font-mono text-slate-400">{{ formatDate(reply.createdAt) }}</span>
                      </div>
                      <p class="text-slate-700 dark:text-slate-200 leading-relaxed mb-3">{{ reply.message }}</p>
                      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800/80 text-[11px]">
                        <span class="block font-bold text-slate-400 uppercase tracking-wider mb-1">Original Feedback Category: {{ reply.feedbackCategory }}</span>
                        <p class="text-slate-500 italic">"{{ reply.feedbackMessage }}"</p>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

          </div>

        </div>      </main>

    <!-- Direct Notification Composer Modal -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="showDirectNotifModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ t('user.sendNotif') }}</h3>
              <!-- Larger Close Button -->
              <button @click="showDirectNotifModal = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                <XMarkIcon class="w-7 h-7" />
              </button>
            </div>
            
            <form @submit.prevent="sendDirectNotification" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('notifications.campaignTitle') }}</label>
                <input v-model="directNotif.title" type="text" required placeholder="e.g. የነገው ጉባኤ ማሳሰቢያ" class="input-base text-xs" />
              </div>
              
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('notifications.messageContent') }}</label>
                <textarea v-model="directNotif.message" rows="5" required placeholder="Write direct telegram notification content..." class="input-base text-xs resize-none" />
              </div>

              <div class="bg-blue-50/50 dark:bg-blue-950/10 p-3 rounded-xl border border-blue-100 dark:border-blue-900/40 text-[10px] text-blue-600 dark:text-blue-400 font-medium">
                Note: This notification is sent directly to this user's Telegram chat and logged in their delivery history.
              </div>

              <div class="flex justify-end gap-3 pt-2">
                <AppButton type="button" variant="ghost" size="sm" @click="showDirectNotifModal = false">{{ t('common.cancel') }}</AppButton>
                <AppButton type="submit" variant="primary" size="sm" :loading="sendingDirect">
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
        <div v-if="showBanConfirmModal && profile && profile.user" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-4"
                 :class="profile.user.active === false ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500'">
              <ExclamationTriangleIcon class="w-6 h-6 shrink-0" v-if="profile.user.active !== false" />
              <CheckCircleIcon class="w-6 h-6 shrink-0" v-else />
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                {{ profile.user.active === false ? t('user.unbanTitle') : t('user.banTitle') }}
              </h3>
            </div>
            
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
              {{ profile.user.active === false ? t('user.unbanConfirmText') : t('user.banConfirmText') }}
            </p>
            
            <div class="flex justify-end gap-3">
              <AppButton variant="ghost" size="sm" @click="showBanConfirmModal = false">{{ t('common.cancel') }}</AppButton>
              <AppButton
                :variant="profile.user.active === false ? 'success' : 'danger'"
                size="sm"
                @click="executeToggleStatus"
              >
                {{ profile.user.active === false ? t('user.unbanBtn') : t('user.banBtn') }}
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
import { ref, onMounted, inject } from 'vue';
import { useRoute } from 'vue-router';
import AppButton from '@/components/AppButton.vue';
import axios   from 'axios';

import { XMarkIcon, ExclamationTriangleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const languageStore = useLanguageStore();
const t = (k, p) => languageStore.translate(k, p);
const route = useRoute();

const loading = ref(true);
const error = ref(false);
const profile = ref(null);
const activeTab = ref('feedbacks');
const showDirectNotifModal = ref(false);
const sendingDirect = ref(false);
const showToast = inject('showToast');

const directNotif = ref({ title: '', message: '' });

// Ban confirmation modal ref
const showBanConfirmModal = ref(false);

const fetchProfile = async () => {
  loading.value = true;
  error.value = false;
  try {
    const res = await axios.get(`/users/${route.params.id}`);
    profile.value = res.data;
  } catch (err) {
    error.value = true;
    showToast('Failed to load profile details', 'error');
  } finally {
    loading.value = false;
  }
};

const toggleStatus = () => {
  showBanConfirmModal.value = true;
};

const executeToggleStatus = async () => {
  showBanConfirmModal.value = false;
  try {
    await axios.post(`/users/${profile.value.user.id}/toggle-status`);
    showToast(t('user.statusUpdated'));
    fetchProfile();
  } catch {
    showToast(t('user.statusUpdateFailed'), 'error');
  }
};

const sendDirectNotification = async () => {
  sendingDirect.value = true;
  try {
    await axios.post(`/users/${profile.value.user.id}/message`, {
      title: directNotif.value.title,
      message: directNotif.value.message
    });
    showToast(t('user.messageSent'));
    showDirectNotifModal.value = false;
    directNotif.value = { title: '', message: '' };
    fetchProfile();
  } catch {
    showToast(t('user.messageSendFailed'), 'error');
  } finally {
    sendingDirect.value = false;
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

const getLanguageName = (lang) => {
  switch (lang) {
    case 'am': return 'Amharic';
    case 'om': return 'Oromifa';
    case 'en': return 'English';
    default: return lang;
  }
};

const getStatusClasses = (s) => ({
  Resolved:      'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  Closed:        'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  'In Progress': 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Read:          'bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-800',
  New:           'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
}[s] ?? 'bg-slate-100 text-slate-500');

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';

onMounted(fetchProfile);
</script>
