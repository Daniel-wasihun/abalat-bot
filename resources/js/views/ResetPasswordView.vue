<template>
  <div class="flex items-center justify-center min-h-screen p-4 bg-slate-900 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(99,102,241,0.25),transparent)]">
    
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/60 backdrop-blur-2xl shadow-2xl p-8">
        
        <!-- Ambient glow blobs -->
        <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-primary-600/20 blur-3xl pointer-events-none" />
        <div class="absolute -bottom-12 -left-12 w-48 h-48 rounded-full bg-violet-600/10 blur-3xl pointer-events-none" />

        <!-- Header -->
        <div class="flex flex-col items-center mb-8">
          <h2 class="text-2xl font-bold text-white tracking-tight">Reset Password</h2>
          <p class="text-slate-400 text-sm mt-1 text-center">Set your new password below</p>
        </div>

        <form @submit.prevent="handleResetPassword" class="space-y-4">
          
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Email Address</label>
            <input v-model="email" type="email" required readonly 
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/40 text-slate-400 outline-none text-sm cursor-not-allowed" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Reset Token</label>
            <input v-model="token" type="text" required readonly 
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/40 text-slate-400 outline-none text-sm cursor-not-allowed" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">New Password</label>
            <input v-model="password" type="password" required placeholder="••••••••" 
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/60
                          text-white placeholder-slate-500 text-sm
                          focus:bg-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                          outline-none transition-all duration-150" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Confirm New Password</label>
            <input v-model="passwordConfirmation" type="password" required placeholder="••••••••" 
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/60
                          text-white placeholder-slate-500 text-sm
                          focus:bg-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                          outline-none transition-all duration-150" />
          </div>

          <button type="submit" :disabled="loading" 
                  class="w-full mt-2 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-white
                         bg-primary-600 hover:bg-primary-500 disabled:opacity-50
                         shadow-lg shadow-primary-600/25 hover:shadow-primary-600/35
                         transition-all duration-150">
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span>{{ loading ? 'Updating…' : 'Update Password' }}</span>
          </button>

        </form>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const showToast = inject('showToast');

const email = ref('');
const token = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);

onMounted(() => {
  email.value = route.query.email || '';
  token.value = route.query.token || '';
});

const handleResetPassword = async () => {
  if (password.value !== passwordConfirmation.value) {
    showToast('Passwords do not match!', 'error');
    return;
  }

  loading.value = true;
  try {
    await authStore.resetPassword(
      email.value,
      token.value,
      password.value,
      passwordConfirmation.value
    );
    showToast('Password reset successfully! Please sign in.');
    router.push({ name: 'Login' });
  } catch (err) {
    showToast(err.response?.data?.message || 'Failed to reset password', 'error');
  } finally {
    loading.value = false;
  }
};
</script>
