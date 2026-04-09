<?php 
include "../config.php"; 
if(!isset($_SESSION['employee_id'])){ header("Location: ../login.php"); exit(); }
if($_SESSION['role'] == 'admin'){ header("Location: ../admin/index.php"); exit(); }

$id = $_SESSION['employee_id'];
$current_month = date('m');
$current_year = date('Y');

// Calculate Monthly Stats
$stats = $conn->query("SELECT 
    COUNT(CASE WHEN status='present' THEN 1 END) as regular_days,
    COUNT(CASE WHEN status='late' THEN 1 END) as late_days,
    COUNT(CASE WHEN status='absent' THEN 1 END) as absent_days,
    SUM(late_minutes) as total_late_minutes,
    SUM(work_hours) as total_work_hours
    FROM attendance 
    WHERE employee_id=$id AND MONTH(date)='$current_month' AND YEAR(date)='$current_year'")->fetch_assoc();

include "../layout/header.php"; 
?>

<div class="dashboard" style="margin: 40px auto; max-width: 800px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin:0;"><i class="fas fa-chart-pie" style="color:var(--primary)"></i> تقرير شهر <?php echo date('F Y'); ?></h2>
    </div>

    <div class="dashboard-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="action-card" style="cursor: default;">
            <i class="fas fa-calendar-check" style="color: var(--secondary)"></i>
            <h3>أيام الحضور (منتظم)</h3>
            <span style="font-size: 28px; font-weight: bold; color: var(--secondary);"><?php echo $stats['regular_days'] ?: 0; ?></span>
        </div>
        
        <div class="action-card" style="cursor: default;">
            <i class="fas fa-user-clock" style="color: #F59E0B"></i>
            <h3>أيام التأخير</h3>
            <span style="font-size: 28px; font-weight: bold; color: #F59E0B;"><?php echo $stats['late_days'] ?: 0; ?></span>
        </div>
        
        <div class="action-card" style="cursor: default;">
            <i class="fas fa-user-times" style="color: var(--danger)"></i>
            <h3>أيام الغياب</h3>
            <span style="font-size: 28px; font-weight: bold; color: var(--danger);"><?php echo $stats['absent_days'] ?: 0; ?></span>
        </div>
        
        <div class="action-card" style="cursor: default;">
            <i class="fas fa-hourglass-half" style="color: var(--primary)"></i>
            <h3>إجمالي ساعات العمل</h3>
            <span style="font-size: 24px; font-weight: bold; color: var(--primary);"><?php echo formatWorkHours($stats['total_work_hours']); ?></span>
        </div>
    </div>

    <div style="margin-top: 20px; background: rgba(239, 68, 68, 0.1); padding: 20px; border-radius: 15px; border: 1px solid rgba(239, 68, 68, 0.3); text-align: center;">
        <h3 style="color: var(--danger); margin:0 0 10px 0;"><i class="fas fa-exclamation-circle"></i> إجمالي دقائق التأخير للشهر الجاري</h3>
        <span style="font-size: 32px; font-weight: bold; color: var(--danger);"><?php echo $stats['total_late_minutes'] ?: 0; ?> دقيقة</span>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <a href="../dashboard.php" class="btn btn-outline" style="width: auto; padding: 10px 20px; display: inline-block;">
            <i class="fas fa-arrow-right"></i> عودة للوحة التحكم
        </a>
    </div>

</div>
<?php include '../layout/footer.php'; ?>
