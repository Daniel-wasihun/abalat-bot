<template>
  <div :class="{ 'dark': isDarkMode }">
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-200">

      <!-- Toast Notification Layer -->
      <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-full max-w-sm pointer-events-none">
        <transition-group name="toast">
          <div
            v-for="toast in toasts"
            :key="toast.id"
            class="pointer-events-auto flex items-center justify-between gap-3 px-4 py-3 rounded-2xl border shadow-lg backdrop-blur-md transition-all duration-200"
            :class="getToastClasses(toast.type)"
          >
            <div class="flex items-center gap-2.5">
              <component :is="getToastIcon(toast.type)" class="w-4.5 h-4.5 shrink-0" />
              <span class="text-sm font-medium leading-snug">{{ toast.message }}</span>
            </div>
            <button @click="removeToast(toast.id)" class="shrink-0 opacity-60 hover:opacity-100 transition-opacity">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </transition-group>
      </div>

      <!-- Guest routes (Login, ForgotPassword, ResetPassword) — no layout shell -->
      <router-view v-if="isGuestRoute" v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>

      <!-- Authenticated routes — Sidebar + Navbar stay permanently mounted -->
      <AppLayout v-else />

    </div>
  </div>
</template>


<script setup>
import { ref, computed, onMounted, provide } from 'vue';
import { useRoute } from 'vue-router';
import AppLayout from './components/AppLayout.vue';
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const route = useRoute();

// Guest-only routes — these render without the Sidebar/Navbar shell
const GUEST_ROUTES = ['Login', 'ForgotPassword', 'ResetPassword'];
const isGuestRoute = computed(() => GUEST_ROUTES.includes(route.name));


// ── Dark Mode ──────────────────────────────────────────────
const isDarkMode = ref(localStorage.getItem('theme') === 'dark');

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
  document.documentElement.classList.toggle('dark', isDarkMode.value);
};

provide('isDarkMode', isDarkMode);
provide('toggleDarkMode', toggleDarkMode);

onMounted(() => {
  document.documentElement.classList.toggle('dark', isDarkMode.value);
});

// ── Toast Notifications ────────────────────────────────────
const toasts = ref([]);
let toastIdCounter = 0;

const showToast = (message, type = 'success', duration = 4000) => {
  const id = toastIdCounter++;
  toasts.value.push({ id, message, type });
  setTimeout(() => removeToast(id), duration);
};

const removeToast = (id) => {
  toasts.value = toasts.value.filter((t) => t.id !== id);
};

provide('showToast', showToast);

// ── Toast Helpers ──────────────────────────────────────────
const getToastClasses = (type) => {
  const map = {
    success: 'bg-emerald-50/95 border-emerald-200 text-emerald-800 dark:bg-emerald-950/90 dark:border-emerald-800 dark:text-emerald-200',
    error:   'bg-red-50/95    border-red-200    text-red-800    dark:bg-red-950/90    dark:border-red-800    dark:text-red-200',
    warning: 'bg-amber-50/95  border-amber-200  text-amber-800  dark:bg-amber-950/90  dark:border-amber-800  dark:text-amber-200',
    info:    'bg-blue-50/95   border-blue-200   text-blue-800   dark:bg-blue-950/90   dark:border-blue-800   dark:text-blue-200',
  };
  return map[type] ?? map.success;
};

const getToastIcon = (type) => {
  const map = {
    success: CheckCircleIcon,
    error:   ExclamationCircleIcon,
    warning: ExclamationTriangleIcon,
    info:    InformationCircleIcon,
  };
  return map[type] ?? CheckCircleIcon;
};
</script>
