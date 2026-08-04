import * as LucideIcons from "lucide-vue-next";
import type { Component } from "vue";

/**
 * Utility to map string icon names from the backend to Lucide icon components.
 * This is the professional way to handle dynamic icons from an API.
 */
export const getLucideIcon = (name: string): Component => {
    // Standardize naming (e.g. "Users" or "users" or "user-group")
    // Note: Lucide components are PascalCase
    const formattedName = name
        .split("-")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join("");

    return (LucideIcons as any)[formattedName] || LucideIcons.HelpCircle;
};
