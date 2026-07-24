<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[variantClass, sizeClass, 'app-btn']"
    v-bind="$attrs"
  >
    <!-- Loading spinner -->
    <svg
      v-if="loading"
      class="animate-spin shrink-0"
      :class="spinnerSize"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>

    <!-- Default slot -->
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: v => ['primary', 'ghost', 'danger', 'success', 'outline', 'outline-danger'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: v => ['sm', 'md', 'lg'].includes(v),
  },
  type:     { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  loading:  { type: Boolean, default: false },
});

const variantClass = computed(() => ({
  primary:        'app-btn--primary',
  ghost:          'app-btn--ghost',
  danger:         'app-btn--danger',
  success:        'app-btn--success',
  outline:        'app-btn--outline',
  'outline-danger': 'app-btn--outline-danger',
}[props.variant]));

const sizeClass = computed(() => ({
  sm: 'app-btn--sm',
  md: 'app-btn--md',
  lg: 'app-btn--lg',
}[props.size]));

const spinnerSize = computed(() => ({
  sm: 'w-3 h-3',
  md: 'w-3.5 h-3.5',
  lg: 'w-4 h-4',
}[props.size]));
</script>
