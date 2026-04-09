<?php 
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}

if(!isset($_GET['id'])){ header("Location: employees"); exit(); }
$id = intval($_GET['id']);

$emp_res = $conn->query("SELECT * FROM employees WHERE id=$id");
$emp = $emp_res->fetch_assoc();
if(!$emp){ header("Location: employees"); exit(); }

$msg = "";
if(isset($_POST['update'])){
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $shift = $_POST['shift'];
    $pass  = $_POST['password'];

    // تحديث البيانات الأساسية
    $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, shift=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $shift, $id);
    
    if($stmt->execute()){
        // تحديث كلمة المرور فقط إذا تم إدخالها
        if(!empty($pass)){
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $upd_pass = $conn->prepare("UPDATE employees SET password=? WHERE id=?");
            $upd_pass->bind_param("si", $hashed, $id);
            $upd_pass->execute();
        }
        $msg = "<script>Swal.fire('تم!', 'تم تحديث بيانات الموظف بنجاح', 'success').then(() => { window.location.href='employees'; });</script>";
    } else {
        $msg = "<div style='color:var(--danger); margin-bottom:15px; text-align:center;'>خطأ في التحديث، البريد قد يكون مستخدماً</div>";
    }
}

include "../layout/header.php"; 
?>

<div class="container" style="margin: 50px auto; max-width: 550px;">
    <?php echo $msg; ?>
    
    <h2><i class="fas fa-user-edit" style="color:var(--primary); margin-left: 10px;"></i> تعديل بيانات الموظف</h2>
    <p style="color: #64748b; margin-bottom: 25px;">يمكنك تحديث معلومات الموظف أو تغيير كلمة المرور من هنا.</p>

    <form method="POST">
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="name" placeholder="الاسم الكامل" value="<?php echo htmlspecialchars($emp['name']); ?>" required>
        </div>

        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="البريد الإلكتروني" value="<?php echo htmlspecialchars($emp['email']); ?>" required>
        </div>

        <div class="input-group">
            <i class="fas fa-clock"></i>
            <select name="shift" style="width: 100%; padding: 15px 45px; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 16px; outline: none; background: white; appearance: none; cursor: pointer;">
                <option value="shift1" <?php if($emp['shift'] == 'shift1') echo 'selected'; ?>>الفترة الصباحية (10 ص - 7 م)</option>
                <option value="shift2" <?php if($emp['shift'] == 'shift2') echo 'selected'; ?>>الفترة المسائية (12 م - 9 م)</option>
            </select>
        </div>

        <hr style="margin: 25px 0;">
        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 15px; text-align: right;"><i class="fas fa-info-circle"></i> اترك حقل كلمة المرور فارغاً إذا كنت لا ترغب في تغييرها.</p>

        <div class="input-group password-container">
            <i class="fas fa-lock" style="right: 15px;"></i>
            <input type="password" name="password" placeholder="كلمة مرور جديدة (اختياري)">
            <i class="fas fa-eye toggle-password" onclick="togglePassword(this)"></i>
        </div>

        <button name="update" type="submit" class="btn btn-success" style="width: 100%; font-weight: bold; padding: 15px; border-radius: 12px; margin-top: 10px;">
            <i class="fas fa-save" style="margin-left: 8px;"></i> حفظ التغييرات الآن
        </button>
    </form>

    <div style="margin-top: 25px; text-align: center;">
        <a href="employees" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 14px;">
            <i class="fas fa-arrow-right"></i> عودة لقائمة الموظفين
        </a>
    </div>
</div>

<?php include '../layout/footer.php'; ?>
