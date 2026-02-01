import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const isWeb = Platform.OS === 'web';

export const saveToken = async (token: string) => {
    if (isWeb) {
        localStorage.setItem('auth_token', token);
    } else {
        await SecureStore.setItemAsync('auth_token', token);
    }
};

export const getToken = async () => {
    if (isWeb) {
        return localStorage.getItem('auth_token');
    } else {
        return await SecureStore.getItemAsync('auth_token');
    }
};

export const removeToken = async () => {
    if (isWeb) {
        localStorage.removeItem('auth_token');
    } else {
        await SecureStore.deleteItemAsync('auth_token');
    }
};
