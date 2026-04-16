<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "../config.php";

if(!isset($_SESSION['employee_id'])){
    header("Location: ../login");
    exit();
}

if($_SESSION['role'] == 'admin'){
    header("Location: ../admin/index.php");
    exit();
}

$emp_id = $_SESSION['employee_id'];
$msg = "";

/* =========================
   إضافة طلب إجازة
========================= */
if(isset($_POST['add_leave'])){

    $from_date = $_POST['from_date'];
    $to_date   = $_POST['to_date'];
    $reason    = $_POST['reason'];

    date_default_timezone_set('Asia/Riyadh');
    $today = date('Y-m-d');

    $errors = [];

    // 1️⃣ التاريخ لازم يكون في المستقبل
    if ($from_date < $today || $to_date < $today) {
        $errors[] = "لا يمكن طلب إجازة في تاريخ قديم";
    }

    // 2️⃣ from لازم أقل من to
    if ($from_date > $to_date) {
        $errors[] = "تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية";
    }

    // 3️⃣ منع الإجازة في أيام العطلات الرسمية (holidays)
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM holidays 
        WHERE `date` BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $from_date, $to_date);
    $stmt->execute();
    $stmt->bind_result($holiday_count);
    $stmt->fetch();
    $stmt->close();

    if ($holiday_count > 0) {
        $errors[] = "لا يمكن طلب إجازة خلال أيام إجازات رسمية";
    }

    // 4️⃣ منع الإجازة في أيام يوجد فيها Permissions (pending/approved)
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM permissions 
        WHERE employee_id = ?
        AND `date` BETWEEN ? AND ? AND status !='rejected'
    ");
    $stmt->bind_param("iss", $emp_id, $from_date, $to_date);
    $stmt->execute();
    $stmt->bind_result($perm_count);
    $stmt->fetch();
    $stmt->close();

    if ($perm_count > 0) {
        $errors[] = "لا يمكن طلب إجازة في أيام يوجد بها أذونات سابقة أو معلقة";
    }

    // ❌ لو فيه أخطاء نوقف
    if (!empty($errors)) {
        $msg = implode("<br>", $errors);
    }else{

    // =========================
    // INSERT
    // =========================

    $stmt = $conn->prepare("
        INSERT INTO leave_requests 
        (employee_id, from_date, to_date, reason, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("isss", $emp_id, $from_date, $to_date, $reason);

    if($stmt->execute()){

        $employee_name = $_SESSION['employee_name'];

        // 🔔 إشعار للأدمن
        $admins = $conn->query("SELECT id FROM employees WHERE role='admin'");

        while($admin = $admins->fetch_assoc()){
            $admin_id = $admin['id'];

            $title = "طلب إجازة جديد";
            $desc  = "الموظف {$employee_name} طلب إجازة من {$from_date} إلى {$to_date}";

            $stmt_notif = $conn->prepare("
                INSERT INTO notifications (user_id, title, `desc`, is_read)
                VALUES (?, ?, ?, 'unread')
            ");
            $stmt_notif->bind_param("iss", $admin_id, $title, $desc);
            $stmt_notif->execute();
        }

        $msg = "تم إرسال طلب الإجازة بنجاح!";
    }}
}

/* =========================
   جلب الطلبات
========================= */
$leaves = $conn->query("
    SELECT * FROM leave_requests 
    WHERE employee_id = $emp_id 
    ORDER BY id DESC
");

include "../layout/header.php";
?>

<div class="main-content">
<div class="dashboard" style="margin:40px auto; max-width:1000px;">

<!-- Header -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
    <h2 style="margin:0; color:#1e293b;">
        <i class="fas fa-plane-departure"></i> إجازاتي
    </h2>
</div>

<!-- Message -->
<?php if($msg): ?>
<div style="background:#ecfdf5;color:#16a34a;padding:15px;border-radius:15px;margin-bottom:20px;font-weight:700;">
    <?= $msg ?>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:30px;">

<!-- FORM -->
<div class="glass-panel" style="padding:30px; border-radius:25px;">
    <h3 style="margin-bottom:20px;">طلب إجازة جديدة</h3>

    <form method="POST" style="display:flex; flex-direction:column; gap:15px;">

        <label>من تاريخ</label>
        <input type="date" name="from_date" required style="padding:12px;border-radius:10px;border:1px solid #e2e8f0;">

        <label>إلى تاريخ</label>
        <input type="date" name="to_date" required style="padding:12px;border-radius:10px;border:1px solid #e2e8f0;">

        <label>السبب</label>
        <textarea name="reason" required style="padding:12px;border-radius:10px;border:1px solid #e2e8f0;min-height:90px;"></textarea>

        <button type="submit" name="add_leave"
            style="padding:14px; border:none; border-radius:12px; background:linear-gradient(135deg,#f97316,#ea580c); color:white; font-weight:700;">
            إرسال الطلب
        </button>

    </form>
</div>

<!-- LIST -->
<div class="glass-panel" style="padding:30px; border-radius:25px;">
    <h3 style="margin-bottom:20px;">سجل الإجازات</h3>

    <?php if($leaves->num_rows > 0): ?>

        <div style="display:flex; flex-direction:column; gap:15px;">

            <?php while($l = $leaves->fetch_assoc()): ?>
                <div style="
                    padding:15px;
                    border-radius:15px;
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                ">

                    <div>
                        <strong>
                            <?= $l['from_date']; ?> → <?= $l['to_date']; ?>
                        </strong>

                        <div style="font-size:13px;color:#64748b;">
                            <?= htmlspecialchars($l['reason']); ?>
                        </div>
                    </div>

                    <div>
                        <?php if($l['status']=='approved'): ?>
                            <span style="color:#16a34a;font-weight:700;">✔ مقبول</span>
                        <?php elseif($l['status']=='rejected'): ?>
                            <span style="color:#ef4444;font-weight:700;">✖ مرفوض</span>
                        <?php else: ?>
                            <span style="color:#f59e0b;font-weight:700;">⏳ انتظار</span>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endwhile; ?>

        </div>

    <?php else: ?>
        <div style="text-align:center;color:#94a3b8;">
            لا توجد طلبات إجازة حتى الآن
        </div>
    <?php endif; ?>

</div>

</div>
</div>

<?php include "../layout/footer.php"; ?>