import { z } from "zod";

export const NAME_MIN = 7;
export const ID_MIN_LETTERS = 2;
export const ID_MAX_LETTERS = 5;
export const ID_MIN_DIGITS = 5;
export const ID_MAX_DIGITS = 8;

export const createProfileSchema = (withParams: any) =>
    z.object({
        name: z
            .string()
            .min(1, "validation.required")
            .min(NAME_MIN, "validation.min_length")
            .regex(/^[A-Za-z\s]+$/, "validation.letters_only")
            .regex(/^\S+\s+\S+.*$/, "validation.name_format"),
        email: z
            .string()
            .min(1, "validation.required")
            .email("validation.email"),
        user_university_id: z
            .string()
            .min(1, "validation.required")
            .regex(
                /^[A-Za-z]{2,5}\d{5,8}$/,
                withParams("validation.id_format", {
                    l_min: ID_MIN_LETTERS,
                    l_max: ID_MAX_LETTERS,
                    d_min: ID_MIN_DIGITS,
                    d_max: ID_MAX_DIGITS,
                }),
            ),
        user_type: z.string().min(1, "validation.required"),
        phone_number: z
            .string()
            .nullable()
            .optional()
            .or(z.literal(""))
            .refine((val) => !val || /^[79]\d{8}$/.test(val), {
                message: "validation.phone_format",
            }),
        gender: z
            .string()
            .min(1, "validation.required")
            .refine((val) => ["male", "female"].includes(val.toLowerCase()), {
                message: "validation.gender_format",
            }),
        role: z.string().optional(),
    });

export const roleSchema = z.object({
    role: z.string().min(1, "validation.required"),
    startDate: z.string().optional(),
    endDate: z.string().optional(),
});
