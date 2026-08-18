import { z } from "zod";

const LETTERS_ONLY = /^[A-Za-z\s]+$/;
const PHONE_REGEX  = /^[79]\d{8}$/;
const ID_REGEX     = /^DBSS-\d{6,}$/;
const SENBET_CLASSES = ["child", "post_12", ...Array.from({ length: 12 }, (_, i) => String(i + 1))];

const optionalLetters = z
    .string().nullable().optional().or(z.literal(""))
    .refine((v) => !v || LETTERS_ONLY.test(v), { message: "validation.letters_only" });

export const createProfileSchema = () =>
    z.object({
        name: z.string()
            .min(2, "validation.min_length")
            .regex(/^[A-Za-z]+$/, "validation.letters_only"),

        email: z.string().email("validation.email").nullable().optional().or(z.literal("")),

        registration_id: z.string().nullable().optional().or(z.literal(""))
            .refine((v) => !v || ID_REGEX.test(v), { message: "validation.id_format" }),

        father_name:      z.string().min(1, "validation.required").regex(LETTERS_ONLY, "validation.letters_only"),
        grandfather_name: z.string().min(1, "validation.required").regex(LETTERS_ONLY, "validation.letters_only"),
        christian_name:        optionalLetters,
        spiritual_father_name: optionalLetters,

        phone_number: z.string().min(1, "validation.required").regex(PHONE_REGEX, "validation.phone_format"),
        address:      z.string().min(1, "validation.required"),

        gender: z.string().min(1, "validation.required")
            .refine((v) => ["male", "female"].includes(v), { message: "validation.gender_format" }),

        senbet_class: z.string().nullable().optional().or(z.literal(""))
            .refine((v) => !v || SENBET_CLASSES.includes(v), { message: "Invalid Senbet class" }),

        roles: z.array(z.string()).min(1, "validation.required"),
    });

export const roleSchema = z.object({
    roles:     z.array(z.string()).min(1, "validation.required"),
    startDate: z.string().optional(),
    endDate:   z.string().optional(),
});
