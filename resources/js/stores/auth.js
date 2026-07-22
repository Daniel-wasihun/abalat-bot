import { defineStore } from 'pinia';
import axios from 'axios';

// Set base URL for all API requests
axios.defaults.baseURL = '/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('admin_token') || null,
    admin: JSON.parse(localStorage.getItem('admin_profile')) || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.admin?.role || 'Viewer',
    isSuperAdmin: (state) => state.admin?.role === 'Super Admin',
    isAdmin: (state) => ['Super Admin', 'Admin'].includes(state.admin?.role),
  },

  actions: {
    setAuthHeader() {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
      } else {
        delete axios.defaults.headers.common['Authorization'];
      }
    },

    async login(email, password) {
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
      } catch (err) {
        this.error = err.response?.data?.message || 'Login failed. Please check credentials.';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async fetchProfile() {
      if (!this.token) return;
      this.setAuthHeader();
      try {
        const response = await axios.get('/auth/profile');
        this.admin = response.data.admin;
        localStorage.setItem('admin_profile', JSON.stringify(this.admin));
      } catch (err) {
        if (err.response?.status === 401) {
          this.logout();
        }
      }
    },

    async updateProfile(name, email, avatar) {
      try {
        const response = await axios.put('/auth/profile', { name, email, avatar });
        this.admin = response.data.admin;
        localStorage.setItem('admin_profile', JSON.stringify(this.admin));
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    async changePassword(currentPassword, newPassword, newPasswordConfirmation) {
      try {
        const response = await axios.put('/auth/change-password', {
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: newPasswordConfirmation
        });
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    async forgotPassword(email) {
      try {
        const response = await axios.post('/auth/forgot-password', { email });
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    async resetPassword(email, token, password, passwordConfirmation) {
      try {
        const response = await axios.post('/auth/reset-password', {
          email,
          token,
          password,
          password_confirmation: passwordConfirmation
        });
        return response.data;
      } catch (err) {
        throw err;
      }
    },

    logout() {
      this.token = null;
      this.admin = null;
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin_profile');
      this.setAuthHeader();
    }
  }
});
