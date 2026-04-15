<?php
include "config.php";

$input = json_decode(file_get_contents('php://input'), true);

$emp_id = intval($input['employee_id'] ?? 0);
$status = $input['status'] ?? 'not-afk';
$timestamp = $input['timestamp'] ?? null;

if(!$emp_id || !$timestamp) exit();

$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE employee_id=? AND date=? AND check_out IS NULL
    LIMIT 1
");
$stmt->bind_param("is", $emp_id, $today);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0) exit();

$row = $res->fetch_assoc();

$now = new DateTime($timestamp);
$now_str = $now->format('Y-m-d H:i:s');

$last_status = $row['last_status'] ?? 'not-afk';
$afk_start = $row['afk_start'] ?? null;

// نحدد بداية الشيفت من check_in
$shift_start = new DateTime($row['check_in'] ?? $now_str);

$actual_interruption = 0;

/*
========================
1. بدأ AFK
========================
*/
if($status == 'afk' && $last_status != 'afk'){
    // إذا AFK قبل بداية الشيفت، نعتبره من بداية الشيفت
    if($now < $shift_start) $now = clone $shift_start;

    $stmt = $conn->prepare("
        UPDATE attendance 
        SET afk_start=? 
        WHERE id=?
    ");
    $stmt->bind_param("si", $now_str, $row['id']);
    $stmt->execute();
}

$shift = getShiftDetails($conn, $emp_id, $today);
/*
========================
2. رجع من AFK
========================
*/
if($status != 'afk' && $last_status == 'afk' && $afk_start){

    $afk_start_dt = new DateTime($afk_start);

    // لا نحسب AFK قبل بداية الشيفت
    if($afk_start_dt < $shift_start) $afk_start_dt = clone $shift_start;

    $minutes = floor(($now->getTimestamp() - $afk_start_dt->getTimestamp()) / 60);

    // تجاهل أي AFK أقل من 15 دقيقة
    if($minutes >= 15){

        // خصم وقت البريك
        $break_start = new DateTime(date('Y-m-d ' . $shift['break_start']));
        $break_end   = new DateTime(date('Y-m-d ' . $shift['break_end']));

        $overlap_start = max($afk_start_dt->getTimestamp(), $break_start->getTimestamp());
        $overlap_end   = min($now->getTimestamp(), $break_end->getTimestamp());

        $overlap = 0;
        if($overlap_end > $overlap_start){
            $overlap = floor(($overlap_end - $overlap_start) / 60);
        }

        $actual_interruption = max(0, $minutes - $overlap);

        if($actual_interruption > 0){
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET interrupted_minutes = interrupted_minutes + ?
                WHERE id=?
            ");
            $stmt->bind_param("ii", $actual_interruption, $row['id']);
            $stmt->execute();
        }
    }
}

/*
========================
3. تحديث الحالة والـ heartbeat
========================
*/
$stmt = $conn->prepare("
    UPDATE attendance 
    SET last_status=?, last_heartbeat=?
    WHERE id=?
");
$stmt->bind_param("ssi", $status, $now_str, $row['id']);
$stmt->execute();
?>