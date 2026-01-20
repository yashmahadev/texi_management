// [v1.0.2] Force SW update
// Import and configure the Firebase SDK
// importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
// importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "AIzaSyAx_aCDd37RcaseDollZxsiB-cH26O-Ap0",
    authDomain: "texi-management.firebaseapp.com",
    projectId: "texi-management",
    storageBucket: "texi-management.firebasestorage.app",
    messagingSenderId: "1050046281618",
    appId: "1:1050046281618:web:f2f425a0b7a554d784f34b",
    measurementId: "G-GFYL118YBW"
};

firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();

// messaging.onBackgroundMessage((payload) => {
//     console.log('[firebase-messaging-sw.js] Received background message ', payload);
//     const notificationTitle = payload.notification.title;
//     const notificationOptions = {
//         body: payload.notification.body,
//         icon: '/favicon.ico',
//         data: {
//             link: payload.fcmOptions?.link || payload.fcm_options?.link || payload.data?.link || '/'
//         }
//     };

//     self.registration.showNotification(notificationTitle, notificationOptions);
// });
messaging.onBackgroundMessage(function(payload) {
    const { title, body, icon, link } = payload.data;

    self.registration.showNotification(title, {
        body,
        icon,
        data: {
            url: link
        }
    });
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
// self.addEventListener('notificationclick', function (event) {
//     console.log('[firebase-messaging-sw.js] Notification click Received.', event.notification.data);

//     const notificationData = event.notification.data || {};
//     const link = notificationData.link || '/';

//     event.notification.close();

//     event.waitUntil(
//         clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
//             // Priority 1: Focus existing window with same URL
//             for (var i = 0; i < windowClients.length; i++) {
//                 var client = windowClients[i];
//                 if (client.url === link && 'focus' in client) {
//                     return client.focus();
//                 }
//             }

//             // Priority 2: Focus ANY window of this app and navigate
//             if (windowClients.length > 0) {
//                 const client = windowClients[0];
//                 if ('focus' in client) {
//                     client.focus();
//                     if ('navigate' in client) {
//                         return client.navigate(link);
//                     }
//                 }
//             }

//             // Priority 3: Open new window
//             if (clients.openWindow) {
//                 return clients.openWindow(link);
//             }
//         })
//     );
// });
