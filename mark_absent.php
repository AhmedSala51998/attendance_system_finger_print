<?php
/**
 * سكربت تسجيل الغياب التلقائي - نسخة "اللحاق بالركب" المطورة
 */
set_time_limit(0);

// التحقق من وجود الاتصال بقاعدة البيانات
if (!isset($conn) || $conn->connect_error) {
    include_once dirname(__FILE__) . '/config.php';
}

// 💡 ميزة مرنة: إذا تم تمرير تاريخ معين ($target_date) من الـ config، نستخدمه
// وإلا، نعتبر التاريخ هو "اليوم"
if (!isset($target_date)) {
    $target_date = date("Y-m-d");
}

// 1. التحقق من يوم الجمعة أو الإجازات الرسمية قبل البدء لهذا التاريخ المحدد
$shift_check = getShiftDetails($conn, 0, $target_date); 
if ($shift_check['is_holiday']) {
    // تحديث تاريخ التشغيل في الإعدادات حتى لو كان إجازة، لكي لا يحاول السكربت العودة لهذا اليوم مرة أخرى
    $conn->query("UPDATE settings SET last_cron_run='$target_date' WHERE id=1");
    
    // تسجيل في الـ Log
    $log_dir = dirname(__FILE__) . '/cron_log.txt';
    $log_entry = "[" . date("Y-m-d H:i:s") . "] Skipping $target_date: " . $shift_check['reason'] . "\n";
    file_put_contents($log_dir, $log_entry, FILE_APPEND);
    return;
}

// 2. تحديث تاريخ التشغيل لهذا اليوم لمنع التكرار
// ملاحظة: سيتم تحديث التاريخ في الـ config بعد نهاية كل الحلقات، لكننا نسجله هنا للاحتياط
$conn->query("UPDATE settings SET last_cron_run='$target_date' WHERE id=1");

// 3. جلب الموظفين الذين لم يسجلوا أي سجل (حضور/انصراف/بريك) في هذا اليوم المحدد
$query = "SELECT id FROM employees 
          WHERE role = 'employee' 
          AND id NOT IN (SELECT employee_id FROM attendance WHERE date = '$target_date')";

$missing_emps = $conn->query($query);

$absent_count = 0;
if ($missing_emps && $missing_emps->num_rows > 0) {
    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'absent')");
    while ($e = $missing_emps->fetch_assoc()) {
        $id = $e['id'];
        $stmt->bind_param("is", $id, $target_date);
        if($stmt->execute()) $absent_count++;
    }
}

// 4. تسجيل النتيجة في الـ Log
$log_dir = dirname(__FILE__) . '/cron_log.txt';
$log_entry = "[" . date("Y-m-d H:i:s") . "] Cron for $target_date finished. Added: $absent_count absent records.\n";
file_put_contents($log_dir, $log_entry, FILE_APPEND);
?>