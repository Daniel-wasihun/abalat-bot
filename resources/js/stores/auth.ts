import { defineStore } from 'pinia';
import axios from 'axios';

// ─────────────────────────────────────────────
//  Types
// ─────────────────────────────────────────────
interface AdminProfile {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar?: string;
}

interface AuthState {
    token: string | null;
    admin: AdminProfile | null;
    loading: boolean;
    error: string | null;
}

// ─────────────────────────────────────────────
//  Axios Bootstrap
// ─────────────────────────────────────────────
axios.defaults.baseURL = '/api';

const initialToken = localStorage.getItem('admin_token');
if (initialToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
}

// Request interceptor: attach token to all requests
axios.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('admin_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor: handle 401 Unauthorized globally
axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response && error.response.status === 401) {
            const authStore = useAuthStore();
            authStore.logout();
            if (window.location.pathname !== '/login') {
                try {
                    const { default: router } = await import('../router');
                    if (router.currentRoute.value.name !== 'login') {
                        router.push({ name: 'login' });
                    }
                } catch (_) {
                    window.location.href = '/login';
                }
            }
        }
        return Promise.reject(error);
    }
);

// ─────────────────────────────────────────────
//  Store
// ─────────────────────────────────────────────
export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        token: localStorage.getItem('admin_token') || null,
        admin: JSON.parse(localStorage.getItem('admin_profile') ?? 'null'),
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state): boolean => !!state.token,
        role: (state): string => state.admin?.role || 'Viewer',
        isSuperAdmin: (state): boolean => state.admin?.role === 'Super Admin',
        isAdmin: (state): boolean =>
            ['Super Admin', 'Admin'].includes(state.admin?.role ?? ''),
    },

    actions: {
        setAuthHeader(): void {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            } else {
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async login(email: string, password: string): Promise<boolean> {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.post('/auth/login', { email, password });
                const { token, admin } = response.data;

                this.token = token;
                this.admin = admin;

                localStorage.setItem('admin_token', token);
                localStorage.setItem('admin_profile', JSON.stringify(admin));

                this.setAuthHeader();
                return true;
            } catch (err: any) {
                this.error =
                    err.response?.data?.message ||
                    'Login failed. Please check credentials.';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async fetchProfile(): Promise<void> {
            if (!this.token) return;
            this.setAuthHeader();
            try {
                const response = await axios.get('/auth/profile');
                this.admin = response.data.admin;
                localStorage.setItem('admin_profile', JSON.stringify(this.admin));
            } catch (err: any) {
                if (err.response?.status === 401) {
                    this.logout();
                }
            }
        },

        async updateProfile(
            name: string,
            email: string,
            avatar?: string
        ): Promise<any> {
            const response = await axios.put('/auth/profile', {
                name,
                email,
                avatar,
            });
            this.admin = response.data.admin;
            localStorage.setItem('admin_profile', JSON.stringify(this.admin));
            return response.data;
        },

        async changePassword(
            currentPassword: string,
            newPassword: string,
            newPasswordConfirmation: string
        ): Promise<any> {
            const response = await axios.put('/auth/change-password', {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: newPasswordConfirmation,
            });
            return response.data;
        },

        async forgotPassword(email: string): Promise<any> {
            const response = await axios.post('/auth/forgot-password', { email });
            return response.data;
        },

        async resetPassword(
            email: string,
            token: string,
            password: string,
            passwordConfirmation: string
        ): Promise<any> {
            const response = await axios.post('/auth/reset-password', {
                email,
                token,
                password,
                password_confirmation: passwordConfirmation,
            });
            return response.data;
        },

        logout(): void {
            this.token = null;
            this.admin = null;
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_profile');
            this.setAuthHeader();
        },
    },
});
