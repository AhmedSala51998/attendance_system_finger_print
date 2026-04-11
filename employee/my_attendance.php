<?php 
include "../config.php"; 
if(!isset($_SESSION['employee_id'])){ header("Location: ../login.php"); exit(); }
if($_SESSION['role'] == 'admin'){ header("Location: ../admin/index.php"); exit(); }

$id = $_SESSION['employee_id'];
include "../layout/header.php"; 
?>
<style>
    /* 📱 Mobile Attendance Table */
@media (max-width: 768px) {

    .dashboard {
        margin: 15px !important;
        max-width: 100% !important;
    }

    /* 🔷 العنوان */
    .dashboard h2 {
        font-size: 18px;
        text-align: center;
    }

    /* 📊 اخفاء الجدول وتحويله لكروت */
    table {
        border: 0;
    }

    table thead {
        display: none;
    }

    table tr {
        display: block;
        margin-bottom: 15px;
        background: #fff;
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    table td {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border: none !important;
        font-size: 14px;
    }

    table td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #6b7280;
    }

    /* 🔘 زر الرجوع */
    .btn {
        width: 100% !important;
        text-align: center;
    }

    .table-wrapper {
        overflow: visible !important;
    }
}
</style>
<div class="dashboard" style="margin: 40px auto; max-width: 1000px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin:0;"><i class="fas fa-list-alt" style="color:var(--primary)"></i> سجل حضوري</h2>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>وقت الحضور</th>
                    <th>وقت الانصراف</th>
                    <th>تأخير (دقيقة)</th>
                    <th>ساعات العمل</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM attendance WHERE employee_id=$id ORDER BY date DESC, check_in DESC");
                if($res->num_rows > 0) {
                    while($row = $res->fetch_assoc()){
                        // Status Badge
                        $status_badge = "<span class='badge' style='background: #E0E7FF; color:var(--primary); padding: 5px 10px; border-radius: 20px; font-size:14px;'><i class='fas fa-check'></i> منتظم</span>";
                        if($row['status'] == 'late') {
                            $status_badge = "<span class='badge' style='background: rgba(239, 68, 68, 0.1); color:var(--danger); padding: 5px 10px; border-radius: 20px; font-size:14px;'><i class='fas fa-exclamation-triangle'></i> متأخر</span>";
                        } elseif ($row['status'] == 'absent') {
                            $status_badge = "<span class='badge' style='background: #FEE2E2; color:var(--danger); padding: 5px 10px; border-radius: 20px; font-size:14px;'><i class='fas fa-times-circle'></i> غائب</span>";
                        }

                        $check_out = $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : "<span style='color:#9CA3AF'>لم ينصرف</span>";
                        $work_hours = $row['work_hours'] ? formatWorkHours($row['work_hours']) : '-';
                        $late_minutes = $row['late_minutes'] > 0 ? "<span style='color:var(--danger); font-weight:bold;'>{$row['late_minutes']}</span>" : "0";

                        // If absent, clear out times
                        if ($row['status'] == 'absent') {
                            $row['check_in'] = '-';
                            $check_out = '-';
                            $late_minutes = '-';
                            $work_hours = '-';
                        }

                        echo "<tr>
                            <td data-label='التاريخ'><strong>{$row['date']}</strong></td>
                            <td data-label='الحضور'>" . ($row['check_in'] != '-' ? date('h:i A', strtotime($row['check_in'])) : '-') . "</td>
                            <td data-label='الانصراف'>{$check_out}</td>
                            <td data-label='التأخير'>{$late_minutes}</td>
                            <td data-label='ساعات العمل'>{$work_hours}</td>
                            <td data-label='الحالة'>{$status_badge}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 20px; color:#6B7280;'>لا يوجد سجلات حضور حتى الآن</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <a href="../dashboard.php" class="btn btn-outline" style="width: auto; padding: 10px 20px; display: inline-block;">
            <i class="fas fa-arrow-right"></i> عودة للوحة التحكم
        </a>
    </div>

</div>
<?php include '../layout/footer.php'; ?>
