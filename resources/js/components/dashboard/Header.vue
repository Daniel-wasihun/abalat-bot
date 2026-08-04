<script setup lang="ts">
import {
 ref,
 onMounted,
 computed,
 watch,
 onUnmounted,
 getCurrentInstance,
} from "vue";
import { useRouter, useRoute } from "vue-router";
import {
 Bell,
 Search,
 Settings,
 MoreHorizontal,
 LogOut,
 PanelLeftClose,
 PanelLeftOpen,
 ShieldAlert,
 Info,
 CheckCircle,
 UserCircle,
 Pencil,
 Shield,
 Monitor,
 Loader2,
 X,
 Trash2,
 RotateCcw,
} from "lucide-vue-next";
import { useAuthStore } from "@/stores/authStore";
import { useNotificationStore } from "@/stores/notificationStore";
import { useLanguageStore } from "@/stores/languageStore";
import LanguageSwitcher from "@/components/navigation/LanguageSwitcher.vue";
import ThemeToggle from "@/components/navigation/ThemeToggle.vue";

const props = defineProps<{
 isCollapsed: boolean;
 isMobile: boolean;
}>();

const emit = defineEmits(["toggle"]);

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const authStore = useAuthStore();
const notificationStore = useNotificationStore();
const languageStore = useLanguageStore();
const route = useRoute();
const router = useRouter();

const isProfileMenuOpen = ref(false);
const isMobileUtilsOpen = ref(false);
const isNotificationsOpen = ref(false);
const isQuickSearchOpen = ref(false);

const activeTab = ref<"active" | "trash">("active");

const profileMenuRef = ref<HTMLElement | null>(null);
const mobileUtilsRef = ref<HTMLElement | null>(null);
const notificationsRef = ref<HTMLElement | null>(null);

const closeMenus = (e: MouseEvent) => {
 const target = e.target as HTMLElement;

 if (
 isProfileMenuOpen.value &&
 profileMenuRef.value &&
 !profileMenuRef.value.contains(target)
 ) {
 isProfileMenuOpen.value = false;
 }
 if (
 isMobileUtilsOpen.value &&
 mobileUtilsRef.value &&
 !mobileUtilsRef.value.contains(target)
 ) {
 isMobileUtilsOpen.value = false;
 }
 if (
 isNotificationsOpen.value &&
 notificationsRef.value &&
 !notificationsRef.value.contains(target)
 ) {
 isNotificationsOpen.value = false;
 }
};

const switchTab = (tab: "active" | "trash") => {
 activeTab.value = tab;
 if (tab === "trash") {
 notificationStore.fetchTrashedNotifications();
 } else {
 notificationStore.fetchNotifications();
 }
};

const formatMessage = (msg: string) => {
 if (!msg) return "";
 const linkText = languageStore.translate("profile.devices");
 return msg.replace(
 /@@Devices Tab@@/g,
 `<span class="text-accent font-bold hover:underline cursor-pointer decoration-2" data-route="/dashboard/profile/devices">${linkText}</span>`,
 );
};

const handleNotificationClick = (e: MouseEvent, notif: any) => {
 const target = e.target as HTMLElement;
 const routeLink = target.getAttribute("data-route");
 if (routeLink) {
 e.stopPropagation(); // Avoid triggering the parent click (expand) if it's the link
 router.push(routeLink);
 isNotificationsOpen.value = false;
 } else {
 notificationStore.toggleExpand(notif.id);
 }
};

const getNotifTitle = (notif: any) => {
 if (notif.title_key) {
 return languageStore.translate(notif.title_key, notif.params);
 }
 return notif.title;
};

const getNotifMessage = (notif: any) => {
 if (notif.message_key) {
 return languageStore.translate(notif.message_key, notif.params);
 }
 return notif.message;
};

onMounted(() => {
 window.addEventListener("click", closeMenus);
 window.addEventListener("keydown", handleGlobalKeyDown);
 notificationStore.fetchNotifications();
 notificationStore.setupNotificationListener();
});
onUnmounted(() => {
 window.removeEventListener("click", closeMenus);
 window.removeEventListener("keydown", handleGlobalKeyDown);
});

