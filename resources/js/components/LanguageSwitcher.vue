<template>
  <div class="lang-switcher" @click.stop>
    <button
      class="lang-trigger"
      @click="open = !open"
      :aria-expanded="open"
      aria-haspopup="listbox"
    >
      <span class="text-base leading-none">{{ current.flag }}</span>
      <span class="lang-trigger-name">{{ current.name }}</span>
      <svg
        class="w-3 h-3 text-slate-400 transition-transform duration-200 shrink-0"
        :class="open ? 'rotate-180' : ''"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <transition name="dropdown">
      <div v-if="open" class="lang-menu" role="listbox">
        <button
          v-for="loc in LOCALES"
          :key="loc.code"
          class="lang-option"
          :class="{ 'lang-option--active': loc.code === current.code }"
          role="option"
          :aria-selected="loc.code === current.code"
          @click="select(loc.code)"
        >
          <span class="text-base leading-none">{{ loc.flag }}</span>
          <span class="flex-1 text-left text-sm font-medium">{{ loc.name }}</span>
          <svg
            v-if="loc.code === current.code"
            class="w-3.5 h-3.5 text-amber-500 shrink-0"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n, LOCALES, setLocale } from '../i18n.js';

const { locale } = useI18n();
const open = ref(false);

const current = computed(() =>
  LOCALES.find(l => l.code === locale.value) || LOCALES[0]
);

const select = (code) => {
  setLocale(code);
  open.value = false;
};

// Click outside to close
const close = () => { open.value = false; };
onMounted(() => window.addEventListener('click', close));
onUnmounted(() => window.removeEventListener('click', close));
</script>

<style scoped>
.lang-switcher {
  position: relative;
}

/* Trigger button */
.lang-trigger {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: rgba(248, 250, 252, 0.8);
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
  /* Subtle shadow so it's noticeable */
  box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 0 0 1px rgba(148,163,184,0.15);
}
.lang-trigger:hover {
  background: white;
  border-color: rgba(245, 158, 11, 0.4);
  box-shadow: 0 2px 8px rgba(245,158,11,0.15), 0 0 0 1px rgba(245,158,11,0.2);
}

:global(.dark) .lang-trigger {
  background: #0f172a;
  border-color: rgba(51, 65, 85, 0.7);
  box-shadow: 0 1px 4px rgba(0,0,0,0.3), 0 0 0 1px rgba(51,65,85,0.4);
}
:global(.dark) .lang-trigger:hover {
  background: #1e293b;
  border-color: rgba(245, 158, 11, 0.6);
  box-shadow: 0 2px 8px rgba(245,158,11,0.2), 0 0 0 1px rgba(245,158,11,0.3);
}

.lang-trigger-name {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
}
:global(.dark) .lang-trigger-name { color: #cbd5e1; }

/* Dropdown menu */
.lang-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  width: 180px;
  background: white;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
  overflow: hidden;
  z-index: 100;
  padding: 4px;
}
:global(.dark) .lang-menu {
  background: #0f172a;
  border-color: rgba(51, 65, 85, 0.8);
  box-shadow: 0 8px 24px rgba(0,0,0,0.5);
}

/* Option rows */
.lang-option {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  cursor: pointer;
  color: #374151;
  transition: background 0.1s ease;
  background: transparent;
  border: none;
}
.lang-option:hover { background: rgba(245, 158, 11, 0.07); }
:global(.dark) .lang-option { color: #cbd5e1; }
:global(.dark) .lang-option:hover { background: rgba(245, 158, 11, 0.1); }

.lang-option--active { background: rgba(245, 158, 11, 0.1); }
.lang-option--active span:last-of-type { font-weight: 700; color: #b45309; }
:global(.dark) .lang-option--active { background: rgba(245, 158, 11, 0.12); }
:global(.dark) .lang-option--active span:last-of-type { color: #fbbf24; }
</style>
