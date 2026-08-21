<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useToastStore } from "@/stores/toast";
import { useLanguageStore } from "@/stores/languageStore";
import {
 ShieldCheck,
 Key,
 Lock,
 Eye,
 EyeOff,
 AlertCircle,
} from "lucide-vue-next";
import FormField from "@/components/common/FormField.vue";
import Button from "@/components/common/Button.vue";

const authStore = useAuthStore();
const toast = useToastStore();
const langStore = useLanguageStore();

const passwordForm = ref({
 current_password: "",
 password: "",
 password_confirmation: "",
});

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isChangingPassword = ref(false);
const errors = ref<any>({});

const handleChangePassword = async () => {
 isChangingPassword.value = true;
 errors.value = {};

 if (
 passwordForm.value.password !== passwordForm.value.password_confirmation
 ) {
 const message = langStore.translate("auth.password_mismatch");
 errors.value = {
 password: [message],
 password_confirmation: [message],
 };
 isChangingPassword.value = false;
 return;
 }

 try {
 await authStore.changePassword(passwordForm.value);

 passwordForm.value = {
 current_password: "",
 password: "",
 password_confirmation: "",
 };
 } catch (error: any) {
 if (error.response?.status === 422) {
 errors.value = error.response.data.errors;
 } else {
 toast.error("Failed to change password");
 }
 } finally {
 isChangingPassword.value = false;
 }
};

// --- 2FA Logic ---
import apiClient from "@/api/apiClient";
const is2faLoading = ref(false);
const qrCodeSvg = ref("");
const twoFactorSecret = ref("");
const otpCode = ref("");
const verifyingOtp = ref(false);
const newlyGeneratedRecoveryCodes = ref<string[]>([]);

const enable2fa = async () => {
    is2faLoading.value = true;
    newlyGeneratedRecoveryCodes.value = [];
    try {
        const response = await apiClient.post("/me/2fa/enable");
        if (response.data?.status === 'success') {
            qrCodeSvg.value = response.data.data.qr_code_svg;
            twoFactorSecret.value = response.data.data.secret;
            if (response.data.data.recovery_codes) {
                newlyGeneratedRecoveryCodes.value = response.data.data.recovery_codes;
            }
        }
    } catch (e: any) {
        toast.error("Failed to enable 2FA.");
    } finally {
        is2faLoading.value = false;
    }
};

const verify2fa = async () => {
    verifyingOtp.value = true;
    try {
        await apiClient.post("/me/2fa/verify", { code: otpCode.value });
        toast.success("2FA has been successfully verified and enabled!");
        authStore.user.two_factor_confirmed = true;
        qrCodeSvg.value = "";
        otpCode.value = "";
    } catch (e: any) {
        toast.error(e.response?.data?.message || "Invalid OTP code.");
    } finally {
        verifyingOtp.value = false;
    }
};

const disable2fa = async () => {
    is2faLoading.value = true;
    try {
        await apiClient.post("/me/2fa/disable");
        toast.success("2FA has been disabled.");
        authStore.user.two_factor_confirmed = false;
    } catch (e: any) {
        toast.error("Failed to disable 2FA.");
    } finally {
        is2faLoading.value = false;
    }
};

const $tr = (key: string, def: string) => langStore.translate(key) || def;
</script>

