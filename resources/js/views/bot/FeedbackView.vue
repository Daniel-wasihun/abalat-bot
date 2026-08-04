<template>
  <div class="flex flex-col flex-1 min-w-0">
    <main class="grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

      <!-- Page header -->
      <PageTitle
        :title="t('feedback.title')"
        :subtitle="t('feedback.subtitle')"
        :icon="ChatBubbleLeftEllipsisIcon"
        icon-bg-class="bg-amber-500/10"
        icon-color-class="text-amber-500"
      >
        <template #actions>
          <Button variant="ghost" size="sm" :icon="ArrowDownTrayIcon" @click="exportData('csv')">
            {{ t('feedback.exportCsv') }}
          </Button>
          <Button variant="ghost" size="sm" :icon="DocumentIcon" @click="exportData('pdf')">
            {{ t('feedback.exportPdf') }}
          </Button>
        </template>
      </PageTitle>

      <!-- Table Toolbar (LMS-matching design) -->
      <TableToolbar
        v-model="feedbackStore.filters.search"
        :placeholder="t('feedback.search')"
        :show-filters="showFilters"
        :has-active-filters="activeFilterCount > 0"
        :filter-label="t('common.filters')"
        :reset-label="t('common.reset')"
        :loading="feedbackStore.loading"
        @update:model-value="debouncedFetch"
        @toggle-filters="showFilters = !showFilters"
        @reset="clearFilters"
      >
        <template #filters>
          <FormSelect
            v-model="feedbackStore.filters.category"
            :options="[{ value: '', label: t('feedback.allCategories') }, ...categoryOptions]"
            :placeholder="t('feedback.allCategories')"
            @change="fetchFeedback"
          />
          <FormSelect
            v-model="feedbackStore.filters.language"
            :options="[{ value: '', label: t('feedback.allLanguages') }, ...languageOptions]"
            :placeholder="t('feedback.allLanguages')"
            @change="fetchFeedback"
          />
          <FormSelect
            v-model="feedbackStore.filters.priority"
            :options="[{ value: '', label: t('feedback.allPriorities') }, ...priorityOptions]"
            :placeholder="t('feedback.allPriorities')"
            @change="fetchFeedback"
          />
          <FormSelect
            v-model="feedbackStore.filters.status"
            :options="[{ value: '', label: t('feedback.allStatuses') }, ...statusOptions]"
            :placeholder="t('feedback.allStatuses')"
            @change="fetchFeedback"
          />
        </template>
        <template #actions>
          <button
            @click="exportData('csv')"
            class="flex items-center gap-2 px-4 h-11 rounded-xl border border-card-border/60 text-main-text/60 hover:text-brand-blue hover:border-brand-blue/30 hover:bg-brand-blue/5 transition-all text-xs font-bold active:scale-95"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span class="hidden sm:inline">{{ t('feedback.exportCsv') }}</span>
          </button>
        </template>
      </TableToolbar>

      <!-- Data Table -->
      <DataTable
        :items="feedbackList"
        :columns="columns"
        :loading="feedbackStore.loading"
        :empty-message="t('feedback.empty')"
        :empty-title="t('feedback.empty')"
        :empty-desc="t('feedback.emptyDesc')"
        :sort-by="feedbackStore.filters.sort_by"
        :sort-order="feedbackStore.filters.sort_order"
        :pagination="{
          currentPage: pagination.current_page,
          lastPage: pagination.last_page || 1,
          total: pagination.total,
          perPage: pagination.per_page,
        }"
        @row-click="openDetail"
        @sort="feedbackStore.handleSort"
        @page-change="changePage"
        @per-page-change="changePerPage"
      >
        <!-- Sender -->
        <template #cell-sender="{ item }">
          <div class="flex items-center gap-2">
            <div>
              <p class="font-medium text-main-text text-sm">{{ item.userName || t('common.anonymous') }}</p>
              <p class="text-xs text-main-text/40 mt-0.5">@{{ item.username || '—' }}</p>
            </div>
            <span
              v-if="item.internalNotes?.length"
              class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold text-[9px] shrink-0"
              title="Internal notes"
            >📝 {{ item.internalNotes.length }}</span>
          </div>
        </template>

        <!-- Language -->
        <template #cell-language="{ item }">
          <span :class="getLanguageBadgeClass(item.language || 'am')" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full">
            {{ getLanguageFlag(item.language || 'am') }}
            {{ (item.language || 'am').toUpperCase() }}
          </span>
        </template>

        <!-- Category -->
        <template #cell-category="{ item }">
          <span class="inline-flex items-center gap-1 text-sm text-main-text/80">
            <span v-if="item.category === 'Prayer Request'">🙏</span>
            <span v-else-if="item.category === 'Spiritual Education'">⛪</span>
            <span v-else-if="item.category === 'Choir & Hymns'">🎵</span>
            <span v-else-if="item.category === 'Liturgy & Service'">📜</span>
            {{ tCategory(item.category) }}
          </span>
        </template>

        <!-- Message -->
        <template #cell-message="{ item }">
          <span class="text-sm text-main-text/70 max-w-xs truncate block">{{ item.message }}</span>
        </template>

        <!-- Priority -->
        <template #cell-priority="{ item }">
          <span class="badge text-xs" :class="getPriorityClasses(item.priority)">
            {{ tPriority(item.priority) }}
          </span>
        </template>

        <!-- Status -->
        <template #cell-status="{ item }">
          <span class="badge text-xs" :class="getStatusClasses(item.status)">
            {{ tStatus(item.status) }}
          </span>
        </template>

        <!-- Date -->
        <template #cell-createdAt="{ item }">
          <span class="text-xs text-main-text/50 whitespace-nowrap">{{ formatDate(item.createdAt) }}</span>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ item }">
          <ActionDropdown :item="item" :actions="getRowActions(item)" />
        </template>
      </DataTable>

    </main>

    <!-- ── View & Reply Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="selectedItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-4xl max-h-[88vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('feedback.modal.id') }}: {{ selectedItem.id }}</p>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-0.5 flex items-center gap-2">
                  <span>{{ selectedItem.userName || t('common.anonymous') }}</span>
                  <span :class="getLanguageBadgeClass(selectedItem.language || 'am')">
                    {{ getLanguageFlag(selectedItem.language || 'am') }} {{ (selectedItem.language || 'am').toUpperCase() }}
                  </span>
                </h3>
              </div>
              <button @click="selectedItem = null" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                <XMarkIcon class="w-7 h-7" />
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- Left: Message + Reply -->
                <div class="lg:col-span-7 space-y-5">
                  <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">{{ t('feedback.modal.messageTitle') }}</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed">{{ selectedItem.message }}</p>
                    <div v-if="selectedItem.attachmentUrl" class="mt-4 pt-4 border-t border-slate-200/60 dark:border-slate-800/60">
                      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">{{ t('feedback.modal.attachment') }}</p>
                      <MediaAttachment
                        :url="selectedItem.attachmentUrl"
                        :type="selectedItem.attachmentType || selectedItem.type || ''"
                        :file-name="selectedItem.fileName || ''"
                        @expand="openLightbox"
                      />
                    </div>
                  </div>

                  <!-- Reply section -->
                  <div class="p-4 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 space-y-3">
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        {{ t('feedback.modal.replyTitle', { name: selectedItem.userName || t('common.anonymous') }) }}
                      </p>
                      <span v-if="selectedItem.telegramMessageId" class="text-[10px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full">
                        {{ t('feedback.modal.directReply') }}
                      </span>
                    </div>
                    <div v-if="selectedItem.replies?.length" class="space-y-2 mb-2 max-h-36 overflow-y-auto pr-1">
                      <div v-for="reply in selectedItem.replies" :key="reply.id" class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-amber-200/60 dark:border-amber-800/40 text-xs space-y-1">
                        <div class="flex justify-between text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                          <span>{{ t('feedback.modal.sentBy', { author: reply.author }) }}</span>
                          <span>{{ formatDate(reply.createdAt) }}</span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ reply.message }}</p>
                      </div>
                    </div>
                    <div class="space-y-2">
                      <textarea v-model="replyMessage" rows="2" :placeholder="t('feedback.modal.replyPlaceholder')" class="input-base text-xs w-full resize-none"></textarea>
                      <div class="flex justify-end">
                        <AppButton variant="primary" size="sm" :loading="sendingReply" :disabled="sendingReply || !replyMessage.trim()" @click="sendReply">
                          {{ sendingReply ? t('feedback.modal.sending') : t('feedback.modal.sendReply') }}
                        </AppButton>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right: Controls -->
                <div class="lg:col-span-5 space-y-5">
                  <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800/60 space-y-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('feedback.manage') }} Parameters</p>
                    <div class="space-y-3">
                      <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('feedback.category') }}</label>
                        <select v-model="selectedItem.category" @change="updateParameter('category')" class="input-base text-xs">
                          <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('feedback.priority') }}</label>
                        <select v-model="selectedItem.priority" @change="updateParameter('priority')" class="input-base text-xs">
                          <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ t('feedback.status') }}</label>
                        <select v-model="selectedItem.status" @change="updateParameter('status')" class="input-base text-xs">
                          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="p-4 rounded-xl border border-dashed border-amber-200 dark:border-amber-800 bg-amber-50/10 flex items-center justify-between text-xs">
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-200">{{ t('feedback.notesLog') }}</p>
                      <p class="text-[10px] text-slate-400 mt-0.5">{{ selectedItem.internalNotes?.length || 0 }} notes registered</p>
                    </div>
                    <AppButton variant="outline" size="sm" @click="openNotes(selectedItem); selectedItem = null">Manage Notes →</AppButton>
                  </div>
                </div>

              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 shrink-0">
              <AppButton variant="ghost" size="sm" @click="selectedItem = null">{{ t('feedback.modal.close') }}</AppButton>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Internal Notes Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="notesItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-xl max-h-[85vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                  <span>{{ notesItem.userName || t('common.anonymous') }}</span>
                  <span class="text-xs text-slate-400">| {{ t('feedback.modal.notes') }}</span>
                </h3>
                <p class="text-[10px] text-slate-400 mt-0.5">ID: {{ notesItem.id }}</p>
              </div>
              <button @click="notesItem = null" class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
              <div class="space-y-2.5">
                <div v-for="note in notesItem.internalNotes" :key="note.id" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                  <div class="flex justify-between text-slate-400 font-semibold mb-1">
                    <span>{{ note.author }}</span><span>{{ formatDate(note.createdAt) }}</span>
                  </div>
                  <p class="text-slate-600 dark:text-slate-300 leading-normal">{{ note.note }}</p>
                </div>
                <p v-if="!notesItem.internalNotes?.length" class="text-xs text-slate-400 py-6 text-center">{{ t('feedback.modal.noNotes') }}</p>
              </div>
            </div>
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 space-y-3 shrink-0">
              <div class="flex gap-2">
                <input v-model="newNote" @keyup.enter="saveNote" type="text" :placeholder="t('feedback.modal.addNote')" class="input-base flex-1 text-xs" />
                <AppButton variant="primary" size="sm" :loading="savingNote" :disabled="savingNote || !newNote.trim()" @click="saveNote">{{ t('feedback.modal.add') }}</AppButton>
              </div>
              <div class="flex justify-between items-center pt-2">
                <AppButton variant="outline" size="sm" @click="openDetail(notesItem); notesItem = null">← Back to Message</AppButton>
                <AppButton variant="ghost" size="sm" @click="notesItem = null">{{ t('feedback.modal.close') }}</AppButton>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Delete Confirmation Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="deleteConfirmItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <div class="flex items-center gap-3 text-red-600 mb-4">
              <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center shrink-0">
                <TrashIcon class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ t('feedback.delete.confirmTitle') }}</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-6">{{ t('feedback.delete.confirmMessage') }}</p>
            <div class="flex justify-end gap-3">
              <AppButton variant="ghost" size="sm" @click="deleteConfirmItem = null">{{ t('feedback.delete.no') }}</AppButton>
              <AppButton variant="danger" size="sm" :loading="deleting" @click="deleteItemConfirmed">{{ t('feedback.delete.yes') }}</AppButton>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Lightbox -->
    <MediaLightbox v-model="lightboxOpen" :media="lightboxMedia" />
  </div>
