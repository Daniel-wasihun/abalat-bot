<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { ChevronDown } from "lucide-vue-next";

/**
 * TableSelect — A high-performance, dependency-free dropdown component.
 * Built with glassmorphism aesthetics and performance-optimized transitions.
 */

interface Option {
 label: string;
 value: string | number | boolean;
 icon?: any;
}

interface Props {
 modelValue: any;
 options: Option[];
 placeholder?: string;
}

const props = defineProps<Props>();
const emit = defineEmits(["update:modelValue", "change"]);

const isOpen = ref(false);
const selectContainer = ref<HTMLElement | null>(null);

const selectedOption = computed(() =>
 props.options.find((opt) => opt.value === props.modelValue),
);

const toggleSelect = () => {
 isOpen.value = !isOpen.value;
};

const selectOption = (option: Option) => {
 emit("update:modelValue", option.value);
 emit("change", option.value);
 isOpen.value = false;
};

// Mutual exclusivity: Close when clicking outside
const handleClickOutside = (event: MouseEvent) => {
 if (
 selectContainer.value &&
 !selectContainer.value.contains(event.target as Node)
 ) {
 isOpen.value = false;
 }
};

onMounted(() => {
 document.addEventListener("mousedown", handleClickOutside);
});

onUnmounted(() => {
 document.removeEventListener("mousedown", handleClickOutside);
});
</script>

<template>
 <div class="relative" ref="selectContainer">
 <button
 type="button"
 @click="toggleSelect"
 class="w-full h-10 px-4 flex items-center justify-between bg-card-bg border border-card-border/60 rounded-lg text-[14px] font-medium transition-all duration-300 hover:border-brand-blue/30 hover:-translate-y-0.5 active:scale-95 focus:outline-none"
 :class="isOpen ? 'border-brand-blue bg-brand-blue/5' : ''"
 aria-haspopup="listbox"
 :aria-expanded="isOpen">
 <div
 class="flex items-center gap-2.5 truncate pr-2 mr-auto"
 v-if="selectedOption">
 <component
 :is="selectedOption.icon"
 v-if="selectedOption.icon"
 class="w-4 h-4 text-brand-blue" />
 <span class="truncate text-main-text">{{
 selectedOption.label
 }}</span>
 </div>
 <div
 v-else
 class="flex items-center gap-2.5 truncate pr-2 text-main-text/30 font-normal italic">
 {{ placeholder || "Select..." }}
 </div>

 <ChevronDown
 class="w-4 h-4 text-main-text/30 transition-transform duration-500 ease-in-out shrink-0"
 :class="{ 'rotate-180 text-brand-blue': isOpen }" />
 </button>

 <!-- Dropdown List -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 scale-95 -translate-y-3"
 enter-to-class="opacity-100 scale-100 translate-y-0"
 leave-active-class="transition duration-100 ease-in"
 leave-from-class="opacity-100 scale-100 translate-y-0"
 leave-to-class="opacity-0 scale-95 -translate-y-1">
 <div
 v-if="isOpen"
 class="absolute z-100 mt-2 w-full bg-card-bg/95 border border-card-border/60 rounded-lg shadow-2xl overflow-hidden py-2 backdrop-blur-2xl"
 role="listbox">
 <div class="max-h-[320px] overflow-y-auto custom-scrollbar">
 <button
 v-for="option in options"
 :key="String(option.value)"
 type="button"
 @click="selectOption(option)"
 class="w-full px-4 py-2.5 flex items-center gap-3 text-left transition-all duration-200 group relative"
 :class="
 modelValue === option.value
 ? 'bg-brand-blue text-white'
 : 'hover:bg-brand-blue/5 text-main-text/70 hover:text-brand-blue'
 "
 role="option"
 :aria-selected="modelValue === option.value">
 <component
 :is="option.icon"
 v-if="option.icon"
 class="w-4 h-4 group-hover:scale-110 transition-transform"
 :class="
 modelValue === option.value
 ? 'text-white'
 : 'text-brand-blue/60'
 " />
 <span class="text-sm font-medium">{{
 option.label
 }}</span>
 <div
 v-if="modelValue === option.value"
 class="absolute left-0 w-1 h-1/2 bg-white rounded-r-full top-1/2 -translate-y-1/2" />
 </button>
 </div>
 </div>
 </Transition>
 </div>
</template>
