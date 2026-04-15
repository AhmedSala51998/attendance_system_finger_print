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

        if(empty($row['check_in']) || $row['status'] == 'absent'){
            continue;
        }
        if ($row['break_start'] !== null && $row['break_end'] === null) {
            continue; // الموظف في بريك → لا يتم تسجيل خروج تلقائي
        }

        $shift = getShiftDetails($conn, $row['employee_id'], $row['date']);
        if($shift['is_holiday']) continue;

        $end_time = new DateTime($row['date'] . ' ' . $shift['end_time']);
        $auto_checkout_time = (clone $end_time)->modify('+1 hour');

        if($now >= $auto_checkout_time){

            //$check_in = new DateTime($row['check_in']);

            $shift_start = new DateTime($row['date'] . ' ' . $shift['start_time']);
            $actual_check_in = new DateTime($row['check_in']);

            $permission_minutes = 0;

            $perm = $conn->query("
                SELECT * FROM permissions 
                WHERE employee_id = {$row['employee_id']}
                AND date = '{$row['date']}'
                AND status = 'approved'
            ")->fetch_assoc();

            if($perm){
                $from = new DateTime($row['date'] . ' ' . $perm['from_time']);
                $to   = new DateTime($row['date'] . ' ' . $perm['to_time']);

                $diff_perm = $from->diff($to);
                $permission_minutes = ($diff_perm->h * 60) + $diff_perm->i;
            }

            $grace_minutes = 15;

            // لو داخل في فترة السماحية → نحسبه من بداية الشيفت
           $has_permission = false;

            if($perm){
                $perm_from = new DateTime($row['date'] . ' ' . $perm['from_time']);
                $perm_to   = new DateTime($row['date'] . ' ' . $perm['to_time']);

                if($actual_check_in >= $perm_from && $actual_check_in <= $perm_to){
                    $has_permission = true;
                }
            }

            // 🔥 المنطق النهائي
            if (
                $actual_check_in <= (clone $shift_start)->modify("+{$grace_minutes} minutes")
                || $has_permission
            ) {
                $check_in = $shift_start;
            } else {
                $check_in = $actual_check_in;
            }

            $calc_end = $end_time;
            if($now < $end_time){
                continue;
            }

            $diff = $check_in->diff($calc_end);
            $minutes = ($diff->h * 60) + $diff->i;

            $actual = $minutes
                - intval($row['break_minutes'] ?? 0)
                - intval($row['interrupted_minutes'] ?? 0)
                - intval($row['late_break_minutes'] ?? 0) + $permission_minutes;

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