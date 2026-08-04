<script setup lang="ts">
import { Mail, Key, Lock, Eye, EyeOff, RotateCcw, XCircle } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import FormField from "@/components/common/FormField.vue";
import { ref } from "vue";

interface Props {
 show: boolean;
 step: number;
 email: string;
 otp: string;
 newPass: string;
 confirmPass: string;
 hpEmail: string;
 hpTimestamp: string;
 loading: boolean;
 errors: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits([
 "update:show",
 "update:email",
 "update:otp",
 "update:newPass",
 "update:confirmPass",
 "update:hpEmail",
 "update:hpTimestamp",
 "send-otp",
 "verify-otp",
 "reset-password",
 "clear-error",
]);

const showNewPass = ref(false);
const showConfirmPass = ref(false);

const close = () => {
 emit("update:show", false);
};
</script>

<template>
 <Modal
 :show="show"
 :title="$tr('auth.reset_password', 'Reset Password')"
 :icon="RotateCcw"
 size="md"
 @close="close">
 
 <!-- Honeypot fields -->
 <input
 type="text"
 :value="hpEmail"
 @input="
 $emit(
 'update:hpEmail',
 ($event.target as HTMLInputElement).value,
 )
 "
 class="hidden"
 tabindex="-1"
 autocomplete="off" />
 <input type="hidden" :value="hpTimestamp" />

 <div class="px-2 pt-2">
 <div v-if="step === 1" class="space-y-6">
 <div class="p-4 rounded-2xl bg-brand-blue/[0.03] border border-brand-blue/10">
 <p class="text-[13px] font-bold text-main-text/40 leading-relaxed capitalize tracking-wider">
 {{ $tr("auth.forgot_password_desc") }}
 </p>
 </div>
 
 <FormField
 :model-value="email"
 type="email"
 :label="$tr('auth.email_address', 'Email Address')"
 placeholder="name@example.com"
 :icon="Mail"
 required
 :error="errors.email"
 @update:model-value="
 $emit('clear-error', 'email');
 $emit('update:email', $event);
 " />
 </div>

 <div v-else-if="step === 2" class="space-y-6">
 <div class="p-4 rounded-2xl bg-brand-blue/[0.03] border border-brand-blue/10">
 <p class="text-[13px] font-bold text-main-text/40 leading-relaxed capitalize tracking-wider">
 {{ $tr("auth.enter_otp_sent_to", { email }) }}
 </p>
 </div>
 
 <FormField
 :model-value="otp"
 type="text"
 :label="$tr('auth.otp_code', 'OTP Code')"
 placeholder="123456"
 :icon="Key"
 required
 maxlength="6"
 class="text-center tracking-widest font-mono"
 :error="errors.otp"
 @update:model-value="
 $emit('clear-error', 'otp');
 $emit('update:otp', $event);
 " />
 </div>

 <div v-else-if="step === 3" class="space-y-6">
 <div class="p-4 rounded-2xl bg-brand-blue/[0.03] border border-brand-blue/10">
 <p class="text-[13px] font-bold text-main-text/40 leading-relaxed capitalize tracking-wider">
 {{ $tr("auth.create_new_password_desc") }}
 </p>
 </div>
 
 <div class="space-y-5">
 <FormField
 :model-value="newPass"
 :type="showNewPass ? 'text' : 'password'"
 :label="$tr('auth.new_password', 'New Password')"
 placeholder="••••••••"
 :icon="Lock"
 required
 :error="errors.password || errors.newPass"
 @update:model-value="
 $emit('clear-error', 'password');
 $emit('clear-error', 'newPass');
 $emit('update:newPass', $event);
 ">
 <template #trailing>
 <Button
 variant="ghost"
 size="sm"
 @click="showNewPass = !showNewPass"
 class="!p-0 !h-9 !w-9 !rounded-lg text-main-text/30 hover:!text-brand-blue hover:!bg-brand-blue/10">
 <Eye v-if="!showNewPass" class="w-5 h-5" />
 <EyeOff v-else class="w-5 h-5" />
 </Button>
 </template>
 </FormField>

 <FormField
 :model-value="confirmPass"
 :type="showConfirmPass ? 'text' : 'password'"
 :label="$tr('auth.confirm_password', 'Confirm Password')"
 placeholder="••••••••"
 :icon="Lock"
 required
 :error="errors.password_confirmation || errors.confirmPass"
 @update:model-value="
 $emit('clear-error', 'password_confirmation');
 $emit('clear-error', 'confirmPass');
 $emit('update:confirmPass', $event);
 ">
 <template #trailing>
 <Button
 variant="ghost"
 size="sm"
 @click="showConfirmPass = !showConfirmPass"
 class="!p-0 !h-9 !w-9 !rounded-lg text-main-text/30 hover:!text-brand-blue hover:!bg-brand-blue/10">
 <Eye v-if="!showConfirmPass" class="w-5 h-5" />
 <EyeOff v-else class="w-5 h-5" />
 </Button>
 </template>
 </FormField>
 </div>
 </div>
 </div>

 <template #footer>
 <div class="px-6 py-4 flex items-center justify-end gap-3 bg-card-bg/30 border-t border-card-border/50">
 <Button variant="secondary" @click="close">
 {{ $tr("common.abort", "Abort") }}
 </Button>
 
 <Button 
 v-if="step === 1"
 @click="$emit('send-otp')" 
 variant="primary"
 :loading="loading"
 class="px-8 ">
 {{ $tr("auth.send_otp", "Send OTP") }}
 </Button>

 <Button 
 v-else-if="step === 2"
 @click="$emit('verify-otp')" 
 variant="primary"
 :loading="loading"
 class="px-8 ">
 {{ $tr("auth.verify_otp", "Verify OTP") }}
 </Button>

 <Button 
 v-else-if="step === 3"
 @click="$emit('reset-password')" 
 variant="primary"
 :loading="loading"
 class="px-8 ">
 {{ $tr("auth.reset_password", "Reset Password") }}
 </Button>
 </div>
 </template>
 </Modal>
</template>
