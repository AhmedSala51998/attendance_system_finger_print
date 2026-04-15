// ✅ 1. تحديث الساعة الرقمية (نظام 12 ساعة)
function updateClock() {
    const now = new Date();
    let h = now.getHours();
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    const ampm = h >= 12 ? 'م' : 'ص';

    h = h % 12;
    h = h ? h : 12; // الساعة 0 تصبح 12
    const hours = String(h).padStart(2, '0');

    const clockElement = document.getElementById('digital-clock');
    if (clockElement) {
        clockElement.innerText = `${hours}:${m}:${s} ${ampm}`;
    }
}

// ✅ 2. عداد مدة الدوام الجاري (Work Duration Counter)
function updateWorkDuration() {
    const counterElement = document.getElementById('work-duration-counter');
    const labelElement = document.getElementById('work-duration-label');
    
    // التأكد من وجود العداد والبيانات الممرة من الـ PHP
    if (!counterElement || typeof ATTENDANCE_DATA === 'undefined') return;

    if (ATTENDANCE_DATA.status === 'checked_out') {
        counterElement.innerText = "00:00:00";
        counterElement.style.color = "rgba(255,255,255,0.4)";
        if (labelElement) labelElement.innerText = "لا يوجد دوام نشط";
        return;
    }

    const now = new Date();
    const actualCheckIn = new Date(ATTENDANCE_DATA.checkIn);
    const officialStart = new Date(ATTENDANCE_DATA.officialStart);
    const officialEnd = new Date(ATTENDANCE_DATA.officialEnd);
    
    // 💡 نقطة البداية الفعالة (بلمسة ذكية للمثالية)
    // لو الفرق بين الحضور والشيفت أقل من دقيقة، نعتبره حضور مثالي ونبدأ من وقت الشيفت لضمان ظهور 8:00:00
    let effectiveStartMs = Math.max(actualCheckIn.getTime(), officialStart.getTime());
    if (Math.abs(actualCheckIn.getTime() - officialStart.getTime()) < 60000) {
        effectiveStartMs = officialStart.getTime();
    }
    const effectiveStart = new Date(effectiveStartMs);

    // 💡 نقطة النهاية الفعالة (الأقدم بين اللحظة الحالية ونهاية الشيفت الرسمي)
    // لضمان توقف العداد عند انتهاء الوردية وعدم احتساب أي وقت إضافي (Overtime)
    const effectiveEnd = new Date(Math.min(now.getTime(), officialEnd.getTime()));

    // سحب وقت بدء البريك الفعلي من الداتا (لو موجود)
    const breakStart = ATTENDANCE_DATA.breakStart ? new Date(ATTENDANCE_DATA.breakStart) : null;
    const breakMins = ATTENDANCE_DATA.breakMinutes || 0;

    let diffMs;

    if (ATTENDANCE_DATA.status === 'on_break' && breakStart) {
        // ⏸️ الموظف في استراحة الآن: العداد يقف عند (لحظة بدء البريك - البداية الفعالة - البريكات السابقة)
        diffMs = (breakStart - effectiveStart) - (breakMins * 60 * 1000);
        counterElement.style.color = "#f59e0b"; // برتقالي أثناء التوقف (الاستراحة)
        if (labelElement) labelElement.innerText = "مدة العمل (متوقف مؤقتاً)";
    } else {
        // ▶️ الموظف يعمل الآن: العداد يحسب (النهاية الفعالة - البداية الفعالة - البريكات السابقة)
        diffMs = (effectiveEnd - effectiveStart) - (breakMins * 60 * 1000);
        
        // تغيير اللون للبرتقالي الفاتح لو العداد وصل للنهاية (للتنبيه)
        if (now >= officialEnd) {
            counterElement.style.color = "#94a3b8"; 
            if (labelElement) labelElement.innerText = "انتهى وقت الشيفت";
        } else {
            counterElement.style.color = "#10b981"; // أخضر نشط
            if (labelElement) labelElement.innerText = "مدة العمل الجارية";
        }
    }

    if (diffMs < 0) diffMs = 0;

    const totalSeconds = Math.floor(diffMs / 1000);
    const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const seconds = String(totalSeconds % 60).padStart(2, '0');

    counterElement.innerText = `${hours}:${minutes}:${seconds}`;
}

// تشغيل الساعة والعداد كل ثانية
setInterval(() => {
    updateClock();
    updateWorkDuration();
}, 1000);

// استدعاء أولي عند التحميل
updateClock();
updateWorkDuration();

// ✅ 3. نظام المراقبة الصامت (Heartbeat)

// =======================
// ✅ Heartbeat مع كشف inactivity محسّن
// =======================

// ✅ 4. تسجيل الخروج
function confirmLogout() {
    Swal.fire({
        title: 'هل تريد تسجيل الخروج؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'نعم، خروج',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = (window.location.pathname.includes('/admin/') || window.location.pathname.includes('/employee/')) ? '../logout' : 'logout';
        }
    });
}

async function smartCheckIn(btn) {

    Swal.fire({
        title: 'تأكيد تسجيل الحضور',
        text: 'اضغط تأكيد ثم تحقق بالبصمة',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء'
    }).then(async (result) => {

        if (!result.isConfirmed) return;

        // 📱 لو موبايل ويدعم البصمة
        if (window.PublicKeyCredential && /Mobi|Android|iPhone/i.test(navigator.userAgent)) {
            try {
                const publicKey = {
                    challenge: new Uint8Array(32),
                    timeout: 60000,
                    userVerification: "required"
                };

                await navigator.credentials.get({ publicKey });

                // ✅ بصمة نجحت
                checkIn(btn);

            } catch (err) {
                console.log(err);

                // ❌ فشل البصمة → نكمل عادي
                checkIn(btn);
            }

        } else {
            // 💻 كمبيوتر
            checkIn(btn);
        }

    });
}

