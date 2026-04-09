<?php
$conn = new mysqli("localhost","u552468652_attendance_sys","Attendance_sys12345","u552468652_attendance_sys");

if($conn->connect_error){
    die("DB Error");
}

// توحيد المنطقة الزمنية السعودية (PHP + MySQL)
$conn->query("SET time_zone = '+03:00'");
date_default_timezone_set("Asia/Riyadh");
session_start();

// 💡 دالة تحويل الساعات العشرية (7.85) إلى صيغة مفهومة (7 س 51 د)
function formatWorkHours($decimalHours) {
    if (!$decimalHours || floatval($decimalHours) <= 0) return "—";
    $h = floor($decimalHours);
    $m = round(($decimalHours - $h) * 60);
    $res = "";
    if ($h > 0) $res .= $h . " س ";
    if ($m > 0) $res .= $m . " د ";
    return $res ? trim($res) : "—";
}

// ==========================================================
// 💡 نظام "اللحاق التلقائي بالغياب" (Absence Catch-up System)
// يتحقق النظام من تسجيل الغياب للأيام الفائتة فور زيارة أي شخص للموقع
// ==========================================================
$today      = date("Y-m-d");
$now_h_m    = date("H:i");
$cron_res   = $conn->query("SELECT last_cron_run FROM settings WHERE id=1");
$setting    = $cron_res->fetch_assoc();
$last_run   = $setting['last_cron_run'] ?? date("Y-m-d", strtotime("-1 day"));

// 1. تعويض الأيام الماضية (التي لم يُفتح فيها الموقع ليلاً)
if ($last_run < $today) {
    $current = date("Y-m-d", strtotime($last_run . " +1 day"));
    
    // نقوم بتشغيل الغياب لكل يوم فات حتى أمس (Yesterday)
    while ($current < $today) {
        $target_date = $current;
        include dirname(__FILE__) . '/mark_absent.php'; 
        $current = date("Y-m-d", strtotime($current . " +1 day"));
    }

    // 2. فحص هل حان موعد تسجيل غياب "اليوم" (الساعة 11:55 م أو بعدها)
    if ($now_h_m >= "23:55") {
        $target_date = $today;
        include dirname(__FILE__) . '/mark_absent.php';
    }
}

// ✅ المحرك المركزي للمواعيد والورديات (لتجنب الأخطاء)
function getShiftDetails($conn, $emp_id, $date = null) {
    if (!$date) $date = date('Y-m-d');
    $day_name = date('D', strtotime($date)); // Sun, Mon, etc.

    // 1. التحقق من يوم الجمعة (إجازة أساسية)
    if ($day_name == 'Fri') {
        return ['is_holiday' => true, 'reason' => 'إجازة نهاية الأسبوع (الجمعة)'];
    }

    // 2. التحقق من الإجازات الإدارية (Admin Holidays)
    $stmt = $conn->prepare("SELECT description FROM holidays WHERE date = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return ['is_holiday' => true, 'reason' => $row['description']];
    }

    // 3. جلب نوع شفت الموظف المعتاد
    $emp_res = $conn->query("SELECT shift FROM employees WHERE id = $emp_id");
    $emp = $emp_res->fetch_assoc();
    $shift_type = $emp['shift'] ?? 'shift1';

    // 4. تحديد المواعيد بناءً على اليوم
    if ($day_name == 'Sat') {
        // السبت: موعد موحد (5 م - 9 م) لجميع الموظفين وبدون بريك
        return [
            'is_holiday' => false,
            'start_time' => '17:00:00',
            'end_time'   => '21:00:00',
            'has_break'  => false,
            'display'    => '05:00 م - 09:00 م (دوام السبت الاستثنائي)'
        ];
    } else {
        // الأيام العادية (الأحد - الخميس)
        if ($shift_type == 'shift1') {
            return [
                'is_holiday' => false,
                'start_time' => '10:00:00',
                'end_time'   => '19:00:00',
                'has_break'  => true,
                'display'    => '10:00 ص - 07:00 م (الشيفت الأول)'
            ];
        } else {
            return [
                'is_holiday' => false,
                'start_time' => '12:00:00',
                'end_time'   => '21:00:00',
                'has_break'  => true,
                'display'    => '12:00 م - 09:00 م (الشيفت الثاني)'
            ];
        }
    }
}
?>