<script setup lang="ts">
import {
 AlertCircle,
 CheckCircle2,
 Power,
 AlertTriangle,
 Layers,
 Zap,
} from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import InlineAlert from "@/components/common/InlineAlert.vue";

interface Props {
 show: boolean;
 action: "activate" | "deactivate" | "delete" | null;
 itemsCount: number;
 itemName: string; // e.g. "Users", "Roles", "Permissions"
 loading?: boolean;
 error?: string;
}

const props = defineProps<Props>();
const emit = defineEmits(["close", "confirm"]);

const getActionIcon = () => {
 switch (props.action) {
 case "delete":
 return AlertTriangle;
 case "activate":
 return CheckCircle2;
 case "deactivate":
 return Power;
 default:
 return AlertCircle;
 }
};

const getThemeStyles = () => {
 switch (props.action) {
 case "delete":
 return {
 icon: "text-rose-500",
 bg: "bg-rose-500/5",
 border: "border-rose-500/10",
 shadow: "",
 button: "danger" as const,
 accent: "bg-rose-500",
 };
 case "activate":
 return {
 icon: "text-emerald-500",
 bg: "bg-emerald-500/5",
 border: "border-emerald-500/10",
 shadow: "",
 button: "success" as const,
 accent: "bg-emerald-500",
 };
 case "deactivate":
 return {
 icon: "text-amber-500",
 bg: "bg-amber-500/5",
 border: "border-amber-500/10",
 shadow: "shadow-amber-500/5",
 button: "warning" as const,
 accent: "bg-amber-500",
 };
 default:
 return {
 icon: "text-brand-blue",
 bg: "bg-brand-blue/5",
 border: "border-brand-blue/10",
 shadow: "",
 button: "primary" as const,
 accent: "bg-brand-blue",
 };
 }
};
</script>

<template>
 <Modal
 :show="show"
 size="confirm"
 :hide-header="true"
 @close="emit('close')">
 <div
 class="flex flex-col bg-main-bg w-full font-sans text-left relative overflow-hidden rounded-[40px]">
 <!-- Decorative Gradient -->
 <div
 class="absolute inset-x-0 top-0 h-48 bg-gradient-to-b opacity-20 pointer-events-none"
 :class="getThemeStyles().bg"></div>

 <div class="px-10 pb-12 pt-12 space-y-10 relative z-10 text-center">
 <!-- Icon & Header -->
 <div class="flex flex-col items-center gap-6">
 <div
 class="w-24 h-24 rounded-[32px] flex items-center justify-center border-2 transition-all hover:scale-110 duration-700 shadow-2xl"
 :class="[
 getThemeStyles().bg,
 getThemeStyles().icon,
 getThemeStyles().border,
 ]">
 <component :is="getActionIcon()" class="w-10 h-10" />
 </div>
 <div class="space-y-3">
 <h4
 class="text-2xl font-black text-main-text capitalize tracking-tight">
 {{ $tr("common.bulk_action") }}
 </h4>
 <p
 class="text-[13px] text-main-text/40 font-bold capitalize tracking-widest leading-relaxed max-w-[340px] mx-auto opacity-70">
 {{
 $tr("common.bulk_action_warning", {
 count: itemsCount,
 })
 }}
 </p>
 </div>
 </div>

 <!-- Items Summary Card -->
 <div
 class="bg-card-bg/40 border-2 border-card-border/40 rounded-[32px] overflow-hidden shadow-inner p-6 flex items-center gap-6 group">
 <div
 class="w-16 h-16 rounded-[24px] bg-main-bg/5 border border-card-border/30 flex items-center justify-center text-brand-blue group-hover:rotate-6 transition-transform">
 <Layers class="w-8 h-8 opacity-40" />
 </div>
 <div class="flex flex-col text-left">
 <h5
 class="text-[18px] font-black text-main-text leading-none mb-1 capitalize tracking-tight">
 {{ itemsCount }} {{ itemName }}
 </h5>
 <p
 class="text-[11px] text-main-text/40 font-black capitalize tracking-widest opacity-60">
 {{
 $tr("common.selected_items") ||
 "Items queued for bulk operation"
 }}
 </p>
 </div>
 </div>

 <!-- Delete Warning -->
 <InlineAlert v-if="action === 'delete'" type="error" class="">
 <div class="flex flex-col gap-1 text-left">
 <h4 class="text-[11px] font-black capitalize tracking-widest text-rose-500">
 DESTRUCTION WARNING
 </h4>
 <p class="text-[13px] font-medium leading-relaxed opacity-80 capitalize tracking-tight">
 {{ $tr("common.irreversible_warning") || "Warning: This action cannot be undone. Data will be permanently removed." }}
 </p>
 </div>
 </InlineAlert>

 <!-- Error Display -->
 <InlineAlert v-if="error" type="error">
 {{ error }}
 </InlineAlert>

 <!-- Actions -->
 <div class="flex gap-4 pt-4">
 <Button class="flex-1 font-bold tracking-tight capitalize px-6 border-card-border/60"
 variant="secondary"
 size="md"
 :disabled="loading"
 @click="emit('close')">
 {{ $tr("common.cancel") }}
 </Button>
 <Button
 :variant="getThemeStyles().button"
 size="md"
 :loading="loading"
 class="flex-1 font-bold tracking-tight shadow-2xl capitalize"
 :class="[
 action === 'activate' ? '' : 
 action === 'deactivate' ? 'shadow-amber-500/20' : 
 ''
 ]"
 @click="emit('confirm')">
 {{ $tr("common.confirm") || "Confirm Action" }}
 </Button>
 </div>
 </div>

 <!-- Bottom Progress Accent -->
 <div class="h-2 w-full bg-main-bg/5 relative">
 <div
 v-if="loading"
 class="absolute inset-0 animate-pulse"
 :class="getThemeStyles().accent"></div>
 </div>
 </div>
 </Modal>
</template>
