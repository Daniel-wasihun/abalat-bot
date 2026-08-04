<script setup lang="ts">
import {
    Users,
    Bookmark,
    Clock,
    BookOpen,
    TrendingUp,
    DollarSign,
    Library,
    AlertTriangle,
    ArrowUpRight,
    ArrowDownRight,
    Minus,
} from "lucide-vue-next";
import { useDashboardStore } from "@/stores/dashboardStore";
import { computed, getCurrentInstance } from "vue";

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const dashboardStore = useDashboardStore();

const metrics = computed(() => {
    if (!dashboardStore.stats) return [];
    const o = dashboardStore.stats.overview;

    return [
        {
            key: "total_users",
            label: "dashboard.metrics.active_users",
            value: o.total_users?.toLocaleString(),
            subVal: `+${o.today_new_users}`,
            subKey: "common.today",
            trend: o.user_trend,
            icon: Users,
            color: "text-accent",
            bg: "bg-accent/10 border-accent/20",
        },
        {
            key: "total_books",
            label: "dashboard.metrics.total_books",
            value: o.total_books?.toLocaleString(),
            subVal:
                dashboardStore.stats.collection?.availability?.available ?? 0,
            subKey: "dashboard.metrics.available",
            trend: null,
            icon: BookOpen,
            color: "text-brand-green",
            bg: "bg-brand-green/10 border-brand-green/20",
        },
        {
            key: "active_loans",
            label: "dashboard.metrics.active_loans",
            value: o.active_loans?.toLocaleString(),
            subVal: o.today_borrows,
            subKey: "dashboard.metrics.new_today",
            trend: o.borrow_trend,
            icon: Bookmark,
            color: "text-brand-yellow",
            bg: "bg-brand-yellow/10 border-brand-yellow/20",
        },
        {
            key: "overdue_loans",
            label: "dashboard.metrics.overdue_items",
            value: o.overdue_loans?.toLocaleString(),
            subVal: `${o.overdue_rate}%`,
            subKey: "dashboard.overdue_rate",
            trend: null,
            icon: AlertTriangle,
            color: o.overdue_loans > 0 ? "text-brand-red" : "text-brand-green",
            bg:
                o.overdue_loans > 0
                    ? "bg-brand-red/10 border-brand-red/20"
                    : "bg-brand-green/10 border-brand-green/20",
        },
        {
            key: "total_fine_revenue",
            label: "dashboard.metrics.revenue",
            value:
                o.total_fine_revenue !== undefined
                    ? `${o.total_fine_revenue.toLocaleString()} ETB`
                    : undefined,
            subVal:
                o.pending_fines !== undefined
                    ? `${o.pending_fines.toLocaleString()} ETB`
                    : undefined,
            subKey: "dashboard.pending",
            trend: null,
            icon: DollarSign,
            color: "text-brand-yellow",
            bg: "bg-brand-yellow/10 border-brand-yellow/20",
        },
        {
            key: "active_spot_readings",
            label: "dashboard.metrics.spot_reading",
            value: o.active_spot_readings?.toLocaleString(),
            subVal: o.today_returns,
            subKey: "dashboard.returned_today",
            trend: null,
            icon: Library,
            color: "text-accent",
            bg: "bg-accent/10 border-accent/20",
        },
    ].filter((m) => m.value !== undefined);
});
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <template v-if="metrics.length > 0">
            <div
                v-for="metric in metrics"
                :key="metric.label"
                class="group premium-card p-4 flex flex-col gap-3 relative overflow-hidden cursor-pointer transition-all duration-300 hover:border-accent/30">
                <!-- Icon -->
                <div
                    :class="[
                        'w-10 h-10 rounded-xl flex items-center justify-center border transition-all duration-300',
                        metric.bg,
                        metric.color,
                    ]">
                    <component
                        :is="metric.icon"
                        class="w-5 h-5"
                        :stroke-width="1.5" />
                </div>

                <!-- Value -->
                <div>
                    <div class="flex items-baseline gap-2">
                        <div
                            v-if="metric.trend"
                            class="flex items-center gap-0.5"
                            :class="[
                                metric.trend.status === 'up'
                                    ? 'text-brand-green'
                                    : metric.trend.status === 'down'
                                      ? 'text-brand-red'
                                      : 'text-main-text/20',
                            ]">
                            <ArrowUpRight
                                v-if="metric.trend.status === 'up'"
                                class="w-3 h-3" />
                            <ArrowDownRight
                                v-if="metric.trend.status === 'down'"
                                class="w-3 h-3" />
                            <Minus
                                v-if="metric.trend.status === 'neutral'"
                                class="w-3 h-3" />
                            <span class="text-xs font-semibold"
                                >{{ metric.trend.value }}%</span
                            >
                        </div>
                    </div>
                    <span
                        class="text-xs font-medium capitalize tracking-widest text-main-text/40">
                        {{ $tr(metric.label) }}
                    </span>
                </div>

                <!-- Sub info -->
                <p class="text-xs text-main-text/30 font-normal mt-auto">
                    {{ metric.subVal }} {{ $tr(metric.subKey) }}
                </p>
            </div>
        </template>

        <!-- Skeleton Loading State -->
        <template v-else>
            <div
                v-for="i in 6"
                :key="i"
                class="premium-card p-4 flex flex-col gap-3 animate-pulse">
                <div
                    class="w-10 h-10 rounded-xl bg-main-text/5 border border-card-border"></div>
                <div>
                    <div class="h-6 w-16 bg-main-text/10 rounded mb-2"></div>
                    <div class="h-3 w-24 bg-main-text/5 rounded"></div>
                </div>
                <div class="h-3 w-20 bg-main-text/5 rounded mt-auto"></div>
            </div>
        </template>
    </div>
</template>
