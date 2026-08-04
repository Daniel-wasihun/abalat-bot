<script setup lang="ts">
import { ref, watch, computed, getCurrentInstance } from "vue";
import { ShieldCheck, Tag, Fingerprint, Save, Hash } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import FormSelect from "@/components/common/FormSelect.vue";
import FormField from "@/components/common/FormField.vue";
import Button from "@/components/common/Button.vue";
import { usePermissionStore } from "@/stores/permissionStore";
import { localize } from "@/utils/format";
import { z } from "zod";

import { useModalLogic } from "@/composables/useModalLogic";

const props = defineProps<{
  show: boolean;
  permission: any | null;
}>();

const emit = defineEmits(["close", "saved"]);

const permissionStore = usePermissionStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const moduleOptions = computed<{ label: string; value: any; icon: any }[]>(
  () => [
    { label: String($tr("common.module")), value: "", icon: ShieldCheck },
    ...Object.keys(permissionStore.options.modules).map((key) => ({
      label: String($tr("module." + key)),
      value: key,
      icon: ShieldCheck,
    })),
  ],
);

const actionOptions = computed<{ label: string; value: any; icon: any }[]>(
  () => [
    { label: String($tr("common.action")), value: "", icon: Fingerprint },
    ...Object.keys(permissionStore.options.actions).map((key) => ({
      label: String($tr("action." + key)),
      value: key,
      icon: Fingerprint,
    })),
  ],
);

const { form, errors, loading, isEdit, submit, clearFieldError, resetForm } =
  useModalLogic({
    props,
    emit,
    store: permissionStore,
    itemKey: "permission",
    schema: {
      module: z.string().min(1, "validation.required"),
      action: z.string().min(1, "validation.required"),
      name: z.string().optional().nullable(),
      slug: z.string().optional().nullable(),
      description: z
        .string()
        .max(500, "validation.max_length")
        .optional()
        .nullable(),
    },
    actions: {
      create: permissionStore.createPermission,
      update: (payload: any) =>
        permissionStore.updatePermission(props.permission?.id, payload),
    },
    logic: {
      getInitialForm: (permission) => ({
        module: permission?.module || "",
        action: permission?.action || "",
        name: permission?.name ? localize(permission.name) : "",
        slug: permission?.slug || "",
        description: permission?.description ? localize(permission.description) : "",
      }),
    },
    successMessage: () =>
      props.permission
        ? "permission_updated_success"
        : "permission_created_success",
  });

// Watch for changes in module and action to generate name and slug
watch([() => form.module, () => form.action], ([newModule, newAction]) => {
  if (newModule && newAction && !isEdit.value) {
    form.slug = `${newModule}.${newAction}`;
    const moduleLabel =
      permissionStore.options.modules[newModule] || newModule;
    const actionLabel =
      permissionStore.options.actions[newAction] || newAction;

    if (proxy.$lang.currentLanguage === "am") {
      if (newAction === "manage_all") {
        form.name = `ሁሉንም ${moduleLabel} አስተዳድር`;
      } else {
        form.name = `${moduleLabel} ${actionLabel}`;
      }
    } else {
      form.name = `${actionLabel} ${moduleLabel}`;
    }
    if (form.name) {
      form.name =
        form.name.charAt(0).toUpperCase() +
        form.name.slice(1).toLowerCase();
    }
  } else if (!isEdit.value) {
    form.slug = "";
    form.name = "";
  }
});
</script>

<template>
  <Modal
    :show="show"
    @close="$emit('close')"
    size="md"
    noPadding
  >
    <template #header>
      <div class="flex items-center gap-4 py-1">
        <div class="w-10 h-10 rounded-lg bg-brand-blue/5 flex items-center justify-center text-brand-blue/70 border border-brand-blue/10 shrink-0">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div class="flex flex-col text-left">
          <p class="text-[12px] font-medium text-main-text/30 mb-0.5">
            {{ isEdit ? $tr('permission.edit_permission') : $tr('permission.create_permission') }}
          </p>
          <h2 class="text-lg font-normal text-main-text leading-none tracking-tight">
            {{ isEdit ? localize(permission?.name) : $tr('permission.new_definition') }}
          </h2>
        </div>
      </div>
    </template>

    <form @submit.prevent="submit" class="flex flex-col bg-main-bg overflow-hidden">
      <div class="px-8 py-8 space-y-6 flex-1 overflow-y-auto custom-scrollbar">
        <!-- Form Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-7">
          <!-- Module Dropdown -->
          <FormSelect
            v-model="form.module"
            :options="moduleOptions"
            :label="$tr('common.module')"
            :icon="ShieldCheck"
            required
            :error="errors.module"
            @change="clearFieldError('module')" />

          <!-- Action Dropdown -->
          <FormSelect
            v-model="form.action"
            :options="actionOptions"
            :label="$tr('common.action')"
            :icon="Fingerprint"
            required
            :error="errors.action"
            @change="clearFieldError('action')" />

          <!-- Generated Name -->
          <FormField
            v-model="form.name"
            :label="$tr('common.name')"
            :icon="Tag"
            :readonly="!isEdit"
            :error="errors.name"
            @input="clearFieldError('name')" />

          <!-- Generated Slug -->
          <FormField
            v-model="form.slug"
            :label="$tr('common.slug')"
            :icon="Hash"
            :readonly="!isEdit"
            :error="errors.slug"
            class="lowercase"
            @input="clearFieldError('slug')" />

          <!-- Description -->
          <FormField
            v-model="form.description"
            type="textarea"
            :rows="4"
            :label="$tr('common.description')"
            :placeholder="$tr('common.description')"
            :error="errors.description"
            class="md:col-span-2"
            @input="clearFieldError('description')" />
        </div>
      </div>

      <!-- Footer Actions -->
      <footer class="flex items-center justify-end gap-3 px-8 py-5 border-t border-card-border/60 bg-card-bg/20 shrink-0">
        <Button 
          variant="secondary" 
          class="h-11 px-6 font-bold tracking-tight capitalize border-card-border/60"
          @click="$emit('close')"
        >
          {{ $tr('action.cancel') }}
        </Button>
        <Button 
          variant="primary" 
          :icon="Save" 
          :loading="loading" 
          class="h-11 px-8 font-bold tracking-tight capitalize"
          @click="submit"
        >
          {{ isEdit ? $tr('common.update') : $tr('common.save') }}
        </Button>
      </footer>
    </form>
  </Modal>
</template>
