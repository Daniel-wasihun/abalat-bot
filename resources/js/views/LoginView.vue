<template>
  <div class="min-h-screen flex items-center justify-center p-4
              bg-slate-900 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(99,102,241,0.25),transparent)]">

    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/60 backdrop-blur-2xl shadow-2xl p-8">

        <!-- Ambient glow blobs -->
        <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-primary-600/20 blur-3xl pointer-events-none" />
        <div class="absolute -bottom-12 -left-12 w-48 h-48 rounded-full bg-violet-600/10 blur-3xl pointer-events-none" />

        <!-- Header -->
        <div class="flex flex-col items-center mb-8">
          <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-primary-600 shadow-xl shadow-primary-600/30 mb-4">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8
                   a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72
                   C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-white tracking-tight">FeedHub Admin</h1>
          <p class="text-sm text-slate-400 mt-1">Sign in to your dashboard</p>
        </div>

        <!-- Error Banner -->
        <transition name="fade">
          <div
            v-if="authStore.error"
            class="mb-5 flex items-start gap-2.5 p-3.5 rounded-xl border border-red-800/50 bg-red-950/30 text-red-300 text-sm"
          >
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ authStore.error }}</span>
          </div>
        </transition>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin" class="space-y-4">
          <!-- Email -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
              Email Address
            </label>
            <div class="relative">
              <input
                v-model="email"
                type="email"
                required
                placeholder="admin@example.com"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/60
                       text-white placeholder-slate-500 text-sm
                       focus:bg-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                       outline-none transition-all duration-150"
              />
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
              </svg>
            </div>
          </div>

          <!-- Password -->
          <div>
            <div class="flex justify-between items-center mb-1.5">
              <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
              <router-link to="/forgot-password" class="text-xs font-semibold text-primary-400 hover:text-primary-300 transition-colors">
                Forgot Password?
              </router-link>
            </div>
            <div class="relative">
              <input
                v-model="password"
                type="password"
                required
                placeholder="••••••••"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/60
                       text-white placeholder-slate-500 text-sm
                       focus:bg-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                       outline-none transition-all duration-150"
              />
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
          </div>

          <!-- Remember me -->
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input v-model="rememberMe" type="checkbox" class="rounded border-slate-700 bg-slate-800 text-primary-600 focus:ring-primary-500/20" />
            <span class="text-sm text-slate-400">Remember me for 30 days</span>
          </label>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="authStore.loading"
            class="w-full mt-2 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-white
                   bg-primary-600 hover:bg-primary-500 disabled:opacity-50
                   shadow-lg shadow-primary-600/25 hover:shadow-primary-600/35
                   transition-all duration-150"
          >
            <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span>{{ authStore.loading ? 'Signing In…' : 'Sign In' }}</span>
          </button>
        </form>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router    = useRouter();
const authStore = useAuthStore();
const showToast = inject('showToast');

const email      = ref('');
const password   = ref('');
const rememberMe = ref(false);

const handleLogin = async () => {
  try {
    await authStore.login(email.value, password.value);
    showToast('Welcome back! Logged in successfully.');
    router.push({ name: 'Dashboard' });
  } catch {
    showToast(authStore.error || 'Login failed', 'error');
  }
};
</script>
