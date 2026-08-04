import { defineStore } from "pinia";
import { ref, watch } from "vue";

export const useThemeStore = defineStore("theme", () => {
    const isDark = ref(localStorage.getItem("theme") === "dark");

    const toggleTheme = () => {
        isDark.value = !isDark.value;
    };

    watch(
        isDark,
        (val) => {
            const applyTheme = () => {
                if (val) {
                    document.documentElement.classList.add("dark");
                } else {
                    document.documentElement.classList.remove("dark");
                }
                localStorage.setItem("theme", val ? "dark" : "light");
            };

            // Freeze ALL element transitions before toggling the class.
            // This guarantees every element (sidebar, table, text) updates
            // instantly — exactly like the body background color does.
            document.documentElement.classList.add("theme-transitioning");

            // @ts-ignore - View Transition API
            if (document.startViewTransition) {
                // @ts-ignore
                document.startViewTransition(applyTheme);
            } else {
                applyTheme();
            }

            // Remove on the very next paint — transitions are un-frozen
            // immediately after, so hover/focus effects work normally.
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    document.documentElement.classList.remove(
                        "theme-transitioning",
                    );
                });
            });
        },
        { immediate: true },
    );

    return {
        isDark,
        toggleTheme,
    };
});
