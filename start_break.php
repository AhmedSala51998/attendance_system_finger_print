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
