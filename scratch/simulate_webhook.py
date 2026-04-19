import requests
import time
import random

# --- CONFIGURATION ---
BASE_URL = "http://localhost/firefly/iot/api/webhook.php"
DEVICE_ID = "fan"
USER_UID = "329bf2ca-3b25-11f1-8bb2-b34956d21a0e"
INTERVAL = 1  # seconds
METHOD = "GET"  # Change to "GET" to use GET-based webhook simulation

def simulate_device():
    print(f"Starting {METHOD} simulation for device: {DEVICE_ID}...")
    
    while True:
        try:
            # Generate sample telemetry data
            payload = {
                "temperature": round(random.uniform(22.0, 28.0), 1),
                "humidity": random.randint(40, 60),
                "rssi": random.randint(-70, -30)
            }
            
            # Auth and routing params (always needed)
            params = {
                "device_id": DEVICE_ID,
                "uid": USER_UID
            }
            
            if METHOD.upper() == "POST":
                # Send POST request with JSON body
                response = requests.post(BASE_URL, params=params, json=payload, timeout=5)
            else:
                # For GET, all telemetry keys must be passed as query parameters
                params.update(payload)
                response = requests.get(BASE_URL, params=params, timeout=5)
            
            if response.status_code == 200:
                print(f"[{time.strftime('%H:%M:%S')}] Method: {METHOD.upper()} | Sent: {payload} | Response: {response.text}")
            else:
                print(f"Error {response.status_code}: {response.text}")
                
        except Exception as e:
            print(f"Connection failed: {e}")
            
        time.sleep(INTERVAL)

if __name__ == "__main__":
    simulate_device()
