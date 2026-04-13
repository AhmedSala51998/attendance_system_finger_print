<?php
include "config.php";

//Auto Check Out Shift
// AUTO CHECKOUT SYSTEM
if(!defined('AUTO_CHECKOUT_RAN')){
    define('AUTO_CHECKOUT_RAN', true);

    $now = new DateTime();

    $res = $conn->query("
        SELECT * FROM attendance 
        WHERE check_out IS NULL
        AND check_in IS NOT NULL
        AND status != 'absent'
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