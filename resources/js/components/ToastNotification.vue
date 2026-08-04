<script setup lang="ts">
import { ref } from "vue";
import { useToastStore } from "@/stores/toast";
import {
 CheckCircle,
 AlertCircle,
 AlertTriangle,
 Info,
 X,
} from "lucide-vue-next";

const toastStore = useToastStore();

// No expansion logic needed
</script>

<template>
 <div
 class="fixed top-6 right-6 z-100000 flex flex-col gap-3 pointer-events-none"
 style="min-width: 280px; max-width: 420px; width: max-content">
 <TransitionGroup name="toast" tag="div" class="flex flex-col gap-3">
 <div
 v-for="toast in toastStore.toasts"
 :key="toast.id"
 class="pointer-events-auto group relative flex items-start gap-3 py-2.5 px-4 rounded-xl shadow-lg transition-all duration-300 hover:-translate-x-0.5 border"
 style="min-width: 280px; max-width: 420px"
 :class="[
 toast.type === 'success'
 ? 'bg-emerald-600 border-emerald-500 text-white'
 : toast.type === 'error'
 ? 'bg-rose-600 border-rose-500 text-white'
 : toast.type === 'warning' || toast.type === 'info'
 ? 'bg-amber-500 border-amber-400 text-white'
 : 'bg-slate-700 border-slate-600 text-white',
 ]">
 <!-- Icon -->
 <div class="shrink-0 flex items-center justify-center mt-0.5">
 <CheckCircle
 v-if="toast.type === 'success'"
 class="w-4.5 h-4.5 text-white" />
 <AlertCircle
 v-else-if="toast.type === 'error'"
 class="w-4.5 h-4.5 text-white" />
 <AlertTriangle
 v-else-if="toast.type === 'warning'"
 class="w-4.5 h-4.5 text-white" />
 <Info v-else class="w-4.5 h-4.5 text-white" />
 </div>

 <!-- Message body -->
 <div class="flex-1 min-w-0 py-0.5">
 <p class="text-sm font-normal leading-snug wrap-break-word">
 {{ toast.message }}
 </p>
 </div>

 <!-- Close button -->
 <button
 @click="toastStore.removeToast(toast.id)"
 class="shrink-0 p-1 rounded-lg bg-white/10 hover:bg-white/20 transition-all text-white active:scale-95 flex items-center justify-center cursor-pointer mt-0.5"
 aria-label="Close notification">
 <X class="w-3.5 h-3.5 stroke-[3px]" />
 </button>
 </div>
 </TransitionGroup>
 </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
 transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-enter-from {
 opacity: 0;
 transform: translateX(100px) scale(0.9);
}

.toast-leave-to {
 opacity: 0;
 transform: translateX(50px) scale(0.9);
}
</style>
