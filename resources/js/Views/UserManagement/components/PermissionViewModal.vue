<script setup lang="ts">
import {
  ShieldCheck,
  Tag,
  Fingerprint,
  Pencil,
  Trash2,
  Hash,
  AlignLeft,
  Calendar,
} from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import { useLanguageStore } from "@/stores/languageStore";
import { usePermissionStore } from "@/stores/permissionStore";
import { computed, getCurrentInstance } from "vue";
import { localize, formatDate } from "@/utils/format";
import { usePermissions } from "@/composables/usePermissions";
import { useSecurity } from "@/composables/useSecurity";
import { Modules } from "@/constants/permissions";

const props = defineProps<{
  show: boolean;
  permission: any | null;
}>();

const emit = defineEmits(["close", "edit", "delete"]);

const lang = useLanguageStore();
const permissionStore = usePermissionStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const { canEdit, canDelete } = usePermissions(Modules.PERMISSIONS);
const { isSuperAdmin } = useSecurity();

const canEditItem = computed(() => {
  if (!canEdit.value) return false;
  return isSuperAdmin.value || !props.permission?.is_system_level;
});
const canDeleteItem = computed(() => {
  if (!canDelete.value) return false;
  return isSuperAdmin.value || !props.permission?.is_system_level;
});
</script>

<template>
  <Modal
    :show="show"
    @close="$emit('close')"
    size="lg"
    noPadding
  >
    <template #header>
      <div v-if="permission" class="flex items-center gap-4 py-1">
        <div class="w-10 h-10 rounded-lg bg-brand-blue/5 flex items-center justify-center text-brand-blue/70 border border-brand-blue/10 shrink-0">
          <ShieldCheck class="w-6 h-6" />
        </div>
        <div class="min-w-0 text-left">
          <p class="text-[12px] font-medium text-main-text/30 mb-0.5">{{ $tr('permission.permission_details') }}</p>
          <div class="flex items-center gap-2.5">
            <h2 class="text-lg font-normal text-main-text tracking-tight">{{ localize(permission.name) }}</h2>
            <span :class="['px-2 py-0.5 rounded text-[11px] font-medium border capitalize', permission.is_system_level ? 'bg-brand-blue/5 text-brand-blue border-brand-blue/10' : 'bg-emerald-500/5 text-emerald-600 border-emerald-500/10']">
              {{ permission.is_system_level ? $tr("common.system") : $tr("common.custom") }}
            </span>
          </div>
        </div>
      </div>
    </template>

    <div v-if="permission" class="flex flex-col bg-main-bg overflow-hidden">
      <div class="p-8 space-y-10 flex-1 overflow-y-auto custom-scrollbar">
        <!-- Core Identifier Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
           <!-- Module & Action Group -->
           <div class="space-y-6">
              <div class="flex flex-col gap-2 text-left">
                <div class="flex items-center gap-2 text-main-text/40">
                  <Tag class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.module") }}</span>
                </div>
                <p class="text-[15px] font-normal text-main-text pl-6 capitalize">
                  {{ $tr("module." + (permission.module || "other")) }}
                </p>
              </div>

              <div class="flex flex-col gap-2 text-left">
                <div class="flex items-center gap-2 text-main-text/40">
                  <Fingerprint class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.action") }}</span>
                </div>
                <p class="text-[15px] font-normal text-main-text pl-6 capitalize">
                  {{ $tr("action." + (permission.action || "view")) }}
                </p>
              </div>
           </div>

           <!-- Slug & Date Group -->
           <div class="space-y-6">
              <div class="flex flex-col gap-2 text-left">
                <div class="flex items-center gap-2 text-main-text/40">
                  <Hash class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.slug") }}</span>
                </div>
                <p class="text-[13px] font-mono text-main-text/70 bg-card-hover/20 px-2 py-1 rounded w-fit ml-6">
                  {{ permission.slug }}
                </p>
              </div>

              <div class="flex flex-col gap-2 text-left">
                <div class="flex items-center gap-2 text-main-text/40">
                  <Calendar class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.created_at") }}</span>
                </div>
                <p class="text-[15px] font-normal text-main-text pl-6">
                  {{ formatDate(permission.created_at) }}
                </p>
              </div>
           </div>
        </div>

        <!-- Description Block -->
        <div class="space-y-4">
           <div class="flex items-center gap-3">
              <div class="flex items-center gap-2 text-main-text/40">
                <AlignLeft class="w-4 h-4" />
                <span class="text-[13px] font-medium capitalize">{{ $tr("common.description") }}</span>
              </div>
              <div class="flex-1 h-px bg-card-border/20"></div>
           </div>
           <div class="p-6 rounded-2xl bg-card-bg border border-card-border/40 hover:border-brand-blue/20 transition-all text-left">
              <p class="text-[15px] text-main-text/60 leading-relaxed">
                {{ localize(permission.description) || $tr("common.no_description") }}
              </p>
           </div>
        </div>
      </div>

      <!-- Action Footer -->
      <footer class="flex items-center justify-end gap-3 px-8 py-5 border-t border-card-border/60 bg-card-bg/20 shrink-0">
        <Button 
          v-if="canDeleteItem" 
          variant="soft-danger" 
          :icon="Trash2" 
          class="h-11 px-6 font-normal" 
          @click="emit('close'); emit('delete')"
        >
          {{ $tr("action.delete") }}
        </Button>
        <div class="flex-1"></div>
        <Button 
          class="h-11 px-6 font-bold tracking-tight capitalize border-card-border/60" 
          variant="secondary" 
          @click="emit('close')"
        >
          {{ $tr("action.cancel") }}
        </Button>
        <Button 
          v-if="canEditItem" 
          variant="primary" 
          :icon="Pencil" 
          class="h-11 px-8 font-normal" 
          @click="emit('edit')"
        >
          {{ $tr('action.edit') }}
        </Button>
      </footer>
    </div>
  </Modal>
</template>
