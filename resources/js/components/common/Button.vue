<script setup lang="ts">
/**
 * Button — Core interactive button component.
 * Fully self-contained inside the `component/` directory.
 *
 * @example
 * <Button variant="primary" :icon="Save">Save Action</Button>
 * <Button variant="danger" loading>Deleting…</Button>
 */
import { computed } from "vue";

interface Props {
 variant?:
 | "primary"
 | "secondary"
 | "danger"
 | "warning"
 | "ghost"
 | "soft-success"
 | "soft-warning"
 | "soft-danger"
 | "outline-danger"
 | "success-outline"
 | "soft-primary"
 | "accent"
 | "success"
 | "info"
 | "soft-secondary"
 | "soft-emerald" | "soft-blue";
 size?: "sm" | "md" | "lg" | "xl";
 type?: "button" | "submit" | "reset";
 /** Show a loading spinner and disable the button natively */
 loading?: boolean;
 disabled?: boolean;
 /** Full width block button */
 block?: boolean;
 /** Lucide icon component */
 icon?: any;
 /** Accessible name for screen readers */
 ariaLabel?: string;
 /** Tooltip/native title */
 title?: string;
}

const props = withDefaults(defineProps<Props>(), {
 variant: "primary",
 size: "md",
 type: "button",
 loading: false,
 disabled: false,
 block: false,
 ariaLabel: "",
 title: "",
});

const SIZES = {
 sm: "h-9 px-4 text-xs rounded-lg",
 md: "h-11 px-6 text-sm rounded-lg",
 lg: "h-13 px-8 text-base rounded-xl",
 xl: "h-14 px-10 text-base rounded-xl",
};

const VARIANTS = {
 primary:
 "bg-brand-blue text-white border border-brand-blue hover:bg-brand-blue-dark focus:ring-4 focus:ring-brand-blue/20",
 secondary:
 "bg-card-bg text-main-text border border-card-border/60 hover:bg-main-text/5 hover:border-main-text/20 focus:ring-4 focus:ring-main-text/5",
 danger: "bg-rose-500 text-white border border-rose-500 hover:bg-rose-600 focus:ring-4 focus:ring-rose-500/20",
 warning:
 "bg-brand-yellow text-white border border-brand-yellow hover:bg-brand-yellow-dark focus:ring-4 focus:ring-brand-yellow/20",
 ghost: "bg-transparent text-main-text/60 border border-transparent hover:bg-main-text/5 hover:text-main-text focus:ring-4 focus:ring-main-text/5 font-sans",
 "soft-success":
 "bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 hover:bg-emerald-600 hover:text-white leading-none",
 "soft-warning":
 "bg-amber-500/10 text-amber-600 border border-amber-500/20 hover:bg-amber-600 hover:text-white leading-none",
 "soft-danger":
 "bg-rose-500/10 text-rose-600 border border-rose-500/20 hover:bg-rose-600 hover:text-white leading-none",
 "outline-danger":
 "bg-transparent text-rose-500 border-2 border-dashed border-rose-500/20 hover:border-rose-500/50 hover:text-rose-600 leading-none",
 "success-outline":
 "bg-emerald-500/5 text-emerald-600 border border-emerald-500/30 hover:bg-emerald-500/10 hover:border-emerald-500/50 hover:text-emerald-700",
 "soft-primary":
 "bg-brand-blue/10 text-brand-blue border border-brand-blue/20 hover:bg-brand-blue hover:text-white leading-none",
 "accent":
 "bg-accent text-white border border-accent hover:bg-accent/90 focus:ring-4 focus:ring-accent/20",
 "success": 
 "bg-emerald-500 text-white border border-emerald-500 hover:bg-emerald-600 focus:ring-4 focus:ring-emerald-500/20",
 "info": 
 "bg-cyan-500 text-white border border-cyan-500 hover:bg-cyan-600 focus:ring-4 focus:ring-cyan-500/20",
 "soft-secondary": 
 "bg-main-text/5 text-main-text/60 border border-main-text/10 hover:bg-main-text/10 hover:text-main-text leading-none",
 "soft-emerald": 
 "bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white leading-none",
 "soft-blue": 
 "bg-brand-blue/10 text-brand-blue border border-brand-blue/20 hover:bg-brand-blue/20 leading-none",
};

const classes = computed(() => [
 "inline-flex flex-row items-center justify-center gap-2 font-bold transition-all duration-300 outline-none select-none active:scale-95",
 SIZES[props.size],
 VARIANTS[props.variant],
 props.block ? "w-full" : "",
 props.disabled || props.loading
 ? "opacity-50 cursor-not-allowed pointer-events-none"
 : "hover:-translate-y-0.5",
]);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
    :aria-label="ariaLabel || undefined"
    :title="title || undefined"
  >
 <!-- Loading spinner overrides icon if active -->
 <svg
 v-if="loading"
 class="w-4 h-4 animate-spin shrink-0"
 fill="none"
 viewBox="0 0 24 24">
 <circle
 class="opacity-25"
 cx="12"
 cy="12"
 r="10"
 stroke="currentColor"
 stroke-width="4" />
 <path
 class="opacity-75"
 fill="currentColor"
 d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
 </svg>
 <component v-else-if="icon" :is="icon" class="w-4.5 h-4.5 shrink-0" />

 <slot />
 </button>
</template>
