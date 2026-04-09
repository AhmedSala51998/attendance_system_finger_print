import time
import requests
from datetime import datetime

# ===== إعدادات الموظف والسيرفر =====
EMPLOYEE_ID = 5  # ضع هنا رقم الموظف
SERVER_URL = "https://attendanceejaz.codeyla.com/heartbeat"  # رابط السيرفر عندك

# REST API المحلي لـ ActivityWatch
AW_LOCAL_API = "http://127.0.0.1:5600/api/0"  # عادة localhost:5600

WATCHER_ID = "aw-watcher-afk_AhmedSalah"
last_sent_idle = 0  # 🔥 آخر قيمة idle تم إرسالها

def get_idle_minutes():
    try:
        # نجيب أحدث الأحداث من watcher
        resp = requests.get(f"{AW_LOCAL_API}/buckets/{WATCHER_ID}/events?limit=1", timeout=5)
        resp.raise_for_status()
        events = resp.json()
        if events:
            duration_sec = events[0]["duration"]
            return int(duration_sec / 60)
        return 0
    except Exception as e:
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Error fetching AW events: {e}")
        return 0

def send_heartbeat(idle_minutes):
    data = {
        "employee_id": EMPLOYEE_ID,
        "idleMinutes": idle_minutes,
        "timestamp": datetime.now().isoformat()
    }
    try:
        response = requests.post(SERVER_URL, json=data, timeout=5)
        if response.status_code == 200:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Sent: {idle_minutes} min idle")
        else:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Server error: {response.status_code}")
    except Exception as e:
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Error sending heartbeat: {e}")

print("ActivityWatch Heartbeat script running...")

while True:
    idle_minutes = get_idle_minutes()

    # 🔄 لو رجع نشيط (مش idle)
    if idle_minutes == 0:
        last_sent_idle = 0

    # 🔥 ابعت بس لو في زيادة جديدة
    if idle_minutes > last_sent_idle:
        send_heartbeat(idle_minutes)
        last_sent_idle = idle_minutes

    time.sleep(60)  # انتظر دقيقة