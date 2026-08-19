<script setup lang="ts">
import { AlertCircle, Trash2 } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";

/**
 * Standardizes deletion confirmations across the app with consistent UI and error handling.
 * Migrated to the ultra-premium 'Elite' component suite.
 */

defineProps<{
 show: boolean;
 title: string;
 description: string;
 itemName?: string;
 itemMeta?: string;
 loading?: boolean;
 error?: string;
}>();

const emit = defineEmits(["close", "confirm"]);
</script>

<template>
 <Modal
 :show="show"
 :title="title"
 :icon="Trash2"
 :iconClass="'text-rose-500'"
 :badgeClass="'bg-rose-500/10'"
 size="confirm"
 @close="!loading && emit('close')">
 
 <div class="px-8 py-6 space-y-6">
  <p class="text-[14px] md:text-[15px] leading-relaxed text-main-text/60 font-medium" v-html="description" />

 <!-- Item Preview Card (Clean Segmented Style) -->
 <div
 v-if="itemName"
 class="bg-main-bg border border-card-border/60 rounded-2xl p-5 space-y-1.5 transition-all hover:border-rose-500/10">
 <h5 class="text-[10px] font-bold text-main-text/30 capitalize tracking-[0.15em]">
 {{ $tr("common.target_item") || "Target Item" }}
 </h5>
 <p class="text-[16px] font-bold text-rose-500 leading-tight">
 &quot;{{ itemName }}&quot;
 </p>
 <p v-if="itemMeta" class="text-[13px] text-main-text/40 font-medium italic">
 {{ itemMeta }}
 </p>
 </div>

 <!-- Error Display -->
 <transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="transform -translate-y-2 opacity-0"
 enter-to-class="transform translate-y-0 opacity-100"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="transform translate-y-0 opacity-100"
 leave-to-class="transform -translate-y-2 opacity-0">
 <div
 v-if="error"
 class="p-4 bg-rose-500/5 border border-rose-500/20 rounded-xl text-rose-600 text-[13px] font-bold flex items-center justify-center gap-3 animate-pulse">
 <AlertCircle class="w-4 h-4 shrink-0" />
 {{ error }}
 </div>
 </transition>
 </div>

  <template #footer>
    <div class="px-8 py-5 border-t border-card-border/40 flex items-center justify-end gap-3 bg-card-bg rounded-b-[inherit]">
      <Button 
        class="font-bold tracking-tight capitalize px-6 border-card-border/60"
        variant="secondary"
        :disabled="loading"
        @click="emit('close')">
        {{ $tr("common.cancel") }}
      </Button>
      <Button
        variant="danger"
        class="font-bold tracking-tight capitalize px-8 min-w-[140px]"
        :loading="loading"
        @click="emit('confirm')">
        {{
          loading
          ? $tr("common.processing")
          : $tr("action.delete")
        }}
      </Button>
    </div>
  </template>
 </Modal>
</template>
