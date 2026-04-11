<?php
include "../config.php";

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    exit("Access Denied");
}

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year  = isset($_GET['year'])  ? intval($_GET['year'])  : date('Y');
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

$filename = "attendance_report_{$month}_{$year}.xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$where = " WHERE MONTH(a.date) = '$month' AND YEAR(a.date) = '$year' ";
if($employee_id > 0) $where .= " AND a.employee_id = $employee_id ";

$query = "
    SELECT a.*, e.name 
    FROM attendance a 
    JOIN employees e ON a.employee_id = e.id 
    $where
    ORDER BY a.date DESC
";
$result = $conn->query($query);

// تصحيح صياغة Excel للعرض باللغة العربية
echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>

<table border="1">
    <thead style="background-color: #4F46E5; color: white;">
        <tr>
            <th>الموظف</th>
            <th>التاريخ</th>
            <th>الحالة</th>
            <th>وقت الحضور</th>
            <th>وقت الانصراف</th>
            <th>تأخير صباحي (د)</th>
            <th>بريك مستهلك (د)</th>
            <th>تأخير البريك (د)</th>
            <th>انقطاع الموقع (د)</th>
            <th>صافي ساعات العمل</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['date']; ?></td>
                <td>
                    <?php 
                        if($row['status'] == 'present') echo 'منتظم';
                        elseif($row['status'] == 'late') echo 'متأخر';
                        else echo 'غائب';
                    ?>
                </td>
                <td><?php echo date('h:i A', strtotime($row['check_in'])); ?></td>
                <td><?php echo ($row['check_out']) ? date('h:i A', strtotime($row['check_out'])) : '—'; ?></td>
                <td><?php echo $row['late_minutes']; ?></td>
                <td><?php echo $row['break_minutes']; ?></td>
                <td><?php echo $row['late_break_minutes']; ?></td>
                <td><?php echo $row['interrupted_minutes']; ?></td>
                <td>
                    <span style="font-weight:bold; color:#4F46E5;">
                        <?php echo formatWorkHours($row['work_hours']); ?>
                    </span>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
