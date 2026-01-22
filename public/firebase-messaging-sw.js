importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Force SW activation
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

// Firebase config
firebase.initializeApp({
  apiKey: "AIzaSyAx_aCDd37RcaseDollZxsiB-cH26O-Ap0",
  authDomain: "texi-management.firebaseapp.com",
  projectId: "texi-management",
  messagingSenderId: "1050046281618",
  appId: "1:1050046281618:web:f2f425a0b7a554d784f34b"
});

const messaging = firebase.messaging();

// BACKGROUND notifications (APP CLOSED / MINIMIZED)
messaging.onBackgroundMessage((payload) => {
  if (!payload.data) return;

  const title = payload.data.title || 'Notification';
  const options = {
    body: payload.data.body || '',
    icon: payload.data.icon || '/favicon.ico',
    badge: payload.data.icon || '/favicon.ico',
    data: { url: payload.data.url || '/' },
    requireInteraction: true
  };

  self.registration.showNotification(title, options);
});

// Notification click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientsArr) => {
      for (const client of clientsArr) {
        if ('focus' in client) return client.focus();
      }
      return clients.openWindow(url);
    })
  );
});
