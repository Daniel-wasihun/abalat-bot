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
 </div>
</template>

<style scoped>
.premium-card {
 border-radius: 1.25rem;
}
</style>
