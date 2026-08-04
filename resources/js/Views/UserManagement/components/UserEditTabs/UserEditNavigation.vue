<script setup lang="ts">
const props = defineProps<{
 tabs: Array<{
 id: string;
 label: string;
 icon: any;
 }>;
 activeTab: string;
}>();

const emit = defineEmits(["update:activeTab"]);
</script>

<template>
 <div
 class="sticky top-0 -mx-8 bg-card-bg border-b border-card-border/60 px-8 pt-2 pb-0 z-50">
 <div class="flex items-end gap-1 px-2 overflow-x-auto no-scrollbar">
 <button
 v-for="tab in tabs"
 :key="tab.id"
 @click="emit('update:activeTab', tab.id)"
 :class="[
 'group flex items-center gap-3 px-8 py-3 transition-all duration-300 rounded-t-[1.5rem] font-normal text-base relative cursor-pointer whitespace-nowrap outline-none',
 activeTab === tab.id
 ? 'bg-card-bg text-accent -mb-px border-t border-x border-card-border/60 active-tab-curve z-10 shadow-[0_-15px_30px_-15px_rgba(0,0,0,0.1)]'
 : 'text-main-text/40 hover:text-main-text/80 bg-main-bg/5 border-transparent',
 ]">
 <component
 :is="tab.icon"
 class="w-5 h-5"
 :class="
 activeTab === tab.id
 ? 'text-accent'
 : 'text-main-text/30 group-hover:text-main-text/50'
 " />
 {{
 tab.id === "permissions"
 ? $tr("user.permissions")
 : tab.label
 }}
 </button>
 </div>
 </div>
</template>
