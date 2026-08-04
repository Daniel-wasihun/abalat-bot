import { ref, computed } from "vue";
import { useUserStore } from "@/stores/userStore";
import { useValidation } from "@/composables/useValidation";
import { useToastStore } from "@/stores/toast";
import { localize } from "@/utils/format";
import { UserCircle2, Briefcase, ShieldCheck, Save } from "lucide-vue-next";
import { createProfileSchema, roleSchema } from "../schemas/userSchemas";
import { useLanguageStore } from "@/stores/languageStore";

export function useUserEditLogic(props: { user: any }, emit: any, $tr: any) {
    const userStore = useUserStore();
    const toast = useToastStore();
    const languageStore = useLanguageStore();
    const { errors, validate, handleBackendErrors, clearErrors, clearFieldError, withParams } = useValidation();

    // ─── State ────────────────────────────────────────────────────────────────

    const profileForm = ref<Record<string, any>>({
        name: "",
        email: "",
        registration_id: "",
        phone_number: "",
        gender: "",
        date_of_birth: "",
        address: "",
        is_active: true,
        role: "",
    });

    const profilePictureFile = ref<File | null>(null);
    const profilePicturePreview = ref<string | null>(null);
    const removeProfilePicture = ref(false);

    const selectedRole = ref("");
    const roleStartDate = ref("");
    const roleEndDate = ref("");

    const selectedPermissions = ref<string[]>([]);
    const assignedPermissions = ref<string[]>([]);
    const permStartDate = ref("");
    const permEndDate = ref("");

    const activeTab = ref("profile");
    const showConfirmModal = ref(false);
    const confirmType = ref<"profile" | "role" | "permissions" | null>(null);
    const loading = ref(false);

    const showScheduleModal = ref(false);
    const editingSchedule = ref<any>(null);
    const scheduleForm = ref({ startDate: "", endDate: "" });

    // ─── Sync with user prop ──────────────────────────────────────────────────

    const syncState = (user: any) => {
        if (!user) {
            profileForm.value = {
                name: "",
                email: "",
                registration_id: "",
                phone_number: "",
                gender: "",
                date_of_birth: "",
                address: "",
                is_active: true,
                role: "",
            };
            selectedRole.value = "";
            selectedPermissions.value = [];
            profilePicturePreview.value = null;
            return;
        }

        profileForm.value = {
            name: localize(user.name, languageStore.currentLanguage) || "",
            email: user.email || "",
            registration_id: user.info?.registration_id || "",
            phone_number: user.info?.phone_number || "",
            gender: (user.info?.gender || "").toLowerCase(),
            date_of_birth: user.info?.date_of_birth || "",
            address: user.info?.address || "",
            is_active: user.is_active ?? true,
            role: "",
        };

        assignedPermissions.value = (user.permissions || []).map((p: any) =>
            typeof p === "string" ? p : p.slug,
        );
        selectedRole.value = user.role?.slug || "";
        selectedPermissions.value = [...assignedPermissions.value];
        profilePicturePreview.value = user.profile_picture || null;

        roleStartDate.value = "";
        roleEndDate.value = "";
        permStartDate.value = "";
        permEndDate.value = "";
    };

    // ─── Avatar helpers ───────────────────────────────────────────────────────

    const handleFileChange = (event: Event) => {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;
        profilePictureFile.value = file;
        profilePicturePreview.value = file ? URL.createObjectURL(file) : null;
        removeProfilePicture.value = false;
    };

    const handleRemoveAvatar = () => {
        profilePictureFile.value = null;
        profilePicturePreview.value = null;
        removeProfilePicture.value = true;
    };

    // ─── Submit flow ──────────────────────────────────────────────────────────

    const openConfirm = (type: "profile" | "role" | "permissions") => {
        if (type === "profile" && !validate(createProfileSchema(withParams), profileForm.value)) return;
        if (type === "role" && !validate(roleSchema, {
            role: selectedRole.value,
            startDate: roleStartDate.value,
            endDate: roleEndDate.value,
        })) return;

        // Skip confirmation dialog when creating a new user
        if (type === "profile" && !props.user) {
            confirmType.value = "profile";
            submitAction();
            return;
        }

        confirmType.value = type;
        showConfirmModal.value = true;
    };

    const submitAction = async () => {
        loading.value = true;
        try {
            if (confirmType.value === "profile") {
                const formData = new FormData();
                Object.entries(profileForm.value).forEach(([k, v]) => {
                    if (k === "is_active") {
                        formData.append(k, v ? "1" : "0");
                    } else if (v !== null && v !== undefined && v !== "") {
                        formData.append(k, String(v));
                    }
                });
                if (profilePictureFile.value)
                    formData.append("profile_picture", profilePictureFile.value);
                if (removeProfilePicture.value)
                    formData.append("remove_profile_picture", "1");

                if (props.user) {
                    await userStore.updateUser(props.user.id, formData);
                } else {
                    if (profileForm.value.role)
                        formData.append("role", profileForm.value.role);
                    await userStore.createUser(formData);
                    emit("close");
                }
            } else if (confirmType.value === "role") {
                await userStore.assignRole(
                    props.user.id,
                    selectedRole.value,
                    roleStartDate.value || undefined,
                    roleEndDate.value || undefined,
                );
            } else if (confirmType.value === "permissions") {
                const payload: Record<string, boolean> = {};
                userStore.allPermissions.forEach((p: any) => {
                    const wasActive = assignedPermissions.value.includes(p.slug);
                    const isSelected = selectedPermissions.value.includes(p.slug);
                    if (wasActive !== isSelected) payload[p.slug] = isSelected;
                });
                await userStore.syncPermissions(
                    props.user.id,
                    payload,
                    permStartDate.value || undefined,
                    permEndDate.value || undefined,
                );
            }

            showConfirmModal.value = false;
            emit("saved");
            clearErrors();
        } catch (error: any) {
            if (!handleBackendErrors(error)) {
                const message = error?.response?.data?.message || $tr("common.sync_failed");
                toast.error(message);
            }
        } finally {
            loading.value = false;
        }
    };

    // ─── Schedule helpers ─────────────────────────────────────────────────────

    const startEditSchedule = (item: any) => {
        editingSchedule.value = item;
        scheduleForm.value = {
            startDate: item.start_date ? item.start_date.split(" ")[0] : "",
            endDate: item.end_date ? item.end_date.split(" ")[0] : "",
        };
        showScheduleModal.value = true;
    };

    const updateSchedule = async () => {
        loading.value = true;
        try {
            const updateFn = editingSchedule.value.role_name
                ? userStore.updateScheduledRole
                : userStore.updateScheduledPermission;
            await updateFn(
                props.user.id,
                editingSchedule.value.id,
                scheduleForm.value.startDate,
                scheduleForm.value.endDate,
            );
            showScheduleModal.value = false;
            emit("saved");
        } catch (e: any) {
            toast.error(e?.response?.data?.message || $tr("common.sync_failed"));
        } finally {
            loading.value = false;
        }
    };

    const cancelSchedule = async (id: number, type: "role" | "permission") => {
        try {
            const cancelFn = type === "role"
                ? userStore.cancelScheduledRole
                : userStore.cancelScheduledPermission;
            await cancelFn(props.user.id, id);
            emit("saved");
        } catch (e: any) {
            toast.error(e?.response?.data?.message || $tr("common.sync_failed"));
        }
    };

    const togglePermission = (slug: string) => {
        const idx = selectedPermissions.value.indexOf(slug);
        if (idx === -1) selectedPermissions.value.push(slug);
        else selectedPermissions.value.splice(idx, 1);
    };

    // ─── Confirmation summary ─────────────────────────────────────────────────

    const confirmationData = computed(() => {
        if (!confirmType.value) return { title: "", description: "", summary: [], icon: Save };

        const res = {
            title: $tr("common.confirmation"),
            description: $tr("common.review_details"),
            summary: [] as any[],
            icon: Save as any,
        };

        if (confirmType.value === "profile") {
            res.title = $tr("user.update_profile") || "Update Profile";
            res.description = $tr("user.review_profile_changes") || "Review the changes to this user's information.";
            res.icon = UserCircle2;

            const originalName = props.user ? localize(props.user.name, languageStore.currentLanguage) : "";
            if (profileForm.value.name !== originalName)
                res.summary.push({ label: $tr("field.full_name") || "Full Name", value: profileForm.value.name });
            if (profileForm.value.email !== (props.user?.email || ""))
                res.summary.push({ label: $tr("field.email_address") || "Email", value: profileForm.value.email });
            if (profilePictureFile.value)
                res.summary.push({ label: $tr("field.profile_picture") || "Avatar", value: $tr("common.new_avatar_selected") || "New avatar selected" });

        } else if (confirmType.value === "role") {
            res.title = $tr("user.change_role") || "Change Role";
            res.description = $tr("user.review_role_assignment") || "You are about to modify this user's role.";
            res.icon = Briefcase;

            const role = userStore.allRoles.find((r) => r.slug === selectedRole.value);
            res.summary.push({
                label: $tr("field.target_role") || "Role",
                value: role ? localize(role.name, languageStore.currentLanguage) : selectedRole.value,
            });
            if (roleStartDate.value) res.summary.push({ label: $tr("field.effective_from") || "From", value: roleStartDate.value });
            if (roleEndDate.value)   res.summary.push({ label: $tr("field.effective_until") || "Until", value: roleEndDate.value });

        } else if (confirmType.value === "permissions") {
            res.title = $tr("user.sync_permissions") || "Sync Permissions";
            res.description = $tr("user.review_perm_changes") || "Updating custom permission overrides.";
            res.icon = ShieldCheck;

            const added   = selectedPermissions.value.filter((p) => !assignedPermissions.value.includes(p)).length;
            const removed = assignedPermissions.value.filter((p) => !selectedPermissions.value.includes(p)).length;
            res.summary.push({ label: $tr("common.permissions_added")   || "Added",   value: `+${added}` });
            res.summary.push({ label: $tr("common.permissions_removed") || "Removed", value: `-${removed}` });
            if (permStartDate.value) res.summary.push({ label: $tr("field.starts") || "Starts", value: permStartDate.value });
            if (permEndDate.value)   res.summary.push({ label: $tr("field.ends")   || "Ends",   value: permEndDate.value });
        }

        return res;
    });

    // ─── Expose ───────────────────────────────────────────────────────────────

    return {
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
        confirmType,
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
    };
}
