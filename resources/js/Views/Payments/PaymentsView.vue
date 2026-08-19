<script setup lang="ts">
import { ref, onMounted, computed, watch, getCurrentInstance } from 'vue';
import {
    Search, CreditCard, AlertCircle, CheckCircle2, Clock,
    RefreshCw, History, XCircle, User, BarChart2, ChevronLeft, ChevronRight, Wallet
} from 'lucide-vue-next';
import apiClient from '@/api/apiClient';
import { useToastStore } from '@/stores/toast';
import PaymentHistoryModal from './components/PaymentHistoryModal.vue';
import Modal from '@/components/common/Modal.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';

const toast = useToastStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

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
const ethMonthName = (v: number) => ETH_MONTHS.find(m => m.value === v)?.label ?? v;
const ethMonthLatin = (v: number) => ETH_MONTHS.find(m => m.value === v)?.latin ?? '';

const getCurrentEthDate = () => {
    try {
        const parts = new Intl.DateTimeFormat('en-US-u-ca-ethiopic', { year: 'numeric', month: 'numeric' }).formatToParts(new Date());
        const yearStr = parts.find(p => p.type === 'year')?.value;
        const monthStr = parts.find(p => p.type === 'month')?.value;
        // Some older browsers might return strings like "2018 ERA1", so parse out just the numbers
        return {
            year: parseInt(yearStr?.replace(/\D/g, '') || '2017', 10),
            month: parseInt(monthStr?.replace(/\D/g, '') || '1', 10)
        };
    } catch (e) {
        // Fallback if Intl Ethiopic is not supported in the browser
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
const meta       = ref({ total: 0, per_page: 25, current_page: 1, last_page: 1 });

const currentEth = getCurrentEthDate();

const filters = ref({
    year:           currentEth.year,
    month:          currentEth.month,
    search:         '',
    work_status:    'all',
    payment_status: 'all',
    grade:          '',
    age_min:        '',
    age_max:        '',
    per_page:       25,
    page:           1,
});

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
        () => filters.value.grade,
        () => filters.value.age_min,
        () => filters.value.age_max,
        () => filters.value.page,
    ],
    () => loadData()
);

const yearOptions = computed(() => {
    const base = currentEth.year;
    // Do not show future years
    return [-5, -4, -3, -2, -1, 0].map(o => ({ value: base + o, label: String(base + o) }));
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
        if (filters.value.grade)                        params.grade          = filters.value.grade;
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

onMounted(loadData);

// ── Name helper ───────────────────────────────────────────────────────────────
const displayName = (u: any) => {
    if (!u?.name) return `Member #${u?.id}`;
    if (typeof u.name === 'object') return u.name.am || u.name.en || `Member #${u.id}`;
    return u.name;
};
const displayNameEn = (u: any) => {
    if (!u?.name) return '';
    if (typeof u.name === 'object') return u.name.en || '';
    return '';
};

// ── Status config ─────────────────────────────────────────────────────────────
type StatusKey = 'paid' | 'pending' | 'partial' | 'late' | 'exempt';
const STATUS: Record<StatusKey, { label: string; cls: string }> = {
    paid:    { label: 'Paid',    cls: 'bg-emerald-500/10 text-emerald-500' },
    partial: { label: 'Partial', cls: 'bg-amber-500/10  text-amber-500'   },
    late:    { label: 'Late',    cls: 'bg-rose-500/10   text-rose-500'    },
    pending: { label: 'Pending', cls: 'bg-brand-blue/10 text-brand-blue'  },
    exempt:  { label: 'Exempt',  cls: 'bg-gray-400/10   text-gray-400'    },
};
const statusCfg = (s: string) => STATUS[s as StatusKey] ?? { label: s, cls: 'bg-gray-400/10 text-gray-400' };
const fmt = (v: any) => parseFloat(v ?? 0).toFixed(2);

// ── Reset filters ─────────────────────────────────────────────────────────────
const resetFilters = () => {
    filters.value.search         = '';
    filters.value.work_status    = 'all';
    filters.value.payment_status = 'all';
    filters.value.grade          = '';
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
    amount:    '',
    fine:      '0',
    method:    'cash',
    reference: '',
    numMonths: 1,
});

// Bulk preview rows from backend
const bulkPreview = ref<any>(null);

const openPayModal = (u: any) => {
    payUser.value = u;
    payForm.value = {
        amount:    fmt(u.balance),
        fine:      '0',
        method:    'cash',
        reference: '',
        numMonths: 1,
    };
    bulkPreview.value = null;
    payModal.value = true;
};

// Fetch backend preview when numMonths > 1 or amount changes
const fetchPreview = async () => {
    if (!payUser.value || !payForm.value.amount || parseFloat(payForm.value.amount) <= 0) {
        bulkPreview.value = null;
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
                amount:     parseFloat(payForm.value.amount),
            },
        });
        bulkPreview.value = res.data.data;
    } catch {
        bulkPreview.value = null;
    } finally {
        previewLoading.value = false;
    }
};

