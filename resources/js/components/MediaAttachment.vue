<template>
  <div class="media-attachment">

    <!-- ── IMAGE ────────────────────────────────────── -->
    <div
      v-if="isImage"
      class="media-thumb-wrap group"
      @click="$emit('expand', { url, type: 'image', fileName })"
    >
      <img
        :src="url"
        :alt="fileName || 'Attachment'"
        class="media-thumb-img"
        @error="onImgError"
      />
      <!-- Expand overlay -->
      <div class="media-thumb-overlay">
        <svg class="w-6 h-6 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0zM11 8v6M8 11h6" />
        </svg>
        <span class="text-white text-xs font-semibold drop-shadow">Click to expand</span>
      </div>
    </div>

    <!-- ── VIDEO ────────────────────────────────────── -->
    <div
      v-else-if="isVideo"
      class="media-thumb-wrap group"
      @click="$emit('expand', { url, type: 'video', fileName })"
    >
      <video
        :src="url"
        class="media-thumb-img object-contain bg-black"
        preload="metadata"
        muted
        @loadedmetadata="e => e.target.pause()"
      />
      <!-- Play icon overlay -->
      <div class="media-thumb-overlay">
        <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur flex items-center justify-center ring-2 ring-white/60">
          <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z" />
          </svg>
        </div>
        <span class="text-white text-xs font-semibold drop-shadow">Click to play</span>
      </div>
    </div>

    <!-- ── AUDIO / VOICE ─────────────────────────────── -->
    <div v-else-if="isAudio" class="media-audio-wrap">
      <div class="flex items-center gap-2 mb-2">
        <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-950/40 flex items-center justify-center shrink-0">
          <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
        </div>
        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Voice Message</span>
      </div>
      <audio controls :src="url" class="w-full h-9 rounded-lg" />
    </div>

    <!-- ── DOCUMENT / OTHER ──────────────────────────── -->
    <a
      v-else
      :href="url"
      target="_blank"
      rel="noopener noreferrer"
      class="media-download-btn"
    >
      <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        </svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">
          {{ fileName || 'Download Attachment' }}
        </p>
        <p class="text-[10px] text-slate-400 uppercase tracking-wider">{{ type || 'File' }}</p>
      </div>
      <svg class="w-4 h-4 text-slate-400 shrink-0 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
      </svg>
    </a>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  url:      { type: String, required: true },
  type:     { type: String, default: '' },    // 'image', 'video', 'voice', 'document', etc.
  fileName: { type: String, default: '' },
});

defineEmits(['expand']);

// Detect by explicit type or infer from URL extension
const isImage = computed(() => {
  if (props.type === 'image') return true;
  return /\.(jpe?g|png|gif|webp|bmp|svg)(\?.*)?$/i.test(props.url);
});

const isVideo = computed(() => {
  if (props.type === 'video') return true;
  return /\.(mp4|webm|ogg|mov|avi)(\?.*)?$/i.test(props.url);
});

const isAudio = computed(() => {
  if (['voice', 'audio'].includes(props.type)) return true;
  return /\.(mp3|ogg|wav|m4a|oga)(\?.*)?$/i.test(props.url);
});

const onImgError = (e) => {
  // Fallback: show broken image placeholder
  e.target.src = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 24 24' fill='none' stroke='%23cbd5e1' stroke-width='1.5'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'/%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'/%3E%3Cpolyline points='21 15 16 10 5 21'/%3E%3C/svg%3E`;
};
</script>

<style scoped>
.media-attachment {
  width: 100%;
}

/* Thumbnail container */
.media-thumb-wrap {
  position: relative;
  display: inline-block;
  cursor: pointer;
  border-radius: 12px;
  overflow: hidden;
  max-width: 100%;
  border: 1px solid rgba(148, 163, 184, 0.25);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: box-shadow 0.2s ease, transform 0.15s ease;
}
.media-thumb-wrap:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

.media-thumb-img {
  display: block;
  max-height: 220px;
  max-width: 100%;
  width: auto;
  object-fit: cover;
}

/* Overlay for expand hint */
.media-thumb-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.42);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  opacity: 0;
  transition: opacity 0.2s ease;
}
.media-thumb-wrap:hover .media-thumb-overlay {
  opacity: 1;
}

/* Audio wrap */
.media-audio-wrap {
  padding: 12px;
  border-radius: 12px;
  background: rgba(245, 158, 11, 0.06);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

/* Download button */
.media-download-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: rgba(248, 250, 252, 0.8);
  text-decoration: none;
  transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.media-download-btn:hover {
  background: white;
  border-color: rgba(148, 163, 184, 0.5);
  box-shadow: 0 3px 10px rgba(0,0,0,0.09);
}

:global(.dark) .media-download-btn {
  background: rgba(30, 41, 59, 0.7);
  border-color: rgba(51, 65, 85, 0.5);
}
:global(.dark) .media-download-btn:hover {
  background: rgba(30, 41, 59, 1);
}
</style>
