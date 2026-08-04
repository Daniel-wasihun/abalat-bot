<script setup lang="ts">
/**
 * TagGroup — displays a list of string tags, categories, or keywords.
 * Useful for author lists, subjects, LCC codes, etc.
 *
 * @example
 * <TagGroup :tags="['Science', 'Physics', 'Mechanics']" variant="blue" />
 */
import { computed } from "vue";

interface Props {
 tags: string[];
 /** Visual theme for the tags */
 variant?: "gray" | "blue" | "green" | "red";
 /** Max tags to show before truncating (e.g. "+ 2 more") */
 limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
 variant: "gray",
 limit: 0, // 0 = no limit
});

const THEMES = {
 gray: "bg-main-text/10 text-main-text/70 border-card-border",
 blue: "bg-brand-blue/10 text-brand-blue border-brand-blue/20",
 green: "bg-brand-green/10 text-brand-green border-brand-green/20",
 red: "bg-rose-500/10 text-rose-500 border-rose-500/20",
};

const displayedTags = computed(() => {
 if (props.limit > 0 && props.tags.length > props.limit) {
 return props.tags.slice(0, props.limit);
 }
 return props.tags;
});

const hiddenCount = computed(() => {
 if (props.limit > 0 && props.tags.length > props.limit) {
 return props.tags.length - props.limit;
 }
 return 0;
});
</script>

<template>
 <div class="flex flex-wrap items-center gap-1.5 list-none m-0 p-0">
 <span
 v-for="tag in displayedTags"
 :key="tag"
 class="inline-flex items-center px-2 py-0.5 rounded-md border text-[11px] font-medium whitespace-nowrap leading-none h-[22px]"
 :class="THEMES[variant]">
 {{ tag }}
 </span>

 <span
 v-if="hiddenCount > 0"
 class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-medium whitespace-nowrap text-main-text/50 bg-main-text/5 h-[22px]">
 +{{ hiddenCount }}
 </span>

 <span
 v-if="tags.length === 0"
 class="text-[12px] text-main-text/30 italic">
 None
 </span>
 </div>
</template>
