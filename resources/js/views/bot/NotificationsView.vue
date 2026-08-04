<template>
  <div class="flex flex-col flex-1 min-w-0 font-sans">
    <main class="grow p-4 md:p-6 lg:p-8 space-y-6 overflow-y-auto">

      <!-- Page Header -->
      <PageTitle
        :title="t('notifications.title')"
        :subtitle="t('notifications.subtitle')"
        :icon="Megaphone"
        icon-bg-class="bg-brand-blue/10"
        icon-color-class="text-brand-blue"
      />

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- ── Campaign Wizard ──────────────────────────────────── -->
        <div class="xl:col-span-2 flex flex-col">
          <div class="bg-card-bg border border-card-border rounded-2xl overflow-hidden flex flex-col flex-1">

            <!-- Wizard Header -->
            <div class="px-6 pt-5 pb-4 border-b border-card-border/50">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-brand-blue/10 flex items-center justify-center shrink-0">
                    <Send class="w-5 h-5 text-brand-blue" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-main-text">{{ t('notifications.newCampaign') }}</h3>
                    <p class="text-xs text-main-text/50 mt-0.5">{{ t('notifications.newCampaignDesc') }}</p>
                  </div>
                </div>
              </div>

              <!-- Step Tracker -->
              <div class="flex items-center gap-0">
                <template v-for="(stepItem, idx) in steps" :key="stepItem.id">
                  <button
                    @click="step > stepItem.id && (step = stepItem.id)"
                    class="flex items-center gap-2 group"
                    :class="step > stepItem.id ? 'cursor-pointer' : 'cursor-default'"
                  >
                    <!-- Circle -->
                    <span
                      class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-300"
                      :class="step > stepItem.id
                        ? 'bg-brand-blue border-brand-blue text-white'
                        : step === stepItem.id
                          ? 'bg-brand-blue/10 border-brand-blue text-brand-blue'
                          : 'bg-main-bg border-card-border text-main-text/30'"
                    >
                      <CheckIcon v-if="step > stepItem.id" class="w-3.5 h-3.5" />
                      <span v-else>{{ stepItem.id }}</span>
                    </span>
                    <!-- Label -->
                    <span
                      class="text-xs font-medium transition-colors duration-200 hidden sm:block"
                      :class="step === stepItem.id
                        ? 'text-brand-blue dark:text-blue-400'
                        : step > stepItem.id
                          ? 'text-main-text/70'
                          : 'text-main-text/30'"
                    >
                      {{ stepItem.label }}
                    </span>
                  </button>
                  <!-- Connector line -->
                  <div
                    v-if="idx < steps.length - 1"
                    class="flex-1 h-px mx-3 transition-colors duration-300"
                    :class="step > stepItem.id ? 'bg-brand-blue' : 'bg-card-border'"
                  />
                </template>
              </div>
            </div>

            <!-- Step Content -->
            <div class="p-6 flex-1">
              <Transition name="step" mode="out-in">

                <!-- ── Step 1: Compose ── -->
                <div v-if="step === 1" key="step1" class="space-y-5">
                  <FormField
                    v-model="form.title"
                    :label="t('notifications.campaignTitle')"
                    :placeholder="t('notifications.campaignTitlePh')"
                    :required="true"
                  />
                  <FormField
                    v-model="form.message"
                    type="textarea"
                    :label="t('notifications.messageContent')"
                    :placeholder="t('notifications.messagePh')"
                    :rows="6"
                    :required="true"
                    :hint="t('notifications.messagePh')"
                  />
                  <!-- Character count -->
                  <div class="flex items-center justify-between text-[11px] text-main-text/40">
                    <span>{{ t('notifications.telegramMd') }}</span>
                    <span :class="form.message.length > 4000 ? 'text-rose-500 font-bold' : ''">
                      {{ form.message.length }} / 4096
                    </span>
                  </div>
                  <div class="flex justify-end pt-2 border-t border-card-border/40">
                    <Button
                      @click="step = 2"
                      :disabled="!form.title.trim() || !form.message.trim()"
                      variant="primary"
                    >
                      {{ t('notifications.continueAudience') }}
                      <ArrowRight class="w-4 h-4 shrink-0" />
                    </Button>
                  </div>
                </div>

                <!-- ── Step 2: Audience ── -->
                <div v-else-if="step === 2" key="step2" class="space-y-5">
                  <div>
                    <p class="text-[13px] font-medium text-main-text/70 mb-3 px-0.5">{{ t('notifications.chooseAudience') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <label
                        v-for="seg in segments"
                        :key="seg.value"
                        class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all duration-200 group"
                        :class="form.targetType === seg.value
                          ? 'border-brand-blue bg-brand-blue/5 dark:bg-brand-blue/10 shadow-[0_0_0_1px] shadow-brand-blue/30'
                          : 'border-card-border bg-main-bg hover:border-brand-blue/50 dark:hover:border-brand-blue/50'"
                        @click="form.targetType = seg.value"
                      >
                        <!-- Icon bubble -->
                        <div
                          class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-all duration-200"
                          :class="form.targetType === seg.value ? 'bg-brand-blue text-white' : 'bg-card-border/50 text-main-text/40 group-hover:text-brand-blue'"
                        >
                          <component :is="seg.icon" class="w-4.5 h-4.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-semibold text-main-text leading-tight">{{ seg.label }}</p>
                          <p class="text-[11px] text-main-text/50 mt-0.5 leading-relaxed">{{ seg.description }}</p>
                        </div>
                        <!-- Radio indicator -->
                        <span
                          class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 transition-all"
                          :class="form.targetType === seg.value ? 'border-brand-blue' : 'border-card-border'"
                        >
                          <span v-if="form.targetType === seg.value" class="w-2 h-2 rounded-full bg-brand-blue" />
                        </span>
                      </label>
                    </div>
                  </div>

                  <!-- Language filter -->
                  <Transition name="fade-slide">
                    <div v-if="form.targetType === 'language'" class="pt-1">
                      <FormSelect
                        v-model="form.targetValue"
                        :label="t('notifications.prefLanguage')"
                        :options="[
                          { value: '', label: t('notifications.selectLang') },
                          { value: 'am', label: '🇪🇹 አማርኛ (Amharic)' },
                          { value: 'en', label: '🇺🇸 English' },
                          { value: 'om', label: '🇪🇹 Afaan Oromoo' },
                        ]"
                        class="max-w-xs"
                      />
                    </div>
                  </Transition>

                  <!-- Category filter -->
                  <Transition name="fade-slide">
                    <div v-if="form.targetType === 'category'" class="pt-1">
                      <FormSelect
                        v-model="form.targetValue"
                        :label="t('notifications.catLabel')"
                        :options="[{ value: '', label: t('notifications.selectCategory') }, ...categoryOptions]"
                        class="max-w-xs"
                      />
                    </div>
                  </Transition>

                  <!-- Selected Subscribers -->
                  <Transition name="fade-slide">
                    <div v-if="form.targetType === 'selected'" class="space-y-3 pt-1">
                      <SearchInput v-model="subscriberSearch" :placeholder="t('notifications.searchSubscribers')" />

                      <div class="border border-card-border rounded-xl overflow-hidden">
                        <!-- Select all bar -->
                        <div class="flex items-center justify-between px-4 py-2.5 bg-main-bg border-b border-card-border/50">
                          <label class="flex items-center gap-2.5 cursor-pointer group" @click="toggleSelectAll">
                            <span
                              class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-all"
                              :class="selectedSubscribers.length === filteredSubscribers.length && filteredSubscribers.length > 0
                                ? 'bg-brand-blue border-brand-blue'
                                : 'bg-card-bg border-card-border group-hover:border-brand-blue/60'"
                            >
                              <svg v-if="selectedSubscribers.length === filteredSubscribers.length && filteredSubscribers.length > 0" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                              </svg>
                            </span>
                            <span class="text-xs font-medium text-main-text/70">{{ t('notifications.selectAll') }}</span>
                          </label>
                          <span class="text-[11px] font-semibold text-brand-blue dark:text-blue-400">
                            {{ t('notifications.selectedCount', { count: selectedSubscribers.length }) }}
                          </span>
                        </div>
                        <!-- Subscriber list -->
                        <div class="max-h-52 overflow-y-auto custom-scrollbar divide-y divide-card-border/40">
                          <label
                            v-for="s in filteredSubscribers"
                            :key="s.id"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-nav-accent/5 cursor-pointer transition-colors group"
                          >
                            <!-- Custom checkbox -->
                            <span
                              class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-all"
                              :class="selectedSubscribers.includes(s.telegramId)
                                ? 'bg-brand-blue border-brand-blue'
                                : 'bg-card-bg border-card-border group-hover:border-brand-blue/60'"
                            >
                              <svg v-if="selectedSubscribers.includes(s.telegramId)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                              </svg>
                            </span>
                            <input type="checkbox" :value="s.telegramId" v-model="selectedSubscribers" class="sr-only" />
                            <!-- Avatar -->
                            <div class="w-7 h-7 rounded-full bg-brand-blue/10 flex items-center justify-center shrink-0">
                              <span class="text-xs font-bold text-brand-blue">
                                {{ (s.firstName || '?').charAt(0).toUpperCase() }}
                              </span>
                            </div>
                            <div class="flex-1 min-w-0">
                              <p class="text-sm font-medium text-main-text truncate">{{ s.firstName }} {{ s.lastName }}</p>
                              <p class="text-[11px] text-main-text/40">@{{ s.username || 'no-username' }}</p>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-main-bg border border-card-border text-main-text/50">
                              {{ (s.preferredLanguage || 'am').toUpperCase() }}
                            </span>
                          </label>
                          <div v-if="filteredSubscribers.length === 0" class="py-8 text-center text-xs text-main-text/30">
                            {{ t('notifications.noSubscribers') }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </Transition>

                  <div class="flex items-center justify-between pt-2 border-t border-card-border/40">
                    <Button variant="ghost" @click="step = 1">
                      <ArrowLeft class="w-4 h-4 shrink-0" />
                      {{ t('notifications.back') }}
                    </Button>
                    <Button variant="primary" @click="estimateAudience">
                      {{ t('notifications.continueConfirm') }}
                      <ArrowRight class="w-4 h-4 shrink-0" />
                    </Button>
                  </div>
                </div>

                <!-- ── Step 3: Confirm & Send ── -->
                <div v-else-if="step === 3" key="step3" class="space-y-5">
                  <!-- Summary card -->
                  <div class="rounded-xl border border-blue-200/60 dark:border-blue-900/40 bg-blue-50/5 dark:bg-blue-950/10 overflow-hidden">
                    <!-- Title row -->
                    <div class="flex items-center gap-4 p-4 border-b border-blue-200/40 dark:border-blue-900/30">
                      <div class="w-10 h-10 rounded-xl bg-brand-blue flex items-center justify-center shrink-0 shadow-lg shadow-brand-blue/30">
                        <Megaphone class="w-5 h-5 text-white" />
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-main-text truncate">{{ form.title }}</p>
                        <p class="text-[11px] text-main-text/50 mt-0.5 uppercase tracking-wider">{{ t('notifications.reviewTitle') }}</p>
                      </div>
                      <!-- Target badge -->
                      <span class="text-[11px] font-bold px-3 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800 uppercase">
                        {{ form.targetType }}
                      </span>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-2 divide-x divide-blue-200/40 dark:divide-blue-900/30">
                      <div class="p-4">
                        <p class="text-[10px] text-main-text/40 uppercase tracking-widest font-semibold mb-1">{{ t('notifications.labelTarget') }}</p>
                        <p class="text-sm font-bold text-main-text capitalize">{{ form.targetType }}</p>
                      </div>
                      <div class="p-4">
                        <p class="text-[10px] text-main-text/40 uppercase tracking-widest font-semibold mb-1">{{ t('notifications.labelReach') }}</p>
                        <p class="text-sm font-bold text-main-text flex items-center gap-1.5">
                          <span v-if="estimate !== null">{{ estimate }} {{ t('notifications.usersCountLabel') }}</span>
                          <span v-else class="flex items-center gap-1.5 text-main-text/40">
                            <Loader2 class="w-3.5 h-3.5 animate-spin" />
                            {{ t('notifications.calculating') }}
                          </span>
                        </p>
                      </div>
                    </div>

                    <!-- Message preview -->
                    <div class="p-4 border-t border-blue-200/40 dark:border-blue-900/30">
                      <p class="text-[10px] text-main-text/40 uppercase tracking-widest font-semibold mb-2">{{ t('notifications.labelPreview') }}</p>
                      <div class="p-3 bg-card-bg rounded-xl border border-card-border text-sm text-main-text whitespace-pre-wrap leading-relaxed max-h-32 overflow-y-auto custom-scrollbar">
                        {{ form.message }}
                      </div>
                    </div>
                  </div>

                  <!-- Warning for 0 recipients -->
                  <div v-if="estimate === 0" class="flex items-center gap-3 p-4 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                    <AlertTriangle class="w-4 h-4 text-rose-500 shrink-0" />
                    <p class="text-sm text-rose-600 dark:text-rose-400 font-medium">{{ t('notifications.noRecipients') }}</p>
                  </div>

                  <div class="flex items-center justify-between pt-2 border-t border-card-border/40">
                    <Button variant="ghost" @click="step = 2">
                      <ArrowLeft class="w-4 h-4 shrink-0" />
                      {{ t('notifications.back') }}
                    </Button>
                    <Button
                      variant="primary"
                      :loading="sending"
                      :disabled="sending || estimate === 0"
                      @click="dispatchBroadcast"
                    >
                      <Send class="w-4 h-4 shrink-0" />
                      {{ sending ? t('notifications.sending') : t('notifications.sendNow') }}
                    </Button>
                  </div>
                </div>

              </Transition>
            </div>
          </div>
        </div>

        <!-- ── Campaign History ──────────────────────────────────── -->
        <div class="flex flex-col min-h-0">
          <div class="bg-card-bg border border-card-border rounded-2xl overflow-hidden flex flex-col flex-1" style="max-height: 680px;">
            <!-- Header -->
            <div class="px-6 pt-5 pb-4 border-b border-card-border/50 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-brand-blue/10 flex items-center justify-center shrink-0">
                <History class="w-5 h-5 text-brand-blue" />
              </div>
              <div>
                <h3 class="text-sm font-semibold text-main-text">{{ t('notifications.history') }}</h3>
                <p class="text-xs text-main-text/50 mt-0.5">{{ t('notifications.historyDesc') }}</p>
              </div>
            </div>

            <!-- Campaign List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar">
              <!-- Loading -->
              <div v-if="loadingCampaigns" class="p-4 space-y-3 animate-pulse">
                <div v-for="i in 5" :key="i" class="flex items-center gap-3 p-3 rounded-xl">
                  <div class="skeleton w-8 h-8 rounded-xl opacity-20" />
                  <div class="flex-1 space-y-1.5">
                    <div class="skeleton h-3 w-3/4 rounded opacity-20" />
                    <div class="skeleton h-2.5 w-1/2 rounded opacity-15" />
                  </div>
                  <div class="skeleton h-5 w-16 rounded-full opacity-15" />
                </div>
              </div>

              <!-- Empty -->
              <div v-else-if="campaigns.length === 0" class="flex flex-col items-center justify-center py-12 text-center px-4">
                <div class="w-12 h-12 rounded-full bg-main-bg flex items-center justify-center mb-3">
                  <Megaphone class="w-5 h-5 text-main-text/20" />
                </div>
                <p class="text-sm font-medium text-main-text/30">{{ t('notifications.noCampaigns') }}</p>
              </div>

              <!-- Campaign Items -->
              <div v-else class="divide-y divide-card-border/40">
                <button
                  v-for="c in campaigns"
                  :key="c.id"
                  @click="viewLogs(c)"
                  class="w-full flex items-start gap-3 p-4 hover:bg-nav-accent/5 transition-colors text-left group"
                >
                  <!-- Status icon -->
                  <div
                    class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                    :class="campaignStatusIcon(c.status?.toLowerCase()).bg"
                  >
                    <component :is="campaignStatusIcon(c.status?.toLowerCase()).icon" class="w-4 h-4" :class="campaignStatusIcon(c.status?.toLowerCase()).color" />
                  </div>
                  <!-- Content -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-main-text truncate group-hover:text-nav-accent-hover transition-colors">
                      {{ c.title }}
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                      <span class="text-[10px] text-main-text/40">{{ formatDate(c.createdAt) }}</span>
                      <span class="text-[10px] text-main-text/20">•</span>
                      <span class="text-[10px] text-main-text/40">{{ c.totalRecipients || 0 }} {{ t('notifications.usersCountLabel') }}</span>
                    </div>
                  </div>
                  <!-- Status badge -->
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full border shrink-0"
                    :class="campaignStatusClasses(c.status?.toLowerCase())"
                  >
                    {{ c.status }}
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- ── Campaign Logs Modal ───────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="selectedCampaign"
          @click.self="selectedCampaign = null"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        >
          <div class="bg-card-bg border border-card-border w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-card-border shrink-0">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-blue/10 flex items-center justify-center">
                  <BarChart3 class="w-4.5 h-4.5 text-brand-blue" />
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-main-text">{{ selectedCampaign.title }}</h3>
                  <p class="text-xs text-main-text/50 mt-0.5">{{ t('notifications.deliveryLogs') }}</p>
                </div>
              </div>
              <button
                @click="selectedCampaign = null"
                class="w-8 h-8 rounded-xl hover:bg-main-bg flex items-center justify-center text-main-text/40 hover:text-main-text transition-all"
              >
                <X class="w-4.5 h-4.5" />
              </button>
            </div>

            <!-- Metrics bar -->
            <div class="grid grid-cols-3 divide-x divide-card-border border-b border-card-border shrink-0">
              <div class="p-4 text-center">
                <p class="text-lg font-bold text-main-text">{{ selectedCampaign.totalRecipients || 0 }}</p>
                <p class="text-[10px] text-main-text/40 uppercase tracking-wider mt-0.5">{{ t('notifications.total') }}</p>
              </div>
              <div class="p-4 text-center">
                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ selectedCampaign.sentCount || 0 }}</p>
                <p class="text-[10px] text-main-text/40 uppercase tracking-wider mt-0.5">{{ t('notifications.sent') }}</p>
              </div>
              <div class="p-4 text-center">
                <p class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ selectedCampaign.failedCount || 0 }}</p>
                <p class="text-[10px] text-main-text/40 uppercase tracking-wider mt-0.5">{{ t('notifications.failed') }}</p>
              </div>
            </div>

            <!-- Logs Table -->
            <div class="flex-1 overflow-hidden flex flex-col min-h-0">
              <DataTable
                :items="logs"
                :columns="logColumns"
                :loading="loadingLogs"
                :empty-message="t('notifications.noLogs')"
                hide-pagination
                class="border-0"
              >
                <template #cell-user="{ item }">
                  <span class="font-mono text-xs text-main-text">ID: {{ item.userId || item.telegramId }}</span>
                </template>
                <template #cell-status="{ item }">
                  <span
                    class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2 py-0.5 rounded-full border"
                    :class="item.status === 'Success'
                      ? 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800'
                      : 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800'"
                  >
                    <CheckCircle2 v-if="item.status === 'Success'" class="w-3 h-3" />
                    <XCircle v-else class="w-3 h-3" />
                    {{ item.status }}
                  </span>
                </template>
                <template #cell-error="{ item }">
                  <span class="text-xs text-rose-500 truncate block max-w-[150px]" :title="item.error">{{ item.error || '—' }}</span>
                </template>
                <template #cell-date="{ item }">
                  <span class="text-xs text-main-text/50">{{ formatDate(item.sentAt) }}</span>
                </template>
              </DataTable>
            </div>

            <div class="px-6 py-4 border-t border-card-border flex justify-end shrink-0">
              <Button variant="ghost" @click="selectedCampaign = null">{{ t('common.close') }}</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { useLanguageStore } from "@/stores/languageStore";
