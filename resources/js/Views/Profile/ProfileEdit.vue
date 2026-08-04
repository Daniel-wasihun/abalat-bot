<script setup lang="ts">
import { ref, watch } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useToastStore } from "@/stores/toast";
import { useProfile } from "@/composables/useProfile";
import { User, Calendar, MapPin, Save, Pencil, Camera, Users } from "lucide-vue-next";
import FormField from "@/components/common/FormField.vue";
import FormSelect from "@/components/common/FormSelect.vue";
import Button from "@/components/common/Button.vue";
import { useLanguageStore } from "@/stores/languageStore";

const authStore = useAuthStore();
const toast = useToastStore();
const langStore = useLanguageStore();
const { userInitial, getProfileImage } = useProfile();

const localize = (val: any) => {
 if (!val) return "";
 return typeof val === "object"
 ? val[langStore.currentLanguage] || val["en"] || ""
 : val;
};

const profileForm = ref({
 name: localize(authStore.user?.name) || "",
 address: authStore.user?.info?.address || "",
 date_of_birth: authStore.user?.info?.date_of_birth || "",
 gender: authStore.user?.info?.gender || "",
});

const profilePicture = ref<File | null>(null);
const profilePictureUrl = ref<string | null>(null);
const isUpdatingProfile = ref(false);
const errors = ref<any>({});

watch(
 () => authStore.user,
 (newUser) => {
 if (newUser) {
 profileForm.value.name = localize(newUser.name) || "";
 profileForm.value.address = newUser.info?.address || "";
 profileForm.value.date_of_birth = newUser.info?.date_of_birth || "";
 profileForm.value.gender = newUser.info?.gender || "";
 }
 },
 { immediate: true },
);

const handleFileChange = (e: Event) => {
 const target = e.target as HTMLInputElement;
 if (target.files && target.files[0]) {
 const file = target.files[0];
 profilePicture.value = file;
 profilePictureUrl.value = URL.createObjectURL(file);
 }
};

const handleUpdateProfile = async () => {
 isUpdatingProfile.value = true;
 errors.value = {};

 const formData = new FormData();
 formData.append("name", profileForm.value.name);
 formData.append("address", profileForm.value.address);
 formData.append("date_of_birth", profileForm.value.date_of_birth);
 formData.append("gender", profileForm.value.gender);
 if (profilePicture.value) {
 formData.append("profile_picture", profilePicture.value);
 }

 try {
 await authStore.updateProfile(formData);

 profilePicture.value = null;
 } catch (error: any) {
 if (error.response?.status === 422) {
 errors.value = error.response.data.errors;
 } else {
 toast.error(langStore.translate("profile.update_failed"));
 }
 } finally {
 isUpdatingProfile.value = false;
 }
};
</script>

<template>
 <div class="pb-6 font-sans">
 <div class="premium-card p-6 md:p-8 bg-card-bg border border-card-border">
 <div class="mb-6 flex items-center justify-between border-b border-card-border pb-4">
 <h2 class="text-lg font-normal text-main-text tracking-tight capitalize">Identity settings</h2>
 <span class="text-[11px] text-main-text/30 capitalize font-normal">Confidential registry</span>
 </div>

 <form @submit.prevent="handleUpdateProfile" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
 <!-- Avatar Compact -->
 <div class="lg:col-span-3 flex flex-col items-center gap-4 py-2 border-r border-card-border/30 pr-4">
 <div class="relative group">
 <div class="w-32 h-32 rounded-3xl border border-card-border p-1 overflow-hidden bg-card-bg shadow-inner">
 <div class="w-full h-full rounded-2xl overflow-hidden bg-main-text/3 flex items-center justify-center">
 <img
 v-if="profilePictureUrl || getProfileImage(authStore.user?.info?.profile_picture)"
 :src="profilePictureUrl || getProfileImage(authStore.user?.info?.profile_picture)"
 class="w-full h-full object-cover" />
 <div v-else class="text-4xl font-light text-main-text/10">
 {{ userInitial }}
 </div>
 </div>
 </div>
 <label class="absolute -bottom-1 -right-1 cursor-pointer transition-transform hover:scale-110 active:scale-90">
 <div class="w-9 h-9 rounded-xl bg-brand-blue text-white shadow-lg flex items-center justify-center border-2 border-card-bg">
 <Camera class="w-4 h-4" />
 </div>
 <input
 type="file"
 class="hidden"
 @change="handleFileChange"
 accept="image/*" />
 </label>
 </div>
 <div class="text-center">
 <p class="text-[11px] font-normal text-main-text/30 capitalize">Profile photo</p>
 <p class="text-[9px] text-main-text/20 mt-1 capitalize">Max 5mb • jpg/png</p>
 </div>
 </div>

 <!-- Fields Grid -->
 <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
 <FormField
 v-model="profileForm.name"
 :label="$tr('common.name')"
 :placeholder="$tr('common.name')"
 :icon="User"
 required
 :error="errors.name?.[0]"
 @input="errors.name = ''"
 class="font-normal" />

 <FormField
 v-model="profileForm.date_of_birth"
 type="date"
 :label="$tr('user.date_of_birth')"
 :icon="Calendar"
 :error="errors.date_of_birth?.[0]"
 @input="errors.date_of_birth = ''"
 class="font-normal" />

 <FormSelect
 v-model="profileForm.gender"
 :label="$tr('user.gender')"
 :icon="Users"
 :options="[
 { label: $tr('user.male'), value: 'male' },
 { label: $tr('user.female'), value: 'female' },
 ]"
 required
 :error="errors.gender?.[0]"
 @change="errors.gender = ''" />

 <div class="md:col-span-2">
 <FormField
 v-model="profileForm.address"
 type="textarea"
 :label="$tr('user.address')"
 :icon="MapPin"
 :error="errors.address?.[0]"
 @input="errors.address = ''"
 class="font-normal" />
 </div>

 <div class="md:col-span-2 flex justify-end pt-2">
 <Button
 type="submit"
 variant="primary"
 :loading="isUpdatingProfile"
 class="!h-10 px-8 w-full md:w-auto">
 <template #icon>
 <Save class="w-4 h-4" />
 </template>
 <span class="capitalize">Save changes</span>
 </Button>
 </div>
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
