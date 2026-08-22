<script setup lang="ts">
import { ref, watch, getCurrentInstance } from 'vue';
import apiClient from '@/api/apiClient';
import { X, Calendar, RefreshCw, ChevronDown, ChevronRight, CreditCard } from 'lucide-vue-next';
import { useToastStore } from '@/stores/toast';
import Modal from '@/components/common/Modal.vue';
import { formatDate, formatTime } from '@/utils/format';
import { useLanguageStore } from '@/stores/languageStore';
import { storeToRefs } from 'pinia';

const props = defineProps<{
    user: any;
    isOpen: boolean;
}>();

const emit = defineEmits(['close']);
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
const toast = useToastStore();
const { currentLanguage } = storeToRefs(useLanguageStore());

const displayFullName = (u: any) => {
    if (!u) return '';
    const first = typeof u.name === 'object' ? (u.name.am || u.name.en || '') : (u.name || '');
    const father = u.father_name || '';
    const grand  = u.grandfather_name || '';
    return [first, father, grand].filter(Boolean).join(' ');
};

const loading = ref(false);
const payments = ref<any[]>([]);
const summary = ref<any>({});
const expandedRows = ref<Set<number>>(new Set());

const ethMonthName = (v: number) => {
    const latins: Record<number, string> = {
        1: 'meskerem', 2: 'tikimt', 3: 'hidar', 4: 'tahsas',
        5: 'tir', 6: 'yekatit', 7: 'megabit', 8: 'miazia',
        9: 'ginbot', 10: 'sene', 11: 'hamle', 12: 'nehase', 13: 'pagume'
    };
    return $tr(`payments.months.${latins[v]}`, latins[v]);
};
const registrationDate = ref('');

watch(() => props.isOpen, async (newVal) => {
    expandedRows.value.clear();
    if (newVal && props.user) {
        loading.value = true;
        try {
            const res = await apiClient.get(`/payments/history/${props.user.id}`);
            payments.value = res.data.data.payments;
            summary.value = res.data.data.summary;
            registrationDate.value = res.data.data.registration_date || '';
        } catch (err) {
            toast.error('Failed to load payment history');
        } finally {
            loading.value = false;
        }
    } else {
        payments.value = [];
        summary.value = {};
        registrationDate.value = '';
    }
});
const toggleRow = (id: number) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const statusClasses: Record<string, string> = {
    paid: 'bg-emerald-500/10 text-emerald-500',
    partial: 'bg-amber-500/10 text-amber-500',
    late: 'bg-rose-500/10 text-rose-500',
    exempt: 'bg-gray-400/10 text-gray-400',
    pending: 'bg-brand-blue/10 text-brand-blue',
};

const fmt = (v: any) => parseFloat(v ?? 0).toFixed(2);
const fmtDate = (dateStr: string) => {
    if (!dateStr) return '—';
    return `${formatDate(dateStr, currentLanguage.value)} - ${formatTime(dateStr, currentLanguage.value)}`;
};

// Credit used = generated - still available (correct representation)
const creditUsed = (sum: any) => {
    const generated = parseFloat(sum?.total_credit_generated ?? 0);
    const available = parseFloat(sum?.available_credit ?? 0);
    return Math.max(0, generated - available).toFixed(2);
};

// Translate payment status key to current language
const statusLabel = (status: string) => {
    const key = `payments.status.${status}`;
    const defaults: Record<string, string> = {
        paid: 'Paid', partial: 'Partial', late: 'Late',
        exempt: 'Exempt', pending: 'Pending',
    };
    return $tr(key, defaults[status] ?? status);
};

// Translate payment method
const methodLabel = (method: string) => {
    if (!method) return '—';
    const key = `payments.method.${method.replace(/ /g, '_')}`;
    const defaults: Record<string, string> = {
        cash: 'Cash', bank_transfer: 'Bank Transfer',
        mobile_banking: 'Mobile Banking', cheque: 'Cheque',
    };
    return $tr(key, defaults[method] ?? method.replace(/_/g, ' '));
};

