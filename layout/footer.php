<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ✅ المسار الذكي للجافاسكربت لضمان تحميل الدالات (Logout/Delete) -->
<?php 
    $current_script = $_SERVER['SCRIPT_NAME'];
    $path_prefix = (strpos($current_script, '/admin/') !== false || strpos($current_script, '/employee/') !== false) ? '../' : ''; 
?>
<script src="<?php echo $path_prefix; ?>assets/script.js?v=<?php echo time(); ?>"></script>

<!-- ✅ Firebase COMPAT (المهم) -->
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>

<script>
const firebaseConfig = {
    apiKey: "AIzaSyBvfV723RBfzYxTzdETJQ6TtZiisRByXug",
    authDomain: "ejaz-attendance-system.firebaseapp.com",
    projectId: "ejaz-attendance-system",
    storageBucket: "ejaz-attendance-system.firebasestorage.app",
    messagingSenderId: "811966641180",
    appId: "1:811966641180:web:169c5e3bfcce19f43d9bd9",
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

// ❗ طلب الإذن الصحيح
Notification.requestPermission().then(permission => {
    if (permission === "granted") {

        messaging.getToken({
            vapidKey: "BPTxs-fzaA54GGIEsQqwVnw_lr1Nnqnf3b7vQizTlGpl4IhFA5C42-mwfLFlWD6C7BE-Z6lXuucvEYc5Bawmb4Y"
        }).then((token) => {

            console.log("FCM TOKEN:", token);

            fetch("/save_fcm_token", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({token})
            });

        });

    }
});
</script>

<script>
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("/firebase-messaging-sw.js")
    .then(() => console.log("SW Registered"));console.log("SW Active");
}
</script>
</body>
</html>