<script setup lang="ts">
import {
  ref,
  watch,
  computed,
  getCurrentInstance,
  onMounted,
  onUnmounted,
  type FunctionalComponent,
  type Component,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  LayoutDashboard,
  MessageSquare,
  Bell,
  Users,
  Settings,
  Shield,
  ShieldCheck,
  UserCircle,
  Pencil,
  Monitor,
  LogOut,
  ChevronRight,
  CreditCard,
  Loader2,
  PanelLeftClose,
  Bot,
  Key,
  GraduationCap,
  BookOpen,
  Calendar,
  Settings2,
} from "lucide-vue-next";
import { useAuthStore } from "@/stores/authStore";
import { useLanguageStore } from "@/stores/languageStore";
import { Modules } from "@/constants/permissions";
import { usePermissions } from "@/composables/usePermissions";

const props = defineProps<{
  isCollapsed: boolean;
  isManuallyCollapsed: boolean;
  isMobile: boolean;
}>();

const emit = defineEmits(["toggle", "hover", "resize", "resizing"]);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const languageStore = useLanguageStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const expandedMenus = ref<string[]>(
  JSON.parse(localStorage.getItem("expandedMenus") || "[]"),
);
const sidebarWidth = ref(Number(localStorage.getItem("sidebarWidth")) || 280);
const effectiveWidth = computed(() => {
  if (props.isMobile) return 260;
  return sidebarWidth.value;
});
const isResizing = ref(false);
const isScrolled = ref(false);
const isLoggingOut = ref(false);
const isProfileMenuOpen = ref(false);
const showLogoutConfirm = ref(false);

const localize = (val: any) => {
  if (!val) return "";
  return typeof val === "object"
    ? val[languageStore.currentLanguage] || val["en"] || ""
    : val;
};

// Permissions
const perms = {
  dashboard:        usePermissions(Modules.DASHBOARD),
  users:            usePermissions(Modules.USERS),
  roles:            usePermissions(Modules.ROLES),
  permissions:      usePermissions(Modules.PERMISSIONS),
  security:         usePermissions(Modules.SECURITY),
  bot:              usePermissions(Modules.BOT),
  // Academic modules
  academicCourses:  usePermissions(Modules.ACADEMIC_COURSES),
  academicClasses:  usePermissions(Modules.ACADEMIC_CLASSES),
};

const toggleMenu = (name: string) => {
  if (expandedMenus.value.includes(name)) {
    expandedMenus.value = expandedMenus.value.filter((n) => n !== name);
  } else {
    expandedMenus.value.push(name);
  }
  localStorage.setItem("expandedMenus", JSON.stringify(expandedMenus.value));
};

const startResizing = (e: MouseEvent) => {
  isResizing.value = true;
  emit("resizing", true);
  document.addEventListener("mousemove", handleMouseMove);
  document.addEventListener("mouseup", stopResizing);
  document.body.style.cursor = "col-resize";
  document.body.style.userSelect = "none";
};

const handleMouseMove = (e: MouseEvent) => {
  if (!isResizing.value) return;
  const newWidth = e.clientX;
  if (newWidth >= 200 && newWidth <= 450) {
    sidebarWidth.value = newWidth;
    emit("resize", newWidth);
  }
};

const stopResizing = () => {
  isResizing.value = false;
  emit("resizing", false);
  document.removeEventListener("mousemove", handleMouseMove);
  document.removeEventListener("mouseup", stopResizing);
  document.body.style.cursor = "default";
  document.body.style.userSelect = "auto";
};

const handleScroll = (e: Event) => {
  const target = e.target as HTMLElement;
  isScrolled.value = target.scrollTop > 10;
};

const toggleProfileMenu = (event?: MouseEvent) => {
  isProfileMenuOpen.value = !isProfileMenuOpen.value;
  if (event) event.stopPropagation();
};

const closeSidebarMenus = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest("#sidebar-profile-menu")) {
    isProfileMenuOpen.value = false;
  }
  if (!target.closest("aside") && props.isMobile) {
    expandedMenus.value = [];
    localStorage.setItem("expandedMenus", JSON.stringify([]));
  }
};

onMounted(() => {
  window.addEventListener("click", closeSidebarMenus);
});

onUnmounted(() => {
  window.removeEventListener("click", closeSidebarMenus);
});

const handleLogout = () => {
  isProfileMenuOpen.value = false;
  showLogoutConfirm.value = true;
};

