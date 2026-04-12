<div class="sidebar-pro" id="sidebar">

    <!-- 🔷 Logo -->
    <div class="brand">
        <div class="logo">SH</div>
        <div class="brand-text">
            <h3>Smart HR</h3>
            <span>Attendance System</span>
        </div>
    </div>

    <!-- 🔹 Menu -->
    <ul class="menu">

        <span class="menu-title">Main</span>

        <li>
            <a href="dashboard" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
                <div class="active-pill"></div>
            </a>
        </li>

        <li>
            <a href="employees">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </a>
        </li>

        <li>
            <a href="attendance_report">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </li>

        <span class="menu-title">Management</span>

        <li>
            <a href="holidays">
                <i class="fas fa-calendar"></i>
                <span>Holidays</span>
            </a>
        </li>

        <li>
            <a href="settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>

    </ul>

    <!-- 🔻 User -->
    <div class="sidebar-user">
        <div class="avatar">
            <?php echo strtoupper(substr($_SESSION['employee_name'],0,1)); ?>
        </div>
        <div class="user-info">
            <strong><?php echo $_SESSION['employee_name']; ?></strong>
            <span>Admin</span>
        </div>
        <i class="fas fa-chevron-down"></i>
    </div>

</div>