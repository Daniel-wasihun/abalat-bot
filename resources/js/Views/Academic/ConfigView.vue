<template>
  <div class="h-full flex flex-col relative">
    <!-- Sticky Header & Tabs Container -->
    <div class="sticky top-0 z-50 bg-main-bg pb-0 shrink-0 pt-2 border-b border-card-border/60">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-1">
        <div>
          <h1 class="text-2xl font-bold text-main-text">{{ $tr('academic.config.title') }}</h1>
          <p class="text-sm mt-1 text-main-text/60">{{ $tr('academic.config.subtitle') }}</p>
        </div>
      </div>

      <!-- Tabs (ClassHub style) -->
      <div class="mt-6 px-1">
        <nav class="flex gap-6 -mb-px" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :id="`config-tab-${tab.key}`"
            :class="[
              'flex items-center gap-2 py-3 px-1 text-sm font-semibold transition-colors whitespace-nowrap bg-transparent outline-none focus-visible:ring-2 focus-visible:ring-brand-blue rounded-t-sm border-b-2',
              activeTab === tab.key
                ? 'border-brand-blue text-brand-blue'
                : 'border-transparent text-main-text/50 hover:text-brand-blue hover:border-main-text/20',
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            {{ tab.label }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Tab Contents Container -->
    <div class="flex-1 min-h-0 flex flex-col mt-6 pb-6 overflow-hidden">

    <!-- Classes Tab -->
    <div v-if="activeTab === 'classes'" class="flex flex-col flex-1 min-h-0 relative font-sans animate-in fade-in duration-500">
      <div class="bg-card-bg border border-card-border/60 rounded-2xl flex flex-col flex-1 min-h-0 overflow-hidden shadow-sm">
        <div class="flex flex-col flex-1 min-h-0">
          <TableToolbar
            modelValue=""
            :placeholder="$tr('academic.config.search_classes')"
            :show-filters="false"
            :has-active-filters="false"
            :create-label="$tr('academic.config.add_class')"
            :can-create="true"
            :loading="loadingClasses"
            @create="openClassModal(null)"
          />
          <div class="flex-1 min-h-0 flex flex-col">
            <DataTable
              class="border-0 rounded-none border-t border-card-border/60"
              :items="classPagedItems"
              :loading="loadingClasses"
              :empty-message="$tr('academic.config.no_classes_msg')"
              :empty-title="$tr('academic.config.no_classes')"
              :empty-desc="$tr('academic.config.add_classes_desc')"
              :sort-by="classSortBy"
              :sort-order="classSortOrder"
              :pagination="classPagination"
              @sort="handleClassSort"
              @page-change="onClassPageChange"
              @per-page-change="onClassPerPageChange"
              row-key="id"
            >
              <TableColumn field="name" :header="$tr('academic.config.columns.name')" align="left" sortable>
                <template #default="{ row: item }">
                  <div class="flex items-center gap-2 py-1">
                    <span class="text-sm md:text-base font-normal text-main-text group-hover:text-accent transition-colors truncate">{{ item.name }}</span>
                  </div>
                </template>
              </TableColumn>

              <TableColumn field="code" :header="$tr('academic.config.columns.code')" align="left" sortable>
                <template #default="{ row: item }">
                  <span class="px-3 py-1.5 rounded-xl bg-brand-blue/5 text-brand-blue text-[13px] font-normal border border-brand-blue/10 tracking-wide font-mono shrink-0 whitespace-nowrap">{{ item.code }}</span>
                </template>
              </TableColumn>

              <TableColumn field="number_of_sections" :header="$tr('academic.config.columns.sections')" align="left" sortable>
                <template #default="{ row: item }">
                  <span class="text-sm md:text-base font-normal text-main-text/80 group-hover:text-accent transition-colors">{{ item.number_of_sections }}</span>
                </template>
              </TableColumn>

              <TableColumn field="intake_capacity_per_section" :header="$tr('academic.config.columns.capacity')" align="left" sortable>
                <template #default="{ row: item }">
                  <span class="text-sm md:text-base font-normal text-main-text/80 group-hover:text-accent transition-colors">{{ item.intake_capacity_per_section }}</span>
                </template>
              </TableColumn>

              <TableColumn field="is_active" :header="$tr('academic.config.columns.status')" align="center" width="120px" sortable>
                <template #default="{ row: item }">
                  <StatusBadge :status="item.is_active ? 'active' : 'inactive'" />
                </template>
              </TableColumn>

              <TableColumn header="" align="right">
                <template #default="{ row: item }">
                  <div class="flex justify-end p-1">
                    <ActionDropdown :actions="getClassActions(item)" :item="item" />
                  </div>
                </template>
              </TableColumn>
            </DataTable>
          </div>
        </div>
      </div>
    </div>

    <!-- Assessment Types Tab -->
    <div v-if="activeTab === 'assessments'" class="flex flex-col flex-1 min-h-0 space-y-4 relative font-sans animate-in fade-in duration-500">
      <div class="flex items-center justify-between shrink-0">
        <p class="text-sm font-medium text-main-text/60">
          {{ $tr('academic.config.total_weight') }} <span :class="totalWeight === 100 ? 'text-emerald-600' : 'text-amber-600'" class="font-bold">{{ totalWeight.toFixed(0) }} / 100</span>
          <span v-if="totalWeight !== 100" class="text-xs text-amber-500 ml-2">{{ $tr('academic.config.should_equal_100') }}</span>
        </p>
      </div>
      <div class="bg-card-bg border border-card-border/60 rounded-2xl flex flex-col flex-1 min-h-0 overflow-hidden shadow-sm">
        <div class="flex flex-col flex-1 min-h-0">
          <TableToolbar
            modelValue=""
            :placeholder="$tr('academic.config.search_assessments')"
            :show-filters="false"
            :has-active-filters="false"
            :create-label="$tr('academic.config.add_assessment')"
            :can-create="true"
            :loading="loadingAssessments"
            @create="openAssessmentModal(null)"
          />
          <div class="flex-1 min-h-0 flex flex-col">
            <DataTable
              class="border-0 rounded-none border-t border-card-border/60"
              :items="assessmentPagedItems"
              :loading="loadingAssessments"
              :empty-message="$tr('academic.config.no_assessments_msg')"
              :empty-title="$tr('academic.config.no_assessments')"
              :empty-desc="$tr('academic.config.add_assessments_desc')"
              :sort-by="assessmentSortBy"
              :sort-order="assessmentSortOrder"
              :pagination="assessmentPagination"
              @sort="handleAssessmentSort"
              @page-change="onAssessmentPageChange"
              @per-page-change="onAssessmentPerPageChange"
              row-key="id"
            >
              <TableColumn field="order" :header="$tr('academic.config.columns.order')" align="left" sortable>
                <template #default="{ row: item }">
                  <span class="font-mono text-[13px] text-main-text/50 tracking-wide">{{ item.order }}</span>
                </template>
              </TableColumn>

              <TableColumn field="name" :header="$tr('academic.config.columns.name')" align="left" sortable>
                <template #default="{ row: item }">
                  <div class="flex items-center gap-2 py-1">
                    <span class="text-sm md:text-base font-normal text-main-text group-hover:text-accent transition-colors truncate">{{ item.name }}</span>
                  </div>
                </template>
              </TableColumn>

              <TableColumn field="code" :header="$tr('academic.config.columns.code')" align="left" sortable>
                <template #default="{ row: item }">
                  <span class="px-3 py-1.5 rounded-xl bg-purple-500/5 text-purple-600 text-[13px] font-normal border border-purple-500/10 tracking-wide font-mono shrink-0 whitespace-nowrap">{{ item.code }}</span>
                </template>
              </TableColumn>

              <TableColumn field="max_score" :header="$tr('academic.config.columns.max_score')" align="left" sortable>
                <template #default="{ row: item }">
                  <span class="text-sm md:text-base font-bold text-brand-blue group-hover:text-accent transition-colors">{{ item.max_score }}</span>
                </template>
              </TableColumn>

              <TableColumn field="is_active" :header="$tr('academic.config.columns.status')" align="center" width="120px" sortable>
                <template #default="{ row: item }">
                  <StatusBadge :status="item.is_active ? 'active' : 'inactive'" />
                </template>
              </TableColumn>

              <TableColumn header="" align="right">
                <template #default="{ row: item }">
                  <div class="flex justify-end p-1">
                    <ActionDropdown :actions="getAssessmentActions(item)" :item="item" />
                  </div>
                </template>
              </TableColumn>
            </DataTable>
          </div>
        </div>
      </div>
    </div>

    <!-- ID Card Config Tab -->
    <div v-if="activeTab === 'idcard'" class="space-y-6 animate-in fade-in duration-500">
      <div v-if="loadingIdCard" class="flex items-center justify-center py-20">
        <Loader2 class="w-6 h-6 animate-spin text-brand-blue" />
      </div>
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- School Titles Card -->
        <div class="bg-card-bg border border-card-border/60 rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-card-border/40 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-brand-blue/10 flex items-center justify-center">
              <CreditCard class="w-4 h-4 text-brand-blue" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-main-text">{{ $tr('id_card.config.school_titles', 'School Titles') }}</h3>
              <p class="text-xs text-main-text/50 mt-0.5">{{ $tr('id_card.config.subtitle', 'Text shown at the top of each printed ID card') }}</p>
            </div>
          </div>
          <div class="p-6 space-y-5">
            <!-- Logo Upload -->
            <div>
              <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-2">{{ $tr('id_card.config.logo', 'School Logo') }}</label>
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl border-2 border-dashed border-card-border/60 bg-main-bg flex items-center justify-center overflow-hidden shrink-0">
                  <img v-if="currentLogoUrl" :src="currentLogoUrl" class="w-full h-full object-contain p-1" alt="School logo" />
                  <CreditCard v-else class="w-6 h-6 text-main-text/20" />
                </div>
                <div class="flex-1 min-w-0">
                  <label class="flex items-center gap-2 cursor-pointer h-10 px-4 rounded-xl border border-dashed border-brand-blue/40 bg-brand-blue/5 hover:bg-brand-blue/10 text-brand-blue text-sm font-semibold transition-colors">
                    <input type="file" accept="image/*" class="hidden" @change="handleLogoUpload" :disabled="uploadingLogo" />
                    <Loader2 v-if="uploadingLogo" class="w-4 h-4 animate-spin" />
                    <span v-else>{{ $tr('id_card.config.upload_logo', 'Upload Logo') }}</span>
                  </label>
                  <p class="text-xs text-main-text/40 mt-1.5">PNG, JPG, SVG · max 2 MB</p>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.title_am', 'Title (Amharic)') }}</label>
              <input v-model="idCardForm['id_card.title_am']" type="text" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.title_en', 'Title (English)') }}</label>
              <input v-model="idCardForm['id_card.title_en']" type="text" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.title_or', 'Title (Oromiffa)') }}</label>
              <input v-model="idCardForm['id_card.title_or']" type="text" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
            </div>
          </div>
        </div>


        <!-- Right column -->
        <div class="flex flex-col gap-6">
          <!-- Authority Labels Card -->
          <div class="bg-card-bg border border-card-border/60 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-card-border/40 flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                <Save class="w-4 h-4 text-emerald-500" />
              </div>
              <h3 class="text-sm font-semibold text-main-text">{{ $tr('id_card.config.authority', 'Issuing Authority Labels') }}</h3>
            </div>
            <div class="p-6 space-y-4">
              <div>
                <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.authority_am') }}</label>
                <input v-model="idCardForm['id_card.authority_am']" type="text" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.authority_en') }}</label>
                <input v-model="idCardForm['id_card.authority_en']" type="text" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.authority_or') }}</label>
                <input v-model="idCardForm['id_card.authority_or']" type="text" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
              </div>
            </div>
          </div>

          <!-- ID Settings Card -->
          <div class="bg-card-bg border border-card-border/60 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-card-border/40">
              <h3 class="text-sm font-semibold text-main-text">{{ $tr('id_card.config.id_settings', 'ID Settings') }}</h3>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.id_prefix', 'ID Prefix') }}</label>
                <input v-model="idCardForm['id_card.id_prefix']" type="text" placeholder="DBSS" class="w-full px-4 h-11 rounded-xl border text-sm font-mono bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('id_card.config.validity_years', 'Validity (Years)') }}</label>
                <input v-model.number="idCardForm['id_card.validity_years']" type="number" min="1" max="10" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button @click="saveIdCardSettings" :disabled="savingIdCard"
          class="flex items-center gap-2 h-11 px-6 rounded-xl text-sm font-bold bg-brand-blue text-white hover:bg-brand-blue/90 transition-all disabled:opacity-50 shadow-lg shadow-brand-blue/20">
          <Loader2 v-if="savingIdCard" class="w-4 h-4 animate-spin" />
          <Save v-else class="w-4 h-4" />
          {{ $tr('id_card.config.save', 'Save ID Card Settings') }}
        </button>
      </div>
    </div>

    <!-- Class Modal -->
    <Teleport to="body">
      <div v-if="classModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-main-bg/80 backdrop-blur-sm" @click="classModalOpen = false" />
        <div class="relative z-10 w-full max-w-2xl rounded-[1.5rem] shadow-2xl p-6 md:p-8 space-y-6 border border-card-border/60 bg-card-bg">
          <div class="flex items-center justify-between pb-4 border-b border-card-border/40">
            <h2 class="text-xl font-bold text-main-text">{{ editingClass?.id ? $tr('academic.config.edit_class') : $tr('academic.config.add_new_class') }}</h2>
            <button @click="classModalOpen = false" class="p-2 rounded-xl hover:bg-main-text/5 transition-colors text-main-text/40 hover:text-main-text">
              <X class="w-5 h-5" />
            </button>
          </div>
          <div class="space-y-6">
            <div>
              <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.class_name') }} <span class="text-rose-500">*</span></label>
              <input v-model="classForm.name" id="class-name-input" placeholder="e.g. Grade 1" type="text"
                class="w-full px-4 h-12 rounded-xl border text-sm font-medium focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all shadow-sm bg-input-bg text-main-text border-input-border" />
              <p class="text-xs mt-2 text-main-text/40">{{ $tr('academic.config.class_name_desc') }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.class_code') }} <span class="text-rose-500">*</span></label>
              <input v-model="classForm.code" id="class-code-input" placeholder="e.g. 1 or KG" type="text" :disabled="!!editingClass?.id"
                class="w-full px-4 h-12 rounded-xl border text-sm font-mono focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all shadow-sm bg-input-bg text-main-text border-input-border disabled:opacity-50" />
              <p class="text-xs mt-2 text-main-text/40">{{ $tr('academic.config.class_code_desc') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.total_sections') }}</label>
                <input v-model.number="classForm.number_of_sections" id="class-sections-input" type="number" min="1"
                  class="w-full px-4 h-12 rounded-xl border text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none shadow-sm bg-input-bg text-main-text border-input-border" />
              </div>
              <div>
                <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.capacity_per_section') }}</label>
                <input v-model.number="classForm.intake_capacity_per_section" id="class-capacity-input" type="number" min="1"
                  class="w-full px-4 h-12 rounded-xl border text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none shadow-sm bg-input-bg text-main-text border-input-border" />
              </div>
            </div>
            <label class="flex items-center gap-4 cursor-pointer p-4 rounded-xl border border-input-border transition-colors hover:bg-main-text/5">
              <input type="checkbox" v-model="classForm.is_active" id="class-active-input" class="w-5 h-5 accent-brand-blue rounded border-input-border bg-input-bg" />
              <div>
                <span class="text-sm font-semibold block text-main-text">{{ $tr('academic.config.active_status') }}</span>
                <span class="text-xs text-main-text/50 mt-1 block">{{ $tr('academic.config.class_active_desc') }}</span>
              </div>
            </label>
          </div>
          <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-card-border/40">
            <button @click="classModalOpen = false" class="h-11 px-6 rounded-xl text-sm font-bold bg-main-text/5 hover:bg-main-text/10 transition-colors text-main-text">{{ $tr('academic.config.cancel') }}</button>
            <button @click="saveClass" :disabled="savingClass" id="save-class-btn"
              class="flex items-center gap-2 h-11 px-6 rounded-xl text-sm font-bold bg-brand-blue text-white hover:bg-brand-blue/90 transition-all disabled:opacity-50 shadow-lg shadow-brand-blue/20">
              <Loader2 v-if="savingClass" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              {{ $tr('academic.config.save_class') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Assessment Modal -->
    <Teleport to="body">
      <div v-if="assessmentModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-main-bg/80 backdrop-blur-sm" @click="assessmentModalOpen = false" />
        <div class="relative z-10 w-full max-w-2xl rounded-[1.5rem] shadow-2xl p-6 md:p-8 space-y-6 border border-card-border/60 bg-card-bg">
          <div class="flex items-center justify-between pb-4 border-b border-card-border/40">
            <h2 class="text-xl font-bold text-main-text">{{ editingAssessment?.id ? $tr('academic.config.edit_assessment') : $tr('academic.config.add_new_assessment') }}</h2>
            <button @click="assessmentModalOpen = false" class="p-2 rounded-xl hover:bg-main-text/5 transition-colors text-main-text/40 hover:text-main-text">
              <X class="w-5 h-5" />
            </button>
          </div>
          <div class="space-y-6">
            <div>
              <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.assessment_name') }} <span class="text-rose-500">*</span></label>
              <input v-model="assessmentForm.name" id="assessment-name-input" placeholder="e.g. Assignment / Quiz" type="text"
                class="w-full px-4 h-12 rounded-xl border text-sm font-medium focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all shadow-sm bg-input-bg text-main-text border-input-border" />
              <p class="text-xs mt-2 text-main-text/40">{{ $tr('academic.config.assessment_name_desc') }}</p>
            </div>
            <div>
              <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.assessment_code') }} <span class="text-rose-500">*</span></label>
              <input v-model="assessmentForm.code" id="assessment-code-input" placeholder="e.g. quiz" type="text" :disabled="!!editingAssessment?.id"
                class="w-full px-4 h-12 rounded-xl border text-sm font-mono focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all shadow-sm bg-input-bg text-main-text border-input-border disabled:opacity-50" />
              <p class="text-xs mt-2 text-main-text/40">{{ $tr('academic.config.assessment_code_desc') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.max_score') }} <span class="text-rose-500">*</span></label>
                <input v-model.number="assessmentForm.max_score" id="assessment-max-score-input" type="number" min="1" max="100"
                  class="w-full px-4 h-12 rounded-xl border text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none shadow-sm bg-input-bg text-main-text border-input-border" />
              </div>
              <div>
                <label class="text-sm font-semibold mb-2 block text-main-text">{{ $tr('academic.config.display_order') }}</label>
                <input v-model.number="assessmentForm.order" id="assessment-order-input" type="number" min="1"
                  class="w-full px-4 h-12 rounded-xl border text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none shadow-sm bg-input-bg text-main-text border-input-border" />
              </div>
            </div>
            <label class="flex items-center gap-4 cursor-pointer p-4 rounded-xl border border-input-border transition-colors hover:bg-main-text/5">
              <input type="checkbox" v-model="assessmentForm.is_active" id="assessment-active-input" class="w-5 h-5 accent-brand-blue rounded border-input-border bg-input-bg" />
              <div>
                <span class="text-sm font-semibold block text-main-text">{{ $tr('academic.config.active_status') }}</span>
                <span class="text-xs text-main-text/50 mt-1 block">{{ $tr('academic.config.assessment_active_desc') }}</span>
              </div>
            </label>
          </div>
          <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-card-border/40">
            <button @click="assessmentModalOpen = false" class="h-11 px-6 rounded-xl text-sm font-bold bg-main-text/5 hover:bg-main-text/10 transition-colors text-main-text">{{ $tr('academic.config.cancel') }}</button>
            <button @click="saveAssessment" :disabled="savingAssessment" id="save-assessment-btn"
              class="flex items-center gap-2 h-11 px-6 rounded-xl text-sm font-bold bg-brand-blue text-white hover:bg-brand-blue/90 transition-all disabled:opacity-50 shadow-lg shadow-brand-blue/20">
              <Loader2 v-if="savingAssessment" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              {{ $tr('academic.config.save_assessment') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ══ PAYMENTS TAB ══ -->
    <div v-if="activeTab === 'payments'" class="flex flex-col flex-1 min-h-0 relative font-sans animate-in fade-in duration-500">
      <div class="bg-card-bg border border-card-border/60 rounded-2xl flex flex-col min-h-0 shrink overflow-hidden shadow-sm">
        <div class="flex flex-col min-h-0 shrink">
          <div class="flex-1 min-h-0 flex flex-col">
            <div class="p-6 md:p-8 space-y-8 flex-1 overflow-auto">
              
              <div class="max-w-2xl">
                <h3 class="text-sm font-semibold text-main-text">{{ $tr('payments.config.rates', 'Payment Rates & Fines') }}</h3>
                <p class="text-xs text-main-text/50 mt-0.5 mb-4">{{ $tr('payments.config.rates_desc', 'Set the monthly amounts for students and workers.') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.student_amount', 'Student Amount (ETB)') }}</label>
                    <input v-model.number="paymentForm['payment.student_amount']" type="number" min="0" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.worker_amount', 'Worker Amount (ETB)') }}</label>
                    <input v-model.number="paymentForm['payment.worker_amount']" type="number" min="0" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.student_fine', 'Student Late Fine / Month (ETB)') }}</label>
                    <input v-model.number="paymentForm['payment.student_fine_per_month']" type="number" min="0" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                    <p class="text-xs text-main-text/40 mt-1">Fine applied to students per overdue month</p>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.worker_fine', 'Worker Late Fine / Month (ETB)') }}</label>
                    <input v-model.number="paymentForm['payment.worker_fine_per_month']" type="number" min="0" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                    <p class="text-xs text-main-text/40 mt-1">Fine applied to workers per overdue month</p>
                  </div>
                </div>
              </div>

              <div class="w-full h-px bg-card-border/40"></div>

              <div class="max-w-2xl">
                <h3 class="text-sm font-semibold text-main-text">{{ $tr('payments.config.eligibility', 'Payment Eligibility') }}</h3>
                <p class="text-xs text-main-text/50 mt-0.5 mb-4">{{ $tr('payments.config.eligibility_desc', 'Define who is required to pay.') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.min_grade', 'Minimum Grade Level') }}</label>
                    <input v-model.number="paymentForm['payment.minimum_grade_level']" type="number" min="1" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.min_age', 'Minimum Age') }}</label>
                    <input v-model.number="paymentForm['payment.minimum_age']" type="number" min="1" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                  </div>
                </div>
              </div>

              <div class="w-full h-px bg-card-border/40"></div>

              <div class="max-w-2xl">
                <h3 class="text-sm font-semibold text-main-text">{{ $tr('payments.config.timing', 'Timing & Calendar') }}</h3>
                <p class="text-xs text-main-text/50 mt-0.5 mb-4">{{ $tr('payments.config.timing_desc', 'Configure when payments are due.') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.deadline_day', 'Monthly Deadline Day') }}</label>
                    <input v-model.number="paymentForm['payment.deadline_day']" type="number" min="1" max="30" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-main-text/60 capitalize tracking-widest mb-1.5">{{ $tr('payments.config.calendar_type', 'Calendar System') }}</label>
                    <select v-model="paymentForm['payment.calendar_type']" class="w-full px-4 h-11 rounded-xl border text-sm bg-input-bg text-main-text border-input-border focus:ring-2 focus:ring-brand-blue/30 outline-none transition-all">
                      <option value="ethiopian">Ethiopian Calendar</option>
                      <option value="gregorian">Gregorian Calendar</option>
                    </select>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="p-4 border-t border-card-border/60 bg-card-bg/30 flex items-center justify-end shrink-0">
          <button
            @click="savePaymentSettings"
            :disabled="savingPayments"
            class="flex items-center gap-2 px-6 h-11 bg-brand-blue hover:bg-brand-blue-dark text-white font-semibold rounded-xl transition-all shadow-lg shadow-brand-blue/20 hover:shadow-brand-blue/30 disabled:opacity-50 disabled:cursor-not-allowed">
            <Loader2 v-if="savingPayments" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            {{ $tr('payments.config.save', 'Save Payment Settings') }}
          </button>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, getCurrentInstance } from 'vue';
import { GraduationCap, FileText, Plus, Pencil, Trash2, Loader2, Save, X, CreditCard } from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import { useToastStore } from '@/stores/toast';
import TableToolbar from '@/components/common/TableToolbar.vue';
import DataTable from '@/components/common/DataTable.vue';
import TableColumn from '@/components/common/TableColumn.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import ActionDropdown from '@/components/common/ActionDropdown.vue';

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const toast = useToastStore();

const tabs = computed(() => [
  { key: 'classes',     label: $tr('academic.config.classes_tab'),     icon: GraduationCap },
  { key: 'assessments', label: $tr('academic.config.assessments_tab'), icon: FileText },
  { key: 'idcard',      label: $tr('id_card.config.tab', 'ID Card'),   icon: CreditCard },
  { key: 'payments',    label: $tr('payments.config.tab', 'Payments'), icon: CreditCard },
]);
const activeTab = ref('classes');

const getClassActions = (cls: any) => [
  { label: $tr('action.edit', 'Edit'), icon: Pencil, colorClass: 'text-amber-500', onClick: () => openClassModal(cls) },
  { label: $tr('action.delete', 'Delete'), icon: Trash2, colorClass: 'text-rose-600', onClick: () => deleteClass(cls) }
];

const getAssessmentActions = (a: any) => [
  { label: $tr('action.edit', 'Edit'), icon: Pencil, colorClass: 'text-amber-500', onClick: () => openAssessmentModal(a) },
  { label: $tr('action.delete', 'Delete'), icon: Trash2, colorClass: 'text-rose-600', onClick: () => deleteAssessment(a) }
];

// --- Classes ---
const classes = ref<any[]>([]);
const loadingClasses = ref(false);
const classModalOpen = ref(false);
const savingClass = ref(false);
const editingClass = ref<any>(null);
const classForm = ref({ name: '', code: '', number_of_sections: 1, intake_capacity_per_section: 50, is_active: true });

// Class sort state
const classSortBy = ref('name');
const classSortOrder = ref<'asc' | 'desc'>('asc');
const sortedClasses = computed(() => {
  const key = classSortBy.value;
  return [...classes.value].sort((a, b) => {
    let av = a[key], bv = b[key];
    if (typeof av === 'string') av = av.toLowerCase();
    if (typeof bv === 'string') bv = bv.toLowerCase();
    if (av < bv) return classSortOrder.value === 'asc' ? -1 : 1;
    if (av > bv) return classSortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});
function handleClassSort(key: string) {
  if (classSortBy.value === key) {
    classSortOrder.value = classSortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    classSortBy.value = key;
    classSortOrder.value = 'asc';
  }
}

// Class pagination
const classPage = ref(1);
const classPerPage = ref(10);
const classPagedItems = computed(() => {
  const start = (classPage.value - 1) * classPerPage.value;
  return sortedClasses.value.slice(start, start + classPerPage.value);
});
const classPagination = computed(() => ({
  currentPage: classPage.value,
  lastPage: Math.max(1, Math.ceil(sortedClasses.value.length / classPerPage.value)),
  total: sortedClasses.value.length,
  perPage: classPerPage.value,
}));
function onClassPageChange(p: number) { classPage.value = p; }
function onClassPerPageChange(n: number) { classPerPage.value = n; classPage.value = 1; }

async function loadClasses() {
  loadingClasses.value = true;
  try {
    const { data } = await apiClient.get('/academic/config/classes');
    classes.value = data.classes || [];
  } finally {
    loadingClasses.value = false;
  }
}

function openClassModal(cls: any) {
  editingClass.value = cls;
  classForm.value = cls
    ? { name: cls.name, code: cls.code, number_of_sections: cls.number_of_sections, intake_capacity_per_section: cls.intake_capacity_per_section, is_active: !!cls.is_active }
    : { name: '', code: '', number_of_sections: 1, intake_capacity_per_section: 50, is_active: true };
  classModalOpen.value = true;
}

async function saveClass() {
  if (!classForm.value.name || !classForm.value.code) { toast.error('Name and Code are required'); return; }
  savingClass.value = true;
  try {
    if (editingClass.value?.id) {
      await apiClient.put(`/academic/config/classes/${editingClass.value.id}`, classForm.value);
    } else {
      await apiClient.post('/academic/config/classes', classForm.value);
    }
    classModalOpen.value = false;
    await loadClasses();
  } catch (e: any) {
    if (!e?.response?.data?.message) {
        toast.error('Failed to save class');
    }
  } finally {
    savingClass.value = false;
  }
}

async function deleteClass(cls: any) {
  if (!confirm(`Delete "${cls.name}"? This cannot be undone.`)) return;
  try {
    await apiClient.delete(`/academic/config/classes/${cls.id}`);
    await loadClasses();
  } catch (e: any) { 
    if (!e?.response?.data?.message) {
        toast.error('Failed to delete class'); 
    }
  }
}

// --- Assessments ---
const assessments = ref<any[]>([]);
const loadingAssessments = ref(false);
const assessmentModalOpen = ref(false);
const savingAssessment = ref(false);
const editingAssessment = ref<any>(null);
const assessmentForm = ref({ name: '', code: '', max_score: 20, order: 1, is_active: true });

// Assessment sort state
const assessmentSortBy = ref('order');
const assessmentSortOrder = ref<'asc' | 'desc'>('asc');
const sortedAssessments = computed(() => {
  const key = assessmentSortBy.value;
  return [...assessments.value].sort((a, b) => {
    let av = a[key], bv = b[key];
    if (typeof av === 'string') av = av.toLowerCase();
    if (typeof bv === 'string') bv = bv.toLowerCase();
    if (av < bv) return assessmentSortOrder.value === 'asc' ? -1 : 1;
    if (av > bv) return assessmentSortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});
function handleAssessmentSort(key: string) {
  if (assessmentSortBy.value === key) {
    assessmentSortOrder.value = assessmentSortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    assessmentSortBy.value = key;
    assessmentSortOrder.value = 'asc';
  }
}

// Assessment pagination
const assessmentPage = ref(1);
const assessmentPerPage = ref(10);
const assessmentPagedItems = computed(() => {
  const start = (assessmentPage.value - 1) * assessmentPerPage.value;
  return sortedAssessments.value.slice(start, start + assessmentPerPage.value);
});
const assessmentPagination = computed(() => ({
  currentPage: assessmentPage.value,
  lastPage: Math.max(1, Math.ceil(sortedAssessments.value.length / assessmentPerPage.value)),
  total: sortedAssessments.value.length,
  perPage: assessmentPerPage.value,
}));
function onAssessmentPageChange(p: number) { assessmentPage.value = p; }
function onAssessmentPerPageChange(n: number) { assessmentPerPage.value = n; assessmentPage.value = 1; }

const totalWeight = computed(() => assessments.value.filter(a => a.is_active).reduce((sum, a) => sum + parseFloat(a.max_score), 0));

async function loadAssessments() {
  loadingAssessments.value = true;
  try {
    const { data } = await apiClient.get('/academic/config/assessments');
    assessments.value = data.assessments || [];
  } finally {
    loadingAssessments.value = false;
  }
}

function openAssessmentModal(a: any) {
  editingAssessment.value = a;
  assessmentForm.value = a
    ? { name: a.name, code: a.code, max_score: parseFloat(a.max_score), order: a.order, is_active: !!a.is_active }
    : { name: '', code: '', max_score: 20, order: (assessments.value.length + 1), is_active: true };
  assessmentModalOpen.value = true;
}

async function saveAssessment() {
  if (!assessmentForm.value.name || !assessmentForm.value.code) { toast.error('Name and Code are required'); return; }
  savingAssessment.value = true;
  try {
    if (editingAssessment.value?.id) {
      await apiClient.put(`/academic/config/assessments/${editingAssessment.value.id}`, assessmentForm.value);
    } else {
      await apiClient.post('/academic/config/assessments', assessmentForm.value);
    }
    assessmentModalOpen.value = false;
    await loadAssessments();
  } catch (e: any) {
    if (!e?.response?.data?.message) {
        toast.error('Failed to save assessment type');
    }
  } finally {
    savingAssessment.value = false;
  }
}

async function deleteAssessment(a: any) {
  if (!confirm(`Delete "${a.name}"? This cannot be undone.`)) return;
  try {
    await apiClient.delete(`/academic/config/assessments/${a.id}`);
    await loadAssessments();
  } catch (e: any) { 
    if (!e?.response?.data?.message) {
        toast.error('Failed to delete assessment type'); 
    }
  }
}

// --- ID Card Settings ---
const loadingIdCard  = ref(false);
const savingIdCard   = ref(false);
const uploadingLogo  = ref(false);
const currentLogoUrl = ref<string | null>(null);

const idCardForm = ref<Record<string, any>>({
  'id_card.title_am':       'የደቂቀ ብርሃን ሰንበት ትምህርት ቤት መታወቂያ',
  'id_card.title_en':       'Dekike Birhan Senbet School ID Card',
  'id_card.title_or':       'Waraqaa Eenyummaa Mana Barumsaa Dekike Birhan Senbet',
  'id_card.authority_am':   'ሰጪው አካል',
  'id_card.authority_en':   'Issuing Authority',
  'id_card.authority_or':   'Qaama Kennaa',
  'id_card.id_prefix':      'DBSS',
  'id_card.validity_years': 2,
});

async function loadIdCardSettings() {
  loadingIdCard.value = true;
  try {
    const { data } = await apiClient.get('/bot/settings/id-card');
    Object.entries(data).forEach(([k, v]) => {
      if (k !== 'id_card.logo') idCardForm.value[k] = v;
    });
    if (data['id_card.logo']) currentLogoUrl.value = data['id_card.logo'];
  } catch { /* use defaults */ } finally {
    loadingIdCard.value = false;
  }
}

async function handleLogoUpload(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  uploadingLogo.value = true;
  try {
    const form = new FormData();
    form.append('logo', file);
    const { data } = await apiClient.post('/bot/settings/id-card/logo', form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    currentLogoUrl.value = data.logo;
    toast.success('Logo uploaded successfully');
  } catch {
    toast.error('Failed to upload logo');
  } finally {
    uploadingLogo.value = false;
  }
}

async function saveIdCardSettings() {
  savingIdCard.value = true;
  try {
    await apiClient.put('/bot/settings', { settings: idCardForm.value });
    toast.success($tr('id_card.config.saved', 'ID Card settings saved'));
  } catch {
    toast.error($tr('id_card.config.save_failed', 'Failed to save'));
  } finally {
    savingIdCard.value = false;
  }
}

// --- Payment Settings ---
const savingPayments = ref(false);
const paymentForm = ref<Record<string, any>>({
  'payment.student_amount': 50,
  'payment.worker_amount': 200,
  'payment.student_fine_per_month': 10,
  'payment.worker_fine_per_month': 20,
  'payment.minimum_grade_level': 3,
  'payment.minimum_age': 18,
  'payment.deadline_day': 10,
  'payment.calendar_type': 'ethiopian',
  'payment.is_enabled': true,
  'payment.payment_info': '',
});

async function savePaymentSettings() {
  savingPayments.value = true;
  try {
    await apiClient.put('/bot/settings', { settings: paymentForm.value });
    toast.success($tr('payments.config.saved', 'Payment settings saved'));
  } catch (err: any) {
    toast.error($tr('payments.config.save_failed', 'Failed to save'));
  } finally {
    savingPayments.value = false;
  }
}

async function loadPaymentSettings() {
  try {
    const { data } = await apiClient.get('/bot/settings/payments');
    Object.entries(data).forEach(([k, v]) => {
      paymentForm.value[k] = v;
    });
  } catch { /* use defaults */ }
}

onMounted(() => { loadClasses(); loadAssessments(); loadIdCardSettings(); loadPaymentSettings(); });
</script>
