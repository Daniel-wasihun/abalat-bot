<script setup lang="ts">
/**
 * ProfileTab
 * Core user profile editing interface.
 * Fully migrated to the premium 'Elite' component suite.
 */
import { computed, getCurrentInstance } from "vue";
import {
 UserCircle2,
 Camera,
 Trash2,
 Save,
 Calendar,
 Check,
 X,
 Pencil,
 Users,
 Shield,
 GraduationCap,
 User,
 Library,
 Phone,
 Mail,
 UserSquare2,
 MapPin,
 XCircle,
 Info,
} from "lucide-vue-next";
import FormSelect from "@/components/common/FormSelect.vue";
import Button from "@/components/common/Button.vue";
import FormField from "@/components/common/FormField.vue";
import FormToggle from "@/components/common/FormToggle.vue";
import FormRadioGroup from "@/components/common/FormRadioGroup.vue";
import { useUserStore } from "@/stores/userStore";
import { useLanguageStore } from "@/stores/languageStore";
import { storeToRefs } from "pinia";
import { useSecurity } from "@/composables/useSecurity";
import { localize } from "@/utils/format";

const { isSuperAdmin: iAmSuperAdmin } = useSecurity();

const props = defineProps<{
 user: any;
 profileForm: any;
 profilePicturePreview: string | null;
 errors: any;
}>();

const emit = defineEmits([
 "file-change",
 "remove-avatar",
 "open-confirm",
 "clear-field-error",
 "close",
]);

const userStore = useUserStore();
const languageStore = useLanguageStore();
const { currentLanguage } = storeToRefs(languageStore);

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const handleFileChange = (e: any) => emit("file-change", e);
const handleRemoveAvatar = () => emit("remove-avatar");
const clearFieldError = (field: string) => emit("clear-field-error", field);
const openConfirm = () => emit("open-confirm", "profile");

const userTypeOptions = computed<{ label: string; value: any; icon: any }[]>(
 () => [
 { label: String($tr("filter.select_type")), value: "", icon: Users },
 ...Object.entries(userStore.userTypes).map(([val, label]) => ({
 label: localize(label, currentLanguage.value) as string,
 value: val,
 icon:
 val === "student"
 ? User
 : val === "teacher"
 ? GraduationCap
 : Users,
 })),
 ],
);



const roleOptions = computed<{ label: string; value: any; icon: any }[]>(() => [
 { label: String($tr("filter.select_role")), value: "", icon: Shield },
 ...userStore.allRoles.map((role: any) => ({
 label: localize(role.name, currentLanguage.value) as string,
 value: role.slug,
 icon: Shield,
 })),
]);



const genderOptions = computed(() => [
 { value: 'male', label: $tr('user.male') },
 { value: 'female', label: $tr('user.female') },
]);
</script>

