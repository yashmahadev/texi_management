<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>

<style>
    #fcm-permission-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        z-index: 9999;
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        text-align: center;
        padding: 20px;
        backdrop-filter: blur(5px);
    }
    #fcm-permission-overlay .card {
        background: #fff;
        color: #333;
        padding: 30px;
        border-radius: 15px;
        max-width: 400px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    #fcm-permission-overlay h2 {
        color: #0d6efd;
        font-weight: bold;
    }
    #fcm-permission-overlay p {
        font-size: 1.1rem;
        margin-bottom: 25px;
    }
    #fcm-permission-overlay .btn-allow {
        background: #0d6efd;
        color: white;
        padding: 12px 30px;
        border-radius: 30px;
        font-weight: bold;
        border: none;
        transition: 0.3s;
    }
    #fcm-permission-overlay .btn-allow:hover {
        background: #0b5ed7;
        transform: scale(1.05);
    }
    .permission-denied-hint {
        font-size: 0.9rem;
        color: #dc3545;
        margin-top: 15px;
        display: none;
    }
</style>

<div id="fcm-permission-overlay">
    <div class="card">
        <i class="bi bi-bell-fill mb-3" style="font-size: 4rem; color: #0d6efd;"></i>
        <h2>Notifications Required</h2>
        <p id="fcm-prompt-text">To receive real-time duty assignments and updates, please enable notifications for this app.</p>
        
        <div id="action-area">
            <button class="btn-allow" onclick="window.requestPermission()">Allow Notifications</button>
        </div>

        <div id="denied-hint" class="permission-denied-hint" style="text-align: left; background: #fff5f5; border: 1px solid #feb2b2; padding: 15px; border-radius: 10px;">
            <div class="fw-bold mb-2">
                <i class="bi bi-exclamation-octagon-fill"></i> Notifications are BLOCKED!
            </div>
            <div class="small text-muted mb-3">
                The browser is preventing us from asking for permission. You must manualy enable it:
            </div>
            <ol class="small text-muted mb-3" style="padding-left: 20px;">
                <li>Click the <b>Lock</b> icon (🔒) or <b>Settings</b> icon (instantly left of the URL).</li>
                <li>Find <b>Notifications</b>.</li>
                <li>Change the setting to <b>Allow</b>.</li>
            </ol>
            <button class="btn btn-sm btn-outline-primary w-100" onclick="window.checkPermission()">
                <i class="bi bi-arrow-repeat"></i> I have enabled it, check again
            </button>
        </div>
    </div>
</div>

