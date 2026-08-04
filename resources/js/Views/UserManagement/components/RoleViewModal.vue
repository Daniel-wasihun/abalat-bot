<script setup lang="ts">
import {
  ShieldCheck,
  Users,
  History,
  Eye,
  Plus,
  Pencil,
  Trash2,
  Shield,
  Hash,
  AlignLeft,
} from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import { localize } from "@/utils/format";
import { useLanguageStore } from "@/stores/languageStore";
import { computed, getCurrentInstance } from "vue";
import { usePermissions } from "@/composables/usePermissions";
import { useSecurity } from "@/composables/useSecurity";
import { Modules } from "@/constants/permissions";

const props = defineProps<{
  show: boolean;
  role: any | null;
}>();

const emit = defineEmits(["close", "edit", "delete"]);
const lang = useLanguageStore();
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;

const { canEdit, canDelete } = usePermissions(Modules.ROLES);
const { isSuperAdmin, canModifyRole } = useSecurity();

const canEditItem = computed(() => {
  if (!canEdit.value) return false;
  return isSuperAdmin.value || canModifyRole(props.role);
});
const canDeleteItem = computed(() => {
  if (!canDelete.value) return false;
  return (
    isSuperAdmin.value ||
    (canModifyRole(props.role) && !props.role?.is_system_level)
  );
});

const actionTypes = [
  { key: "create", icon: Plus, color: "text-emerald-600" },
  { key: "update", icon: Pencil, color: "text-amber-600" },
  { key: "view", icon: Eye, color: "text-brand-blue" },
  { key: "delete", icon: Trash2, color: "text-rose-600" },
  { key: "others", icon: Shield, color: "text-main-text/60" },
];

const getActionType = (slug: string) => {
  const action = slug.split(".")[1] || "";
  if (["create", "store", "add", "import", "generate"].some((a) => action.includes(a))) return "create";
  if (["update", "edit", "patch", "toggle", "sync", "reset", "bulk-toggle"].some((a) => action.includes(a))) return "update";
  if (["view", "list", "show", "export", "index", "details", "template", "status"].some((a) => action.includes(a))) return "view";
  if (["delete", "destroy", "remove", "bulk-delete"].some((a) => action.includes(a))) return "delete";
  return "others";
};

const groupedPermissions = computed(() => {
  const perms = props.role?.permissions || [];
  const byModule: Record<string, any> = {};
  perms.forEach((p: any) => {
    const module = p.module || "Other";
    if (!byModule[module]) byModule[module] = { create: [], update: [], view: [], delete: [], others: [] };
    const type = getActionType(p.slug);
    byModule[module][type].push(p);
  });
  return byModule;
});
</script>

