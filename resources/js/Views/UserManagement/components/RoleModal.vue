<script setup lang="ts">
import { onMounted, ref, watch, computed, getCurrentInstance } from "vue";
import { ShieldCheck, Hash, Save, AlignLeft, Check } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import FormField from "@/components/common/FormField.vue";
import Button from "@/components/common/Button.vue";
import { useRoleStore } from "@/stores/roleStore";
import { usePermissionStore } from "@/stores/permissionStore";
import { groupPermissionsByModule } from "@/utils/permissionUtils";
import { localize } from "@/utils/format";
import { z } from "zod";

import { useModalLogic } from "@/composables/useModalLogic";

const props = defineProps<{
  show: boolean;
  role: any | null;
}>();

const emit = defineEmits(["close", "saved"]);

const roleStore = useRoleStore();
const permissionStore = usePermissionStore();

onMounted(() => {
  permissionStore.fetchAllPermissions();
});
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const { form, errors, loading, isEdit, submit, clearFieldError } =
  useModalLogic({
    props,
    emit,
    store: roleStore,
    itemKey: "role",
    schema: {
      name: z
        .string()
        .min(1, "validation.required")
        .min(2, "validation.min_2")
        .max(100, "validation.max_length"),
      description: z
        .string()
        .max(1000, "validation.max_length")
        .optional()
        .nullable(),
      hierarchy_level: z.coerce
        .number()
        .min(1, "validation.numeric_min")
        .max(100, "validation.numeric_max"),
      permissions: z.array(z.string()).min(1, "validation.required"),
    },
    actions: {
      create: roleStore.createRole,
      update: (payload: any) =>
        roleStore.updateRole(props.role?.slug, payload),
    },
    logic: {
      getInitialForm: (role) => ({
        name: role ? localize(role.name) : "",
        description: role
          ? localize(role.description) === "N/A"
            ? ""
            : localize(role.description)
          : "",
        hierarchy_level: role?.hierarchy_level || 1,
        permissions: role?.permissions
          ? role.permissions.map((p: any) => p.slug)
          : [],
      }),
    },
    successMessage: () =>
      props.role ? "role_updated_success" : "role_created_success",
  });

const groupedPermissions = computed(() => {
  return groupPermissionsByModule(permissionStore.permissions);
});

const togglePermission = (slug: string) => {
  const idx = form.permissions.indexOf(slug);
  if (idx > -1) form.permissions.splice(idx, 1);
  else form.permissions.push(slug);
  clearFieldError("permissions");
};
</script>

