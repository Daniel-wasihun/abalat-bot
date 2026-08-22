<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import {
    Search, CreditCard, CheckCircle2,
    RefreshCw, History, XCircle, User, Wallet,
    Download, Printer, ChevronDown
} from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import { useToastStore } from '@/stores/toast';
import { useLanguageStore } from '@/stores/languageStore';
import PaymentHistoryModal from './components/PaymentHistoryModal.vue';
import Modal from '@/components/common/Modal.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import DataTable from '@/components/common/DataTable.vue';
import TableColumn from '@/components/common/TableColumn.vue';

const toast = useToastStore();
const langStore = useLanguageStore();

const $tr = (key: string, paramsOrFallback?: any, params?: any) => {
    if (paramsOrFallback && typeof paramsOrFallback === 'object') {
        return langStore.translate(key, paramsOrFallback);
    }
    if (params && typeof params === 'object') {
        return langStore.translate(key, params);
    }
    const res = langStore.translate(key);
    if (res && res !== key) return res;
    return typeof paramsOrFallback === 'string' ? paramsOrFallback : key;
};

// ── Ethiopian calendar helpers ───────────────────────────────────────────────
const ETH_MONTHS = [
    { value: 1,  label: 'መስከረም', latin: 'Meskerem'  },
    { value: 2,  label: 'ጥቅምት',  latin: 'Tikimt'    },
    { value: 3,  label: 'ኅዳር',   latin: 'Hidar'     },
    { value: 4,  label: 'ታኅሣሥ',  latin: 'Tahsas'    },
    { value: 5,  label: 'ጥር',    latin: 'Tir'       },
    { value: 6,  label: 'የካቲት',  latin: 'Yekatit'   },
    { value: 7,  label: 'መጋቢት',  latin: 'Megabit'   },
    { value: 8,  label: 'ሚያዝያ',  latin: 'Miazia'    },
    { value: 9,  label: 'ግንቦት',  latin: 'Ginbot'    },
    { value: 10, label: 'ሰኔ',    latin: 'Sene'      },
    { value: 11, label: 'ሐምሌ',   latin: 'Hamle'     },
    { value: 12, label: 'ነሐሴ',   latin: 'Nehase'    },
    { value: 13, label: 'ጳጉሜ',   latin: 'Pagume'    },
];
const ethMonthName = (v: number) => {
    const m = ETH_MONTHS.find(m => m.value === v);
    if (!m) return v;
    return $tr(`payments.months.${m.latin.toLowerCase()}`, m.label);
};
const ethMonthLatin = (v: number) => ETH_MONTHS.find(m => m.value === v)?.latin ?? '';

const getCurrentEthDate = () => {
    try {
        const parts = new Intl.DateTimeFormat('en-US-u-ca-ethiopic', { year: 'numeric', month: 'numeric' }).formatToParts(new Date());
        const yearStr = parts.find(p => p.type === 'year')?.value;
        const monthStr = parts.find(p => p.type === 'month')?.value;
        return {
            year: parseInt(yearStr?.replace(/\D/g, '') || '2017', 10),
            month: parseInt(monthStr?.replace(/\D/g, '') || '1', 10)
        };
    } catch (e) {
        const now = new Date();
        const fallbackYear = now.getMonth() >= 8 ? now.getFullYear() - 7 : now.getFullYear() - 8;
        return { year: fallbackYear, month: 1 };
    }
};

// ── State ────────────────────────────────────────────────────────────────────
const loading    = ref(false);
const rows       = ref<any[]>([]);
const settings   = ref<any>({});
const stats      = ref<any>({});
const meta       = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 });
const exportOpen = ref(false);

const currentEth = getCurrentEthDate();

const filters = ref({
    year:           currentEth.year,
    month:          currentEth.month,
    search:         '',
    work_status:    'all',
    payment_status: 'all',
    eligibility:    'all',
    grade:          'all',
    age_min:        '',
    age_max:        '',
    per_page:       10,
    page:           1,
});

const classOptions = ref<any[]>([]);

// Debounce search
let searchTimer: ReturnType<typeof setTimeout>;
const debouncedSearch = (val: string) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        filters.value.page = 1;
        loadData();
    }, 350);
};
watch(() => filters.value.search, debouncedSearch);

watch(
    [
        () => filters.value.year,
        () => filters.value.month,
        () => filters.value.work_status,
        () => filters.value.payment_status,
        () => filters.value.eligibility,
        () => filters.value.grade,
        () => filters.value.age_min,
        () => filters.value.age_max,
        () => filters.value.page,
    ],
    () => loadData()
);

const yearOptions = computed(() => {
    const minYear = 2017;
    const maxYear = currentEth.year + 2;
    const options = [];
    for (let y = minYear; y <= maxYear; y++) {
        options.push({ value: y, label: String(y) });
    }
    return options;
});

// ── API calls ────────────────────────────────────────────────────────────────
const loadData = async () => {
    loading.value = true;
    try {
        const params: any = {
            year:     filters.value.year,
            month:    filters.value.month,
            per_page: filters.value.per_page,
            page:     filters.value.page,
        };
        if (filters.value.search)                       params.search         = filters.value.search;
        if (filters.value.work_status    !== 'all') params.work_status    = filters.value.work_status;
        if (filters.value.payment_status !== 'all') params.payment_status = filters.value.payment_status;
        if (filters.value.eligibility    !== 'all') params.eligibility    = filters.value.eligibility;
        if (filters.value.grade          !== 'all') params.grade          = filters.value.grade;
        if (filters.value.age_min)                      params.age_min        = filters.value.age_min;
        if (filters.value.age_max)                      params.age_max        = filters.value.age_max;

        const [tableRes, statsRes] = await Promise.all([
            apiClient.get('/payments', { params }),
            apiClient.get('/payments/statistics', { params: { year: filters.value.year, month: filters.value.month } }),
        ]);

        rows.value     = tableRes.data.data.users;
        settings.value = tableRes.data.data.settings;
        meta.value     = tableRes.data.data.meta;
        stats.value    = statsRes.data.data;
    } catch {
        toast.error('Failed to load payments.');
    } finally {
        loading.value = false;
    }
};

