# Firefly IoT Control

## Project Overview

**Firefly IoT** is a lightweight, monolithic, self-hosted web application designed to act as a centralized command and control dashboard for remote microcontrollers (such as ESP32 and Arduino).

**Core Purpose:**
The platform simplifies the process of establishing bidirectional communication between hardware and a web interface without the overhead of complex messaging brokers (like MQTT) or third-party cloud SaaS solutions.

**Target Users:**
Hardware developers, makers, hobbyists, and IoT engineers looking for an isolated, fast-to-deploy, and easily customizable platform.

**Main Problem It Solves:**
Building IoT infrastructure often demands heavy message brokers, complex cloud setups, or paid subscriptions. Firefly IoT solves this by offering a straightforward HTTP-based polling and webhook architecture. This makes it universally compatible with highly constrained edge devices and eliminates the barrier to entry for full-stack IoT integration.

**High-Level Idea:**
Users register and create isolated device profiles. For each profile, the system generates unique API endpoints secured by UUIDs. The core magic lies in the dashboard: it dynamically parses incoming JSON state data from edge devices, infers the data types, and automatically renders the appropriate UI controls (toggles, text inputs) without requiring any manual UI configuration.

---

## System Capabilities / Features

- **Dual State Management:** The system handles both *live actuation control* (e.g., toggling a relay via the dashboard, which is then fetched by the edge device) and *historical telemetry logging* (e.g., time-series sensor data pushed by the edge device).
- **Auto-Typed State Inference:** A unique, intelligent feature where the backend parses incoming telemetry and automatically infers whether a value is `binary` (boolean/1/0), a `number`, or `text`. The dashboard uses this metadata to dynamically construct the correct UI component (a toggle switch vs. a numeric input block).
- **Inline Data Cap Management:** To prevent database bloat and enforce subscription tiers (Free vs. Premium), the webhook ingestion system automatically enforces a rolling window. It purges the oldest telemetry records directly during the `POST` request, ensuring the user's data limits are strictly maintained.
- **Dynamic UI Generation:** The Vanilla JS frontend is entirely reactive. It periodically fetches the typed JSON state and constructs the user interface on-the-fly, allowing for immediate visualization of newly added sensors.
- **Historical Telemetry Visualization:** Integrates Chart.js to visualize paginated historical logs and time-series sensor data natively within the dashboard.

---

## Technical Architecture

**Overall Architecture:**
Firefly IoT relies on a **Monolithic Client-Server Architecture** augmented by **Edge Device HTTP Polling**. It intentionally avoids persistent connections (like WebSockets) in favor of frequent, lightweight asynchronous HTTP requests.

**Folder & Component Structure:**

- **Frontend (Root Level):** Single-Page Application (SPA)-style UI served via PHP (`index.php`, `dashboard.php`, `telemetry.php`).
- **Backend (`/api/`):** A stateless REST-like PHP API (`read.php`, `write.php`, `webhook.php`, `add_device.php`) that handles external communication.
- **Helper Modules:** Shared internal logic (`auth_helpers.php` for session/token resolution, `state_helpers.php` for type inference, `db_config.php` for connections).

**Data Flow:**

1. **Actuation (Dashboard → Device):**
   - User clicks a toggle on the UI.
   - Dashboard asynchronously calls `api/write.php`.
   - Backend updates the JSON blob in the `device_status` database table.
   - ESP32 edge device continuously polls `api/read.php`.
   - ESP32 receives the new JSON and executes the hardware change (e.g., toggles a relay).
2. **Telemetry (Device → Dashboard):**
   - ESP32 pushes sensor data (e.g., temperature) to `api/webhook.php`.
   - Backend logs the event to `device_telemetry` and updates the live `device_status` JSON blob.
   - Dashboard's 5-second polling loop fetches the updated state and visually re-renders the component.

**Database / Schema Design:**

- `iot_users`: Manages authentication, tier levels, and UUID generation.
- `device_status`: Holds the absolute *latest* state of a device as a JSON blob (`state_json`). This allows for fast, O(1) retrieval during aggressive polling.
- `device_telemetry`: An append-only log for historical time-series data.

**Authentication & Authorization:**
The system uses a hybrid approach. The web UI relies on secure PHP session cookies. However, edge devices—which typically lack advanced cookie management—use a permanent UUID (`uid`) passed as a query parameter. The `resolve_uid_to_user_id` module maps this UUID to an internal, sequential User ID, securing endpoints against Insecure Direct Object Reference (IDOR) attacks.

---

## Internal Logic & Implementation

**Webhook Ingestion (`api/webhook.php`):**
This endpoint acts as the primary data ingestion pipeline. It accepts both JSON bodies and query parameters. Internally, it maps the UUID, verifies ownership, checks the user tier quota, and deletes overflow records (`DELETE FROM ... ORDER BY recorded_at ASC LIMIT X`). It then appends to the telemetry log and merges the new data into the active JSON state.