const confirmLogout = async () => {
  isLoggingOut.value = true;
  try {
    await authStore.logout();
  } catch {
    isLoggingOut.value = false;
    showLogoutConfirm.value = false;
  }
};

const getProfileImage = (path: string | null | undefined): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith("http")) return path;
  const baseUrl = import.meta.env.VITE_STORAGE_URL || "/storage";
  return `${baseUrl}/${path}`;
};

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

interface MenuItem {
  name: string;
  icon?: Component;
  to: string;
  condition?: () => boolean;
  children?: MenuItem[];
}

interface MenuGroup {
  name: string;
  items: MenuItem[];
  condition?: () => boolean;
}

const isSuperAdmin = computed(() => {
  const u = authStore.user as any;
  if (!u) return false;
  if (u.is_super_admin) return true;
  const slug = (u.role?.slug || '').toLowerCase().replace(/[\s_]/g, '-');
  return ['super-admin', 'superadmin'].includes(slug);
});

const menuGroups = computed<MenuGroup[]>(() => [
  {
    name: "nav.main",
    items: [
      {
        name: "nav.dashboard",
        icon: LayoutDashboard,
        to: "/dashboard",
        condition: () => perms.dashboard.canView.value,
      },
    ],
  },
  {
    name: "nav.telegram_bot",
    items: [
      {
        name: "nav.feedback",
        icon: MessageSquare,
        to: "/dashboard/telegram-bot/feedback",
        condition: () => perms.bot.canView.value,
      },
      {
        name: "nav.bot_users",
        icon: Users,
        to: "/dashboard/telegram-bot/users",
        condition: () => perms.bot.canManage?.value || authStore.isStaff,
      },
      {
        name: "nav.notifications",
        icon: Bell,
        to: "/dashboard/telegram-bot/notifications",
        condition: () => perms.bot.canNotify?.value || authStore.isStaff,
      },
      {
        name: "nav.bot_settings",
        icon: Settings,
        to: "/dashboard/telegram-bot/settings",
        condition: () => perms.bot.canManage?.value || authStore.isStaff,
      },
    ],
  },
  {
    name: "nav.system",
    condition: () => authStore.isStaff,
    items: [
      {
        name: "nav.user_management",
        icon: ShieldCheck,
        to: "/dashboard/system/user-management/roles",
        condition: () =>
          perms.roles.canView.value || perms.permissions.canView.value,
        children: [
          {
            name: "nav.users",
            to: "/dashboard/system/user-management/users",
            condition: () => perms.users.canView.value,
          },
          {
            name: "nav.roles",
            to: "/dashboard/system/user-management/roles",
            condition: () => perms.roles.canView.value,
          },
          {
            name: "nav.permissions",
            to: "/dashboard/system/user-management/permissions",
            condition: () => perms.permissions.canView.value,
          },
        ],
      },
      {
        name: "nav.payments",
        icon: CreditCard,
        to: "/dashboard/system/payments",
        condition: () => perms.users.canView.value,
      },
    ],
  },
  {
    // Academic Management — visible to users with academic permissions, teachers, and students
    name: "nav.academic",
    condition: () =>
      perms.academicCourses.canView.value ||
      perms.academicClasses.canView.value ||
      authStore.isTeacher ||
      authStore.isStudent,
    items: [
      {
        // Course admin — create/edit/delete/assign teachers — admin+ only
        name: "nav.courses",
        icon: GraduationCap,
        to: "/dashboard/academic/courses",
        condition: () => perms.academicCourses.canView.value,
      },
      {
        // My Courses — teachers, students, and admin
        name: "nav.my_courses",
        icon: BookOpen,
        to: "/dashboard/academic/my-courses",
        condition: () =>
          perms.academicCourses.canView.value ||
          perms.academicClasses.canView.value ||
          authStore.isTeacher ||
          authStore.isStudent,
      },
      {
        // General Attendance — admin or homeroom teachers
        name: "nav.general_attendance",
        icon: Calendar,
        to: "/dashboard/academic/general-attendance",
        condition: () => perms.academicCourses.canView.value || authStore.isTeacher,
      },
      {
        // Academic Configuration — admin only
        name: "common.config",
        icon: Settings2,
        to: "/dashboard/academic/config",
        condition: () => perms.academicCourses.canManage.value,
      },
    ],
  },
]);

