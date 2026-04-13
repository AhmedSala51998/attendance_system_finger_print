<?php 
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}

$msg = "";
$edit_holiday = null;

// 1. إضافة أو تحديث إجازة
if (isset($_POST['save_holiday'])) {
    $date = $_POST['date'];
    $desc = $_POST['description'];
    $id   = isset($_POST['holiday_id']) ? intval($_POST['holiday_id']) : 0;
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE holidays SET date=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $date, $desc, $id);
        if ($stmt->execute()) { $msg = "تم تحديث الإجازة بنجاح!"; }
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO holidays (date, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $date, $desc);
        if ($stmt->execute()) { $msg = "تمت إضافة الإجازة بنجاح!"; }
    }
}

// 2. التحضير للتعديل
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM holidays WHERE id = $id");
    $edit_holiday = $res->fetch_assoc();
}

// 3. حذف إجازة
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM holidays WHERE id = $id");
    header("Location: holidays.php"); exit();
}

$holidays = $conn->query("SELECT * FROM holidays ORDER BY date DESC");

include "../layout/header.php"; 
?>
<div class="main-content">
    <div class="dashboard" style="margin: 40px auto; max-width: 1000px;">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="index" style="width:45px; height:45px; background:white; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--primary); box-shadow:0 4px 10px rgba(0,0,0,0.05); text-decoration:none;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin:0; font-size:24px; font-weight:800; color:#1e293b;">إدارة الإجازات الرسمية</h2>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start;">
            
            <!-- Form Column -->
            <div class="glass-panel" style="padding:35px; border-radius:30px; border:1px solid rgba(255,255,255,0.5); box-shadow:0 20px 40px rgba(0,0,0,0.03);">
                <h3 style="margin-top:0; margin-bottom:25px; font-size:18px; color:var(--primary);">
                    <i class="fas <?php echo $edit_holiday ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> 
                    <?php echo $edit_holiday ? 'تعديل بيانات الإجازة' : 'إضافة إجازة جديدة'; ?>
                </h3>

                <?php if($msg): ?>
                    <div style="background:#f0fdf4; color:#16a34a; padding:15px; border-radius:15px; margin-bottom:25px; font-size:14px; border:1px solid #bbf7d0; font-weight:bold;">
                        <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="display:flex; flex-direction:column; gap:20px;">
                    <input type="hidden" name="holiday_id" value="<?php echo $edit_holiday['id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label style="display:block; margin-bottom:8px; font-weight:700; color:#475569; font-size:13px;">تاريخ اليوم</label>
                        <input type="date" name="date" required value="<?php echo $edit_holiday['date'] ?? ''; ?>" 
                            style="width:100%; padding:14px; border-radius:15px; border:2px solid #f1f5f9; outline:none; font-family:'Cairo';">
                    </div>

                    <div class="form-group">
                        <label style="display:block; margin-bottom:8px; font-weight:700; color:#475569; font-size:13px;">وصف المناسبة (مثال: عيد الأضحى)</label>
                        <input type="text" name="description" required placeholder="ادخل اسم المناسبة..." value="<?php echo $edit_holiday['description'] ?? ''; ?>"
                            style="width:100%; padding:14px; border-radius:15px; border:2px solid #f1f5f9; outline:none; font-family:'Cairo';">
                    </div>

                    <button type="submit" name="save_holiday" class="btn btn-primary" style="padding:16px; border-radius:15px; font-weight:700; font-size:16px; box-shadow:0 10px 20px rgba(79, 70, 229, 0.2);">
                        <i class="fas fa-save"></i> <?php echo $edit_holiday ? 'حفظ التعديلات' : 'إضافة الإجازة الآن'; ?>
                    </button>
                    
                    <?php if($edit_holiday): ?>
                        <a href="holidays.php" style="text-align:center; color:#64748b; font-size:14px; text-decoration:none;">إلغاء التعديل والرجوع</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Column -->
            <div class="glass-panel" style="padding:35px; border-radius:30px; border:1px solid rgba(255,255,255,0.5); box-shadow:0 20px 40px rgba(0,0,0,0.03);">
                <h3 style="margin-top:0; margin-bottom:25px; font-size:18px;">سجل العطلات</h3>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:right; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:1px;">
                                <th style="padding:15px; border-bottom:1px solid #f1f5f9;">التاريخ</th>
                                <th style="padding:15px; border-bottom:1px solid #f1f5f9;">المناسبة</th>
                                <th style="padding:15px; border-bottom:1px solid #f1f5f9; text-align:center;">خيارات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($h = $holidays->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #f8fafc; transition:0.3s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding:15px; font-weight:700; color:#1e293b; font-size:14px;"><?php echo $h['date']; ?></td>
                                <td style="padding:15px; color:#475569; font-size:14px;"><?php echo htmlspecialchars($h['description']); ?></td>
                                <td style="padding:15px; text-align:center;">
                                    <div style="display:flex; gap:10px; justify-content:center;">
                                        <a href="?edit=<?php echo $h['id']; ?>" style="color:#3b82f6; background:rgba(59, 130, 246, 0.1); width:35px; height:35px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a onclick="confirmDeleteHoliday(<?php echo $h['id']; ?>)" style="color:#ef4444; background:rgba(239, 68, 68, 0.1); width:35px; height:35px; border-radius:10px; display:flex; align-items:center; justify-content:center; text-decoration:none;cursor:pointer">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($holidays->num_rows == 0): ?>
                                <tr><td colspan="3" style="padding:40px; text-align:center; color:#94a3b8;">لا توجد إجازات استثنائية مسجلة حالياً.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "../layout/footer.php"; ?>