<template>
  <Modal
    :show="show"
    @close="$emit('close')"
    size="xl"
    noPadding
  >
    <template #header>
      <div v-if="role" class="flex items-center gap-4 py-1">
        <div class="w-10 h-10 rounded-lg bg-brand-blue/5 flex items-center justify-center text-brand-blue/70 border border-brand-blue/10 shrink-0">
          <ShieldCheck class="w-6 h-6" />
        </div>
        <div class="min-w-0">
          <p class="text-[12px] font-medium text-main-text/30 mb-0.5">{{ $tr('role.role_details') }}</p>
          <div class="flex items-center gap-2.5">
            <h2 class="text-lg font-normal text-main-text tracking-tight">{{ localize(role.name) }}</h2>
            <span :class="['px-2 py-0.5 rounded text-[11px] font-medium border capitalize', role.is_system_level ? 'bg-brand-blue/5 text-brand-blue border-brand-blue/10' : 'bg-emerald-500/5 text-emerald-600 border-emerald-500/10']">
              {{ role.is_system_level ? $tr("common.system") : $tr("common.custom") }}
            </span>
          </div>
        </div>
      </div>
    </template>

    <div v-if="role" class="flex flex-col bg-main-bg h-[80vh] max-h-[850px] overflow-hidden">
      <!-- Split View Body -->
      <div class="flex-1 flex min-h-0 overflow-hidden">
        <!-- Sidebar: Basic Info -->
        <aside class="w-[320px] shrink-0 border-r border-card-border/30 flex flex-col overflow-y-auto custom-scrollbar p-8 space-y-8">
           <h4 class="text-[14px] font-medium text-main-text/80 capitalize pb-2 border-b border-card-border/20">
              {{ $tr('common.details') }}
            </h4>

            <div class="space-y-8">
              <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2 text-main-text/60">
                  <History class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.hierarchy") }}</span>
                </div>
                <p class="text-xl font-normal text-main-text pl-6">{{ role.hierarchy_level }}</p>
              </div>

              <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2 text-main-text/60">
                  <Users class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("user.member") }}</span>
                </div>
                <p class="text-xl font-normal text-main-text pl-6">{{ role.users_count || 0 }}</p>
              </div>

              <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2 text-main-text/60">
                  <Hash class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.slug") }}</span>
                </div>
                <p class="text-[14px] font-mono text-main-text/70 bg-card-hover/20 px-2 py-1 rounded w-fit ml-6">
                  {{ role.slug }}
                </p>
              </div>

              <div v-if="localize(role.description) && localize(role.description) !== 'N/A'" class="flex flex-col gap-3">
                <div class="flex items-center gap-2 text-main-text/60">
                  <AlignLeft class="w-4 h-4" />
                  <span class="text-[13px] font-medium capitalize">{{ $tr("common.description") }}</span>
                </div>
                <p class="text-[14px] font-normal text-main-text/60 leading-relaxed text-left pl-6">
                  {{ localize(role.description) }}
                </p>
              </div>
            </div>
        </aside>

        <!-- Permissions List -->
        <main class="flex-1 flex flex-col min-h-0 p-8 pt-6 overflow-hidden">
           <div class="flex items-center justify-between mb-6">
              <h4 class="text-[14px] font-medium text-main-text/80 capitalize">
                {{ $tr("role.permissions") }}
              </h4>
              <div class="text-[12px] font-medium text-brand-blue bg-brand-blue/5 border border-brand-blue/10 px-3 py-1 rounded-full">
                {{ role.permissions?.length || 0 }} {{ $tr('common.total') }}
              </div>
           </div>

           <div class="flex-1 overflow-y-auto pr-4 custom-scrollbar space-y-10">
              <div v-for="(groups, module) in groupedPermissions" :key="module" class="space-y-5">
                <div class="flex items-center gap-3">
                  <div class="w-1.5 h-1.5 rounded-full bg-brand-blue/40"></div>
                  <h5 class="text-[15px] font-normal text-main-text capitalize tracking-tight">
                    {{ $tr(`module.${module.toLowerCase()}`) || module }}
                  </h5>
                  <div class="flex-1 h-px bg-card-border/30"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6 pl-4">
                  <template v-for="type in actionTypes" :key="type.key">
                    <div v-if="groups[type.key].length > 0" class="flex flex-col gap-3">
                      <div class="flex items-center gap-2 text-main-text/40">
                        <component :is="type.icon" :class="['w-3.5 h-3.5', type.color]" />
                        <span class="text-[11px] font-medium capitalize">{{ $tr("action." + type.key) }}</span>
                      </div>
                      <div class="flex flex-wrap gap-2">
                        <div v-for="permission in groups[type.key]" :key="permission.id" class="px-3 py-1.5 rounded border border-card-border/60 bg-transparent text-[13px] font-normal text-main-text/80 hover:border-brand-blue/40 hover:text-brand-blue transition-all cursor-default">
                          {{ localize(permission.name) }}
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
           </div>
        </main>
      </div>

      <!-- Unified Action Footer -->
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
          @click="emit('close'); emit('edit')"
        >
          {{ $tr('action.edit') }}
        </Button>
      </footer>
    </div>
  </Modal>
</template>