import { useEnumI18n } from '@/bot_enums';
import { ref, computed, onMounted, inject } from 'vue';
import apiClient from '@/api/apiClient';

// Components
import PageTitle from '@/components/common/PageTitle.vue';
import DataTable from '@/components/common/DataTable.vue';
import SearchInput from '@/components/common/SearchInput.vue';
import Button from '@/components/common/Button.vue';
import FormField from '@/components/common/FormField.vue';
import FormSelect from '@/components/common/FormSelect.vue';

// Icons
import {
  Megaphone,
  Send,
  History,
  CheckIcon,
  CheckCircle2,
  XCircle,
  X,
  ArrowRight,
  ArrowLeft,
  Loader2,
  AlertTriangle,
  BarChart3,
  Users,
  Activity,
  Globe,
  Tag,
  UserCheck,
} from 'lucide-vue-next';

const languageStore = useLanguageStore();
const t = (k: string, p?: any) => languageStore.translate(k, p);
const { categoryOptions } = useEnumI18n();
const showToast = inject('showToast') as any;

const step = ref(1);
const campaigns = ref<any[]>([]);
const loadingCampaigns = ref(true);
const subscribers = ref<any[]>([]);
const selectedSubscribers = ref<string[]>([]);
const subscriberSearch = ref('');
const estimate = ref<number | null>(null);
const sending = ref(false);