const initOptions = async () => {
    try {
        const res = await apiClient.get('/academic/config/classes');
        classOptions.value = res.data.classes || [];
    } catch (error) {
        console.error('Failed to load classes', error);
    }
};

onMounted(() => {
    initOptions();
    loadData();
});

// ── Export / Print Helpers ───────────────────────────────────────────────────
const getExportParams = () => {
    const params: any = {
        year:  filters.value.year,
        month: filters.value.month,
    };
    if (filters.value.search)                       params.search         = filters.value.search;
    if (filters.value.work_status    !== 'all') params.work_status    = filters.value.work_status;
    if (filters.value.payment_status !== 'all') params.payment_status = filters.value.payment_status;
    if (filters.value.grade)                        params.grade          = filters.value.grade;
    if (filters.value.age_min)                      params.age_min        = filters.value.age_min;
    if (filters.value.age_max)                      params.age_max        = filters.value.age_max;
    return new URLSearchParams(params).toString();
};

const exportCsv = () => {
    exportOpen.value = false;
    window.open(`/api/payments/export-csv?${getExportParams()}`, '_blank');
};

const exportExcel = () => {
    exportOpen.value = false;
    window.open(`/api/payments/export-excel?${getExportParams()}`, '_blank');
};

const exportPdf = () => {
    exportOpen.value = false;
    window.open(`/api/payments/export-pdf?${getExportParams()}`, '_blank');
};

const printReport = () => {
    exportOpen.value = false;
    window.print();
};

// ── Name helper ───────────────────────────────────────────────────────────────
const displayFullName = (u: any) => {
    if (!u) return '';
    const first = typeof u.name === 'object' ? (u.name.am || u.name.en || '') : (u.name || '');
    const father = u.father_name || '';
    const grand  = u.grandfather_name || '';
    const full = [first, father, grand].filter(Boolean).join(' ');
    return full || `Member #${u.registration_id ?? u.id}`;
};
// Keep for modal/other usages
const displayName = displayFullName;

// ── Status config & Exempt reasons ─────────────────────────────────────────────
type StatusKey = 'paid' | 'pending' | 'partial' | 'late' | 'exempt';
const statusCfg = (s: string) => {
    const keyMap: Record<StatusKey, string> = {
        paid:    'payments.stats.paid',
        partial: 'payments.stats.partial',
        late:    'payments.stats.late',
        pending: 'payments.stats.pending',
        exempt:  'payments.stats.exempt',
    };
    const defaultLabels: Record<StatusKey, string> = {
        paid:    'Paid',
        partial: 'Partial',
        late:    'Late',
        pending: 'Pending',
        exempt:  'Exempt',
    };
    const clsMap: Record<StatusKey, string> = {
        paid:    'bg-emerald-500/10 text-emerald-500',
        partial: 'bg-amber-500/10  text-amber-500',
        late:    'bg-rose-500/10   text-rose-500',
        pending: 'bg-brand-blue/10 text-brand-blue',
        exempt:  'bg-gray-400/10   text-gray-400',
    };
    const key = keyMap[s as StatusKey];
    const def = defaultLabels[s as StatusKey] ?? s;
    return {
        label: key ? $tr(key, def) : s,
        cls: clsMap[s as StatusKey] ?? 'bg-gray-400/10 text-gray-400',
    };
};

const formatExemptReason = (reason: string) => {
    if (!reason) return '';

    // Structured codes from backend: "grade_and_age:7,13", "grade:7", "age:13"
    if (reason.startsWith('grade_and_age:')) {
        const parts = reason.replace('grade_and_age:', '').split(',');
        const grade = parts[0] ?? '7';
        const age   = parts[1] ?? '13';
        return $tr('payments.exempt_below_grade_age', `Below minimum grade (${grade}) and age (${age})`, { grade, age });
    }
    if (reason.startsWith('grade:')) {
        const grade = reason.replace('grade:', '');
        return $tr('payments.exempt_below_grade', `Below minimum grade (${grade})`, { grade });
    }
    if (reason.startsWith('age:')) {
        const age = reason.replace('age:', '');
        return $tr('payments.exempt_below_age', `Below minimum age (${age})`, { age });
    }
    if (reason === 'no_membership') {
        return $tr('payments.exempt_no_membership', 'No membership record');
    }

    // Legacy fallback – parse old English strings if still in DB
    if (reason.includes('Below minimum grade') && reason.includes('and age')) {
        const m = reason.match(/\d+/g);
        const grade = m ? m[0] : '7';
        const age   = m && m.length > 1 ? m[1] : '13';
        return $tr('payments.exempt_below_grade_age', `Below minimum grade (${grade}) and age (${age})`, { grade, age });
    }
    if (reason.includes('Below minimum grade')) {
        const m = reason.match(/\d+/g);
        const grade = m ? m[0] : '7';
        return $tr('payments.exempt_below_grade', `Below minimum grade (${grade})`, { grade });
    }
    if (reason.includes('Below minimum age') || reason.includes('Below minimum age')) {
        const m = reason.match(/\d+/g);
        const age = m ? m[0] : '13';
        return $tr('payments.exempt_below_age', `Below minimum age (${age})`, { age });
    }

    return reason;
};

const fmt = (v: any) => parseFloat(v ?? 0).toFixed(2);

// ── Reset filters ─────────────────────────────────────────────────────────────
const resetFilters = () => {
    filters.value.search         = '';
    filters.value.work_status    = 'all';
    filters.value.payment_status = 'all';
    filters.value.grade          = 'all';
    filters.value.eligibility    = 'all';
    filters.value.age_min        = '';
    filters.value.age_max        = '';
    filters.value.page           = 1;
};

// ── Record payment modal ──────────────────────────────────────────────────────
const payModal    = ref(false);
const payUser     = ref<any>(null);
const payLoading  = ref(false);
const previewLoading = ref(false);
const confirmModal = ref(false);

const payForm = ref({
    amount:      '',
    fine:        '0',
    method:      'cash',
    reference:   '',
    numMonths:   1,
    giftSurplus: false,
});

// Bulk preview rows from backend
const bulkPreview = ref<any>(null);

const openPayModal = (u: any) => {
    payUser.value = u;
    payForm.value = {
        amount:      fmt(u.balance),
        fine:        '0',
        method:      'cash',
        reference:   '',
        numMonths:   1,
        giftSurplus: false,
    };
    bulkPreview.value = null;
    payModal.value = true;
};