// Translate credit source note stored in DB (e.g. "Overpayment on 2018/12")
const creditSourceLabel = (note: string) => {
    if (!note) return $tr('payments.modal.credit_applied', 'Credit');
    // Pattern: "Overpayment on YEAR/MONTH"
    const overpayMatch = note.match(/Overpayment on (\d+)\/(\d+)/i);
    if (overpayMatch) {
        const prefix = $tr('payments.modal.overpayment_note', 'Overpayment on');
        return `${prefix} ${overpayMatch[1]}/${overpayMatch[2]}`;
    }
    // Pattern: "Gift/donation surplus on payment for YEAR/MONTH"
    const giftMatch = note.match(/Gift\/donation surplus on payment for (\d+)\/(\d+)/i);
    if (giftMatch) {
        const prefix = $tr('payments.modal.gift_surplus_note', 'Gift/Donation for');
        return `${prefix} ${giftMatch[1]}/${giftMatch[2]}`;
    }
    return note;
};
</script>

<template>
    <Modal :show="isOpen" @close="emit('close')" size="lg" hide-header no-padding>
        <div class="flex flex-col h-full max-h-[85vh]">

                <!-- Header -->
                <div class="px-6 py-4 border-b border-card-border flex justify-between items-center bg-card-bg shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-main-text flex items-center gap-2">
                            <Calendar class="w-5 h-5 text-brand-blue" />
                            {{ $tr('payments.history.title', 'Payment History') }}
                        </h2>
                        <div class="text-sm text-main-text/60 mt-0.5 flex flex-wrap gap-x-4 items-center">
                            <span class="font-semibold text-main-text">{{ displayFullName(user) }}</span>
                            <span class="text-main-text/20">|</span>
                            <span>{{ $tr('payments.history.reg_id', 'Reg ID') }}: <strong class="font-mono text-main-text">{{ user?.registration_id }}</strong></span>
                            <span v-if="registrationDate" class="text-main-text/20">|</span>
                            <span v-if="registrationDate">{{ $tr('payments.history.reg_date', 'Reg Date') }}: <strong class="text-main-text">{{ registrationDate }}</strong></span>
                        </div>
                    </div>
                    <button @click="emit('close')" class="p-2 rounded-xl hover:bg-main-text/5 text-main-text/60 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-auto p-6 space-y-4">
                    <div v-if="loading" class="flex justify-center p-16">
                        <div class="flex flex-col items-center gap-3 text-main-text/40">
                            <RefreshCw class="w-8 h-8 animate-spin text-brand-blue" />
                            <span class="text-sm">{{ $tr('payments.history.loading', 'Loading history…') }}</span>
                        </div>
                    </div>

                    <div v-else-if="payments.length === 0" class="flex flex-col items-center justify-center p-16 text-main-text/40">
                        <Calendar class="w-12 h-12 mb-3 text-main-text/20" />
                        <p class="text-sm font-medium">{{ $tr('payments.history.no_history', 'No recorded payment history found.') }}</p>
                    </div>

                    <div v-else class="space-y-6">
                        <!-- Summary metrics -->
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6 shrink-0">
                            <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.total_paid', 'Total Paid') }}</div>
                                <div class="text-lg font-bold text-emerald-500">{{ fmt(summary.total_paid) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                            <div class="bg-rose-500/5 border border-rose-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.total_fines', 'Total Fines') }}</div>
                                <div class="text-lg font-bold text-rose-500">{{ fmt(summary.total_fines) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                            <div class="bg-violet-500/5 border border-violet-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.total_credit_generated', 'Total Credit Gen') }}</div>
                                <div class="text-lg font-bold text-violet-500">{{ fmt(summary.total_credit_generated) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                            <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.total_credit_used', 'Total Credit Used') }}</div>
                                <div class="text-lg font-bold text-indigo-500">{{ fmt(summary.total_credit_applied) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                            <div class="bg-purple-500/5 border border-purple-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.current_credit', 'Current Credit') }}</div>
                                <div class="text-lg font-bold text-purple-600">{{ fmt(summary.available_credit) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                            <div class="bg-pink-500/5 border border-pink-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.total_donations', 'Total Donations') }}</div>
                                <div class="text-lg font-bold text-pink-600">{{ fmt(summary.total_donations) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-3 text-center flex flex-col justify-center min-h-[5.5rem]">
                                <div class="text-[10px] text-main-text/40 font-bold capitalize tracking-wider mb-1">{{ $tr('payments.history.total_outstanding', 'Total Outstanding') }}</div>
                                <div class="text-lg font-bold text-amber-500">{{ fmt(summary.total_outstanding) }} {{ $tr('payments.currency', 'ETB') }}</div>
                            </div>
                        </div>

                        <!-- History DataGrid -->
                        <div class="border border-card-border rounded-xl overflow-hidden bg-card-bg">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-card-bg/50 border-b border-card-border text-xs font-semibold text-main-text/50 capitalize tracking-wider">
                                    <tr>
                                        <th class="w-10"></th>
                                        <th class="px-4 py-3.5">{{ $tr('payments.table.period', 'Period') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.base', 'Base') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.fine', 'Fine') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.paid', 'Paid') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.credit_used', 'Credit Used') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.credit_gen', 'Credit Gen') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.donation', 'Donation') }}</th>
                                        <th class="px-4 py-3.5 text-right">{{ $tr('payments.table.outstanding', 'Outstanding') }}</th>
                                        <th class="px-4 py-3.5 text-center">{{ $tr('payments.table.status', 'Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-card-border/60">
                                    <template v-for="p in payments" :key="p.id">
                                        <!-- Master Row -->
                                        <tr class="hover:bg-main-text/[0.02] transition-colors cursor-pointer" @click="toggleRow(p.id)">
                                            <td class="pl-4 py-3 text-main-text/40">
                                                <ChevronDown v-if="expandedRows.has(p.id)" class="w-4 h-4" />
                                                <ChevronRight v-else class="w-4 h-4" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-main-text">{{ ethMonthName(p.for_month) }}</div>
                                                <div class="text-xs text-main-text/40">{{ $tr('payments.modal.year', 'Year') }} {{ p.for_year }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-main-text/70">{{ fmt(p.base_amount) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <span v-if="parseFloat(p.fine_amount) > 0" class="text-rose-500 font-semibold">{{ fmt(p.fine_amount) }}</span>
                                                <span v-else class="text-main-text/30">—</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-emerald-500">{{ fmt(p.amount_paid) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-violet-500">{{ fmt(p.credit_applied) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-indigo-500">{{ fmt(p.credit_generated) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-pink-500">{{ fmt(p.donation) }}</td>
                                            <td class="px-4 py-3 text-right font-bold">
                                                <span :class="parseFloat(p.balance) > 0 ? 'text-amber-500' : 'text-main-text/30'">
                                                    {{ fmt(p.balance) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full capitalize', statusClasses[p.status] ?? 'bg-gray-400/10 text-gray-400']">
                                                    {{ statusLabel(p.status) }}
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- Detail Row (Transactions & Credits) -->
                                        <tr v-if="expandedRows.has(p.id)" class="bg-main-text/[0.01]">
                                            <td colspan="10" class="px-8 py-4 border-l-2 border-l-brand-blue/30">
                                                <div v-if="p.status === 'exempt'" class="text-sm text-main-text/60 italic">
                                                    {{ $tr('payments.history.exempt_note', 'This obligation was marked as exempt.') }}
                                                </div>
                                                <div v-else-if="(!p.transactions || p.transactions.length === 0) && (!p.credit_applications || p.credit_applications.length === 0)" class="text-sm text-main-text/40 italic">
                                                    {{ $tr('payments.history.no_transactions', 'No transactions or credits recorded for this period.') }}
                                                </div>
                                                <div v-else class="space-y-4">
                                                    <!-- Detailed Summary Table -->
                                                    <div class="bg-main-bg border border-card-border/60 rounded-xl p-4 space-y-2">
                                                        <h4 class="text-xs font-bold text-main-text/50 capitalize tracking-widest mb-3">{{ $tr('payments.modal.period_details_summary', 'Period Details Summary') }}</h4>
                                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.base_amount', 'Base Amount') }}:</span> <strong class="text-main-text text-sm">{{ fmt(p.base_amount) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.fine', 'Fine') }}:</span> <strong class="text-rose-500 text-sm">{{ fmt(p.fine_amount) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.total_due', 'Total Due') }}:</span> <strong class="text-main-text text-sm">{{ fmt(p.total_amount_due) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.amount_paid', 'Amount Paid') }}:</span> <strong class="text-emerald-500 text-sm">{{ fmt(p.amount_paid) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.credit_used', 'Credit Used') }}:</span> <strong class="text-violet-500 text-sm">{{ fmt(p.credit_applied) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.credit_generated', 'Credit Generated') }}:</span> <strong class="text-indigo-500 text-sm">{{ fmt(p.credit_generated) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.donation_gift', 'Donation/Gift') }}:</span> <strong class="text-pink-500 text-sm">{{ fmt(p.donation) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.outstanding', 'Outstanding') }}:</span> <strong class="text-amber-500 text-sm">{{ fmt(p.balance) }} {{ $tr('payments.currency', 'ETB') }}</strong></div>
                                                            <div><span class="text-main-text/40 block">{{ $tr('payments.modal.status', 'Status') }}:</span> <span :class="['px-2 py-0.5 mt-0.5 inline-block text-xs font-semibold rounded capitalize', statusClasses[p.status] ?? 'bg-gray-400/10 text-gray-400']">{{ statusLabel(p.status) }}</span></div>
                                                        </div>
                                                    </div>

                                                    <!-- Transactions -->
                                                    <div v-if="p.transactions && p.transactions.length > 0">
                                                        <h4 class="text-xs font-semibold text-main-text/50 capitalize tracking-widest mb-2 flex items-center gap-1.5">
                                                            <CreditCard class="w-3.5 h-3.5" /> {{ $tr('payments.modal.transaction_log', 'Transaction Log') }}
                                                        </h4>
                                                        <div class="space-y-2">
                                                            <div v-for="t in p.transactions" :key="t.id"
                                                                class="bg-main-bg border border-card-border/60 rounded-lg p-3 flex justify-between items-center text-sm">
                                                                <div class="flex flex-col gap-0.5">
                                                                    <span class="font-medium text-main-text">{{ fmtDate(t.paid_at) }}</span>
                                                                    <div class="flex items-center gap-2 text-xs text-main-text/50">
                                                                        <span>{{ methodLabel(t.payment_method) }}</span>
                                                                        <span v-if="t.reference_number" class="px-1.5 py-0.5 rounded bg-main-text/5 text-main-text/60 border border-main-text/10 font-mono text-[10px]">
                                                                            {{ $tr('payments.modal.ref', 'Ref') }}: {{ t.reference_number }}
                                                                        </span>
                                                                        <span v-if="t.recorded_by" class="text-[10px] text-main-text/30">
                                                                            {{ $tr('payments.modal.by_id', 'By ID #') }}{{ t.recorded_by }}
                                                                        </span>
                                                                    </div>
                                                                 </div>
                                                                <div class="text-right">
                                                                    <div class="font-bold text-emerald-500">{{ fmt(t.amount) }} {{ $tr('payments.currency', 'ETB') }}</div>
                                                                    <div v-if="parseFloat(t.fine_paid) > 0" class="text-xs text-rose-400">
                                                                        {{ $tr('payments.modal.incl_fine', 'incl.') }} {{ fmt(t.fine_paid) }} {{ $tr('payments.modal.fine', 'Fine') }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Credit Applications -->
                                                    <div v-if="p.credit_applications && p.credit_applications.length > 0">
                                                        <h4 class="text-xs font-semibold text-violet-500/80 capitalize tracking-widest mb-2">
                                                            {{ $tr('payments.modal.credit_applied', 'Credit Applied') }}
                                                        </h4>
                                                        <div class="space-y-2">
                                                            <div v-for="c in p.credit_applications" :key="c.id"
                                                                class="bg-violet-500/5 border border-violet-500/20 rounded-lg p-3 flex justify-between items-center text-sm">
                                                                <div class="flex flex-col gap-0.5">
                                                                    <span class="font-medium text-violet-500">{{ fmtDate(c.created_at) }}</span>
                                                                    <span class="text-xs text-violet-500/70">{{ creditSourceLabel(c.credit_source) }}</span>
                                                                </div>
                                                                <div class="text-right font-bold text-violet-500">
                                                                    {{ fmt(c.amount_applied) }} {{ $tr('payments.currency', 'ETB') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-card-border bg-card-bg shrink-0 flex justify-end mt-auto">
                    <button @click="emit('close')" class="px-6 py-2 rounded-xl border border-card-border text-main-text font-semibold hover:bg-main-text/5 transition-colors text-sm">
                        {{ $tr('payments.modal.close', 'Close') }}
                    </button>
                </div>
            </div>
    </Modal>
</template>