const isActive = (item: MenuItem): boolean => {
  if (item.children) return item.children.some((child) => isActive(child));
  if (!item.to) return false;
  if (item.to === "/dashboard") return route.path === "/dashboard";
  return route.path.startsWith(item.to);
};

const visibleItems = (items: MenuItem[]): MenuItem[] => {
  return items
    .filter((item) => {
      if (isSuperAdmin.value) return true;
      if (item.condition && !item.condition()) return false;
      if (item.children?.length) {
        return item.children.some((child) => !child.condition || child.condition());
      }
      return true;
    })
    .map((item) => {
      if (item.children?.length) {
        return {
          ...item,
          children: item.children.filter(
            (child) => isSuperAdmin.value || !child.condition || child.condition(),
          ),
        };
      }
      return item;
    });
};

const filteredMenuGroups = computed(() => {
  return menuGroups.value
    .map((group) => ({
      ...group,
      items: visibleItems(group.items),
    }))
    .filter((group) => {
      if (isSuperAdmin.value) return group.items.length > 0;
      if (group.condition && !group.condition()) return false;
      return group.items.length > 0;
    });
});

const handleItemClick = () => {
  if (props.isMobile) emit("toggle");
};
</script>

<template>
  <aside
    @mouseenter="emit('hover', true)"
    @mouseleave="emit('hover', false)"
    :class="[
      'fixed left-0 top-0 h-full z-150 border-r border-card-border bg-card-bg flex flex-col',
      !isResizing ? 'layout-transition' : '',
      isCollapsed ? 'w-20' : '',
    ]"
    :style="!isCollapsed ? { width: effectiveWidth + 'px' } : {}">

    <!-- Logo Area -->
    <div
      class="h-12 md:h-14 flex items-center px-4 md:px-6 overflow-hidden shrink-0 z-10 transition-shadow duration-300"
      :class="[
        isCollapsed ? 'justify-center' : 'justify-start gap-4',
        isScrolled
          ? 'shadow-md border-b border-card-border/50 bg-card-bg'
          : 'mb-4 md:mb-6',
      ]">
      <div
        class="flex items-center gap-2 md:gap-3 shrink-0 group cursor-pointer transition-all duration-300"
        @click="router.push('/dashboard')">
        <img
          src="/logo.webp"
          alt="Logo"
          class="w-12 h-12 md:w-14 md:h-14 object-contain group-hover:scale-105 transition-transform duration-300" />
        <div
          v-if="!isCollapsed"
          class="font-bold text-xl md:text-2xl tracking-tighter animate-in fade-in slide-in-from-left-4 duration-300">
          <span class="text-brand-blue">{{ $tr("app.name") }}</span>
        </div>
      </div>
    </div>

    <!-- Navigation Menu -->
    <div
      @scroll="handleScroll"
      class="px-3 space-y-8 overflow-y-auto flex-1 min-h-0 custom-scrollbar pb-5">

      <!-- Loading Skeletons -->
      <div v-if="!authStore.user" class="space-y-8 animate-pulse">
        <div v-for="g in 2" :key="'g-' + g" class="space-y-3">
          <div v-if="!isCollapsed" class="skeleton h-3 w-20 rounded ml-2.5 opacity-20"></div>
          <div class="space-y-2">
            <div v-for="i in 3" :key="'i-' + i" class="flex items-center gap-4 px-5 py-2.5">
              <div class="skeleton w-5 h-5 rounded opacity-20 shrink-0"></div>
              <div v-if="!isCollapsed" class="skeleton h-4 w-28 rounded opacity-10"></div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else
        v-for="group in filteredMenuGroups"
        :key="group.name"
        class="space-y-3">
        <p
          v-if="!isCollapsed"
          class="px-2.5 text-xs tracking-widest text-main-text/50 capitalize animate-in fade-in duration-300">
          {{ $tr(group.name) }}
        </p>
        <div class="space-y-1 font-normal">
          <template v-for="item in group.items" :key="item.name">

            <!-- Item with Children -->
            <div v-if="item.children && item.children.length > 0">
              <button
                @click="toggleMenu(item.name)"
                :class="[
                  'w-full flex items-center gap-4 px-5 py-2.5 rounded-xl transition-all duration-300 group relative select-none',
                  isActive(item)
                    ? 'text-nav-accent-hover font-medium'
                    : 'text-main-text hover:bg-nav-accent/5 hover:text-nav-accent-hover',
                ]">
                <component
                  :is="item.icon"
                  class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0"
                  :class="[
                    isActive(item) ? 'text-nav-accent-light' : 'group-hover:text-nav-accent',
                  ]" />
                <span
                  v-if="!isCollapsed"
                  class="flex-1 text-left text-sm tracking-tight animate-in fade-in duration-300">
                  {{ $tr(item.name) }}
                </span>
                <ChevronRight
                  v-if="!isCollapsed"
                  :class="[
                    'w-4 h-4 transition-transform duration-300',
                    expandedMenus.includes(item.name) ? 'rotate-90' : '',
                  ]" />
              </button>

              <!-- Children -->
              <div
                v-if="expandedMenus.includes(item.name) && !isCollapsed"
                class="mt-1 space-y-1">
                <template v-for="child in item.children" :key="child.name">
                  <router-link
                    :to="child.to"
                    @click="handleItemClick"
                    :class="[
                      'flex items-center gap-4 pl-14 pr-5 py-2 rounded-xl transition-all duration-300 group relative',
                      isActive(child)
                        ? 'bg-nav-accent/8 text-nav-accent-hover font-medium'
                        : 'text-main-text/60 hover:text-nav-accent hover:bg-nav-accent/5',
                    ]">
                    <span class="text-sm tracking-tight">{{ $tr(child.name) }}</span>
                    <div
                      v-if="isActive(child)"
                      class="absolute left-10 top-1/2 -translate-y-1/2 w-1 h-1.5 bg-nav-accent-hover rounded-full">
                    </div>
                  </router-link>
                </template>
              </div>
            </div>

            <!-- Normal Item -->
            <router-link
              v-else
              :to="item.to"
              @click="handleItemClick"
              :class="[
                'flex items-center gap-4 px-5 py-2.5 rounded-xl transition-all duration-300 group relative',
                isActive(item)
                  ? 'bg-nav-accent/10 text-nav-accent-hover'
                  : 'text-main-text hover:bg-nav-accent/5 hover:text-nav-accent-hover',
              ]">
              <component
                :is="item.icon"
                class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0"
                :class="[
                  isActive(item) ? 'text-nav-accent-light' : 'group-hover:text-nav-accent',
                ]" />
              <span
                v-if="!isCollapsed"
                class="text-sm tracking-tight animate-in fade-in duration-300">
                {{ $tr(item.name) }}
              </span>
              <div
                v-if="isActive(item)"
                class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-9 bg-nav-accent-hover rounded-r-full">
              </div>
            </router-link>

          </template>
        </div>
      </div>
    </div>

    <!-- User Section -->
    <div id="sidebar-profile-menu" class="border-t border-card-border bg-card-bg p-2 shrink-0">
      <div class="relative">
        <!-- Skeleton -->
        <div v-if="!authStore.user" class="p-2">
          <div class="flex items-center gap-3 px-2 py-1" :class="{ 'justify-center': isCollapsed }">
            <div class="skeleton w-10 h-10 rounded-full opacity-30 shrink-0"></div>
            <div v-if="!isCollapsed" class="flex-1 space-y-2">
              <div class="skeleton h-4 w-24 rounded opacity-20"></div>
              <div class="skeleton h-3 w-16 rounded opacity-10"></div>
            </div>
          </div>
        </div>

        <!-- User Info -->
        <div
          v-else
          @click="toggleProfileMenu"
          class="flex items-center gap-3 px-2 py-2 rounded-xl cursor-pointer hover:bg-nav-accent/5 transition-colors"
          :class="{ 'justify-center': isCollapsed }">
          <div class="w-10 h-10 rounded-full overflow-hidden border border-card-border bg-nav-accent/5 flex items-center justify-center shrink-0">
            <img
              v-if="authStore.user?.info?.profile_picture"
              :src="getProfileImage(authStore.user.info.profile_picture)"
              class="w-full h-full object-cover" />
            <span v-else class="text-base font-medium text-nav-accent">{{ userInitial }}</span>
          </div>
          <div v-if="!isCollapsed" class="flex-1 min-w-0">
            <p class="text-sm font-medium text-main-text truncate">
              {{ localize(authStore.user?.name) }}
            </p>
            <p class="text-xs text-main-text/50 truncate capitalize font-medium">
              {{ localize(authStore.user?.role?.name) }}
            </p>
          </div>
        </div>

        <!-- Profile Popover -->
        <transition
          enter-active-class="transition duration-200 ease-out"
          :enter-from-class="isMobile ? 'transform scale-95 opacity-0 translate-y-4' : 'transform scale-95 opacity-0 -translate-x-2'"
          enter-to-class="transform scale-100 opacity-100 translate-y-0 translate-x-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="transform scale-100 opacity-100"
          :leave-to-class="isMobile ? 'transform scale-95 opacity-0 translate-y-4' : 'transform scale-95 opacity-0 -translate-x-2'">
          <div
            v-if="isProfileMenuOpen"
            :class="[
              'absolute bg-card-bg rounded-xl shadow-2xl border border-card-border overflow-hidden p-1 z-150 backdrop-blur-xl',
              isMobile ? 'bottom-full left-[calc(100%-120px)] mb-3 w-64' : 'left-full bottom-2 ml-2 w-64',
            ]">
            <button @click="router.push('/dashboard/profile/overview'); isProfileMenuOpen = false"
              class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-main-text hover:bg-nav-accent/5 hover:text-nav-accent-hover rounded-xl transition-all group">
              <UserCircle class="w-4 h-4 text-main-text/30 group-hover:text-nav-accent transition-colors" />
              <span>{{ $tr("profile.overview") }}</span>
            </button>
            <button @click="router.push('/dashboard/profile/edit'); isProfileMenuOpen = false"
              class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-main-text hover:bg-nav-accent/5 hover:text-nav-accent-hover rounded-xl transition-all group">
              <Pencil class="w-4 h-4 text-main-text/30 group-hover:text-nav-accent transition-colors" />
              <span>{{ $tr("profile.edit") }}</span>
            </button>
            <button @click="router.push('/dashboard/profile/security'); isProfileMenuOpen = false"
              class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-main-text hover:bg-nav-accent/5 hover:text-nav-accent-hover rounded-xl transition-all group">
              <Shield class="w-4 h-4 text-main-text/30 group-hover:text-nav-accent transition-colors" />
              <span>{{ $tr("profile.security") }}</span>
            </button>
            <button @click="router.push('/dashboard/profile/devices'); isProfileMenuOpen = false"
              class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-main-text hover:bg-nav-accent/5 hover:text-nav-accent-hover rounded-xl transition-all group">
              <Monitor class="w-4 h-4 text-main-text/30 group-hover:text-nav-accent transition-colors" />
              <span>{{ $tr("profile.active_sessions") }}</span>
            </button>
            <div class="my-1 border-t border-card-border/50"></div>
            <button @click="handleLogout"
              class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-main-text/40 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 rounded-xl transition-all group">
              <LogOut class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
              <span>{{ $tr("logout") }}</span>
            </button>
          </div>
        </transition>
      </div>
    </div>

    <!-- Resizer -->
    <div
      v-if="!isCollapsed"
      class="absolute right-0 top-0 w-1 h-full cursor-col-resize z-110 group/resizer"
      @mousedown="startResizing">
      <div :class="[
        'absolute right-0 top-0 w-1 h-full transition-all duration-300',
        isResizing ? 'bg-brand-blue opacity-100' : 'bg-brand-blue/0 group-hover/resizer:bg-brand-blue/30',
      ]"></div>
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
          class="fixed inset-0 z-999 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div class="bg-white dark:bg-card-bg rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-card-border animate-in zoom-in-95 duration-200">
            <h3 class="text-base font-semibold text-main-text mb-1">{{ $tr("logout") }}</h3>
            <p class="text-sm text-main-text/60 mb-6">{{ $tr("auth.logout_confirmation") }}</p>
            <div class="flex items-center justify-end gap-3">
              <button
                @click="showLogoutConfirm = false"
                class="px-4 py-2 text-sm text-main-text/60 hover:text-main-text rounded-xl transition-colors"
                :disabled="isLoggingOut">
                {{ $tr("common.cancel") }}
              </button>
              <button
                @click="confirmLogout"
                class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-red-500/20"
                :disabled="isLoggingOut">
                <Loader2 v-if="isLoggingOut" class="w-4 h-4 animate-spin" />
                <span>{{ isLoggingOut ? $tr("logging_out") : $tr("logout") }}</span>
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </aside>
</template>

<style scoped>
.layout-transition {
  transition: width 0.3s ease-in-out;
}
</style>