</template>

<script setup lang="ts">
import { useLanguageStore } from "@/stores/languageStore";
import { useEnumI18n } from '@/bot_enums';
import { ref, computed, onMounted, inject } from 'vue';

// Reusable UI components
import PageTitle       from '@/components/common/PageTitle.vue';
import TableToolbar    from '@/components/common/TableToolbar.vue';
import FormSelect      from '@/components/common/FormSelect.vue';
import DataTable       from '@/components/common/DataTable.vue';
import ActionDropdown  from '@/components/common/ActionDropdown.vue';
import Button          from '@/components/common/Button.vue';

// Feature components
import AppButton       from '@/components/AppButton.vue';
import MediaAttachment from '@/components/MediaAttachment.vue';
import MediaLightbox   from '@/components/MediaLightbox.vue';

import { useFeedbackStore } from '@/stores/feedback';

import {
  ChatBubbleLeftEllipsisIcon,
  ArrowDownTrayIcon,
  DocumentIcon,
  ChatBubbleLeftRightIcon as ReplyIcon,
  DocumentTextIcon as NotesIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';

const languageStore = useLanguageStore();
const t = (k: string, p?: any) => languageStore.translate(k, p);
const {
  tCategory, tPriority, tStatus,
  categoryOptions, priorityOptions, statusOptions, languageOptions
} = useEnumI18n();

const feedbackStore = useFeedbackStore();
const showToast = inject('showToast') as any;

// UI state
const showFilters = ref(sessionStorage.getItem('feedback_filters_visible') === 'true');
const toggleFilters = () => {
  showFilters.value = !showFilters.value;
  sessionStorage.setItem('feedback_filters_visible', String(showFilters.value));
};

// Modal state
const selectedItem      = ref<any>(null);
const notesItem         = ref<any>(null);
const deleteConfirmItem = ref<any>(null);

// Form inputs
const newNote      = ref('');
const replyMessage = ref('');
const sendingReply = ref(false);
const deleting     = ref(false);
const savingNote   = ref(false);

// Lightbox
const lightboxOpen  = ref(false);
const lightboxMedia = ref({ url: '', type: '', fileName: '' });

const openLightbox = ({ url, type, fileName }: any) => {
  lightboxMedia.value = { url, type: type || '', fileName: fileName || '' };
  lightboxOpen.value = true;
};

// Computed from store
const feedbackList = computed(() => feedbackStore.feedbackList);
const pagination   = computed(() => feedbackStore.pagination);

const activeFilterCount = computed(() => {
  const f = feedbackStore.filters;
  return [f.category, f.language, f.priority, f.status].filter(v => v !== '').length;
});

const clearFilters = () => {
  feedbackStore.filters.search   = '';
  feedbackStore.filters.category = '';
  feedbackStore.filters.language = '';
  feedbackStore.filters.priority = '';
  feedbackStore.filters.status   = '';
  feedbackStore.filters.sort_by  = undefined;
  feedbackStore.filters.sort_order = 'desc';
  feedbackStore.fetchFeedback(true);
};

// Column definitions for DataTable — all with sortable where applicable
const columns = computed(() => [
  { key: 'sender',    label: t('feedback.sender'),   width: '200px', sortable: true  },
  { key: 'language',  label: t('feedback.language'),  width: '80px',  sortable: true  },
  { key: 'category',  label: t('feedback.category'),  width: '160px', sortable: true  },
  { key: 'message',   label: t('feedback.message'),   width: '240px', sortable: false },
  { key: 'priority',  label: t('feedback.priority'),  width: '100px', sortable: true  },
  { key: 'status',    label: t('feedback.status'),    width: '110px', sortable: true  },
  { key: 'createdAt', label: t('feedback.date'),      width: '110px', sortable: true  },
  { key: 'actions',   label: '',                       width: '60px',  align: 'right' as const },
]);

// Row action definitions
const getRowActions = (item: any) => [
  {
    label: t('feedback.action.viewReply'),
    icon: ReplyIcon,
    onClick: (row: any) => openDetail(row),
  },
  {
    label: t('feedback.modal.notes'),
    icon: NotesIcon,
    onClick: (row: any) => openNotes(row),
  },
  {
    label: t('feedback.action.delete'),
    icon: TrashIcon,
    colorClass: 'text-red-500',
    onClick: (row: any) => confirmDelete(row),
  },
];

// Fetch
const fetchFeedback = async () => {
  try { await feedbackStore.fetchFeedback(); } catch (_) {}
};

let debounceTimer: ReturnType<typeof setTimeout>;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => feedbackStore.fetchFeedback(true), 350);
};