**Type Inference Engine (`api/state_helpers.php`):**
A critical subsystem for the UI engine. Functions like `infer_type_from_value` evaluate raw payload data to assign structural metadata (`type: binary/number/text`). The `cast_value_by_type` function ensures strict data consistency, interpreting diverse inputs like `"on"`, `"true"`, or `1` strictly as an integer `1` for the database.

**Polling Optimization:**
Endpoints like `api/read.php` are explicitly optimized for minimal overhead, utilizing single indexed queries and fast JSON encoding to reduce the burden on the server caused by frequent edge device requests.

---

## Tech Stack

- **Backend:** PHP 8+. Chosen for its ubiquity, shared-nothing architecture (perfect for stateless HTTP request handling), and ease of deployment on any LAMP/LEMP stack.
- **Database:** MySQL/MariaDB. Selected for robust ACID compliance, reliable user management, and excellent JSON column support.
- **Frontend:** HTML5, CSS3, Vanilla JS. Chosen for minimal footprint, zero-build-step deployment, and ensuring the application remains lightweight.
- **Libraries:** Chart.js (via CDN) for fluid, client-side time-series visualization.
- **Edge Devices:** C++ (Arduino framework) utilizing `HTTPClient` and `ArduinoJson`.

---

## User Experience / Product Flow

1. **Onboarding:** A user registers an account (`signup.php`) and authenticates.
2. **Dashboard Management:** On the main dashboard, the user inputs a custom identifier to register a new edge device.
3. **Integration Setup:** Selecting a device card reveals the specific `READ API` and `WEBHOOK API` URLs. These URLs already include the user's secure UUID and are ready to be copy-pasted into the microcontroller code.
4. **Interaction:**
   - As the device sends data, the UI auto-generates cards displaying sensor readings or controls.
   - Users can manually inject new key/value pairs using the "Add / Update State" panel.
   - The UI intelligently refreshes every 5 seconds, suppressing updates if the user is actively typing in an input field.
5. **Analytics:** Clicking "DEVICE HISTORY" transitions to `telemetry.php`, offering an interactive graph and paginated table of historical data.

---

## Engineering Decisions

- **HTTP Polling vs. WebSockets/MQTT:**
  Deliberately chosen to eliminate the need for persistent connection management, external broker installations (like Mosquitto), and complex NAT/Firewall configurations. It sacrifices micro-second latency in exchange for unbeatable architectural simplicity.
- **JSON Blobs for State Management:**
  Instead of an Entity-Attribute-Value (EAV) database design, `device_status` utilizes a single JSON column. Trade-off: It limits the ability to write SQL queries targeting specific sensor keys. Benefit: Infinite schema flexibility. Users can send any arbitrary payload (`temperature`, `servo_angle`) without requiring database migrations.
- **Inline Cleanup vs. Cron Jobs:**
  Telemetry data limits are enforced directly within the `POST` request cycle rather than relying on a background Cron job. This guarantees strict adherence to storage quotas at all times, with a negligible increase in latency during webhook ingestion.

---

## Current Status

- **Completed Core Platform:** User authentication, device CRUD operations, dynamic state UI rendering, hybrid auth mapping (UUID to Session ID), webhook data merging, and telemetry charting are fully functional.
- **Technical Debt & Tradeoffs:** The aggressive edge device polling approach puts continuous read load on the MySQL database. Additionally, the `webhook.php` syntax for deleting rows with limit constraints might experience performance degradation on exceptionally massive tables without highly optimized subqueries.

---

## Future Improvements

- **Real-Time WebSockets Bridge:** Introduce a lightweight WebSocket server (e.g., Swoole or Ratchet) for low-latency UI updates without aggressive frontend polling, retaining HTTP as a fallback.
- **Automated Rules Engine:** Implement a background logic processor to evaluate simple conditionals on the backend (e.g., `IF device_A.temp > 30 THEN SET device_B.fan = 1`).
- **Caching Layer:** Integrate Redis or Memcached to store the active `device_status` JSON blobs, offloading read pressure from the primary MySQL database.
- **Data Export:** Add CSV/JSON export capabilities directly from the telemetry view for advanced data science integrations.
- **OTA Provisioning:** Develop an Over-The-Air Wi-Fi configuration portal for the ESP32 boilerplate firmware to eliminate hardcoded network credentials.

---

## Conclusion

Firefly IoT Control is an elegantly straightforward platform that bridges the gap between hardware and the web. By leveraging robust web standards and prioritizing architectural simplicity over trendy complex frameworks, it offers a highly capable, zero-friction environment for developers to deploy, visualize, and command IoT networks effectively.
