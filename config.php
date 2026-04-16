<?php
$conn = new mysqli("localhost","u552468652_attendance_sys","Attendance_sys12345","u552468652_attendance_sys");

if($conn->connect_error){
    die("DB Error");
}

// توحيد المنطقة الزمنية السعودية (PHP + MySQL)
$conn->query("SET time_zone = '+03:00'");
date_default_timezone_set("Asia/Riyadh");
session_start();

// 💡 دالة تحويل الساعات العشرية (7.85) إلى صيغة مفهومة (7 س 51 د)
function formatWorkHours($decimalHours) {
    if (!$decimalHours || floatval($decimalHours) <= 0) return "—";
    $h = floor($decimalHours);
    $m = round(($decimalHours - $h) * 60);
    $res = "";
    if ($h > 0) $res .= $h . " س ";
    if ($m > 0) $res .= $m . " د ";
    return $res ? trim($res) : "—";
}

// ==========================================================
// 💡 نظام "اللحاق التلقائي بالغياب" (Absence Catch-up System)
// يتحقق النظام من تسجيل الغياب للأيام الفائتة فور زيارة أي شخص للموقع
// ==========================================================
$today      = date("Y-m-d");
$now_h_m    = date("H:i");
$cron_res   = $conn->query("SELECT last_cron_run FROM settings WHERE id=1");
$setting    = $cron_res->fetch_assoc();
$last_run   = $setting['last_cron_run'] ?? date("Y-m-d", strtotime("-1 day"));

// 1. تعويض الأيام الماضية (التي لم يُفتح فيها الموقع ليلاً)
if ($last_run < $today) {
    $current = date("Y-m-d", strtotime($last_run . " +1 day"));
    
    // نقوم بتشغيل الغياب لكل يوم فات حتى أمس (Yesterday)
    while ($current < $today) {
        $target_date = $current;
        include dirname(__FILE__) . '/mark_absent.php'; 
        $current = date("Y-m-d", strtotime($current . " +1 day"));
    }

    // 2. فحص هل حان موعد تسجيل غياب "اليوم" (الساعة 11:55 م أو بعدها)
    if ($now_h_m >= "23:55") {
        $target_date = $today;
        include dirname(__FILE__) . '/mark_absent.php';
    }
}


