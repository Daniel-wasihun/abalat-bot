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
          <h2 class="text-2xl font-bold text-white tracking-tight">{{ t('auth.forgotPassword') }}</h2>
          <p class="text-slate-400 text-sm mt-1 text-center">Enter your email and we'll generate a reset token link for you</p>
        </div>

        <!-- Success Message -->
        <div v-if="successMsg" class="mb-5 p-4 rounded-xl border border-emerald-800/50 bg-emerald-950/30 text-emerald-300 text-xs font-medium space-y-2">
          <p>{{ successMsg }}</p>
          <div class="p-2.5 rounded-lg bg-slate-800 border border-slate-700 break-all">
            <a :href="resetUrl" class="text-primary-450 hover:underline font-bold text-[11px]">{{ resetUrl }}</a>
          </div>
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="handleForgotPassword" class="space-y-4">
          
          <div>
            <label class="block text-xs font-semibold text-slate-400 capitalize tracking-wider mb-1.5">{{ t('auth.registeredEmail') }}</label>
            <div class="relative">
              <input v-model="email" type="email" required placeholder="admin@example.com" 
                     class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/60
                            text-white placeholder-slate-500 text-sm
                            focus:bg-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                            outline-none transition-all duration-150" />
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
              </svg>
            </div>
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
            <span>{{ loading ? 'Generating…' : 'Generate Reset Link' }}</span>
          </button>

        </form>

        <div class="mt-6 text-center">
          <router-link to="/login" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            Back to Sign In
          </router-link>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useLanguageStore } from '../stores/languageStore';

const authStore = useAuthStore();
const languageStore = useLanguageStore();
const t = computed(() => languageStore.t).value;
const showToast = inject('showToast');

const email = ref('');
const loading = ref(false);
const successMsg = ref('');
const resetUrl = ref('');

const handleForgotPassword = async () => {
  loading.value = true;
  try {
    const res = await authStore.forgotPassword(email.value);
    successMsg.value = res.message;
    resetUrl.value = res.reset_url || '';
    showToast('Reset link generated successfully!');
  } catch (err) {
    showToast('Email not found or verification error', 'error');
  } finally {
    loading.value = false;
  }
};
</script>
