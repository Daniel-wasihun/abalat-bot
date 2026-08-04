<script setup lang="ts">
import { Save, AlertCircle, type LucideIcon } from "lucide-vue-next";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";

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
 icon?: LucideIcon;
 loading?: boolean;
 }>(),
 {
 icon: Save,
 loading: false,
 },
);

const emit = defineEmits(["close", "confirm"]);
</script>

<template>
 <Modal
 :show="show"
 size="confirm"
 :hide-header="true"
 @close="emit('close')">
 <div
 class="flex flex-col bg-card-bg w-full font-sans text-left relative overflow-hidden rounded-[40px]">
 <!-- Decorative Gradient -->
 <div
 class="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-brand-blue/5 to-transparent pointer-events-none"></div>

 <div class="px-10 pb-12 pt-12 space-y-10 relative z-10 text-center">
 <!-- Icon & Header -->
 <div class="flex flex-col items-center gap-6">
 <div
 class="w-24 h-24 rounded-[32px] bg-brand-blue/5 flex items-center justify-center text-brand-blue border-2 border-brand-blue/10 shadow-2xl transition-all hover:scale-110 duration-700">
 <component :is="icon" class="w-10 h-10" />
 </div>
 <div class="space-y-3">
 <h4
 class="text-2xl font-black text-main-text capitalize tracking-tight">
 {{ title }}
 </h4>
 <p
 class="text-[13px] text-main-text/40 font-bold capitalize tracking-widest leading-relaxed max-w-[320px] mx-auto opacity-70">
 {{ description }}
 </p>
 </div>
 </div>

 <!-- Summary Grid -->
 <div
 v-if="summary && summary.length"
 class="bg-card-bg/40 border-2 border-card-border/40 rounded-[32px] overflow-hidden shadow-inner translate-y-2">
 <div
 v-for="(item, index) in summary"
 :key="item.label"
 :class="[
 'flex justify-between items-center px-8 py-5 transition-colors',
 index !== summary.length - 1
 ? 'border-b border-card-border/20'
 : '',
 ]">
 <span
 class="text-[10px] font-black text-main-text/30 capitalize tracking-[0.2em]">
 {{ item.label }}
 </span>
 <span
 class="text-[13px] font-black text-main-text truncate bg-main-bg/5 px-4 py-1.5 rounded-xl border border-card-border/30 capitalize tracking-tight">
 {{ item.value }}
 </span>
 </div>
 </div>

 <!-- Actions -->
 <div class="flex gap-4 pt-4">
 <Button
 variant="secondary"
 size="md"
 class="!flex-1 !h-14 !rounded-2xl !bg-main-bg/5 !border-none !text-main-text/40 !font-black capitalize tracking-widest hover:!bg-rose-500 hover:!text-white transition-all shadow-none"
 @click="emit('close')">
 {{ $tr("common.abort") }}
 </Button>
 <Button
 variant="accent"
 size="md"
 class="!flex-1 !h-14 !rounded-2xl !font-black capitalize tracking-widest "
 :loading="loading"
 @click="emit('confirm')">
 {{
 loading
 ? $tr("common.processing")
 : $tr("common.confirm")
 }}
 </Button>
 </div>
 </div>

 <!-- Animated Status Loader -->
 <div class="h-1.5 w-full bg-main-bg/5 relative">
 <div
 v-if="loading"
 class="absolute inset-0 bg-brand-blue/40 animate-pulse"></div>
 </div>
 </div>
 </Modal>
</template>
