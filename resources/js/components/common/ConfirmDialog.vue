<script setup lang="ts">
/**
 * ConfirmDialog — standard wrapper around Modal used specifically for
 * prompting the user before destructive or major actions.
 *
 * Emits 'confirm' when the primary button is clicked.
 * Handles the "loading" state natively so the modal doesn't close prematurely.
 *
 * @example
 * <ConfirmDialog
 * :show="showConfirm"
 * title="Delete Member"
 * message="Are you sure you want to delete John Doe? This cannot be undone."
 * confirm-text="Delete Member"
 * variant="danger"
 * :loading="isDeleting"
 * @close="showConfirm = false"
 * @confirm="submitDelete"
 * />
 */
import Modal from "./Modal.vue";
import Button from "./Button.vue";
import { AlertCircle, Trash2, ShieldAlert } from "lucide-vue-next";
import { computed } from "vue";

interface Props {
 show: boolean;
 title: string;
 /** Main body text for the confirmation prompt */
 message: string;
 /** Style of the confirm button and the modal header icon */
 variant?: "danger" | "warning" | "primary";
 /** Text shown on the confirmation button */
 confirmText?: string;
 /** Disable controls and show spinner during async execution */
 loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 variant: "danger",
 confirmText: "Confirm",
 loading: false,
});

const emit = defineEmits<{
 (e: "close"): void;
 (e: "confirm"): void;
}>();

const config = computed(() => {
 switch (props.variant) {
 case "danger":
 return {
 icon: Trash2,
 badge: "bg-rose-500/10",
 color: "text-rose-500",
 btn: "danger",
 };
 case "warning":
 return {
 icon: AlertCircle,
 badge: "bg-brand-yellow/10",
 color: "text-brand-yellow",
 btn: "warning",
 };
 default:
 return {
 icon: ShieldAlert,
 badge: "bg-brand-blue/10",
 color: "text-brand-blue",
 btn: "primary",
 };
 }
});
</script>

<template>
 <Modal
 :show="show"
 size="sm"
 hideHeader
 @close="!loading && emit('close')">
 <!-- Modal body -->
 <div class="px-6 py-8 flex flex-col items-center text-center">
    <div 
        class="w-16 h-16 rounded-full flex items-center justify-center mb-5 shrink-0"
        :class="config.badge"
    >
        <component :is="config.icon" class="w-8 h-8" :class="config.color" stroke-width="1.5" />
    </div>
    
    <h3 class="text-xl font-bold text-main-text tracking-tight mb-2">
        {{ title }}
    </h3>
    
    <p class="text-[15px] leading-relaxed text-main-text/60" v-html="message" />
    <slot />
 </div>

  <template #footer>
    <div class="px-6 py-5 bg-card-bg/50 border-t border-card-border/40 flex items-center gap-3 w-full justify-center rounded-b-[inherit]">
      <Button 
        variant="secondary"
        class="flex-1 font-semibold tracking-tight !h-11"
        :disabled="loading"
        @click="emit('close')">
        {{ $tr("common.cancel", "Cancel") }}
      </Button>

      <Button
        :variant="config.btn as any"
        :loading="loading"
        class="flex-1 font-semibold tracking-tight !h-11"
        @click="emit('confirm')">
        {{ confirmText }}
      </Button>
    </div>
  </template>
 </Modal>
</template>
