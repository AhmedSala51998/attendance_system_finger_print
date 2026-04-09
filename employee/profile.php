<?php 
include "../config.php"; 
if(!isset($_SESSION['employee_id'])){ header("Location: ../login.php"); exit(); }
if($_SESSION['role'] == 'admin'){ header("Location: ../admin/index.php"); exit(); }

$id = $_SESSION['employee_id'];
$stmt = $conn->prepare("SELECT * FROM employees WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include "../layout/header.php"; 
?>

<div class="container" style="margin: 50px auto; max-width: 500px;">
    
    <div class="user-avatar mx-auto mb-4" style="margin: 0 auto 20px; width: 100px; height: 100px; font-size: 40px;">
        <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
    </div>
    
    <h2>بياناتي الشخصية</h2>

    <div style="text-align: right; margin-bottom: 20px; background: rgba(255,255,255,0.6); padding: 20px; border-radius: 15px; border: 1px solid rgba(0,0,0,0.05);">
        <p style="margin-bottom: 15px; font-size: 18px;"><i class="fas fa-fingerprint" style="color:var(--primary); width: 25px;"></i> <strong>الرقم الوظيفي:</strong> <?php echo $user['id']; ?></p>
        <p style="margin-bottom: 15px; font-size: 18px;"><i class="fas fa-user" style="color:var(--primary); width: 25px;"></i> <strong>الاسم:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p style="margin-bottom: 15px; font-size: 18px;"><i class="fas fa-envelope" style="color:var(--primary); width: 25px;"></i> <strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        
        <?php if($user['role'] == 'admin'): ?>
            <p style="margin-bottom: 0px; font-size: 18px;"><i class="fas fa-user-shield" style="color:var(--danger); width: 25px;"></i> <strong>الصلاحية:</strong> مدير نظام</p>
        <?php else: ?>
            <p style="margin-bottom: 0px; font-size: 18px;"><i class="fas fa-clock" style="color:var(--primary); width: 25px;"></i> <strong>الشيفت:</strong> 
                <?php echo ($user['shift'] == 'shift1') ? 'الفترة الصباحية (10 ص - 7 م)' : 'الفترة المسائية (12 م - 9 م)'; ?>
            </p>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="change_password.php" class="btn btn-primary" style="margin-bottom: 10px;">
            <i class="fas fa-key"></i> تغيير كلمة المرور
        </a>
        <a href="../dashboard.php" style="color: var(--primary); text-decoration: none; font-weight: 600; display: block; margin-top: 15px;">
            <i class="fas fa-arrow-right"></i> عودة للوحة التحكم
        </a>
    </div>

</div>
<?php include '../layout/footer.php'; ?>
