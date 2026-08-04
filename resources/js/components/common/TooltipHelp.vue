<script setup lang="ts">
/**
 * TooltipHelp — a tiny inline (i) or (?) icon that reveals a tooltip on hover.
 * Useful for adding subtle context or explanations next to form labels.
 *
 * @example
 * <label>LCC Code <TooltipHelp text="Library of Congress Classification string" /></label>
 */
import Tooltip from "./Tooltip.vue";
import { HelpCircle } from "lucide-vue-next";

interface Props {
 text: string;
 position?: "top" | "bottom" | "left" | "right";
}

const props = withDefaults(defineProps<Props>(), {
 position: "top",
});
</script>

<template>
 <span class="inline-flex items-center align-middle ml-1.5 cursor-help">
 <Tooltip :text="text" :position="position">
 <HelpCircle
 class="w-[15px] h-[15px] text-main-text/30 hover:text-brand-blue transition-colors" />
 </Tooltip>
 </span>
</template>
