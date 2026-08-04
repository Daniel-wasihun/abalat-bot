<script setup lang="ts">
/**
 * TabBar — a horizontal tab navigation strip.
 * Emits 'update:modelValue' so it works with v-model.
 *
 * @example
 * const tabs = [
 * { key: 'details', label: 'Details', icon: Info },
 * { key: 'borrows', label: 'Borrows', icon: BookOpen, badge: 5 },
 * { key: 'history', label: 'History' },
 * ];
 * <TabBar v-model="activeTab" :tabs="tabs" />
 * <div v-if="activeTab === 'details'">…</div>
 */
interface Tab {
 key: string;
 label: string;
 /** Lucide (or any) icon component */
 icon?: any;
 /** Optional numeric badge shown on the tab */
 badge?: number;
 /** Disable this particular tab */
 disabled?: boolean;
}

interface Props {
 modelValue: string;
 tabs: Tab[];
}

defineProps<Props>();
const emit = defineEmits<{ (e: "update:modelValue", key: string): void }>();
</script>

<template>
 <div
 class="flex items-center gap-1 border-b border-card-border overflow-x-auto hide-scrollbar">
 <button
 v-for="tab in tabs"
 :key="tab.key"
 type="button"
 :disabled="tab.disabled"
 class="relative flex items-center gap-2 px-4 py-3 text-[13px] font-medium whitespace-nowrap transition-all duration-200 shrink-0 border-b-2 -mb-[2px] outline-none disabled:opacity-40 disabled:cursor-not-allowed"
 :class="[
 modelValue === tab.key
 ? 'border-brand-blue text-brand-blue'
 : 'border-transparent text-main-text/50 hover:text-main-text hover:border-main-text/20',
 ]"
 @click="!tab.disabled && emit('update:modelValue', tab.key)">
 <component
 v-if="tab.icon"
 :is="tab.icon"
 class="w-4 h-4 shrink-0" />
 {{ tab.label }}

 <!-- Badge -->
 <span
 v-if="tab.badge !== undefined"
 class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold"
 :class="
 modelValue === tab.key
 ? 'bg-brand-blue text-white'
 : 'bg-main-text/10 text-main-text/60'
 ">
 {{ tab.badge }}
 </span>
 </button>
 </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
 display: none;
}
.hide-scrollbar {
 -ms-overflow-style: none;
 scrollbar-width: none;
}
</style>
