import { create } from 'zustand';
import { saveToken, removeToken, getToken } from '../services/storage';
import { registerForPushNotificationsAsync } from '../services/pushNetwork';
import api from '../services/api';

interface User {
    id: number;
    name: string;
    mobile_number: string;
    // add other fields
}

interface AuthState {
    user: User | null;
    token: string | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (mobile: string) => Promise<{ success: boolean; message?: string }>;
    verifyOtp: (mobile: string, otp: string) => Promise<{ success: boolean; message?: string }>;
    logout: () => Promise<void>;
    checkAuth: () => Promise<void>;
    registerPushToken: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
    user: null,
    token: null,
    isAuthenticated: false,
    isLoading: true,

    login: async (mobile: string) => {
        try {
            await api.post('/login', { mobile_number: mobile });
            return { success: true, message: 'OTP sent successfully' };
        } catch (error: any) {
            const msg = error.response?.data?.message || error.message || 'Login failed';
            console.error('Login error', msg);
            return { success: false, message: msg };
        }
    },

    verifyOtp: async (mobile: string, otp: string) => {
        try {
            const response = await api.post('/verify', { mobile_number: mobile, otp });
            const { token, driver } = response.data;

            await saveToken(token);
            set({ token, user: driver, isAuthenticated: true });

            // Trigger Push Token Registration after login
            get().registerPushToken();

            return { success: true };
        } catch (error: any) {
            const msg = error.response?.data?.message || 'Invalid OTP';
            console.error('Verify error', msg);
            return { success: false, message: msg };
        }
    },

    logout: async () => {
        try {
            await api.post('/logout');
        } catch (e) {
            // ignore
        }
        await removeToken();
        set({ token: null, user: null, isAuthenticated: false });
    },

    checkAuth: async () => {
        set({ isLoading: true });
        const token = await getToken();
        if (token) {
            // Optionally validate token with an API call /user or /dashboard
            // For now, assume valid or handle 401 in interceptor later
            set({ token, isAuthenticated: true });
            get().registerPushToken();
        }
        set({ isLoading: false });
    },

    registerPushToken: async () => {
        try {
            const token = await registerForPushNotificationsAsync();
            if (token) {
                await api.post('/update-fcm-token', { fcm_token: token });
                console.log('FCM Token updated on server');
            }
        } catch (e) {
            console.error('Failed to update FCM token', e);
        }
    }
}));
