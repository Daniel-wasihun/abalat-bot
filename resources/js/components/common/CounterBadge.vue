<script setup lang="ts">
/**
 * CounterBadge — an inline number bubble used on nav items, buttons, or cards
 * to indicate unread counts, pending items, or totals.
 *
 * @example
 * <CounterBadge :count="pendingFines" /> <!-- shows 5 -->
 * <CounterBadge :count="105" :max="99" /> <!-- shows 99+ -->
 * <CounterBadge :count="0" :showZero="true" />
 */
import { computed } from "vue";

interface Props {
 count: number;
 /** Cap the displayed number (shows "max+") */
 max?: number;
 /** Colour theme */
 variant?: "blue" | "red" | "green" | "yellow" | "gray";
 /** Display zero by default is hidden; set true to show */
 showZero?: boolean;
 size?: "sm" | "md";
}

const props = withDefaults(defineProps<Props>(), {
 max: 99,
 variant: "red",
 showZero: false,
 size: "md",
});

const VARIANT = {
 blue: "bg-brand-blue text-white",
 red: "bg-rose-500 text-white",
 green: "bg-brand-green text-white",
 yellow: "bg-brand-yellow text-white",
 gray: "bg-main-text/20 text-main-text/60",
};

const display = computed(() =>
 props.count > props.max ? `${props.max}+` : String(props.count),
);

const visible = computed(() => props.showZero || props.count > 0);
</script>

<template>
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 scale-75"
 enter-to-class="opacity-100 scale-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100 scale-100"
 leave-to-class="opacity-0 scale-75">
 <span
 v-if="visible"
 class="inline-flex items-center justify-center rounded-full font-bold tabular-nums leading-none"
 :class="[
 VARIANT[variant],
 size === 'sm'
 ? 'min-w-[16px] h-4 px-1 text-[9px]'
 : 'min-w-[20px] h-5 px-1.5 text-[10px]',
 ]">
 {{ display }}
 </span>
 </Transition>
</template>
