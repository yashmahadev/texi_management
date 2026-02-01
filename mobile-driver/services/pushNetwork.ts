import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

Notifications.setNotificationHandler({
    handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
        shouldShowBanner: true,
        shouldShowList: true,
    }),
});

export async function registerForPushNotificationsAsync() {
    let token;

    if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync('default', {
            name: 'default',
            importance: Notifications.AndroidImportance.MAX,
            vibrationPattern: [0, 250, 250, 250],
            lightColor: '#FF231F7C',
        });
    }

    if (Device.isDevice) {
        const { status: existingStatus } = await Notifications.getPermissionsAsync();
        let finalStatus = existingStatus;
        if (existingStatus !== 'granted') {
            const { status } = await Notifications.requestPermissionsAsync();
            finalStatus = status;
        }
        if (finalStatus !== 'granted') {
            console.log('Failed to get push token for push notification!');
            return;
        }

        try {
            // Switch to getDevicePushTokenAsync() to get the RAW FCM Token.
            // This does NOT require an Expo Project ID.
            // It is required if you are sending notifications directly from Laravel via Firebase Admin SDK.

            const tokenInstance = await Notifications.getDevicePushTokenAsync();
            token = tokenInstance.data;
            console.log('FCM Device Token Generated:', token);
        } catch (e) {
            console.error('Push Token Error:', e);
            return null;
        }
    } else {
        console.log('Must use physical device (or Emulator with Play Services) for Push Notifications');
    }

    return token;
}
