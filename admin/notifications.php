<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../config.php";

if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login");
    exit();
}
$admin_id = $_SESSION['employee_id'];

/*
|--------------------------------------------------------------------------
| تعليم إشعار كمقروء
|--------------------------------------------------------------------------
*/
if(isset($_GET['read'])){
    $id = intval($_GET['read']);

    $conn->query("
        UPDATE notifications 
        SET is_read = 'read' 
        WHERE id = $id
    ");

    header("Location: notifications.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| حذف إشعار
|--------------------------------------------------------------------------
*/
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    $conn->query("DELETE FROM notifications WHERE id = $id");

    header("Location: notifications.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| جلب الإشعارات
|--------------------------------------------------------------------------
*/
$notifications = $conn->query("
    SELECT n.*, e.name AS employee_name
    FROM notifications n
    LEFT JOIN employees e ON e.id = n.user_id
    WHERE n.user_id = $admin_id 
    ORDER BY n.id DESC
");

include "../layout/header.php";
?>

<div class="main-content">
    <div class="dashboard" style="margin:40px auto; max-width:1000px;">

        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h2 style="margin:0;">🔔 الإشعارات</h2>
            <a href="index" style="text-decoration:none; color:#64748b;">رجوع للرئيسية</a>
        </div>

        <div class="glass-panel" style="padding:25px; border-radius:20px;">

            <?php if($notifications->num_rows == 0): ?>
                <div style="text-align:center; padding:50px; color:#94a3b8;">
                    لا توجد إشعارات حالياً
                </div>
            <?php else: ?>

                <?php while($n = $notifications->fetch_assoc()): ?>

                    <div style="
                        padding:20px;
                        border-bottom:1px solid #f1f5f9;
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        background: <?= $n['is_read'] == 'read' ? '#fff' : '#f8fafc' ?>;
                    ">

                        <!-- المحتوى -->
                        <div style="flex:1;">
                            
                            <div style="display:flex; align-items:center; gap:10px;">
                                <strong style="font-size:15px;">
                                    <?= htmlspecialchars($n['title']) ?>
                                </strong>

                                <?php if($n['is_read'] == 'unread'): ?>
                                    <span style="
                                        background:red;
                                        color:#fff;
                                        font-size:11px;
                                        padding:3px 8px;
                                        border-radius:20px;
                                    ">
                                        جديد
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div style="font-size:13px; color:#64748b; margin-top:5px;">
                                <?= htmlspecialchars($n['desc']) ?>
                            </div>

                            <div style="font-size:12px; color:#94a3b8; margin-top:5px;">
                                👤 <?= $n['employee_name'] ?? 'النظام' ?> | 
                                🕒 <?= $n['created_at'] ?>
                            </div>

                        </div>

                        <!-- الأزرار -->
                        <div style="display:flex; gap:10px;">

                           <?php if($n['is_read'] == 'unread'): ?>
                                <a href="?read=<?= $n['id'] ?>" style="color:#10b981; font-size:13px;">
                                    ✔ قراءة
                                </a>
                            <?php endif; ?>

                            <a href="?delete=<?= $n['id'] ?>" 
                               onclick="return confirm('هل أنت متأكد؟')"
                               style="color:#ef4444; font-size:13px;">
                               حذف
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include "../layout/footer.php"; ?>