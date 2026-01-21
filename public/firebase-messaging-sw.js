importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

/**
 * Force immediate activation
 */
self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

/**
 * Firebase config (USE YOUR OWN)
 */
firebase.initializeApp({
  apiKey: "AIzaSyAx_aCDd37RcaseDollZxsiB-cH26O-Ap0",
  authDomain: "texi-management.firebaseapp.com",
  projectId: "texi-management",
  messagingSenderId: "1050046281618",
  appId: "1:1050046281618:web:f2f425a0b7a554d784f34b",  
});

const messaging = firebase.messaging();

/**
 * Handle background messages (DATA-ONLY)
 */
messaging.onBackgroundMessage((payload) => {
  if (!payload?.data) return;

  const title = payload.data.title || 'Notification';
  const body = payload.data.body || '';
  const icon = payload.data.icon || '/favicon.ico';
  const url  = payload.data.url || '/';

  const options = {
    body,
    icon,
    badge: icon,
    data: {
      url,
    },
    tag: `notif-${Date.now()}`, // prevent collapse
    requireInteraction: true,
  };

  self.registration.showNotification(title, options);
});

/**
 * Handle notification click
 */
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification?.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      return clients.openWindow(url);
    })
  );
});
