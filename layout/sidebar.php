<?php
function navActive($key, $current){
    $isActive = strpos($current, $key) !== false;

    return $isActive ? [
        'class' => 'active',
        'pill'  => '<div class="active-pill"></div>'
    ] : [
        'class' => '',
        'pill'  => ''
    ];
}

$current = basename($_SERVER['REQUEST_URI']);
?>
<div class="sidebar-pro" id="sidebar">

    <div class="brand">
        <div class="logo">
            <img src="/images/logo.png" alt="Logo">
        </div>
        <div class="brand-text">
            <h3>نظام الحضور</h3>
            <span>Smart Attendance</span>
        </div>
    </div>

    <!-- 🔹 القائمة -->
    <ul class="menu">

        <span class="menu-title">القائمة الرئيسية</span>

        <?php if($_SESSION['role'] == 'admin'): ?>

            <?php $nav = navActive('index', $current); ?>
            <li>
                <a href="/admin/index" class="<?= $nav['class']; ?>">
                    <i class="fas fa-home"></i>
                    <span>الرئيسية</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('employees', $current); ?>
            <li>
                <a href="/admin/employees" class="<?= $nav['class']; ?>">
                    <i class="fas fa-users"></i>
                    <span>الموظفين</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('attendance_report', $current); ?>
            <li>
                <a href="/admin/attendance_report" class="<?= $nav['class']; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>التقارير</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('holidays', $current); ?>
            <li>
                <a href="/admin/holidays" class="<?= $nav['class']; ?>">
                    <i class="fas fa-umbrella-beach"></i>
                    <span>الإجازات</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('settings', $current); ?>
            <li>
                <a href="/admin/settings" class="<?= $nav['class']; ?>">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

        <?php else: ?>

            <?php $nav = navActive('dashboard', $current); ?>
            <li>
                <a href="/dashboard" class="<?= $nav['class']; ?>">
                    <i class="fas fa-home"></i>
                    <span>الرئيسية</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('my_attendance', $current); ?>
            <li>
                <a href="/employee/my_attendance" class="<?= $nav['class']; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>سجلاتي</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('monthly_report', $current); ?>
            <li>
                <a href="/employee/monthly_report" class="<?= $nav['class']; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>التقرير الشهري</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

            <?php $nav = navActive('profile', $current); ?>
            <li>
                <a href="/employee/profile" class="<?= $nav['class']; ?>">
                    <i class="fas fa-user"></i>
                    <span>ملفي الشخصي</span>
                    <?= $nav['pill']; ?>
                </a>
            </li>

        <?php endif; ?>

    </ul>

    <!-- 🔻 المستخدم (Dropdown) -->
    <div class="sidebar-user" onclick="toggleUserMenu()">
        <div class="avatar">
            <?php echo mb_substr($_SESSION['employee_name'], 0, 1, 'UTF-8'); ?>
        </div>
        <div class="user-info">
            <strong><?php echo $_SESSION['employee_name']; ?></strong>
            <span><?php echo $_SESSION['role'] == 'admin' ? '' : 'موظف'; ?></span>
        </div>
        <i class="fas fa-chevron-up"></i>
    </div>
    <!-- 🔻 Dropdown -->
    <div class="user-dropdown" id="userDropdown">
         <?php if($_SESSION['role'] == 'employee'): ?>
           <a href="/employee/profile"><i class="fas fa-user"></i> الملف الشخصي</a>
        <?php endif; ?>
        <a onclick="confirmLogout()" onclick="confirmLogout()" style="color:#ef4444;cursor:pointer">
            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
        </a>
    </div>

</div>