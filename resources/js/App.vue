<script setup lang="ts">
import { computed, watch, ref, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import ToastNotification from "@/components/ToastNotification.vue";
import ScrollProgress from "@/components/common/ScrollProgress.vue";

import { useAuthStore } from "@/stores/authStore";

const route = useRoute();
const authStore = useAuthStore();

const isDashboard = computed(() => route.path.startsWith("/dashboard"));

</script>

<template>
 <ScrollProgress />
 <router-view />
 <ToastNotification />
</template>
