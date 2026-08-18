<script setup lang="ts">
import { ref, watch, computed, getCurrentInstance } from 'vue';
import { X, Printer, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Modal from '@/components/common/Modal.vue';
import Button from '@/components/common/Button.vue';
import FormField from '@/components/common/FormField.vue';
import { localize, capitalize } from '@/utils/format';
import { useLanguageStore } from '@/stores/languageStore';
import apiClient from '@/api/apiClient';

const props = defineProps<{ show: boolean; users: any[] }>();
const emit = defineEmits(['close']);
const languageStore = useLanguageStore();
const lang = computed(() => languageStore.currentLanguage);
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

interface CardData {
    id: number; name: string; registration_id: string; phone: string;
    christian_name: string; grade_level: string; gender: string; age: string;
    emergency_name: string; emergency_phone: string; chairman_name: string;
    issue_date: string; expiry_date: string; profile_picture: string;
    address: string; nationality?: string;
}

interface IdCardSettings {
    'id_card.title_am': string; 'id_card.title_en': string; 'id_card.title_or': string;
    'id_card.authority_am': string; 'id_card.authority_en': string; 'id_card.authority_or': string;
    'id_card.id_prefix': string; 'id_card.validity_years': number; 'id_card.logo': string | null;
}

const cardDataList = ref<CardData[]>([]);
const currentIndex = ref(0);
const currentCard  = computed(() => cardDataList.value[currentIndex.value] ?? {} as CardData);

const idCardSettings = ref<IdCardSettings>({
    'id_card.title_am':       'የደቂቀ ብርሃን ሰንበት ትምህርት ቤት መታወቂያ',
    'id_card.title_en':       'Dekike Birhan Senbet School ID Card',
    'id_card.title_or':       'Waraqaa Eenyummaa Mana Barumsaa Dekike Birhan Senbet',
    'id_card.authority_am':   'ሰጪው አካል',
    'id_card.authority_en':   'Issuing Authority',
    'id_card.authority_or':   'Qaama Kennaa',
    'id_card.id_prefix':      'DBSS',
    'id_card.validity_years': 2,
    'id_card.logo':           null,
});

async function fetchIdCardSettings() {
    try {
        const { data } = await apiClient.get('/bot/settings/id-card');
        idCardSettings.value = { ...idCardSettings.value, ...data };
    } catch { /* use defaults */ }
}

/* ── Helpers ─────────────────────────────────────────── */
function formatPhone(phone: string): string {
    if (!phone) return '';
    const d = phone.replace(/\D/g, '');
    if (d.startsWith('251')) return `+${d}`;
    if (d.startsWith('0'))   return `+251${d.slice(1)}`;
    if (d.length === 9)      return `+251${d}`;
    return phone.startsWith('+') ? phone : `+${phone}`;
}

function computeAge(birthDate: string | null): string {
    if (!birthDate) return '---';
    const d = new Date(birthDate);
    if (isNaN(d.getTime())) return '---';
    const age = Math.floor((Date.now() - d.getTime()) / (1000 * 60 * 60 * 24 * 365.25));
    return (age > 0 && age < 120) ? String(age) : '---';
}

function formatGender(g: string | null): string {
    if (!g) return '---';
    const t = g.toLowerCase();
    if (lang.value === 'am') return t === 'male' ? 'ወንድ' : 'ሴት';
    if (lang.value === 'or') return t === 'male' ? 'Dhiira' : 'Dubartii';
    return t === 'male' ? 'Male' : 'Female';
}

function defaultIssueDate() { return new Date().toISOString().split('T')[0]; }
function defaultExpiryDate() {
    const d = new Date();
    d.setFullYear(d.getFullYear() + (Number(idCardSettings.value['id_card.validity_years']) || 2));
    return d.toISOString().split('T')[0];
}

watch(() => props.show, async (v) => {
    if (v && props.users?.length) {
        currentIndex.value = 0;
        await fetchIdCardSettings();
        const l = lang.value;
        cardDataList.value = props.users.map(user => {
            const firstName  = user.name ? localize(user.name, l) : '';
            const fatherName = user.info?.father_name ? localize(user.info.father_name, l) : '';
            return {
                id: user.id,
                name: `${capitalize(firstName)} ${capitalize(fatherName)}`.trim(),
                registration_id: user.info?.registration_id || `${idCardSettings.value['id_card.id_prefix']}-000000`,
                phone:           formatPhone(user.info?.phone_number || ''),
                christian_name:  user.info?.christian_name ? localize(user.info.christian_name, l) : '',
                grade_level:     user.senbet_membership?.senbet_class || user.senbetMembership?.senbet_class || '',
                gender:          formatGender(user.gender || user.info?.gender),
                age:             computeAge(user.birth_date || user.info?.birth_date),
                address:         user.info?.address || '',
                emergency_name:  user.info?.emergency_name || '',
                emergency_phone: formatPhone(user.info?.emergency_phone || ''),
                chairman_name:   '',
                nationality:     l === 'am' ? 'ኢትዮጵያዊ' : l === 'or' ? 'Itoophiyaa' : 'Ethiopian',
                issue_date:      defaultIssueDate(),
                expiry_date:     defaultExpiryDate(),
                profile_picture: user.profile_picture || user.avatar || '',
            };
        });
    }
});

const nextCard = () => { if (currentIndex.value < cardDataList.value.length - 1) currentIndex.value++; };
const prevCard = () => { if (currentIndex.value > 0) currentIndex.value--; };

function fmtDate(s: string): string {
    if (!s) return '---';
    try { return new Date(s).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); }
    catch { return s; }
}

