<script setup lang="ts">
import { Save } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import { getCurrentInstance } from "vue";

interface SummaryItem {
 label: string;
 value: any;
}

withDefaults(
 defineProps<{
 show: boolean;
 title: string;
 description: string;
 summary: SummaryItem[];
 icon?: any;
 loading?: boolean;
 }>(),
 {
 icon: Save,
 loading: false,
 },
);

const emit = defineEmits(["close", "confirm"]);
const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
</script>

<template>
  <Modal
    :show="show"
    size="sm"
    hideHeader
    @close="!loading && emit('close')"
  >
    <!-- Modal body -->
    <div class="px-6 py-8 flex flex-col items-center text-center">
      <div 
        class="w-16 h-16 rounded-full flex items-center justify-center mb-5 shrink-0 bg-brand-blue/10"
      >
        <component :is="icon" class="w-8 h-8 text-brand-blue" stroke-width="1.5" />
      </div>
      
      <h3 class="text-xl font-bold text-main-text tracking-tight mb-2">
        {{ title }}
      </h3>
      
      <p class="text-[15px] leading-relaxed text-main-text/60 mb-6">
        {{ description }}
      </p>

      <!-- Summary Grid -->
      <div
        v-if="summary && summary.length"
        class="w-full bg-card-bg/40 border border-card-border/60 rounded-xl overflow-hidden shadow-sm"
      >
        <div
          v-for="(item, index) in summary"
          :key="item.label"
          :class="[
            'flex justify-between items-center px-4 py-3 transition-colors',
            index !== summary.length - 1 ? 'border-b border-card-border/40' : '',
          ]"
        >
          <span class="text-xs font-semibold text-main-text/60">
            {{ item.label }}
          </span>
          <span class="text-sm font-bold text-main-text truncate bg-main-bg/30 px-3 py-1 rounded-md border border-card-border/30">
            {{ item.value }}
          </span>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="px-6 py-5 bg-card-bg/50 border-t border-card-border/40 flex items-center gap-3 w-full justify-center rounded-b-[inherit]">
        <Button 
          variant="secondary"
          class="flex-1 font-semibold tracking-tight !h-11"
          :disabled="loading"
          @click="emit('close')"
        >
          {{ $tr('common.abort') || 'Cancel' }}
        </Button>

        <Button
          variant="primary"
          :loading="loading"
          class="flex-1 font-semibold tracking-tight !h-11"
          @click="emit('confirm')"
        >
          {{ loading ? ($tr('common.processing') || 'Processing...') : ($tr('common.confirm') || 'Confirm') }}
        </Button>
      </div>
    </template>
  </Modal>
</template>
