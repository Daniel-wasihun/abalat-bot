<script setup lang="ts">
/**
 * FormField — wraps a label, an input, and an error message into one clean unit.
 * Use this whenever you need a standard labeled text input in a form.
 *
 * @example
 * <FormField label="Full Name" v-model="form.name" :error="errors.name" required />
 */
import { computed, ref } from "vue";

interface Props {
 modelValue: string | number | null;
 label?: string;
 type?:
 | "text"
 | "email"
 | "password"
 | "number"
 | "search"
 | "tel"
 | "url"
 | "date"
 | "textarea"
 | any;
 placeholder?: string;
 error?: string;
 hint?: string;
 icon?: any; // Lucide component or any renderable
 disabled?: boolean;
 readonly?: boolean;
 required?: boolean;
 autofocus?: boolean;
 /** Show a clear (×) button when the field has a value */
 clearable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 type: "text",
 disabled: false,
 readonly: false,
 required: false,
 autofocus: false,
 clearable: false,
});

const emit = defineEmits<{
 (e: "update:modelValue", value: string | number | null): void;
 (e: "input", event: Event): void;
 (e: "blur", event: FocusEvent): void;
 (e: "focus", event: FocusEvent): void;
 (e: "clear"): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);

const showClear = computed(
 () =>
 props.clearable &&
 props.modelValue !== null &&
 props.modelValue !== undefined &&
 String(props.modelValue).length > 0,
);

const inputClasses = computed(() => [
 "w-full h-11 bg-main-bg border border-input-border rounded-lg transition-all duration-300 outline-none text-[14px] font-normal text-main-text placeholder:text-main-text/40",
 props.icon ? "pl-11 pr-5" : "px-5",
 showClear.value ? "pr-10" : "",
 props.error
 ? "border-rose-500/40 bg-rose-500/5 focus:border-rose-500"
 : "hover:border-brand-blue/30 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/5",
 props.disabled ? "opacity-50 cursor-not-allowed" : "cursor-text",
]);

function onInput(e: Event) {
 const raw = (e.target as HTMLInputElement).value;
 const value: string | number | null =
 props.type === "number"
 ? raw === ""
 ? null
 : Number.isNaN(parseFloat(raw))
 ? null
 : parseFloat(raw)
 : raw;
 emit("update:modelValue", value);
 emit("input", e);
}

function onClear() {
 emit("update:modelValue", props.type === "number" ? null : "");
 emit("clear");
 inputRef.value?.focus();
}

/** Allow parent to call focus() programmatically */
defineExpose({ focus: () => inputRef.value?.focus() });
  const fieldId = `field-${Math.random().toString(36).slice(2, 9)}`;
</script>

<template>
  <div class="form-field w-full group">
  <!-- Label row -->
  <div v-if="label" class="flex items-center justify-between mb-1 px-1 text-left">
  <label :for="fieldId" class="text-[13px] font-normal capitalize text-main-text/70 cursor-pointer">
  {{ label
  }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
  </label>
  <!-- Optional right-side slot (e.g. "Forgot password?" link) -->
  <slot name="label-right" />
  </div>

  <!-- Input wrapper -->
  <div class="relative flex items-center">
  <!-- Left icon -->
  <div
  v-if="icon"
  class="absolute left-4 flex items-center justify-center pointer-events-none transition-colors"
  :class="
  error
  ? 'text-rose-500/60'
  : 'text-brand-blue/40 group-focus-within:text-brand-blue'
  ">
  <component :is="icon" class="w-4.5 h-4.5" />
  </div>

  <input
  :id="fieldId"
  ref="inputRef"
  :type="type"
  :value="modelValue"
  :placeholder="placeholder"
  :disabled="disabled"
  :readonly="readonly"
  :autofocus="autofocus"
  :class="inputClasses"
  @input="onInput"
  @blur="emit('blur', $event)"
  @focus="emit('focus', $event)"
  v-bind="$attrs" />

 <!-- Right slot (e.g. password-toggle button) or clear button -->
 <div class="absolute right-4 flex items-center gap-1">
 <slot name="trailing" />
 <button
 v-if="showClear"
 type="button"
 tabindex="-1"
 class="text-main-text/30 hover:text-main-text/70 transition-colors"
 @click="onClear">
 <svg
 xmlns="http://www.w3.org/2000/svg"
 class="w-3.5 h-3.5"
 viewBox="0 0 24 24"
 fill="none"
 stroke="currentColor"
 stroke-width="2.5"
 stroke-linecap="round"
 stroke-linejoin="round">
 <line x1="18" y1="6" x2="6" y2="18" />
 <line x1="6" y1="6" x2="18" y2="18" />
 </svg>
 </button>
 </div>
 </div>

 <!-- Error message (animated) -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 -translate-y-1"
 enter-to-class="opacity-100 translate-y-0">
 <p
 v-if="error"
 class="text-[12px] text-rose-500 mt-1.5 px-1 font-normal capitalize">
 {{ error }}
 </p>
 </Transition>

 <!-- Hint text -->
 <p
 v-if="hint && !error"
 class="text-[11px] text-main-text/40 mt-1.5 px-1">
 {{ hint }}
 </p>
 </div>
</template>

<style scoped>
/* Remove browser autofill yellow background */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
 -webkit-text-fill-color: var(--main-text);
 -webkit-box-shadow: 0 0 0px 1000px transparent inset;
 transition: background-color 5000s ease-in-out 0s;
}

/* Hide number spinners */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: textfield;
  margin: 0;
}
input[type="number"] {
  appearance: textfield;
  -moz-appearance: textfield;
}
</style>
