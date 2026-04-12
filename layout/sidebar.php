<div class="sidebar-pro" id="sidebar">

    <!-- 🔷 Logo (بصمة احترافية) -->
    <div class="brand">
        <div class="logo">
            <i class="fas fa-fingerprint"></i>
        </div>
        <div class="brand-text">
            <h3>نظام الحضور</h3>
            <span>Smart Attendance</span>
        </div>
    </div>

    <!-- 🔹 القائمة -->
    <ul class="menu">

        <span class="menu-title">القائمة الرئيسية</span>

        <li>
            <a href="dashboard" class="active">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
                <div class="active-pill"></div>
            </a>
        </li>

        <?php if($_SESSION['role'] == 'admin'): ?>

            <li>
                <a href="employees">
                    <i class="fas fa-users"></i>
                    <span>الموظفين</span>
                </a>
            </li>

            <li>
                <a href="attendance_report">
                    <i class="fas fa-chart-line"></i>
                    <span>التقارير</span>
                </a>
            </li>

            <li>
                <a href="holidays">
                    <i class="fas fa-umbrella-beach"></i>
                    <span>الإجازات</span>
                </a>
            </li>

            <li>
                <a href="settings">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                </a>
            </li>

        <?php else: ?>

            <li>
                <a href="employee/my_attendance">
                    <i class="fas fa-calendar-alt"></i>
                    <span>سجلاتي</span>
                </a>
            </li>

            <li>
                <a href="employee/monthly_report">
                    <i class="fas fa-file-alt"></i>
                    <span>التقرير الشهري</span>
                </a>
            </li>

            <li>
                <a href="employee/profile">
                    <i class="fas fa-user"></i>
                    <span>ملفي الشخصي</span>
                </a>
            </li>

        <?php endif; ?>

    </ul>

    <!-- 🔻 المستخدم (Dropdown) -->
    <div class="sidebar-user" onclick="toggleUserMenu()">
        <div class="avatar">
            <?php echo strtoupper(substr($_SESSION['employee_name'],0,1)); ?>
        </div>

        <div class="user-info">
            <strong><?php echo $_SESSION['employee_name']; ?></strong>
            <span><?php echo $_SESSION['role'] == 'admin' ? 'مدير النظام' : 'موظف'; ?></span>
        </div>

        <i class="fas fa-chevron-up"></i>
    </div>

    <!-- 🔻 Dropdown -->
    <div class="user-dropdown" id="userDropdown">
        <a href="profile"><i class="fas fa-user"></i> الملف الشخصي</a>
        <a onclick="confirmLogout()" onclick="confirmLogout()" style="color:#ef4444;">
            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
        </a>
    </div>

</div>