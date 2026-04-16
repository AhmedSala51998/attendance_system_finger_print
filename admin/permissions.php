<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "../config.php";

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}

$msg = "";
$edit_permission = null;

$msg_deleted = false;

if(isset($_SESSION['deleted'])) {
    $msg_deleted = true;
    unset($_SESSION['deleted']);
}

// قبول الإذن
if(isset($_GET['approve'])){
    $id = intval($_GET['approve']);

    // تحديث الحالة
    $conn->query("UPDATE permissions SET status='approved' WHERE id=$id");

    // جلب بيانات الموظف
    $perm = $conn->query("SELECT employee_id FROM permissions WHERE id=$id")->fetch_assoc();
    $emp_id = $perm['employee_id'];

    $emp = $conn->query("SELECT name, fcm_token FROM employees WHERE id=$emp_id")->fetch_assoc();
    $token = $emp['fcm_token'] ?? null;

    // إشعار
    $title = "تم قبول الإذن";
    $body  = "تمت الموافقة على طلب الإذن الخاص بك";

    $conn->query("
        INSERT INTO notifications (user_id, title, `desc`, is_read)
        VALUES ($emp_id, '$title', '$body', 'unread')
    ");

    // FCM
    $data = [
        "type" => "permission",
        "status" => "approved",
        "url" => "/employee/notifications"
    ];

    if($token){
        sendFCM($token, $title, $body, $data);
    }

    header("Location: permissions.php");
    exit();
}

// رفض الإذن
if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);

    // تحديث الحالة
    $conn->query("UPDATE permissions SET status='rejected' WHERE id=$id");

    // جلب بيانات الموظف
    $perm = $conn->query("SELECT employee_id FROM permissions WHERE id=$id")->fetch_assoc();
    $emp_id = $perm['employee_id'];

    $emp = $conn->query("SELECT name, fcm_token FROM employees WHERE id=$emp_id")->fetch_assoc();
    $token = $emp['fcm_token'] ?? null;

    // إشعار
    $title = "تم رفض الإذن";
    $body  = "تم رفض طلب الإذن الخاص بك";

    $conn->query("
        INSERT INTO notifications (user_id, title, `desc`, is_read)
        VALUES ($emp_id, '$title', '$body', 'unread')
    ");

    // FCM
    $data = [
        "type" => "permission",
        "status" => "rejected",
        "url" => "/employee/notifications"
    ];

    if($token){
        sendFCM($token, $title, $body, $data);
    }

    header("Location: permissions.php");
    exit();
}

// حذف إذن
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM permissions WHERE id = $id");
    $_SESSION['deleted'] = true;
    header("Location: permissions.php");
    exit();
}

// تعديل
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM permissions WHERE id = $id");
    $edit_permission = $res->fetch_assoc();
}

