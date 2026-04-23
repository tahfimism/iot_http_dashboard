# Firefly IoT — Project Blueprint & Documentation

## 1. Project Overview

**Purpose:**
Firefly IoT is a lightweight, self-hosted IoT management platform designed to connect, control, and monitor microcontrollers (like ESP32 and Arduino) via a centralized web dashboard.

**Problem it Solves:**
Building IoT infrastructure often requires heavy message brokers (MQTT), complex cloud setups (AWS IoT), or paid SaaS platforms. Firefly IoT solves this by offering a simpler, HTTP-based polling and webhook architecture that allows makers and developers to instantly sync device state and log historical telemetry without managing complex pub/sub systems.

**Target Users:**
Hardware developers, hobbyists, and IoT engineers who need a quick, reliable, and isolated dashboard to manage remote sensors and actuators.

**Core Value Proposition:**

- **Simplicity:** HTTP-first communication easily implemented on any microcontroller with Wi-Fi.
- **Dual State Management:** Supports both live-state syncing (actuator control) and historical telemetry logging (sensor data).
- **Auto-Typed State:** The backend automatically infers whether a payload value is a number, binary (toggle), or text, dynamically generating the appropriate UI controls without explicit configuration.

---

## 2. System Architecture

**High-Level Architecture:**
The system is built as a **Monolithic Client-Server Architecture** augmented by **Edge Device Polling**. It does not use WebSockets or MQTT; instead, it relies on frequent asynchronous HTTP requests.

**Component Breakdown:**

1. **Frontend (Dashboard & Telemetry View):** Vanilla JS Single Page Application (SPA)-like experience served via PHP. Polls the backend for state updates.
2. **Backend (API Server):** A PHP-based REST API that processes authentication, device state mutations, and telemetry ingestion.
3. **Database:** MySQL relational database storing user profiles, current device states (as JSON blobs), and historical telemetry rows.
4. **Edge Devices (Microcontrollers):** Physical ESP32/Arduino devices that execute a continuous loop, pushing data to webhooks and polling the read API for actuation commands.

**Interaction & Data Flow:**

- **Control Flow (Dashboard → Device):**
  User clicks a toggle on the UI → Dashboard calls `api/write.php` → Backend updates the JSON blob in `device_status` table.
  Independently, the ESP32 polls `api/read.php` every second → Receives the new JSON → Changes hardware state (e.g., turns on LED).
- **Telemetry Flow (Device → Dashboard):**
  ESP32 reads sensor data → Sends HTTP GET/POST to `api/webhook.php` → Backend logs to `device_telemetry` and updates live `device_status` → Dashboard auto-refreshes every 5 seconds to display new data.

---

## 3. Tech Stack

### Frontend

- **HTML5/CSS3/Vanilla JS:** Chosen for ultimate lightweight performance and zero build-step overhead. No React/Vue means the system can run on heavily constrained servers or shared hosting environments.
- **Chart.js:** Used in `telemetry.php` to visualize historical sensor data over time. Chosen for its minimal footprint and ease of integration via CDN.

### Backend

- **PHP 8+:** Serves as the core logic engine. Chosen for its ubiquity, "shared-nothing" architecture (which inherently isolates request states), and ease of deployment on any standard LAMP/LEMP stack.
- **Stateless API Design:** Interactions between the frontend UI and the backend operate via dedicated REST-like API endpoints.

### Database

- **MySQL / MariaDB:** Relational database. Chosen for robust ACID compliance, structured user management, and excellent JSON column support.

### Hardware (Edge)

- **C++ (Arduino framework):** Used on the edge devices. Utilizes `HTTPClient` and `ArduinoJson` to parse incoming HTTP responses and format outbound webhooks.

---

## 4. Database Design

**Schema Strategy:**
The database uses a hybrid approach: relational tables for structured data (Users, Constraints) and JSON columns for flexible schema-less data (Device State).

**Key Models:**

- `iot_users`:
  - Fields: `id`, `uid` (UUID), `user_type` (ENUM: free, premium), `email`, `password_hash`.
  - *Why UUID?* The `uid` is used in public-facing device URLs. This prevents Insecure Direct Object Reference (IDOR) attacks, ensuring malicious actors cannot enumerate numeric `id`s to hijack other users' devices.
- `device_status`:
  - Fields: `device_id` (Unique string), `user_id` (Foreign Key), `state_json` (TEXT/JSON), `last_seen` (TIMESTAMP).
  - *Role:* Holds the absolute *latest* state of the device. This allows O(1) retrieval for the frequent device polling requests.
