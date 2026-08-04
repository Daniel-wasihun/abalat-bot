/**
 * System-wide formatting utilities
 */

export function localize(val: any, lang?: string): string {
    if (val === null || val === undefined) return "N/A";
    if (typeof val === "string") return val;
    if (Array.isArray(val)) return val.join(", ");

    // Handle localized objects (e.g., { en: 'Name', am: '...' })
    if (typeof val === "object") {
        const currentLang =
            lang ||
            document.cookie
                .split("; ")
                .find((row) => row.startsWith("lang="))
                ?.split("=")[1] ||
            "en";
        const firstVal = val[currentLang] || val.en || Object.values(val)[0];
        if (Array.isArray(firstVal)) return firstVal.join(", ");
        if (typeof firstVal === "string") return firstVal;
        if (firstVal === null || firstVal === undefined) return "N/A";
        return String(firstVal);
    }

    return String(val);
}

export function formatDate(
    date: string | number | Date | undefined,
    lang: string = "en",
): string {
    if (!date) return "N/A";
    const locale = lang === "am" ? "am-ET" : "en-US";
    return new Date(date).toLocaleDateString(locale, {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

export function formatTime(
    date: string | number | Date | undefined,
    lang: string = "en",
): string {
    if (!date) return "";
    const locale = lang === "am" ? "am-ET" : "en-US";
    return new Date(date).toLocaleTimeString(locale, {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
}
export function capitalize(str: string | undefined | null): string {
    if (!str) return "";
    return str.toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * Standardizes a Cutter number for display.
 * Enforces LCC format: . followed by one Capital Letter and exactly three digits (e.g., .A123).
 * Removes all work marks (trailing lowercase letters) and excess digits.
 */
export function cleanCutter(val: string | null | undefined): string {
    if (!val) return "";
    let clean = String(val).trim();
    if (!clean.startsWith(".")) clean = "." + clean;

    // Extract parts: . + Letter + Digits
    const match = clean.match(/^\.([A-Z])(\d{1,3})/);
    if (!match) return clean.replace(/[a-z]/g, ""); // Fallback: just remove lowercase

    const letter = match[1];
    let digits = match[2];

    // Pad with '5' if less than 3 digits (Standard LCC breathing room) or '0'
    // But user says "three numbers only", implying they should be there.
    // If we have 1 or 2, we pad.
    while (digits.length < 3) {
        digits += "5";
    }

    return `.${letter}${digits.substring(0, 3)}`;
}

/**
 * Standardizes a year value, ensuring it only contains digits.
 */
export function cleanYear(val: string | number | null | undefined): string {
    if (!val) return "";
    return String(val).replace(/[a-zA-Z]+$/, "");
}

/**
 * Strips internal system prefixes from barcodes for cleaner human reading on labels.
 */
export function formatDisplayBarcode(raw: string | null | undefined): string {
    if (!raw) return "";
    return String(raw)
        .replace(/-LMS-/gi, "-")
        .replace(/-\d{4}-/g, "-")
        .replace(/^(L\.?M\.?S\.?-?|አቡጊዳ-?)/i, "");
}
/**
 * Formats a list of row numbers into individual R prefixed values (e.g., R1, R2, R3)
 */
export function formatRows(
    rows: (number | string)[] | null | undefined,
): string {
    if (!rows || rows.length === 0) return "Gen";

    return rows
        .map((r) => `R${String(r).trim().replace(/^R/i, "")}`)
        .join(", ");
}

/**
 * Removes row patterns like (R1, R2) from shelf names to avoid redundancy
 */
export function cleanShelfName(name: string | null | undefined): string {
    if (!name) return "";
    return name.replace(/\s?\(?R\d+.*?\)?/gi, "").trim();
}

/**
 * Removes internal system prefixes (like timestamps) from file paths to get the real filename.
 */
export function cleanFileName(val: string | null | undefined): string {
    if (!val) return "";
    // Decode URI components to support Amharic and other non-ASCII characters
    let rawName = "";
    try {
        rawName = decodeURIComponent(val.split("/").pop() || "");
    } catch (e) {
        rawName = val.split("/").pop() || "";
    }

    // Removes prefixes like "123456789_" or "course_outline_" or "lecture_note_"
    let clean = rawName
        .replace(/^\d+_/, "") // Leading timestamps
        .replace(
            /^(course_outline|lecture_note|assignment|worksheet|video_lecture|reference_book)_/i,
            "",
        ); // Common prefixes

    // Removes trailing database IDs or unique slugs like "-1776849527"
    // We only remove them if they look like long numeric IDs or have specific patterns
    // that the backend usually appends to the original filename.
    clean = clean.replace(/-[0-9]{10,}/g, "");

    return clean || rawName;
}

/**
 * Extracts filename from Content-Disposition header, handling UTF-8 encoding.
 */
export function extractFileNameFromHeader(
    header: string | null | undefined,
): string | null {
    if (!header) return null;

    // Try filename*=UTF-8'' (Modern Standard)
    const utf8Match = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        try {
            return decodeURIComponent(utf8Match[1]);
        } catch (e) {
            console.warn("Failed to decode UTF-8 filename", e);
        }
    }

    // Try standard filename="name"
    const standardMatch = header.match(/filename="?([^";]+)"?/i);
    if (standardMatch?.[1]) {
        try {
            return decodeURIComponent(standardMatch[1]);
        } catch (e) {
            return standardMatch[1];
        }
    }

    return null;
}
