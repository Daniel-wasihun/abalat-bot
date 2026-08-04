<script setup lang="ts">
defineProps<{
 message?: string | string[];
}>();
</script>

<template>
 <Transition
 enter-active-class="transition duration-100 ease-out"
 enter-from-class="transform -translate-y-2 opacity-0"
 enter-to-class="transform translate-y-0 opacity-100">
 <p
 v-if="message"
 class="mt-2 text-sm font-normal text-red-500/90 flex items-center gap-2 ml-1 animate-pulse-subtle leading-normal">
 <!-- <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> -->
 {{ Array.isArray(message) ? message[0] : message }}
 </p>
 </Transition>
</template>

<style scoped>
.animate-pulse-subtle {
 animation: pulse-subtle 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {
 0%,
 100% {
 opacity: 1;
 }
 50% {
 opacity: 0.8;
 }
}
</style>
