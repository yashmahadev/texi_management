import { View, Text, FlatList, ActivityIndicator, RefreshControl, Alert } from 'react-native';
import React, { useEffect, useState } from 'react';
import { SafeAreaView } from 'react-native-safe-area-context';
import api from '@/services/api';

export default function HistoryScreen() {
    const [logs, setLogs] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    const fetchHistory = async (pageNum = 1) => {
        try {
            if (pageNum === 1) setLoading(true);
            const res = await api.get(`/duty/history?page=${pageNum}`);
            if (pageNum === 1) {
                setLogs(res.data.data);
            } else {
                setLogs(prev => [...prev, ...res.data.data]);
            }
            setHasMore(!!res.data.next_page_url);
        } catch (error: any) {
            console.error(error);
            Alert.alert('Error', 'Failed to load history.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchHistory(1);
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        setPage(1);
        fetchHistory(1);
    };

    const loadMore = () => {
        if (!loading && hasMore) {
            const nextPage = page + 1;
            setPage(nextPage);
            fetchHistory(nextPage);
        }
    }

    const renderItem = ({ item }: { item: any }) => (
        <View className="bg-white p-4 mb-3 rounded-lg shadow-sm mx-4">
            <View className="flex-row justify-between mb-2">
                <Text className="font-bold text-gray-800">{item.duty_date}</Text>
                <Text className={`font-bold uppercase text-xs px-2 py-1 rounded ${item.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                    }`}>
                    {item.status}
                </Text>
            </View>
            <Text className="text-gray-600">🚗 {item.monthly_duty?.vehicle?.vehicle_number || 'N/A'}</Text>
            <View className="flex-row justify-between mt-2">
                <Text>Start: {item.start_time || '-'}</Text>
                <Text>End: {item.end_time || '-'}</Text>
            </View>
            {item.total_km && <Text className="mt-2 text-right font-semibold text-blue-600">{item.total_km} KM</Text>}
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-gray-50">
            <Text className="text-xl font-bold p-4 bg-white border-b border-gray-200">Duty History</Text>
            {loading && page === 1 ? (
                <ActivityIndicator size="large" className="mt-10" />
            ) : (
                <FlatList
                    data={logs}
                    renderItem={renderItem}
                    keyExtractor={(item) => item.id.toString()}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                    onEndReached={loadMore}
                    onEndReachedThreshold={0.5}
                    contentContainerStyle={{ paddingVertical: 10 }}
                    ListEmptyComponent={<Text className="text-center text-gray-400 mt-10">No history found</Text>}
                />
            )}
        </SafeAreaView>
    );
}
