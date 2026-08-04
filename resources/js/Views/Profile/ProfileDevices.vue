<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useToastStore } from "@/stores/toast";
import {
 Smartphone,
 Laptop,
 Monitor,
 ShieldCheck,
 LogOut,
 AlertCircle,
 Activity,
 Globe,
 Cpu,
 Zap,
} from "lucide-vue-next";
import { useNotificationStore } from "@/stores/notificationStore";
import SectionCard from "@/components/common/SectionCard.vue";
import Button from "@/components/common/Button.vue";
import ConfirmDialog from "@/components/common/ConfirmDialog.vue";
import StatusBadge from "@/components/common/StatusBadge.vue";

const authStore = useAuthStore();
const toast = useToastStore();
const notificationStore = useNotificationStore();

const sessions = ref<any[]>([]);
const isLoadingSessions = ref(false);
const isLoggingOutSession = ref<number | null>(null);
const isLoggingOutOthers = ref(false);
const showLogoutConfirm = ref(false);
const showLogoutOthersConfirm = ref(false);
const targetSessionId = ref<number | null>(null);

const fetchUserSessions = async () => {
 isLoadingSessions.value = true;
 try {
 const response = await authStore.fetchSessions();
 sessions.value = [...response].sort((a, b) => (b.is_current ? 1 : 0) - (a.is_current ? 1 : 0));
 
 // ⚡ Auto-read security notifications when looking at devices
 autoReadSecurityNotifications();
 } catch (error) {
 toast.error("Failed to fetch sessions");
 } finally {
 isLoadingSessions.value = false;
 }
};

const autoReadSecurityNotifications = () => {
    notificationStore.notifications.forEach(n => {
        if (!n.read && (n.type === 'security' || n.title_key?.includes('security') || n.message_key?.includes('device'))) {
            notificationStore.markAsRead(n.id);
        }
    });
};

const handleLogoutSession = (sessionId: number) => {
 targetSessionId.value = sessionId;
 showLogoutConfirm.value = true;
};

const confirmLogoutSession = async () => {
 if (!targetSessionId.value) return;

 isLoggingOutSession.value = targetSessionId.value;
 try {
 await authStore.logoutSession(targetSessionId.value);
 showLogoutConfirm.value = false;
 await fetchUserSessions();
 } catch (error: any) {
 toast.error(
 error.response?.data?.message || "Failed to terminate session",
 );
 } finally {
 isLoggingOutSession.value = null;
 targetSessionId.value = null;
 }
};

const handleLogoutOtherSessions = () => {
 showLogoutOthersConfirm.value = true;
};

const confirmLogoutOtherSessions = async () => {
 isLoggingOutOthers.value = true;
 try {
 await authStore.logoutAllOtherSessions();
 showLogoutOthersConfirm.value = false;
 await fetchUserSessions();
 } catch (error: any) {
 toast.error(
 error.response?.data?.message ||
 "Failed to terminate other sessions",
 );
 } finally {
 isLoggingOutOthers.value = false;
 }
};

// Real-time listener for last seen updates via store
watch(
 () => notificationStore.lastSessionUpdate,
 (session: any) => {
 if (!session) return;
 const index = sessions.value.findIndex(
 (s) => s.session_id === session.session_id,
 );
 if (index !== -1) {
 sessions.value[index] = {
 ...sessions.value[index],
 last_active_at: session.last_active_at,
 last_active_at_human: session.last_active_at_human,
 ip_address: session.ip_address,
 location: session.location,
 };
 } else {
 fetchUserSessions();
 }
 },
);

// Auto-read security notifications if they arrive while we are on this page
watch(
    () => notificationStore.notifications.length,
    () => autoReadSecurityNotifications()
);

onMounted(() => {
 fetchUserSessions();
});
</script>

