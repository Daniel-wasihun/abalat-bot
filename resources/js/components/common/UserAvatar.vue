<script setup lang="ts">
/**
 * UserAvatar — round avatar with image, initials fallback, optional status dot,
 * and optional tooltip with the user's name.
 *
 * @example
 * <UserAvatar :src="user.avatar" :name="user.name" size="md" status="online" />
 * <UserAvatar :name="user.name" size="lg" /> <!-- initials only -->
 */
import { computed } from "vue";

interface Props {
 /** Image URL – if absent, initials are shown instead */
 src?: string | null;
 /** Full name – used to generate initials and as alt/tooltip text */
 name?: string;
 size?: "xs" | "sm" | "md" | "lg" | "xl";
 /** Online presence indicator */
 status?: "online" | "away" | "offline" | "busy" | "none";
 /** Show name as a tooltip on hover */
 showTooltip?: boolean;
 /** Override the auto-generated initials */
 initials?: string;
}

const props = withDefaults(defineProps<Props>(), {
 size: "md",
 status: "none",
 showTooltip: false,
});

// ─── Size maps ────────────────────────────────────────────────────────────────
const SIZE = {
 xs: { ring: "w-6 h-6", text: "text-[9px]", dot: "w-1.5 h-1.5" },
 sm: { ring: "w-8 h-8", text: "text-[10px]", dot: "w-2 h-2" },
 md: { ring: "w-10 h-10", text: "text-[13px]", dot: "w-2.5 h-2.5" },
 lg: { ring: "w-12 h-12", text: "text-[15px]", dot: "w-3 h-3" },
 xl: { ring: "w-16 h-16", text: "text-[18px]", dot: "w-3.5 h-3.5" },
};

// ─── Status dot colour ────────────────────────────────────────────────────────
const STATUS_COLOR: Record<string, string> = {
 online: "bg-brand-green",
 away: "bg-brand-yellow",
 offline: "bg-main-text/20",
 busy: "bg-rose-500",
};

// ─── Initials generation ──────────────────────────────────────────────────────
const autoInitials = computed(() => {
 if (props.initials) return props.initials.slice(0, 2).toUpperCase();
 if (!props.name) return "?";
 const parts = props.name.trim().split(/\s+/);
 return parts.length >= 2
 ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
 : parts[0].slice(0, 2).toUpperCase();
});

/** Generate a stable HSL hue from the name so avatar colour is consistent per user */
const hue = computed(() => {
 let hash = 0;
 const str = props.name ?? "X";
 for (let i = 0; i < str.length; i++)
 hash = str.charCodeAt(i) + (hash << 5) - hash;
 return ((hash % 360) + 360) % 360;
});

const initialsStyle = computed(() => ({
 background: `hsl(${hue.value}, 55%, 88%)`,
 color: `hsl(${hue.value}, 55%, 30%)`,
}));

const sz = computed(() => SIZE[props.size]);
</script>

<template>
 <div class="relative inline-block shrink-0 group" :class="sz.ring">
 <!-- Image -->
 <img
 v-if="src"
 :src="src"
 :alt="name ?? 'User avatar'"
 class="w-full h-full rounded-full object-cover ring-2 ring-card-bg" />

 <!-- Initials fallback -->
 <div
 v-else
 class="w-full h-full rounded-full ring-2 ring-card-bg flex items-center justify-center font-semibold select-none"
 :class="sz.text"
 :style="initialsStyle">
 {{ autoInitials }}
 </div>

 <!-- Status dot -->
 <span
 v-if="status && status !== 'none'"
 class="absolute bottom-0 right-0 rounded-full ring-2 ring-card-bg"
 :class="[sz.dot, STATUS_COLOR[status] ?? 'bg-main-text/30']" />

 <!-- Tooltip -->
 <div
 v-if="showTooltip && name"
 class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 bg-slate-900/95 backdrop-blur-md text-white text-[10px] capitalize font-bold tracking-wider rounded-lg border border-brand-blue/50 whitespace-nowrap pointer-events-none opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-200 z-[10000] shadow-[0_10px_30px_rgba(0,0,0,0.5)]">
 {{ name }}
 <span
 class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-brand-blue/50" />
 </div>
 </div>
</template>
