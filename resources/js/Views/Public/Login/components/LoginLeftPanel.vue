<script setup lang="ts">
import LmsFlowAnimation from "@/components/animations/LmsFlowAnimation.vue";
</script>

<template>
 <div
 class="hidden lg:flex lg:w-1/2 relative items-center justify-center p-0 overflow-hidden bg-sidebar-bg border-r border-card-border">
 <!-- Animated Grid Background -->
 <div class="absolute inset-0 opacity-40 z-0 pointer-events-none">
 <div
 class="absolute inset-0 bg-[linear-gradient(to_right,#0b529c10_1px,transparent_1px),linear-gradient(to_bottom,#0b529c10_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
 <div
 class="absolute inset-0 bg-[radial-gradient(circle_800px_at_50%_-100px,#0b529c05,transparent)]"></div>
 </div>

 <!-- Floating Geometric Elements -->
 <div
 class="absolute top-20 right-20 w-64 h-64 border border-[#fba81c]/10 rounded-3xl rotate-12 animate-float-slow z-0"></div>
 <div
 class="absolute bottom-32 left-16 w-48 h-48 border border-[#0b529c]/10 rounded-full -rotate-12 animate-float-reverse z-0"></div>

 <!-- Glowing Accent Circles -->
 <div
 class="absolute top-1/4 right-1/4 w-4 h-4 bg-[#fba81c]/40 rounded-full blur-md animate-pulse"></div>
 <div
 class="absolute bottom-1/3 left-1/3 w-6 h-6 bg-[#0b529c]/40 rounded-full blur-lg animate-pulse delay-700"></div>

 <!-- Main Animation Component -->
 <div class="relative z-10 w-full h-full">
 <LmsFlowAnimation />
 </div>
 </div>
</template>

<style scoped>
/* Redundant animations removed - now handled by global utilities in style.css */
</style>
