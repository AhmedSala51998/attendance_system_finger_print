<?php 
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}
include "../layout/header.php"; 
?>

<div class="container" style="margin: 50px auto; max-width: 500px;">
    <h2><i class="fas fa-user-plus" style="color:var(--primary); margin-left: 10px;"></i> إضافة حساب موظف جديد</h2>

    <?php
    if(isset($_POST['save'])){
        $name = $_POST['name']; 
        $email = $_POST['email']; 
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $shift = ($role === 'admin') ? NULL : $_POST['shift'];

        $stmt = $conn->prepare("INSERT INTO employees (name, email, password, shift, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $shift, $role);
        
        if($stmt->execute()){
            echo '<div style="color:var(--secondary); background:rgba(16, 185, 129, 0.1); padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; text-align: center;"><i class="fas fa-check-circle"></i> تم تسجيل الموظف بنجاح!</div>';
        } else {
            echo '<div style="color:var(--danger); background:rgba(239, 68, 68, 0.1); padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; text-align: center;"><i class="fas fa-exclamation-circle"></i> حدث خطأ، قد يكون البريد مسجلاً مسبقاً</div>';
        }
    }
    ?>

    <form method="POST">
        <div class="input-group">
            <i class="fas fa-user" style="right: 15px;"></i>
            <input type="text" name="name" placeholder="الاسم الكامل للموظف" required>
        </div>
        <div class="input-group">
            <i class="fas fa-envelope" style="right: 15px;"></i>
            <input type="email" name="email" placeholder="البريد الإلكتروني المخصص" required>
        </div>
        <div class="input-group password-container">
            <i class="fas fa-lock" style="right: 15px;"></i>
            <input type="password" name="password" placeholder="كلمة المرور الابتدائية" required>
            <i class="fas fa-eye toggle-password" onclick="togglePassword(this)"></i>
        </div>

        <div class="input-group" style="text-align: right;">
            <label style="display:block; margin-bottom:10px; color:#6B7280; font-weight:600;">صلاحية الحساب:</label>
            <select name="role" id="roleSelect" style="width: 100%; padding: 15px; border: 2px solid #E5E7EB; border-radius: 12px; outline: none; background: white;" required onchange="toggleShift()">
                <option value="employee">موظف عادي</option>
                <option value="admin">إداري (Admin)</option>
            </select>
        </div>
        
        <div class="input-group" id="shiftGroup" style="text-align: right;">
            <label style="display:block; margin-bottom:10px; color:#6B7280; font-weight:600;">فترة العمل (Shift):</label>
            <select name="shift" id="shiftSelect" style="width: 100%; padding: 15px; border: 2px solid #E5E7EB; border-radius: 12px; outline: none; background: white;" required>
                <option value="shift1">الفترة الصباحية</option>
                <option value="shift2">الفترة المسائية</option>
            </select>
        </div>

        <button name="save" type="submit" class="btn btn-success" style="width: 100%; padding: 15px; border-radius: 12px; font-weight: bold; margin-top: 20px;">
            <i class="fas fa-save" style="margin-left: 8px;"></i> حفظ بيانات الموظف
        </button>
    </form>

    <div style="margin-top: 25px; text-align: center;">
        <a href="employees" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 14px;">
            <i class="fas fa-arrow-right"></i> عودة لقائمة الموظفين
        </a>
    </div>
</div>

<script>
function toggleShift() {
    var role = document.getElementById('roleSelect').value;
    var shiftGroup = document.getElementById('shiftGroup');
    var shiftSelect = document.getElementById('shiftSelect');
    if (role === 'admin') {
        shiftGroup.style.display = 'none';
        shiftSelect.removeAttribute('required');
    } else {
        shiftGroup.style.display = 'block';
        shiftSelect.setAttribute('required', 'required');
    }
}
</script>

<?php include "../layout/footer.php"; ?>