<script>
    // Firebase Configuration - Replace with your own config from Firebase Console
    const firebaseConfig = {
        apiKey: "AIzaSyAx_aCDd37RcaseDollZxsiB-cH26O-Ap0",
        authDomain: "texi-management.firebaseapp.com",
        projectId: "texi-management",
        storageBucket: "texi-management.firebasestorage.app",
        messagingSenderId: "1050046281618",
        appId: "1:1050046281618:web:f2f425a0b7a554d784f34b",
        measurementId: "G-GFYL118YBW"
    };

    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/firebase-messaging-sw.js')
            .then((registration) => {
                console.log('FCM: Service Worker registered. Scope:', registration.scope);
                window.fcmRegistration = registration;
            }).catch((err) => {
                console.error('FCM: Service Worker registration failed:', err);
            });
    }

    const overlay = document.getElementById('fcm-permission-overlay');
    const deniedHint = document.getElementById('denied-hint');
    const actionArea = document.getElementById('action-area');
    const promptText = document.getElementById('fcm-prompt-text');

    window.checkPermission = function() {
        const isAuthenticated = {{ auth($guard ?? "web")->check() ? 'true' : 'false' }};
        if (!isAuthenticated) {
            console.log('FCM: Skipping permission check as user is not authenticated.');
            return;
        }

        console.log('FCM: Checking permission state:', Notification.permission);
        if (Notification.permission === 'granted') {
            overlay.style.display = 'none';
            window.getFcmToken();
        } else if (Notification.permission === 'denied') {
            overlay.style.display = 'flex';
            deniedHint.style.display = 'block';
            actionArea.style.display = 'none';
            promptText.style.display = 'none';
        } else {
            overlay.style.display = 'flex';
            deniedHint.style.display = 'none';
            actionArea.style.display = 'block';
            promptText.style.display = 'block';
        }
    };

    // Foreground Message Handler
    messaging.onMessage((payload) => {
        console.log('FCM: Foreground message received:', payload);
        if (Notification.permission === 'granted') {
            const notificationTitle = payload.notification.title;
            const logo = '{{ \App\Models\Setting::get("company_logo") ? asset("storage/" . \App\Models\Setting::get("company_logo")) : asset("favicon.ico") }}';
            const notificationOptions = {
                body: payload.notification.body,
                icon: logo,
                data: {
                    link: payload.fcmOptions?.link || payload.fcm_options?.link || payload.data?.link || null
                }
            };
            const notification = new Notification(notificationTitle, notificationOptions);
            notification.onclick = function(event) {
                event.preventDefault();
                
                // Robust link retrieval for different browser event objects
                const data = (event.notification && event.notification.data) 
                    ? event.notification.data 
                    : (event.target && event.target.data ? event.target.data : {});
                
                const link = data.link;
                console.log('FCM: Foreground notification clicked. Data:', data);

                if (link) {
                    // If it's an absolute URL and matches current origin, or is relative
                    if (link.startsWith('/') || link.includes(window.location.host)) {
                        window.location.href = link;
                    } else {
                        window.open(link, '_blank');
                    }
                } else {
                    window.focus();
                }
                notification.close();
            };
        }
    });

    // Function to get Token
    window.getFcmToken = function() {
        console.log('FCM: Retrieving token...');
        const tokenOptions = { 
            vapidKey: 'BIsVK2bY_D2XmJUkcvqLz3ajwLE6qpCihrDouNyy3SHw_iqf7FSjv1FWwAb5GeAb6NkHuE3_paBn0Mt2E6e5J-8'
        };

        if (window.fcmRegistration) {
            tokenOptions.serviceWorkerRegistration = window.fcmRegistration;
        }

        messaging.getToken(tokenOptions)
            .then((currentToken) => {
                if (currentToken) {
                    const guard = '{{ $guard ?? "web" }}';
                    const userId = '{{ auth($guard ?? "web")->id() }}';
                    const syncKey = 'last_synced_fcm_token_' + guard + '_' + userId;
                    // const lastSyncedToken = localStorage.getItem(syncKey);
                    // const forceSync = {{ session('fcm_sync_required') ? 'true' : 'false' }};
                    
                    // if (!forceSync && lastSyncedToken === currentToken) {
                    //     console.log('FCM: Token is already synced for this account. Skipping.');
                    //     return;
                    // }

                    // if (forceSync) {
                    //     console.log('FCM: Fresh login detected. Forcing token sync.');
                    // }

                    console.log('FCM: New token or account detected. Syncing with server...');
                    
                    fetch('/update-fcm-token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            token: currentToken
                        })
                    })
                    .then(response => {
                        console.log('FCM: Update Response Status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            console.log('FCM: Token synced successfully.', data.sync_info);
                            localStorage.setItem(syncKey, currentToken);
                        } else {
                            console.error('FCM: Sync failed:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('FCM: Sync Error:', error);
                    });

                } else {
                    console.warn('FCM: No registration token available.');
                    localStorage.removeItem('last_synced_fcm_token');
                }
            }).catch((err) => {
                console.error('FCM: An error occurred while retrieving token:', err);
            });
    };

    // Request Permission
    window.requestPermission = function() {
        console.log('FCM: requestPermission button clicked');
        if (!('Notification' in window)) {
            alert('This browser does not support desktop notification');
            return;
        }
        
        Notification.requestPermission().then((permission) => {
            console.log('FCM: Permission result:', permission);
            window.checkPermission();
        }).catch(err => {
            console.error('FCM: Error requesting permission:', err);
        });
    };

    // Automatically request permission on page load
    window.addEventListener('load', () => {
        setTimeout(() => {
            window.checkPermission();
        }, 1000);
    });
</script>
