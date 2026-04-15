<?php
include "../config.php";

if(!isset($_SESSION['employee_id'])){
    header("Location: ../login"); exit();
}
if($_SESSION['role'] == 'admin'){ header("Location: ../admin/index.php"); exit(); }

$emp_id = $_SESSION['employee_id'];

// تعليم كمقروء
if(isset($_GET['read'])){
    $id = intval($_GET['read']);
    $conn->query("UPDATE notifications SET is_read = 'read' WHERE id = $id AND user_id = $emp_id");
    header("Location: notifications.php");
    exit();
}

// حذف
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM notifications WHERE id = $id AND user_id = $emp_id");
    header("Location: notifications.php");
    exit();
}

// جلب الإشعارات الخاصة بالموظف فقط
$notifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $emp_id 
    ORDER BY id DESC
");

include "../layout/header.php";
?>

<div class="main-content">
<div class="dashboard" style="margin:40px auto; max-width:900px;">

<h2>🔔 إشعاراتي</h2>

<?php if($notifications->num_rows == 0): ?>
    <div style="text-align:center; padding:50px; color:#94a3b8;">
        لا توجد إشعارات حالياً
    </div>
<?php else: ?>

<?php while($n = $notifications->fetch_assoc()): ?>
<div style="
    padding:20px;
    border-bottom:1px solid #f1f5f9;
    background: <?= $n['is_read'] == 'read' ? '#fff' : '#f8fafc' ?>;
">

    <strong><?= htmlspecialchars($n['title']) ?></strong>

    <?php if($n['is_read'] == 'unread'): ?>
        <span style="color:red; font-size:12px;">● جديد</span>
    <?php endif; ?>

    <div style="color:#64748b; margin-top:5px;">
        <?= htmlspecialchars($n['desc']) ?>
    </div>

    <div style="font-size:12px; color:#94a3b8; margin-top:5px;">
        🕒 <?= $n['created_at'] ?>
    </div>

    <div style="margin-top:10px;">
        <?php if($n['is_read'] == 'unread'): ?>
            <a href="?read=<?= $n['id'] ?>">✔ قراءة</a>
        <?php endif; ?>

        <a href="?delete=<?= $n['id'] ?>" style="color:red;"> حذف</a>
    </div>

</div>
<?php endwhile; ?>

<?php endif; ?>

</div>
</div>

<?php include "../layout/footer.php"; ?>