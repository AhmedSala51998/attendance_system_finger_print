<?php 
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}
include "../layout/header.php"; 
?>

<div class="dashboard" style="margin: 40px auto; max-width: 1000px;">
    
    <div class="user-header" style="background: linear-gradient(135deg, var(--primary), #a855f7); color: white; padding: 40px; border-radius: 30px; box-shadow: 0 20px 40px rgba(79, 70, 229, 0.2); margin-bottom: 50px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;">
        <div style="z-index: 2;">
            <p style="margin: 0; font-size: 16px; opacity: 0.9;">أهلاً بك مجدداً،</p>
            <h1 style="margin: 5px 0 0 0; font-size: 32px;">المدير العام 👋</h1>
        </div>
        <div style="z-index: 2;">
            <a onclick="confirmLogout()" class="btn btn-logout_admin" style="width:auto; padding:10px 25px; font-size:15px;">
                <i class="fas fa-sign-out-alt"></i> تسجيل خروج
            </a>
        </div>
        <div style="position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
    </div>

    <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
        
        <!-- بطاقة الموظفين -->
        <a href="employees" class="action-card" style="background: white; padding: 35px 25px; border-radius: 25px; text-decoration: none; display: block; transition: all 0.4s ease; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
            <div style="width: 70px; height: 70px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px;">
                <i class="fas fa-users"></i>
            </div>
            <h3 style="color: #1e293b; font-size: 20px; margin: 0 0 10px;">إدارة الموظفين</h3>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">إضافة، تعديل وحذف بيانات الموظفين المسجلين في النظام</p>
        </a>

        <!-- بطاقة التقارير -->
        <a href="attendance_report" class="action-card" style="background: white; padding: 35px 25px; border-radius: 25px; text-decoration: none; display: block; transition: all 0.4s ease; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
            <div style="width: 70px; height: 70px; background: rgba(168, 85, 247, 0.1); color: #a855f7; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px;">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 style="color: #1e293b; font-size: 20px; margin: 0 0 10px;">التقارير الإحصائية</h3>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">عرض وتحليل سجلات الحضور والانصراف بفلترة دقيقة</p>
        </a>

        <!-- بطاقة الإجازات -->
        <a href="holidays" class="action-card" style="background: white; padding: 35px 25px; border-radius: 25px; text-decoration: none; display: block; transition: all 0.4s ease; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
            <div style="width: 70px; height: 70px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px;">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <h3 style="color: #1e293b; font-size: 20px; margin: 0 0 10px;">إدارة الإجازات</h3>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">تحديد أيام العطلات الرسمية والاستثنائية لجميع الموظفين</p>
        </a>

        <!-- بطاقة الإعدادات -->
        <a href="settings" class="action-card" style="background: white; padding: 35px 25px; border-radius: 25px; text-decoration: none; display: block; transition: all 0.4s ease; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02);">
            <div style="width: 70px; height: 70px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px;">
                <i class="fas fa-cog"></i>
            </div>
            <h3 style="color: #1e293b; font-size: 20px; margin: 0 0 10px;">إعدادات النظام</h3>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">تغيير موقع الشركة الجغرافي والمسافات المسموحة</p>
        </a>

    </div>
</div>

<style>
    .action-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08) !important;
        border-color: var(--primary) !important;
    }
</style>

<?php include "../layout/footer.php"; ?>