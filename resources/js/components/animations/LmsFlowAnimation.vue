<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import {
 BookOpen,
 Library,
 GraduationCap,
 Search,
 Users,
 ShieldCheck,
} from "lucide-vue-next";

const mouseX = ref(0);
const mouseY = ref(0);
const containerRef = ref<HTMLElement | null>(null);

// Static Particles Logic
const staticParticles = ref<
 {
 id: number;
 x: number;
 y: number;
 size: number;
 color: string;
 opacity: number;
 }[]
>([]);

const generateElements = () => {
 // Generate static particles
 for (let i = 0; i < 50; i++) {
 staticParticles.value.push({
 id: i,
 x: Math.random() * 100,
 y: Math.random() * 100,
 size: 1 + Math.random() * 3,
 color: i % 2 === 0 ? "blue" : "yellow",
 opacity: 0.3 + Math.random() * 0.4,
 });
 }
};

const handleMouseMove = (e: MouseEvent) => {
 if (!containerRef.value) return;
 const rect = containerRef.value.getBoundingClientRect();
 mouseX.value = ((e.clientX - rect.left) / rect.width) * 100;
 mouseY.value = ((e.clientY - rect.top) / rect.height) * 100;
};

onMounted(() => {
 generateElements();
 if (containerRef.value) {
 containerRef.value.addEventListener("mousemove", handleMouseMove);
 }
});

onUnmounted(() => {
 if (containerRef.value) {
 containerRef.value.removeEventListener("mousemove", handleMouseMove);
 }
});
</script>

