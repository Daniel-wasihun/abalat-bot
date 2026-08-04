import { z } from "zod";

/**
 * Common Zod validation primitives and schemas used across the application.
 * All messages are keys intended for localization via the useLanguageStore.
 */

export const V = {
    // Basic Strings
    required: z.string().min(1, "validation.required"),
    optional: z.string().optional().nullable(),
    email: z.string().email("validation.email"),
    
    // Numbers
    number: z.coerce.number(),
    requiredNumber: z.coerce.number().min(1, "validation.min_1"),
    positiveNumber: z.coerce.number().min(0, "validation.min_0"),
    
    // Specialized
    boolean: z.boolean(),
    date: z.string().optional().nullable().or(z.literal("")).refine((val) => !val || /^\d{4}-\d{2}-\d{2}$/.test(val), { message: "validation.date_format" }),
    phone: z.string().nullable().optional().or(z.literal("")).refine((val) => !val || /^[79]\d{8}$/.test(val), { message: "validation.phone_format" }),
    universityId: (l_min: number, l_max: number, d_min: number, d_max: number) => 
        z.string().min(1, "validation.required").regex(new RegExp(`^[A-Za-z]{${l_min},${l_max}}\\d{${d_min},${d_max}}$`)),
    
    // Domain Specific
    isbn: z.string().regex(/^(97[89][-\s]?)?(\d[-\s]?){9}[\dXx]$/, "validation.isbn_format").optional().nullable().or(z.literal("")),
    year: z.coerce.number().int().min(1000, "validation.year_min").max(new Date().getFullYear() + 5, "validation.year_max").optional().nullable(),
    price: z.coerce.number().min(0, "validation.min_0").max(999999.99, "validation.max_price"),
    copies: z.coerce.number().int().min(1, "validation.min_1").max(10000, "validation.max_copies"),
    
    // Complex Objects
    localized: z.object({
        en: z.string().min(1, "validation.required"),
        am: z.string().min(1, "validation.required"),
    }),

    optionalLocalized: z.object({
        en: z.string().optional().nullable(),
        am: z.string().optional().nullable(),
    }),

    // Custom Helpers
    min: (len: number) => z.string().min(len, `validation.min_length|count=${len}`),
    max: (len: number) => z.string().max(len, `validation.max_length|count=${len}`),
    between: (min: number, max: number) => z.string().min(min, `validation.min_length|count=${min}`).max(max, `validation.max_length|count=${max}`),
};
