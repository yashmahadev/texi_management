import axios from 'axios';
import { getToken } from './storage';

// standard emulator ip for android is 10.0.2.2.
// For physical device, use your machine's LAN IP e.g., http://192.168.1.5:8000/api/driver
const BASE_URL = 'http://192.168.29.213:8000/api/driver';

const api = axios.create({
    baseURL: BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

api.interceptors.request.use(async (config) => {
    const token = await getToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
