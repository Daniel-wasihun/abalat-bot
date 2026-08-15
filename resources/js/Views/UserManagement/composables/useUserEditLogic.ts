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
        role: "",
        roles: [],
        father_name: "",
        grandfather_name: "",
        christian_name: "",
        spiritual_father_name: "",
        sub_city: "",
        woreda: "",
        house_number: "",
        is_member: false,
        senbet_date_of_birth: "",
        education_level: "",
        emergency_name: "",
        emergency_phone: "",
        emergency_sub_city: "",
        emergency_woreda: "",
        emergency_house_number: "",
        emergency_address: "",
        senbet_class: "",
        section: "",
        previous_participation: false,
        previous_participation_document: null as File | null,
    });

    const profilePictureFile = ref<File | null>(null);
    const profilePicturePreview = ref<string | null>(null);
    const removeProfilePicture = ref(false);

    const selectedRoles = ref<string[]>([]);
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
                roles: [],
                father_name: "",
                grandfather_name: "",
                christian_name: "",
                spiritual_father_name: "",
                sub_city: "",
                woreda: "",
                house_number: "",
                is_member: false,
                senbet_date_of_birth: "",
                education_level: "",
                emergency_name: "",
                emergency_phone: "",
                emergency_sub_city: "",
                emergency_woreda: "",
                emergency_house_number: "",
                emergency_address: "",
                senbet_class: "",
                previous_participation: false,
                previous_participation_document: null,
            };
            selectedRoles.value = [];
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
            address: user.info?.address || "",
            father_name: user.info?.father_name || "",
            grandfather_name: user.info?.grandfather_name || "",
            christian_name: user.info?.christian_name || "",
            spiritual_father_name: user.info?.spiritual_father_name || "",
            sub_city: user.info?.sub_city || "",
            woreda: user.info?.woreda || "",
            house_number: user.info?.house_number || "",
            is_active: user.is_active ?? true,
            role: "", // keep for fallback if any component strictly needs it, though we use roles now
            roles: user.roles?.map((r: any) => r.slug) || [],
            is_member: !!user.senbetMembership,
            senbet_date_of_birth: user.senbetMembership?.date_of_birth || "",
            education_level: user.senbetMembership?.education_level || "",
            emergency_name: user.senbetMembership?.emergency_name || "",
            emergency_phone: user.senbetMembership?.emergency_phone || "",
            emergency_sub_city: user.senbetMembership?.emergency_sub_city || "",
            emergency_woreda: user.senbetMembership?.emergency_woreda || "",
            emergency_house_number: user.senbetMembership?.emergency_house_number || "",
            emergency_address: user.senbetMembership?.emergency_address || "",
            senbet_class: user.senbetMembership?.senbet_class || "",
            section: user.senbetMembership?.section || "",
            previous_participation: !!user.senbetMembership?.previous_participation,
            previous_participation_document: null, // Don't load file objects from string path
        };

        assignedPermissions.value = (user.permissions || []).map((p: any) =>
            typeof p === "string" ? p : p.slug,
        );
        selectedRoles.value = profileForm.value.roles;
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
        if (type === "profile" && !validate(createProfileSchema(), profileForm.value)) return;
        if (type === "role" && !validate(roleSchema, {
            roles: selectedRoles.value,
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
                const form = profileForm.value;

                // --- Core user fields ---
                const stringFields = [
                    "name", "email", "registration_id", "phone_number", "gender",
                    "address", "father_name", "grandfather_name", "christian_name",
                    "spiritual_father_name", "sub_city", "woreda", "house_number",
                ];
                stringFields.forEach((k) => {
                    if (form[k] !== null && form[k] !== undefined) {
                        formData.append(k, String(form[k]));
                    }
                });

                // --- Boolean fields ---
                formData.append("is_active", form.is_active ? "1" : "0");
                formData.append("is_member", form.is_member ? "1" : "0");
                formData.append("previous_participation", form.previous_participation ? "1" : "0");

                // --- Roles (array) ---
                const roles: string[] = Array.isArray(form.roles) ? form.roles : [];
                roles.forEach((r) => formData.append("roles[]", r));

                // --- Senbet membership fields (only if is_member) ---
                if (form.is_member) {
                    const memberFields = [
                        "senbet_date_of_birth", "education_level", "senbet_class",
                        "emergency_name", "emergency_phone",
                        "emergency_sub_city", "emergency_woreda", "emergency_house_number",
                        "emergency_address",
                    ];
                    memberFields.forEach((k) => {
                        if (form[k] !== null && form[k] !== undefined && form[k] !== "") {
                            formData.append(k, String(form[k]));
                        }
                    });

                    // Participation document (File object)
                    if (form.previous_participation && form.previous_participation_document instanceof File) {
                        formData.append("previous_participation_document", form.previous_participation_document);
                    }
                }

                // --- Profile picture ---
                if (profilePictureFile.value) {
                    formData.append("profile_picture", profilePictureFile.value);
                }
                if (removeProfilePicture.value) {
                    formData.append("remove_profile_picture", "1");
                }

                if (props.user) {
                    await userStore.updateUser(props.user.id, formData);
                } else {
                    await userStore.createUser(formData);
                    emit("close");
                }
            } else if (confirmType.value === "role") {
            const currentRoles = (props.user?.roles || []).map((r: any) => typeof r === 'string' ? r : r.slug);
            const isRemoving = false; // Logic needs adaptation if multiple
            const isAdding = selectedRoles.value.length > 0;
            const hasChange = isAdding || isRemoving;
            await userStore.assignRole(
                    props.user.id,
                    selectedRoles.value,
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

            const roles = userStore.allRoles.filter((r) => selectedRoles.value.includes(r.slug));
            res.summary.push({
                label: $tr("field.target_role") || "Roles",
                value: roles.length > 0 ? roles.map(r => localize(r.name, languageStore.currentLanguage)).join(', ') : selectedRoles.value.join(', '),
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
        selectedRoles,
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
