// public/firebase-messaging-sw.js
// [v1.0.3] Force SW update
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
self.addEventListener('fetch', () => { });

// Handle background messages
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    const notificationTitle = payload.notification?.title || payload.data?.title || 'New Assignment';
    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || 'Please check your dashboard.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: {
            url: payload.fcmOptions?.link || payload.fcm_options?.link || payload.data?.link || payload.data?.click_action || '/',
            ...payload.data
        },
        requireInteraction: true,
        tag: 'duty-alert',
        renotify: true,
        vibrate: [200, 100, 200]
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
    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if there's already a window open
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                // Check if the client is at our base URL (for simpler matching)
                if (urlToOpen !== '/' && client.url.includes(urlToOpen) && 'focus' in client) {
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

    // We let onBackgroundMessage handle it if possible, but we log here.
    // Ensure we don't display double notifications by checking tag if possible, 
    // but FCM's SDK usually handles this.
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