<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";

const progress = ref(0);

const updateProgress = () => {
 const totalHeight =
 document.documentElement.scrollHeight -
 document.documentElement.clientHeight;
 progress.value = (window.scrollY / totalHeight) * 100;
};

onMounted(() => window.addEventListener("scroll", updateProgress));
onUnmounted(() => window.removeEventListener("scroll", updateProgress));
</script>

<template>
 <div class="scroll-progress-container">
 <div
 class="scroll-progress-bar"
 :style="{ width: progress + '%' }"></div>
 </div>
</template>
