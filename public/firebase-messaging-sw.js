// Import and configure the Firebase SDK
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Initialize the Firebase app in the service worker by passing in
// your app's Firebase config object.
// You can get this object from your Firebase project settings.
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

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    // Customize notification here
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/firebase-logo.png'
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
