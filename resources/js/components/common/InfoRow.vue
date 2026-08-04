<script setup lang="ts">
/**
 * InfoRow — a horizontal key → value row used inside detail/view panels.
 * Stack multiple rows inside a SectionCard to build a clean record summary.
 *
 * @example
 * <InfoRow label="ISBN" :value="book.isbn" />
 * <InfoRow label="Status" :value="book.status" :icon="Tag" />
 * <InfoRow label="Date Issued" :value="formatDate(borrow.issued_at)" />
 * <InfoRow label="Notes">
 * <p class="text-sm text-main-text">{{ borrow.notes }}</p>
 * </InfoRow>
 */
interface Props {
 label: string;
 value?: string | number | null;
 /** Lucide icon placed before the value */
 icon?: any;
 /** Make the value text stand out (bold + primary colour) */
 highlight?: boolean;
 /** Stack label above value instead of side-by-side */
 stacked?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 highlight: false,
 stacked: false,
});

const displayValue = props.value ?? "—";
</script>

<template>
 <div
 class="flex gap-4 py-2 border-b border-card-border last:border-0"
 :class="stacked ? 'flex-col gap-0.5' : 'items-start'">
  <!-- Label -->
  <span
  class="text-[14px] font-bold text-main-text/30 capitalize tracking-[0.2em] shrink-0 leading-tight"
  :class="stacked ? 'mb-2' : 'w-56 pt-1'">
  {{ label }}
  </span>

  <!-- Value or slot -->
  <div class="flex items-center gap-3 flex-1 min-w-0">
  <component
  v-if="icon"
  :is="icon"
  class="w-4 h-4 shrink-0 text-brand-blue/60" />

  <slot>
  <span
  class="text-[16px] font-medium text-main-text leading-snug break-words"
  :class="highlight ? 'text-brand-blue font-bold' : ''">
  {{ displayValue }}
  </span>
  </slot>
  </div>
 </div>
</template>
