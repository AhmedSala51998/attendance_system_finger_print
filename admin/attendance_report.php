<?php 
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}

$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval(date('Y'));
$selected_emp   = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

$arabic_months = [1=>'يناير', 2=>'فبراير', 3=>'مارس', 4=>'أبريل', 5=>'مايو', 6=>'يونيو', 7=>'يوليو', 8=>'أغسطس', 9=>'سبتمبر', 10=>'أكتوبر', 11=>'نوفمبر', 12=>'ديسمبر'];

// جلب الموظفين للفلاتر
$all_emps = $conn->query("SELECT id, name FROM employees WHERE role='employee' ORDER BY name ASC");

// بناء شروط البحث
$where_stats = " WHERE e.role = 'employee' ";
$where_detail = " WHERE MONTH(a.date) = '$selected_month' AND YEAR(a.date) = '$selected_year' ";

if($selected_emp > 0){
    $where_stats .= " AND e.id = $selected_emp ";
    $where_detail .= " AND e.id = $selected_emp ";
}

// استعلام الإحصائيات (الكروت)
$stats_query = "
    SELECT 
        e.id, e.name,
        COUNT(CASE WHEN a.status='present' THEN 1 END) AS present_days,
        COUNT(CASE WHEN a.status='late'    THEN 1 END) AS late_days,
        COUNT(CASE WHEN a.status='absent'  THEN 1 END) AS absent_days
    FROM employees e
    LEFT JOIN attendance a 
        ON a.employee_id = e.id 
        AND MONTH(a.date) = '$selected_month' 
        AND YEAR(a.date)  = '$selected_year'
    $where_stats
    GROUP BY e.id, e.name
    ORDER BY e.name ASC
";
$stats_result = $conn->query($stats_query);

include "../layout/header.php"; 
?>