<template>
 <div
 ref="containerRef"
 aria-hidden="true"
 role="presentation"
 class="relative w-full h-full flex items-center justify-center overflow-hidden bg-main-bg cursor-interaction animate-fade-in"
 :style="{
 '--mouse-x': `${mouseX}%`,
 '--mouse-y': `${mouseY}%`,
 }">
 <!-- Cursor Spotlight -->
 <div
 class="cursor-spotlight"
 :style="{
 left: `${mouseX}%`,
 top: `${mouseY}%`,
 }"></div>

 <!-- Static Particles Layer -->
 <div class="absolute inset-0 overflow-hidden pointer-events-none z-4">
 <div
 v-for="particle in staticParticles"
 :key="`particle-${particle.id}`"
 class="absolute rounded-full animate-pulse-particle"
 :class="
 particle.color === 'blue' ? 'bg-[#0b529c]' : 'bg-[#fba81c]'
 "
 :style="{
 left: `${particle.x}%`,
 top: `${particle.y}%`,
 width: `${particle.size}px`,
 height: `${particle.size}px`,
 opacity: particle.opacity,
 animationDelay: `${particle.id * 0.1}s`,
 }"></div>
 </div>

 <!-- Circuit Board Background with Animated Paths -->
 <svg
 class="absolute inset-0 w-full h-full parallax-svg z-3"
 :style="{
 transform: `translate(${(mouseX - 50) * 0.02}px, ${
 (mouseY - 50) * 0.02
 }px)`,
 }"
 viewBox="0 0 800 600"
 xmlns="http://www.w3.org/2000/svg"
 preserveAspectRatio="xMidYMid slice">
 <defs>
 <!-- Glow filter for circuit lines -->
 <filter id="glow">
 <feGaussianBlur stdDeviation="2" result="coloredBlur" />
 <feMerge>
 <feMergeNode in="coloredBlur" />
 <feMergeNode in="SourceGraphic" />
 </feMerge>
 </filter>

 <!-- Gradients for animated lights -->
 <linearGradient
 id="lightGradient1"
 x1="0%"
 y1="0%"
 x2="100%"
 y2="0%">
 <stop offset="0%" stop-color="rgba(11, 82, 156, 0)" />
 <stop offset="50%" stop-color="rgba(11, 82, 156, 1)" />
 <stop offset="100%" stop-color="rgba(11, 82, 156, 0)" />
 </linearGradient>

 <linearGradient
 id="lightGradient2"
 x1="0%"
 y1="0%"
 x2="100%"
 y2="0%">
 <stop offset="0%" stop-color="rgba(251, 168, 28, 0)" />
 <stop offset="50%" stop-color="rgba(251, 168, 28, 1)" />
 <stop offset="100%" stop-color="rgba(251, 168, 28, 0)" />
 </linearGradient>

 <radialGradient id="nodeGlow">
 <stop
 offset="0%"
 stop-color="var(--accent-text)"
 stop-opacity="1" />
 <stop
 offset="50%"
 stop-color="var(--accent-text)"
 stop-opacity="0.5" />
 <stop
 offset="100%"
 stop-color="var(--accent-text)"
 stop-opacity="0" />
 </radialGradient>
 </defs>

 <!-- Horizontal Circuit Lines -->
 <g class="circuit-group">
 <!-- Top horizontal line -->
 <line
 x1="50"
 y1="100"
 x2="750"
 y2="100"
 stroke="var(--card-border)"
 stroke-width="2"
 filter="url(#glow)" />
 <line
 x1="50"
 y1="102"
 x2="750"
 y2="102"
 stroke="var(--card-border)"
 opacity="0.3"
 stroke-width="1" />

 <line
 x1="100"
 y1="200"
 x2="700"
 y2="200"
 stroke="rgba(251, 168, 28, 0.2)"
 stroke-width="2"
 filter="url(#glow)" />
 <line
 x1="100"
 y1="202"
 x2="700"
 y2="202"
 stroke="rgba(251, 168, 28, 0.1)"
 stroke-width="1" />

 <!-- Center horizontal line -->
 <line
 x1="50"
 y1="300"
 x2="750"
 y2="300"
 stroke="rgba(11, 82, 156, 0.3)"
 stroke-width="2"
 filter="url(#glow)" />
 <line
 x1="50"
 y1="302"
 x2="750"
 y2="302"
 stroke="rgba(11, 82, 156, 0.1)"
 stroke-width="1" />

 <!-- Lower mid horizontal line -->
 <line
 x1="100"
 y1="400"
 x2="700"
 y2="400"
 stroke="rgba(251, 168, 28, 0.3)"
 stroke-width="2"
 filter="url(#glow)" />
 <line
 x1="100"
 y1="402"
 x2="700"
 y2="402"
 stroke="rgba(251, 168, 28, 0.1)"
 stroke-width="1" />

 <!-- Bottom horizontal line -->
 <line
 x1="50"
 y1="500"
 x2="750"
 y2="500"
 stroke="var(--card-border)"
 stroke-width="2"
 filter="url(#glow)" />
 <line
 x1="50"
 y1="502"
 x2="750"
 y2="502"
 stroke="var(--card-border)"
 opacity="0.3"
 stroke-width="1" />
 </g>

 <!-- Vertical Circuit Lines -->
 <g class="circuit-group">
 <!-- Left vertical -->
 <line
 x1="150"
 y1="50"
 x2="150"
 y2="550"
 stroke="var(--card-border)"
 stroke-width="2"
 filter="url(#glow)" />

 <!-- Left-center vertical -->
 <line
 x1="300"
 y1="80"
 x2="300"
 y2="520"
 stroke="rgba(251, 168, 28, 0.3)"
 stroke-width="2"
 filter="url(#glow)" />

 <!-- Center vertical -->
 <line
 x1="450"
 y1="50"
 x2="450"
 y2="550"
 stroke="var(--card-border)"
 stroke-width="2"
 filter="url(#glow)" />

 <!-- Right-center vertical -->
 <line
 x1="600"
 y1="80"
 x2="600"
 y2="520"
 stroke="rgba(251, 168, 28, 0.3)"
 stroke-width="2"
 filter="url(#glow)" />

 <!-- Right vertical -->
 <line
 x1="680"
 y1="50"
 x2="680"
 y2="550"
 stroke="var(--card-border)"
 stroke-width="2"
 filter="url(#glow)" />
 </g>

 <!-- Connection Nodes -->
 <g class="nodes">
 <!-- Horizontal line nodes -->
 <circle
 cx="150"
 cy="100"
 r="5"
 fill="#0b529c"
 filter="url(#glow)" />
 <circle
 cx="300"
 cy="100"
 r="5"
 fill="#fba81c"
 filter="url(#glow)" />
 <circle
 cx="450"
 cy="200"
 r="5"
 fill="#0b529c"
 filter="url(#glow)" />
 <circle
 cx="600"
 cy="200"
 r="5"
 fill="#fba81c"
 filter="url(#glow)" />
 <circle
 cx="150"
 cy="300"
 r="5"
 fill="#0b529c"
 filter="url(#glow)" />
 <circle
 cx="450"
 cy="300"
 r="5"
 fill="#fba81c"
 filter="url(#glow)" />
 <circle
 cx="680"
 cy="300"
 r="5"
 fill="#0b529c"
 filter="url(#glow)" />
 <circle
 cx="300"
 cy="400"
 r="5"
 fill="#fba81c"
 filter="url(#glow)" />
 <circle
 cx="600"
 cy="400"
 r="5"
 fill="#0b529c"
 filter="url(#glow)" />
 <circle
 cx="450"
 cy="500"
 r="5"
 fill="#fba81c"
 filter="url(#glow)" />
 </g>

 <!-- Animated Light Pulses traveling along circuits -->
 <g class="light-pulses">
 <!-- Horizontal pulses -->
 <circle
 r="6"
 fill="url(#lightGradient1)"
 class="traveling-light">
 <animateMotion
 dur="12s"
 repeatCount="indefinite"
 path="M 50,100 L 750,100" />
 </circle>
 <circle
 r="5"
 fill="url(#lightGradient2)"
 class="traveling-light">
 <animateMotion
 dur="13s"
 repeatCount="indefinite"
 begin="1s"
 path="M 100,200 L 700,200" />
 </circle>
 <circle
 r="6"
 fill="url(#lightGradient1)"
 class="traveling-light">
 <animateMotion
 dur="14s"
 repeatCount="indefinite"
 begin="2s"
 path="M 750,300 L 50,300" />
 </circle>
 <circle
 r="5"
 fill="url(#lightGradient2)"
 class="traveling-light">
 <animateMotion
 dur="12.5s"
 repeatCount="indefinite"
 begin="0.5s"
 path="M 700,400 L 100,400" />
 </circle>
 <circle
 r="6"
 fill="url(#lightGradient1)"
 class="traveling-light">
 <animateMotion
 dur="15s"
 repeatCount="indefinite"
 begin="1.5s"
 path="M 50,500 L 750,500" />
 </circle>

 <!-- Vertical pulses -->
 <circle
 r="5"
 fill="url(#lightGradient2)"
 class="traveling-light">
 <animateMotion
 dur="11s"
 repeatCount="indefinite"
 path="M 150,50 L 150,550" />
 </circle>
 <circle
 r="6"
 fill="url(#lightGradient1)"
 class="traveling-light">
 <animateMotion
 dur="13s"
 repeatCount="indefinite"
 begin="1s"
 path="M 300,520 L 300,80" />
 </circle>
 <circle
 r="5"
 fill="url(#lightGradient2)"
 class="traveling-light">
 <animateMotion
 dur="12s"
 repeatCount="indefinite"
 begin="2s"
 path="M 450,50 L 450,550" />
 </circle>
 <circle
 r="6"
 fill="url(#lightGradient1)"
 class="traveling-light">
 <animateMotion
 dur="14s"
 repeatCount="indefinite"
 begin="0.8s"
 path="M 600,520 L 600,80" />
 </circle>
 <circle
 r="5"
 fill="url(#lightGradient2)"
 class="traveling-light">
 <animateMotion
 dur="13s"
 repeatCount="indefinite"
 begin="1.8s"
 path="M 680,50 L 680,550" />
 </circle>

 <!-- Moving Particles along lines (small dots) -->
 <!-- Horizontal moving particles -->
 <circle
 r="3"
 fill="#0b529c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="10s"
 repeatCount="indefinite"
 path="M 50,100 L 750,100" />
 </circle>
 <circle
 r="2"
 fill="#fba81c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="11s"
 repeatCount="indefinite"
 begin="2s"
 path="M 750,100 L 50,100" />
 </circle>
 <circle
 r="3"
 fill="#fba81c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="9s"
 repeatCount="indefinite"
 begin="1s"
 path="M 100,200 L 700,200" />
 </circle>
 <circle
 r="2"
 fill="#0b529c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="10s"
 repeatCount="indefinite"
 begin="3s"
 path="M 700,200 L 100,200" />
 </circle>
 <circle
 r="3"
 fill="#0b529c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="12s"
 repeatCount="indefinite"
 path="M 50,300 L 750,300" />
 </circle>
 <circle
 r="2"
 fill="#fba81c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="11s"
 repeatCount="indefinite"
 begin="1.5s"
 path="M 750,300 L 50,300" />
 </circle>
 <circle
 r="3"
 fill="#fba81c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="10s"
 repeatCount="indefinite"
 begin="0.5s"
 path="M 100,400 L 700,400" />
 </circle>
 <circle
 r="2"
 fill="#0b529c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="9s"
 repeatCount="indefinite"
 begin="2.5s"
 path="M 700,400 L 100,400" />
 </circle>
 <circle
 r="3"
 fill="#0b529c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="13s"
 repeatCount="indefinite"
 begin="1s"
 path="M 50,500 L 750,500" />
 </circle>

 <!-- Vertical moving particles -->
 <circle
 r="3"
 fill="#0b529c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="10s"
 repeatCount="indefinite"
 path="M 150,50 L 150,550" />
 </circle>
 <circle
 r="2"
 fill="#fba81c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="11s"
 repeatCount="indefinite"
 begin="2s"
 path="M 150,550 L 150,50" />
 </circle>
 <circle
 r="3"
 fill="#fba81c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="9s"
 repeatCount="indefinite"
 begin="1s"
 path="M 300,80 L 300,520" />
 </circle>
 <circle
 r="2"
 fill="#0b529c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="10s"
 repeatCount="indefinite"
 begin="3s"
 path="M 300,520 L 300,80" />
 </circle>
 <circle
 r="3"
 fill="#0b529c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="11s"
 repeatCount="indefinite"
 path="M 450,50 L 450,550" />
 </circle>
 <circle
 r="2"
 fill="#fba81c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="12s"
 repeatCount="indefinite"
 begin="1.5s"
 path="M 450,550 L 450,50" />
 </circle>
 <circle
 r="3"
 fill="#fba81c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="10s"
 repeatCount="indefinite"
 begin="0.5s"
 path="M 600,80 L 600,520" />
 </circle>
 <circle
 r="2"
 fill="#0b529c"
 opacity="0.5"
 class="moving-particle">
 <animateMotion
 dur="9s"
 repeatCount="indefinite"
 begin="2.5s"
 path="M 600,520 L 600,80" />
 </circle>
 <circle
 r="3"
 fill="#0b529c"
 opacity="0.6"
 class="moving-particle">
 <animateMotion
 dur="13s"
 repeatCount="indefinite"
 begin="1s"
 path="M 680,50 L 680,550" />
 </circle>
 </g>
 </svg>

 <!-- Central Content - Company Name & Tech Icons -->
 <div
 class="relative z-10 flex flex-col items-center justify-center gap-16">
 <!-- Company Name -->
 <div class="company-branding">
 <h1 class="company-name">
 <span class="text-gradient">{{ $tr('app.name') }}</span>
 </h1>
 <p class="company-tagline">
 {{ $tr("auth.login_animation_tagline") }}
 </p>
 <div class="brand-underline"></div>
 </div>

 <!-- Technology Grid -->
 <div class="tech-grid">
 <!-- Technology Icon 1 - Resources -->
 <div class="tech-icon" style="--i: 1">
 <div class="icon-wrapper">
 <BookOpen class="w-8 h-8" />
 </div>
 <p class="tech-label">
 {{ $tr("auth.login_animation_resources") }}
 </p>
 </div>

 <!-- Technology Icon 2 - Archive -->
 <div class="tech-icon" style="--i: 2">
 <div class="icon-wrapper">
 <Library class="w-8 h-8" />
 </div>
 <p class="tech-label">
 {{ $tr("auth.login_animation_archive") }}
 </p>
 </div>

 <!-- Technology Icon 3 - Learning -->
 <div class="tech-icon" style="--i: 3">
 <div class="icon-wrapper">
 <GraduationCap class="w-8 h-8" />
 </div>
 <p class="tech-label">
 {{ $tr("auth.login_animation_learning") }}
 </p>
 </div>

 <!-- Technology Icon 4 - Discovery -->
 <div class="tech-icon" style="--i: 4">
 <div class="icon-wrapper">
 <Search class="w-8 h-8" />
 </div>
 <p class="tech-label">
 {{ $tr("auth.login_animation_discovery") }}
 </p>
 </div>

 <!-- Technology Icon 5 - Community -->
 <div class="tech-icon" style="--i: 5">
 <div class="icon-wrapper">
 <Users class="w-8 h-8" />
 </div>
 <p class="tech-label">
 {{ $tr("auth.login_animation_community") }}
 </p>
 </div>

 <!-- Technology Icon 6 - Security -->
 <div class="tech-icon" style="--i: 6">
 <div class="icon-wrapper">
 <ShieldCheck class="w-8 h-8" />
 </div>
 <p class="tech-label">
 {{ $tr("auth.login_animation_secure") }}
 </p>
 </div>
 </div>
 </div>
 </div>
