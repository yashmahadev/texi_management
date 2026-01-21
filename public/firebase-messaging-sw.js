// public/firebase-messaging-sw.js
// Import Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Initialize Firebase in the service worker
firebase.initializeApp({
    apiKey: "AIzaSyAx_aCDd37RcaseDollZxsiB-cH26O-Ap0",
    authDomain: "texi-management.firebaseapp.com",
    projectId: "texi-management",
    storageBucket: "texi-management.firebasestorage.app",
    messagingSenderId: "1050046281618",
    appId: "1:1050046281618:web:f2f425a0b7a554d784f34b",
    measurementId: "G-GFYL118YBW"
});

const messaging = firebase.messaging();


/**
 * VERY IMPORTANT
 * This empty fetch handler makes Chrome treat SW as active-capable
 */
self.addEventListener('fetch', () => {});

// Handle background messages
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message', payload);

    const notificationTitle = payload.data?.title || payload.notification?.title || 'New Notification';
    const notificationOptions = {
        body: payload.data?.body || payload.notification?.body || 'You have a new notification',
        icon: payload.data?.icon || payload.notification?.icon || '/favicon.ico',
        badge: payload.data?.icon || '/favicon.ico',
        tag: payload.data?.tag || 'notification-' + Date.now(),
        requireInteraction: true,
        vibrate: [200, 100, 200],
        data: {
            url: payload.data?.link || payload.fcmOptions?.link || '/',
            click_action: payload.data?.click_action || payload.data?.link,
            ...payload.data
        },
        actions: [
            {
                action: 'open',
                title: 'Open'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };

    // Show notification
    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
    console.log('[firebase-messaging-sw.js] Notification click received.', event);

    event.notification.close();

    // Handle action buttons
    if (event.action === 'close') {
        return;
    }

    // Get the URL from notification data
    const urlToOpen = event.notification.data?.url || event.notification.data?.click_action || '/';

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if there's already a window open
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // If no window is open, open a new one
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Handle push event (additional layer for reliability)
self.addEventListener('push', (event) => {
    console.log('[firebase-messaging-sw.js] Push event received', event);

    if (!event.data) {
        console.log('[firebase-messaging-sw.js] Push event has no data');
        return;
    }

    try {
        const payload = event.data.json();
        console.log('[firebase-messaging-sw.js] Push payload:', payload);
    } catch (error) {
        console.error('[firebase-messaging-sw.js] Error parsing push data:', error);
    }
});

// Periodic sync to keep service worker alive (optional)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'keep-alive') {
        event.waitUntil(
            // Dummy task to keep service worker alive
            Promise.resolve()
        );
    }
});

// Service worker activation
self.addEventListener('activate', (event) => {
    console.log('[firebase-messaging-sw.js] Service worker activated');
    event.waitUntil(clients.claim());
});

// Service worker installation
self.addEventListener('install', (event) => {
    console.log('[firebase-messaging-sw.js] Service worker installed');
    self.skipWaiting();
});