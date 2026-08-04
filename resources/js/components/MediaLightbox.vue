<script setup lang="ts">
/**
 * MediaLightbox — fullscreen overlay for viewing Telegram media attachments.
 */
const props = defineProps<{
  modelValue: boolean;
  media: { url: string; type: string } | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [boolean];
}>();

const close = () => emit('update:modelValue', false);
const isImage = (type: string) => type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp)$/i.test(type);
const isVideo = (type: string) => type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(type);
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue && media"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
        @click.self="close"
      >
        <button
          @click="close"
          class="absolute top-4 right-4 text-white/70 hover:text-white transition-colors"
        >
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <img
          v-if="isImage(media.type)"
          :src="media.url"
          alt="media preview"
          class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl"
        />
        <video
          v-else-if="isVideo(media.type)"
          :src="media.url"
          controls
          autoplay
          class="max-w-full max-h-[90vh] rounded-xl shadow-2xl"
        />
      </div>
    </Transition>
  </Teleport>
</template>