<template>
 <div class="space-y-6 pt-2 pb-6 overflow-x-hidden">
 <div class="flex flex-col md:flex-row items-start gap-8">
 <!-- Compact Avatar Section -->
 <div class="relative group/avatar shrink-0 mx-auto md:mx-0">
 <div
 class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-card-border shadow-soft relative bg-main-bg transition-all duration-300 group-hover/avatar:border-brand-blue/30 scale-100 group-hover/avatar:scale-[1.02]">
 <img
 v-if="profilePicturePreview"
 :src="profilePicturePreview"
 class="w-full h-full object-cover" />
 <div
 v-else
 class="w-full h-full flex items-center justify-center bg-brand-blue/5">
 <UserCircle2 class="w-12 h-12 text-brand-blue/30" />
 </div>
 </div>
 <!-- Compact Upload Label -->
 <label
 class="absolute -bottom-1 -right-1 cursor-pointer z-30 group/upload translate-x-1 translate-y-1">
 <div
 class="w-9 h-9 rounded-xl bg-white dark:bg-card-bg border-2 border-slate-100 dark:border-card-border shadow-md flex items-center justify-center text-brand-blue transition-all duration-300 group-hover/upload:bg-brand-blue group-hover/upload:text-white">
 <Camera class="w-4 h-4 stroke-[2.5px]" />
 </div>
 <input
 type="file"
 @change="handleFileChange"
 class="hidden"
 accept="image/*" />
 </label>
 <Button
 v-if="profilePicturePreview"
 variant="soft-danger"
 size="sm"
 :icon="Trash2"
 @click="handleRemoveAvatar"
 class="absolute -top-2 -right-2 !w-8 !h-8 !p-0 rounded-full shadow-md hover:scale-110 !min-h-0" />
 </div>

 <!-- Basic Identity Info -->
 <div class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full text-left self-center">
 <FormField
 v-model="profileForm.name"
 :label="$tr('filter.name')"
 :placeholder="$tr('filter.name')"
 required
 :icon="User"
 :error="errors.name"
 @input="clearFieldError('name')" />

 <FormField
 v-model="profileForm.email"
 type="email"
 :label="$tr('auth.email')"
 :placeholder="$tr('auth.email')"
 required
 :icon="Mail"
 :error="errors.email"
 @input="clearFieldError('email')" />

 <FormField
 v-model="profileForm.user_university_id"
 :label="$tr('user.id_number')"
 :placeholder="$tr('user.university_id')"
 required
 :icon="UserSquare2"
 :error="errors.user_university_id"
 @input="clearFieldError('user_university_id')" />

 <FormSelect v-if="!user"
 v-model="profileForm.role"
 :options="roleOptions"
 :icon="Shield"
 :label="$tr('role.role')" />
 </div>
 </div>

 <div class="w-full h-px bg-card-border/60"></div>

 <!-- Secondary Information Grid -->
 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-x-6 gap-y-5 text-left items-end">
 <!-- Row 1: Organizational -->
 <div class="lg:col-span-4">
 <FormSelect
 v-model="profileForm.user_type"
 :options="userTypeOptions"
 :label="$tr('user.user_type')"
 required
 :icon="Users"
 :error="errors.user_type"
 @change="clearFieldError('user_type')" />
 </div>



 <div class="lg:col-span-4">
 <FormRadioGroup
 v-model="profileForm.gender"
 :label="$tr('user.gender')"
 :options="genderOptions"
 :error="errors.gender"
 required
 inline
 @change="clearFieldError('gender')" />
 </div>

 <!-- Row 2: Contact & Metadata -->
 <div class="lg:col-span-4">
 <FormField
 v-model="profileForm.phone_number"
 :label="$tr('user.phone_number')"
 placeholder="9XXXXXXXX"
 maxlength="9"
 :icon="Phone"
 :error="errors.phone_number"
 @input="clearFieldError('phone_number')">
 <template #prepend>
 <div class="px-3 flex items-center text-[12px] font-bold text-main-text/30 border-r border-card-border/60">
 +251
 </div>
 </template>
 </FormField>
 </div>



 <div class="lg:col-span-4">
 <FormField
 v-model="profileForm.date_of_birth"
 type="date"
 :label="$tr('user.date_of_birth')"
 :icon="Calendar"
 :error="errors.date_of_birth"
 @input="clearFieldError('date_of_birth')" />
 </div>

 <!-- Row 3: Status & Address -->
 <div class="lg:col-span-3">
 <FormToggle
 v-model="profileForm.is_active"
 :label="$tr('common.status')"
 labelPosition="top"
 :description="profileForm.is_active ? $tr('user.active') : $tr('user.inactive')"
 :icon="profileForm.is_active ? Check : XCircle" />
 </div>

 <div class="lg:col-span-9">
 <FormField
 v-model="profileForm.address"
 :label="$tr('user.address')"
 :placeholder="$tr('user.address')"
 :icon="MapPin"
 :error="errors.address"
 @input="clearFieldError('address')" />
 </div>
 </div>

 <!-- Action Buttons -->
 <div class="flex items-center justify-end gap-3 pt-6 border-t border-card-border/60">
 <Button
 variant="ghost"
 @click="emit('close')"
 class="hover:bg-main-text/5">
 {{ $tr('action.cancel') }}
 </Button>
 <Button
 variant="primary"
 :icon="Save"
 class=""
 @click="openConfirm">
 {{ props.user ? $tr('common.update') : $tr('common.save') }}
 </Button>
 </div>
 </div>
</template>
