<?php 
include "config.php";
if(!isset($_SESSION['employee_id'])){ header("Location: login"); exit(); }

$emp_id = $_SESSION['employee_id'];
$today = date('Y-m-d');

// 💡 استخدام المحرك المركزي للمواعيد
$shift = getShiftDetails($conn, $emp_id, $today);

// جلب سجل الحضور
$res = $conn->query("SELECT * FROM attendance WHERE employee_id=$emp_id AND date='$today' AND check_out IS NULL AND status != 'absent'");
$att = $res->fetch_assoc();

$status = $att ? 'checked_in' : 'checked_out';

// تحديد حال الموظف حالياً
$is_on_break = ($att && $att['break_start'] !== NULL && $att['break_end'] === NULL);
$is_break_finished = ($att && $att['break_end'] !== NULL);

// فحص موعد البريك الرسمي (4-5 عصراً) - فقط إذا كان اليوم يدعم البريك
$now_h = intval(date('H'));
$is_break_time_window = ($shift['has_break'] && $now_h == 16); 

include "layout/header.php"; 
?>

<!-- 🖥️ تمرير بيانات الحضور لعداد ساعات العمل (Stopwatch) -->
<script>
    const ATTENDANCE_DATA = {
        status: <?php echo json_encode($att ? ($is_on_break ? 'on_break' : 'checked_in') : 'checked_out'); ?>,
        checkIn: <?php echo json_encode($att ? $att['check_in'] : null); ?>,
        officialStart: <?php echo json_encode(date('Y-m-d ' . $shift['start_time'])); ?>,
        officialEnd: <?php echo json_encode(date('Y-m-d ' . $shift['end_time'])); ?>,
        breakStart: <?php echo json_encode($is_on_break ? $att['break_start'] : null); ?>,
        breakMinutes: <?php echo intval($att['break_minutes'] ?? 0); ?>
    };
</script>

