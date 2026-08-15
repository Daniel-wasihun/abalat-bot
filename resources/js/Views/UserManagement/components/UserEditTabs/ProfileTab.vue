<script setup lang="ts">
import { computed, ref, onMounted, getCurrentInstance, watch } from "vue";
import {
    UserCircle2, Camera, Trash2, Save, Calendar,
    Check, User, Phone, Mail, UserSquare2,
    MapPin, XCircle, Briefcase, Home, BookOpen,
    AlertCircle, ChevronRight,
} from "lucide-vue-next";
import Button from "@/components/common/Button.vue";
import FormField from "@/components/common/FormField.vue";
import FormToggle from "@/components/common/FormToggle.vue";
import FormRadioGroup from "@/components/common/FormRadioGroup.vue";
import FormSelect from "@/components/common/FormSelect.vue";
import { useUserStore } from "@/stores/userStore";
import { useLanguageStore } from "@/stores/languageStore";
import { storeToRefs } from "pinia";
import { localize } from "@/utils/format";
import apiClient from "@/api/apiClient";

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

const genderOptions = computed(() => [
    { value: "male",   label: $tr("user.form.male")   || "Male"   },
    { value: "female", label: $tr("user.form.female") || "Female" },
]);

// Load classes from configuration API
const configClasses = ref<Array<{code: string; name: string; number_of_sections: number}>>([]);
onMounted(async () => {
    try {
        const { data } = await apiClient.get('/academic/config/classes');
        configClasses.value = (data.classes || []).map((c: any) => ({ code: c.code, name: c.name, number_of_sections: c.number_of_sections || 1 }));
    } catch {
        // Fall back to defaults
        configClasses.value = [
            { code: 'child', name: 'Child', number_of_sections: 1 },
            ...Array.from({ length: 12 }, (_, i) => ({ code: String(i + 1), name: `Grade ${i + 1}`, number_of_sections: 1 })),
            { code: 'post_12', name: 'Post-12', number_of_sections: 1 },
        ];
    }
});

const classOptions = computed(() =>
    configClasses.value.map(c => ({ value: c.code, label: c.name }))
);

const sectionOptions = computed(() => {
    if (!props.profileForm?.senbet_class) return [];
    const cls = configClasses.value.find(c => c.code === props.profileForm.senbet_class);
    if (!cls || !cls.number_of_sections) return [];
    
    // Generate sections: 1 -> '1', 2 -> '1', '2', etc.
    return Array.from({ length: cls.number_of_sections }, (_, i) => {
        const sec = String(i + 1);
        return { value: sec, label: `Section ${sec}` };
    });
});

watch(() => props.profileForm?.senbet_class, (newClass) => {
    if (newClass && sectionOptions.value.length === 1) {
        // Auto-select if only 1 section exists
        props.profileForm.section = sectionOptions.value[0].value;
    } else if (props.profileForm?.section) {
        // Clear selected section if it doesn't exist in the new class's options
        const isValidSection = sectionOptions.value.some(opt => opt.value === props.profileForm.section);
        if (!isValidSection) {
            props.profileForm.section = '';
        }
    }
});

const isStudentOnly = computed(() => {
    const roles: string[] = Array.isArray(props.profileForm?.roles) ? props.profileForm.roles : [];
    return roles.length === 1 && roles[0] === "student";
});
</script>