const selectedCampaign = ref<any>(null);
const logs = ref<any[]>([]);
const loadingLogs = ref(false);

const form = ref({
  title: '',
  message: '',
  targetType: 'all',
  targetValue: ''
});

const steps = computed(() => [
  { id: 1, label: t('notifications.step1') },
  { id: 2, label: t('notifications.step2') },
  { id: 3, label: t('notifications.step3') },
]);

// Audience segments with icons
const segments = computed(() => [
  { value: 'all',      label: t('notifications.targetAll'),      description: t('notifications.segDescAll'),      icon: Users     },
  { value: 'active',   label: t('notifications.targetActive'),   description: t('notifications.segDescActive'),   icon: Activity  },
  { value: 'language', label: t('notifications.targetLanguage'), description: t('notifications.segDescLanguage'), icon: Globe     },
  { value: 'category', label: t('notifications.targetCategory'), description: t('notifications.segDescCategory'), icon: Tag       },
  { value: 'selected', label: t('notifications.targetSelected'), description: t('notifications.segDescSelected'), icon: UserCheck },
]);

const logColumns = [
  { key: 'user',   label: 'Target ID' },
  { key: 'status', label: 'Status'    },
  { key: 'error',  label: 'Details'   },
  { key: 'date',   label: 'Date', align: 'right' as const },
];

