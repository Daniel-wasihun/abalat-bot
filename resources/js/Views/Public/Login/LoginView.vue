<script setup lang="ts">
import { useParticles } from "@/composables/useParticles";
import { useAuth } from "@/composables/useAuth";
import LoginLeftPanel from "./components/LoginLeftPanel.vue";
import LoginHeader from "./components/LoginHeader.vue";
import LoginForm from "./components/LoginForm.vue";
import ForgotPasswordModal from "./components/ForgotPasswordModal.vue";

const { particles } = useParticles();
const {
 loginForm,
 isLoggingIn,
 requires2fa,
 handleLogin,
 errors,
 clearFieldError,
 forgotData,
 openForgotModal,
 handleSendOtp,
 handleVerifyOtp,
 handleResetPassword,
} = useAuth();

const {
 showForgotModal,
 forgotStep,
 forgotEmail,
 forgotOtp,
 newPassword,
 confirmPassword,
 hpEmail,
 hpTimestamp,
 isProcessingOtp,
} = forgotData;
</script>

<template>
 <main class="h-screen w-screen overflow-hidden bg-main-bg relative flex">
 <!-- Header Actions (Top Right) -->
 <LoginHeader />

 <!-- Cursor Particles (Global Overlay) -->
 <div class="absolute inset-0 pointer-events-none z-50 overflow-hidden">
 <div
 v-for="particle in particles"
 :key="particle.id"
 class="absolute w-1.5 h-1.5 rounded-full -translate-x-1/2 -translate-y-1/2 pointer-events-none z-40 hidden lg:block"
 :class="
 particle.id % 2 === 0
 ? 'bg-[#fba81c] blur-[2px] opacity-60'
 : 'bg-[#0b529c] blur-[2px] opacity-60'
 "
 :style="{
 left: `${particle.x}%`,
 top: `${particle.y}%`,
 }"></div>
 </div>

 <!-- LEFT PANEL: Animation & Brand (50%) -->
 <LoginLeftPanel />

 <!-- RIGHT PANEL: Login Form (50%) -->
 <div
 class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-main-bg relative z-10">
 <div class="w-full max-w-110">
 <LoginForm
 :form="loginForm"
 :loading="isLoggingIn"
 :requires2fa="requires2fa"
 :errors="errors"
 @submit="handleLogin"
 @clear-error="clearFieldError"
 @forgot-password="openForgotModal" />
 </div>
 </div>
 </main>

 <ForgotPasswordModal
 v-model:show="showForgotModal"
 v-model:step="forgotStep"
 v-model:email="forgotEmail"
 v-model:otp="forgotOtp"
 v-model:newPass="newPassword"
 v-model:confirmPass="confirmPassword"
 v-model:hpEmail="hpEmail"
 v-model:hpTimestamp="hpTimestamp"
 :errors="errors"
 :loading="isProcessingOtp"
 @clear-error="clearFieldError"
 @send-otp="handleSendOtp"
 @verify-otp="handleVerifyOtp"
 @reset-password="handleResetPassword" />
</template>
