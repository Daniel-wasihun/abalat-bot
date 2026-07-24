<template>
  <div>
    <!-- Mobile backdrop -->
    <transition name="fade">
      <div
        v-if="isOpen"
        @click="$emit('close')"
        class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"
      />
    </transition>

    <!-- Sidebar panel -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex flex-col
             border-r border-slate-200/80 bg-white/95 backdrop-blur-md
             dark:border-slate-800/70 dark:bg-slate-900/95
             transition-[transform,width] duration-200 ease-in-out select-none"
      :style="{ width: collapsed ? '68px' : `${sidebarWidth}px` }"
      :class="[
        isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
    >
      <!-- Drag Resize Handle -->
      <div
        v-if="!collapsed"
        class="resize-handle"
        @mousedown="startResize"
        title="Drag to resize sidebar width"
      />

      <!-- ── Brand ──────────────────────────────── -->
      <div
        class="flex items-center h-16 border-b border-slate-200/70 dark:border-slate-800/70 shrink-0 overflow-hidden"
        :class="collapsed ? 'justify-center px-0' : 'gap-3 px-5'"
      >
        <!-- Church icon / logo -->
        <div class="flex items-center justify-center w-9 h-9 shrink-0 rounded-xl
                    bg-gradient-to-tr from-amber-600 to-amber-500
                    shadow-md shadow-amber-600/25 text-white font-bold text-lg select-none">
          ⛪
        </div>

        <!-- App name — only shown when expanded -->
        <transition name="sidebar-text">
          <div v-if="!collapsed" class="min-w-0 overflow-hidden">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-tight font-ethiopic whitespace-nowrap">
              ደቂቀ ብርሃን
            </p>
          </div>
        </transition>
      </div>

      <!-- ── Navigation ────────────────────────── -->
      <nav class="flex-1 py-4 space-y-1 overflow-y-auto overflow-x-hidden"
           :class="collapsed ? 'px-2' : 'px-3'">
        <router-link
          v-for="item in navItems"
          :key="item.key"
          :to="item.to"
          @click="$emit('close')"
          class="flex items-center rounded-xl text-xs font-semibold transition-all duration-150 group relative focus:outline-none focus:ring-0"
          :class="[
            collapsed ? 'justify-center h-11 w-11 mx-auto' : 'gap-3 px-3.5 py-2.5',
            isActive(item.to)
              ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/30'
              : 'border border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-100',
          ]"
        >
          <!-- Icon -->
          <component
            :is="item.icon"
            class="w-[18px] h-[18px] shrink-0 transition-transform duration-150 group-hover:scale-110"
            :class="isActive(item.to) ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'"
          />

          <!-- Label (hidden when collapsed) -->
          <transition name="sidebar-text">
            <span v-if="!collapsed" class="truncate">{{ t(item.key) }}</span>
          </transition>

          <!-- Active dot -->
          <span
            v-if="isActive(item.to) && !collapsed"
            class="ml-auto w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"
          />

          <!-- Tooltip on collapsed hover -->
          <div
            v-if="collapsed"
            class="sidebar-tooltip"
          >
            {{ t(item.key) }}
          </div>
        </router-link>
      </nav>



      <!-- ── Admin card ─────────────────────────── -->
      <div
        class="border-t border-slate-200/70 dark:border-slate-800/70 shrink-0 overflow-hidden"
        :class="collapsed ? 'p-2' : 'p-4'"
      >
        <div class="flex items-center" :class="collapsed ? 'justify-center' : 'gap-3'">
          <img
            :src="authStore.admin?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=admin`"
            class="w-9 h-9 rounded-full border-2 border-amber-500/40 dark:border-amber-500/30 bg-slate-100 object-cover shrink-0"
            alt="Avatar"
          />
          <transition name="sidebar-text">
            <div v-if="!collapsed" class="flex-1 min-w-0">
              <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">
                {{ authStore.admin?.name || t('nav.sidebar.admin') }}
              </p>
              <span class="badge mt-0.5" :class="roleBadgeClasses(authStore.role)">
                {{ authStore.role }}
              </span>
            </div>
          </transition>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../i18n.js';
