<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../config.php";

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}

/* =========================
   موافقة / رفض الإجازة
========================= */
if(isset($_GET['action']) && isset($_GET['id'])){

    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if(in_array($action, ['approved','rejected'])){

        // =====================
        // تحديث الحالة
        // =====================
        $stmt = $conn->prepare("
            UPDATE leave_requests 
            SET status = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();

        // =====================
        // جلب بيانات الطلب
        // =====================
        $req = $conn->query("
            SELECT lr.*, e.name, e.fcm_token
            FROM leave_requests lr
            JOIN employees e ON e.id = lr.employee_id
            WHERE lr.id = $id
        ")->fetch_assoc();

        if($req){

            $emp_id = $req['employee_id'];
            $token  = $req['fcm_token'] ?? null;

            $title = "تحديث طلب الإجازة";

            $statusText = ($action == 'approved') ? "تمت الموافقة" : "تم الرفض";

            $message = "{$statusText} على طلب إجازتك من {$req['from_date']} إلى {$req['to_date']}";

            // =====================
            // حفظ إشعار في DB
            // =====================
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, `desc`, is_read)
                VALUES (?, ?, ?, 'unread')
            ");
            $stmt->bind_param("iss", $emp_id, $title, $message);
            $stmt->execute();

            // =====================
            // FCM إرسال
            // =====================
            $data = [
                "type" => "leave",
                "status" => $action,
                "url" => "/employee/notifications"
            ];

            if($token){
                sendFCM($token, $title, $message, $data);
            }
        }
    }

    header("Location: leave_requests.php");
    exit();
}

/* =========================
   جلب الطلبات
========================= */
$requests = $conn->query("
    SELECT lr.*, e.name AS employee_name
    FROM leave_requests lr
    JOIN employees e ON e.id = lr.employee_id
    ORDER BY lr.id DESC
");

include "../layout/header.php";
?>

<div class="main-content">
<div class="dashboard" style="margin:40px auto; max-width:1000px;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
        <h2>📅 طلبات إجازات الموظفين</h2>
    </div>

    <div class="glass-panel" style="padding:25px;border-radius:20px;">

        <?php if($requests->num_rows == 0): ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                لا توجد طلبات إجازة
            </div>
        <?php endif; ?>

        <?php while($r = $requests->fetch_assoc()): ?>

            <div style="
                padding:20px;
                border-bottom:1px solid #f1f5f9;
                display:flex;
                justify-content:space-between;
                align-items:center;
            ">

                <!-- البيانات -->
                <div>
                    <strong style="color:#1e293b;">
                        <?= $r['employee_name'] ?>
                    </strong>

                    <div style="font-size:14px;color:#64748b;">
                        <?= $r['from_date'] ?> → <?= $r['to_date'] ?>
                    </div>

                    <div style="font-size:13px;color:#94a3b8;">
                        <?= htmlspecialchars($r['reason']) ?>
                    </div>
                </div>

                <!-- الحالة -->
                <div style="text-align:center;">

                    <?php if($r['status'] == 'approved'): ?>
                        <span style="color:#16a34a;font-weight:700;">✔ مقبول</span>

                    <?php elseif($r['status'] == 'rejected'): ?>
                        <span style="color:#ef4444;font-weight:700;">✖ مرفوض</span>

                    <?php else: ?>
                        <span style="color:#f59e0b;font-weight:700;">⏳ معلق</span>

                        <div style="margin-top:10px; display:flex; gap:10px;">
                            <a href="?action=approved&id=<?= $r['id'] ?>"
                               style="color:#16a34a;font-size:13px;">
                                قبول
                            </a>

                            <a href="?action=rejected&id=<?= $r['id'] ?>"
                               style="color:#ef4444;font-size:13px;">
                                رفض
                            </a>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    </div>

</div>
</div>

<?php include "../layout/footer.php"; ?>