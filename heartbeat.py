import time
import requests
from datetime import datetime

EMPLOYEE_ID = 6
SERVER_URL = "https://attendanceejaz.codeyla.com/heartbeat"

AW_LOCAL_API = "http://127.0.0.1:5600/api/0"
WATCHER_ID = "aw-watcher-afk_AhmedSalah"

def get_status():
    try:
        resp = requests.get(
            f"{AW_LOCAL_API}/buckets/{WATCHER_ID}/events?limit=1",
            timeout=5
        )
        resp.raise_for_status()
        events = resp.json()

        if events:
            event = events[-1]
            status = event["data"].get("status", "not-afk")
            print(f"DEBUG -> Status: {status}")
            return status

        return "not-afk"

    except Exception as e:
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Error: {e}")
        return "not-afk"

def send_heartbeat(status):
    data = {
        "employee_id": EMPLOYEE_ID,
        "status": status,
        "timestamp": datetime.now().isoformat()
    }

    try:
        response = requests.post(SERVER_URL, json=data, timeout=5)

        if response.status_code == 200:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Sent: {status}")
        else:
            print(f"Server error: {response.status_code}")

    except Exception as e:
        print(f"Error sending: {e}")

print("Heartbeat running...")

while True:
    status = get_status()
    send_heartbeat(status)
    time.sleep(60)