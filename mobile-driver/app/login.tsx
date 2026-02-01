import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../store/authStore';

export default function LoginScreen() {
    const [mobile, setMobile] = useState('');
    const [loading, setLoading] = useState(false);
    const router = useRouter();
    const login = useAuthStore((state) => state.login);

    const handleLogin = async () => {
        if (mobile.length < 10) {
            Alert.alert('Error', 'Please enter a valid mobile number');
            return;
        }

        setLoading(true);
        const result = await login(mobile);
        setLoading(false);

        if (result.success) {
            router.push({ pathname: '/verify', params: { mobile } });
        } else {
            Alert.alert('Error', result.message || 'Failed to send OTP.');
        }
    };

    return (
        <View className="flex-1 justify-center items-center bg-gray-100 p-4">
            <View className="w-full max-w-sm bg-white p-6 rounded-2xl shadow-md">
                <Text className="text-2xl font-bold text-center mb-2 text-gray-800">Driver Login</Text>
                <Text className="text-gray-500 text-center mb-6">Enter your mobile number to continue</Text>

                <TextInput
                    className="w-full bg-gray-50 border border-gray-300 rounded-lg p-4 mb-4 text-lg"
                    placeholder="Mobile Number"
                    keyboardType="phone-pad"
                    value={mobile}
                    onChangeText={setMobile}
                    maxLength={10}
                />

                <TouchableOpacity
                    className="w-full bg-blue-600 p-4 rounded-lg items-center"
                    onPress={handleLogin}
                    disabled={loading}
                >
                    {loading ? (
                        <ActivityIndicator color="#fff" />
                    ) : (
                        <Text className="text-white text-lg font-semibold">Send OTP</Text>
                    )}
                </TouchableOpacity>
            </View>
        </View>
    );
}
