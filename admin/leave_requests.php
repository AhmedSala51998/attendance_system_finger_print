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

        $conn->query("
            UPDATE leave_requests 
            SET status = '$action' 
            WHERE id = $id
        ");

        // 🔔 جلب بيانات الطلب لإرسال إشعار للموظف
        $req = $conn->query("
            SELECT lr.*, e.name AS employee_name 
            FROM leave_requests lr
            JOIN employees e ON e.id = lr.employee_id
            WHERE lr.id = $id
        ")->fetch_assoc();

        if($req){

            $title = "تحديث طلب الإجازة";
            $message = "تم $action طلب الإجازة من {$req['from_date']} إلى {$req['to_date']}";

            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, `desc`, is_read)
                VALUES (?, ?, ?, 'unread')
            ");

            $stmt->bind_param("iss", $req['employee_id'], $title, $message);
            $stmt->execute();
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