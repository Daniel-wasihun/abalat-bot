<script setup lang="ts">
interface Props {
 badge: string;
 title: string;
 accent?: string;
 description?: string;
 centered?: boolean;
 isGrid?: boolean;
}

withDefaults(defineProps<Props>(), {
 centered: false,
 isGrid: false,
});
</script>

<template>
 <div
 :class="[
 'mb-24 animate-fade-up relative z-10',
 centered ? 'text-center' : 'text-left',
 ]">
 <!-- Badge -->
 <div
 :class="[
 'inline-flex items-center gap-2.5 px-5 py-2.5 bg-brand-blue/5 border border-brand-blue/10 rounded-full mb-8',
 centered ? 'mx-auto' : '',
 ]">
 <div class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></div>
 <span class="text-xs font-normal tracking-[0.2em] text-brand-blue">
 {{ badge }}
 </span>
 </div>

 <!-- Content Area -->
 <div
 :class="
 isGrid ? 'grid lg:grid-cols-2 gap-12 items-end' : 'space-y-6'
 ">
 <div :class="centered ? 'max-w-2xl mx-auto' : ''">
 <h2
 class="text-4xl md:text-5xl font-bold text-main-text tracking-tight leading-[1.1]">
 {{ title }} <br v-if="!centered && !isGrid && accent" />
 <span class="text-brand-blue">{{ accent }}</span>
 </h2>
 <div
 v-if="!centered"
 class="h-1 w-20 bg-brand-blue/10 mt-6 rounded-full"></div>
 </div>

 <div
 v-if="description || $slots.actions"
 :class="[
 'flex flex-col gap-8',
 centered
 ? 'items-center'
 : isGrid
 ? 'items-start'
 : 'items-start',
 isGrid
 ? 'max-w-xl'
 : centered
 ? 'max-w-2xl mx-auto'
 : 'max-w-xl',
 ]">
 <p
 v-if="description"
 class="text-main-text text-sm font-normal leading-relaxed">
 {{ description }}
 </p>
 <div v-if="$slots.actions">
 <slot name="actions" />
 </div>
 </div>
 </div>
 </div>
</template>
