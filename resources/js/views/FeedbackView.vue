<template>
  <div class="flex flex-col flex-1 min-w-0">
  <main class="grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <!-- Page header + export actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ t('feedback.title') }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">{{ t('feedback.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-2">
            <AppButton variant="ghost" size="sm" @click="exportData('csv')">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
              </svg>
              {{ t('feedback.exportCsv') }}
            </AppButton>
            <AppButton variant="ghost" size="sm" @click="exportData('pdf')">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ t('feedback.exportPdf') }}
            </AppButton>
          </div>
        </div>

        <!-- Filter row -->
        <div class="card p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
          <input v-model="filters.search" @input="debouncedFetch" type="text"
                 :placeholder="t('feedback.search')"
                 class="input-base text-xs" />

          <select v-model="filters.category" @change="fetchFeedback" class="input-base text-xs">
            <option value="">{{ t('feedback.allCategories') }}</option>
            <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>

          <select v-model="filters.language" @change="fetchFeedback" class="input-base text-xs">
            <option value="">{{ t('feedback.allLanguages') }}</option>
            <option v-for="opt in languageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>

          <select v-model="filters.priority" @change="fetchFeedback" class="input-base text-xs">
            <option value="">{{ t('feedback.allPriorities') }}</option>
            <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>

          <select v-model="filters.status" @change="fetchFeedback" class="input-base text-xs">
            <option value="">{{ t('feedback.allStatuses') }}</option>
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/60">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50 text-slate-400 font-semibold uppercase tracking-wide">
                  <th class="px-4 py-3">{{ t('feedback.sender') }}</th>
                  <th class="px-4 py-3">{{ t('feedback.language') }}</th>
                  <th class="px-4 py-3">{{ t('feedback.category') }}</th>
                  <th class="px-4 py-3 max-w-xs">{{ t('feedback.message') }}</th>
                  <th class="px-4 py-3">{{ t('feedback.priority') }}</th>
                  <th class="px-4 py-3">{{ t('feedback.status') }}</th>
                  <th class="px-4 py-3">{{ t('feedback.date') }}</th>
                  <th class="px-4 py-3 text-right">{{ t('feedback.action') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <!-- Skeleton rows while loading -->
                <template v-if="feedbackStore.loading">
                  <tr v-for="i in 8" :key="'sk-'+i" class="animate-pulse">
                    <td class="px-4 py-3">
                      <div class="space-y-1.5">
                        <div class="h-3 skeleton rounded w-28" />
                        <div class="h-2.5 skeleton rounded w-20" />
                      </div>
                    </td>
                    <td class="px-4 py-3"><div class="h-5 w-10 skeleton rounded-full" /></td>
                    <td class="px-4 py-3"><div class="h-3 skeleton rounded w-24" /></td>
                    <td class="px-4 py-3"><div class="h-3 skeleton rounded w-40" /></td>
                    <td class="px-4 py-3"><div class="h-5 w-14 skeleton rounded-full" /></td>
                    <td class="px-4 py-3"><div class="h-5 w-16 skeleton rounded-full" /></td>
                    <td class="px-4 py-3"><div class="h-3 skeleton rounded w-20" /></td>
                    <td class="px-4 py-3 text-right"><div class="h-6 w-6 skeleton rounded-lg ml-auto" /></td>
                  </tr>
                </template>
                <tr v-else-if="!feedbackList.length">
                  <td colspan="8" class="px-4 py-10 text-center text-slate-400">{{ t('feedback.empty') }}</td>
                </tr>
                <tr
                  v-else
                  v-for="item in feedbackList"
                  :key="item.id"
                  class="table-row-base"
                >
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <div>
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ item.userName || t('common.anonymous') }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">@{{ item.username || t('common.noData') }}</p>
                      </div>
                      <!-- Smart visible sticky notes indicator count badge -->
                      <span
                        v-if="item.internalNotes?.length"
                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold text-[9px] shrink-0"
                        title="Internal notes logged"
                      >
                        📝 {{ item.internalNotes.length }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span :class="getLanguageBadgeClass(item.language || 'am')">
                      <span>{{ getLanguageFlag(item.language || 'am') }}</span>
                      {{ (item.language || 'am').toUpperCase() }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium">
                    <span class="inline-flex items-center gap-1">
                      <span v-if="item.category === 'Prayer Request'">🙏</span>
                      <span v-else-if="item.category === 'Spiritual Education'">⛪</span>
                      <span v-else-if="item.category === 'Choir & Hymns'">🎵</span>
                      <span v-else-if="item.category === 'Liturgy & Service'">📜</span>
                      {{ tCategory(item.category) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 max-w-xs truncate text-slate-600 dark:text-slate-300">{{ item.message }}</td>
                  <td class="px-4 py-3">
                    <span class="badge" :class="getPriorityClasses(item.priority)">{{ tPriority(item.priority) }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="badge" :class="getStatusClasses(item.status)">{{ tStatus(item.status) }}</span>
                  </td>
                  <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ formatDate(item.createdAt) }}</td>
                  <td class="px-4 py-3 text-right relative">
                    <!-- Three vertical dots action trigger button -->
                    <button
                      @click.stop="toggleDropdown(item.id, $event)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                      title="Actions"
                    >
                      <EllipsisVerticalIcon class="w-5 h-5" />
                    </button>

                    <!-- Row actions context dropdown overlay menu -->
                    <div
                      v-if="activeDropdownId === item.id"
                      class="absolute right-4 top-10 mt-1 w-44 rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/60 py-1.5 z-40 text-left"
                    >
                      <button
                        @click="openDetail(item)"
                        class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                      >
                        <ReplyIcon class="w-4 h-4 text-slate-400" />
                        {{ t('feedback.action.viewReply') }}
                      </button>
                      <button
                        @click="openNotes(item)"
                        class="w-full flex items-center justify-between px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                      >
                        <span class="flex items-center gap-2">
                          <NotesIcon class="w-4 h-4 text-slate-400" />
                          <span>{{ t('feedback.modal.notes') }}</span>
                        </span>
                        <span v-if="item.internalNotes?.length" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold text-[9px]">
                          {{ item.internalNotes.length }}
                        </span>
                      </button>
                      <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                      <button
                        @click="confirmDelete(item)"
                        class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors"
                      >
                        <TrashIcon class="w-4 h-4" />
                        {{ t('feedback.action.delete') }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500">
            <div class="flex items-center gap-4">
              <span>{{ t('feedback.pageOf', { current: pagination.current_page, total: pagination.last_page || 1 }) }}</span>
              <div class="flex items-center gap-2">
                <span class="text-slate-400">Rows per page:</span>
                <select :value="pagination.per_page" @change="changePerPage($event.target.value)" class="input-base text-xs py-1 px-2 min-h-0 cursor-pointer focus:ring-amber-500/30">
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
            <div class="flex gap-2">
              <AppButton variant="ghost" size="sm" @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1">
                {{ t('feedback.prevPage') }}
              </AppButton>
              <AppButton variant="ghost" size="sm" @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page">
                {{ t('feedback.nextPage') }}
              </AppButton>
            </div>
          </div>
        </div>
      </main>

    <!-- ── View & Reply Modal (separated) ──────────────── -->
    <teleport to="body">
      <transition name="modal">
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
              <!-- Larger Close Button -->
              <button @click="selectedItem = null" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                <XMarkIcon class="w-7 h-7" />
              </button>
            </div>

            <!-- Scrollable body: Split 2-Column Grid Layout -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left Column: Message Content & Direct Telegram Reply -->
                <div class="lg:col-span-7 space-y-5">
                  <!-- Message content -->
                  <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">{{ t('feedback.modal.messageTitle') }}</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed">{{ selectedItem.message }}</p>

                    <!-- Genuine attachment display -->
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

                  <!-- Direct Telegram Reply Section -->
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

                    <!-- Past Replies Thread -->
                    <div v-if="selectedItem.replies?.length" class="space-y-2 mb-2 max-h-36 overflow-y-auto pr-1">
                      <div v-for="reply in selectedItem.replies" :key="reply.id" class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-amber-200/60 dark:border-amber-800/40 text-xs space-y-1">
                        <div class="flex justify-between text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                          <span>{{ t('feedback.modal.sentBy', { author: reply.author }) }}</span>
                          <span>{{ formatDate(reply.createdAt) }}</span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ reply.message }}</p>
                      </div>
                    </div>

                    <!-- Input and send button -->
                    <div class="space-y-2">
                      <textarea
                        v-model="replyMessage"
                        rows="2"
                        :placeholder="t('feedback.modal.replyPlaceholder')"
                        class="input-base text-xs w-full resize-none"
                      ></textarea>
                      <div class="flex justify-end">
                        <AppButton
                          variant="primary"
                          size="sm"
                          :loading="sendingReply"
                          :disabled="sendingReply || !replyMessage.trim()"
                          @click="sendReply"
                        >
                          {{ sendingReply ? t('feedback.modal.sending') : t('feedback.modal.sendReply') }}
                        </AppButton>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Status controls & Quick Info -->
                <div class="lg:col-span-5 space-y-5">
                  <!-- Status controls -->
                  <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800/60 space-y-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-semibold">{{ t('feedback.manage') }} Parameters</p>
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
                  
                  <!-- Internal notes summary button helper -->
                  <div class="p-4 rounded-xl border border-dashed border-amber-200 dark:border-amber-800 bg-amber-50/10 flex items-center justify-between text-xs">
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-200">Notes Log</p>
                      <p class="text-[10px] text-slate-400 mt-0.5">{{ selectedItem.internalNotes?.length || 0 }} notes registered</p>
                    </div>
                    <AppButton variant="outline" size="sm" @click="openNotes(selectedItem); selectedItem = null">
                      Manage Notes →
                    </AppButton>
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
      </transition>
    </teleport>

    <!-- ── Internal Notes Modal (separated) ────────────── -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="notesItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-xl max-h-[85vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-ethiopic flex items-center gap-2">
                  <span>{{ notesItem.userName || t('common.anonymous') }}</span>
                  <span class="text-xs text-slate-400">| {{ t('feedback.modal.notes') }}</span>
                </h3>
                <p class="text-[10px] text-slate-400 mt-0.5">ID: {{ notesItem.id }}</p>
              </div>
              <!-- Larger Close Button -->
              <button @click="notesItem = null" class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>

            <!-- Notes List -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
              <div class="space-y-2.5">
                <div v-for="note in notesItem.internalNotes" :key="note.id"
                     class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                  <div class="flex justify-between text-slate-400 font-semibold mb-1">
                    <span>{{ note.author }}</span><span>{{ formatDate(note.createdAt) }}</span>
                  </div>
                  <p class="text-slate-600 dark:text-slate-300 leading-normal">{{ note.note }}</p>
                </div>
                <p v-if="!notesItem.internalNotes?.length" class="text-xs text-slate-400 py-6 text-center">
                  {{ t('feedback.modal.noNotes') }}
                </p>
              </div>
            </div>

            <!-- Notes input & Close footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 space-y-3 shrink-0">
              <div class="flex gap-2">
                <input v-model="newNote" @keyup.enter="saveNote" type="text" :placeholder="t('feedback.modal.addNote')" class="input-base flex-1 text-xs" />
                <AppButton variant="primary" size="sm" :loading="savingNote" :disabled="savingNote || !newNote.trim()" @click="saveNote">{{ t('feedback.modal.add') }}</AppButton>
              </div>
              <div class="flex justify-between items-center pt-2">
                <AppButton variant="outline" size="sm" @click="openDetail(notesItem); notesItem = null">
                  ← Back to Message
                </AppButton>
                <AppButton variant="ghost" size="sm" @click="notesItem = null">{{ t('feedback.modal.close') }}</AppButton>
              </div>
            </div>

          </div>
        </div>
      </transition>
    </teleport>

    <!-- ── Custom Deletion Confirmation Modal ─────────── -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="deleteConfirmItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="modal-panel w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            
            <div class="flex items-center gap-3 text-red-600 mb-4">
              <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center shrink-0">
                <TrashIcon class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                {{ t('feedback.delete.confirmTitle') }}
              </h3>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
              {{ t('feedback.delete.confirmMessage') }}
            </p>

            <div class="flex justify-end gap-3">
              <AppButton variant="ghost" size="sm" @click="deleteConfirmItem = null">
                {{ t('feedback.delete.no') }}
              </AppButton>
              <AppButton variant="danger" size="sm" :loading="deleting" @click="deleteItemConfirmed">
                {{ t('feedback.delete.yes') }}
              </AppButton>
            </div>

          </div>
        </div>
      </transition>
    </teleport>

    <!-- Lightbox for image/video expansion -->
    <MediaLightbox v-model="lightboxOpen" :media="lightboxMedia" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import AppButton       from '../components/AppButton.vue';
import MediaAttachment from '../components/MediaAttachment.vue';
import MediaLightbox   from '../components/MediaLightbox.vue';
import { useFeedbackStore } from '../stores/feedback';
import { useI18n, useEnumI18n } from '../i18n.js';
import {
  EllipsisVerticalIcon,
  ChatBubbleLeftRightIcon as ReplyIcon,
  DocumentTextIcon as NotesIcon,
  TrashIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const {
  tCategory, tPriority, tStatus,
  categoryOptions, priorityOptions, statusOptions, languageOptions
} = useEnumI18n();

const feedbackStore = useFeedbackStore();

// Active Modal triggers
const selectedItem      = ref(null); // View & Reply Modal
const notesItem         = ref(null); // Internal Notes Modal
const deleteConfirmItem = ref(null); // Delete Confirm Modal

// Form inputs
const newNote       = ref('');
const replyMessage  = ref('');
const sendingReply  = ref(false);
const deleting      = ref(false);
const showToast     = inject('showToast');

// Context dropdown open menu tracking
const activeDropdownId = ref(null);

const toggleDropdown = (id, event) => {
  event.stopPropagation();
  if (activeDropdownId.value === id) {
    activeDropdownId.value = null;
  } else {
    activeDropdownId.value = id;
  }
};

// Lightbox state
const lightboxOpen  = ref(false);
const lightboxMedia = ref({ url: '', type: '', fileName: '' });

const openLightbox = ({ url, type, fileName }) => {
  lightboxMedia.value = { url, type: type || '', fileName: fileName || '' };
  lightboxOpen.value = true;
};

const feedbackList = computed(() => feedbackStore.feedbackList);
const pagination   = computed(() => feedbackStore.pagination);
const filters      = computed(() => feedbackStore.filters);

const fetchFeedback = async () => {
  try {
    await feedbackStore.fetchFeedback();
  } catch (_) {}
};

let debounceTimer;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { feedbackStore.fetchFeedback(true); }, 350);
};

const changePage = (p) => feedbackStore.setPage(p);
const changePerPage = (size) => feedbackStore.setPerPage(Number(size));

const openDetail = (item) => {
  selectedItem.value = { ...item };
  replyMessage.value = '';
  activeDropdownId.value = null;
};

const openNotes = (item) => {
  notesItem.value = { ...item };
  newNote.value = '';
  activeDropdownId.value = null;
};

const confirmDelete = (item) => {
  deleteConfirmItem.value = item;
  activeDropdownId.value = null;
};

const updateParameter = async (key) => {
  const target = selectedItem.value || notesItem.value;
  if (!target) return;
  try {
    await feedbackStore.updateParameter(target.id, key, target[key]);
    showToast(`${key.charAt(0).toUpperCase() + key.slice(1)} updated`);
  } catch { showToast(`Failed to update ${key}`, 'error'); }
};

const savingNote = ref(false);

const saveNote = async () => {
  const target = notesItem.value || selectedItem.value;
  if (!newNote.value.trim() || !target || savingNote.value) return;
  savingNote.value = true;
  try {
    const notes = await feedbackStore.addNote(target.id, newNote.value);
    target.internalNotes = notes;
    
    // Also sync the notes count in local feed list so table row matches instantly
    const match = feedbackList.value.find(f => f.id === target.id);
    if (match) match.internalNotes = notes;
    
    newNote.value = '';
    showToast(t('feedback.note.added'));
  } catch {
    showToast(t('feedback.note.failed'), 'error');
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
    showToast('Reply sent to user via Telegram successfully!');
  } catch (e) {
    const errorMsg = e.response?.data?.message || 'Failed to send reply to user';
    showToast(errorMsg, 'error');
  } finally {
    sendingReply.value = false;
  }
};

const deleteItemConfirmed = async () => {
  if (!deleteConfirmItem.value) return;
  deleting.value = true;
  try {
    await feedbackStore.deleteFeedback(deleteConfirmItem.value.id);
    showToast('Feedback deleted');
    deleteConfirmItem.value = null;
  } catch {
    showToast('Failed to delete', 'error');
  } finally {
    deleting.value = false;
  }
};

const exportData = (format) => {
  const q = new URLSearchParams({ ...filters.value, token: localStorage.getItem('admin_token') }).toString();
  window.open(`/api/feedback/export/${format}?${q}`, '_blank');
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

const getPriorityClasses = (p) => ({
  Critical: 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800',
  High:     'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
  Medium:   'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Low:      'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
}[p] ?? 'bg-slate-100 text-slate-500');

const getStatusClasses = (s) => ({
  Resolved:      'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  Closed:        'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  'In Progress': 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Read:          'bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-800',
  New:           'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
}[s] ?? 'bg-slate-100 text-slate-500');

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '';

const clickOutside = () => {
  activeDropdownId.value = null;
};

onMounted(() => {
  window.addEventListener('click', clickOutside);
  fetchFeedback();
});

onUnmounted(() => {
  window.removeEventListener('click', clickOutside);
});
</script>
