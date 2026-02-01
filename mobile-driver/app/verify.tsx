import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useAuthStore } from '../store/authStore';

export default function VerifyScreen() {
    const { mobile } = useLocalSearchParams<{ mobile: string }>();
    const [otp, setOtp] = useState('');
    const [loading, setLoading] = useState(false);
    const verifyOtp = useAuthStore((state) => state.verifyOtp);
    const router = useRouter();

    const handleVerify = async () => {
        if (otp.length !== 6) {
            Alert.alert('Error', 'Please enter a 6-digit OTP');
            return;
        }

        setLoading(true);
        const result = await verifyOtp(mobile, otp);
        setLoading(false);

        if (result.success) {
            router.replace('/(tabs)');
        } else {
            Alert.alert('Error', result.message || 'Invalid OTP. Please try again.');
        }
    };

    return (
        <View className="flex-1 justify-center items-center bg-gray-100 p-4">
            <View className="w-full max-w-sm bg-white p-6 rounded-2xl shadow-md">
                <Text className="text-2xl font-bold text-center mb-2 text-gray-800">Verify OTP</Text>
                <Text className="text-gray-500 text-center mb-6">Enter the code sent to {mobile}</Text>

                <TextInput
                    className="w-full bg-gray-50 border border-gray-300 rounded-lg p-4 mb-4 text-lg text-center tracking-widest"
                    placeholder="123456"
                    keyboardType="number-pad"
                    value={otp}
                    onChangeText={setOtp}
                    maxLength={6}
                />

                <TouchableOpacity
                    className="w-full bg-blue-600 p-4 rounded-lg items-center"
                    onPress={handleVerify}
                    disabled={loading}
                >
                    {loading ? (
                        <ActivityIndicator color="#fff" />
                    ) : (
                        <Text className="text-white text-lg font-semibold">Verify</Text>
                    )}
                </TouchableOpacity>

                <TouchableOpacity className="mt-4" onPress={() => router.back()}>
                    <Text className="text-blue-500 text-center">Change Mobile Number</Text>
                </TouchableOpacity>
            </View>
        </View>
    );
}