const filteredSubscribers = computed(() => {
  if (!subscriberSearch.value.trim()) return subscribers.value;
  const q = subscriberSearch.value.toLowerCase();
  return subscribers.value.filter(s =>
    (s.firstName ?? '').toLowerCase().includes(q) ||
    (s.lastName ?? '').toLowerCase().includes(q) ||
    (s.username ?? '').toLowerCase().includes(q) ||
    String(s.telegramId).includes(q)
  );
});

const toggleSelectAll = () => {
  if (selectedSubscribers.value.length === filteredSubscribers.value.length) {
    selectedSubscribers.value = [];
  } else {
    selectedSubscribers.value = filteredSubscribers.value.map(s => s.telegramId);
  }
};

const fetchCampaigns = async () => {
  loadingCampaigns.value = true;
  try {
    const res = await apiClient.get('/bot/notifications');
    campaigns.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (_) {}
  finally { loadingCampaigns.value = false; }
};

const fetchSubscribers = async () => {
  try {
    const res = await apiClient.get('/bot/users', { params: { per_page: 1000 } });
    subscribers.value = res.data?.data || res.data || [];
  } catch (_) {}
};

const estimateAudience = async () => {
  estimate.value = null;
  step.value = 3;
  try {
    const res = await apiClient.post('/bot/notifications/estimate', {
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
    await apiClient.post('/bot/notifications', {
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

const viewLogs = async (c: any) => {
  selectedCampaign.value = c;
  loadingLogs.value = true;
  logs.value = [];
  try {
    const res = await apiClient.get(`/bot/notifications/${c.id}/logs`);
    logs.value = Array.isArray(res.data.logs) ? res.data.logs : (res.data.data || []);
  } catch {
    logs.value = [];
  } finally {
    loadingLogs.value = false;
  }
};

const campaignStatusClasses = (status: string) => {
  switch (status) {
    case 'completed':  return 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
    case 'failed':     return 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800';
    case 'sending':
    case 'processing': return 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800';
    default:           return 'bg-main-bg text-main-text/50 border-card-border';
  }
};

const campaignStatusIcon = (status: string) => {
  switch (status) {
    case 'completed':  return { icon: CheckCircle2, bg: 'bg-emerald-50 dark:bg-emerald-900/20', color: 'text-emerald-500' };
    case 'failed':     return { icon: XCircle,      bg: 'bg-rose-50 dark:bg-rose-900/20',       color: 'text-rose-500'    };
    case 'sending':
    case 'processing': return { icon: Loader2,      bg: 'bg-blue-50 dark:bg-blue-900/20',        color: 'text-blue-500 animate-spin' };
    default:           return { icon: Send,          bg: 'bg-main-bg',                            color: 'text-main-text/30' };
  }
};

const formatDate = (d: string) => d ? new Date(d).toLocaleDateString() : '';

onMounted(() => {
  fetchCampaigns();
  fetchSubscribers();
});
</script>

<style scoped>
/* Step content transitions */
.step-enter-active,
.step-leave-active {
  transition: all 0.25s ease;
}
.step-enter-from {
  opacity: 0;
  transform: translateX(10px);
}
.step-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}

/* Fade + slide for conditional panels */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.2s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
