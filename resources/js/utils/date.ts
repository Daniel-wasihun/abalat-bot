/**
 * Date formatting utilities
 */

export function formatDateTime(date: string | Date | undefined): string {
    if (!date) return "N/A";
    const d = new Date(date);
    return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: true })}`;
}
