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

const loading = ref(false);
const payments = ref<any[]>([]);
const summary = ref<any>({});
const expandedRows = ref<Set<number>>(new Set());

const ethMonths: Record<number, string> = {
    1: 'መስከረም (Meskerem)', 2: 'ጥቅምት (Tikimt)', 3: 'ኅዳር (Hidar)', 4: 'ታኅሣሥ (Tahsas)',
    5: 'ጥር (Tir)', 6: 'የካቲት (Yekatit)', 7: 'መጋቢት (Megabit)', 8: 'ሚያዝያ (Miazia)',
    9: 'ግንቦት (Ginbot)', 10: 'ሰኔ (Sene)', 11: 'ሐምሌ (Hamle)', 12: 'ነሐሴ (Nehase)', 13: 'ጳጉሜ (Pagume)'
};

watch(() => props.isOpen, async (newVal) => {
    expandedRows.value.clear();
    if (newVal && props.user) {
        loading.value = true;
        try {
            const res = await apiClient.get(`/payments/history/${props.user.id}`);
            payments.value = res.data.data.payments;
            summary.value = res.data.data.summary;
        } catch (err) {
            toast.error('Failed to load payment history');
        } finally {
            loading.value = false;
        }
    } else {
        payments.value = [];
        summary.value = {};
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
</script>

<template>
    <Modal :show="isOpen" @close="emit('close')" size="lg" hide-header no-padding>
        <div class="flex flex-col h-full max-h-[85vh]">

                <!-- Header -->
                <div class="px-6 py-4 border-b border-card-border flex justify-between items-center bg-card-bg shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-main-text flex items-center gap-2">
                            <Calendar class="w-5 h-5 text-brand-blue" />
                            Payment History
                        </h2>
                        <p class="text-sm text-main-text/60 mt-0.5">
                            {{ user?.name?.am || user?.name?.en || user?.name }} — ID #{{ user?.id }}
                        </p>
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
                            <span class="text-sm">Loading history…</span>
                        </div>
                    </div>

                    <div v-else-if="payments.length === 0" class="flex flex-col items-center justify-center p-16 text-main-text/40">
                        <Calendar class="w-12 h-12 mb-3 text-main-text/20" />
                        <p class="text-sm font-medium">No recorded payment history found.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <!-- Summary metrics -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 text-center flex flex-col justify-center h-24">
                                <div class="text-xs font-semibold text-emerald-500 capitalize tracking-widest mb-1.5">Total Paid</div>
                                <div class="text-2xl font-bold text-emerald-500">{{ fmt(summary.total_paid) }} ETB</div>
                            </div>
                            <div class="bg-rose-500/5 border border-rose-500/20 rounded-xl p-4 text-center flex flex-col justify-center h-24">
                                <div class="text-xs font-semibold text-rose-500 capitalize tracking-widest mb-1.5">Total Fines</div>
                                <div class="text-2xl font-bold text-rose-500">{{ fmt(summary.total_fines) }} ETB</div>
                            </div>
                            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4 text-center flex flex-col justify-center h-24">
                                <div class="text-xs font-semibold text-amber-500 capitalize tracking-widest mb-1.5">Outstanding</div>
                                <div class="text-2xl font-bold text-amber-500">{{ fmt(summary.total_outstanding) }} ETB</div>
                            </div>
                        </div>

                        <!-- History DataGrid -->
                        <div class="border border-card-border rounded-xl overflow-hidden bg-card-bg">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-card-bg/50 border-b border-card-border text-xs font-semibold text-main-text/50 capitalize tracking-wider">
                                    <tr>
                                        <th class="w-10"></th>
                                        <th class="px-4 py-3.5">Period</th>
                                        <th class="px-4 py-3.5 text-right">Base</th>
                                        <th class="px-4 py-3.5 text-right">Fine</th>
                                        <th class="px-4 py-3.5 text-right">Total Due</th>
                                        <th class="px-4 py-3.5 text-right">Paid</th>
                                        <th class="px-4 py-3.5 text-right">Balance</th>
                                        <th class="px-4 py-3.5 text-center">Status</th>
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
                                                <div class="font-bold text-main-text">{{ ethMonths[p.for_month] }}</div>
                                                <div class="text-xs text-main-text/40">Year {{ p.for_year }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-main-text/70">{{ fmt(p.base_amount) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <span v-if="parseFloat(p.fine_amount) > 0" class="text-rose-500 font-semibold">{{ fmt(p.fine_amount) }}</span>
                                                <span v-else class="text-main-text/30">—</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-main-text">{{ fmt(p.total_amount_due) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-emerald-500">{{ fmt(p.amount_paid) }}</td>
                                            <td class="px-4 py-3 text-right font-bold">
                                                <span :class="parseFloat(p.balance) > 0 ? 'text-amber-500' : 'text-main-text/30'">
                                                    {{ fmt(p.balance) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full capitalize', statusClasses[p.status] ?? 'bg-gray-400/10 text-gray-400']">
                                                    {{ p.status }}
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- Detail Row (Transactions & Credits) -->
                                        <tr v-if="expandedRows.has(p.id)" class="bg-main-text/[0.01]">
                                            <td colspan="8" class="px-8 py-4 border-l-2 border-l-brand-blue/30">
                                                <div v-if="p.status === 'exempt'" class="text-sm text-main-text/60 italic">
                                                    This obligation was marked as exempt.
                                                </div>
                                                <div v-else-if="(!p.transactions || p.transactions.length === 0) && (!p.credit_applications || p.credit_applications.length === 0)" class="text-sm text-main-text/40 italic">
                                                    No transactions or credits recorded for this period.
                                                </div>
                                                <div v-else class="space-y-4">
                                                    <!-- Transactions -->
                                                    <div v-if="p.transactions && p.transactions.length > 0">
                                                        <h4 class="text-xs font-semibold text-main-text/50 capitalize tracking-widest mb-2 flex items-center gap-1.5">
                                                            <CreditCard class="w-3.5 h-3.5" /> Transaction Log
                                                        </h4>
                                                        <div class="space-y-2">
                                                            <div v-for="t in p.transactions" :key="t.id"
                                                                class="bg-main-bg border border-card-border/60 rounded-lg p-3 flex justify-between items-center text-sm">
                                                                <div class="flex flex-col gap-0.5">
                                                                    <span class="font-medium text-main-text">{{ fmtDate(t.paid_at) }}</span>
                                                                    <div class="flex items-center gap-2 text-xs text-main-text/50 capitalize">
                                                                        <span>{{ t.payment_method.replace('_', ' ') }}</span>
                                                                        <span v-if="t.reference_number" class="px-1.5 py-0.5 rounded bg-main-text/5 text-main-text/60 border border-main-text/10 font-mono text-[10px]">
                                                                            Ref: {{ t.reference_number }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-right">
                                                                    <div class="font-bold text-emerald-500">{{ fmt(t.amount) }} ETB</div>
                                                                    <div v-if="parseFloat(t.fine_paid) > 0" class="text-xs text-rose-400">
                                                                        incl. {{ fmt(t.fine_paid) }} fine
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Credit Applications -->
                                                    <div v-if="p.credit_applications && p.credit_applications.length > 0">
                                                        <h4 class="text-xs font-semibold text-violet-500/80 capitalize tracking-widest mb-2">
                                                            Credit Applied
                                                        </h4>
                                                        <div class="space-y-2">
                                                            <div v-for="c in p.credit_applications" :key="c.id"
                                                                class="bg-violet-500/5 border border-violet-500/20 rounded-lg p-3 flex justify-between items-center text-sm">
                                                                <div class="flex flex-col gap-0.5">
                                                                    <span class="font-medium text-violet-500">{{ fmtDate(c.created_at) }}</span>
                                                                    <span class="text-xs text-violet-500/70">{{ c.credit_source }}</span>
                                                                </div>
                                                                <div class="text-right font-bold text-violet-500">
                                                                    {{ fmt(c.amount_applied) }} ETB
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
                        Close
                    </button>
                </div>
            </div>
    </Modal>
</template>