<template>
 <div class="space-y-8 pb-12 font-sans relative">
 <SectionCard
 :title="$tr('profile.active_sessions')"
 :description="$tr('profile.manage_devices_desc') || 'Monitor and manage the devices currently logged into your account.'"
 :icon="Smartphone"
 no-padding>
 <template #action>
 <Button
 v-if="sessions.filter((s) => !s.is_current).length > 0"
 @click="handleLogoutOtherSessions"
 variant="soft-danger"
 size="sm"
 :loading="isLoggingOutOthers"
 class="px-5! rounded-lg! capitalize font-normal text-[10px]">
 <LogOut class="w-3.5 h-3.5 mr-2" />
 {{ $tr("profile.sign_out_others") }}
 </Button>
 </template>

 <!-- Loading State -->
 <div
 v-if="isLoadingSessions"
 class="py-12 flex flex-col items-center justify-center gap-4">
 <p class="text-[11px] font-normal text-main-text/40 capitalize">
 Loading sessions...
 </p>
 </div>

 <div v-else-if="sessions.length > 0" class="divide-y divide-card-border">
 <div
 v-for="session in sessions"
 :key="session.id"
 class="p-5 md:p-6 hover:bg-main-text/2 transition-colors group">
 
 <div class="flex flex-col lg:flex-row lg:items-center gap-6">
 <!-- Icon Stage -->
 <div class="w-12 h-12 shrink-0 rounded-xl bg-main-text/3 border border-card-border flex items-center justify-center">
 <Laptop
 v-if="session.device_type === 'desktop' || session.device_type === 'laptop'"
 class="w-5 h-5 text-main-text/20" />
 <Smartphone
 v-else-if="['mobile', 'phone', 'tablet'].includes(session.device_type)"
 class="w-5 h-5 text-main-text/20" />
 <Monitor v-else class="w-5 h-5 text-main-text/20" />
 </div>

 <!-- Data Stage -->
 <div class="flex-1 min-w-0 space-y-4">
 <div class="flex flex-wrap items-center gap-3">
 <h3 class="text-base font-normal text-main-text tracking-tight capitalize">
 {{ session.device_name || "Nexus device" }}
 </h3>
 <div class="flex items-center gap-2">
 <span
 v-if="session.is_current"
 class="px-2 py-0.5 bg-emerald-500 text-white text-[9px] font-normal capitalize tracking-wide rounded">
 Current
 </span>
 <span
 v-if="session.is_protected"
 class="px-2 py-0.5 bg-brand-blue/10 text-brand-blue text-[9px] font-normal capitalize tracking-wide rounded border border-brand-blue/20">
 Verified
 </span>
 </div>
 </div>

 <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-3">
 <div class="space-y-0.5">
 <p class="text-[9px] font-normal text-main-text/30 capitalize tracking-wide">{{ $tr('profile.location', 'Location') }}</p>
 <p class="text-[13px] font-normal text-main-text/70 truncate capitalize">
 {{ session.location || "Unknown" }}
 </p>
 </div>
 <div class="space-y-0.5">
 <p class="text-[9px] font-normal text-main-text/30 capitalize tracking-wide">{{ $tr('profile.platform', 'Platform') }}</p>
 <p class="text-[13px] font-normal text-main-text/70 truncate capitalize">
 {{ session.platform || "Unknown" }}
 </p>
 </div>
 <div class="space-y-0.5">
 <p class="text-[9px] font-normal text-main-text/30 capitalize tracking-wide">{{ $tr('profile.ipAddress', 'Ip address') }}</p>
 <p class="text-[13px] font-mono font-normal text-main-text/70">
 {{ session.ip_address }}
 </p>
 </div>
 <div class="space-y-0.5">
 <p class="text-[9px] font-normal text-main-text/30 capitalize tracking-wide">{{ $tr('profile.activity', 'Activity') }}</p>
 <p class="text-[13px] font-normal text-main-text/70 truncate capitalize">
 {{ session.last_active_at_human }}
 </p>
 </div>
 </div>
 </div>

 <!-- Action Stage -->
 <div class="shrink-0">
 <Button
 v-if="!session.is_current"
 @click="handleLogoutSession(session.id)"
 :loading="isLoggingOutSession === session.id"
 variant="soft-danger"
 size="sm"
 class="h-9! px-5 rounded-lg!">
 <template #icon>
 <LogOut class="w-3.5 h-3.5" />
 </template>
 <span class="capitalize">{{ $tr('profile.logout', 'Logout') }}</span>
 </Button>
 </div>
 </div>
 </div>
 </div>

 <div v-else class="py-20 text-center space-y-4">
 <div class="w-16 h-16 rounded-3xl bg-main-bg/5 flex items-center justify-center mx-auto border-2 border-dashed border-card-border/40">
 <Smartphone class="w-6 h-6 text-main-text/10" />
 </div>
 <p class="text-[10px] font-normal text-main-text/20 capitalize tracking-widest">
 {{ $tr("profile.no_other_sessions") }}
 </p>
 </div>
 </SectionCard>

 <!-- Validation Modals -->
 <ConfirmDialog
 :show="showLogoutConfirm"
 :title="$tr('profile.sign_out_device')"
 :message="$tr('profile.sign_out_confirm')"
 :confirm-text="$tr('common.confirm')"
 variant="danger"
 :loading="isLoggingOutSession !== null"
 @close="showLogoutConfirm = false"
 @confirm="confirmLogoutSession" />

 <ConfirmDialog
 :show="showLogoutOthersConfirm"
 :title="$tr('profile.sign_out_others')"
 :message="$tr('profile.sign_out_others_desc')"
 :confirm-text="$tr('common.confirm')"
 variant="danger"
 :loading="isLoggingOutOthers"
 @close="showLogoutOthersConfirm = false"
 @confirm="confirmLogoutOtherSessions" />
 </div>
</template>
