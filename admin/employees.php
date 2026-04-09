<?php 
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}
include "../layout/header.php"; 
?>

<div class="dashboard" style="margin: 40px auto; max-width: 1100px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; flex-wrap: wrap; gap: 15px;">
        <h2 style="margin:0; font-size: 28px;">
            <i class="fas fa-users-cog" style="color:var(--primary); margin-left: 10px;"></i> إدارة شؤون الموظفين
        </h2>
        <a href="add_employee" class="btn btn-success" style="width: auto; padding: 12px 25px; border-radius: 15px; background: linear-gradient(135deg, #10b981, #059669); border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
            <i class="fas fa-user-plus"></i> إضافة موظف جديد
        </a>
    </div>

    <div class="table-wrapper" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.3); overflow: hidden; box-shadow: var(--glass-shadow);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: var(--primary); color: white;">
                <tr>
                    <th style="padding: 15px 20px; text-align: right;">الموظف</th>
                    <th style="padding: 15px 20px; text-align: center;">الصلاحية</th>
                    <th style="padding: 15px 20px; text-align: center;">الشيفت</th>
                    <th style="padding: 15px 20px; text-align: center;">البريد الإلكتروني</th>
                    <th style="padding: 15px 20px; text-align: center;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM employees ORDER BY role ASC, name ASC");
                while($row = $res->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.03); transition: all 0.3s ease;" class="employee-row">
                    <td style="padding: 15px 20px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: <?php echo $row['role']=='admin' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #6366f1, #4f46e5)'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                <?php echo mb_substr($row['name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <span style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($row['name']); ?></span>
                        </div>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <?php if($row['role'] == 'admin'): ?>
                            <span style="background: #fffbeb; color: #b45309; padding: 5px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; border: 1px solid #fde68a;">
                                <i class="fas fa-shield-alt" style="margin-left: 4px;"></i> مدير نظام
                            </span>
                        <?php else: ?>
                            <span style="background: #f1f5f9; color: #475569; padding: 5px 12px; border-radius: 10px; font-size: 12px; font-weight: 600;">
                                موظف
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <?php if($row['role'] != 'admin'): ?>
                            <span style="color: #64748b; font-size: 13px;">
                                <i class="far fa-clock" style="margin-left: 5px; color: var(--primary);"></i>
                                <?php echo ($row['shift'] == 'shift1') ? 'صباحي' : 'مسائي'; ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">
                        <?php echo htmlspecialchars($row['email']); ?>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <div style="display: flex; gap: 8px; justify-content: center;">
                            <?php if($row['role'] != 'admin'): ?>
                                <a href="edit_employee?id=<?php echo $row['id']; ?>" class="btn-action" style="color: #6366f1; background: #eef2ff;" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDeleteEmployee(<?php echo $row['id']; ?>, '<?php echo $row['role']; ?>')" class="btn-action" style="color: #ef4444; background: #fef2f2; border: none; cursor: pointer;" title="حذف">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 11px;">محمي <i class="fas fa-lock"></i></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 35px; text-align: center;">
        <a href="index" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-right"></i> العودة للوحة التحكم الرئيسية
        </a>
    </div>
</div>

<style>
    .employee-row:hover {
        background: rgba(99, 102, 241, 0.03) !important;
    }
    .btn-action {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 14px;
    }
    .btn-action:hover {
        transform: scale(1.1);
        filter: brightness(0.9);
    }
</style>

<?php include "../layout/footer.php"; ?>