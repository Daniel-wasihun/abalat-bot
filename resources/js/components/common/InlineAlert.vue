<script setup lang="ts">
/**
 * InlineAlert — a compact, dismissable alert banner for page-level feedback.
 * Use for success messages after a save, validation summaries, API error notices, etc.
 *
 * @example
 * <InlineAlert type="success" message="Book saved successfully." />
 * <InlineAlert type="error" message="Failed to save. Please try again." dismissable />
 * <InlineAlert type="warning">
 * This member has <strong>2 overdue books</strong>. Review before processing.
 * </InlineAlert>
 */
import { ref, computed } from "vue";
import { CheckCircle, AlertTriangle, XCircle, Info, X } from "lucide-vue-next";

interface Props {
 type?: "success" | "error" | "warning" | "info";
 message?: string;
 /** Show a × close button */
 dismissable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 type: "info",
 dismissable: false,
});

const emit = defineEmits<{ (e: "dismiss"): void }>();

const visible = ref(true);

const CONFIG = {
 success: {
 bg: "bg-brand-green/8 border-brand-green/20",
 icon: CheckCircle,
 iclr: "text-brand-green",
 tclr: "text-brand-green",
 mclr: "text-brand-green/80",
 },
 error: {
 bg: "bg-rose-500/8 border-rose-500/20",
 icon: XCircle,
 iclr: "text-rose-500",
 tclr: "text-rose-500",
 mclr: "text-rose-500/80",
 },
 warning: {
 bg: "bg-brand-yellow/8 border-brand-yellow/20",
 icon: AlertTriangle,
 iclr: "text-brand-yellow",
 tclr: "text-brand-yellow",
 mclr: "text-brand-yellow/80",
 },
 info: {
 bg: "bg-brand-blue/8 border-brand-blue/20",
 icon: Info,
 iclr: "text-brand-blue",
 tclr: "text-brand-blue",
 mclr: "text-brand-blue/80",
 },
};

const cfg = computed(() => CONFIG[props.type]);

function dismiss() {
 visible.value = false;
 emit("dismiss");
}
</script>

<template>
 <Transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="opacity-0 -translate-y-2"
 enter-to-class="opacity-100 translate-y-0"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0"
 leave-to-class="opacity-0 -translate-y-2">
 <div
 v-if="visible"
 class="flex items-start gap-3 p-4 rounded-xl border"
 :class="cfg.bg"
 role="alert">
 <!-- Icon -->
 <component
 :is="cfg.icon"
 class="w-4.5 h-4.5 shrink-0 mt-0.5"
 :class="cfg.iclr" />

 <!-- Text -->
 <div
 class="flex-1 min-w-0 text-[13px] leading-relaxed"
 :class="cfg.mclr">
 <slot>{{ message }}</slot>
 </div>

 <!-- Dismiss button -->
 <button
 v-if="dismissable"
 type="button"
 class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
 :class="cfg.iclr"
 @click="dismiss">
 <X class="w-4 h-4" />
 </button>
 </div>
 </Transition>
</template>