<template>
    <div class="space-y-0 pb-6 overflow-x-hidden">

        <!-- ══ SECTION 1: IDENTITY & PROFILE PICTURE ══ -->
        <div class="px-1 pt-4 pb-6">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 bg-brand-blue rounded-full"></div>
                <h3 class="text-sm font-semibold text-main-text/60 uppercase tracking-wider">
                    {{ $tr('user.section.identity') || 'Identity' }}
                </h3>
            </div>

            <div class="flex flex-col md:flex-row gap-6 items-start">
                <!-- Profile Picture -->
                <div class="relative group/avatar shrink-0 mx-auto md:mx-0">
                    <div class="w-28 h-28 rounded-2xl overflow-hidden border-2 border-card-border shadow-soft relative bg-main-bg transition-all duration-300 group-hover/avatar:border-brand-blue/30">
                        <img v-if="profilePicturePreview" :src="profilePicturePreview" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center bg-brand-blue/5">
                            <UserCircle2 class="w-14 h-14 text-brand-blue/30" />
                        </div>
                    </div>
                    <label class="absolute -bottom-1 -right-1 cursor-pointer z-30">
                        <div class="w-9 h-9 rounded-xl bg-white dark:bg-card-bg border-2 border-slate-100 dark:border-card-border shadow-md flex items-center justify-center text-brand-blue transition-all duration-300 hover:bg-brand-blue hover:text-white">
                            <Camera class="w-4 h-4 stroke-[2.5px]" />
                        </div>
                        <input type="file" @change="handleFileChange" class="hidden" accept="image/*" />
                    </label>
                    <Button v-if="profilePicturePreview" variant="soft-danger" size="sm" :icon="Trash2"
                        @click="handleRemoveAvatar"
                        class="absolute -top-2 -right-2 !w-8 !h-8 !p-0 rounded-full shadow-md hover:scale-110 !min-h-0" />
                </div>

                <!-- Identity fields -->
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4 text-left w-full">
                    <FormField
                        v-model="profileForm.name"
                        :label="$tr('user.form.full_name') || 'Full Name'"
                        :placeholder="$tr('user.form.full_name_placeholder') || 'e.g. Daniel Aklilu'"
                        required :icon="User" :error="errors.name" @input="clearFieldError('name')" />

                    <FormField
                        v-model="profileForm.email"
                        type="email"
                        :label="$tr('user.form.email') || 'Email Address'"
                        :placeholder="$tr('user.form.email_placeholder') || 'user@example.com'"
                        :required="!isStudentOnly" :icon="Mail" :error="errors.email" @input="clearFieldError('email')">
                        <template #hint v-if="isStudentOnly">
                            <span class="text-xs text-amber-500">{{ $tr('user.form.optional_student') || 'Optional for student-only accounts' }}</span>
                        </template>
                    </FormField>

                    <FormField
                        v-model="profileForm.father_name"
                        :label="$tr('user.form.father_name') || `Father's Name`"
                        :placeholder="$tr('user.form.father_name_placeholder') || `Father's full name`"
                        required :icon="User" :error="errors.father_name" @input="clearFieldError('father_name')" />

                    <FormField
                        v-model="profileForm.grandfather_name"
                        :label="$tr('user.form.grandfather_name') || `Grandfather's Name`"
                        :placeholder="$tr('user.form.grandfather_name_placeholder') || `Grandfather's full name`"
                        required :icon="User" :error="errors.grandfather_name" @input="clearFieldError('grandfather_name')" />

                    <FormField
                        v-model="profileForm.christian_name"
                        :label="$tr('user.form.christian_name') || 'Christian Name (ስም)'"
                        :placeholder="$tr('user.form.christian_name_placeholder') || 'Baptismal / Christian name'"
                        :icon="User" :error="errors.christian_name" @input="clearFieldError('christian_name')" />

                    <FormField
                        v-model="profileForm.spiritual_father_name"
                        :label="$tr('user.form.spiritual_father') || 'Spiritual Father'"
                        :placeholder="$tr('user.form.spiritual_father_placeholder') || `Spiritual father's name`"
                        :icon="User" :error="errors.spiritual_father_name" @input="clearFieldError('spiritual_father_name')" />

                    <FormField
                        v-if="props.user"
                        v-model="profileForm.registration_id"
                        :label="$tr('user.form.registration_id') || 'Registration ID'"
                        placeholder="DBSS000001"
                        disabled :icon="UserSquare2" :error="errors.registration_id" />

                    <FormRadioGroup
                        v-model="profileForm.gender"
                        :label="$tr('user.form.gender') || 'Gender'"
                        :options="genderOptions"
                        :error="errors.gender"
                        required inline
                        @change="clearFieldError('gender')" />
                </div>
            </div>
        </div>

        <div class="w-full h-px bg-card-border/40"></div>

        <!-- ══ SECTION 2: ROLE SELECTION (new user only) ══ -->
        <div v-if="!user" class="px-1 py-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                <h3 class="text-sm font-semibold text-main-text/60 uppercase tracking-wider">
                    {{ $tr('user.section.roles') || 'Roles' }}
                </h3>
                <span class="text-xs text-rose-500 font-medium ml-1">{{ $tr('user.form.roles_required') || '*required' }}</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <label v-for="role in userStore.allRoles" :key="role.slug"
                    :class="[
                        'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all duration-200 select-none',
                        profileForm.roles.includes(role.slug)
                            ? 'bg-brand-blue/10 border-brand-blue text-brand-blue'
                            : 'bg-card-bg/30 border-card-border/60 text-main-text/70 hover:border-brand-blue/40'
                    ]">
                    <div :class="[
                        'w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all',
                        profileForm.roles.includes(role.slug) ? 'bg-brand-blue border-brand-blue' : 'border-card-border'
                    ]">
                        <Check v-if="profileForm.roles.includes(role.slug)" class="w-2.5 h-2.5 text-white" />
                    </div>
                    <input type="checkbox" :value="role.slug" v-model="profileForm.roles" class="sr-only" @change="clearFieldError('roles')" />
                    <span class="text-sm font-medium capitalize">{{ localize(role.name, currentLanguage) || role.slug }}</span>
                </label>
            </div>
            <p v-if="errors.roles" class="text-xs text-rose-500 mt-2 flex items-center gap-1">
                <AlertCircle class="w-3 h-3" /> {{ errors.roles }}
            </p>
        </div>

        <div v-if="!user" class="w-full h-px bg-card-border/40"></div>

        <!-- ══ SECTION 3: CONTACT & ADDRESS ══ -->
        <div class="px-1 py-6">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                <h3 class="text-sm font-semibold text-main-text/60 uppercase tracking-wider">
                    {{ $tr('user.section.contact_address') || 'Contact & Address' }}
                </h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-left">
                <FormField
                    v-model="profileForm.phone_number"
                    :label="$tr('user.form.phone_number') || 'Phone Number'"
                    :placeholder="$tr('user.form.phone_placeholder') || '9XXXXXXXX'"
                    maxlength="9" required :icon="Phone" :error="errors.phone_number" @input="clearFieldError('phone_number')">
                    <template #prepend>
                        <div class="px-3 flex items-center text-[12px] font-bold text-main-text/30 border-r border-card-border/60">+251</div>
                    </template>
                </FormField>

                <FormField
                    v-model="profileForm.sub_city"
                    :label="$tr('user.form.sub_city') || 'Sub City'"
                    :placeholder="$tr('user.form.sub_city_placeholder') || 'e.g. Bole'"
                    :icon="MapPin" :error="errors.sub_city" @input="clearFieldError('sub_city')" />

                <FormField
                    v-model="profileForm.woreda"
                    :label="$tr('user.form.woreda') || 'Woreda'"
                    :placeholder="$tr('user.form.woreda_placeholder') || 'e.g. 03'"
                    :icon="MapPin" :error="errors.woreda" @input="clearFieldError('woreda')" />

                <FormField
                    v-model="profileForm.house_number"
                    :label="$tr('user.form.house_number') || 'House Number'"
                    :placeholder="$tr('user.form.house_number_placeholder') || 'e.g. 123'"
                    :icon="Home" :error="errors.house_number" @input="clearFieldError('house_number')" />

                <div class="md:col-span-2">
                    <FormField
                        v-model="profileForm.address"
                        :label="$tr('user.form.address') || 'Additional Address / Description'"
                        :placeholder="$tr('user.form.address_placeholder') || 'Any extra address details'"
                        required :icon="MapPin" :error="errors.address" @input="clearFieldError('address')" />
                </div>
            </div>
        </div>

        <div class="w-full h-px bg-card-border/40"></div>

        <!-- ══ SECTION 4: ACCOUNT STATUS (edit only) ══ -->
        <div v-if="user" class="px-1 py-6">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 bg-amber-500 rounded-full"></div>
                <h3 class="text-sm font-semibold text-main-text/60 uppercase tracking-wider">
                    {{ $tr('user.section.account_status') || 'Account Status' }}
                </h3>
            </div>
            <FormToggle
                v-model="profileForm.is_active"
                :label="$tr('user.form.account_active') || 'Account Active'"
                labelPosition="top"
                :description="profileForm.is_active
                    ? ($tr('user.form.account_active_on') || 'User can log in and access the system')
                    : ($tr('user.form.account_active_off') || 'User is blocked from logging in')"
                :icon="profileForm.is_active ? Check : XCircle" />
        </div>

        <div v-if="user" class="w-full h-px bg-card-border/40"></div>

        <!-- ══ SECTION 5: SENBET SCHOOL MEMBERSHIP ══ -->
        <div class="px-1 py-6">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
                <h3 class="text-sm font-semibold text-main-text/60 uppercase tracking-wider">
                    {{ $tr('user.section.senbet') || 'Senbet School Membership' }}
                </h3>
            </div>

            <FormToggle
                v-model="profileForm.is_member"
                :label="$tr('user.form.is_member') || 'This user is a Senbet School member'" />

            <!-- Membership details — only shown when is_member is true -->
            <div v-if="profileForm.is_member" class="mt-6 space-y-6">

                <!-- 5a. Academic Info -->
                <div>
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-purple-500 uppercase tracking-wider mb-4">
                        <BookOpen class="w-3.5 h-3.5" />
                        {{ $tr('user.section.academic_info') || 'Academic Information' }}
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-left">
                        <FormField
                            v-model="profileForm.senbet_date_of_birth"
                            type="date"
                            :label="$tr('user.form.date_of_birth') || 'Date of Birth'"
                            :icon="Calendar" :error="errors.senbet_date_of_birth" @input="clearFieldError('senbet_date_of_birth')" />

                        <FormSelect
                            v-model="profileForm.senbet_class"
                            :label="$tr('user.form.senbet_class') || 'Senbet Class'"
                            :options="classOptions"
                            :error="errors.senbet_class" @change="clearFieldError('senbet_class')" />

                        <FormSelect
                            v-model="profileForm.section"
                            :label="$tr('user.form.section') || 'Section'"
                            :options="sectionOptions"
                            :disabled="sectionOptions.length === 0"
                            :error="errors.section" @change="clearFieldError('section')" />

                        <FormField
                            v-model="profileForm.education_level"
                            :label="$tr('user.form.education_level') || 'Education Level'"
                            :placeholder="$tr('user.form.education_level_placeholder') || 'e.g. Grade 10, Degree'"
                            :icon="Briefcase" :error="errors.education_level" @input="clearFieldError('education_level')" />
                    </div>
                </div>

                <div class="w-full h-px bg-card-border/30"></div>

                <!-- 5b. Emergency Contact -->
                <div>
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-rose-500 uppercase tracking-wider mb-4">
                        <Phone class="w-3.5 h-3.5" />
                        {{ $tr('user.section.emergency_contact') || 'Emergency Contact' }}
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                        <FormField
                            v-model="profileForm.emergency_name"
                            :label="$tr('user.form.emergency_name') || 'Emergency Contact Name'"
                            :placeholder="$tr('user.form.emergency_name_placeholder') || 'Full name of contact person'"
                            :icon="User" :error="errors.emergency_name" @input="clearFieldError('emergency_name')" />

                        <FormField
                            v-model="profileForm.emergency_phone"
                            :label="$tr('user.form.emergency_phone') || 'Emergency Phone'"
                            :placeholder="$tr('user.form.emergency_phone_placeholder') || 'e.g. +251911000000'"
                            :icon="Phone" :error="errors.emergency_phone" @input="clearFieldError('emergency_phone')" />
                    </div>
                </div>

                <!-- 5c. Emergency Contact Address -->
                <div>
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-main-text/40 uppercase tracking-wider mb-4">
                        <MapPin class="w-3.5 h-3.5" />
                        {{ $tr('user.section.emergency_address') || 'Emergency Contact Address' }}
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-left">
                        <FormField
                            v-model="profileForm.emergency_sub_city"
                            :label="$tr('user.form.emergency_sub_city') || 'Sub City'"
                            :placeholder="$tr('user.form.sub_city_placeholder') || 'e.g. Bole'"
                            :icon="MapPin" :error="errors.emergency_sub_city" @input="clearFieldError('emergency_sub_city')" />

                        <FormField
                            v-model="profileForm.emergency_woreda"
                            :label="$tr('user.form.emergency_woreda') || 'Woreda'"
                            :placeholder="$tr('user.form.woreda_placeholder') || 'e.g. 03'"
                            :icon="MapPin" :error="errors.emergency_woreda" @input="clearFieldError('emergency_woreda')" />

                        <FormField
                            v-model="profileForm.emergency_house_number"
                            :label="$tr('user.form.emergency_house_number') || 'House Number'"
                            :placeholder="$tr('user.form.house_number_placeholder') || 'e.g. 456'"
                            :icon="Home" :error="errors.emergency_house_number" @input="clearFieldError('emergency_house_number')" />

                        <div class="md:col-span-2 lg:col-span-3">
                            <FormField
                                v-model="profileForm.emergency_address"
                                :label="$tr('user.form.emergency_address') || 'Additional Address / Notes'"
                                :placeholder="$tr('user.form.emergency_address_placeholder') || 'Any extra address details for the emergency contact'"
                                :icon="MapPin" :error="errors.emergency_address" @input="clearFieldError('emergency_address')" />
                        </div>
                    </div>
                </div>

                <div class="w-full h-px bg-card-border/30"></div>

                <!-- 5d. Previous Participation -->
                <div>
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-main-text/40 uppercase tracking-wider mb-4">
                        <ChevronRight class="w-3.5 h-3.5" />
                        {{ $tr('user.section.participation') || 'Participation History' }}
                    </p>
                    <div class="space-y-4 text-left">
                        <FormToggle
                            v-model="profileForm.previous_participation"
                            :label="$tr('user.form.previous_participation') || 'Has previously participated in Senbet'" />

                        <div v-if="profileForm.previous_participation"
                            class="p-4 rounded-xl border border-dashed border-card-border/60 bg-card-bg/20 space-y-2">
                            <label class="block text-sm font-semibold text-main-text mb-1">
                                {{ $tr('user.form.participation_document') || 'Previous Participation Document' }}
                                <span class="text-xs font-normal text-main-text/40 ml-1">
                                    ({{ $tr('user.form.participation_doc_hint') || 'Image or PDF, max 5MB' }})
                                </span>
                            </label>
                            <p v-if="user?.senbetMembership?.previous_participation_document && !profileForm.previous_participation_document"
                                class="text-xs text-emerald-600 flex items-center gap-1 mb-2">
                                <Check class="w-3 h-3" />
                                {{ $tr('user.form.document_uploaded') || 'Document already uploaded' }}
                            </p>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="(e) => { profileForm.previous_participation_document = (e.target as HTMLInputElement).files?.[0] || null; clearFieldError('previous_participation_document') }"
                                class="block w-full text-sm text-main-text file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-brand-blue/10 file:text-brand-blue hover:file:bg-brand-blue/20 cursor-pointer"
                            />
                            <p v-if="errors.previous_participation_document" class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                                <AlertCircle class="w-3 h-3" /> {{ errors.previous_participation_document }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ ACTION BUTTONS ══ -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-card-border/60">
            <Button variant="ghost" @click="emit('close')" class="hover:bg-main-text/5">
                {{ $tr('user.form.cancel') || 'Cancel' }}
            </Button>
            <Button variant="primary" :icon="Save" @click="openConfirm">
                {{ props.user ? ($tr('user.form.update') || 'Update') : ($tr('user.form.save') || 'Save') }}
            </Button>
        </div>
    </div>
</template>
