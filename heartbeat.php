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

$actual_interruption = 0;

/*
========================
1. بدأ AFK
========================
*/
if($status == 'afk' && $last_status != 'afk'){

    $stmt = $conn->prepare("
        UPDATE attendance 
        SET afk_start=? 
        WHERE id=?
    ");
    $stmt->bind_param("si", $now_str, $row['id']);
    $stmt->execute();
}

/*
========================
2. رجع من AFK
========================
*/
if($status != 'afk' && $last_status == 'afk' && $afk_start){

    $afk_start_dt = new DateTime($afk_start);
    $minutes = floor(($now->getTimestamp() - $afk_start_dt->getTimestamp()) / 60);

    // خصم وقت البريك
    $break_start = new DateTime(date('Y-m-d 16:00:00'));
    $break_end   = new DateTime(date('Y-m-d 17:00:00'));

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