// Fetch backend preview when numMonths > 1 or amount changes
const fetchPreview = async () => {
    if (!payUser.value) {
        bulkPreview.value = null;
        return;
    }
    if (payForm.value.numMonths === 1) {
        if (!payForm.value.amount || parseFloat(payForm.value.amount) <= 0) {
            bulkPreview.value = null;
        }
        return;
    }
    previewLoading.value = true;
    try {
        const res = await apiClient.get('/payments/preview-bulk', {
            params: {
                user_id:    payUser.value.id,
                from_year:  filters.value.year,
                from_month: filters.value.month,
                num_months: payForm.value.numMonths,
                amount:     parseFloat(payForm.value.amount) || 0,
            },
        });
        bulkPreview.value = res.data.data;
    } catch {
        bulkPreview.value = null;
    } finally {
        previewLoading.value = false;
    }
};

// Auto-calculate total amount when numMonths changes to > 1
const autoCalcBulkAmount = async () => {
    if (!payUser.value || payForm.value.numMonths === 1) return;
    previewLoading.value = true;
    try {
        const res = await apiClient.get('/payments/preview-bulk', {
            params: {
                user_id:    payUser.value.id,
                from_year:  filters.value.year,
                from_month: filters.value.month,
                num_months: payForm.value.numMonths,
                amount:     999999,
            },
        });
        const preview = res.data.data;
        const totalDue = (preview.rows as any[])
            .filter((r: any) => r.status !== 'exempt')
            .reduce((sum: number, r: any) => sum + parseFloat(r.balance ?? r.total_due ?? 0), 0);
        payForm.value.amount = totalDue.toFixed(2);
        await fetchPreview();
    } catch {
        bulkPreview.value = null;
    } finally {
        previewLoading.value = false;
    }
};

// Watch for changes to refresh preview or reset single month
watch(() => payForm.value.numMonths, () => {
    if (payModal.value) {
        if (payForm.value.numMonths > 1) {
            autoCalcBulkAmount();
        } else {
            // Reset to single month balance immediately
            payForm.value.amount = fmt(payUser.value?.balance ?? 0);
            payForm.value.fine   = '0';
            bulkPreview.value    = null;
        }
    }
});

watch(() => payForm.value.amount, () => {
    if (payModal.value && payForm.value.numMonths > 1) fetchPreview();
}, { debounce: 400 } as any);

const surplusAmount = computed(() => {
    if (!payUser.value || !payForm.value.amount) return 0;
    const paying = parseFloat(payForm.value.amount || '0');
    if (payForm.value.numMonths > 1) {
        if (bulkPreview.value) {
            return Math.max(0, parseFloat(bulkPreview.value.surplus_credit ?? 0));
        }
        return 0;
    }
    const currentBal = parseFloat(payUser.value.balance ?? 0);
    return Math.max(0, paying - currentBal);
});

const requestSubmit = () => {
    confirmModal.value = true;
};

const submitPayment = async () => {
    if (!payUser.value) return;
    payLoading.value = true;
    try {
        const isMulti = payForm.value.numMonths > 1;
        if (isMulti) {
            await apiClient.post('/payments/bulk', {
                user_id:        payUser.value.id,
                from_year:      filters.value.year,
                from_month:     filters.value.month,
                num_months:     payForm.value.numMonths,
                amount_paid:    parseFloat(payForm.value.amount),
                payment_method: payForm.value.method,
                reference:      payForm.value.reference || undefined,
                gift_surplus:   payForm.value.giftSurplus,
            });
            toast.success(`${payForm.value.numMonths}-month payment recorded successfully.`);
        } else {
            await apiClient.post('/payments', {
                user_id:        payUser.value.id,
                for_year:       filters.value.year,
                for_month:      filters.value.month,
                amount_paid:    parseFloat(payForm.value.amount),
                fine_paid:      parseFloat(payForm.value.fine || '0'),
                payment_method: payForm.value.method,
                reference:      payForm.value.reference || undefined,
                gift_surplus:   payForm.value.giftSurplus,
            });
            toast.success('Payment recorded successfully.');
        }
        payModal.value = false;
        confirmModal.value = false;
        loadData();
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to record payment.');
    } finally {
        payLoading.value = false;
    }
};

