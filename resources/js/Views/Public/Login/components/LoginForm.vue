<script setup lang="ts">
import { Mail, Lock, BookOpen, Eye, EyeOff, ArrowRight } from "lucide-vue-next";
import Button from "@/components/common/Button.vue";
import FormField from "@/components/common/FormField.vue";
import { ref } from "vue";

const rememberMe = ref(false);
const showPassword = ref(false);

defineProps<{
  form: {
    email: string;
    password: string;
    _hp_email_verification: string;
    _hp_timestamp: string;
  };
  loading: boolean;
  errors: Record<string, string>;
}>();

defineEmits(["submit", "forgot-password", "clear-error"]);
</script>

<template>
  <div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header Section -->
    <div class="mb-10 text-center">
      <div
        class="inline-flex items-center justify-center w-14 h-14 mb-6 rounded-2xl bg-linear-to-br from-brand-blue to-brand-blue-dark shadow-lg shadow-brand-blue/20 transform hover:scale-105 transition-all duration-500 border border-white/10 ring-4 ring-brand-blue/5">
        <BookOpen class="w-7 h-7 text-white" />
      </div>
      <h1
        class="text-3xl font-bold text-main-text tracking-tight mb-3 capitalize">
        {{ $tr("auth.welcome_back") }}
      </h1>
      <p
        class="max-w-xs mx-auto text-[13px] font-medium text-main-text/40 tracking-wide leading-relaxed">
        {{ $tr("auth.login_subtitle") }}
      </p>
    </div>

    <!-- Form Card -->
    <div
      class="p-8 bg-card-bg/50 backdrop-blur-2xl rounded-[32px] border border-card-border shadow-soft-xl relative group transition-all duration-500 hover:border-brand-blue/30 overflow-hidden">
      <!-- Ambient Background Glow -->
      <div class="absolute -top-24 -right-24 w-48 h-48 bg-brand-blue/5 blur-3xl pointer-events-none group-hover:bg-brand-blue/10 transition-colors duration-1000"></div>
      
      <form @submit.prevent="$emit('submit')" class="space-y-6 relative z-10">
        <!-- Honeypot fields -->
        <input
          type="text"
          v-model="form._hp_email_verification"
          class="hidden"
          tabindex="-1"
          autocomplete="off" />
        <input type="hidden" v-model="form._hp_timestamp" />
        
        <!-- Email Field -->
        <FormField
          v-model="form.email"
          type="email"
          :label="$tr('auth.email', 'Email')"
          placeholder="name@university.edu"
          :icon="Mail"
          required
          :error="errors.email"
          @input="$emit('clear-error', 'email')" />

        <!-- Password Field -->
        <FormField
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          :label="$tr('auth.password', 'Password')"
          placeholder="••••••••"
          :icon="Lock"
          required
          :error="errors.password"
          @input="$emit('clear-error', 'password')">
          <template #trailing>
            <button 
              type="button" 
              @click="showPassword = !showPassword"
              class="text-main-text/30 hover:text-brand-blue transition-colors outline-none translate-y-[-0.5px]"
            >
              <component :is="showPassword ? EyeOff : Eye" class="w-4.5 h-4.5" />
            </button>
          </template>
        </FormField>

        <!-- Remember & Forgot -->
        <div class="flex items-center justify-between pt-1">
          <label class="flex items-center gap-2.5 cursor-pointer group/check select-none">
            <div class="relative">
              <input type="checkbox" v-model="rememberMe" class="peer sr-only" />
              <div class="w-4.5 h-4.5 border-[1.5px] border-main-text/10 rounded-md bg-main-bg peer-checked:bg-brand-blue peer-checked:border-brand-blue transition-all duration-300"></div>
              <div class="absolute inset-0 flex items-center justify-center text-white scale-0 peer-checked:scale-100 transition-transform duration-300">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                  <path d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
            <span class="text-[11px] font-bold text-main-text/40 group-hover/check:text-brand-blue transition-colors uppercase tracking-[0.08em]">{{ $tr('auth.remember_me') }}</span>
          </label>

          <Button
            variant="ghost"
            size="sm"
            @click.prevent="$emit('forgot-password')"
            class="text-brand-blue! font-bold tracking-widest uppercase text-[10px]! px-0! bg-transparent hover:bg-transparent">
            {{ $tr("auth.forgot_password") }}
          </Button>
        </div>

        <!-- Submit Button -->
        <Button
          type="submit"
          variant="primary"
          :loading="loading"
          class="w-full h-13 text-sm! font-bold! uppercase! tracking-widest shadow-lg shadow-brand-blue/10">
          <template #default>
            <div class="flex items-center justify-center gap-2">
              {{ $tr("auth.sign_in") }}
              <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" v-if="!loading" />
            </div>
          </template>
        </Button>
      </form>
    </div>
  </div>
</template>
