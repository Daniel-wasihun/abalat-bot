<script setup lang="ts">
/**
 * PageTitle — page-level heading with an optional icon badge, subtitle,
 * breadcrumb trail, and a right-side actions slot.
 * Meant to appear at the top of every admin/management page.
 *
 * @example
 * <PageTitle title="Books" :icon="BookOpen" subtitle="Manage the book catalogue">
 * <template #actions>
 * <Button :icon="Plus" @click="openModal">Add Book</Button>
 * </template>
 * </PageTitle>
 */
interface Props {
 title: string;
 subtitle?: string;
 /** Lucide component */
 icon?: any;
 /** Colour applied to the icon badge background */
 iconBgClass?: string;
 /** Colour applied to the icon itself */
 iconColorClass?: string;
 /** Simple breadcrumb array, e.g. ['Home', 'Cataloging', 'Books'] */
 breadcrumb?: string[];
}

const props = withDefaults(defineProps<Props>(), {
 iconBgClass: "bg-brand-blue/10",
 iconColorClass: "text-brand-blue",
});
</script>

<template>
 <div class="flex items-start justify-between gap-4 flex-wrap">
 <!-- Left: icon + text -->
 <div class="flex items-center gap-4 min-w-0">
 <!-- Icon badge -->
 <div
 v-if="icon"
 class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
 :class="iconBgClass">
 <component
 :is="icon"
 class="w-5.5 h-5.5"
 :class="iconColorClass" />
 </div>

 <div class="min-w-0">
 <!-- Breadcrumb -->
 <nav
 v-if="breadcrumb?.length"
 class="flex items-center gap-1 mb-0.5">
 <template v-for="(crumb, i) in breadcrumb" :key="i">
 <span class="text-[11px] text-main-text/40">{{
 crumb
 }}</span>
 <span
 v-if="i < breadcrumb.length - 1"
 class="text-[11px] text-main-text/20"
 >/</span
 >
 </template>
 </nav>

 <h1
 class="text-[22px] font-bold text-main-text tracking-tight leading-snug truncate">
 <slot name="title">{{ title }}</slot>
 </h1>

 <p
 v-if="subtitle"
 class="text-[13px] text-main-text/40 mt-0.5 leading-relaxed">
 {{ subtitle }}
 </p>
 </div>
 </div>

 <!-- Right: action buttons -->
 <div
 v-if="$slots.actions"
 class="flex items-center gap-3 shrink-0 flex-wrap">
 <slot name="actions" />
 </div>
 </div>
</template>