// إضافة / تحديث
if(isset($_POST['save_permission'])){

    $employee_id = (int) $_POST['employee_id'];
    $date        = $_POST['date'];
    $from = date("H:i:s", strtotime($_POST['from_time']));
    $to   = date("H:i:s", strtotime($_POST['to_time']));
    $status      = $_POST['status'];
    $id          = $_POST['permission_id'] ?? 0;

    date_default_timezone_set('Asia/Riyadh');

    $today = date('Y-m-d');
    $nowTime = date('H:i:s');

    $errors = [];

    // 1️⃣ التاريخ لا يكون في الماضي
    if ($date < $today) {
        $errors[] = "لا يمكن إنشاء إذن بتاريخ سابق";
    }

    // 2️⃣ لو نفس اليوم → الوقت لازم يكون في المستقبل
    if ($date == $today) {
        if ($from <= $nowTime) {
            $errors[] = "وقت البداية يجب أن يكون في المستقبل";
        }
        if ($to <= $nowTime) {
            $errors[] = "وقت النهاية يجب أن يكون في المستقبل";
        }
    }

    // 3️⃣ from_time لازم أقل من to_time
    if ($from >= $to) {
        $errors[] = "وقت البداية يجب أن يكون أقل من وقت النهاية";
    }

    // 4️⃣ منع التداخل مع الإجازات (leave_requests)
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM leave_requests 
        WHERE employee_id=? 
        AND status='approved'
        AND (
            (date BETWEEN from_date AND to_date)
        )
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->bind_result($leave_count);
    $stmt->fetch();
    $stmt->close();

    if ($leave_count > 0) {
        $errors[] = "لا يمكن إنشاء إذن في فترة إجازة الموظف";
    }


    // 5️⃣ منع الإذن في يوم إجازة رسمية (Holidays)
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
        $errors[] = "لا يمكن إنشاء إذن في يوم إجازة رسمية";
    }

    // 6️⃣ التحقق من مواعيد الشيفت
    $shift = getShiftDetails($conn, $employee_id, $date);

    // ❌ لو اليوم إجازة
    if ($shift['is_holiday']) {
        $errors[] = "هذا اليوم إجازة: " . $shift['reason'];
    } else {

        // ❌ خارج مواعيد الشيفت
        if ($from < $shift['start_time'] || $to > $shift['end_time']) {
            $errors[] = "هذا الموعد خارج موعد الشيفت (" . $shift['display'] . ")";
        }

        // ❌ (اختياري 🔥) داخل البريك
        if (isset($shift['has_break']) && $shift['has_break']) {

            $break_start = $shift['break_start'] ?? null;
            $break_end   = $shift['break_end'] ?? null;

            if ($break_start && $break_end) {

                if (
                    ($from >= $break_start && $from < $break_end) ||
                    ($to > $break_start && $to <= $break_end) ||
                    ($from <= $break_start && $to >= $break_end)
                ) {
                    $errors[] = "لا يمكن إنشاء إذن أثناء وقت البريك";
                }
            }
        }
    }

    // ❌ لو فيه أخطاء
    if (!empty($errors)) {
        $msg = implode("<br>", $errors);
    }else{

    // =========================
    // الحفظ
    // =========================

    if($id){
        $stmt = $conn->prepare("
            UPDATE permissions 
            SET employee_id=?, date=?, from_time=?, to_time=?, status=? 
            WHERE id=?
        ");
        $stmt->bind_param("issssi", $employee_id, $date, $from, $to, $status, $id);
        $stmt->execute();

        $msg = "تم تحديث الإذن بنجاح";

    } else {
        $stmt = $conn->prepare("
            INSERT INTO permissions (employee_id, date, from_time, to_time, status) 
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("issss", $employee_id, $date, $from, $to, $status);
        $stmt->execute();

        $msg = "تم إضافة الإذن بنجاح";
    }
    // =========================
    // 🔔 جلب بيانات الموظف + التوكن
    // =========================
    $emp = $conn->query("SELECT name, fcm_token FROM employees WHERE id = $employee_id");
    $empData = $emp->fetch_assoc();

    $employee_name = $empData['name'];
    $token = $empData['fcm_token'] ?? null;

    // =========================
    // 🔔 تجهيز بيانات الإشعار
    // =========================
    $title = "تم اعتماد إذن";
    $body  = "تم إضافة/تحديث إذن لك بتاريخ {$date} من {$from} إلى {$to}";

    // حفظ في جدول notifications (للموظف)
    $stmt_notif = $conn->prepare("
        INSERT INTO notifications (user_id, title, `desc`, is_read)
        VALUES (?, ?, ?, 'unread')
    ");

    $stmt_notif->bind_param("iss", $employee_id, $title, $body);
    $stmt_notif->execute();

    // =========================
    // 🔥 إرسال FCM
    // =========================
    $url = "/employee/notifications";

    $data = [
        "type" => "permission",
        "permission_date" => $date,
        "url" => $url,
        "title" => $title,
        "body" => $body
    ];

    if ($token) {
        sendFCM($token, $title, $body, $data);
    }
 }
}

// الموظفين
$employees = $conn->query("SELECT id, name FROM employees");

// الأذونات
$permissions = $conn->query("
    SELECT p.*, e.name 
    FROM permissions p
    LEFT JOIN employees e ON p.employee_id = e.id
    ORDER BY p.id DESC
");

include "../layout/header.php";
?>
<?php if($msg_deleted): ?>
<script>
Swal.fire({
    title: 'تم الحذف!',
    text: 'تم حذف الإذن بنجاح',
    icon: 'success',
    confirmButtonText: 'تمام'
});
</script>
<?php endif; ?>
<div class="main-content">
<div class="dashboard" style="margin:40px auto; max-width:1100px;">

<!-- Header -->
<h2 style="margin-bottom:30px;">إدارة الأذونات ⏱️</h2>

<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:30px;">

<!-- الفورم -->
<div class="glass-panel" style="padding:30px;">
<h3><?= $edit_permission ? 'تعديل إذن' : 'إضافة إذن جديد' ?></h3>

<?php if($msg): ?>
<div style="background:#dcfce7; padding:10px; border-radius:10px; margin-bottom:15px;">
<?= $msg ?>
</div>
<?php endif; ?>

<form method="POST" style="display:flex; flex-direction:column; gap:15px;">
<input type="hidden" name="permission_id" value="<?= $edit_permission['id'] ?? '' ?>">
<div class="select-wrapper">
<select name="employee_id" required>
<option value="">اختر الموظف</option>
<?php while($emp = $employees->fetch_assoc()): ?>
<option value="<?= $emp['id'] ?>" 
<?= (isset($edit_permission) && $edit_permission['employee_id'] == $emp['id']) ? 'selected' : '' ?>>
<?= $emp['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<input type="date" name="date" required value="<?= $edit_permission['date'] ?? '' ?>">

<label>من الساعة</label>
<input type="time" name="from_time" required value="<?= $edit_permission['from_time'] ?? '' ?>">

<label>إلى الساعة</label>
<input type="time" name="to_time" required value="<?= $edit_permission['to_time'] ?? '' ?>">
<div class="select-wrapper">
<select name="status">
<option value="pending" <?= (isset($edit_permission) && $edit_permission['status']=='pending')?'selected':'' ?>>قيد الانتظار</option>
<option value="approved" <?= (isset($edit_permission) && $edit_permission['status']=='approved')?'selected':'' ?>>موافق</option>
<option value="rejected" <?= (isset($edit_permission) && $edit_permission['status']=='rejected')?'selected':'' ?>>مرفوض</option>
</select>
</div>

<button type="submit" name="save_permission">حفظ</button>
</form>
</div>

<!-- الجدول -->
<div class="glass-panel" style="padding:30px;">
<h3>سجل الأذونات</h3>

<div style="overflow-x:auto;">
<table style="width:100%; min-width:700px;">
<tr>
<th>الموظف</th>
<th>التاريخ</th>
<th>من</th>
<th>إلى</th>
<th>الحالة</th>
<th>خيارات</th>
</tr>

<?php while($p = $permissions->fetch_assoc()): ?>
<tr>
<td><?= $p['name'] ?></td>
<td><?= $p['date'] ?></td>
<td><?= $p['from_time'] ?></td>
<td><?= $p['to_time'] ?></td>
<td>
<?php
if($p['status']=='approved') echo "✅ موافق";
elseif($p['status']=='rejected') echo "❌ مرفوض";
else echo "⏳ انتظار";
?>
</td>

<td style="display:flex; gap:8px; align-items:center;">

<?php if($p['status'] == 'pending'): ?>

    <!-- زر قبول -->
    <a href="?approve=<?= $p['id'] ?>" 
       style="color:#16a34a; background:#dcfce7; padding:5px 10px; border-radius:8px; font-size:13px;">
       ✔ قبول
    </a>

    <!-- زر رفض -->
    <a href="?reject=<?= $p['id'] ?>" 
       style="color:#dc2626; background:#fee2e2; padding:5px 10px; border-radius:8px; font-size:13px;">
       ✖ رفض
    </a>

<?php endif; ?>

<!-- تعديل -->
<a href="?edit=<?= $p['id'] ?>">✏️</a>

<!-- حذف -->
<a onclick="confirmDeletePermission(<?= $p['id'] ?>)" 
   style="color:#ef4444; background:rgba(239,68,68,0.1); width:35px; height:35px; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer;">
    <i class="fas fa-trash-alt"></i>
</a>

</td>
</tr>
<?php endwhile; ?>

</table>
</div>
</div>

</div>
</div>
</div>

<?php include "../layout/footer.php"; ?>