<div class="dashboard" style="margin: 40px auto; max-width: 900px;">
    
    <!-- 🏢 Header Section (The Digital Core) -->
    <div class="user-header" style="background: linear-gradient(135deg, #4f46e5, #9333ea); color: white; padding: 45px; border-radius: 35px; box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.4); margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;">
        <div style="z-index: 2;">
            <p style="margin: 0; font-size: 16px; opacity: 0.85;">طاب يومك،</p>
            <h1 style="margin: 5px 0 0 0; font-size: 32px; font-weight: 800;"><?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'موظف'); ?></h1>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <span style="background: rgba(255,255,255,0.15); backdrop-filter: blur(5px); padding: 6px 18px; border-radius: 50px; font-size: 13px; font-weight: 600; border: 1px solid rgba(255,255,255,0.25);">
                    <i class="fas fa-clock"></i> <?php echo $shift['is_holiday'] ? 'يوم إجازة' : $shift['display']; ?>
                </span>
            </div>
        </div>
        <div style="z-index: 2; text-align: center; background: rgba(0,0,0,0.15); padding: 25px 35px; border-radius: 35px; border: 1px solid rgba(255,255,255,0.1); min-width: 280px; backdrop-filter: blur(5px);">
            <div id="digital-clock" style="font-size: 55px; font-weight: 900; color: #ffffff; line-height: 1; text-shadow: 0 10px 20px rgba(0,0,0,0.3);">00:00:00</div>
            <p style="margin: 8px 0 15px 0; opacity: 0.8; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
                <?php 
                $days = ['Sun' => 'الأحد', 'Mon' => 'الاثنين', 'Tue' => 'الثلاثاء', 'Wed' => 'الأربعاء', 'Thu' => 'الخميس', 'Fri' => 'الجمعة', 'Sat' => 'السبت'];
                $months = ['Jan' => 'يناير', 'Feb' => 'فبراير', 'Mar' => 'مارس', 'Apr' => 'أبريل', 'May' => 'مايو', 'Jun' => 'يونيو', 'Jul' => 'يوليو', 'Aug' => 'أغسطس', 'Sep' => 'سبتمبر', 'Oct' => 'أكتوبر', 'Nov' => 'نوفمبر', 'Dec' => 'ديسمبر'];
                echo $days[date('D')] . '، ' . date('d') . ' ' . $months[date('M')] . ' ' . date('Y'); 
                ?>
            </p>
            
            <!-- ⏱️ عداد مدة العمل الفعلي (Stopwatch) -->
            <div style="display: flex; flex-direction: column; align-items: center;">
                <span id="work-duration-label" style="font-size: 11px; opacity: 0.7; font-weight: 700; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">مدة العمل الجارية</span>
                <div id="work-duration-counter" style="font-size: 28px; font-weight: 800; color: #10b981; font-family: 'Courier New', monospace; text-shadow: 0 0 20px rgba(16, 185, 129, 0.4);">00:00:00</div>
            </div>
        </div>
    </div>

    <!-- ⚡ Smart Action Center (Attendance Panel) -->
    <div class="attendance-card" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(25px); padding: 50px 40px; border-radius: 40px; border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 40px 100px -20px rgba(0,0,0,0.05); margin-bottom: 40px; text-align: center; position: relative;">
        
        <?php if($shift['is_holiday']): ?>
            <!-- عرض حالة الإجازة -->
            <div style="padding: 40px; text-align:center;">
                <i class="fas fa-umbrella-beach" style="font-size: 80px; color: #4f46e5; margin-bottom: 25px; opacity: 0.5;"></i>
                <h2 style="color: #1e293b;"><?php echo $shift['reason']; ?></h2>
                <p style="color: #64748b;">استمتع بوقتك، لا يوجد دوام مرصود لهذا اليوم.</p>
            </div>
        <?php else: ?>
            <!-- عرض أزرار الحضور والبريك -->
            <div id="status-controller" style="margin-bottom: 35px;">
                <?php if($status == 'checked_in'): ?>
                    <div style="display: inline-flex; align-items: center; gap: 12px; background: #f0fdf4; color: #15803d; padding: 12px 28px; border-radius: 100px; font-weight: 800; font-size: 15px; border: 1px solid #bbf7d0;">
                        <span class="status-pulse"></span>
                        <i class="fas fa-briefcase"></i> نظام الدوام نشط حالياً
                    </div>
                <?php else: ?>
                    <div style="display: inline-flex; align-items: center; gap: 12px; background: #f8fafc; color: #64748b; padding: 12px 28px; border-radius: 100px; font-weight: 800; font-size: 15px; border: 1px solid #e2e8f0;">
                        <i class="fas fa-power-off"></i> لم يتم تسجيل حضور اليوم
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: flex; justify-content: center; gap: 25px; flex-wrap: wrap;">
                <?php if($status == 'checked_out'): ?>
                    <button id="checkInBtn" class="action-btn-premium" style="background: linear-gradient(135deg, #10b981, #059669); color: white; width: 280px; height: 75px;" onclick="checkIn(this)">
                        <i class="fas fa-fingerprint" style="font-size: 24px;"></i>
                        <span>تسجيل حضور ذكي</span>
                    </button>
                <?php else: ?>
                    <button id="checkOutBtn" class="action-btn-premium" 
                        style="background: <?php echo $is_on_break ? '#f1f5f9' : 'linear-gradient(135deg, #ef4444, #dc2626)'; ?>; color: <?php echo $is_on_break ? '#94a3b8' : 'white'; ?>; width: 280px; height: 75px; cursor: <?php echo $is_on_break ? 'not-allowed' : 'pointer'; ?>;" 
                        <?php echo $is_on_break ? 'disabled' : ''; ?>
                        onclick="checkOut(this)">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>إنهاء الدوام الآن</span>
                    </button>

                    <!-- البريك (يظهر فقط للأيام التي تدعم البريك) -->
                    <?php if($shift['has_break']): ?>
                        <?php if(!$is_on_break && !$is_break_finished): ?>
                            <?php if($is_break_time_window): ?>
                                <button class="action-btn-premium" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; width: 280px; height: 75px;" onclick="startBreak()">
                                    <i class="fas fa-coffee"></i> <span>بدء الاستراحة</span>
                                </button>
                            <?php else: ?>
                                <div class="info-chip" style="width: 280px; height: 75px; border-radius: 20px; justify-content: center; background: #fafafa; border: 1px dashed #e2e8f0;">
                                    <i class="fas fa-lock" style="color: #cbd5e1;"></i> <span style="color: #94a3b8;">موعد البريك: 04:00 م</span>
                                </div>
                            <?php endif; ?>
                        <?php elseif($is_on_break): ?>
                            <button class="action-btn-premium" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; width: 280px; height: 75px;" onclick="endBreak()">
                                <i class="fas fa-undo-alt"></i> <span>العودة للعمل</span>
                            </button>
                        <?php else: ?>
                            <div class="info-chip" style="width: 280px; height: 75px; border-radius: 20px; justify-content: center; background: #ecfdf5; border: 1px solid #10b981;">
                                <i class="fas fa-check-circle" style="color: #10b981;"></i> تم استهلاك البريك
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 🧩 Navigation Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
        <a href="employee/my_attendance" class="action-card" style="background: white; padding: 35px; border-radius: 30px; text-decoration: none; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02); display: block;">
            <div style="width: 70px; height: 70px; background: #eef2ff; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-calendar-alt" style="font-size: 30px; color: #4f46e5;"></i>
            </div>
            <span style="font-weight: 800; color: #1e293b; font-size: 18px;">سجلاتي اليومية</span>
        </a>
        <a href="employee/monthly_report" class="action-card" style="background: white; padding: 35px; border-radius: 30px; text-decoration: none; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02); display: block;">
            <div style="width: 70px; height: 70px; background: #ecfdf5; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-file-invoice-dollar" style="font-size: 30px; color: #10b981;"></i>
            </div>
            <span style="font-weight: 800; color: #1e293b; font-size: 18px;">التقرير الشهري</span>
        </a>
        <a href="employee/profile" class="action-card" style="background: white; padding: 35px; border-radius: 30px; text-decoration: none; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 10px 20px rgba(0,0,0,0.02); display: block;">
            <div style="width: 70px; height: 70px; background: #f5f3ff; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-user-shield" style="font-size: 30px; color: #9333ea;"></i>
            </div>
            <span style="font-weight: 800; color: #1e293b; font-size: 18px;">ملفي الشخصي</span>
        </a>
    </div>

    <div style="margin-top: 50px; text-align: center;">
        <button onclick="confirmLogout()" class="btn" style="background: transparent; border: 2px solid #e2e8f0; color: #64748b; padding: 15px 40px; border-radius: 20px; font-weight: 700; width: auto; display: inline-flex; align-items: center; gap: 10px;">
            <i class="fas fa-power-off"></i> تسجيل الخروج
        </button>
    </div>
</div>

<?php include "layout/footer.php"; ?>
<?php if($status == 'checked_in' && !empty($att['check_in'])): ?>
<script>
    startHeartbeat(<?php echo json_encode($att['check_in']); ?>);
</script>
<?php endif; ?>