const changePage    = (p: number) => feedbackStore.setPage(p);
const changePerPage = (size: number) => feedbackStore.setPerPage(Number(size));

// Modal helpers
const openDetail = (item: any) => {
  selectedItem.value = { ...item };
  replyMessage.value = '';
};
const openNotes = (item: any) => {
  notesItem.value = { ...item };
  newNote.value = '';
};
const confirmDelete = (item: any) => {
  deleteConfirmItem.value = item;
};

// Actions
const updateParameter = async (key: string) => {
  const target = selectedItem.value || notesItem.value;
  if (!target) return;
  try {
    await feedbackStore.updateParameter(target.id, key, target[key]);
    showToast?.(`${key.charAt(0).toUpperCase() + key.slice(1)} updated`);
  } catch {
    showToast?.(`Failed to update ${key}`, 'error');
  }
};

const saveNote = async () => {
  const target = notesItem.value || selectedItem.value;
  if (!newNote.value.trim() || !target || savingNote.value) return;
  savingNote.value = true;
  try {
    const notes = await feedbackStore.addNote(target.id, newNote.value);
    target.internalNotes = notes;
    const match = feedbackList.value.find((f: any) => f.id === target.id);
    if (match) (match as any).internalNotes = notes;
    newNote.value = '';
    showToast?.(t('feedback.note.added'));
  } catch {
    showToast?.(t('feedback.note.failed'), 'error');
  } finally {
    savingNote.value = false;
  }
};

