<template>
  <header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 sm:px-6
                 border-b border-slate-200/80 bg-white/80 backdrop-blur-md
                 dark:border-slate-800/70 dark:bg-slate-900/80 transition-colors duration-200">

    <!-- Left: sidebar toggle icon (ChatGPT style) + page title -->
    <div class="flex items-center gap-3">
      <button
        @click="handleSidebarToggle"
        class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        aria-label="Toggle sidebar"
        title="Toggle sidebar"
      >
        <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>
      <h1 class="hidden sm:block text-base font-bold text-slate-800 dark:text-slate-100">
        {{ pageTitle }}
      </h1>
    </div>

    <!-- Right: language switcher + dark mode toggle + user menu -->
    <div class="flex items-center gap-2">

      <!-- Language Switcher -->
      <LanguageSwitcher />

      <!-- Theme toggle -->
      <button
        @click="toggleDarkMode"
        class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        aria-label="Toggle theme"
      >
        <SunIcon  v-if="isDarkMode"  class="w-5 h-5" />
        <MoonIcon v-else             class="w-5 h-5" />
      </button>

      <!-- User dropdown trigger -->
      <div class="relative" @click.stop>
        <button
          @click="isDropdownOpen = !isDropdownOpen"
          class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <img
            :src="authStore.admin?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=admin`"
            class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 object-cover"
            alt="Avatar"
          />
          <span class="hidden md:block text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ authStore.admin?.name || 'Admin' }}
          </span>
          <ChevronDownIcon
            class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200"
            :class="isDropdownOpen ? 'rotate-180' : ''"
          />
        </button>

        <!-- Dropdown menu -->
        <transition name="dropdown">
          <div
            v-if="isDropdownOpen"
            class="absolute right-0 top-full mt-2 w-56 rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60
                   dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/60 py-1 z-50"
          >
            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('nav.signedInAs') }}</p>
              <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate mt-0.5">
                {{ authStore.admin?.email }}
              </p>
            </div>

            <div class="py-1">
              <button @click="openProfileModal" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <UserIcon class="w-4 h-4 text-slate-400" />
                {{ t('nav.editProfile') }}
              </button>
              <button @click="openPasswordModal" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <KeyIcon class="w-4 h-4 text-slate-400" />
                {{ t('nav.changePassword') }}
              </button>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 py-1">
              <button @click="handleLogout" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                {{ t('nav.signOut') }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- ── Edit Profile Modal ──────────────────────────── -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showProfileModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-5">{{ t('profile.editProfile') }}</h3>
            <form @submit.prevent="saveProfile" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('profile.name') }}</label>
                <input v-model="profileForm.name" type="text" required class="input-base" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('profile.emailAddress') }}</label>
                <input v-model="profileForm.email" type="email" required class="input-base" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('profile.avatarImageUrl') }}</label>
                <input v-model="profileForm.avatar" type="url" placeholder="https://..." class="input-base" />
              </div>
              <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="showProfileModal = false" class="btn-ghost">{{ t('common.cancel') }}</button>
                <button type="submit" :disabled="isSaving" class="btn-primary">
                  {{ isSaving ? t('common.saving') : t('profile.saveChanges') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- ── Change Password Modal ─────────────────────────── -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-5">{{ t('profile.changePassword') }}</h3>
            <form @submit.prevent="savePassword" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('profile.currentPassword') }}</label>
                <input v-model="passwordForm.current" type="password" required class="input-base" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('profile.newPassword') }}</label>
                <input v-model="passwordForm.new" type="password" required class="input-base" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">{{ t('profile.confirmNewPassword') }}</label>
                <input v-model="passwordForm.confirm" type="password" required class="input-base" />
              </div>
              <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="showPasswordModal = false" class="btn-ghost">{{ t('common.cancel') }}</button>
                <button type="submit" :disabled="isSaving" class="btn-primary">
                  {{ isSaving ? t('common.saving') : t('profile.updatePassword') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </teleport>

  </header>
</template>

<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../i18n.js';
import LanguageSwitcher from './LanguageSwitcher.vue';
import {
  SunIcon,
  MoonIcon,
  ChevronDownIcon,
  UserIcon,
  KeyIcon,
  ArrowLeftOnRectangleIcon,
} from '@heroicons/vue/24/outline';

defineProps({ sidebarOpen: Boolean });
const emit = defineEmits(['toggle-sidebar']);

const handleSidebarToggle = () => {
  if (window.innerWidth < 1024) {
    emit('toggle-sidebar');
  } else {
    window.dispatchEvent(new Event('sidebar-toggle-action'));
  }
};

const { t } = useI18n();
const router    = useRouter();
const route     = useRoute();
const authStore = useAuthStore();

const isDarkMode     = inject('isDarkMode');
const toggleDarkMode = inject('toggleDarkMode');
const showToast      = inject('showToast');

const isDropdownOpen    = ref(false);
const showProfileModal  = ref(false);
const showPasswordModal = ref(false);
const isSaving          = ref(false);

const profileForm  = ref({ name: '', email: '', avatar: '' });
const passwordForm = ref({ current: '', new: '', confirm: '' });

const pageTitle = computed(() => {
  const keys = {
    Dashboard:     'nav.dashboard',
    Feedback:      'nav.feedback',
    Users:         'nav.users',
    UserProfile:   'user.profile',
    Notifications: 'nav.notifications',
    Settings:      'nav.settings',
  };
  return keys[route.name] ? t(keys[route.name]) : 'ደቂቀ ብርሃን';
});

const openProfileModal = () => {
  profileForm.value = {
    name:   authStore.admin?.name   || '',
    email:  authStore.admin?.email  || '',
    avatar: authStore.admin?.avatar || '',
  };
  showProfileModal.value  = true;
  isDropdownOpen.value    = false;
};

const openPasswordModal = () => {
  passwordForm.value       = { current: '', new: '', confirm: '' };
  showPasswordModal.value  = true;
  isDropdownOpen.value     = false;
};

const saveProfile = async () => {
  isSaving.value = true;
  try {
    await authStore.updateProfile(profileForm.value.name, profileForm.value.email, profileForm.value.avatar);
    showToast('Profile updated successfully!');
    showProfileModal.value = false;
  } catch {
    showToast('Failed to update profile', 'error');
  } finally {
    isSaving.value = false;
  }
};

const savePassword = async () => {
  if (passwordForm.value.new !== passwordForm.value.confirm) {
    showToast('New passwords do not match', 'error');
    return;
  }
  isSaving.value = true;
  try {
    await authStore.changePassword(passwordForm.value.current, passwordForm.value.new, passwordForm.value.confirm);
    showToast('Password changed successfully!');
    showPasswordModal.value = false;
  } catch (err) {
    showToast(err.response?.data?.message || 'Failed to change password', 'error');
  } finally {
    isSaving.value = false;
  }
};

const handleLogout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
  showToast('Signed out successfully');
};

const closeDropdown = () => { isDropdownOpen.value = false; };

onMounted(()    => window.addEventListener('click', closeDropdown));
onUnmounted(()  => window.removeEventListener('click', closeDropdown));
</script>
