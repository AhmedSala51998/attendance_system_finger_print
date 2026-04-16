<?php 
include "../config.php";

if(!isset($_SESSION['employee_id'])){
    header("Location: ../login");
    exit();
}
if($_SESSION['role'] == 'admin'){ header("Location: ../admin/index.php"); exit(); }

$emp_id = $_SESSION['employee_id'];
$msg = "";

/* =========================
   إضافة طلب إذن جديد
========================= */
if(isset($_POST['add_permission'])){

    date_default_timezone_set('Asia/Riyadh'); // 🇸🇦 تايم زون السعودية

    $date = $_POST['date'];
    $from = date("H:i:s", strtotime($_POST['from_time']));
    $to   = date("H:i:s", strtotime($_POST['to_time']));
    $reason = $_POST['reason'];

    $today = date('Y-m-d');
    $now_time = date('H:i:s');

    // ❌ وقت البداية لازم أقل من النهاية
    if($from >= $to){
        $msg = "❌ وقت البداية لازم يكون قبل وقت النهاية";
    }

    // ❌ تاريخ قديم
    elseif($date < $today){
        $msg = "❌ لا يمكن طلب إذن في تاريخ سابق";
    }

    // ❌ لو نفس اليوم → لازم الوقتين يكونوا لسه مجوش
    elseif($date == $today && ($from <= $now_time || $to <= $now_time)){
        $msg = "❌ لا يمكن اختيار وقت انتهى بالفعل";
    }

    // ❌ 3. منع لو عنده إجازة في نفس اليوم
    else {

        $check_leave = $conn->prepare("
            SELECT id FROM leave_requests 
            WHERE employee_id = ?
            AND status = 'approved'
            AND ? BETWEEN from_date AND to_date
        ");
        $check_leave->bind_param("is", $emp_id, $date);
        $check_leave->execute();
        $result_leave = $check_leave->get_result();

        if($result_leave->num_rows > 0){
            $msg = "❌ لا يمكن طلب إذن في يوم لديك إجازة";
        }

        else {


         $stmt1 = $conn->prepare("
                SELECT COUNT(*) 
                FROM holidays 
                WHERE date = ?
            ");
            $stmt1->bind_param("s", $date);
            $stmt1->execute();
            $stmt1->bind_result($holiday_count);
            $stmt1->fetch();
            $stmt1->close();

            if ($holiday_count > 0) {
                 $msg = "لا يمكن إنشاء إذن في يوم إجازة رسمية";
            }else{
                
            // بعد التحقق من holidays مباشرة

            // ✅ التحقق من مواعيد الشيفت
            $shift = getShiftDetails($conn, $emp_id, $date);

            $hasError = false;

            // ❌ لو اليوم إجازة
            if($shift['is_holiday']){
                $msg = "❌ هذا اليوم إجازة: " . $shift['reason'];
                $hasError = true;
            }

            // ❌ خارج الشيفت
            elseif($from < $shift['start_time'] || $to > $shift['end_time']){
                $msg = "❌ هذا الموعد خارج موعد الشيفت (" . $shift['display'] . ")";
                $hasError = true;
            }

            // ❌ داخل البريك
            elseif(isset($shift['has_break']) && $shift['has_break']){

                $break_start = $shift['break_start'] ?? null;
                $break_end   = $shift['break_end'] ?? null;

                if($break_start && $break_end){
                    if(
                        ($from >= $break_start && $from < $break_end) ||
                        ($to > $break_start && $to <= $break_end) ||
                        ($from <= $break_start && $to >= $break_end)
                    ){
                        $msg = "❌ لا يمكن طلب إذن أثناء وقت البريك";
                        $hasError = true;
                    }
                }
            }
            if(!$hasError){ 
            // ✅ الإدخال طبيعي
            $stmt = $conn->prepare("
                INSERT INTO permissions 
                (employee_id, date, from_time, to_time, reason, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("issss", $emp_id, $date, $from, $to, $reason);

            if($stmt->execute()){

                $employee_name = $_SESSION['employee_name'];

                $admins = $conn->query("
                    SELECT id, fcm_token 
                    FROM employees 
                    WHERE role = 'admin' 
                    AND fcm_token IS NOT NULL
                ");

                while($admin = $admins->fetch_assoc()){

                    $admin_id = $admin['id'];
                    $token = $admin['fcm_token'];

                    $title = "طلب إذن جديد";
                    $body  = "الموظف {$employee_name} طلب إذن يوم {$date} من {$from} إلى {$to}";

                    // 1️⃣ حفظ في قاعدة البيانات (زي ما عندك)
                    $stmt_notif = $conn->prepare("
                        INSERT INTO notifications (user_id, title, `desc`, is_read)
                        VALUES (?, ?, ?, 'unread')
                    ");
                    $stmt_notif->bind_param("iss", $admin_id, $title, $body);
                    $stmt_notif->execute();

                    // 2️⃣ تحديد صفحة الفتح حسب الدور
                    $url = "/admin/notifications";
                    $data = [
                        "type" => "permission",
                        "permission_date" => $date,
                        "url" => $url
                    ];

                    // 3️⃣ إرسال FCM
                    if($token){
                        sendFCM($token, $title, $body, $data);
                    }
                }

                $msg = "✅ تم إرسال طلب الإذن بنجاح!";
            }

         }}
        }
    }
}

/* =========================
   جلب الأذونات
========================= */
$permissions = $conn->query("
    SELECT * FROM permissions 
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
                <i class="fas fa-clock"></i> أذوناتي
            </h2>
        </div>

        <!-- Message -->
        <?php if($msg): ?>
            <div style="background:#ecfdf5; color:#16a34a; padding:15px; border-radius:15px; margin-bottom:20px; font-weight:700;">
                <?= $msg; ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:30px;">

            <!-- 🟢 FORM -->
            <div class="glass-panel" style="padding:30px; border-radius:25px;">
                <h3 style="margin-bottom:20px;">طلب إذن جديد</h3>

                <form method="POST" style="display:flex; flex-direction:column; gap:15px;">

                    <label>التاريخ</label>
                    <input type="date" name="date" required style="padding:12px; border-radius:10px; border:1px solid #e2e8f0;">

                    <label>من الساعة</label>
                    <input type="time" name="from_time" required style="padding:12px; border-radius:10px; border:1px solid #e2e8f0;">

                    <label>إلى الساعة</label>
                    <input type="time" name="to_time" required style="padding:12px; border-radius:10px; border:1px solid #e2e8f0;">

                    <label>السبب</label>
                    <textarea name="reason" required style="padding:12px; border-radius:10px; border:1px solid #e2e8f0; min-height:80px;"></textarea>

                    <button type="submit" name="add_permission"
                        style="padding:14px; border:none; border-radius:12px; background:linear-gradient(135deg,#f97316,#ea580c); color:white; font-weight:700;">
                        إرسال الطلب
                    </button>

                </form>
            </div>

            <!-- 🔵 LIST -->
            <div class="glass-panel" style="padding:30px; border-radius:25px;">
                <h3 style="margin-bottom:20px;">سجل الأذونات</h3>

                <?php if($permissions->num_rows > 0): ?>
                    <div style="display:flex; flex-direction:column; gap:15px;">

                        <?php while($p = $permissions->fetch_assoc()): ?>
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
                                    <strong><?= $p['date']; ?></strong>
                                    <div style="font-size:13px; color:#64748b;">
                                        <?= $p['from_time']; ?> → <?= $p['to_time']; ?>
                                    </div>
                                    <div style="font-size:12px; color:#94a3b8;">
                                        <?= htmlspecialchars($p['reason']); ?>
                                    </div>
                                </div>

                                <div>
                                    <?php if($p['status'] == 'approved'): ?>
                                        <span style="color:#16a34a; font-weight:700;">✔ مقبول</span>
                                    <?php elseif($p['status'] == 'rejected'): ?>
                                        <span style="color:#ef4444; font-weight:700;">✖ مرفوض</span>
                                    <?php else: ?>
                                        <span style="color:#f59e0b; font-weight:700;">⏳ انتظار</span>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endwhile; ?>

                    </div>
                <?php else: ?>
                    <div style="text-align:center; color:#94a3b8;">
                        لا توجد أذونات حتى الآن
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include "../layout/footer.php"; ?>