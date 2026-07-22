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
      class="fixed inset-y-0 left-0 z-40 flex flex-col w-64
             border-r border-slate-200/80 bg-white/95 backdrop-blur-md
             dark:border-slate-800/70 dark:bg-slate-900/95
             transition-transform duration-300 ease-in-out lg:translate-x-0"
      :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Brand -->
      <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-200/70 dark:border-slate-800/70 shrink-0">
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-600 shadow-md shadow-primary-600/25">
          <svg class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8
                 a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72
                 C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-tight">FeedHub</p>
          <p class="text-[10px] text-slate-400 font-medium">Admin Dashboard</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
        <router-link
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          @click="$emit('close')"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group"
          :class="isActive(item.to)
            ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400'
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-100'"
        >
          <component
            :is="item.icon"
            class="w-5 h-5 shrink-0 transition-transform duration-150 group-hover:scale-110"
            :class="isActive(item.to) ? 'text-primary-600 dark:text-primary-400' : ''"
          />
          <span>{{ item.name }}</span>
          <!-- Active indicator dot -->
          <span
            v-if="isActive(item.to)"
            class="ml-auto w-1.5 h-1.5 rounded-full bg-primary-500"
          />
        </router-link>
      </nav>

      <!-- Current operator card -->
      <div class="p-4 border-t border-slate-200/70 dark:border-slate-800/70 shrink-0">
        <div class="flex items-center gap-3">
          <img
            :src="authStore.admin?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=admin`"
            class="w-9 h-9 rounded-full border-2 border-slate-200 dark:border-slate-700 bg-slate-100 object-cover"
            alt="Avatar"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate leading-tight">
              {{ authStore.admin?.name || 'Administrator' }}
            </p>
            <span
              class="badge mt-0.5"
              :class="roleBadgeClasses(authStore.role)"
            >
              {{ authStore.role }}
            </span>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import {
  HomeIcon,
  ChatBubbleLeftRightIcon,
  UsersIcon,
  MegaphoneIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

defineProps({ isOpen: Boolean });
defineEmits(['close']);

const route    = useRoute();
const authStore = useAuthStore();

const navItems = [
  { name: 'Dashboard',   to: '/',              icon: HomeIcon },
  { name: 'Feedback',    to: '/feedback',       icon: ChatBubbleLeftRightIcon },
  { name: 'Subscribers', to: '/users',          icon: UsersIcon },
  { name: 'Broadcasts',  to: '/notifications',  icon: MegaphoneIcon },
  { name: 'Settings',    to: '/settings',       icon: Cog6ToothIcon },
];

const isActive = (path) =>
  path === '/' ? route.path === '/' : route.path.startsWith(path);

const roleBadgeClasses = (role) => {
  switch (role) {
    case 'Super Admin':
      return 'bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-800';
    case 'Admin':
      return 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800';
    default:
      return 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
  }
};
</script>