</template>

<style scoped>
/* Cursor Interaction Styles */
.cursor-interaction {
 position: relative;
}

.cursor-spotlight {
 position: absolute;
 width: 800px;
 height: 800px;
 border-radius: 50%;
 background: radial-gradient(
 circle,
 rgba(11, 82, 156, 0.12) 0%,
 rgba(251, 168, 28, 0.08) 25%,
 transparent 60%
 );
 pointer-events: none;
 transform: translate(-50%, -50%);
 transition: opacity 0.5s ease;
 z-index: 5;
 mix-blend-mode: soft-light;
}

.parallax-svg {
 transition: transform 1s cubic-bezier(0.16, 1, 0.3, 1);
}

.circuit-group {
 transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Traveling light blur effect */
.traveling-light {
 filter: blur(4px);
}

/* Moving particles blur effect */
.moving-particle {
 filter: blur(2px);
}

/* Static Particles Pulse Animation */
@keyframes pulseParticle {
 0%,
 100% {
 transform: scale(1);
 opacity: 0.3;
 }
 50% {
 transform: scale(1.5);
 opacity: 0.8;
 }
}

.animate-pulse-particle {
 animation: pulseParticle 3s ease-in-out infinite;
}

/* Company Branding */
.company-branding {
 text-align: center;
 position: relative;
}

.company-name {
 font-size: 4rem;
 font-weight: 800;
 letter-spacing: -0.02em;
 line-height: 1;
 display: flex;
 gap: 1rem;
 margin-bottom: 0.75rem;
}

.text-gradient {
 color: #0b529c;
 font-weight: 900;
 background: linear-gradient(120deg, #0b529c 30%, #4a90e2 50%, #0b529c 70%);
 background-size: 200% auto;
 -webkit-background-clip: text;
 background-clip: text;
 -webkit-text-fill-color: transparent;
 animation: shine 5s linear infinite;
}

@keyframes shine {
 to {
 background-position: 200% center;
 }
}



.company-tagline {
 font-size: 1.125rem;
 font-weight: 600;
 text-transform: capitalize;
 letter-spacing: 0.15em;
 color: rgba(100, 116, 139, 0.6);
 margin-bottom: 2rem;
 animation: pulseFade 4s ease-in-out infinite;
}

@keyframes pulseFade {
 0%,
 100% {
 opacity: 0.5;
 letter-spacing: 0.15em;
 }
 50% {
 opacity: 0.8;
 letter-spacing: 0.18em;
 }
}

.brand-underline {
 width: 140px;
 height: 3px;
 background: linear-gradient(
 90deg,
 transparent,
 #0b529c,
 #fba81c,
 transparent
 );
 margin: 0 auto;
 border-radius: 4px;
 position: relative;
 overflow: hidden;
}

.brand-underline::after {
 content: "";
 position: absolute;
 inset: 0;
 background: linear-gradient(90deg, transparent, white, transparent);
 width: 50%;
 filter: blur(4px);
 animation: scanner 3s infinite;
}

@keyframes scanner {
 0% {
 transform: translateX(-200%);
 }
 100% {
 transform: translateX(400%);
 }
}

/* Technology Grid */
.tech-grid {
 display: grid;
 grid-template-columns: repeat(3, 1fr);
 gap: 2rem;
 max-width: 600px;
}

.tech-icon {
 display: flex;
 flex-direction: column;
 align-items: center;
 gap: 0.75rem;
 cursor: pointer;
 position: relative;
 transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
 opacity: 0;
 transform: translateY(20px);
 animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
 animation-delay: calc(var(--i) * 0.1s + 0.5s);
}

@keyframes slideUpFade {
 to {
 opacity: 1;
 transform: translateY(0);
 }
}

.tech-icon:hover {
 transform: translateY(-8px) scale(1.05);
}

.tech-icon:hover .icon-wrapper {
 border-color: rgba(11, 82, 156, 0.4);
 background: rgba(11, 82, 156, 0.05);
}

.tech-icon:hover .icon-wrapper::after {
 content: "";
 position: absolute;
 inset: 0;
 background: rgba(11, 82, 156, 0.2);
 filter: blur(20px);
 z-index: -1;
 animation: pulseGlow 2s infinite;
}

@keyframes pulseGlow {
 0%,
 100% {
 transform: scale(1);
 opacity: 0.5;
 }
 50% {
 transform: scale(1.2);
 opacity: 0.8;
 }
}

.icon-wrapper {
 width: 90px;
 height: 90px;
 display: flex;
 align-items: center;
 justify-content: center;
 background: var(--card-bg);
 backdrop-filter: blur(10px);
 border-radius: 20px;
 border: 2px solid var(--card-border);
 position: relative;
 overflow: hidden;
 transition: all 0.3s cubic-bezier(0.33, 1, 0.68, 1);
}

.tech-icon svg {
 color: #0b529c;
 z-index: 1;
}

.tech-icon:nth-child(even) svg {
 color: #fba81c;
}

.tech-label {
 font-size: 0.875rem;
 font-weight: 700;
 color: var(--text-main);
 text-transform: capitalize;
 letter-spacing: 0.08em;
 opacity: 0.8;
}

/* Responsive Design */
@media (max-width: 768px) {
 .company-name {
 font-size: 2.5rem;
 gap: 0.5rem;
 }

 .company-tagline {
 font-size: 0.875rem;
 }

 .tech-grid {
 grid-template-columns: repeat(2, 1fr);
 gap: 1.5rem;
 max-width: 400px;
 }

 .icon-wrapper {
 width: 70px;
 height: 70px;
 }

 .tech-icon svg {
 width: 1.5rem;
 height: 1.5rem;
 }

 .tech-label {
 font-size: 0.75rem;
 }
}

@media (max-width: 480px) {
 .company-name {
 font-size: 2rem;
 }

 .tech-grid {
 grid-template-columns: repeat(2, 1fr);
 gap: 1rem;
 }

 .icon-wrapper {
 width: 60px;
 height: 60px;
 }
}

/* Fade-in animation */
@keyframes fadeIn {
 from {
 opacity: 0;
 }
 to {
 opacity: 1;
 }
}

.animate-fade-in {
 animation: fadeIn 0.6s ease-out;
}

/* Accessibility: Reduced motion support */
@media (prefers-reduced-motion: reduce) {
 .cursor-spotlight,
 .parallax-svg,
 .tech-icon,
 .animate-fade-in,
 .traveling-light,
 .moving-particle,
 .animate-pulse-particle {
 animation: none !important;
 transition: none !important;
 }

 .cursor-spotlight,
 .parallax-svg {
 transform: none !important;
 }

 * {
 animation-duration: 0.01ms !important;
 animation-iteration-count: 1 !important;
 transition-duration: 0.01ms !important;
 }
}
</style>
