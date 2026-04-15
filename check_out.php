<?php
include "config.php";

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مسجل دخول']); exit();
}

$emp_id = $_SESSION['employee_id'];
$today = date('Y-m-d');

// 💡 استخدام المحرك المركزي للمواعيد
$shift = getShiftDetails($conn, $emp_id, $today);

// البحث عن سجل اليوم المفتوح
$query = "SELECT * FROM attendance WHERE employee_id = $emp_id AND date = '$today' AND check_out IS NULL";
$res = $conn->query($query);

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    
    // منع الانصراف أثناء البريك
    if($row['break_start'] !== NULL && $row['break_end'] === NULL) {
        echo json_encode(['status' => 'error', 'message' => "لا يمكنك تسجيل الانصراف وأنت في فترة استراحة. يرجى تسجيل العودة أولاً."]);
        exit();
    }

    // منع الانصراف قبل موعد نهاية الوردية لليوم المحدد (7 م أو 9 م)
    $now = new DateTime();
    $end_time = new DateTime(date('Y-m-d ' . $shift['end_time']));

    if ($now < $end_time) {
        $display_end = date('h:i A', strtotime($shift['end_time']));
        echo json_encode(['status' => 'error', 'message' => "لا يمكنك الانصراف قبل انتهاء شيفتك الرسمي (الساعة $display_end)"]);
        exit();
    }

    //$check_in_time = new DateTime($row['check_in']);


    $shift_start = new DateTime(date('Y-m-d ' . $shift['start_time']));
    $actual_check_in = new DateTime($row['check_in']);

    $perm = $conn->query("
        SELECT * FROM permissions 
        WHERE employee_id = $emp_id 
        AND date = '$today'
        AND status = 'approved'
    ")->fetch_assoc();
    
    $has_permission = false;

    if($perm){
        $perm_from = new DateTime($row['date'] . ' ' . $perm['from_time']);
        $perm_to   = new DateTime($row['date'] . ' ' . $perm['to_time']);

        if($actual_check_in >= $perm_from && $actual_check_in <= $perm_to){
            $has_permission = true;
        }
    }

    // 🔥 نفس منطق الأوتو تشيك
    if (
        $actual_check_in <= (clone $shift_start)->modify('+15 minutes')
        || $has_permission
    ) {
        $check_in_time = $shift_start;
    } else {
        $check_in_time = $actual_check_in;
    }

    // 💡 المنطق الجديد للنهاية: نأخذ الوقت الأقدم بين لحظة الانصراف الحالية ونهاية الوردية الرسمية
    // لضمان تجاهل أي وقت إضافي (Overtime) بعد الساعة 7 أو 9 مساءً
    $calc_end_time = ($now > $end_time) ? $end_time : $now;
    
    $diff = $check_in_time->diff($calc_end_time);
    $total_presence_minutes = ($diff->h * 60) + $diff->i;

    // خصم دقائق البريك الفعلية والتأخير والانقطاعات
    $break_deduction = intval($row['break_minutes'] ?? 0);
    $late_break_deduction = intval($row['late_break_minutes'] ?? 0);
    $interruption_deduction = intval($row['interrupted_minutes'] ?? 0);

    $permission_minutes = 0;

    if($perm){
        $from = new DateTime($perm['from_time']);
        $to   = new DateTime($perm['to_time']);

        $diff = $from->diff($to);
        $permission_minutes = ($diff->h * 60) + $diff->i;
    }

    $actual_work_minutes = $total_presence_minutes - $break_deduction - $interruption_deduction - $late_break_deduction + $permission_minutes;
    $work_hours = round(max(0, $actual_work_minutes / 60), 2);

    $sql = "UPDATE attendance SET check_out = NOW(), work_hours = $work_hours WHERE id = ".$row['id'];

    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => "تم تسجيل الانصراف بنجاح. العمل الفعلي: $work_hours ساعة."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'لا يوجد سجل حضور مفتوح']);
}
?>