// ✅ 5. تسجيل الحضور (Check-in)
function checkIn(btn) {
    if (navigator.geolocation) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحقق...';
        
        // 💡 طلب الموقع الجغرافي بدقة عالية ومعالجة الرفض أو الإغلاق
        navigator.geolocation.getCurrentPosition(function (pos) {
            const formData = new FormData();
            formData.append('lat', pos.coords.latitude);
            formData.append('lng', pos.coords.longitude);

            fetch('check_in', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                Swal.fire(d.status === 'success' ? 'تم العملية!' : 'تنبيه!', d.message, d.status === 'success' ? 'success' : 'error').then(() => {
                    if (d.status === 'success') location.reload();
                    else { 
                        btn.disabled = false; 
                        btn.innerHTML = '<i class="fas fa-fingerprint"></i> تسجيل حضور'; 
                    }
                });
            })
            .catch(err => {
                Swal.fire('خطأ!', 'فشل الاتصال بالسيرفر، يرجى المحاولة لاحقاً', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-fingerprint"></i> تسجيل حضور';
            });
        }, function (error) {
            let msg = 'يرجى تشغيل الموقع (GPS) والسماح للمتصفح بالوصول إليه للمتابعة';
            if(error.code === 1) msg = 'لقد رفضت إذن الوصول للموقع، يرجى تفعيله من إعدادات المتصفح وإعادة المحاولة';
            else if(error.code === 3) msg = 'انتهى الوقت المسموح لجلب الموقع، يرجى إعادة المحاولة من مكان أفضل';
            
            Swal.fire('خطأ في الموقع', msg, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-fingerprint"></i> تسجيل حضور';
        }, { 
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0 
        });
    } else {
        Swal.fire('خطأ!', 'متصفحك لا يدعم خاصية تحديد الموقع الجغرافي', 'error');
    }
}

async function smartCheckOut(btn) {

    Swal.fire({
        title: 'تأكيد إنهاء الدوام',
        text: 'اضغط تأكيد ثم تحقق بالبصمة',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد الانصراف',
        cancelButtonText: 'إلغاء'
    }).then(async (result) => {

        if (!result.isConfirmed) return;

        // 📱 لو موبايل ويدعم البصمة
        if (window.PublicKeyCredential && /Mobi|Android|iPhone/i.test(navigator.userAgent)) {
            try {
                const publicKey = {
                    challenge: new Uint8Array(32),
                    timeout: 60000,
                    userVerification: "required"
                };

                await navigator.credentials.get({ publicKey });

                // ✅ بصمة نجحت
                checkOut(btn);

            } catch (err) {
                console.log(err);

                // ❌ فشل البصمة → نكمل عادي
                checkOut(btn);
            }

        } else {
            // 💻 كمبيوتر
            checkOut(btn);
        }

    });
}

// ✅ 6. تسجيل الانصراف (Check-out)
function checkOut(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التسجيل...';
    fetch('check_out', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'error') {
                Swal.fire('عذراً!', d.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> تسجيل انصراف';
            } else {
                Swal.fire('تم الانصراف', d.message, 'success').then(() => location.reload());
            }
        })
        .catch(() => {
            Swal.fire('خطأ!', 'فشل الاتصال بالسيرفر', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> تسجيل انصراف';
        });
}

// ✅ 7. نظام البريك التفاعلي
function startBreak() {
    Swal.fire({
        title: 'الخروج للاستراحة؟',
        text: 'سيتم تعطيل زر الانصراف وحساب وقت الاستراحة المستهلك.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'بدء الاستراحة الآن',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('start_break').then(r => r.json()).then(d => {
                Swal.fire(d.status === 'success' ? 'بريك ممتع!' : 'تنبيه!', d.message, d.status).then(() => location.reload());
            });
        }
    });
}

function endBreak() {
    Swal.fire({
        title: 'هل عدت من الاستراحة؟',
        text: 'سيتم تفعيل زر الانصراف وحساب الوقت المستهلك.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        confirmButtonText: 'نعم، عدت الآن',
        cancelButtonText: 'لا زلت في بريك'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('end_break').then(r => r.json()).then(d => {
                Swal.fire(d.status === 'success' ? 'مرحباً بعودتك!' : 'تنبيه!', d.message, d.status).then(() => location.reload());
            });
        }
    });
}

// ✅ 8. إظهار/إخفاء كلمة المرور
function togglePassword(icon) {
    const input = icon.closest('.password-container').querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

function confirmDeleteEmployee(id, role) {

    if (role === 'admin') {
        Swal.fire('خطأ!', 'لا يمكن حذف مدير النظام', 'error');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'لن تستطيع استرجاع هذا الموظف بعد الحذف!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_employee?id=' + id;
        }
    });
}


function confirmDeleteHoliday(id) {

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'لن تستطيع استرجاع هذه الإجازة بعد الحذف!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'holidays.php?delete=' + id + '&deleted=1';
        }
    });
}

function confirmDeletePermission(id){
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "لن تستطيع استرجاع هذا الإذن!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "permissions.php?delete=" + id;
        }
    });
}

function toggleUserMenu() {
    const menu = document.getElementById('userDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}