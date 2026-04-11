<?php 
include "config.php"; 
if(isset($_SESSION['employee_id'])) {
    header("Location: dashboard");
    exit();
}
include "layout/header.php"; 
?>

<div class="container" style="margin: 50px auto; max-width: 450px;">
    <div class="user-avatar" style="margin: 0 auto 20px; width: 80px; height: 80px; font-size: 36px; background: linear-gradient(135deg, var(--primary), #a855f7); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);">
        <i class="fas fa-fingerprint"></i>
    </div>
    
    <h2 style="text-align: center; margin-bottom: 30px;">تسجيل الدخول</h2>
    
    <?php
    if(isset($_POST['login'])){
        $email=$_POST['email'];
        $password=$_POST['password'];
        
        $stmt=$conn->prepare("SELECT * FROM employees WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user=$stmt->get_result()->fetch_assoc();
        
        if($user && password_verify($password,$user['password'])){
            $_SESSION['employee_id']=$user['id'];
            $_SESSION['employee_name']=$user['name'];
            $_SESSION['role']=$user['role'];

            setcookie("api_token", $user['api_token'], time()+3600*24*30, "/");
            
            if($user['role'] == 'admin') {
                header("Location: admin/index");
            } else {
                header("Location: dashboard");
            }
            exit();
        }else{
            echo '<div style="color:var(--danger); background:rgba(239, 68, 68, 0.1); padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; text-align: center;"><i class="fas fa-exclamation-circle"></i> البريد الإلكتروني أو كلمة المرور غير صحيحة</div>';
        }
    }
    ?>
    
    <form method="POST">
        <div class="input-group">
            <i class="fas fa-envelope" style="right: 15px;"></i>
            <input type="email" name="email" placeholder="البريد الالكتروني" required>
        </div>
        
        <div class="input-group password-container">
            <i class="fas fa-lock" style="right: 15px;"></i>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <i class="fas fa-eye toggle-password" onclick="togglePassword(this)"></i>
        </div>
        
        <button name="login" type="submit" class="btn" style="width: 100%; padding: 15px; border-radius: 12px; font-weight: bold; margin-top: 10px;">
            دخول للنظام <i class="fas fa-sign-in-alt" style="margin-right: 10px;"></i>
        </button>
    </form>
</div>

<?php include "layout/footer.php"; ?>