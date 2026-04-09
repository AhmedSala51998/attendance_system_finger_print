<?php
include "config.php";

$input = json_decode(file_get_contents('php://input'), true);
$emp_id = isset($input['employee_id']) ? intval($input['employee_id']) : 0;
if(!$emp_id) exit();

$emp_id = intval($emp_id);
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

$now = new DateTime();
$check_in_time = new DateTime($row['check_in']);

$last_hb = !empty($row['last_heartbeat']) ? new DateTime($row['last_heartbeat']) : null;

$start_ts = $last_hb 
    ? max($last_hb->getTimestamp(), $check_in_time->getTimestamp())
    : $check_in_time->getTimestamp();

// استلام idleMinutes من JS
$idleMinutes = isset($input['idleMinutes']) ? intval($input['idleMinutes']) : 0;

$actual_interruption = 0;

if($last_hb){
    $diff = $now->getTimestamp() - $last_hb->getTimestamp();

    // حساب الفجوة بالدقائق
    $gap_minutes = floor(($now->getTimestamp() - $start_ts) / 60);

    // الانقطاع الطبيعي > دقيقتين (120 ثانية)
    if($diff > 120){
        $break_start = new DateTime(date('Y-m-d 16:00:00'));
        $break_end   = new DateTime(date('Y-m-d 17:00:00'));
        $overlap_start = max($start_ts, $break_start->getTimestamp());
        $overlap_end   = min($now->getTimestamp(), $break_end->getTimestamp());
        $overlap_minutes = 0;
        if($overlap_end > $overlap_start){
            $overlap_minutes = floor(($overlap_end - $overlap_start) / 60);
        }

        $actual_interruption += max(0, $gap_minutes - $overlap_minutes);
    }

    // ✅ إضافة inactivity ≥ 15 دقيقة
    // نستخدم idleMinutes المرسل من JS الذي يحسب الفجوة بشكل أدق
    if($idleMinutes >= 15){
        // نتأكد أن idleMinutes لا يزيد عن الفجوة الفعلية
        $effective_idle = min($idleMinutes, $gap_minutes);

        $break_start = new DateTime(date('Y-m-d 16:00:00'));
        $break_end   = new DateTime(date('Y-m-d 17:00:00'));

        $idle_start = clone $now;
        $idle_start->modify("-{$effective_idle} minutes");

        if($idle_start->getTimestamp() < $start_ts){
            $idle_start = (new DateTime())->setTimestamp($start_ts);
        }

        $overlap_start = max($idle_start->getTimestamp(), $break_start->getTimestamp());
        $overlap_end   = min($now->getTimestamp(), $break_end->getTimestamp());

        $overlap_minutes = 0;
        if($overlap_end > $overlap_start){
            $overlap_minutes = floor(($overlap_end - $overlap_start) / 60);
        }

        $actual_idle = max(0, floor(($now->getTimestamp() - $idle_start->getTimestamp()) / 60) - $overlap_minutes);

        $actual_interruption = max($actual_interruption, $actual_idle);
        
    }

    if($actual_interruption > 0){
        $stmt2 = $conn->prepare("
            UPDATE attendance 
            SET interrupted_minutes = interrupted_minutes + ?
            WHERE id=?
        ");
        $stmt2->bind_param("ii", $actual_interruption, $row['id']);
        $stmt2->execute();
    }
}

// تحديث آخر heartbeat
$stmt3 = $conn->prepare("UPDATE attendance SET last_heartbeat = NOW() WHERE id=?");
$stmt3->bind_param("i", $row['id']);
$stmt3->execute();



//Auto Check Out Shift
// AUTO CHECKOUT SYSTEM
if(!defined('AUTO_CHECKOUT_RAN')){
    define('AUTO_CHECKOUT_RAN', true);

    $now = new DateTime();

    $res = $conn->query("
        SELECT * FROM attendance 
        WHERE check_out IS NULL
    ");

    while($row = $res->fetch_assoc()){

        $shift = getShiftDetails($conn, $row['employee_id'], $row['date']);
        if($shift['is_holiday']) continue;

        $end_time = new DateTime($row['date'] . ' ' . $shift['end_time']);
        $auto_checkout_time = (clone $end_time)->modify('+1 hour');

        if($now >= $auto_checkout_time){

            $check_in = new DateTime($row['check_in']);
            $calc_end = $end_time;

            $diff = $check_in->diff($calc_end);
            $minutes = ($diff->h * 60) + $diff->i;

            $actual = $minutes
                - intval($row['break_minutes'] ?? 0)
                - intval($row['interrupted_minutes'] ?? 0)
                - intval($row['late_break_minutes'] ?? 0);

            $hours = round(max(0, $actual / 60), 2);

            $conn->query("
                UPDATE attendance 
                SET check_out = '".$end_time->format('Y-m-d H:i:s')."',
                    work_hours = $hours
                WHERE id = ".$row['id']."
            ");
        }
    }
}
?>