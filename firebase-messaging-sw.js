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

messaging.onBackgroundMessage(function(payload) {

    console.log("FULL PAYLOAD:", payload);

    const title = payload.data.title;
    const body  = payload.data.body;
    const url   = payload.data.url || "/";

    self.registration.showNotification(title, {
        body: body,
        icon: "/images/logo.png",
        data: { url }
    });
});

self.addEventListener("push", function(event) {

    console.log("🔥 PUSH RECEIVED:", event);

    const data = event.data ? event.data.json() : {};

    const title = data?.notification?.title || data?.data?.title || "No title";
    const body  = data?.notification?.body  || data?.data?.body  || "";

    event.waitUntil(
        self.registration.showNotification(title, {
            body: body,
            icon: "/images/logo.png",
            data: data?.data || {}
        })
    );
});