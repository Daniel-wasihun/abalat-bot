<script setup lang="ts">
/**
 * MediaAttachment — displays a Telegram feedback attachment (image, video, audio, or file).
 */
defineProps<{
  url: string;
  type?: string;
  fileName?: string;
}>();

const emit = defineEmits<{
  expand: [{ url: string; type: string }];
}>();

const isImage  = (type: string) => type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp)$/i.test(type);
const isVideo  = (type: string) => type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(type);
const isAudio  = (type: string) => type.startsWith('audio/') || /\.(mp3|ogg|wav|oga)$/i.test(type);
</script>

<template>
  <div class="rounded-xl overflow-hidden border border-card-border">
    <!-- Image -->
    <img
      v-if="isImage(type || '')"
      :src="url"
      :alt="fileName || 'attachment'"
      class="max-h-64 w-full object-cover cursor-pointer hover:opacity-90 transition-opacity"
      @click="emit('expand', { url, type: type || 'image' })"
    />
    <!-- Video -->
    <video
      v-else-if="isVideo(type || '')"
      :src="url"
      controls
      class="max-h-64 w-full"
    />
    <!-- Audio -->
    <audio
      v-else-if="isAudio(type || '')"
      :src="url"
      controls
      class="w-full p-3"
    />
    <!-- Generic file -->
    <a
      v-else
      :href="url"
      target="_blank"
      rel="noopener noreferrer"
      class="flex items-center gap-3 p-3 hover:bg-card-hover transition-colors text-sm text-accent font-medium"
    >
      <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
      </svg>
      <span class="truncate">{{ fileName || 'Download attachment' }}</span>
    </a>
  </div>
</template>
