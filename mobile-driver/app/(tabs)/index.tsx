import { Image, StyleSheet, Platform, View, Text, TouchableOpacity, ScrollView, RefreshControl, Alert, ActivityIndicator } from 'react-native';
import React, { useEffect, useState } from 'react';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import api from '@/services/api';
import { useAuthStore } from '@/store/authStore';
import { formatDate, formatTime } from '@/utils/dateUtils';

export default function HomeScreen() {
  const { user } = useAuthStore();
  const router = useRouter();
  const [dashboardData, setDashboardData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchDashboard = async () => {
    try {
      setLoading(true);
      const res = await api.get('/dashboard');
      setDashboardData(res.data);
    } catch (error: any) {
      console.error(error);
      Alert.alert('Error', 'Failed to load dashboard. Pull to refresh.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchDashboard();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchDashboard();
  };

  const DutyCard = ({ duty }: { duty: any }) => {
    if (!duty) return <Text className="text-gray-500 text-center mt-4">No Active Duty Today</Text>;

    const isStarted = duty.today_log?.status === 'started';
    const isCompleted = duty.today_log?.status === 'completed';

    return (
      <View className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-4">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-lg font-bold text-gray-800">{duty.vehicle?.vehicle_number || 'No Vehicle'}</Text>
          <View className={`px-2 py-1 rounded-full ${isStarted ? 'bg-green-100' : 'bg-yellow-100'}`}>
            <Text className={`${isStarted ? 'text-green-700' : 'text-yellow-700'} text-xs font-semibold uppercase`}>
              {duty.today_log?.status || 'Pending'}
            </Text>
          </View>
        </View>

        <Text className="text-gray-600 mb-1">📅 Contract Start: {formatDate(duty.start_date)}</Text>
        <Text className="text-gray-600 mb-1">⏰ Reporting Time: {formatTime(duty.expected_start_time)}</Text>
        <Text className="text-gray-600 mb-4">🏢 {duty.department_name || 'Department'}</Text>

        {!isCompleted && (
          <TouchableOpacity
            className={`w-full p-4 rounded-lg items-center ${isStarted ? 'bg-red-500' : 'bg-blue-600'}`}
            onPress={() => {
              const type = isStarted ? 'end' : 'start';
              // @ts-ignore
              router.push({
                pathname: '/duty-action',
                params: {
                  logId: duty.today_log?.id,
                  type,
                  vehicle: duty.vehicle?.vehicle_number,
                  currentKm: duty.today_log?.start_km?.toString() || '0'
                }
              });
            }}
          >
            <Text className="text-white font-bold">{isStarted ? 'End Duty' : 'Start Duty'}</Text>
          </TouchableOpacity>
        )}
      </View>
    );
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50">
      <ScrollView
        contentContainerStyle={{ padding: 16 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        <View className="flex-row justify-between items-center mb-6">
          <View>
            <Text className="text-gray-500 text-sm">Today: {formatDate(dashboardData?.date)}</Text>
            <Text className="text-2xl font-bold text-gray-900">{user?.name || 'Driver'}</Text>
          </View>
          <View className="bg-blue-100 p-2 rounded-full">
            <Text className="text-blue-600 font-bold">{user?.name?.substring(0, 2).toUpperCase() || 'DR'}</Text>
          </View>
        </View>

        <Text className="text-lg font-semibold text-gray-800 mb-3">Today's Duty</Text>

        {loading ? (
          <ActivityIndicator size="small" color="#0284c7" className="mt-10" />
        ) : (
          <DutyCard duty={dashboardData?.current_duty} />
        )}

      </ScrollView>
    </SafeAreaView>
  );
}
