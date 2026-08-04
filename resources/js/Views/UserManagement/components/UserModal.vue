<script setup lang="ts">
import { onMounted, watch, getCurrentInstance, computed } from "vue";
import { UserCircle2, ShieldCheck, Key } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import { usePermissions } from "@/composables/usePermissions";
import { Modules } from "@/constants/permissions";

// Modular Imports
import { useUserEditLogic } from "../composables/useUserEditLogic";
import UserEditNavigation from "./UserEditTabs/UserEditNavigation.vue";
import ProfileTab from "./UserEditTabs/ProfileTab.vue";
import RoleTab from "./UserEditTabs/RoleTab.vue";
import PermissionsTab from "./UserEditTabs/PermissionsTab.vue";
import ConfirmActionModal from "@/components/common/ConfirmActionModal.vue";
import ScheduleModal from "@/components/common/ScheduleModal.vue";

import { useLanguageStore } from "@/stores/languageStore";

const props = defineProps<{
 show: boolean;
 user: any;
}>();

const emit = defineEmits(["close", "saved"]);
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
const languageStore = useLanguageStore();

// Unified Logic
const {
 profileForm,
 profilePicturePreview,
 selectedRole,
 roleStartDate,
 roleEndDate,
 selectedPermissions,
 permStartDate,
 permEndDate,
 activeTab,
 showConfirmModal,
 loading,
 showScheduleModal,
 scheduleForm,
 errors,
 syncState,
 handleFileChange,
 handleRemoveAvatar,
 openConfirm,
 submitAction,
 startEditSchedule,
 updateSchedule,
 cancelSchedule,
 togglePermission,
 clearFieldError,
 confirmationData,
} = useUserEditLogic(props, emit, $tr);

// Permission-based Tab Visibility
const { canEdit: canEditProfile } = usePermissions(Modules.USERS);
const { canEdit: canEditRoles } = usePermissions(Modules.ROLES);
const { canEdit: canEditPermissions } = usePermissions(Modules.PERMISSIONS);

const tabs = computed(() =>
 [
 {
 id: "profile",
 label: $tr("user.profile"),
 icon: UserCircle2,
 visible: canEditProfile.value,
 },
 {
 id: "role",
 label: $tr("role.role"),
 icon: ShieldCheck,
 visible: canEditRoles.value,
 },
 {
 id: "permissions",
 label: $tr("nav.permissions"),
 icon: Key,
 visible: canEditPermissions.value,
 },
 ].filter((t) => t.visible),
);

// Sync state on component mount and prop changes
onMounted(() => syncState(props.user));
watch(
 () => props.show,
 (s) => s && syncState(props.user),
);
watch(
 () => props.user,
 (u) => syncState(u),
 { deep: true },
);
watch(
 () => languageStore.currentLanguage,
 () => {
 if (props.show) syncState(props.user);
 },
);
</script>

<template>
 <Modal
 :show="show"
 @close="emit('close')"
 size="xl"
 :title="props.user ? $tr('user.edit_member') : $tr('user.new_registration')"
 :icon="UserCircle2"
 noPadding>
 
 <div class="flex flex-col overflow-x-hidden">
 <!-- Navigation Tabs -->
 <UserEditNavigation
 v-if="props.user"
 class="px-8 border-b border-card-border/40 bg-card-bg/30"
 :tabs="tabs"
 v-model:activeTab="activeTab" />

 <!-- Tab Content -->
 <div class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar px-8">
 <Transition name="page" mode="out-in">
 <div :key="activeTab">
 <ProfileTab
 v-if="activeTab === 'profile'"
 :user="user"
 :profileForm="profileForm"
 :profilePicturePreview="profilePicturePreview"
 :errors="errors"
 @file-change="handleFileChange"
 @remove-avatar="handleRemoveAvatar"
 @clear-field-error="clearFieldError"
 @open-confirm="openConfirm"
 @close="emit('close')" />

 <RoleTab
 v-else-if="activeTab === 'role'"
 :user="user"
 v-model:selectedRole="selectedRole"
 v-model:roleStartDate="roleStartDate"
 v-model:roleEndDate="roleEndDate"
 :errors="errors"
 @open-confirm="openConfirm"
 @edit-schedule="startEditSchedule"
 @delete-schedule="cancelSchedule"
 @close="emit('close')" />

 <PermissionsTab
 v-else-if="activeTab === 'permissions'"
 :user="user"
 v-model:selectedPermissions="selectedPermissions"
 v-model:permStartDate="permStartDate"
 v-model:permEndDate="permEndDate"
 @toggle-permission="togglePermission"
 @open-confirm="openConfirm"
 @edit-schedule="startEditSchedule"
 @delete-schedule="cancelSchedule"
 @close="emit('close')" />
 </div>
 </Transition>
 </div>
 </div>

 <!-- Global Modals (Confirmation & Scheduling) -->
 <ConfirmActionModal
 :show="showConfirmModal"
 :title="confirmationData.title"
 :description="confirmationData.description"
 :summary="confirmationData.summary"
 :icon="confirmationData.icon"
 :loading="loading"
 @close="showConfirmModal = false"
 @confirm="submitAction" />

 <ScheduleModal
 :show="showScheduleModal"
 :form="scheduleForm"
 :submitting="loading"
 @close="showScheduleModal = false"
 @submit="updateSchedule" />
 </Modal>
</template>
