<?php 
include "../config.php"; 
if(!isset($_SESSION['employee_id'])){ header("Location: ../login"); exit(); }
if($_SESSION['role'] == 'admin'){ header("Location: ../admin/index"); exit(); }

$id = $_SESSION['employee_id'];
include "../layout/header.php"; 
?>
<div class="main-content">
    <div class="container" style="margin: 50px auto; max-width: 500px;">
        <h2><i class="fas fa-key" style="color:var(--primary); margin-left: 10px;"></i> تحديث كلمة المرور</h2>

        <?php
        if(isset($_POST['change_password'])){
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];

            $stmt = $conn->prepare("SELECT password FROM employees WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if(!password_verify($current, $user['password'])){
                echo '<div style="color:var(--danger); background:rgba(239, 68, 68, 0.1); padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; text-align: center;"><i class="fas fa-exclamation-circle"></i> كلمة المرور الحالية غير صحيحة</div>';
            } elseif($new !== $confirm) {
                echo '<div style="color:var(--danger); background:rgba(239, 68, 68, 0.1); padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; text-align: center;"><i class="fas fa-exclamation-circle"></i> كلمتا المرور الجديدتان غير متطابقتان</div>';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE employees SET password=? WHERE id=?");
                $upd->bind_param("si", $hashed, $id);
                if($upd->execute()){
                    echo '<div style="color:var(--secondary); background:rgba(16, 185, 129, 0.1); padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; text-align: center;"><i class="fas fa-check-circle"></i> تم تحديث كلمة المرور بنجاح!</div>';
                }
            }
        }
        ?>

        <form method="POST">
            <div class="input-group password-container">
                <i class="fas fa-lock" style="right: 15px;"></i>
                <input type="password" name="current_password" placeholder="كلمة المرور الحالية" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword(this)"></i>
            </div>
            
            <div class="input-group password-container">
                <i class="fas fa-key" style="right: 15px;"></i>
                <input type="password" name="new_password" placeholder="كلمة المرور الجديدة" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword(this)"></i>
            </div>
            
            <div class="input-group password-container">
                <i class="fas fa-check-double" style="right: 15px;"></i>
                <input type="password" name="confirm_password" placeholder="تأكيد كلمة المرور الجديدة" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword(this)"></i>
            </div>

            <button name="change_password" type="submit" class="btn btn-success" style="width: 100%; padding: 15px; border-radius: 12px; font-weight: bold; margin-top: 20px;">
                <i class="fas fa-save" style="margin-left: 8px;"></i> تعيين كلمة المرور الجديدة
            </button>
        </form>

        <div style="margin-top: 25px; text-align: center;">
            <a href="../dashboard" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 14px;">
                <i class="fas fa-arrow-right"></i> عودة للوحة التحكم
            </a>
        </div>

    </div>
</div>
<?php include '../layout/footer.php'; ?>
