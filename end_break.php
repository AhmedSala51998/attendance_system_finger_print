<?php
include "config.php";

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مسجل دخول']); exit();
}

$emp_id = $_SESSION['employee_id'];
$today = date('Y-m-d');

// البحث عن سجل اليوم المفتوح والبريك الذي لم ينتهِ
$query = "SELECT * FROM attendance WHERE employee_id = $emp_id AND date = '$today' AND break_start IS NOT NULL AND break_end IS NULL";
$res = $conn->query($query);
$row = $res->fetch_assoc();

if ($row) {
    $now = new DateTime();
    $start_time = new DateTime($row['break_start']);
    
    // 1. حساب الدقائق الفعلية للبريك
    $interval = $start_time->diff($now);
    $minutes_spent = ($interval->h * 60) + $interval->i;
    
    // 2. حساب التأخير بناءً على الساعة 5:00 مساءً (17:00)
    // أي دقيقة بعد الخامسة تعتبر تأخير رسمي
    // 💡 استخدام الشيفت
    $shift = getShiftDetails($conn, $emp_id, $today);

    $break_end_time = new DateTime(date('Y-m-d ' . $shift['break_end']));
    
    $late_minutes = 0;

    if ($now > $break_end_time) {
        $late_diff = $break_end_time->diff($now);
        $late_minutes = ($late_diff->h * 60) + $late_diff->i;
    }

    $sql = "UPDATE attendance SET 
                break_end = NOW(), 
                break_minutes = $minutes_spent, 
                late_break_minutes = $late_minutes 
            WHERE id = " . $row['id'];

    if ($conn->query($sql)) {
        $msg = "تمت العودة من الاستراحة بنجاح.";
        if($late_minutes > 0) {
            $msg .= " (لقد تجاوزت موعد الاستراحة الرسمي بـ $late_minutes دقيقة).";
        }
        
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'لا توجد فترة استراحة مفتوحة حالياً.']);
}
?>
