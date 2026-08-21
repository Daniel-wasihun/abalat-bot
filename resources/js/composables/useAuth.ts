import { ref, reactive } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useToastStore } from "@/stores/toast";
import { useRouter } from "vue-router";
import { useLanguageStore } from "@/stores/languageStore";
import { useValidation } from "./useValidation";
import { findFirstAccessibleRoute } from "@/utils/routeUtils";
import { z } from "zod";

export function useAuth() {
    const authStore = useAuthStore();
    const toast = useToastStore();
    const router = useRouter();
    const lang = useLanguageStore();
    const {
        errors,
        validate,
        handleBackendErrors,
        clearErrors,
        clearFieldError,
    } = useValidation();

    const loginSchema = z.object({
        email: z
            .string()
            .min(1, "validation.required")
            .email("validation.email"),
        password: z
            .string()
            .min(1, "validation.required")
            .min(8, "validation.min_length"),
    });

    const loginForm = reactive({
        email: "",
        password: "",
        twoFactorCode: "",
        _hp_email_verification: "", // Honeypot field
        _hp_timestamp: btoa(Math.floor(Date.now() / 1000).toString()), // Time-based honeypot
    });

    const isLoggingIn = ref(false);
    const requires2fa = ref(false);

    const handleLogin = async () => {
        const validated = validate(loginSchema, loginForm);
        if (!validated) return;

        if (requires2fa.value && !loginForm.twoFactorCode) {
            toast.error("Please enter the 2FA code.");
            return;
        }

        isLoggingIn.value = true;
        try {
            let response;
            if (requires2fa.value) {
                response = await authStore.verify2faLogin({
                    email: loginForm.email,
                    password: loginForm.password,
                    code: loginForm.twoFactorCode
                });
            } else {
                response = await authStore.login(loginForm);
            }
            
            if (response.requires_2fa) {
                requires2fa.value = true;
                toast.info(response.message || "Please provide 2FA code.");
                return;
            }

            toast.success(
                response.message || lang.translate("auth.login_success"),
            );

            // Redirect to the first authorized route instead of hardcoded dashboard
            const targetRoute = findFirstAccessibleRoute(
                authStore.hasPermission,
            );
            router.replace({ name: targetRoute });
        } catch (err: any) {
            if (!handleBackendErrors(err)) {
                const msg =
                    err.response?.data?.message ||
                    err.message ||
                    lang.translate("auth.login_failed");
                toast.error(msg);
            }
        } finally {
            isLoggingIn.value = false;
        }
    };

    // Forgot Password Logic
    const showForgotModal = ref(false);
    const forgotStep = ref(1);
    const forgotEmail = ref("");
    const forgotOtp = ref("");
    const newPassword = ref("");
    const confirmPassword = ref("");

    const isProcessingOtp = ref(false);
    const resendTimer = ref(0);
    const hpEmail = ref("");
    const hpTimestamp = ref(btoa(Math.floor(Date.now() / 1000).toString()));

    const forgotEmailSchema = z.object({
        email: z
            .string()
            .min(1, "validation.required")
            .email("validation.email"),
    });

    const verifyOtpSchema = z.object({
        otp: z
            .string()
            .min(1, "validation.required")
            .min(6, "validation.min_length"),
    });

    const resetPasswordSchema = z
        .object({
            password: z
                .string()
                .min(1, "validation.required")
                .min(8, "validation.min_length")
                .regex(
                    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&!#%^&*()\-+_={}\[\]|\\:;"'<>,.\/]).+$/,
                    "validation.password_complexity",
                ),
            password_confirmation: z.string().min(1, "validation.required"),
        })
        .refine((data) => data.password === data.password_confirmation, {
            message: "validation.password_mismatch",
            path: ["password_confirmation"],
        });

    const openForgotModal = () => {
        clearErrors();
        forgotStep.value = 1;
        forgotEmail.value = "";
        forgotOtp.value = "";
        newPassword.value = "";
        confirmPassword.value = "";
        showForgotModal.value = true;
    };

    const closeForgotModal = () => {
        showForgotModal.value = false;
        clearErrors();
    };

    const handleSendOtp = async () => {
        if (!validate(forgotEmailSchema, { email: forgotEmail.value })) return;

        isProcessingOtp.value = true;
        try {
            const response = await authStore.sendOtp({
                email: forgotEmail.value,
                _hp_email_verification: hpEmail.value,
                _hp_timestamp: hpTimestamp.value,
            });
            toast.success(
                response.data?.message || lang.translate("auth.otp_sent"),
            );
            forgotStep.value = 2;
        } catch (err: any) {
            handleBackendErrors(err);
            toast.error(
                err.response?.data?.message ||
                    lang.translate("auth.otp_sent_error"),
            );
        } finally {
            isProcessingOtp.value = false;
        }
    };

    const handleVerifyOtp = async () => {
        if (!validate(verifyOtpSchema, { otp: forgotOtp.value })) return;

        isProcessingOtp.value = true;
        try {
            const response = await authStore.verifyOtp({
                email: forgotEmail.value,
                otp: forgotOtp.value,
                _hp_email_verification: hpEmail.value,
                _hp_timestamp: hpTimestamp.value,
            });
            toast.success(
                response.data?.message ||
                    lang.translate("auth.otp_verify_success"),
            );
            forgotStep.value = 3;
        } catch (err: any) {
            handleBackendErrors(err);
            toast.error(
                err.response?.data?.message ||
                    lang.translate("auth.otp_verify_failed"),
            );
        } finally {
            isProcessingOtp.value = false;
        }
    };

    const handleResetPassword = async () => {
        const validated = validate(resetPasswordSchema, {
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        });
        if (!validated) return;

        isProcessingOtp.value = true;
        try {
            const response = await authStore.resetPassword({
                email: forgotEmail.value,
                otp: forgotOtp.value,
                password: newPassword.value,
                password_confirmation: confirmPassword.value,
                _hp_email_verification: hpEmail.value,
                _hp_timestamp: hpTimestamp.value,
            });
            toast.success(
                response.data?.message ||
                    lang.translate("auth.password_reset_success"),
            );
            showForgotModal.value = false;
            clearErrors();
        } catch (err: any) {
            handleBackendErrors(err);
            toast.error(
                err.response?.data?.message ||
                    lang.translate("auth.password_reset_failed"),
            );
        } finally {
            isProcessingOtp.value = false;
        }
    };

    return {
        loginForm,
        isLoggingIn,
        requires2fa,
        handleLogin,
        errors,
        clearFieldError,
        forgotData: {
            showForgotModal,
            forgotStep,
            forgotEmail,
            forgotOtp,
            newPassword,
            confirmPassword,
            isProcessingOtp,
            resendTimer,
            hpEmail,
            hpTimestamp,
        },
        openForgotModal,
        closeForgotModal,
        handleSendOtp,
        handleVerifyOtp,
        handleResetPassword,
    };
}