/* ── Per-language labels (single language) ──────────── */
function getLabels() {
    return {
        regNo:     $tr('id_card.reg_no', 'Reg. No'),
        idNo:      $tr('id_card.id_no', 'ID No'),
        fullName:  $tr('user.form.full_name', 'Full Name'),
        chrName:   $tr('id_card.chr_name', 'Chr. Name'),
        grade:     $tr('academic.class', 'Grade'),
        phone:     $tr('id_card.phone_no', 'Phone No'),
        gender:    $tr('user.form.gender', 'Gender'),
        age:       $tr('user.age', 'Age'),
        emerg:     $tr('id_card.emergency_contact', 'Emerg. Contact'),
        address:   $tr('user.address', 'Address'),
        issued:    $tr('id_card.issued', 'Issued'),
        expiry:    $tr('id_card.expiry', 'Expiry'),
        authTitle: $tr('id_card.auth_title', 'Issuing Authority'),
        nat:       $tr('user.nationality', 'Nationality')
    };
}

/* ── HTML builder ────────────────────────────────────── */
function buildCardHtml(card: CardData): string {
    const photo = card.profile_picture
        ? `<img src="${card.profile_picture}" class="photo-img" crossorigin="anonymous" />`
        : `<div class="photo-ph">Photo</div>`;

    const logo = idCardSettings.value['id_card.logo']
        ? `<img src="${idCardSettings.value['id_card.logo']}" class="school-logo" crossorigin="anonymous" />`
        : '';

    const lbl = getLabels();
    const l   = lang.value;
    const titlePrimary   = l === 'am' ? idCardSettings.value['id_card.title_am']
                         : l === 'or' ? idCardSettings.value['id_card.title_or']
                         :              idCardSettings.value['id_card.title_en'];
    const titleSecondary = l === 'am' ? idCardSettings.value['id_card.title_en']
                         : l === 'or' ? idCardSettings.value['id_card.title_en']
                         :              idCardSettings.value['id_card.title_am'];
    const authority = (l === 'am' ? idCardSettings.value['id_card.authority_am']
                    : l === 'or' ? idCardSettings.value['id_card.authority_or']
                    :              idCardSettings.value['id_card.authority_en']) || '---';
    const natValue = card.nationality || (l === 'am' ? 'ኢትዮጵያዊ' : l === 'or' ? 'Itoophiyaa' : 'Ethiopian');

    const r = (lbl: string, val: string, mono = false, extraStyle = '') =>
        `<div class="row" ${extraStyle ? `style="${extraStyle}"` : ''}><span class="lbl">${lbl}</span><span class="val${mono ? ' mono' : ''}">${val}</span></div>`;

    return `
<div class="card">
  <div class="card-header">
    ${logo ? `<div class="logo-wrap">${logo}</div>` : '<div class="logo-wrap"></div>'}
    <div class="titles">
      <div class="title-primary">${titlePrimary}</div>
      <div class="title-secondary">${titleSecondary}</div>
    </div>
    <div></div> <!-- Empty right col for exact grid centering -->
  </div>

  <div class="reg-row">
    <span class="lbl">${lbl.regNo}</span>
    <span class="val mono">${card.registration_id || '---'}</span>
  </div>

  <div class="main-body">
    <div class="left-col">
      <div class="photo-box">${photo}</div>
      <div class="sig-box"></div>
      <div class="sig-name">${card.chairman_name || '&nbsp;'}</div>
      <div class="auth-lbl">${authority}</div>
    </div>

    <div class="right-col" style="position:relative; z-index:1;">
      ${logo ? `<img src="${idCardSettings.value['id_card.logo']}" class="watermark-logo" crossorigin="anonymous" />` : ''}
      ${r(lbl.fullName, card.name || '---')}
      ${r(lbl.chrName,  card.christian_name || '---')}
      <div class="split-row" style="gap:1mm;">
        ${r(lbl.gender, card.gender || '---')}
        ${r(lbl.age,    card.age    || '---')}
        ${r(lbl.grade, card.grade_level || '---')}
      </div>
      <div class="split-row" style="gap:0;">
        ${r(lbl.phone, card.phone || '---', true, 'flex:1.1;')}
        ${r(lbl.nat, natValue, false, 'flex:0.9;')}
      </div>
      ${r(lbl.address, card.address || '---')}
      <div class="row emerg-row">
        <span class="lbl">${lbl.emerg}</span>
        <span class="val">${card.emergency_name}${card.emergency_phone ? ' · ' + card.emergency_phone : ''}</span>
      </div>
      <div class="split-row">
        ${r(lbl.issued, fmtDate(card.issue_date), true)}
        ${r(lbl.expiry, fmtDate(card.expiry_date), true)}
      </div>
    </div>
  </div>
</div>`;
}

