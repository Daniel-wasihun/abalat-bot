import { ref, onMounted, onUnmounted } from "vue";

export function useParticles() {
    const mouseX = ref(0);
    const mouseY = ref(0);
    const isOverInput = ref(false);
    const transitionFactor = ref(0);
    const particles = ref([
        { id: 1, x: 0, y: 15, homeX: 8, homeY: 15, delay: 0, wobble: 0 },
        { id: 2, x: 0, y: 28, homeX: 12, homeY: 28, delay: 0.15, wobble: 1 },
        { id: 3, x: 0, y: 42, homeX: 10, homeY: 42, delay: 0.3, wobble: 2 },
        { id: 4, x: 0, y: 56, homeX: 14, homeY: 56, delay: 0.45, wobble: 3 },
        { id: 5, x: 0, y: 70, homeX: 11, homeY: 70, delay: 0.6, wobble: 4 },
        { id: 6, x: 0, y: 84, homeX: 9, homeY: 84, delay: 0.75, wobble: 5 },
    ]);

    let animationFrame: number;
    let time = 0;
    let lastMouseUpdate = 0;
    const isMobile = ref(false);

    const handleMouseMove = (e: MouseEvent) => {
        const now = Date.now();
        if (now - lastMouseUpdate < 33) return;
        lastMouseUpdate = now;

        mouseX.value = (e.clientX / window.innerWidth) * 100;
        mouseY.value = (e.clientY / window.innerHeight) * 100;

        const target = e.target as HTMLElement;
        isOverInput.value = target?.tagName === "INPUT";
    };

    const updateParticles = () => {
        time += 0.016;
        const targetFactor = isOverInput.value ? 1 : 0;
        transitionFactor.value +=
            (targetFactor - transitionFactor.value) * 0.03;

        particles.value.forEach((particle) => {
            const cursorX = mouseX.value;
            const cursorY = mouseY.value;
            const homeX = particle.homeX;
            const homeY = particle.homeY;

            const t = transitionFactor.value;
            const targetX = cursorX * (1 - t) + homeX * t;
            const targetY = cursorY * (1 - t) + homeY * t;

            const waveStrength = 1.5 * (1 - t * 0.7);
            const waveOffset = Math.sin(time + particle.wobble) * waveStrength;

            const baseSpeed = 0.04 + particle.delay * 0.015;
            const speed = baseSpeed * (1 - t * 0.3);

            const dx = targetX - particle.x;
            const dy = targetY - particle.y;

            particle.x += dx * speed + waveOffset * 0.1;
            particle.y += dy * speed;
        });

        animationFrame = requestAnimationFrame(updateParticles);
    };

    onMounted(() => {
        isMobile.value = window.innerWidth < 1024;
        window.addEventListener("mousemove", handleMouseMove);
        if (!isMobile.value) {
            updateParticles();
        }
    });

    onUnmounted(() => {
        window.removeEventListener("mousemove", handleMouseMove);
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
        }
    });

    return {
        particles,
        isOverInput,
    };
}