- `device_telemetry`:
  - Fields: `id`, `user_id`, `device_id`, `payload` (JSON), `recorded_at` (TIMESTAMP).
  - *Role:* Append-only log of sensor readings for historical charting. Indexed by `(device_id, recorded_at)` for fast time-series retrieval.

**Data Lifecycle & Optimization:**

- **Creation:** Devices are created manually via UI.
- **Update:** `state_json` is overwritten on every `write.php` or `webhook.php` call.
- **Deletion (Rolling Window):** To prevent database bloat, the system enforces user tier limits (e.g., 500 records for free, 10,000 for premium). When `webhook.php` receives a new payload, it counts existing records and executes an inline `DELETE ... ORDER BY recorded_at ASC LIMIT X` to remove oldest records.

---

## 5. Backend Architecture

**Folder Structure:**

- `/` (Root): Public-facing PHP views (`index.php`, `login.php`, `dashboard.php`, `telemetry.php`).
- `/api/`: Core REST endpoints (`read.php`, `write.php`, `webhook.php`, etc.).
- `/api/auth_helpers.php`: Session and UUID resolution logic.
- `/api/state_helpers.php`: Type inference logic for JSON payloads.
- `/api/db_config.php`: Database connection bootstrapping.

**Core Modules:**

- **State Helpers:** Contains the critical `normalize_typed_state()` function. Since devices just send raw numbers or strings, this module infers if a value is a `binary` (0/1/true/false), `number`, or `text`, and wraps it in a typed schema. This tells the frontend whether to render a toggle switch or a text input.
- **Auth Helpers:** Handles both session-based auth (for the browser UI) and token-based auth (`uid` in query params for edge devices).

---

## 6. API Design

**Key Endpoints:**

- **State Management:**
  - `GET /api/read.php?device_id=X&uid=Y`: Device polls this. Returns simple flat JSON `{ "led": 1 }`.
  - `GET /api/read.php?device_id=X&uid=Y&format=full`: UI polls this. Returns rich JSON with type metadata.
  - `GET /api/write.php?device_id=X&key=Y&value=Z`: UI calls this to mutate state.
- **Telemetry Ingestion:**
  - `GET/POST /api/webhook.php?device_id=X&uid=Y`: Device sends sensor data. Supports URL params or JSON body.
- **Device CRUD:**
  - `GET /api/add_device.php`, `GET /api/delete_device.php`, `GET /api/get_devices.php`.

**Request Flow Example (Webhook):**

1. Request arrives at `webhook.php`.
2. Auth layer resolves `uid` to internal `user_id`.
3. Validates device ownership.
4. Checks user tier limits; purges oldest telemetry if cap is reached.
5. Inserts payload into `device_telemetry`.
6. Merges payload into current `device_status` state.

**Error Handling:**
All API endpoints return standard HTTP status codes (200, 401) with JSON payload `{ "error": "Reason" }`.

---

## 7. Frontend Architecture

**UI Structure:**

- The UI uses a card-based layout system. The left panel shows available devices, the right panel dynamically generates controls based on the selected device's schema.

**State Management & API Integration:**

- The frontend holds minimal local state (`state.currentDevice`).
- A `setInterval` loop runs every 5 seconds, calling `loadFeatures()` which fetches `/api/read.php?format=full`.
- The UI is entirely reactive to the backend JSON. It destroys and rebuilds the DOM elements for the device cards based on the incoming JSON.
- To prevent UI jank, the loop skips fetching if the user is currently focused on an input field (`isEditingValue()`).

---

## 8. Core Features

### 1. Dynamic UI Generation (Macro)

- *What it does:* Generates toggles, inputs, or text displays automatically.
- *How it works:* The backend wraps data in a typed format: `"led_1": { "value": 1, "type": "binary" }`. The JS frontend loops through these keys. If `type === 'binary'`, it renders a `.toggle-btn`. If `number`, it renders a `.number-input` with a "SET" button.

### 2. Telemetry Ingestion & Charting (Macro)

- *What it does:* Stores and visualizes historical sensor data.
- *How it works:* Edge devices push data. `telemetry.php` fetches paginated data from `api/telemetry_read.php`. The frontend parses the history, extracts dynamic keys, and feeds arrays into `Chart.js` for plotting.

### 3. Inline Data Cap Management (Micro)

- *What it does:* Prevents infinite database growth per user.
- *How it works:* During the `webhook.php` execution, the script fetches the user tier limit from `iot_config.php`. It calculates the overflow and deletes the exact number of excess old records before inserting the new one.

### 4. Hybrid Authentication (Micro)

