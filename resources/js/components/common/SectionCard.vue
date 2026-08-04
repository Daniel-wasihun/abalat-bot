<script setup lang="ts">
/**
 * SectionCard — a consistent bordered card used to visually group related
 * content within a page (e.g. a form section, a stats block, a list).
 *
 * @example
 * <SectionCard title="Borrowing Information" :icon="BookOpen">
 * <!-- any content -->
 * </SectionCard>
 *
 * <!-- With a header action button -->
 * <SectionCard title="Members">
 * <template #action><Button size="sm">Add</Button></template>
 * <!-- content -->
 * </SectionCard>
 */
interface Props {
 title?: string;
 description?: string;
 /** Lucide component or any renderable icon */
 icon?: any;
 /** Icon badge background colour class */
 iconBgClass?: string;
 /** Remove the default padding from the body area */
 noPadding?: boolean;
 /** Remove the card border (useful for transparent/background sections) */
 noBorder?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 iconBgClass: "bg-brand-blue/10",
 noPadding: false,
 noBorder: false,
});
</script>

<template>
 <div
 class="rounded-xl bg-card-bg transition-all"
 :class="[noBorder ? '' : 'border border-card-border']">
 <!-- Header (only rendered when there's a title, icon, description, or action slot) -->
 <div
 v-if="
 title || icon || description || $slots.action || $slots.header
 "
 class="flex items-start justify-between gap-4 px-6 py-5 border-b border-card-border">
 <div class="flex items-center gap-3 min-w-0">
 <!-- Icon badge -->
 <div
 v-if="icon"
 class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
 :class="iconBgClass">
 <component :is="icon" class="w-4.5 h-4.5 text-brand-blue" />
 </div>

 <div class="min-w-0">
 <h2
 class="text-[15px] font-medium text-main-text tracking-tight truncate">
 <slot name="title">{{ title }}</slot>
 </h2>
 <p
 v-if="description"
 class="text-[12px] text-main-text/40 mt-0.5 leading-relaxed">
 {{ description }}
 </p>
 </div>
 </div>

 <!-- Right-side header actions -->
 <div
 v-if="$slots.action || $slots.header"
 class="flex items-center gap-2 shrink-0">
 <slot name="action" />
 <slot name="header" />
 </div>
 </div>

 <!-- Body -->
 <div :class="noPadding ? '' : 'p-6'">
 <slot />
 </div>

 <!-- Optional footer -->
 <div
 v-if="$slots.footer"
 class="px-6 py-4 border-t border-card-border flex items-center justify-end gap-3">
 <slot name="footer" />
 </div>
 </div>
</template>