const handleGlobalKeyDown = (e: KeyboardEvent) => {
 if ((e.ctrlKey || e.metaKey) && e.key === "k") {
 e.preventDefault();
 isQuickSearchOpen.value = true;
 }
};

// Localized function helper
const localize = (val: any) => {
 if (!val) return "";
 return typeof val === "object"
 ? val[languageStore.currentLanguage] || val["en"] || ""
 : val;
};

watch(isNotificationsOpen, (newVal) => {
 if (newVal) {
 notificationStore.expandedId = null;
 }
});

const userInitial = computed(() => {
 const rawName = authStore.user?.name;
 const name =
 typeof rawName === "object"
 ? rawName[languageStore.currentLanguage] || rawName["en"] || ""
 : rawName || "";
 const parts = name.trim().split(/\s+/) || [];
 if (parts.length >= 2) {
 return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
 }
 return name.charAt(0).toUpperCase() || "U";
});

const isLoggingOut = ref(false);
const showLogoutConfirm = ref(false);

const handleLogout = () => {
 isProfileMenuOpen.value = false;
 showLogoutConfirm.value = true;
};

const confirmLogout = async () => {
 isLoggingOut.value = true;
 try {
 await authStore.logout();
 } catch (error) {
 isLoggingOut.value = false;
 showLogoutConfirm.value = false;
 }
};

const getProfileImage = (
 path: string | null | undefined,
): string | undefined => {
 if (!path) return undefined;
 if (path.startsWith("http")) return path;
 const baseUrl = import.meta.env.VITE_STORAGE_URL || "/storage";
 return `${baseUrl}/${path}`;
};

const showEmptyTrashConfirm = ref(false);
const isCleaningTrash = ref(false);

const handleEmptyTrash = () => {
 showEmptyTrashConfirm.value = true;
};

const confirmEmptyTrash = async () => {
 isCleaningTrash.value = true;
 try {
 await notificationStore.emptyTrash();
 showEmptyTrashConfirm.value = false;
 } finally {
 isCleaningTrash.value = false;
 }
};
</script>