function getAccessToken() {

    $keyFile = __DIR__ . "/config/serviceAccountKey.json";
    $jsonKey = json_decode(file_get_contents($keyFile), true);

    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $now = time();

    $payload = [
        "iss" => $jsonKey['client_email'],
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud" => "https://oauth2.googleapis.com/token",
        "iat" => $now,
        "exp" => $now + 3600
    ];

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

    openssl_sign($signatureInput, $signature, $jsonKey['private_key'], 'sha256');

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $signatureInput . "." . $base64UrlSignature;

    // 🔥 طلب access token
    $ch = curl_init("https://oauth2.googleapis.com/token");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion" => $jwt
    ]));

    $response = curl_exec($ch);

    if(curl_errno($ch)){
        die("CURL ERROR: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    echo "<pre>";
    print_r([
        "HTTP_CODE" => $httpCode,
        "RESPONSE" => $response
    ]);
    echo "</pre>";
    die();

    $result = json_decode($response, true);

    return $result['access_token'];
}

function sendFCM($token, $title, $body, $data = []) {

    $projectId = "ejaz-attendance-system";

    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

    $accessToken = getAccessToken();

    $payload = [
        "message" => [
            "token" => $token,
            "notification" => [
                "title" => $title,
                "body" => $body
            ],
            "data" => $data
        ]
    ];

    $headers = [
        "Authorization: Bearer " . $accessToken,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);

    if(curl_errno($ch)){
        error_log(curl_error($ch));
    }

    curl_close($ch);

    return $response;
}

// ✅ المحرك المركزي للمواعيد والورديات (لتجنب الأخطاء)
function getShiftDetails($conn, $emp_id, $date = null) {
    if (!$date) $date = date('Y-m-d');
    $day_name = date('D', strtotime($date)); // Sun, Mon, etc.

    // 1. التحقق من يوم الجمعة (إجازة أساسية)
    if ($day_name == 'Fri') {
        return ['is_holiday' => true, 'reason' => 'إجازة نهاية الأسبوع (الجمعة)'];
    }

    // 2. التحقق من الإجازات الإدارية (Admin Holidays)
    $stmt = $conn->prepare("SELECT description FROM holidays WHERE date = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return ['is_holiday' => true, 'reason' => $row['description']];
    }

    // 2.5 ✅ التحقق من إجازات الموظف (Leave Requests)
    $stmt = $conn->prepare("
        SELECT reason 
        FROM leave_requests 
        WHERE employee_id = ?
        AND status = 'approved'
        AND ? BETWEEN from_date AND to_date
    ");
    $stmt->bind_param("is", $emp_id, $date);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        return [
            'is_holiday' => true,
            'reason' => 'إجازة موظف: ' . $row['reason']
        ];
    }

    // 3. جلب نوع شفت الموظف المعتاد
    $emp_res = $conn->query("SELECT shift , nationality_type FROM employees WHERE id = $emp_id");
    $emp = $emp_res->fetch_assoc();
    $shift_type = $emp['shift'] ?? 'shift1';

    // 4. تحديد المواعيد بناءً على اليوم
    $shift_type = $emp['shift'] ?? 'shift1';
    $nationality = $emp['nationality_type'] ?? 'expat';

    if ($day_name == 'Sat') {

        // ✅ السعودي: إجازة السبت
        if ($nationality == 'saudi') {
            return [
                'is_holiday' => true,
                'reason' => 'إجازة نهاية الأسبوع (السبت )'
            ];
        }
        // السبت: موعد موحد (5 م - 9 م) لجميع الموظفين وبدون بريك
        return [
            'is_holiday' => false,
            'start_time' => '17:00:00',
            'end_time'   => '21:00:00',
            'has_break'  => false,
            'display'    => '05:00 م - 09:00 م (دوام السبت الاستثنائي)'
        ];
    } else {
        // الأيام العادية (الأحد - الخميس)
        switch ($shift_type) {

            case 'shift1':
                return [
                    'is_holiday' => false,
                    'start_time' => '10:00:00',
                    'end_time'   => '19:00:00',
                    'break_start'=> '16:00:00',
                    'break_end'  => '17:00:00',
                    'has_break'  => true,
                    'display'    => '10:00 ص - 07:00 م'
                ];

            case 'shift2':
                return [
                    'is_holiday' => false,
                    'start_time' => '12:00:00',
                    'end_time'   => '21:00:00',
                    'break_start'=> '16:00:00',
                    'break_end'  => '17:00:00',
                    'has_break'  => true,
                    'display'    => '12:00 م - 09:00 م'
                ];

            case 'shift3': // ✅ 9 → 7
                return [
                    'is_holiday' => false,
                    'start_time' => '09:00:00',
                    'end_time'   => '19:00:00',
                    'break_start'=> '15:00:00', // 3 العصر
                    'break_end'  => '17:00:00', // 5 العصر
                    'has_break'  => true,
                    'display'    => '09:00 ص - 07:00 م'
                ];

            case 'shift4': // ✅ 9 → 9
                return [
                    'is_holiday' => false,
                    'start_time' => '09:00:00',
                    'end_time'   => '21:00:00',
                    'break_start'=> '13:00:00', // 1 الظهر
                    'break_end'  => '17:00:00', // 5 العصر
                    'has_break'  => true,
                    'display'    => '09:00 ص - 09:00 م'
                ];

            case 'shift5': // ✅ 9 → 9 (بريك 2 → 6)
                return [
                    'is_holiday' => false,
                    'start_time' => '09:00:00',
                    'end_time'   => '21:00:00',
                    'break_start'=> '14:00:00', // 2 الظهر
                    'break_end'  => '18:00:00', // 6 المغرب
                    'has_break'  => true,
                    'display'    => '09:00 ص - 09:00 م (بريك 2 - 6)'
                ];

            case 'shift6': // ✅ 9 → 5 (بدون بريك)
                return [
                    'is_holiday' => false,
                    'start_time' => '09:00:00',
                    'end_time'   => '17:00:00',
                    'has_break'  => false,
                    'display'    => '09:00 ص - 05:00 م (بدون بريك)'
                ];

            default:
                return [
                    'is_holiday' => false,
                    'start_time' => '10:00:00',
                    'end_time'   => '19:00:00',
                    'has_break'  => true,
                    'display'    => 'افتراضي'
                ];
        }
    }
}
?>