/* ── Print handler ───────────────────────────────────── */
function handlePrint() {
    const css = `
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff; font-family:'Segoe UI',Arial,sans-serif; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.page { padding:8mm; }
.grid { display:grid; grid-template-columns:repeat(2,85.6mm); gap:6mm; justify-content:center; }

.card { width:85.6mm; height:54mm; background:#fff; border:.3mm solid #b6c4d8; border-radius:2mm; overflow:hidden; padding:1.8mm 2mm 1.8mm; display:flex; flex-direction:column; }

/* ── Header ── */
.card-header { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; border-bottom:.25mm solid #2563eb30; padding-bottom:1mm; margin-bottom:1mm; }
.logo-wrap { display:flex; justify-content:center; }
.school-logo { width:14mm; height:8mm; object-fit:contain; }
.titles { text-align:center; }
.title-primary   { font-size:8pt; font-weight:800; color:#000; line-height:1.25; }
.title-secondary { font-size:6.5pt; font-weight:600; color:#000; line-height:1.2; }

/* ── Reg row ── */
.reg-row { display:flex; align-items:baseline; gap:.8mm; font-size:7pt; margin-bottom:1mm; }

/* ── Main body ── */
.main-body { display:flex; gap:2mm; flex:1; min-height:0; }

/* ── Left col (photo + sig) ── */
.left-col { width:23mm; display:flex; flex-direction:column; align-items:center; flex-shrink:0; }
.photo-box { width:23mm; height:24mm; border:.3mm solid #94a3b8; background:#f1f5f9; margin-bottom:.5mm; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.photo-img { width:100%; height:100%; object-fit:cover; }
.photo-ph { font-size:5pt; color:#94a3b8; }
.sig-box { width:23mm; height:4mm; border-bottom:.35mm dashed #374151; margin-bottom:.5mm; margin-top:auto; }
.sig-name { font-size:5pt; font-weight:700; color:#000; text-align:center; line-height:1.1; width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.auth-lbl { font-size:5pt; font-weight:700; color:#000; text-align:center; line-height:1.1; margin-top:1mm; width:100%; }

/* ── Right col (fields) ── */
.right-col { flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:.8mm; min-width:0; }
.row { display:flex; align-items:baseline; gap:.8mm; font-size:7pt; min-width:0; }
.split-row { display:flex; gap:1.5mm; }
.split-row .row { flex:1; }
.lbl  { color:#000; font-weight:500; white-space:nowrap; flex-shrink:0; }
.val  { color:#000; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0; }
.mono { font-family:monospace; }
.emerg-row .val { white-space:normal; line-height:1.2; }
.watermark-logo { position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:40mm; height:40mm; object-fit:contain; opacity:0.25; z-index:-1; }

@media print { @page { size:A4 portrait; margin:0; } body { margin:0; } }
`;

    const cardsHtml = cardDataList.value.map(buildCardHtml).join('');
    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>ID Cards</title><style>${css}</style></head>
<body><div class="page"><div class="grid">${cardsHtml}</div></div>
<script>window.onload=function(){window.print();window.onafterprint=function(){window.close();};};<\/script>
</body></html>`;
    const win = window.open('', '_blank', 'width=900,height=650');
    if (win) { win.document.write(html); win.document.close(); }
}
</script>

<template>
    <Modal :show="show" @close="emit('close')" size="2xl" hide-header no-padding>
        <!-- Header bar -->
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

        <div class="flex min-h-0 overflow-hidden" style="height:580px;">
            <!-- Left: edit form -->
            <div class="w-80 shrink-0 border-r border-card-border/40 overflow-y-auto p-5 bg-main-bg/40 custom-scrollbar">
                <p class="text-[11px] uppercase tracking-widest font-semibold text-main-text/40 mb-4">{{ $tr('user.edit_id_info', 'Edit ID Information') }}</p>
                <div class="space-y-3" v-if="cardDataList.length > 0">
                    <FormField v-model="cardDataList[currentIndex].name"           :label="$tr('user.form.full_name', 'Full Name')" />
                    <FormField v-model="cardDataList[currentIndex].registration_id" :label="$tr('user.form.registration_id', 'Registration ID')" />
                    <FormField v-model="cardDataList[currentIndex].christian_name"  :label="$tr('user.form.christian_name', 'Christian Name')" />
                    <FormField v-model="cardDataList[currentIndex].grade_level"    :label="$tr('academic.class', 'Grade / Class')" />
                    <FormField v-model="cardDataList[currentIndex].phone"          :label="$tr('user.form.phone_number', 'Phone')" />
                    <FormField v-model="cardDataList[currentIndex].nationality"    :label="$tr('user.nationality', 'Nationality')" />
                    <FormField v-model="cardDataList[currentIndex].gender"         :label="$tr('user.form.gender', 'Gender')" />
                    <FormField v-model="cardDataList[currentIndex].age"            :label="$tr('user.age', 'Age')" />
                    <FormField v-model="cardDataList[currentIndex].address"        :label="$tr('user.address', 'Address')" />
                    <FormField v-model="cardDataList[currentIndex].issue_date"     type="date" :label="$tr('user.issue_date', 'Issue Date')" />
                    <FormField v-model="cardDataList[currentIndex].expiry_date"    type="date" :label="$tr('user.expiry_date', 'Expiry Date')" />
                    <div class="pt-3 border-t border-card-border/40">
                        <p class="text-[11px] uppercase tracking-widest font-semibold text-main-text/40 mb-3">{{ $tr('user.emergency_contact', 'Emergency Contact') }}</p>
                        <div class="space-y-3">
                            <FormField v-model="cardDataList[currentIndex].emergency_name"  :label="$tr('user.form.emergency_name', 'Contact Name')" />
                            <FormField v-model="cardDataList[currentIndex].emergency_phone" :label="$tr('user.form.emergency_phone', 'Contact Phone')" />
                        </div>
                    </div>
                    <div class="pt-3 border-t border-card-border/40">
                        <p class="text-[11px] uppercase tracking-widest font-semibold text-main-text/40 mb-3">{{ $tr('user.issuer', 'Issuer') }}</p>
                        <FormField v-model="cardDataList[currentIndex].chairman_name" :label="$tr('user.issuer_name', 'Issuer Name & Signature')" />
                    </div>
                </div>
            </div>

            <!-- Right: Live card preview -->
            <div class="flex-1 bg-gray-100 dark:bg-gray-900/50 flex items-center justify-center overflow-hidden p-6">
                <div class="preview-wrapper">
                    <div v-if="currentCard.id" class="card-shell" v-html="buildCardHtml(currentCard)"></div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width:5px; }
.custom-scrollbar::-webkit-scrollbar-track { background:transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background:rgba(156,163,175,.5); border-radius:10px; }

.preview-wrapper {
    transform: scale(1.15);
    transform-origin: center center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    border-radius: 2mm;
}

/* Preview card mirrors print CSS */
.card-shell {
    width:85.6mm; height:54mm; background:#fff; border:.3mm solid #b6c4d8;
    border-radius:2mm; overflow:hidden; padding:1.8mm 2mm;
    display:flex; flex-direction:column;
    font-family:'Segoe UI',Arial,sans-serif;
}

:deep(.card-header) { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; border-bottom:.25mm solid #2563eb30; padding-bottom:1mm; margin-bottom:1mm; }
:deep(.logo-wrap)   { display:flex; justify-content:center; }
:deep(.school-logo) { width:14mm; height:8mm; object-fit:contain; }
:deep(.titles)      { text-align:center; }
:deep(.title-primary)   { font-size:8pt; font-weight:800; color:#000; line-height:1.25; }
:deep(.title-secondary) { font-size:6.5pt; font-weight:600; color:#000; line-height:1.2; }

:deep(.reg-row) { display:flex; align-items:baseline; gap:.8mm; font-size:7pt; margin-bottom:1mm; }

:deep(.main-body) { display:flex; gap:2mm; flex:1; min-height:0; }

:deep(.left-col)  { width:23mm; display:flex; flex-direction:column; align-items:center; flex-shrink:0; }
:deep(.photo-box) { width:23mm; height:24mm; border:.3mm solid #94a3b8; background:#f1f5f9; margin-bottom:.5mm; overflow:hidden; display:flex; align-items:center; justify-content:center; }
:deep(.photo-img) { width:100%; height:100%; object-fit:cover; }
:deep(.photo-ph)  { font-size:5pt; color:#94a3b8; }
:deep(.sig-box)   { width:23mm; height:4mm; border-bottom:.35mm dashed #374151; margin-bottom:.5mm; margin-top:auto; }
:deep(.sig-name)  { font-size:5pt; font-weight:700; color:#000; text-align:center; line-height:1.1; width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
:deep(.auth-lbl)  { font-size:5pt; font-weight:700; color:#000; text-align:center; line-height:1.1; margin-top:1mm; width:100%; }

:deep(.right-col)  { flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:.8mm; min-width:0; }
:deep(.row)        { display:flex; align-items:baseline; gap:.8mm; font-size:7pt; min-width:0; }
:deep(.split-row)  { display:flex; gap:1.5mm; }
:deep(.split-row .row) { flex:1; }
:deep(.lbl)  { color:#000; font-weight:500; white-space:nowrap; flex-shrink:0; }
:deep(.val)  { color:#000; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0; }
:deep(.mono) { font-family:monospace; }
:deep(.emerg-row .val) { white-space:normal; line-height:1.2; }
:deep(.watermark-logo) { position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:40mm; height:40mm; object-fit:contain; opacity:0.25; z-index:-1; }
</style>
