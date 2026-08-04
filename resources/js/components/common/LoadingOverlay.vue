<script setup lang="ts">
/**
 * LoadingOverlay — a full-area overlay spinner shown while async data loads.
 * Works both as a full-page overlay and as a local overlay inside a relative
 * container (position is 'absolute' by default, set `fixed` for global use).
 *
 * @example
 * <!-- Inside a card -->
 * <div class="relative min-h-40">
 * <LoadingOverlay v-if="loading" />
 * <!-- content -->
 * </div>
 *
 * <!-- Full page -->
 * <LoadingOverlay :fixed="true" message="Loading books…" />
 */
interface Props {
 /** Use fixed positioning (full-page) instead of absolute (local container) */
 fixed?: boolean;
 /** Optional message displayed below the spinner */
 message?: string;
 /** Blur the content behind the overlay */
 blur?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 fixed: false,
 blur: false,
});
</script>

<template>
 <div
 :class="[
 'inset-0 z-50 flex flex-col items-center justify-center gap-3',
 fixed ? 'fixed' : 'absolute',
 blur ? 'backdrop-blur-sm' : '',
 'bg-card-bg/60',
 ]">
 <!-- Spinner -->
 <div class="relative w-10 h-10">
 <!-- Outer track -->
 <div
 class="absolute inset-0 rounded-full border-4 border-brand-blue/10" />
 <!-- Spinning arc -->
 <div
 class="absolute inset-0 rounded-full border-4 border-transparent border-t-brand-blue animate-spin" />
 </div>

 <p v-if="message" class="text-[13px] text-main-text/50 font-normal">
 {{ message }}
 </p>
 </div>
</template>
