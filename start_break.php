<?php
include "config.php";

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مسجل دخول']); exit();
}

$emp_id = $_SESSION['employee_id'];
$today = date('Y-m-d');

// البحث عن سجل اليوم المفتوح
$query = "SELECT * FROM attendance WHERE employee_id = $emp_id AND date = '$today' AND check_out IS NULL";
$res = $conn->query($query);
$row = $res->fetch_assoc();

if ($row) {
    // التحقق مسبقاً إذا كان خرج بريك ورجع منه (لا يسمح بأكثر من بريك واحد)
    if($row['break_end'] !== NULL) {
        echo json_encode(['status' => 'error', 'message' => 'لقد استهلكت فترة الاستراحة الخاصة بك اليوم بالفعل.']);
        exit();
    }

    // 💡 استخدام الشيفت
    $shift = getShiftDetails($conn, $emp_id, $today);

    // منع البريك لو مفيش بريك أصلاً
    if(!$shift['has_break']){
        echo json_encode(['status' => 'error', 'message' => 'لا يوجد استراحة في هذا الشيفت']);
        exit();
    }

    // تحديد وقت البريك حسب الشيفت
    $now = new DateTime();
    $break_start = new DateTime(date('Y-m-d ' . $shift['break_start']));
    $break_end   = new DateTime(date('Y-m-d ' . $shift['break_end']));

    // ❌ منع الخروج برا وقت البريك
    if($now < $break_start || $now > $break_end){
        echo json_encode([
            'status' => 'error',
            'message' => 'غير مسموح بالاستراحة الآن. وقت البريك: ' 
                . date('h:i A', strtotime($shift['break_start'])) 
                . ' - ' 
                . date('h:i A', strtotime($shift['break_end']))
        ]);
        exit();
    }

    $sql = "UPDATE attendance SET break_start = NOW() WHERE id = " . $row['id'];
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'أنت الآن في فترة استراحة. تم تعطيل زر الانصراف مؤقتاً.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'يجب تسجيل الحضور أولاً قبل الخروج لاستراحة.']);
}
?>
