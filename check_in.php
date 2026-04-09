<?php
include "config.php";
include "distance.php"; // 1. إضافة ملف المسافة

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مسجل دخول']); exit();
}

$emp_id = $_SESSION['employee_id'];
$today = date('Y-m-d');
$now_time = date('H:i:s');

// 💡 2. جلب إعدادات المسافة والمقر من الداتابيز
$settings = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();
$company_lat = $settings['company_lat'];
$company_lng = $settings['company_lng'];
$allowed_dist = $settings['allowed_distance'];

// 3. استقبال إحداثيات الموظف والتحقق من المسافة
$user_lat = $_POST['lat'] ?? null;
$user_lng = $_POST['lng'] ?? null;

if($user_lat && $user_lng){
    $dist = distance($user_lat, $user_lng, $company_lat, $company_lng);
    if($dist > $allowed_dist){
        echo json_encode(['status' => 'error', 'message' => 'عذراً، أنت خارج النطاق المسموح به لتسجيل الحضور. المسافة الحالية: ' . round($dist) . ' متر']);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'يرجى السماح بالوصول للموقع الجغرافي (GPS) للمتابعة']);
    exit();
}

// 💡 استخدام المحرك المركزي للمواعيد
$shift = getShiftDetails($conn, $emp_id, $today);

// منع الحضور في الإجازات (حماية إضافية)
if ($shift['is_holiday']) {
    echo json_encode(['status' => 'error', 'message' => "اليوم إجازة: " . $shift['reason']]);
    exit();
}

// 4. التحقق من وجود سجل مسبق اليوم (مع معالجة حالة الغياب التلقائي)
$check_res = $conn->query("SELECT id, status FROM attendance WHERE employee_id = $emp_id AND date = '$today'");
$existing_record = $check_res->fetch_assoc();

if ($existing_record) {
    if ($existing_record['status'] != 'absent') {
        echo json_encode(['status' => 'error', 'message' => 'لقد قمت بتسجيل الحضور مسبقاً اليوم.']);
        exit();
    }
    // إذا كانت الحالة 'absent'، سنقوم بتحديث هذا السجل بدلاً من رفض الطلب
}

// حساب دقائق التأخير بناءً على موعد بداية الوردية لليوم المحدد
$start_time = new DateTime($shift['start_time']);
$now = new DateTime($now_time);

$late_minutes = 0;
if ($now > $start_time) {
    $diff = $start_time->diff($now);
    $late_minutes = ($diff->h * 60) + $diff->i;
}

$status = ($late_minutes > 15) ? 'late' : 'present';

if ($existing_record && $existing_record['status'] == 'absent') {
    // تحديث سجل الغياب القائم
    $sql = "UPDATE attendance SET check_in = NOW(), status = '$status', late_minutes = $late_minutes WHERE id = " . $existing_record['id'];
} else {
    // إدخال سجل جديد
    $sql = "INSERT INTO attendance (employee_id, date, check_in, status, late_minutes) 
            VALUES ($emp_id, '$today', NOW(), '$status', $late_minutes)";
}

if ($conn->query($sql)) {
    $msg = "تم تسجيل الحضور بنجاح.";
    if($late_minutes > 0) $msg .= " (تأخير: $late_minutes دقيقة)";
    echo json_encode(['status' => 'success', 'message' => $msg]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات']);
}
?>