// Watch for changes to refresh preview
watch([() => payForm.value.numMonths, () => payForm.value.amount], () => {
    if (payModal.value) fetchPreview();
}, { debounce: 300 } as any);

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
        <div class="flex items-start justify-between mb-4 shrink-0 gap-4">
            <div>
                <h1 class="text-xl font-bold text-main-text">Monthly Payments</h1>
                <p class="text-sm text-main-text/50 mt-0.5">
                    {{ ethMonthName(filters.month) }} ({{ ethMonthLatin(filters.month) }}) {{ filters.year }}
                </p>
            </div>
            <button @click="loadData" :disabled="loading"
                class="flex items-center gap-2 px-3 py-2 text-sm rounded-xl border border-card-border text-main-text/60 hover:bg-card-bg transition-colors disabled:opacity-40">
                <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                Refresh
            </button>
        </div>

        <!-- ── Summary Cards ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 mb-4 shrink-0">
            <div class="col-span-1 bg-card-bg border border-card-border rounded-xl p-3 text-center">
                <div class="text-xs text-main-text/40 mb-1">Eligible</div>
                <div class="text-lg font-bold text-main-text">{{ stats.eligible ?? '—' }}</div>
            </div>
            <div class="col-span-1 bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-3 text-center">
                <div class="text-xs text-emerald-500 mb-1">Paid</div>
                <div class="text-lg font-bold text-emerald-500">{{ stats.paid ?? '—' }}</div>
            </div>
            <div class="col-span-1 bg-brand-blue/5 border border-brand-blue/20 rounded-xl p-3 text-center">
                <div class="text-xs text-brand-blue mb-1">Pending</div>
                <div class="text-lg font-bold text-brand-blue">{{ stats.pending ?? '—' }}</div>
            </div>
            <div class="col-span-1 bg-rose-500/5 border border-rose-500/20 rounded-xl p-3 text-center">
                <div class="text-xs text-rose-500 mb-1">Late</div>
                <div class="text-lg font-bold text-rose-500">{{ stats.late ?? '—' }}</div>
            </div>
            <div class="col-span-1 bg-amber-500/5 border border-amber-500/20 rounded-xl p-3 text-center">
                <div class="text-xs text-amber-500 mb-1">Partial</div>
                <div class="text-lg font-bold text-amber-500">{{ stats.partial ?? '—' }}</div>
            </div>
            <div class="col-span-1 bg-card-bg border border-card-border rounded-xl p-3 text-center">
                <div class="text-xs text-main-text/40 mb-1">Exempt</div>
                <div class="text-lg font-bold text-main-text/40">{{ stats.exempt ?? '—' }}</div>
            </div>
            <div class="col-span-1 bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-3 text-center">
                <div class="text-xs text-emerald-500 mb-1">Collected</div>
                <div class="text-sm font-bold text-emerald-500">{{ fmt(stats.collected) }}</div>
            </div>
            <div class="col-span-1 bg-rose-500/5 border border-rose-500/20 rounded-xl p-3 text-center">
                <div class="text-xs text-rose-500 mb-1">Outstanding</div>
                <div class="text-sm font-bold text-rose-500">{{ fmt(stats.outstanding) }}</div>
            </div>
        </div>

        <!-- ── Main Card ──────────────────────────────────────────────────── -->
        <div class="bg-card-bg/50 backdrop-blur-xl border border-card-border rounded-2xl flex-1 flex flex-col overflow-hidden">

            <!-- Filters -->
            <div class="p-3 border-b border-card-border/60 bg-card-bg/30 flex flex-wrap gap-2 items-center shrink-0">
                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-main-text/40" />
                    <input v-model="filters.search" placeholder="Search name or ID…"
                        class="pl-8 pr-3 h-9 w-52 text-sm rounded-lg border border-input-border bg-input-bg text-main-text focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                </div>

                <!-- Month -->
                <select v-model.number="filters.month" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option v-for="m in ETH_MONTHS" :key="m.value" :value="m.value">{{ m.label }} ({{ m.latin }})</option>
                </select>

                <!-- Year -->
                <select v-model.number="filters.year" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option v-for="y in yearOptions" :key="y.value" :value="y.value">{{ y.value }}</option>
                </select>

                <!-- Work Status -->
                <select v-model="filters.work_status" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option value="all">All Types</option>
                    <option value="student">Students</option>
                    <option value="worker">Workers</option>
                </select>

                <!-- Payment Status -->
                <select v-model="filters.payment_status" @change="filters.page=1"
                    class="h-9 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30">
                    <option value="all">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="late">Late</option>
                    <option value="exempt">Exempt</option>
                </select>

                <!-- Grade -->
                <input v-model="filters.grade" @change="filters.page=1"
                    placeholder="Grade" type="text" maxlength="5"
                    class="h-9 w-20 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30" />

                <!-- Age range -->
                <div class="flex items-center gap-1">
                    <input v-model="filters.age_min" @change="filters.page=1"
                        placeholder="Age ≥" type="number" min="0"
                        class="h-9 w-16 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30" />
                    <span class="text-main-text/30 text-xs">–</span>
                    <input v-model="filters.age_max" @change="filters.page=1"
                        placeholder="≤" type="number" min="0"
                        class="h-9 w-16 px-2 text-sm rounded-lg border border-input-border bg-input-bg text-main-text outline-none focus:ring-2 focus:ring-brand-blue/30" />
                </div>

                <!-- Reset -->
                <button @click="resetFilters"
                    class="h-9 px-3 flex items-center gap-1 text-sm rounded-lg border border-card-border text-main-text/50 hover:bg-card-bg transition-colors">
                    <XCircle class="w-3.5 h-3.5" /> Reset
                </button>

                <span class="ml-auto text-xs text-main-text/30">{{ meta.total }} records</span>
            </div>

            <!-- Table -->
            <div class="flex-1 overflow-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="sticky top-0 bg-card-bg/95 backdrop-blur z-10">
                        <tr class="text-xs font-semibold text-main-text/40 capitalize tracking-wider">
                            <th class="px-3 py-2.5">Member</th>
                            <th class="px-3 py-2.5 text-center">Grade</th>
                            <th class="px-3 py-2.5 text-center">Age</th>
                            <th class="px-3 py-2.5 text-center">Type</th>
                            <th class="px-3 py-2.5 text-right">Base</th>
                            <th class="px-3 py-2.5 text-right">Fine</th>
                            <th class="px-3 py-2.5 text-right">Total Due</th>
                            <th class="px-3 py-2.5 text-right">Paid</th>
                            <th class="px-3 py-2.5 text-right">Balance</th>
                            <th class="px-3 py-2.5 text-center">Status</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-card-border/50">
                        <tr v-if="loading">
                            <td colspan="11" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-main-text/40">
                                    <RefreshCw class="w-7 h-7 animate-spin text-brand-blue" />
                                    <span class="text-xs">Loading…</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="rows.length === 0">
                            <td colspan="11" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-main-text/30">
                                    <BarChart2 class="w-10 h-10" />
                                    <span class="text-sm font-medium">No records match the selected filters.</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="u in rows" :key="u.id"
                            class="hover:bg-main-text/[0.02] transition-colors">

                            <td class="px-3 py-2.5">
                                <div class="font-semibold text-main-text leading-tight">{{ displayName(u) }}</div>
                                <div v-if="displayNameEn(u)" class="text-xs text-main-text/40">{{ displayNameEn(u) }}</div>
                                <div class="text-xs text-main-text/30">#{{ u.id }}</div>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full bg-brand-blue/10 text-brand-blue text-xs font-semibold">
                                    {{ u.grade ?? '—' }}
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-center text-main-text/60 text-xs">{{ u.age ?? '—' }}</td>

                            <td class="px-3 py-2.5 text-center">
                                <span :class="[
                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold capitalize',
                                    u.work_status === 'worker' ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600'
                                ]">
                                    <User class="w-2.5 h-2.5" />
                                    {{ u.work_status ?? 'N/A' }}
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-right text-main-text/70 text-xs">{{ fmt(u.base_amount) }}</td>

                            <td class="px-3 py-2.5 text-right text-xs">
                                <span v-if="parseFloat(u.fine_amount ?? 0) > 0" class="text-rose-500 font-semibold">
                                    {{ fmt(u.fine_amount) }}
                                </span>
                                <span v-else class="text-main-text/20">—</span>
                            </td>

                            <td class="px-3 py-2.5 text-right font-bold text-main-text text-xs">{{ fmt(u.total_amount_due) }}</td>
                            <td class="px-3 py-2.5 text-right text-emerald-500 font-semibold text-xs">{{ fmt(u.amount_paid) }}</td>

                            <td class="px-3 py-2.5 text-right text-xs font-bold">
                                <div class="flex flex-col items-end gap-0.5">
                                    <span :class="parseFloat(u.balance ?? 0) > 0 ? 'text-amber-500' : 'text-main-text/20'">
                                        {{ fmt(u.balance) }}
                                    </span>
                                    <!-- Credit badge -->
                                    <span v-if="parseFloat(u.available_credit ?? 0) > 0"
                                        class="inline-flex items-center gap-0.5 text-[10px] px-1.5 py-0.5 rounded-full bg-violet-500/10 text-violet-500 font-semibold">
                                        <Wallet class="w-2.5 h-2.5" />
                                        +{{ fmt(u.available_credit) }} credit
                                    </span>
                                </div>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <div>
                                    <span :class="['px-2 py-0.5 text-xs font-semibold rounded-full capitalize', statusCfg(u.status).cls]">
                                        {{ statusCfg(u.status).label }}
                                    </span>
                                    <div v-if="u.status === 'exempt' && u.exempt_reason"
                                        class="text-xs text-main-text/30 mt-0.5 max-w-[120px] mx-auto leading-tight">
                                        {{ u.exempt_reason }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-2.5">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openHistory(u)"
                                        class="p-1.5 rounded-lg hover:bg-card-border text-main-text/30 hover:text-main-text/70 transition-colors"
                                        title="View history">
                                        <History class="w-3.5 h-3.5" />
                                    </button>
                                    <button v-if="u.status !== 'paid' && u.status !== 'exempt'"
                                        @click="openPayModal(u)"
                                        class="px-2.5 py-1 text-xs rounded-lg bg-brand-blue text-white font-semibold hover:bg-brand-blue/80 transition-colors whitespace-nowrap flex items-center gap-1">
                                        <CreditCard class="w-3 h-3" />
                                        {{ u.status === 'partial' ? 'Pay Balance' : 'Pay' }}
                                    </button>
                                    <span v-else-if="u.status === 'paid'"
                                        class="text-xs text-emerald-500 flex items-center gap-0.5 font-semibold">
                                        <CheckCircle2 class="w-3.5 h-3.5" /> Done
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1"
                class="border-t border-card-border/60 bg-card-bg/30 px-4 py-2.5 flex items-center justify-between gap-4 shrink-0">
                <span class="text-xs text-main-text/40">
                    Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} records)
                </span>
                <div class="flex items-center gap-1">
                    <button :disabled="meta.current_page <= 1"
                        @click="filters.page = meta.current_page - 1"
                        class="p-1.5 rounded-lg border border-card-border hover:bg-card-bg transition-colors disabled:opacity-30">
                        <ChevronLeft class="w-4 h-4 text-main-text/60" />
                    </button>
                    <template v-for="p in meta.last_page" :key="p">
                        <button v-if="Math.abs(p - meta.current_page) <= 2 || p === 1 || p === meta.last_page"
                            @click="filters.page = p"
                            :class="['w-8 h-8 text-xs rounded-lg border transition-colors font-medium',
                                p === meta.current_page
                                    ? 'bg-brand-blue text-white border-brand-blue'
                                    : 'border-card-border hover:bg-card-bg text-main-text/60']">
                            {{ p }}
                        </button>
                        <span v-else-if="Math.abs(p - meta.current_page) === 3" class="text-main-text/30 text-xs px-1">…</span>
                    </template>
                    <button :disabled="meta.current_page >= meta.last_page"
                        @click="filters.page = meta.current_page + 1"
                        class="p-1.5 rounded-lg border border-card-border hover:bg-card-bg transition-colors disabled:opacity-30">
                        <ChevronRight class="w-4 h-4 text-main-text/60" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Record Payment Modal ───────────────────────────────────────── -->
        <Modal :show="payModal" @close="payModal = false" size="md" hide-header no-padding>
            <div class="relative bg-main-bg w-full rounded-2xl flex flex-col overflow-hidden max-h-[90vh]">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-card-border bg-card-bg flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-bold text-main-text text-base">Record Payment</h3>
                        <p class="text-xs text-main-text/50 mt-0.5">
                            {{ displayName(payUser) }} —
                            <span v-if="payForm.numMonths === 1">{{ ethMonthName(filters.month) }} {{ filters.year }}</span>
                            <span v-else>{{ payForm.numMonths }} months from {{ ethMonthName(filters.month) }} {{ filters.year }}</span>
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
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-2">Number of Months</label>
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
                            Payment will be distributed across {{ payForm.numMonths }} consecutive months starting {{ ethMonthName(filters.month) }} {{ filters.year }}.
                        </p>
                    </div>

                    <!-- Single month summary (numMonths === 1) -->
                    <div v-if="payForm.numMonths === 1" class="grid grid-cols-3 gap-3 bg-card-bg rounded-xl p-3 border border-card-border text-center">
                        <div>
                            <div class="text-xs text-main-text/40">Base</div>
                            <div class="font-bold text-main-text text-sm">{{ fmt(payUser?.base_amount) }} ETB</div>
                        </div>
                        <div>
                            <div class="text-xs text-rose-400">Fine</div>
                            <div class="font-bold text-rose-500 text-sm">{{ fmt(payUser?.fine_amount) }} ETB</div>
                        </div>
                        <div>
                            <div class="text-xs text-amber-500">Balance</div>
                            <div class="font-bold text-amber-500 text-sm">{{ fmt(payUser?.balance) }} ETB</div>
                        </div>
                    </div>

                    <!-- Available Credit (always show if > 0) -->
                    <div v-if="parseFloat(payUser?.available_credit ?? 0) > 0"
                        class="flex items-center gap-2 bg-violet-500/8 border border-violet-500/20 rounded-xl px-3 py-2.5">
                        <Wallet class="w-4 h-4 text-violet-500 shrink-0" />
                        <div class="text-xs">
                            <span class="font-semibold text-violet-500">{{ fmt(payUser?.available_credit) }} ETB</span>
                            <span class="text-main-text/50 ml-1">available credit will be applied automatically</span>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">Amount Paying (ETB) *</label>
                        <input v-model="payForm.amount" type="number" min="0.01" step="0.01"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                        <p v-if="payForm.numMonths === 1 && parseFloat(payForm.amount) > parseFloat(payUser?.balance ?? 0)"
                            class="text-xs text-violet-500 mt-1">
                            ✦ {{ (parseFloat(payForm.amount) - parseFloat(payUser?.balance ?? 0)).toFixed(2) }} ETB surplus will become credit
                        </p>
                    </div>

                    <!-- Fine paid (single month only) -->
                    <div v-if="payForm.numMonths === 1">
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">Fine Included (ETB)</label>
                        <input v-model="payForm.fine" type="number" min="0" step="0.01"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                    </div>

                    <!-- Method -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">Payment Method</label>
                        <select v-model="payForm.method"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money (Telebirr)</option>
                        </select>
                    </div>

                    <!-- Reference -->
                    <div>
                        <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest block mb-1.5">Reference Number (optional)</label>
                        <input v-model="payForm.reference" type="text" maxlength="100"
                            class="w-full h-10 px-3 rounded-xl border border-input-border bg-input-bg text-main-text text-sm focus:ring-2 focus:ring-brand-blue/30 outline-none" />
                    </div>

                    <!-- Multi-month breakdown preview -->
                    <div v-if="payForm.numMonths > 1">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-main-text/50 capitalize tracking-widest">Payment Breakdown</label>
                            <RefreshCw v-if="previewLoading" class="w-3.5 h-3.5 text-brand-blue animate-spin" />
                        </div>

                        <div v-if="bulkPreview" class="rounded-xl border border-card-border overflow-hidden">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-card-bg text-main-text/40 capitalize tracking-widest">
                                        <th class="px-3 py-2 text-left font-semibold">Month</th>
                                        <th class="px-3 py-2 text-right font-semibold">Base</th>
                                        <th class="px-3 py-2 text-right font-semibold text-rose-400">Fine</th>
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
                                        <td class="px-3 py-2 text-right font-bold text-emerald-500">{{ fmt(bulkPreview.total_applied) }} ETB</td>
                                    </tr>
                                    <tr v-if="parseFloat(bulkPreview.surplus_credit ?? 0) > 0" class="bg-violet-500/5 border-t border-violet-500/20">
                                        <td colspan="4" class="px-3 py-2 text-right text-violet-500 font-semibold">Credit Created</td>
                                        <td class="px-3 py-2 text-right font-bold text-violet-500">+{{ fmt(bulkPreview.surplus_credit) }} ETB</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else-if="!previewLoading && parseFloat(payForm.amount) > 0"
                            class="text-center py-4 text-main-text/30 text-xs">Enter amount to see breakdown</div>
                    </div>

                    <!-- Single month remaining preview -->
                    <div v-if="payForm.numMonths === 1" class="bg-card-bg rounded-xl p-3 border border-card-border text-xs text-main-text/60 flex justify-between">
                        <span>Remaining after this payment:</span>
                        <span class="font-bold text-main-text">
                            {{ Math.max(0, parseFloat(payUser?.balance ?? 0) - parseFloat(payForm.amount || '0')).toFixed(2) }} ETB
                        </span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 py-3 border-t border-card-border bg-card-bg flex justify-end gap-2 shrink-0">
                    <button @click="payModal = false"
                        class="px-4 py-2 rounded-xl border border-card-border text-main-text/60 text-sm font-semibold hover:bg-main-text/5 transition-colors">
                        Cancel
                    </button>
                    <button @click="requestSubmit"
                        :disabled="payLoading || !payForm.amount || parseFloat(payForm.amount) <= 0"
                        class="px-4 py-2 rounded-xl bg-brand-blue text-white text-sm font-semibold hover:bg-brand-blue/80 transition-colors disabled:opacity-40 flex items-center gap-2">
                        <RefreshCw v-if="payLoading" class="w-4 h-4 animate-spin" />
                        <CreditCard v-else class="w-4 h-4" />
                        {{ payForm.numMonths > 1 ? `Pay ${payForm.numMonths} Months` : 'Confirm Payment' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ── History Modal ─────────────────────────────────────────────── -->
        <PaymentHistoryModal :is-open="historyOpen" :user="historyUser" @close="historyOpen = false" />

        <!-- ── Confirmation Modal ─────────────────────────────────────────────── -->
        <ConfirmDialog
            :show="confirmModal"
            title="Confirm Payment"
            message=""
            confirm-text="Record Payment"
            variant="primary"
            :loading="payLoading"
            @close="confirmModal = false"
            @confirm="submitPayment"
        >
            <div class="mt-2 text-sm text-main-text/70 space-y-4">
                <p>Are you sure you want to record this payment?</p>
                <div class="bg-card-bg/50 border border-card-border p-4 rounded-xl space-y-2">
                    <div class="flex justify-between">
                        <span class="font-semibold">Member:</span>
                        <span>{{ displayName(payUser) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold">Period:</span>
                        <span>{{ payForm.numMonths }} {{ payForm.numMonths > 1 ? 'Months' : 'Month' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold">Total Paid:</span>
                        <span class="font-bold text-brand-blue">{{ fmt(payForm.amount) }} ETB</span>
                    </div>
                    <div v-if="payForm.numMonths === 1" class="flex justify-between">
                        <span class="font-semibold text-rose-500">Fine Paid:</span>
                        <span class="font-bold text-rose-500">{{ fmt(payForm.fine || 0) }} ETB</span>
                    </div>
                    <div v-if="bulkPreview && parseFloat(bulkPreview.surplus_credit ?? 0) > 0" class="flex justify-between">
                        <span class="font-semibold text-violet-500">Credit Created:</span>
                        <span class="font-bold text-violet-500">+{{ fmt(bulkPreview.surplus_credit) }} ETB</span>
                    </div>
                    <div class="flex justify-between border-t border-card-border/50 pt-2 mt-2">
                        <span class="font-semibold">Method:</span>
                        <span class="capitalize">{{ payForm.method }}</span>
                    </div>
                    <div v-if="payForm.reference" class="flex justify-between">
                        <span class="font-semibold">Reference:</span>
                        <span>{{ payForm.reference }}</span>
                    </div>
                </div>
                
                <div v-if="payForm.numMonths > 1 && bulkPreview" class="mt-4">
                    <p class="font-semibold mb-2">Month-by-Month Breakdown:</p>
                    <div class="max-h-40 overflow-y-auto custom-scrollbar border border-card-border rounded-lg">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-card-bg/50 sticky top-0 border-b border-card-border">
                                <tr>
                                    <th class="px-2 py-1.5">Month</th>
                                    <th class="px-2 py-1.5 text-right">Base</th>
                                    <th class="px-2 py-1.5 text-right">Fine</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-card-border/50">
                                <tr v-for="(row, idx) in bulkPreview.rows" :key="idx" class="hover:bg-main-text/5">
                                    <td class="px-2 py-1.5">{{ ethMonthLatin(row.month) }} {{ row.year }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ fmt(row.base) }} ETB</td>
                                    <td class="px-2 py-1.5 text-right" :class="{'text-rose-500 font-medium': row.fine > 0, 'text-emerald-500': row.fine == 0}">
                                        {{ fmt(row.fine) }} ETB
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
