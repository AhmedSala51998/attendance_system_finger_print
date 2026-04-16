importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "AIzaSyBvfV723RBfzYxTzdETJQ6TtZiisRByXug",
    authDomain: "ejaz-attendance-system.firebaseapp.com",
    projectId: "ejaz-attendance-system",
    storageBucket: "ejaz-attendance-system.firebasestorage.app",
    messagingSenderId: "811966641180",
    appId: "1:811966641180:web:169c5e3bfcce19f43d9bd9",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    alert("SW TRIGGERED"); // ⚠️ اختبار قوي
    console.log(payload);
});

self.addEventListener("notificationclick", function(event) {

    event.notification.close();

    const url = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: "window", includeUncontrolled: true })
        .then(function(clientList) {

            for (const client of clientList) {
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }

            return clients.openWindow(url);
        })
    );
});