// ── History modal ─────────────────────────────────────────────────────────────
const historyOpen = ref(false);
const historyUser = ref<any>(null);
const openHistory = (u: any) => { historyUser.value = u; historyOpen.value = true; };
</script>
<template>
    <div class="h-[calc(100vh-theme(spacing.16))] flex flex-col p-4 md:p-6 overflow-hidden bg-main-bg">

        <!-- ── Header ────────────────────────────────────────────────────── -->
        <div class="flex items-start justify-between mb-4 shrink-0 gap-4 print:hidden">
            <div>
                <h1 class="text-xl font-bold text-main-text flex items-center gap-2">
                    <CreditCard class="w-6 h-6 text-brand-blue" />
                    {{ $tr('payments.title', 'Monthly Payments') }}
                </h1>
                <p class="text-xs text-main-text/40 mt-0.5">
                    {{ $tr('payments.subtitle', 'Track and manage student monthly payments and fines.') }}
                </p>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 print:hidden">
                <!-- Rates badge -->
                <div class="hidden lg:flex items-center gap-3 px-3 py-1.5 rounded-xl border border-card-bg/60 text-xs text-main-text/60">
                    <span>{{ $tr('payments.student_rate', 'Student:') }} <strong class="text-main-text">{{ fmt(settings.student_amount) }}</strong> {{ $tr('payments.currency', 'ETB') }}</span>
                    <span class="text-main-text/20">•</span>
                    <span>{{ $tr('payments.worker_rate', 'Worker:') }} <strong class="text-main-text">{{ fmt(settings.worker_amount) }}</strong> {{ $tr('payments.currency', 'ETB') }}</span>
                    <span class="text-main-text/20">•</span>
                    <span>{{ $tr('payments.student_fine', 'Student Fine:') }} <strong class="text-rose-500">{{ fmt(settings.student_fine_per_month) }}</strong>{{ $tr('payments.per_month', '/mo') }}</span>
                    <span class="text-main-text/20">•</span>
                    <span>{{ $tr('payments.worker_fine', 'Worker Fine:') }} <strong class="text-rose-500">{{ fmt(settings.worker_fine_per_month) }}</strong>{{ $tr('payments.per_month', '/mo') }}</span>
                </div>

                <!-- Export dropdown -->
                <div class="relative">
                    <button @click="exportOpen = !exportOpen"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl bg-brand-blue text-white hover:bg-brand-blue/80 transition-colors shadow-sm">
                        <Download class="w-4 h-4" />
                        {{ $tr('payments.export', 'Export') }}
                        <ChevronDown class="w-3.5 h-3.5 opacity-60" />
                    </button>

                    <div v-if="exportOpen" @click.outside="exportOpen = false"
                        class="absolute right-0 mt-2 w-52 bg-card-bg border border-card-border rounded-xl shadow-xl z-50 py-1 text-sm text-main-text">
                        <button @click="exportPdf" class="w-full px-4 py-2 text-left hover:bg-main-text/5 flex items-center gap-2">
                            <Download class="w-4 h-4 text-rose-500" /> {{ $tr('payments.export_pdf', 'Export as PDF') }}
                        </button>
                        <button @click="exportExcel" class="w-full px-4 py-2 text-left hover:bg-main-text/5 flex items-center gap-2">
                            <Download class="w-4 h-4 text-emerald-500" /> {{ $tr('payments.export_excel', 'Export as Excel (.xlsx)') }}
                        </button>
                        <button @click="exportCsv" class="w-full px-4 py-2 text-left hover:bg-main-text/5 flex items-center gap-2">
                            <Download class="w-4 h-4 text-brand-blue" /> {{ $tr('payments.export_csv', 'Export as CSV') }}
                        </button>
                        <div class="h-px bg-card-border/50 my-1"></div>
                        <button @click="printReport" class="w-full px-4 py-2 text-left hover:bg-main-text/5 flex items-center gap-2">
                            <Printer class="w-4 h-4 text-amber-500" /> {{ $tr('payments.export_print', 'Print Table') }}
                        </button>
                    </div>
                </div>

                <button @click="loadData" :disabled="loading"
                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-xl border border-card-border text-main-text/60 hover:bg-card-bg transition-colors disabled:opacity-40">
                    <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                    {{ $tr('payments.filter.refresh', 'Refresh') }}
                </button>
            </div>
        </div>

        <!-- ── Summary Cards ──────────────────────────────────────────────── -->
        <div class="space-y-2 mb-4 shrink-0">
            <!-- Member Statistics Row -->
            <!-- Member Statistics & Financial Totals Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-2">
                <div class="bg-card-bg border border-card-border rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-main-text/40 mb-0.5">{{ $tr('payments.stats.paid_count', 'Paid (Count)') }}</div>
                    <div class="text-base font-bold text-emerald-500">{{ stats.paid ?? '0' }}</div>
                </div>
                <div class="bg-card-bg border border-card-border rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-main-text/40 mb-0.5">{{ $tr('payments.stats.pending_count', 'Pending (Count)') }}</div>
                    <div class="text-base font-bold text-brand-blue">{{ stats.pending ?? '0' }}</div>
                </div>
                <div class="bg-card-bg border border-card-border rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-main-text/40 mb-0.5">{{ $tr('payments.stats.partial_count', 'Partial (Count)') }}</div>
                    <div class="text-base font-bold text-amber-500">{{ stats.partial ?? '0' }}</div>
                </div>
                <div class="bg-card-bg border border-card-border rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-main-text/40 mb-0.5">{{ $tr('payments.stats.late_count', 'Late (Count)') }}</div>
                    <div class="text-base font-bold text-rose-500">{{ stats.late ?? '0' }}</div>
                </div>
                <div class="bg-card-bg border border-card-border rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-main-text/40 mb-0.5">{{ $tr('payments.stats.exempt_count', 'Exempt (Count)') }}</div>
                    <div class="text-base font-bold text-main-text/40">{{ stats.exempt ?? '0' }}</div>
                </div>
                <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-emerald-500 mb-0.5">{{ $tr('payments.stats.collected', 'Total Paid') }}</div>
                    <div class="text-xs font-bold text-emerald-500">{{ fmt(stats.collected) }}</div>
                </div>
                <div class="bg-rose-500/5 border border-rose-500/20 rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-rose-500 mb-0.5">{{ $tr('payments.stats.outstanding', 'Total Outstanding') }}</div>
                    <div class="text-xs font-bold text-rose-500">{{ fmt(stats.outstanding) }}</div>
                </div>
                <div class="bg-rose-500/5 border border-rose-500/20 rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-rose-500 mb-0.5">{{ $tr('payments.stats.fines', 'Total Fines') }}</div>
                    <div class="text-xs font-bold text-rose-500">{{ fmt(stats.fines) }}</div>
                </div>
                <div class="bg-violet-500/5 border border-violet-500/20 rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-violet-500 mb-0.5">{{ $tr('payments.stats.credit_held', 'Total Credit Held') }}</div>
                    <div class="text-xs font-bold text-violet-500">{{ fmt(stats.total_credits_held) }}</div>
                </div>
                <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-indigo-500 mb-0.5">{{ $tr('payments.stats.credit_used', 'Total Credit Used') }}</div>
                    <div class="text-xs font-bold text-indigo-500">{{ fmt(stats.total_credits_used) }}</div>
                </div>
                <div class="bg-pink-500/5 border border-pink-500/20 rounded-xl p-2.5 text-center">
                    <div class="text-[11px] text-pink-500 mb-0.5">{{ $tr('payments.stats.donations', 'Total Donations') }}</div>
                    <div class="text-xs font-bold text-pink-600">{{ fmt(stats.total_donations) }}</div>
                </div>
            </div>

        </div>

        <!-- ── Main Card ──────────────────────────────────────────────────── -->
        <div class="bg-card-bg/50 backdrop-blur-xl border border-card-border rounded-2xl flex-1 flex flex-col overflow-hidden">

            <!-- Filters -->
            <div class="p-3 border-b border-card-border/60 bg-card-bg/30 flex flex-wrap gap-2 items-center shrink-0 print:hidden">
                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-main-text/40" />
                    <input v-model="filters.search" :placeholder="$tr('payments.search_placeholder', 'Search name or Reg ID…')"
                        class="pl-8 pr-3 h-9 w-52 text-sm rounded-lg border border-input-border bg-input-bg text-main-text focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                </div>

                <!-- Month -->
                <select v-model.number="filters.month" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option v-for="m in ETH_MONTHS" :key="m.value" :value="m.value">{{ ethMonthName(m.value) }}</option>
                </select>

                <!-- Year -->
                <select v-model.number="filters.year" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option v-for="y in yearOptions" :key="y.value" :value="y.value">{{ y.value }}</option>
                </select>

                <!-- Work Status -->
                <select v-model="filters.work_status" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option value="all">{{ $tr('payments.filter.all_types', 'All Types') }}</option>
                    <option value="student">{{ $tr('payments.filter.students', 'Students') }}</option>
                    <option value="worker">{{ $tr('payments.filter.workers', 'Workers') }}</option>
                </select>

                <!-- Payment Status -->
                <select v-model="filters.payment_status" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option value="all">{{ $tr('payments.filter.all_statuses', 'All Statuses') }}</option>
                    <option value="paid">{{ $tr('payments.stats.paid', 'Paid') }}</option>
                    <option value="pending">{{ $tr('payments.stats.pending', 'Pending') }}</option>
                    <option value="partial">{{ $tr('payments.stats.partial', 'Partial') }}</option>
                    <option value="late">{{ $tr('payments.stats.late', 'Late') }}</option>
                    <option value="exempt">{{ $tr('payments.stats.exempt', 'Exempt') }}</option>
                </select>

                <!-- Eligibility -->
                <select v-model="filters.eligibility" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option value="all">{{ $tr('payments.filter.eligibility_all', 'All (Eligibility)') }}</option>
                    <option value="eligible">{{ $tr('payments.filter.eligible_only', 'Eligible') }}</option>
                    <option value="exempt">{{ $tr('payments.filter.not_eligible', 'Not Eligible') }}</option>
                </select>

                <!-- Grade -->
                <select v-model="filters.grade" @change="filters.page=1"
                    class="h-9 w-36 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option value="all">{{ $tr('payments.filter.all_grades', 'All Grades') }}</option>
                    <option v-for="cls in classOptions" :key="cls.id" :value="cls.code">
                        {{ $tr(`academic.classes.${cls.name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_$/, '')}`, cls.name) }}
                    </option>
                </select>
                <!-- Reset -->
                <button @click="resetFilters"
                    class="h-9 px-3 flex items-center gap-1 text-sm rounded-lg border border-card-border text-main-text/50 hover:bg-card-bg transition-colors">
                    <XCircle class="w-3.5 h-3.5" /> {{ $tr('payments.filter.reset', 'Reset') }}
                </button>

                <span class="ml-auto text-xs text-main-text/30">{{ meta.total }} {{ $tr('payments.records', 'records') }}</span>
            </div>

            <!-- Table using shared DataTable component -->
            <DataTable
                :items="rows"
                :loading="loading"
                :pagination="{
                    currentPage: meta.current_page,
                    lastPage:    meta.last_page,
                    total:       meta.total,
                    perPage:     meta.per_page ?? 20,
                }"
                :empty-title="$tr('payments.no_records', 'No records found')"
                :empty-desc="$tr('payments.no_records_desc', 'No payment records match the selected filters.')"
                class="flex-1"
                @page-change="filters.page = $event"
            >
                <!-- Column definitions (parsed by DataTable via TableColumn slot) -->
                <TableColumn field="name" :header="$tr('payments.table.member', 'Member')" align="left" width="260px" />
                <TableColumn field="registration_id" :header="$tr('payments.table.reg_id', 'Reg ID')" align="center" width="120px" />
                <TableColumn field="grade" :header="$tr('payments.table.grade', 'Grade')" align="center" width="70px" />
                <TableColumn field="age" :header="$tr('payments.table.age', 'Age')" align="center" width="60px" />
                <TableColumn field="work_status" :header="$tr('payments.table.type', 'Type')" align="center" width="110px" />
                <TableColumn field="period" :header="$tr('payments.table.period', 'Period')" align="center" width="80px" />
                <TableColumn field="base_amount" :header="$tr('payments.table.base', 'Base')" align="right" width="90px" />
                <TableColumn field="fine_amount" :header="$tr('payments.table.fine', 'Fine')" align="right" width="80px" />
                <TableColumn field="total_amount_due" :header="$tr('payments.table.total_due', 'Total Due')" align="right" width="90px" />
                <TableColumn field="amount_paid" :header="$tr('payments.table.paid', 'Paid')" align="right" width="80px" />
                <TableColumn field="available_credit" :header="$tr('payments.table.credit', 'Credit')" align="right" width="90px" />
                <TableColumn field="balance" :header="$tr('payments.table.balance', 'Balance')" align="right" width="90px" />
                <TableColumn field="status" :header="$tr('payments.table.status', 'Status')" align="center" width="130px" />
                <TableColumn field="actions" :header="$tr('payments.table.actions', 'Actions')" align="right" width="110px" />

                <!-- Custom cell renderers -->
                <template #cell-name="{ item }">
                    <div class="min-w-0">
                        <div class="font-semibold text-main-text leading-snug text-sm">{{ displayFullName(item) }}</div>
                    </div>
                </template>

                <template #cell-registration_id="{ item }">
                    <span class="font-mono text-xs font-medium text-main-text/60">{{ item.registration_id ?? '—' }}</span>
                </template>

                <template #cell-grade="{ item }">
                    <span class="px-2 py-0.5 rounded-full bg-brand-blue/10 text-brand-blue text-xs font-semibold">
                        {{ item.grade ?? '—' }}
                    </span>
                </template>

                <template #cell-age="{ item }">
                    <span class="text-xs text-main-text/60">{{ item.age ?? '—' }}</span>
                </template>

                <template #cell-work_status="{ item }">
                    <span :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold',
                        item.work_status === 'worker' ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600'
                    ]">
                        <User class="w-2.5 h-2.5" />
                        {{ item.work_status === 'worker' ? $tr('payments.filter.workers', 'Worker') : $tr('payments.filter.students', 'Student') }}
                    </span>
                </template>

                <template #cell-period>
                    <span class="text-xs text-main-text/60 font-medium">{{ filters.month }}/{{ filters.year }}</span>
                </template>

                <template #cell-base_amount="{ item }">
                    <span class="text-xs text-main-text/70">{{ fmt(item.base_amount) }}</span>
                </template>

                <template #cell-fine_amount="{ item }">
                    <span v-if="parseFloat(item.fine_amount ?? 0) > 0" class="text-rose-500 font-semibold text-xs">
                        {{ fmt(item.fine_amount) }}
                    </span>
                    <span v-else class="text-main-text/20 text-xs">—</span>
                </template>

                <template #cell-total_amount_due="{ item }">
                    <span class="text-xs font-bold text-main-text">{{ fmt(item.total_amount_due) }}</span>
                </template>

                <template #cell-amount_paid="{ item }">
                    <span class="text-xs font-semibold text-emerald-500">{{ fmt(item.amount_paid) }}</span>
                </template>

                <template #cell-available_credit="{ item }">
                    <span v-if="parseFloat(item.available_credit ?? 0) > 0"
                        class="inline-flex items-center gap-0.5 text-xs px-1.5 py-0.5 rounded-full bg-violet-500/10 text-violet-500 font-semibold">
                        <Wallet class="w-3 h-3" />
                        {{ fmt(item.available_credit) }}
                    </span>
                    <span v-else class="text-main-text/20 text-xs">—</span>
                </template>

                <template #cell-balance="{ item }">
                    <span :class="parseFloat(item.balance ?? 0) > 0 ? 'text-amber-500 text-xs font-bold' : 'text-main-text/20 text-xs'">
                        {{ fmt(item.balance) }}
                    </span>
                </template>

                <template #cell-status="{ item }">
                    <div>
                        <span :class="['px-2 py-0.5 text-xs font-semibold rounded-full capitalize', statusCfg(item.status).cls]">
                            {{ statusCfg(item.status).label }}
                        </span>
                        <div v-if="item.status === 'exempt' && item.exempt_reason"
                            class="text-[10px] text-main-text/30 mt-0.5 max-w-[120px] mx-auto leading-tight">
                            {{ formatExemptReason(item.exempt_reason) }}
                        </div>
                    </div>
                </template>

                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-end gap-1">
                        <button @click.stop="openHistory(item)"
                            class="p-1.5 rounded-lg hover:bg-card-border text-main-text/30 hover:text-main-text/70 transition-colors"
                            :title="$tr('payments.view_history', 'View history')">
                            <History class="w-3.5 h-3.5" />
                        </button>
                        <button v-if="item.status !== 'paid' && item.status !== 'exempt'"
                            @click.stop="openPayModal(item)"
                            class="px-2.5 py-1 text-xs rounded-lg bg-brand-blue text-white font-semibold hover:bg-brand-blue/80 transition-colors whitespace-nowrap flex items-center gap-1">
                            <CreditCard class="w-3 h-3" />
                            {{ item.status === 'partial' ? $tr('payments.pay_balance', 'Pay Balance') : $tr('payments.pay', 'Pay') }}
                        </button>
                        <span v-else-if="item.status === 'paid'"
                            class="text-xs text-emerald-500 flex items-center gap-0.5 font-semibold">
                            <CheckCircle2 class="w-3.5 h-3.5" /> {{ $tr('payments.done', 'Done') }}
                        </span>
                    </div>
                </template>

            </DataTable>
        </div>

        <!-- ── Record Payment Modal ───────────────────────────────────────── -->
        <Modal :show="payModal" @close="payModal = false" size="md" hide-header no-padding>
            <div class="relative bg-main-bg w-full rounded-2xl flex flex-col overflow-hidden max-h-[90vh]">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-card-border bg-card-bg flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-bold text-main-text text-base">{{ $tr('payments.modal.record_payment', 'Record Payment') }}</h3>
                        <p class="text-xs text-main-text/50 mt-0.5">
                            {{ displayName(payUser) }} (#{{ payUser?.registration_id }}) —
                            <span v-if="payForm.numMonths === 1">{{ ethMonthName(filters.month) }} {{ filters.year }}</span>
                            <span v-else>{{ $tr('payments.months_from', `${payForm.numMonths} months from ${ethMonthName(filters.month)} ${filters.year}`, { count: payForm.numMonths, month: ethMonthName(filters.month), year: filters.year }) }}</span>
                        </p>
                    </div>
                    <button @click="payModal = false" class="p-1.5 rounded-lg hover:bg-main-text/5 text-main-text/40">
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4 overflow-y-auto flex-1 custom-scrollbar">

                    <!-- Number of Months selector -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-2">{{ $tr('payments.modal.num_months', 'Number of Months') }}</label>
                        <div class="flex gap-2">
                            <button v-for="n in [1, 3, 6, 12]" :key="n"
                                @click="payForm.numMonths = n"
                                :class="[
                                    'flex-1 py-2 rounded-xl text-sm font-bold border transition-all',
                                    payForm.numMonths === n
                                        ? 'bg-brand-blue text-white border-brand-blue shadow-md'
                                        : 'bg-card-bg text-main-text/60 border-card-border hover:border-brand-blue/40'
                                ]">
                                {{ n === 1 ? '1 mo' : n + ' mo' }}
                            </button>
                        </div>
                        <p v-if="payForm.numMonths > 1" class="text-xs text-brand-blue/70 mt-1.5">
                            {{ $tr('payments.distributed_across', `Payment will be distributed across ${payForm.numMonths} consecutive months starting ${ethMonthName(filters.month)} ${filters.year}.`, { count: payForm.numMonths, month: ethMonthName(filters.month), year: filters.year }) }}
                        </p>
                    </div>

                    <!-- Single month summary (numMonths === 1) -->
                    <div v-if="payForm.numMonths === 1" class="grid grid-cols-3 gap-3 bg-card-bg rounded-xl p-3 border border-card-border text-center">
                        <div>
                            <div class="text-xs text-main-text/40">{{ $tr('payments.table.base', 'Base') }}</div>
                            <div class="font-bold text-main-text text-sm">{{ fmt(payUser?.base_amount) }} ETB</div>
                        </div>
                        <div>
                            <div class="text-xs text-rose-400">{{ $tr('payments.table.fine', 'Fine') }}</div>
                            <div class="font-bold text-rose-500 text-sm">{{ fmt(payUser?.fine_amount) }} ETB</div>
                        </div>
                        <div>
                            <div class="text-xs text-amber-500">{{ $tr('payments.table.balance', 'Balance') }}</div>
                            <div class="font-bold text-amber-500 text-sm">{{ fmt(payUser?.balance) }} ETB</div>
                        </div>
                    </div>

                    <!-- Available Credit (always show if > 0) -->
                    <div v-if="parseFloat(payUser?.available_credit ?? 0) > 0"
                        class="flex items-center gap-2 bg-violet-500/8 border border-violet-500/20 rounded-xl px-3 py-2.5">
                        <Wallet class="w-4 h-4 text-violet-500 shrink-0" />
                        <div class="text-xs">
                            <span class="font-semibold text-violet-500">{{ fmt(payUser?.available_credit) }} ETB</span>
                            <span class="text-main-text/50 ml-1">{{ $tr('payments.modal.credit_available', 'available credit will be applied automatically') }}</span>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">{{ $tr('payments.modal.amount_paying', 'Amount Paying (ETB) *') }}</label>
                        <input v-model="payForm.amount" type="number" min="0.01" step="0.01"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                    </div>

                    <!-- Gift Surplus Toggle -->
                    <div v-if="surplusAmount > 0" class="bg-pink-500/10 border border-pink-500/30 rounded-xl p-3 text-xs space-y-2">
                        <div class="flex justify-between font-semibold text-pink-600">
                            <span>{{ $tr('payments.modal.surplus_amount', 'Surplus Amount:') }}</span>
                            <span>+{{ fmt(surplusAmount) }} {{ $tr('payments.currency', 'ETB') }}</span>
                        </div>
                        <label class="flex items-start gap-2 cursor-pointer text-main-text select-none">
                            <input type="checkbox" v-model="payForm.giftSurplus" class="mt-0.5 rounded border-input-border text-pink-500 focus:ring-pink-400" />
                            <span>
                                {{ $tr('payments.modal.gift_surplus_toggle', 'Treat surplus as a Gift / Donation to Senbet School asset income (will not become member credit wallet).') }}
                            </span>
                        </label>
                    </div>

                    <!-- Fine paid (single month only) -->
                    <div v-if="payForm.numMonths === 1">
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">{{ $tr('payments.modal.fine_included', 'Fine Included (ETB)') }}</label>
                        <input v-model="payForm.fine" type="number" min="0" step="0.01"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                    </div>

                    <!-- Method -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">{{ $tr('payments.modal.payment_method', 'Payment Method') }}</label>
                        <select v-model="payForm.method"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none">
                            <option value="cash">{{ $tr('payments.modal.cash', 'Cash') }}</option>
                            <option value="bank_transfer">{{ $tr('payments.modal.bank_transfer', 'Bank Transfer') }}</option>
                            <option value="mobile_money">{{ $tr('payments.modal.mobile_money', 'Mobile Money (Telebirr)') }}</option>
                        </select>
                    </div>

                    <!-- Reference -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">{{ $tr('payments.modal.reference_number', 'Reference Number (optional)') }}</label>
                        <input v-model="payForm.reference" type="text" maxlength="100"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                    </div>

                    <!-- Multi-month breakdown preview -->
                    <div v-if="payForm.numMonths > 1">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest">{{ $tr('payments.modal.payment_breakdown', 'Payment Breakdown') }}</label>
                            <RefreshCw v-if="previewLoading" class="w-3.5 h-3.5 text-brand-blue animate-spin" />
                        </div>

                        <div v-if="bulkPreview" class="rounded-xl border border-card-border overflow-hidden">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-card-bg text-main-text/40 capitalize tracking-widest">
                                        <th class="px-3 py-2 text-left font-semibold">Month</th>
                                        <th class="px-3 py-2 text-right font-semibold">{{ $tr('payments.table.base', 'Base') }}</th>
                                        <th class="px-3 py-2 text-right font-semibold text-rose-400">{{ $tr('payments.table.fine', 'Fine') }}</th>
                                        <th class="px-3 py-2 text-right font-semibold">Total</th>
                                        <th class="px-3 py-2 text-right font-semibold text-emerald-500">Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in bulkPreview.rows" :key="`${row.year}-${row.month}`"
                                        :class="['border-t border-card-border/40', row.status === 'exempt' ? 'opacity-40' : '']">
                                        <td class="px-3 py-2 font-medium text-main-text">
                                            {{ ethMonthName(row.month) }} {{ row.year }}
                                        </td>
                                        <td class="px-3 py-2 text-right text-main-text/60">{{ fmt(row.base) }}</td>
                                        <td class="px-3 py-2 text-right" :class="parseFloat(row.fine) > 0 ? 'text-rose-500' : 'text-main-text/20'">
                                            {{ parseFloat(row.fine) > 0 ? fmt(row.fine) : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold text-main-text">{{ fmt(row.total_due) }}</td>
                                        <td class="px-3 py-2 text-right font-bold"
                                            :class="parseFloat(row.amount_applied) > 0 ? 'text-emerald-500' : 'text-main-text/20'">
                                            {{ parseFloat(row.amount_applied) > 0 ? fmt(row.amount_applied) : '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-card-bg border-t border-card-border">
                                        <td colspan="4" class="px-3 py-2 text-right text-main-text/60 font-semibold">Total Applied</td>
                                        <td class="px-3 py-2 text-right font-bold text-emerald-500">{{ fmt(bulkPreview.total_applied) }} {{ $tr('payments.currency', 'ETB') }}</td>
                                    </tr>
                                    <tr v-if="parseFloat(bulkPreview.surplus_credit ?? 0) > 0" class="bg-violet-500/5 border-t border-violet-500/20">
                                        <td colspan="4" class="px-3 py-2 text-right text-violet-500 font-semibold">
                                            {{ payForm.giftSurplus ? 'Gift / Donation' : 'Credit Created' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold" :class="payForm.giftSurplus ? 'text-pink-600' : 'text-violet-500'">
                                            +{{ fmt(bulkPreview.surplus_credit) }} {{ $tr('payments.currency', 'ETB') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Single month remaining preview -->
                    <div v-if="payForm.numMonths === 1" class="bg-card-bg rounded-xl p-3 border border-card-border text-xs text-main-text/60 flex justify-between">
                        <span>{{ $tr('payments.remaining_after', 'Remaining after this payment:') }}</span>
                        <span class="font-bold text-main-text">
                            {{ Math.max(0, parseFloat(payUser?.balance ?? 0) - parseFloat(payForm.amount || '0')).toFixed(2) }} {{ $tr('payments.currency', 'ETB') }}
                        </span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 py-3 border-t border-card-border bg-card-bg flex justify-end gap-2 shrink-0">
                    <button @click="payModal = false"
                        class="px-4 py-2 rounded-xl border border-card-border text-main-text/60 text-sm font-semibold hover:bg-main-text/5 transition-colors">
                        {{ $tr('payments.modal.cancel', 'Cancel') }}
                    </button>
                    <button @click="requestSubmit"
                        :disabled="payLoading || !payForm.amount || parseFloat(payForm.amount) <= 0"
                        class="px-5 py-2 rounded-xl bg-brand-blue text-white font-bold text-sm hover:bg-brand-blue/80 transition-colors disabled:opacity-40 flex items-center gap-2 shadow-sm">
                        <RefreshCw v-if="payLoading" class="w-4 h-4 animate-spin" />
                        <CreditCard v-else class="w-4 h-4" />
                        {{ payForm.numMonths > 1 ? $tr('payments.modal.pay_months', 'Pay Months') : $tr('payments.modal.confirm_payment', 'Confirm Payment') }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ── History Modal ─────────────────────────────────────────────── -->
        <PaymentHistoryModal :is-open="historyOpen" :user="historyUser" @close="historyOpen = false" />

        <!-- ── Confirmation Modal ─────────────────────────────────────────────── -->
        <ConfirmDialog
            :show="confirmModal"
            :title="$tr('payments.modal.confirm_title', 'Confirm Payment')"
            message=""
            :confirm-text="$tr('payments.modal.confirm_payment', 'Confirm Payment')"
            variant="primary"
            :loading="payLoading"
            @close="confirmModal = false"
            @confirm="submitPayment"
        >
            <div class="mt-4 text-sm text-main-text/80 space-y-4">
                <p>{{ $tr('payments.modal.confirm_desc', 'Are you sure you want to record this payment?') }}</p>
                <div class="space-y-3 pt-3 border-t border-card-border/60">
                    <div class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">{{ $tr('payments.table.member', 'Member') }}:</span>
                        <span class="font-medium text-main-text">{{ displayName(payUser) }} ({{ payUser?.registration_id }})</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">{{ $tr('payments.modal.period', 'Period') }}:</span>
                        <span class="font-medium text-main-text">{{ payForm.numMonths }} {{ payForm.numMonths > 1 ? $tr('payments.modal.months', 'Months') : $tr('payments.modal.month', 'Month') }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">{{ $tr('payments.history.total_paid', 'Total Paid') }}:</span>
                        <span class="font-medium text-brand-blue">{{ fmt(payForm.amount) }} {{ $tr('payments.currency', 'ETB') }}</span>
                    </div>
                    <div v-if="payForm.numMonths === 1" class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">{{ $tr('payments.modal.fine_paid', 'Fine Paid') }}:</span>
                        <span class="font-medium text-main-text">{{ fmt(payForm.fine || 0) }} {{ $tr('payments.currency', 'ETB') }}</span>
                    </div>
                    <div v-if="surplusAmount > 0" class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">
                            {{ payForm.giftSurplus ? $tr('payments.modal.gift_surplus', 'Gift Surplus') : $tr('payments.modal.credit_created', 'Credit Created') }}:
                        </span>
                        <span class="font-medium" :class="payForm.giftSurplus ? 'text-pink-600' : 'text-violet-500'">
                            +{{ fmt(surplusAmount) }} {{ $tr('payments.currency', 'ETB') }}
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">{{ $tr('payments.modal.method', 'Method') }}:</span>
                        <span class="font-medium text-main-text capitalize">{{ payForm.method }}</span>
                    </div>
                    <div v-if="payForm.reference" class="flex flex-col">
                        <span class="text-xs text-main-text/60 font-medium">{{ $tr('payments.modal.reference', 'Reference') }}:</span>
                        <span class="font-medium text-main-text">{{ payForm.reference }}</span>
                    </div>
                </div>

                <div v-if="payForm.numMonths > 1 && bulkPreview" class="mt-4">
                    <p class="font-semibold mb-2">{{ $tr('payments.modal.month_by_month', 'Month-by-Month Breakdown:') }}</p>
                    <div class="max-h-40 overflow-y-auto custom-scrollbar border border-card-border rounded-lg">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-card-bg/50 sticky top-0 border-b border-card-border">
                                <tr>
                                    <th class="px-2 py-1.5">{{ $tr('payments.modal.month', 'Month') }}</th>
                                    <th class="px-2 py-1.5 text-right">{{ $tr('payments.table.base', 'Base') }}</th>
                                    <th class="px-2 py-1.5 text-right">{{ $tr('payments.table.fine', 'Fine') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-card-border/50">
                                <tr v-for="(row, idx) in bulkPreview.rows" :key="idx" class="hover:bg-main-text/5">
                                    <td class="px-2 py-1.5">{{ ethMonthName(row.month) }} {{ row.year }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ fmt(row.base) }} {{ $tr('payments.currency', 'ETB') }}</td>
                                    <td class="px-2 py-1.5 text-right" :class="{'text-rose-500 font-medium': row.fine > 0, 'text-emerald-500': row.fine == 0}">
                                        {{ fmt(row.fine) }} {{ $tr('payments.currency', 'ETB') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </ConfirmDialog>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden { display: none !important; }
    body, html { background: white !important; color: black !important; }
    .print-table { width: 100% !important; font-size: 10pt !important; border-collapse: collapse !important; }
    .print-table th, .print-table td { border: 1px solid #ccc !important; padding: 4px 6px !important; }
}
</style>