<template>
 <div class="pb-6 font-sans">
 <div class="premium-card p-6 md:p-8 bg-card-bg border border-card-border">
 <div class="mb-6 flex items-center justify-between border-b border-card-border pb-4">
 <h2 class="text-lg font-normal text-main-text tracking-tight capitalize">{{ $tr('profile.securityCredentials', 'Security credentials') }}</h2>
 <span class="text-[11px] text-main-text/30 capitalize font-normal">{{ $tr('profile.authenticationSafe', 'Authentication safe') }}</span>
 </div>

 <form @submit.prevent="handleChangePassword" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
 <div class="md:col-span-2">
 <FormField
 v-model="passwordForm.current_password"
 :type="showCurrentPassword ? 'text' : 'password'"
 :label="$tr('profile.current_password', 'Current password')"
 placeholder="••••••••"
 :icon="Key"
 required
 :error="errors.current_password?.[0]"
 @input="errors.current_password = ''"
 class="font-normal">
 <template #trailing>
 <button
 type="button"
 @click="showCurrentPassword = !showCurrentPassword"
 class="p-1 px-2 text-main-text/20 hover:text-brand-blue transition-colors outline-none cursor-pointer">
 <Eye v-if="!showCurrentPassword" class="w-4 h-4" />
 <EyeOff v-else class="w-4 h-4" />
 </button>
 </template>
 </FormField>
 </div>

 <FormField
 v-model="passwordForm.password"
 :type="showPassword ? 'text' : 'password'"
 :label="$tr('profile.new_password', 'New password')"
 placeholder="••••••••"
 :icon="Lock"
 required
 :error="errors.password?.[0]"
 @input="errors.password = ''"
 class="font-normal">
 <template #trailing>
 <button
 type="button"
 @click="showPassword = !showPassword"
 class="p-1 px-2 text-main-text/20 hover:text-brand-blue transition-colors outline-none cursor-pointer">
 <Eye v-if="!showPassword" class="w-4 h-4" />
 <EyeOff v-else class="w-4 h-4" />
 </button>
 </template>
 </FormField>

 <FormField
 v-model="passwordForm.password_confirmation"
 :type="showConfirmPassword ? 'text' : 'password'"
 :label="$tr('profile.confirm_password', 'Confirm password')"
 placeholder="••••••••"
 :icon="Lock"
 required
 :error="errors.password_confirmation?.[0]"
 @input="errors.password_confirmation = ''"
 class="font-normal">
 <template #trailing>
 <button
 type="button"
 @click="showConfirmPassword = !showConfirmPassword"
 class="p-1 px-2 text-main-text/20 hover:text-brand-blue transition-colors outline-none cursor-pointer">
 <Eye v-if="!showConfirmPassword" class="w-4 h-4" />
 <EyeOff v-else class="w-4 h-4" />
 </button>
 </template>
 </FormField>

 <div class="md:col-span-2 flex justify-end pt-2">
 <Button
 type="submit"
 variant="primary"
 :loading="isChangingPassword"
 class="!h-10 px-8 w-full md:w-auto">
 <template #icon>
 <ShieldCheck class="w-4 h-4" />
 </template>
 <span class="capitalize">{{ $tr('profile.updatePassword', 'Update password') }}</span>
 </Button>
 </div>
 </form>

  </div>

  <!-- 2FA Section -->
  <div class="mt-6 premium-card p-6 md:p-8 bg-card-bg border border-card-border">
    <div class="mb-6 flex items-center justify-between border-b border-card-border pb-4">
      <h2 class="text-lg font-normal text-main-text tracking-tight capitalize">Two-Factor Authentication (2FA)</h2>
      <span class="text-[11px] text-main-text/30 capitalize font-normal">Add extra security to your account</span>
    </div>

    <!-- Active State -->
    <div v-if="authStore.user?.two_factor_confirmed" class="space-y-4">
      <div class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-500/5 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-600 shrink-0">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-main-text">2FA is Enabled</h3>
          <p class="text-xs text-main-text/60 mt-0.5">Your account is secured with Two-Factor Authentication.</p>
        </div>
      </div>
      <div class="flex justify-end pt-2">
        <Button variant="danger" @click="disable2fa" :loading="is2faLoading" class="!h-10 px-8 w-full md:w-auto">
          Disable 2FA
        </Button>
      </div>
    </div>

    <!-- Setup State -->
    <div v-else-if="qrCodeSvg" class="space-y-6">
      <div class="p-4 rounded-xl border border-card-border bg-main-bg text-sm text-main-text/80 leading-relaxed">
        Scan the QR code below with your authenticator app (e.g. Google Authenticator, Authy), then enter the 6-digit code to verify setup.
      </div>
      
      <div class="flex flex-col md:flex-row gap-8 items-start">
        <div class="p-4 bg-white rounded-xl shadow-sm mx-auto md:mx-0">
          <!-- The SVG is base64 encoded -->
          <img :src="`data:image/svg+xml;base64,${qrCodeSvg}`" alt="2FA QR Code" class="w-48 h-48" />
        </div>
        
        <div class="flex-1 w-full space-y-4">
          <FormField
            v-model="otpCode"
            type="text"
            label="Verification Code"
            placeholder="123456"
            :icon="Key"
            required
            class="font-normal"
          />
          
          <div class="flex gap-3 pt-2">
            <Button variant="secondary" @click="qrCodeSvg = ''" :disabled="verifyingOtp" class="!h-10 px-6 flex-1">
              Cancel
            </Button>
            <Button variant="primary" @click="verify2fa" :loading="verifyingOtp" class="!h-10 px-6 flex-1">
              Verify & Enable
            </Button>
          </div>
        </div>
      </div>

      <!-- Recovery Codes -->
      <div v-if="newlyGeneratedRecoveryCodes.length > 0" class="mt-6 p-5 border border-rose-200 bg-rose-50 rounded-xl">
        <h4 class="text-sm font-bold text-rose-800 flex items-center gap-2 mb-2">
          <AlertCircle class="w-4 h-4" /> Save your recovery codes
        </h4>
        <p class="text-xs text-rose-700/80 mb-4">
          Please copy and save these recovery codes in a secure location. You can use them to access your account if you lose your authenticator device.
        </p>
        <div class="grid grid-cols-2 gap-2 text-sm font-mono bg-white p-4 rounded-lg border border-rose-100">
          <div v-for="code in newlyGeneratedRecoveryCodes" :key="code" class="text-slate-800">
            {{ code }}
          </div>
        </div>
      </div>
    </div>

    <!-- Disabled State -->
    <div v-else class="space-y-4">
      <div class="p-4 rounded-xl border border-card-border bg-main-bg flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-main-text/5 flex items-center justify-center text-main-text/40 shrink-0">
          <AlertCircle class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-main-text">2FA is Not Enabled</h3>
          <p class="text-xs text-main-text/60 mt-0.5">Protect your account by enabling Two-Factor Authentication.</p>
        </div>
      </div>
      <div class="flex justify-end pt-2">
        <Button variant="primary" @click="enable2fa" :loading="is2faLoading" class="!h-10 px-8 w-full md:w-auto">
          Set up 2FA
        </Button>
      </div>
    </div>
  </div>
 </div>
</template>

<style scoped>
.premium-card {
 border-radius: 1.25rem;
}
</style>