<template>
  <Modal
    :show="show"
    @close="$emit('close')"
    size="xl"
    noPadding
  >
    <template #header>
      <div class="flex items-center gap-4 py-1">
        <div class="w-10 h-10 rounded-lg bg-brand-blue/5 flex items-center justify-center text-brand-blue/70 border border-brand-blue/10 shrink-0">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div class="flex flex-col text-left">
          <p class="text-[12px] font-medium text-main-text/30 mb-0.5">
            {{ isEdit ? $tr('role.edit_role') : $tr('role.create_role') }}
          </p>
          <h2 class="text-lg font-normal text-main-text leading-none tracking-tight">
            {{ isEdit ? localize(role?.name) : $tr('role.new_role_definition') }}
          </h2>
        </div>
      </div>
    </template>

    <div class="flex flex-col bg-main-bg h-[80vh] max-h-[850px] overflow-hidden">
      <div class="flex-1 flex min-h-0 overflow-hidden">
        <!-- Sidebar: Configuration Details -->
        <aside class="w-[320px] shrink-0 border-r border-card-border/30 flex flex-col overflow-y-auto custom-scrollbar p-8 space-y-10">
           <div class="space-y-1.5 shrink-0">
              <h4 class="text-[13px] font-medium text-main-text/80 capitalize">{{ $tr('common.basic_info') }}</h4>
              <p class="text-[12px] text-main-text/40 leading-relaxed">{{ $tr('role.basic_info_desc') }}</p>
           </div>

            <div class="flex-1 flex flex-col gap-8 min-h-0">
              <FormField
                v-model="form.name"
                :label="$tr('role.name')"
                :icon="ShieldCheck"
                required
                :error="errors.name"
                class="font-normal shrink-0"
                @input="clearFieldError('name')"
              />

              <FormField
                v-model.number="form.hierarchy_level"
                type="number"
                min="1"
                max="99"
                :label="$tr('role.hierarchy_level')"
                :icon="Hash"
                required
                :error="errors.hierarchy_level"
                class="shrink-0"
                @input="clearFieldError('hierarchy_level')"
              />

              <!-- Bigger Description Field -->
              <FormField
                v-model="form.description"
                type="textarea"
                :rows="12"
                :label="$tr('role.description')"
                :icon="AlignLeft"
                :error="errors.description"
                class="flex-1 flex flex-col"
                inputClass="flex-1"
                @input="clearFieldError('description')"
              />
            </div>
        </aside>

        <!-- Main Panel: Permission Matrix -->
        <main class="flex-1 flex flex-col min-h-0 overflow-hidden">
           <div class="p-8 pt-7 border-b border-card-border/20 flex items-center justify-between bg-card-bg/5 shrink-0">
              <div class="flex flex-col gap-1 text-left">
                <h4 class="text-[15px] font-medium text-main-text/80 capitalize">{{ $tr("role.permissions_matrix") }}</h4>
                <p class="text-[12px] text-main-text/40">{{ $tr('role.permissions_matrix_desc') }}</p>
              </div>
              <div class="flex items-center gap-3">
                <p v-if="errors.permissions" class="text-[12px] text-rose-500 font-medium bg-rose-50 px-3 py-1 rounded-md border border-rose-100">
                  {{ errors.permissions }}
                </p>
                <div class="text-[12px] font-medium text-brand-blue bg-brand-blue/5 border border-brand-blue/10 px-4 py-1.5 rounded-full">
                  {{ form.permissions.length }} {{ $tr('common.active_permissions') }}
                </div>
              </div>
           </div>

           <div class="flex-1 overflow-y-auto p-8 custom-scrollbar space-y-12">
              <div v-for="(perms, module) in groupedPermissions" :key="module" class="space-y-6">
                <!-- Module Header -->
                <div class="flex items-center gap-3">
                  <h5 class="text-[14px] font-medium text-main-text/60 capitalize tracking-wide">
                    {{ $tr(`module.${module.toLowerCase()}`) || module }}
                  </h5>
                  <div class="flex-1 h-px bg-card-border/30"></div>
                </div>

                <!-- Smaller Permissions Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5">
                   <div 
                     v-for="permission in perms" 
                     :key="permission.id"
                     @click="togglePermission(permission.slug)"
                     :class="[
                       'group flex items-center gap-2.5 px-3 py-2 rounded-lg border transition-all cursor-pointer select-none relative overflow-hidden',
                       form.permissions.includes(permission.slug)
                        ? 'bg-brand-blue/3 border-brand-blue/40 ring-1 ring-brand-blue/5'
                        : 'bg-transparent border-card-border/40 hover:border-brand-blue/20 hover:bg-card-hover/5'
                     ]"
                   >
                      <div :class="['w-4.5 h-4.5 rounded border flex items-center justify-center transition-all shrink-0', 
                        form.permissions.includes(permission.slug) ? 'bg-brand-blue border-brand-blue text-white' : 'border-card-border/50 bg-white/5 group-hover:border-brand-blue/25']">
                         <Check v-if="form.permissions.includes(permission.slug)" class="w-2.5 h-2.5 stroke-[4px]" />
                      </div>
                      <div class="flex flex-col min-w-0 text-left">
                        <span :class="['text-[12.5px] font-medium truncate transition-colors leading-tight', form.permissions.includes(permission.slug) ? 'text-brand-blue' : 'text-main-text/70']">
                          {{ localize(permission.name) }}
                        </span>
                        <span class="text-[9px] text-main-text/20 font-mono truncate">{{ permission.slug }}</span>
                      </div>
                   </div>
                </div>
              </div>
           </div>
        </main>
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
          class="h-11 px-8 font-normal"
          @click="submit"
        >
          {{ isEdit ? $tr('common.update') : $tr('common.save') }}
        </Button>
      </footer>
    </div>
  </Modal>
</template>