<style>
    /* 🖨️ تصميم الوثيقة عند الطباعة (PDF) فقط */
    @media print {
        @page { size: landscape; margin: 15mm; }
        header, nav, .sidebar, .no-print, .btn, form, footer, .stats-container, .btn-outline { display: none !important; }
        
        body { background: white !important; font-family: 'Cairo', sans-serif !important; color: #000 !important; padding: 0; margin: 0; direction: rtl; }
        
        .dashboard { margin: 0 !important; padding: 0 !important; width: 100% !important; box-shadow: none !important; border:none !important; }
        .table-wrapper { border: none !important; box-shadow: none !important; overflow: visible !important; width: 100% !important; }
        
        table { width: 100% !important; border-collapse: collapse !important; border: 1px solid #000 !important; table-layout: fixed !important; margin-top: 10px; min-width: 0 !important; }
        
        /* توزيع المساحة بدقة على الـ 9 أعمدة لضمان ظهور 'صافي العمل' */
        th:nth-child(1), td:nth-child(1) { width: 15%; } /* الموظف */
        th:nth-child(2), td:nth-child(2) { width: 12%; } /* التاريخ */
        th:nth-child(3), td:nth-child(3) { width: 10%; } /* الحضور */
        th:nth-child(4), td:nth-child(4) { width: 10%; } /* الانصراف */
        th:nth-child(5), td:nth-child(5) { width: 10%; } /* الحالة */
        th:nth-child(6), td:nth-child(6) { width: 8%; }  /* البريك */
        th:nth-child(7), td:nth-child(7) { width: 10%; } /* تأخير بريك */
        th:nth-child(8), td:nth-child(8) { width: 10%; } /* انقطاع */
        th:nth-child(9), td:nth-child(9) { width: 15%; } /* صافي العمل */
        
        th { background: #f8fafc !important; color: #000 !important; border: 1px solid #000 !important; padding: 6px !important; font-size: 9pt !important; font-weight: 800 !important; }
        td { border: 1px solid #000 !important; padding: 4px !important; font-size: 8pt !important; text-align: center !important; }
        
        tr { page-break-inside: avoid; } /* منع انقسام الصف الواحد بين صفحتين */
        
        .print-header { display: block !important; text-align: center; margin-bottom: 30px; border-bottom: 3px double #4F46E5; padding-bottom: 10px; }
        
        /* تحويل الفوتر ليكون أسفل الجدول مباشرة بدون تداخل */
        .print-footer { display: block !important; margin-top: 30px; padding-top: 10px; border-top: 1px solid #000; font-size: 9pt; width: 100%; position: relative; clear: both; }
        
        span[style*="background"] { background: none !important; padding: 0 !important; border: none !important; color: #000 !important; font-weight: bold !important; font-size: 9pt !important; }
        
        /* تمييز الصفوف لتسهيل القراءة في الورق */
        tr:nth-child(even) { background-color: #fcfcfc !important; }
    }

    /* إخفاء عناصر الطباعة في العرض العادي */
    .print-header, .print-footer { display: none; }
    .late-card-trigger:hover { transform: translateY(-3px); background: #fef3c7 !important; border: 1px solid #f59e0b !important; cursor: pointer; }
</style>

<div class="dashboard" style="margin: 30px auto; max-width: 1400px;">

    <!-- 📄 ترويسة الطباعة (تظهر في PDF فقط) -->
    <div class="print-header">
        <h1 style="margin:0; color:#4F46E5;">تقرير الحضور والانصراف الرسمي</h1>
        <p style="margin:5px 0; color:#64748b;">سجل الموظفين للفترة المحددة</p>
    </div>
    
    <!-- Title & Navigation -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;" class="no-print">
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="index" style="width:40px; height:40px; background:white; border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--primary); box-shadow:0 4px 6px rgba(0,0,0,0.05); text-decoration:none; transition:0.2s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='var(--primary)';">
                <i class="fas fa-arrow-right"></i>
            </a>
            <h2 style="margin:0;"><i class="fas fa-file-invoice" style="color:var(--primary)"></i> التقارير والرقابة التفصيلية</h2>
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
             <form method="GET" style="display:flex; gap:8px; background:white; padding:8px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
                <select name="employee_id" onchange="this.form.submit()" style="padding:4px 10px; border-radius:8px; border:1px solid #e2e8f0; font-family:'Cairo';">
                    <option value="0">--- الموظفين ---</option>
                    <?php while($e = $all_emps->fetch_assoc()): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo ($e['id'] == $selected_emp) ? 'selected' : ''; ?>><?php echo $e['name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="month" onchange="this.form.submit()" style="padding:4px 10px; border-radius:8px; border:1px solid #e2e8f0; font-family:'Cairo';">
                    <?php foreach($arabic_months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo ($num == $selected_month) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" onchange="this.form.submit()" style="padding:4px 10px; border-radius:8px; border:1px solid #e2e8f0; font-family:'Cairo';">
                    <?php for($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <a href="export_excel?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>&employee_id=<?php echo $selected_emp; ?>" class="btn" style="background:#10B981; width:auto; padding:8px 15px;">Excel</a>
            <button onclick="window.print()" class="btn" style="background:#EF4444; width:auto; padding:8px 15px;">PDF</button>
        </div>
    </div>

    <!-- Stats Cards (no-print) -->
    <div class="stats-container no-print" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px; margin-bottom:30px;">
        <?php if($stats_result->num_rows > 0): ?>
            <?php while($s = $stats_result->fetch_assoc()): ?>
                <?php 
                // جلب تفاصيل التأخير لكل الموظفين لضمه للكارت
                $days_sql = "SELECT date, late_minutes FROM attendance WHERE employee_id={$s['id']} AND status='late' AND MONTH(date)='$selected_month' AND YEAR(date)='$selected_year' ORDER BY date ASC";
                $days_res = $conn->query($days_sql);
                $late_days_json = [];
                while($d = $days_res->fetch_assoc()){ $late_days_json[] = $d; }
                $json_data = json_encode($late_days_json);
                ?>
                <div class="glass-panel" style="padding:15px; border-radius:20px; border-top: 3px solid var(--primary);">
                    <h4 style="margin:0 0 10px 0; font-size:15px;"><?php echo htmlspecialchars($s['name']); ?></h4>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px;">
                        <div style="background:#f0fdf4; padding:8px; border-radius:10px; text-align:center;">
                            <div style="font-weight:bold; color:#16a34a;"><?php echo $s['present_days']; ?></div>
                            <div style="font-size:9px;">منتظم</div>
                        </div>
                        <!-- كارت التأخير التفاعلي -->
                        <div onclick='showLateDetails(<?php echo $json_data; ?>, "<?php echo $s['name']; ?>")' 
                             class="late-card-trigger" style="background:#fffbeb; padding:8px; border-radius:10px; text-align:center; transition:0.2s; border:1px solid transparent;">
                            <div style="font-weight:bold; color:#d97706;"><?php echo $s['late_days']; ?></div>
                            <div style="font-size:9px;">تأخير <i class="fas fa-external-link-alt" style="font-size:7px;"></i></div>
                        </div>
                        <div style="background:#fef2f2; padding:8px; border-radius:10px; text-align:center;">
                            <div style="font-weight:bold; color:#ef4444;"><?php echo $s['absent_days']; ?></div>
                            <div style="font-size:9px;">غياب</div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Details Table -->
    <div class="table-wrapper" style="background:white; border-radius:15px; overflow-x:auto; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
        <table style="width:100%; border-collapse:collapse; min-width:1200px;">
            <thead style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                <tr>
                    <th style="padding:12px; text-align:right;">الموظف</th>
                    <th style="padding:12px; text-align:center;">التاريخ</th>
                    <th style="padding:12px; text-align:center;">وقت الحضور</th>
                    <th style="padding:12px; text-align:center;">وقت الانصراف</th>
                    <th style="padding:12px; text-align:center;">الحالة</th>
                    <th style="padding:12px; text-align:center;">البريك (د)</th>
                    <th style="padding:12px; text-align:center;">تأخير البريك</th>
                    <th style="padding:12px; text-align:center;">الانقطاع</th>
                    <th style="padding:12px; text-align:center;">صافي العمل</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $detail_res = $conn->query("
                    SELECT a.*, e.name 
                    FROM attendance a 
                    JOIN employees e ON a.employee_id = e.id 
                    $where_detail
                    ORDER BY a.date DESC
                ");
                while($row = $detail_res->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #f8fafc;">
                    <td style="padding:12px;"><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td style="padding:12px; text-align:center;"><?php echo $row['date']; ?></td>
                    <td style="padding:12px; text-align:center; font-weight:bold;"><?php echo date('h:i A', strtotime($row['check_in'])); ?></td>
                    <td style="padding:12px; text-align:center; font-weight:bold;"><?php echo ($row['check_out']) ? date('h:i A', strtotime($row['check_out'])) : '—'; ?></td>
                    <td style="padding:12px; text-align:center;">
                        <?php 
                            if($row['status'] == 'present') echo '<span style="color:#16a34a; background:#f0fdf4; padding:3px 8px; border-radius:8px; font-size:11px; font-weight:bold;">منتظم</span>';
                            elseif($row['status'] == 'late') echo '<span style="color:#d97706; background:#fffbeb; padding:3px 8px; border-radius:8px; font-size:11px; font-weight:bold;">متأخر ('.$row['late_minutes'].' د)</span>';
                            else echo '<span style="color:#ef4444; background:#fef2f2; padding:3px 8px; border-radius:8px; font-size:11px; font-weight:bold;">غائب</span>';
                        ?>
                    </td>
                    <td style="padding:12px; text-align:center;"><?php echo $row['break_minutes'] ?? 0; ?> د</td>
                    <td style="padding:12px; text-align:center; color:#ef4444; font-weight:600;"><?php echo ($row['late_break_minutes'] > 0) ? $row['late_break_minutes'] . ' د' : '—'; ?></td>
                    <td style="padding:12px; text-align:center; color:#ef4444;"><?php echo ($row['interrupted_minutes'] > 0) ? $row['interrupted_minutes'] . ' د' : '—'; ?></td>
                    <td style="padding:12px; text-align:center;"><span style="font-weight:bold; color:var(--primary);"><?php echo formatWorkHours($row['work_hours']); ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <!-- 📄 تذييل الطباعة (يظهر في PDF فقط) -->
    <div class="print-footer">
        <span>طُبع بتاريخ: <?php echo date('Y-m-d H:i'); ?></span>
        <span style="float:left;">النظام الذكي لإدارة الحضور والانصراف - نسخة الإدارة</span>
    </div>
</div>

<script>
function showLateDetails(data, name) {
    if (data.length === 0) {
        Swal.fire({ title: 'سجل نظيف!', text: `الموظف ${name} لم يتأخر في أي يوم خلال هذا الشهر.`, icon: 'success' });
        return;
    }
    let html = `<div style="max-height:300px; overflow-y:auto; text-align:right; direction:rtl;">
        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <thead style="background:#f8fafc;">
                <tr><th style="padding:10px; border-bottom:1px solid #e2e8f0;">التاريخ</th><th style="padding:10px; border-bottom:1px solid #e2e8f0;">مدة التأخير</th></tr>
            </thead>
            <tbody>`;
    data.forEach(day => {
        html += `<tr>
            <td style="padding:10px; border-bottom:1px solid #f1f5f9;">${day.date}</td>
            <td style="padding:10px; border-bottom:1px solid #f1f5f9; color:#d97706; font-weight:bold;">${day.late_minutes} دقيقة</td>
        </tr>`;
    });
    html += `</tbody></table></div>`;
    Swal.fire({ title: `أيام تأخير: ${name}`, html: html, confirmButtonText: 'إغلاق' });
}
</script>

<?php include "../layout/footer.php"; ?>