import {
  HomeIcon,
  ChatBubbleLeftRightIcon,
  UsersIcon,
  MegaphoneIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

defineProps({ isOpen: Boolean });
defineEmits(['close']);

const { t } = useI18n();
const route     = useRoute();
const authStore = useAuthStore();

// Collapsed & Custom width state
const collapsed    = ref(localStorage.getItem('sidebar_collapsed') === 'true');
const sidebarWidth = ref(parseInt(localStorage.getItem('sidebar_width') || '256'));

const toggleCollapse = () => {
  collapsed.value = !collapsed.value;
  localStorage.setItem('sidebar_collapsed', collapsed.value);
};

// Resizable functionality
const startResize = (e) => {
  e.preventDefault();
  document.addEventListener('mousemove', doResize);
  document.addEventListener('mouseup', stopResize);
  document.body.style.cursor = 'col-resize';
  document.body.style.userSelect = 'none';
};

const doResize = (e) => {
  let newWidth = e.clientX;
  if (newWidth < 180) newWidth = 180;
  if (newWidth > 380) newWidth = 380;
  sidebarWidth.value = newWidth;
  localStorage.setItem('sidebar_width', newWidth);
};

const stopResize = () => {
  document.removeEventListener('mousemove', doResize);
  document.removeEventListener('mouseup', stopResize);
  document.body.style.cursor = '';
  document.body.style.userSelect = '';
};

// Keep root CSS variable --sidebar-width updated for layout padding
const activeWidth = computed(() => (collapsed.value ? 68 : sidebarWidth.value));

watch(activeWidth, (w) => {
  document.documentElement.style.setProperty('--sidebar-width', `${w}px`);
}, { immediate: true });

// Listen for Navbar action toggle
const handleActionToggle = () => toggleCollapse();

onMounted(() => {
  window.addEventListener('sidebar-toggle-action', handleActionToggle);
});
onUnmounted(() => {
  window.removeEventListener('sidebar-toggle-action', handleActionToggle);
});

// Nav items use i18n keys instead of hardcoded strings
const navItems = [
  { key: 'nav.sidebar.dashboard',     to: '/',              icon: HomeIcon },
  { key: 'nav.sidebar.feedback',      to: '/feedback',      icon: ChatBubbleLeftRightIcon },
  { key: 'nav.sidebar.users',         to: '/users',         icon: UsersIcon },
  { key: 'nav.sidebar.notifications', to: '/notifications', icon: MegaphoneIcon },
  { key: 'nav.sidebar.settings',      to: '/settings',      icon: Cog6ToothIcon },
];

const isActive = (path) =>
  path === '/' ? route.path === '/' : route.path.startsWith(path);

const roleBadgeClasses = (role) => {
  switch (role) {
    case 'Super Admin':
      return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800';
    case 'Admin':
      return 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800';
    default:
      return 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
  }
};
</script>

<style scoped>
/* Drag handle at right border */
.resize-handle {
  position: absolute;
  top: 0;
  right: -2px;
  bottom: 0;
  width: 5px;
  cursor: col-resize;
  z-index: 50;
  background: transparent;
  transition: background 0.2s ease;
}
.resize-handle:hover,
.resize-handle:active {
  background: rgba(245, 158, 11, 0.6);
}

/* Tooltip shown when sidebar is collapsed */
.sidebar-tooltip {
  position: absolute;
  left: calc(100% + 10px);
  top: 50%;
  transform: translateY(-50%);
  background: #1e293b;
  color: #f8fafc;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
  padding: 5px 10px;
  border-radius: 8px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s ease;
  z-index: 100;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}
.sidebar-tooltip::before {
  content: '';
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-right-color: #1e293b;
}
.group:hover .sidebar-tooltip { opacity: 1; }

/* Text fade transition used for labels when collapsing */
.sidebar-text-enter-active { transition: opacity 0.18s ease, max-width 0.3s ease; }
.sidebar-text-leave-active { transition: opacity 0.1s ease, max-width 0.25s ease; }
.sidebar-text-enter-from,
.sidebar-text-leave-to     { opacity: 0; max-width: 0; }
.sidebar-text-enter-to,
.sidebar-text-leave-from   { opacity: 1; max-width: 200px; }
</style>
