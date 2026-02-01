import { View, Text, TextInput, TouchableOpacity, Image, ScrollView, Alert, ActivityIndicator } from 'react-native';
import React, { useState } from 'react';
import { useLocalSearchParams, useRouter } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import api from '@/services/api';

export default function DutyActionScreen() {
    const { logId, type, vehicle, currentKm } = useLocalSearchParams<{ logId: string, type: 'start' | 'end', vehicle: string, currentKm: string }>();
    const [odometer, setOdometer] = useState('');
    const [image, setImage] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const router = useRouter();

    const handleImagePick = async () => {
        const result = await ImagePicker.launchCameraAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsEditing: true,
            quality: 0.5,
        });

        if (!result.canceled) {
            setImage(result.assets[0].uri);
        }
    };

    const handleSubmit = async () => {
        if (!odometer) {
            Alert.alert('Required', 'Please enter odometer reading.');
            return;
        }
        // Validation: End KM > Start KM (if type is end)
        if (type === 'end' && parseInt(odometer) < parseInt(currentKm || '0')) {
            Alert.alert('Invalid', `Odometer must be greater than start KM (${currentKm}).`);
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            const fieldName = type === 'start' ? 'start_km' : 'end_km';
            formData.append(fieldName, odometer);

            if (image) {
                // @ts-ignore
                formData.append('photo', {
                    uri: image,
                    name: 'duty-photo.jpg',
                    type: 'image/jpeg',
                });
            }

            await api.post(`/duty/${logId}/${type}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            Alert.alert('Success', `Duty ${type === 'start' ? 'Started' : 'Ended'} successfully!`, [
                { text: 'OK', onPress: () => router.replace('/(tabs)') }
            ]);

        } catch (error: any) {
            console.error(error);
            Alert.alert('Error', error.response?.data?.message || 'Failed to submit duty.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <ScrollView className="flex-1 bg-white p-6">
            <Text className="text-2xl font-bold mb-2 capitalize">{type} Duty</Text>
            <Text className="text-gray-500 mb-6">Vehicle: {vehicle}</Text>

            <View className="mb-6">
                <Text className="text-gray-700 font-semibold mb-2">Odometer Reading (KM)</Text>
                <TextInput
                    className="bg-gray-50 border border-gray-300 rounded-lg p-4 text-xl"
                    placeholder={type === 'end' ? `Min: ${currentKm}` : 'Enter KM'}
                    keyboardType="number-pad"
                    value={odometer}
                    onChangeText={setOdometer}
                />
            </View>

            <View className="mb-8">
                <Text className="text-gray-700 font-semibold mb-2">Photo Proof</Text>
                <TouchableOpacity
                    className="bg-gray-100 border-2 border-dashed border-gray-300 rounded-xl h-48 justify-center items-center overflow-hidden"
                    onPress={handleImagePick}
                >
                    {image ? (
                        <Image source={{ uri: image }} className="w-full h-full" resizeMode="cover" />
                    ) : (
                        <View className="items-center">
                            <Text className="text-gray-400">Tap to take photo</Text>
                        </View>
                    )}
                </TouchableOpacity>
            </View>

            <TouchableOpacity
                className={`p-4 rounded-lg items-center ${loading ? 'bg-gray-400' : 'bg-blue-600'}`}
                onPress={handleSubmit}
                disabled={loading}
            >
                {loading ? (
                    <ActivityIndicator color="#fff" />
                ) : (
                    <Text className="text-white text-lg font-bold capitalize">Submit {type}</Text>
                )}
            </TouchableOpacity>
        </ScrollView>
    );
}
