<template>
  <teleport to="body">
    <transition name="lightbox">
      <div
        v-if="modelValue"
        class="lightbox-backdrop"
        @click.self="close"
        role="dialog"
        aria-modal="true"
        aria-label="Media viewer"
      >
        <!-- Close button -->
        <button class="lightbox-close" @click="close" aria-label="Close">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Download button (images & videos) -->
        <button
          v-if="media.url"
          @click.prevent="downloadMedia"
          class="lightbox-action-btn"
          :disabled="downloading"
        >
          <svg v-if="!downloading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="hidden sm:inline">{{ downloading ? 'Downloading...' : 'Download' }}</span>
        </button>

        <!-- Media content panel -->
        <div class="lightbox-content" @click.stop>

          <!-- IMAGE -->
          <img
            v-if="media.type === 'image'"
            :src="media.url"
            :alt="media.fileName || 'Attachment'"
            class="lightbox-image"
            @error="onImgError"
          />

          <!-- VIDEO -->
          <video
            v-else-if="media.type === 'video'"
            :src="media.url"
            class="lightbox-video"
            controls
            autoplay
            playsinline
          />

          <!-- FALLBACK -->
          <div v-else class="lightbox-fallback">
            <svg class="w-12 h-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm text-slate-400 mt-3">Preview not available</p>
            <AppButton variant="primary" size="sm" @click="downloadMedia" class="mt-4 inline-flex items-center gap-1.5">
              Download File ↗
            </AppButton>
          </div>

        </div>

        <!-- File name caption -->
        <p v-if="media.fileName" class="lightbox-caption">
          {{ media.fileName }}
        </p>

      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AppButton from './AppButton.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  media: {
    type: Object,
    default: () => ({ url: '', type: '', fileName: '' }),
  },
});

const emit = defineEmits(['update:modelValue']);

const close = () => emit('update:modelValue', false);

const downloading = ref(false);

const downloadMedia = async () => {
  if (downloading.value) return;
  downloading.value = true;
  try {
    const res = await fetch(props.media.url);
    const blob = await res.blob();
    const blobUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = blobUrl;
    let filename = props.media.fileName;
    if (!filename) {
      const ext = props.media.type === 'video' ? 'mp4' : 'jpg';
      filename = `attachment.${ext}`;
    }
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(blobUrl);
  } catch (err) {
    console.error('Download failed:', err);
    // Fallback: trigger download in the same tab via normal navigation if CORS allows
    const link = document.createElement('a');
    link.href = props.media.url;
    link.download = props.media.fileName || 'download';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } finally {
    downloading.value = false;
  }
};

const onImgError = (e) => {
  e.target.src = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='1.2'%3E%3Crect x='3' y='3' width='18' height='18' rx='2'/%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'/%3E%3Cpolyline points='21 15 16 10 5 21'/%3E%3C/svg%3E`;
};

// Keyboard: ESC to close
const onKeydown = (e) => { if (e.key === 'Escape') close(); };

// Lock body scroll while open
watch(() => props.modelValue, (open) => {
  document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* ── Backdrop ─────────────────────────────── */
.lightbox-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9000;
  background: rgba(0, 0, 0, 0.88);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

/* ── Close button ─────────────────────────── */
.lightbox-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s ease;
  z-index: 10;
}
.lightbox-close:hover { background: rgba(255, 255, 255, 0.25); }

/* ── Download / Action button ─────────────── */
.lightbox-action-btn {
  position: absolute;
  top: 16px;
  left: 16px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 20px;
  background: rgba(245, 158, 11, 0.9);
  border: 1px solid rgba(245, 158, 11, 0.6);
  color: white;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.1s ease;
  z-index: 10;
  box-shadow: 0 2px 10px rgba(245, 158, 11, 0.4);
}
.lightbox-action-btn:hover {
  background: rgba(217, 119, 6, 0.95);
  transform: translateY(-1px);
}

/* ── Content panel ────────────────────────── */
.lightbox-content {
  max-width: min(90vw, 1100px);
  max-height: min(85vh, 900px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-image {
  display: block;
  max-width: 100%;
  max-height: min(80vh, 860px);
  width: auto;
  height: auto;
  border-radius: 12px;
  object-fit: contain;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
}

.lightbox-video {
  display: block;
  max-width: 100%;
  max-height: min(80vh, 860px);
  border-radius: 12px;
  outline: none;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
}

.lightbox-fallback {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* ── Caption ──────────────────────────────── */
.lightbox-caption {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 12px;
  color: rgba(255, 255, 255, 0.55);
  font-weight: 500;
  text-align: center;
  max-width: 80%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── Transition ───────────────────────────── */
.lightbox-enter-active,
.lightbox-leave-active { transition: opacity 0.2s ease; }
.lightbox-enter-from,
.lightbox-leave-to     { opacity: 0; }

.lightbox-enter-active .lightbox-content,
.lightbox-leave-active .lightbox-content { transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1); }
.lightbox-enter-from .lightbox-content   { transform: scale(0.88); }
.lightbox-leave-to .lightbox-content     { transform: scale(0.92); }
</style>