<template>
 <header
 class="sticky top-0 z-100 h-16 md:h-14 bg-transparent flex items-center justify-between px-4 md:px-8">
 <!-- Left Side: Logo (Mobile) & Page Info (Breadcrumbs) -->
 <div class="flex items-center gap-3 md:gap-6">
 <img
 v-if="isMobile"
 src="/logo.webp"
 alt="Logo"
 @click="router.push('/')"
 class="w-16 h-16 object-contain cursor-pointer active:scale-95 transition-transform" />

 <button
 v-if="!isMobile"
 @click="emit('toggle')"
 class="relative flex items-center justify-center cursor-pointer rounded-xl hover:text-nav-accent transition-all duration-300 hover:bg-card-hover active:scale-95 group/toggler p-2 md:-ml-5"
 :aria-label="isCollapsed ? $tr('nav.expand_sidebar') : $tr('nav.collapse_sidebar')">
 <PanelLeftOpen
 v-if="isCollapsed"
 class="w-8 h-8 md:w-10 md:h-8" />
 <PanelLeftClose v-else class="w-8 h-8" />
 </button>

 <div
 v-if="!authStore.user"
 class="w-full md:w-72 lg:w-96 xl:w-[500px] h-11 rounded-xl skeleton opacity-20"></div>
 <div
 v-else
 @click="isQuickSearchOpen = true"
 class="relative group flex-1 md:flex-none cursor-pointer">
 <div
 class="relative w-full md:w-72 lg:w-96 xl:w-[500px] h-11 pl-12 pr-4 rounded-xl border border-card-border bg-main-bg/5 flex items-center justify-between text-sm text-main-text/60 hover:bg-card-bg/50 hover:border-nav-accent hover:ring-4 hover:ring-nav-accent/5 backdrop-blur-sm transition-all duration-300">
 <Search
 class="absolute left-4 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-nav-accent/80 group-hover:text-nav-accent transition-colors z-10"
 :stroke-width="2.5" />
 <span class="font-normal">
 {{
 isMobile
 ? $tr("common.search") || "Search..."
 : $tr("common.search_placeholder") ||
 "Search anything..."
 }}
 </span>
 <div
 v-if="!isMobile"
 class="flex items-center gap-1 opacity-20 group-hover:opacity-100 transition-opacity">
 <span
 class="flex items-center justify-center h-5 min-w-[20px] px-1 rounded-md bg-main-bg/20 text-[10px] font-bold"
 >CTRL</span
 >
 <span
 class="flex items-center justify-center h-5 min-w-[20px] px-1 rounded-md bg-main-bg/20 text-[10px] font-bold"
 >K</span
 >
 </div>
 </div>
 </div>
 </div>

 <!-- Right Side Actions -->
 <div class="flex items-center gap-4">
 <div class="flex items-center gap-2 md:gap-4">
 <div v-if="isMobile" ref="mobileUtilsRef" class="relative">
 <button
 @click="isMobileUtilsOpen = !isMobileUtilsOpen"
 class="p-2.5 rounded-xl transition-all duration-300 hover:bg-nav-accent/10 text-main-text group cursor-pointer border border-transparent hover:border-nav-accent/20"
 :class="{
 'bg-nav-accent/10 border-nav-accent/20':
 isMobileUtilsOpen,
 }"
 aria-label="More actions">
 <MoreHorizontal class="w-5 h-5 text-main-text" />
 </button>

 <!-- Mobile Utils (no animation) -->
 <div
 v-if="isMobileUtilsOpen"
 class="absolute right-0 mt-2 w-64 bg-card-bg rounded-xl shadow-2xl border border-card-border p-3 z-110 backdrop-blur-xl">
 <div class="flex flex-col gap-3">
 <div
 class="flex items-center justify-between px-2 pb-2 border-b border-card-border/50 mb-1">
 <span
 class="text-xs font-medium text-main-text/40 tracking-wider capitalize"
 >{{ $tr("app.system_settings") }}</span
 >
 </div>
 <button
 @click="
 isQuickSearchOpen = true;
 isMobileUtilsOpen = false;
 "
 class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-main-text hover:bg-nav-accent/5 rounded-xl transition-all border border-transparent hover:border-nav-accent/10 group">
 <Search class="w-5 h-5 text-nav-accent" />
 <span>{{ $tr("app.quick_search") }}</span>
 </button>
 <div class="relative"><LanguageSwitcher /></div>
 <div
 class="flex items-center justify-between px-3 py-2 bg-main-bg/5 rounded-xl border border-card-border/40">
 <span
 class="text-sm font-medium text-main-text"
 >{{ $tr("app.theme_appearance") }}</span
 >
 <ThemeToggle />
 </div>
 </div>
 </div>
 </div>
 <div v-else class="flex items-center gap-3">
 <LanguageSwitcher />
 <ThemeToggle />
 </div>

 <div ref="notificationsRef" class="relative">
 <button
 @click="isNotificationsOpen = !isNotificationsOpen"
 class="relative p-2.5 rounded-xl transition-all duration-300 hover:bg-nav-accent/10 text-main-text group cursor-pointer overflow-hidden border border-transparent hover:border-nav-accent/20"
 :class="{
 'bg-nav-accent/10 border-nav-accent/20':
 isNotificationsOpen,
 }"
 aria-label="Notifications">
 <Bell class="w-5 h-5 text-nav-accent" />
 <span
 v-if="notificationStore.unreadCount > 0"
 class="absolute top-1.5 right-1.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-nav-accent px-1 text-xs font-medium text-white ring-2 ring-card-bg">
 {{
 notificationStore.unreadCount > 99
 ? "99+"
 : notificationStore.unreadCount
 }}
 </span>
 </button>

 <!-- Notifications Panel (no animation) -->
 <div
 v-if="isNotificationsOpen"
 class="absolute -right-16 md:right-0 mt-3 w-[calc(100vw-2rem)] md:w-[480px] max-h-[80vh] md:max-h-[600px] flex flex-col bg-card-bg rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-card-border z-110 overflow-hidden">
 <!-- Header -->
 <div
 class="px-5 py-4 border-b border-card-border flex items-center justify-between bg-main-bg/5">
 <div class="flex items-center gap-2">
 <button
 @click="switchTab('active')"
 class="px-4 py-1.5 rounded-xl text-xs font-medium transition-all relative"
 :class="
 activeTab === 'active'
 ? 'bg-nav-accent text-white shadow-lg shadow-nav-accent/20'
 : 'text-main-text/40 hover:bg-main-bg/5 hover:text-main-text'
 ">
 {{ $tr("notifications.active") }}
 <span
 v-if="
 notificationStore.unreadCount > 0 &&
 activeTab !== 'active'
 "
 class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-nav-accent border-2 border-card-bg"></span>
 </button>
 <button
 @click="switchTab('trash')"
 class="px-4 py-1.5 rounded-xl text-xs font-medium transition-all flex items-center gap-1.5"
 :class="
 activeTab === 'trash'
 ? 'bg-brand-red text-white shadow-lg shadow-brand-red/20'
 : 'text-main-text/40 hover:bg-main-bg/5 hover:text-main-text'
 ">
 {{ $tr("notifications.trash") }}
 <span
 v-if="
 notificationStore
 .trashedNotifications.length > 0
 "
 class="w-1.5 h-1.5 rounded-full bg-brand-red"></span>
 </button>
 </div>
 <button
 v-if="
 activeTab === 'active' &&
 notificationStore.unreadCount > 0
 "
 @click="notificationStore.markAllAsRead"
 class="text-xs font-medium text-nav-accent hover:text-nav-accent/80 transition-all cursor-pointer p-1">
 {{ $tr("notifications.mark_all_read") }}
 </button>
 </div>

 <!-- Body -->
 <div
 class="flex-1 overflow-y-auto py-2 custom-scrollbar">
 <div
 v-if="
 (activeTab === 'active'
 ? notificationStore.notifications
 : notificationStore.trashedNotifications
 ).length === 0
 "
 class="py-16 flex flex-col items-center justify-center text-center px-10">
 <div
 class="w-16 h-16 rounded-3xl bg-main-bg/5 flex items-center justify-center text-main-text/10 mb-5 transition-none group-hover:rotate-0">
 <Bell
 v-if="activeTab === 'active'"
 class="w-8 h-8" />
 <Trash2 v-else class="w-8 h-8" />
 </div>
 <h4
 class="text-sm font-medium text-main-text mb-1">
 {{
 activeTab === "active"
 ? $tr("notifications.end_of_line")
 : $tr(
 "notifications.trash_empty_title",
 )
 }}
 </h4>
 <p class="text-xs text-main-text/40">
 {{
 activeTab === "active"
 ? $tr(
 "notifications.no_notifications",
 )
 : $tr(
 "notifications.trash_empty_desc",
 )
 }}
 </p>
 </div>
 <div v-else class="px-2 space-y-1">
 <div
 v-for="notif in activeTab === 'active'
 ? notificationStore.notifications
 : notificationStore.trashedNotifications"
 :key="notif.id"
 @click.stop="
 handleNotificationClick($event, notif)
 "
 class="py-2.5 px-3 rounded-xl hover:bg-main-bg/5 cursor-pointer relative group border-l-[3px]"
 :class="[
 notif.read
 ? 'border-transparent'
 : 'border-nav-accent bg-nav-accent/5',
 notificationStore.expandedId ===
 notif.id
 ? 'bg-main-bg/5 border-l-nav-accent ring-1 ring-main-bg/5'
 : '',
 ]">
 <div class="flex gap-4">
 <div
 class="shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center relative overflow-hidden"
 :class="{
 'bg-brand-red/10 text-brand-red':
 notif.type === 'security' ||
 notif.type === 'error',
 'bg-brand-yellow/10 text-brand-yellow':
 notif.type === 'warning',
 'bg-brand-green/10 text-brand-green':
 notif.type === 'success',
 'bg-nav-accent/10 text-nav-accent':
 notif.type === 'info',
 }">
 <ShieldAlert
 v-if="notif.type === 'security'"
 class="w-5 h-5 relative z-10" />
 <CheckCircle
 v-else-if="
 notif.type === 'success'
 "
 class="w-5 h-5 relative z-10" />
 <Info
 v-else
 class="w-5 h-5 relative z-10" />
 </div>
 <div class="flex-1 min-w-0">
 <div
 class="flex items-start justify-between gap-2 mb-1">
 <p
 class="text-[16px] font-bold text-main-text leading-tight pr-2"
 :class="{
 'opacity-50':
 notif.read,
 }">
 {{ getNotifTitle(notif) }}
 </p>
 <div
 class="flex items-center gap-1 opacity-0 group-hover:opacity-100">
 <template
 v-if="
 activeTab ===
 'active'
 ">
 <button
 v-if="!notif.read"
 @click.stop="
 notificationStore.markAsRead(
 notif.id,
 )
 "
 class="p-1.5 hover:bg-nav-accent/10 rounded-xl text-nav-accent transition-colors"
 title="Mark as read">
 <CheckCircle
 class="w-4 h-4" />
 </button>
 <button
 @click.stop="
 notificationStore.trashNotification(
 notif.id,
 )
 "
 class="p-1.5 hover:bg-brand-red/10 rounded-xl text-main-text/30 hover:text-brand-red transition-colors"
 title="Move to trash">
 <Trash2
 class="w-4 h-4" />
 </button>
 </template>
 <template v-else>
 <button
 @click.stop="
 notificationStore.restoreNotification(
 notif.id,
 )
 "
 class="p-1.5 hover:bg-brand-green/10 rounded-xl text-brand-green transition-colors"
 title="Restore">
 <RotateCcw
 class="w-4 h-4" />
 </button>
 <button
 @click.stop="
 notificationStore.deletePermanently(
 notif.id,
 )
 "
 class="p-1.5 hover:bg-brand-red/10 rounded-xl text-brand-red transition-colors"
 title="Delete permanently">
 <X
 class="w-4 h-4" />
 </button>
 </template>
 </div>
 </div>
 <div
 class="flex items-center justify-between gap-4 mt-0.5">
 <p
 v-if="
 notificationStore.expandedId ===
 notif.id
 "
 class="text-sm text-main-text/80 leading-relaxed whitespace-normal"
 :class="{
 'opacity-50':
 notif.read,
 }"
 v-html="
 formatMessage(
 getNotifMessage(
 notif,
 ),
 )
 "></p>
 <p
 v-else
 class="text-sm text-main-text/60 leading-none truncate flex-1"
 :class="{
 'opacity-40':
 notif.read,
 }">
 {{ getNotifMessage(notif) }}
 </p>

 <button
 class="shrink-0 text-sm font-bold capitalize tracking-wider text-nav-accent/60 hover:text-nav-accent cursor-pointer"
 @click.stop="
 notificationStore.toggleExpand(
 notif.id,
 )
 ">
 {{
 notificationStore.expandedId ===
 notif.id
 ? $tr("common.less")
 : $tr(
 "common.detail",
 )
 }}
 </button>
 </div>
 <!-- Timestamp remains compact -->
 <div
 class="flex items-center gap-2 mt-1.5">
 <span
 class="text-sm text-main-text/40 font-medium tracking-wide">
 {{
 new Date(
 notif.time,
 ).toLocaleString([], {
 year: "numeric",
 month: "short",
 day: "numeric",
 hour: "2-digit",
 minute: "2-digit",
 hour12: true,
 })
 }}
 </span>
 <span
 v-if="!notif.read"
 class="w-1.5 h-1.5 rounded-full bg-nav-accent"></span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <div
 class="p-3 border-t border-card-border bg-main-bg/5 flex gap-2">
 <button
 @click="notificationStore.trashAll"
 class="flex-1 py-2.5 text-sm font-medium capitalize tracking-widest text-main-text/40 hover:text-brand-yellow hover:bg-brand-yellow/5 rounded-xl transition-all border border-transparent hover:border-brand-yellow/10">
 {{ $tr("notifications.clear_history") }}
 </button>
 </div>
 </div>
 </div>

 <div v-if="!authStore.user" class="flex items-center gap-4">
 <div class="skeleton w-10 h-10 rounded-xl opacity-20"></div>
 <div
 class="skeleton w-10 h-10 md:w-11 md:h-11 rounded-full opacity-30"></div>
 </div>

 <div v-else ref="profileMenuRef" class="relative">
 <button
 @click="isProfileMenuOpen = !isProfileMenuOpen"
 class="flex items-center p-1 rounded-full cursor-pointer">
 <div
 class="w-10 h-10 md:w-11 md:h-11 rounded-full overflow-hidden border border-card-border bg-nav-accent/5 flex items-center justify-center shrink-0">
 <img
 v-if="authStore.user?.info?.profile_picture"
 :src="
 getProfileImage(
 authStore.user.info.profile_picture,
 )
 "
 class="w-full h-full object-cover" />
 <span
 v-else
 class="text-base font-normal text-nav-accent tracking-tighter"
 >{{ userInitial }}</span
 >
 </div>
 </button>

 <div
 v-if="isProfileMenuOpen"
 class="absolute right-0 mt-3 w-64 bg-card-bg rounded-2xl shadow-2xl border border-card-border overflow-hidden p-2 z-110 backdrop-blur-xl">
 <div
 class="px-4 py-3 border-b border-card-border/50 mb-2">
 <p
 class="text-sm font-medium text-main-text truncate">
 {{ localize(authStore.user?.name) }}
 </p>
 <p
 class="text-xs font-medium capitalize tracking-widest text-main-text/30 mt-0.5">
 {{ localize(authStore.user?.role?.name) }}
 </p>
 </div>

 <button
 @click="
 router.push('/dashboard/profile/overview');
 isProfileMenuOpen = false;
 "
 class="flex items-center gap-3 w-full px-4 py-3 text-sm font-normal text-main-text hover:bg-nav-accent/5 hover:text-nav-accent rounded-xl transition-all group">
 <UserCircle
 class="w-4.5 h-4.5 text-main-text/30 group-hover:text-nav-accent transition-colors" />
 <span>{{ $tr("profile.overview") }}</span>
 </button>

 <button
 @click="
 router.push('/dashboard/profile/edit');
 isProfileMenuOpen = false;
 "
 class="flex items-center gap-3 w-full px-4 py-3 text-sm font-normal text-main-text hover:bg-nav-accent/5 hover:text-nav-accent rounded-xl transition-all group">
 <Pencil
 class="w-4.5 h-4.5 text-main-text/30 group-hover:text-nav-accent transition-colors" />
 <span>{{ $tr("profile.edit") }}</span>
 </button>

 <button
 @click="
 router.push('/dashboard/profile/security');
 isProfileMenuOpen = false;
 "
 class="flex items-center gap-3 w-full px-4 py-3 text-sm font-normal text-main-text hover:bg-nav-accent/5 hover:text-nav-accent rounded-xl transition-all group">
 <Shield
 class="w-4.5 h-4.5 text-main-text/30 group-hover:text-nav-accent transition-colors" />
 <span>{{ $tr("profile.security") }}</span>
 </button>

 <button
 @click="
 router.push('/dashboard/profile/devices');
 isProfileMenuOpen = false;
 "
 class="flex items-center gap-3 w-full px-4 py-3 text-sm font-normal text-main-text hover:bg-nav-accent/5 hover:text-nav-accent rounded-xl transition-all group">
 <Monitor
 class="w-4.5 h-4.5 text-main-text/30 group-hover:text-nav-accent transition-colors" />
 <span>{{ $tr("profile.active_sessions") }}</span>
 </button>

 <div class="h-px bg-card-border/50 my-2 mx-2"></div>

 <button
 @click="handleLogout"
 class="flex items-center gap-3 w-full px-4 py-3 text-sm font-normal text-brand-red hover:bg-brand-red/5 rounded-xl transition-all group">
 <LogOut
 class="w-4.5 h-4.5 transition-transform group-hover:-translate-x-1" />
 <span>{{ $tr("logout") }}</span>
 </button>
 </div>
 </div>
 </div>
 </div>

 <!-- Logout Confirmation Modal -->
 <Teleport to="body">
 <transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0"
 enter-to-class="opacity-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100"
 leave-to-class="opacity-0">
 <div
 v-if="showLogoutConfirm"
 @click.self="showLogoutConfirm = false"
 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
 <div
 class="bg-card-bg rounded-xl shadow-2xl w-full max-w-md p-6 border border-card-border animate-in zoom-in-95 duration-200">
 <h3 class="text-lg font-normal text-main-text mb-2">
 {{ $tr("logout") }}
 </h3>
 <p class="text-main-text/60 mb-6 font-normal">
 {{ $tr("auth.logout_confirmation") }}
 </p>
 <div class="flex items-center justify-end gap-3">
 <button
 @click="showLogoutConfirm = false"
 class="px-5 py-2.5 text-sm font-bold text-main-text/60 hover:text-main-text rounded-xl transition-all border border-transparent hover:border-card-border"
 :disabled="isLoggingOut">
 {{ $tr("common.cancel") }}
 </button>
 <button
 @click="confirmLogout"
 class="px-6 py-2.5 text-sm font-bold text-white bg-brand-red hover:bg-brand-red/90 rounded-xl flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-brand-red/20"
 :disabled="isLoggingOut">
 <Loader2
 v-if="isLoggingOut"
 class="w-4 h-4 animate-spin text-white" />
 <span>{{
 isLoggingOut
 ? $tr("logging_out")
 : $tr("logout")
 }}</span>
 </button>
 </div>
 </div>
 </div>
 </transition>
 </Teleport>

 <!-- Empty Trash Confirmation Modal -->
 <Teleport to="body">
 <transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0"
 enter-to-class="opacity-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100"
 leave-to-class="opacity-0">
 <div
 v-if="showEmptyTrashConfirm"
 @click.self="showEmptyTrashConfirm = false"
 class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
 <div
 class="bg-card-bg rounded-xl shadow-2xl w-full max-w-md p-6 border border-card-border animate-in zoom-in-95 duration-200">
 <h3 class="text-sm font-medium text-main-text mb-2">
 {{ $tr("notifications.empty_trash_confirm_title") }}
 </h3>
 <p class="text-main-text/60 mb-6 font-normal">
 {{ $tr("notifications.empty_trash_confirm_desc") }}
 </p>
 <div class="flex items-center justify-end gap-3">
 <button
 @click="showEmptyTrashConfirm = false"
 class="px-4 py-2 text-sm font-semibold text-main-text/60 hover:text-main-text rounded-lg transition-colors"
 :disabled="isCleaningTrash">
 {{ $tr("common.cancel") }}
 </button>
 <button
 @click="confirmEmptyTrash"
 class="px-4 py-2 text-sm font-medium text-white bg-brand-red hover:bg-brand-red/90 rounded-lg flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-brand-red/20"
 :disabled="isCleaningTrash">
 <Loader2
 v-if="isCleaningTrash"
 class="w-4 h-4 animate-spin text-white" />
 <span>{{
 isCleaningTrash
 ? $tr("common.deleting") + "..."
 : $tr(
 "notifications.delete_permanently",
 )
 }}</span>
 </button>
 </div>
 </div>
 </div>
 </transition>
 </Teleport>
 </header>
</template>
