<script setup lang="ts">
import { ref, watch, computed, getCurrentInstance } from 'vue';
import { X, Printer, ShieldCheck, UserCircle2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Modal from '@/components/common/Modal.vue';
import Button from '@/components/common/Button.vue';
import FormField from '@/components/common/FormField.vue';
import { localize, capitalize } from '@/utils/format';
import { useLanguageStore } from '@/stores/languageStore';

const props = defineProps<{
    show: boolean;
    users: any[];
}>();

const emit = defineEmits(['close']);
const languageStore = useLanguageStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

interface CardData {
    id: number;
    name: string;
    registration_id: string;
    phone: string;
    christian_name: string;
    grade_level: string;
    emergency_name: string;
    emergency_phone: string;
    chairman_name: string;
    issue_date: string;
    expiry_date: string;
    profile_picture: string;
}

const cardDataList = ref<CardData[]>([]);
const currentIndex = ref(0);
const currentCard = computed(() => cardDataList.value[currentIndex.value] ?? {} as CardData);

function formatPhone(phone: string): string {
    if (!phone) return '';
    const digits = phone.replace(/\D/g, '');
    if (digits.startsWith('251')) return `+${digits}`;
    if (digits.startsWith('0')) return `+251${digits.slice(1)}`;
    if (digits.length === 9) return `+251${digits}`;
    return phone.startsWith('+') ? phone : `+${phone}`;
}

function defaultIssueDate(): string {
    return new Date().toISOString().split('T')[0];
}

function defaultExpiryDate(): string {
    const d = new Date();
    d.setFullYear(d.getFullYear() + 2);
    return d.toISOString().split('T')[0];
}

watch(() => props.show, (newVal) => {
    if (newVal && props.users && props.users.length > 0) {
        currentIndex.value = 0;
        const lang = languageStore.currentLanguage;
        cardDataList.value = props.users.map(user => {
            const firstName = user.name ? localize(user.name, lang) : '';
            const fatherName = user.info?.father_name ? localize(user.info.father_name, lang) : '';
            const fullName = `${capitalize(firstName)} ${capitalize(fatherName)}`.trim();
            const christianName = user.info?.christian_name ? localize(user.info.christian_name, lang) : '';
            // API returns phone without +251 prefix (it strips it), so re-add
            const rawPhone = user.info?.phone_number || '';
            return {
                id: user.id,
                name: fullName,
                registration_id: user.info?.registration_id || 'DBSS-000000',
                phone: formatPhone(rawPhone),
                christian_name: christianName,
                grade_level: user.senbet_membership?.senbet_class || '',
                // Populate from DB if available
                emergency_name: user.info?.emergency_name || '',
                emergency_phone: formatPhone(user.info?.emergency_phone || ''),
                chairman_name: '',
                issue_date: defaultIssueDate(),
                expiry_date: defaultExpiryDate(),
                profile_picture: user.info?.profile_picture || user.profile_picture || ''
            };
        });
    }
});

const nextCard = () => { if (currentIndex.value < cardDataList.value.length - 1) currentIndex.value++; };
const prevCard = () => { if (currentIndex.value > 0) currentIndex.value--; };

function formatDateDisplay(dateStr: string): string {
    if (!dateStr) return '---';
    try {
        return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch { return dateStr; }
}

function buildCardHtml(card: CardData): string {
    const schoolName   = $tr('user.senbet_school_id', 'Dekike Birhan Senbet School');
    const idLabel      = $tr('user.id_card_label', 'Student Identification Card');
    const labelName    = $tr('user.form.full_name', 'Name');
    const labelChrist  = $tr('user.form.christian_name', 'Christian Name');
    const labelGrade   = $tr('academic.class', 'Grade Level');
    const labelPhone   = $tr('user.form.phone_number', 'Phone');
    const labelEmerg   = $tr('user.emergency_contact', 'Emergency Contact');
    const labelIssue   = $tr('user.issue_date', 'Issue Date');
    const labelExpiry  = $tr('user.expiry_date', 'Expiry Date');
    const labelChairSig = $tr('user.chairman_signature', 'Signature');
    const labelChair   = $tr('user.chairman_name', 'Chairman');

    const photo = card.profile_picture
        ? `<img src="${card.profile_picture}" class="photo-img" />`
        : `<div class="photo-ph"></div>`;

    return `
<div class="card">
  <div class="top-stripe"></div>
  <div class="card-header">
    <svg class="shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      <path d="M9 12l2 2 4-4"/>
    </svg>
    <div class="school-name">${schoolName}</div>
    <div class="card-sub">${idLabel}</div>
  </div>
  <div class="card-body">
    <div class="photo-col">
      <div class="photo">${photo}</div>
      <div class="reg-id">${card.registration_id || '---'}</div>
    </div>
    <div class="info-col">
      <div class="field"><div class="lbl">${labelName}</div><div class="val name-val">${card.name || '---'}</div></div>
      <div class="field"><div class="lbl">${labelChrist}</div><div class="val">${card.christian_name || '---'}</div></div>
      <div class="row2">
        <div class="field"><div class="lbl">${labelGrade}</div><div class="val">${card.grade_level || '---'}</div></div>
        <div class="field"><div class="lbl">${labelPhone}</div><div class="val mono">${card.phone || '---'}</div></div>
      </div>
      <div class="row2">
        <div class="field"><div class="lbl">${labelIssue}</div><div class="val mono">${formatDateDisplay(card.issue_date)}</div></div>
        <div class="field"><div class="lbl">${labelExpiry}</div><div class="val mono">${formatDateDisplay(card.expiry_date)}</div></div>
      </div>
    </div>
  </div>
  <div class="card-footer">
    <div class="emerg">
      <div class="lbl">${labelEmerg}</div>
      <div class="val">${card.emergency_name || '---'}</div>
      <div class="val mono">${card.emergency_phone || '---'}</div>
    </div>
    <div class="chairman">
      <div class="sig-space"></div>
      <div class="sig-line"></div>
      <div class="chair-name">${card.chairman_name || '_______________'}</div>
      <div class="lbl">${labelChair} &amp; ${labelChairSig}</div>
    </div>
  </div>
  <div class="bottom-stripe"></div>
</div>`;
}

function handlePrint() {
    const css = `
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff; font-family:'Segoe UI',Arial,sans-serif; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.page { padding:12mm; }
.grid { display:grid; grid-template-columns:repeat(2, 85.6mm); gap:7mm 10mm; justify-content:center; }
.card { position:relative; width:85.6mm; height:54mm; background:#fff; border:1px solid #bbb; border-radius:3mm; overflow:hidden; display:flex; flex-direction:column; }
.top-stripe { position:absolute; top:0; left:0; right:0; height:2.5px; background:#166534; z-index:1; }
.bottom-stripe { position:absolute; bottom:0; left:0; right:0; height:2px; background:#166534; z-index:1; }
.card-header { text-align:center; padding:5px 6px 3px; border-bottom:1px solid #e5e7eb; background:#f9fafb; margin-top:3px; flex-shrink:0; display:flex; flex-direction:column; align-items:center; }
.shield { width:14px; height:14px; color:#16a34a; margin-bottom:1px; }
.school-name { font-size:8pt; font-weight:800; color:#111; letter-spacing:0.01em; line-height:1.3; }
.card-sub { font-size:5.5pt; color:#6b7280; margin-top:1px; }
.card-body { display:flex; flex:1; padding:4px 5px; gap:5px; overflow:hidden; }
.photo-col { display:flex; flex-direction:column; align-items:center; gap:2px; flex-shrink:0; }
.photo { width:17mm; height:21mm; border:1px solid #ccc; background:#f3f4f6; overflow:hidden; }
.photo-img { width:100%; height:100%; object-fit:cover; }
.photo-ph { width:100%; height:100%; background:#e5e7eb; }
.reg-id { font-size:5.5pt; font-weight:700; font-family:monospace; color:#111; border:1px solid #d1d5db; padding:1px 3px; background:#f9fafb; text-align:center; width:100%; }
.info-col { flex:1; display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; gap:2px; }
.row2 { display:grid; grid-template-columns:1fr 1fr; gap:3px; }
.field { display:flex; flex-direction:column; overflow:hidden; }
.lbl { font-size:5pt; font-weight:600; color:#9ca3af; letter-spacing:0.04em; line-height:1.3; }
.val { font-size:7pt; font-weight:700; color:#111; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.name-val { font-size:8pt; }
.mono { font-family:monospace; font-size:6.5pt; }
.card-footer { display:flex; align-items:flex-end; justify-content:space-between; padding:3px 5px 5px; border-top:1px solid #f0f0f0; flex-shrink:0; }
.emerg { display:flex; flex-direction:column; gap:1px; }
.chairman { display:flex; flex-direction:column; align-items:center; gap:1px; }
.sig-space { height:10px; }
.sig-line { width:22mm; border-bottom:1px solid #374151; margin-bottom:1px; }
.chair-name { font-size:6.5pt; font-weight:600; color:#374151; }
@media print { @page { size:A4 portrait; margin:0; } body { margin:0; } }
`;

    const cardsHtml = cardDataList.value.map(buildCardHtml).join('');
    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>ID Cards</title><style>${css}</style></head>
<body>
  <div class="page"><div class="grid">${cardsHtml}</div></div>
  <script>window.onload=function(){window.print();window.onafterprint=function(){window.close();};};<\/script>
</body></html>`;

    const win = window.open('', '_blank', 'width=900,height=650');
    if (win) { win.document.write(html); win.document.close(); }
}
</script>

<template>
    <Modal :show="show" @close="emit('close')" size="2xl" hide-header no-padding>
        <!-- Compact header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-card-border/40 bg-card-bg shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-semibold text-main-text text-sm">{{ $tr('user.generate_id_card', 'Generate ID Card') }}</span>
                <div v-if="cardDataList.length > 1" class="flex items-center bg-main-bg rounded-md border border-card-border/50 overflow-hidden text-xs">
                    <button @click="prevCard" :disabled="currentIndex === 0" class="px-2 py-1.5 text-main-text/70 hover:bg-card-border/20 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <ChevronLeft class="w-3.5 h-3.5" />
                    </button>
                    <span class="px-2 font-medium text-main-text border-x border-card-border/50 py-1.5">{{ currentIndex + 1 }}/{{ cardDataList.length }}</span>
                    <button @click="nextCard" :disabled="currentIndex === cardDataList.length - 1" class="px-2 py-1.5 text-main-text/70 hover:bg-card-border/20 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <ChevronRight class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="primary" size="sm" :icon="Printer" @click="handlePrint">
                    {{ $tr('action.print', 'Print / Save PDF') }}<span v-if="cardDataList.length > 1"> ({{ cardDataList.length }})</span>
                </Button>
                <button @click="emit('close')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-rose-500/10 text-main-text/50 hover:text-rose-500 transition-all">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Two-column layout -->
        <div class="flex min-h-0 overflow-hidden" style="height: 560px;">
            <!-- Left: edit form -->
            <div class="w-72 shrink-0 border-r border-card-border/40 overflow-y-auto p-5 bg-main-bg/40">
                <p class="text-[11px] uppercase tracking-widest font-semibold text-main-text/40 mb-4">{{ $tr('user.edit_id_info', 'Edit ID Information') }}</p>
                <div class="space-y-3" v-if="cardDataList.length > 0">
                    <FormField v-model="cardDataList[currentIndex].name" :label="$tr('user.form.full_name', 'Full Name')" />
                    <FormField v-model="cardDataList[currentIndex].registration_id" :label="$tr('user.form.registration_id', 'Registration ID')" />
                    <FormField v-model="cardDataList[currentIndex].christian_name" :label="$tr('user.form.christian_name', 'Christian Name')" />
                    <FormField v-model="cardDataList[currentIndex].grade_level" :label="$tr('academic.class', 'Grade / Class')" />
                    <FormField v-model="cardDataList[currentIndex].phone" :label="$tr('user.form.phone_number', 'Phone')" />
                    <FormField v-model="cardDataList[currentIndex].issue_date" type="date" :label="$tr('user.issue_date', 'Issue Date')" />
                    <FormField v-model="cardDataList[currentIndex].expiry_date" type="date" :label="$tr('user.expiry_date', 'Expiry Date')" />
                    <div class="pt-3 border-t border-card-border/40">
                        <p class="text-[11px] uppercase tracking-widest font-semibold text-main-text/40 mb-3">{{ $tr('user.emergency_contact', 'Emergency Contact') }}</p>
                        <div class="space-y-3">
                            <FormField v-model="cardDataList[currentIndex].emergency_name" :label="$tr('user.form.emergency_name', 'Emergency Contact Name')" />
                            <FormField v-model="cardDataList[currentIndex].emergency_phone" :label="$tr('user.form.emergency_phone', 'Emergency Phone')" />
                        </div>
                    </div>
                    <div class="pt-3 border-t border-card-border/40">
                        <p class="text-[11px] uppercase tracking-widest font-semibold text-main-text/40 mb-3">{{ $tr('user.chairman_name', 'Chairman') }}</p>
                        <FormField v-model="cardDataList[currentIndex].chairman_name" :label="$tr('user.chairman_name', 'Chairman Full Name')" />
                    </div>
                </div>
            </div>

            <!-- Right: Live card preview -->
            <div class="flex-1 bg-gray-100 dark:bg-gray-900/50 flex items-center justify-center overflow-hidden p-6">
                <div v-if="currentCard.id" class="card-shell">
                    <div class="top-stripe"></div>

                    <!-- Header: centered school name -->
                    <div class="card-header">
                        <ShieldCheck class="card-shield" />
                        <div class="school-name">{{ $tr('user.senbet_school_id', 'Dekike Birhan Senbet School') }}</div>
                        <div class="card-sub">{{ $tr('user.id_card_label', 'Student Identification Card') }}</div>
                    </div>

                    <!-- Body -->
                    <div class="card-body">
                        <!-- Photo -->
                        <div class="photo-col">
                            <div class="photo">
                                <img v-if="currentCard.profile_picture" :src="currentCard.profile_picture" class="photo-img" />
                                <UserCircle2 v-else class="photo-ph" />
                            </div>
                            <div class="reg-id">{{ currentCard.registration_id || '---' }}</div>
                        </div>
                        <!-- Info -->
                        <div class="info-col">
                            <div class="field">
                                <div class="lbl">{{ $tr('user.form.full_name', 'Name') }}</div>
                                <div class="val name-val">{{ currentCard.name || '---' }}</div>
                            </div>
                            <div class="field">
                                <div class="lbl">{{ $tr('user.form.christian_name', 'Christian Name') }}</div>
                                <div class="val">{{ currentCard.christian_name || '---' }}</div>
                            </div>
                            <div class="row2">
                                <div class="field">
                                    <div class="lbl">{{ $tr('academic.class', 'Grade') }}</div>
                                    <div class="val">{{ currentCard.grade_level || '---' }}</div>
                                </div>
                                <div class="field">
                                    <div class="lbl">{{ $tr('user.form.phone_number', 'Phone') }}</div>
                                    <div class="val mono">{{ currentCard.phone || '---' }}</div>
                                </div>
                            </div>
                            <div class="row2">
                                <div class="field">
                                    <div class="lbl">{{ $tr('user.issue_date', 'Issue Date') }}</div>
                                    <div class="val mono">{{ formatDateDisplay(currentCard.issue_date) }}</div>
                                </div>
                                <div class="field">
                                    <div class="lbl">{{ $tr('user.expiry_date', 'Expiry Date') }}</div>
                                    <div class="val mono">{{ formatDateDisplay(currentCard.expiry_date) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer">
                        <div class="emerg">
                            <div class="lbl">{{ $tr('user.emergency_contact', 'Emergency Contact') }}</div>
                            <div class="val">{{ currentCard.emergency_name || '---' }}</div>
                            <div class="val mono">{{ currentCard.emergency_phone || '---' }}</div>
                        </div>
                        <div class="chairman-block">
                            <div class="sig-space"></div>
                            <div class="sig-line"></div>
                            <div class="chair-name-val">{{ currentCard.chairman_name || '_______________' }}</div>
                            <div class="lbl">{{ $tr('user.chairman_name', 'Chairman') }} &amp; {{ $tr('user.chairman_signature', 'Signature') }}</div>
                        </div>
                    </div>

                    <div class="bottom-stripe"></div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
/* Card shell — scaled CR80 (85.6 × 54 mm) for screen preview */
.card-shell {
    position: relative;
    width: 390px;
    height: 246px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0,0,0,0.10);
    flex-shrink: 0;
}

/* Accent stripes */
.top-stripe { position:absolute; top:0; left:0; right:0; height:3px; background:#166534; z-index:2; }
.bottom-stripe { position:absolute; bottom:0; left:0; right:0; height:2px; background:#166534; z-index:2; }

/* Header — centered */
.card-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 7px 8px 4px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
    margin-top: 3px;
    flex-shrink: 0;
}
.card-shield { width: 16px; height: 16px; color: #16a34a; margin-bottom: 2px; }
.school-name { font-size: 10px; font-weight: 800; color: #111827; letter-spacing: 0.01em; }
.card-sub { font-size: 6.5px; color: #6b7280; margin-top: 1px; }

/* Body */
.card-body { display:flex; flex:1; padding:6px 7px 4px; gap:7px; overflow:hidden; }

/* Photo col */
.photo-col { display:flex; flex-direction:column; align-items:center; gap:3px; flex-shrink:0; }
.photo { width:56px; height:68px; border:1px solid #d1d5db; background:#f3f4f6; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.photo-img { width:100%; height:100%; object-fit:cover; }
.photo-ph { width:28px; height:28px; color:#9ca3af; }
.reg-id { font-size:7.5px; font-weight:700; font-family:monospace; color:#111; border:1px solid #d1d5db; padding:2px 3px; background:#f9fafb; text-align:center; width:100%; }

/* Info col */
.info-col { flex:1; display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; gap:2px; }
.row2 { display:grid; grid-template-columns:1fr 1fr; gap:4px; }
.field { display:flex; flex-direction:column; overflow:hidden; }
.lbl { font-size:6.5px; font-weight:600; color:#9ca3af; letter-spacing:0.04em; line-height:1.3; }
.val { font-size:8.5px; font-weight:700; color:#111827; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.name-val { font-size:10px; }
.mono { font-family:monospace; font-size:8px; }

/* Footer */
.card-footer { display:flex; align-items:flex-end; justify-content:space-between; padding:4px 7px 7px; border-top:1px solid #f0f0f0; flex-shrink:0; }
.emerg { display:flex; flex-direction:column; gap:1px; }
.chairman-block { display:flex; flex-direction:column; align-items:center; gap:1px; }
.sig-space { height:10px; }
.sig-line { width:88px; border-bottom:1px solid #374151; margin-bottom:1px; }
.chair-name-val { font-size:8px; font-weight:600; color:#374151; }
</style>