- *What it does:* Secures devices without requiring complex JWT logic on edge microcontrollers.
- *How it works:* The UI relies on secure PHP sessions cookies. Edge devices, lacking cookie support, include a permanent `uid` (UUID) query parameter. The `resolve_uid_to_user_id()` function maps this UUID securely back to the session user.

---

## 9. Data Flow & Pipelines (CRITICAL)

**End-to-End Control Flow (User toggles LED):**

1. User clicks Toggle on `dashboard.php`.
2. JS event listener fires `setFeatureValue('led', 1, 'binary')`.
3. JS performs `fetch('api/write.php?device_id=X&key=led&value=1&type=binary')`.
4. `write.php` decodes the current `state_json`, updates the `led` key, and executes `UPDATE device_status...`.
5. *Wait 100ms...*
6. ESP32 loop runs `http.GET("api/read.php?device_id=X&uid=Y")`.
7. `read.php` queries `device_status`, flattens the JSON, and outputs `{"led": 1}`.
8. ESP32 parses JSON, executes `digitalWrite(LED_PIN, 1)`.

**End-to-End Telemetry Flow (Sensor reads Temp):**

1. ESP32 loop reads DHT11 sensor `temp = 24.5`.
2. ESP32 performs `http.GET("api/webhook.php?uid=Y&temp=24.5")`.
3. `webhook.php` resolves UUID to User ID.
4. Purges oldest row if tier limit (e.g., 500 rows) is exceeded.
5. `INSERT INTO device_telemetry (payload) VALUES ('{"temp": 24.5}')`.
6. Merges `{"temp": 24.5}` into `device_status` JSON and `UPDATE`s it.
7. *Wait 3 seconds...*
8. Dashboard 5-second polling loop triggers.
9. Dashboard fetches `api/read.php?format=full`.
10. UI updates to show the new temperature badge in the "Latest Readings" panel.

---

## 10. Security & Validation

- **Authentication:** Web UI uses PHP Session auth. Device API uses UUIDs (`uid`). Sequential IDs are strictly hidden from external APIs to prevent enumeration.
- **Authorization:** Every API call explicitly binds queries with `user_id = ?` AND `device_id = ?`. A user can never mutate or read a device they do not own.
- **Input Validation:** Backend `cast_value_by_type` normalizes inputs (e.g., mapping `"on"`, `"true"`, `1` to a strict integer `1`).
- **SQL Injection Prevention:** 100% of database queries utilize MySQLi Prepared Statements (`bind_param`).
- **Data Protection:** Passwords are hashed using `PASSWORD_DEFAULT` (bcrypt).

---

## 11. Performance Considerations

- **Polling Overhead:** Polling introduces backend load. To mitigate this, `api/read.php` is heavily optimized. It executes a single indexed query, parses JSON, and exits in milliseconds.
- **JSON Payload Flattening:** Edge microcontrollers have tiny RAM footprints. `read.php` natively flattens the rich metadata payload into a 1-dimensional key-value JSON string to prevent ArduinoJson memory overflows.
- **Cleanup Strategy:** Telemetry cleanup runs *inline* during the POST request rather than via a cron job. This guarantees data caps are never breached, even momentarily, at the cost of slight latency on the ingestion webhook.

---

## 12. Design Decisions & Trade-offs

**HTTP Polling vs. MQTT / WebSockets:**

- *Decision:* Used HTTP Polling.
- *Trade-off:* Higher latency (1-5 seconds) and network overhead compared to WebSockets. However, it completely eliminates the need for maintaining persistent connections, running a broker (Mosquitto), or dealing with complex NAT/Firewall keep-alives. Unbeatable simplicity for basic home IoT.

**JSON Blobs vs. EAV (Entity-Attribute-Value) Tables:**

- *Decision:* Device state is stored as a single JSON blob in `device_status`.
- *Trade-off:* We lose the ability to write efficient SQL queries targeting specific device keys (e.g., `SELECT * WHERE state.led = 1`). However, it allows infinite schema flexibility. Users can send `temperature`, `humidity`, or `servo_angle` without needing to define database columns beforehand.

---

## 13. Future Scope

- **WebSockets / MQTT Bridge:** Introduce a lightweight WebSocket server (e.g., using Swoole or Ratchet) for true real-time, low-latency UI updates, falling back to HTTP polling.
- **Rules Engine:** Implement a backend CRON task that evaluates simple if-this-then-that logic (e.g., `IF temperature > 30 THEN write fan = 1`).
- **Data Export:** Add a CSV/JSON export feature on the telemetry page for external data science processing.
- **Device Provisioning:** Add an OTA (Over-The-Air) Wi-Fi provisioning portal on the ESP32 to eliminate hardcoding network credentials.
