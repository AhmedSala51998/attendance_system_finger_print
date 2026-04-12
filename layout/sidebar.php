<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>⏱️ Smart HR</h2>
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <ul class="sidebar-menu">
        <?php if($_SESSION['role'] == 'admin'): ?>
            
            <li><a href="dashboard" class="active">
                <i class="fas fa-home"></i> <span>الرئيسية</span>
            </a></li>

            <li><a href="employees">
                <i class="fas fa-users"></i> <span>الموظفين</span>
            </a></li>

            <li><a href="attendance_report">
                <i class="fas fa-chart-line"></i> <span>التقارير</span>
            </a></li>

            <li><a href="holidays">
                <i class="fas fa-umbrella-beach"></i> <span>الإجازات</span>
            </a></li>

            <li><a href="settings">
                <i class="fas fa-cog"></i> <span>الإعدادات</span>
            </a></li>

        <?php else: ?>

            <li><a href="index" class="active">
                <i class="fas fa-home"></i> <span>الرئيسية</span>
            </a></li>

            <li><a href="employee/my_attendance">
                <i class="fas fa-calendar-alt"></i> <span>سجلاتي</span>
            </a></li>

            <li><a href="employee/monthly_report">
                <i class="fas fa-file-invoice"></i> <span>التقرير</span>
            </a></li>

            <li><a href="employee/profile">
                <i class="fas fa-user"></i> <span>الملف الشخصي</span>
            </a></li>

        <?php endif; ?>

        <li class="logout">
            <a onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> <span>تسجيل الخروج</span>
            </a>
        </li>
    </ul>
</div>