const sendReply = async () => {
  if (!replyMessage.value.trim() || !selectedItem.value) return;
  sendingReply.value = true;
  try {
    const updated = await feedbackStore.replyToFeedback(selectedItem.value.id, replyMessage.value);
    selectedItem.value = { ...updated };
    replyMessage.value = '';
    showToast?.('Reply sent to user via Telegram successfully!');
  } catch (e: any) {
    showToast?.(e.response?.data?.message || 'Failed to send reply to user', 'error');
  } finally {
    sendingReply.value = false;
  }
};

const deleteItemConfirmed = async () => {
  if (!deleteConfirmItem.value) return;
  deleting.value = true;
  try {
    await feedbackStore.deleteFeedback(deleteConfirmItem.value.id);
    showToast?.('Feedback deleted');
    deleteConfirmItem.value = null;
  } catch {
    showToast?.('Failed to delete', 'error');
  } finally {
    deleting.value = false;
  }
};

const exportData = (format: string) => {
  const q = new URLSearchParams({ ...feedbackStore.filters, token: localStorage.getItem('admin_token') || '' }).toString();
  window.open(`/api/feedback/export/${format}?${q}`, '_blank');
};

// Badge helpers
const getLanguageBadgeClass = (lang: string) => {
  switch (lang) {
    case 'am': return 'lang-badge-am';
    case 'en': return 'lang-badge-en';
    case 'om': return 'lang-badge-om';
    default:   return 'lang-badge-en';
  }
};
const getLanguageFlag = (lang: string) => {
  switch (lang) {
    case 'am': return '🇪🇹';
    case 'om': return '🇪🇹';
    case 'en': return '🇺🇸';
    default:   return '🌐';
  }
};
const getPriorityClasses = (p: string) => ({
  Critical: 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800',
  High:     'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
  Medium:   'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Low:      'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
}[p] ?? 'bg-slate-100 text-slate-500');

const getStatusClasses = (s: string) => ({
  Resolved:      'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  Closed:        'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  'In Progress': 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Read:          'bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-800',
  New:           'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
}[s] ?? 'bg-slate-100 text-slate-500');

const formatDate = (d: string) => d ? new Date(d).toLocaleDateString() : '';

onMounted(() => {
  fetchFeedback();
});
</script>
