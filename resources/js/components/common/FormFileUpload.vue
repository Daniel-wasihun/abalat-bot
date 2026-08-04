<script setup lang="ts">
import { ref } from "vue";
import { UploadCloud, FileText, X, CheckCircle2 } from "lucide-vue-next";

/**
 * FormFileUpload
 * A premium, high-fidelity file upload component for the 'Elite' design system.
 * Features smooth transitions, dashed borders, and clear state visualization.
 */

interface Props {
 modelValue: File | null;
 label?: string;
 accept?: string;
 id?: string;
 required?: boolean;
 placeholder?: string;
 error?: string;
}

const props = withDefaults(defineProps<Props>(), {
 accept: ".csv",
 id: "file-upload",
 placeholder: "user.upload_csv",
});

const emit = defineEmits(["update:modelValue", "change"]);

const handleFileChange = (event: any) => {
 const file = event.target.files[0];
 if (file) {
 emit("update:modelValue", file);
 emit("change", event);
 }
 // Reset input so the same file can be selected again if cleared
 event.target.value = '';
};

const clearFile = () => {
 emit("update:modelValue", null);
};

const formatSize = (bytes: number) => {
 if (bytes === 0) return '0 Bytes';
 const k = 1024;
 const sizes = ['Bytes', 'KB', 'MB', 'GB'];
 const i = Math.floor(Math.log(bytes) / Math.log(k));
 return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
 <div class="flex flex-col w-full group text-left font-sans">
 <!-- Label Layer -->
 <div v-if="label" class="flex items-center justify-between mb-2.5 px-1">
 <label :for="id" class="text-[13px] font-semibold text-main-text tracking-tight flex items-center gap-1.5">
 {{ label }}
 <span v-if="required" class="text-rose-500 font-bold">*</span>
 </label>
 <transition name="fade">
 <span v-if="modelValue" class="text-[11px] font-bold text-emerald-500 flex items-center gap-1">
 <CheckCircle2 class="w-3 h-3" />
 {{ $tr('status.ready') || 'Ready' }}
 </span>
 </transition>
 </div>

 <div class="relative group/field">
 <input
 type="file"
 :accept="accept"
 @change="handleFileChange"
 :id="id"
 class="hidden" />

 <label
 :for="id"
 :class="[
 'flex items-center w-full min-h-[56px] bg-card-bg/50 border-2 border-dashed rounded-2xl transition-all duration-300 cursor-pointer overflow-hidden relative group/label px-4',
 modelValue
 ? 'border-brand-blue bg-brand-blue/[0.03] '
 : error 
 ? 'border-rose-500/50 bg-rose-500/[0.03]' 
 : 'border-card-border/60 hover:border-brand-blue/50 hover:bg-card-hover/20'
 ]">
 
 <!-- Icon Layer -->
 <div
 class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-all duration-300 mr-4"
 :class="[
 modelValue 
 ? 'bg-brand-blue text-white rotate-0' 
 : 'bg-main-bg/50 text-main-text/20 group-hover/label:text-brand-blue group-hover/label:bg-brand-blue/10 -rotate-3 group-hover/label:rotate-0'
 ]">
 <UploadCloud v-if="!modelValue" class="w-5 h-5" />
 <FileText v-else class="w-5 h-5" />
 </div>

 <!-- Text Content -->
 <div class="flex flex-col min-w-0 flex-1 py-1 pr-2">
 <span 
 class="text-sm font-semibold truncate transition-colors"
 :class="modelValue ? 'text-brand-blue' : 'text-main-text/40 group-hover/label:text-main-text/60'">
 {{ modelValue ? modelValue.name : $tr(placeholder) }}
 </span>
 <span 
 v-if="!modelValue"
 class="text-[10px] font-medium text-main-text/30 tracking-tight mt-0.5">
 {{ accept ? `Allowed formats: ${accept}` : 'Maximum size: 10MB' }}
 </span>
 <span 
 v-else
 class="text-[10px] font-bold text-brand-blue/60 tracking-tight mt-0.5 capitalize">
 {{ formatSize(modelValue.size) }}
 </span>
 </div>

 <!-- Action Layer -->
 <div v-if="modelValue" class="shrink-0">
 <button
 @click.prevent="clearFile"
 type="button"
 class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all transform active:scale-95">
 <X class="w-4 h-4" />
 </button>
 </div>
 <div v-else class="shrink-0 lg:block hidden">
 <span
 class="text-[11px] font-bold text-brand-blue bg-brand-blue/10 px-4 py-2 rounded-xl transition-all group-hover/label:bg-brand-blue group-hover/label:text-white"
 >Browse</span
 >
 </div>
 </label>
 </div>

 <!-- Error Layer -->
 <transition name="slide-up">
 <p v-if="error" class="text-[11px] font-bold text-rose-500 mt-2 px-1 flex items-center gap-1 capitalize tracking-wide">
 <X class="w-3 h-3" />
 {{ error }}
 </p>
 </transition>
 </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
 transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
 opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
 transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
 opacity: 0;
 transform: translateY(-4px);
}
</style>
