<?php
include 'db_config.php';
require_once 'state_helpers.php';
require_once 'auth_helpers.php';

// Webhook endpoint: supports both GET and POST
// Requires: uid (UUID), device_id
// Optional: key-value pairs of telemetry data

$userId = resolve_uid_to_user_id($conn);
if ($userId <= 0) {
    // resolve_uid_to_user_id already handles 401 if it fails but uid was provided.
    // However, if uid was missing, it returns 0.
    header("Content-Type: text/plain");
    echo "ERROR: Missing or invalid uid";
    exit;
}

$device_id = $_GET['device_id'] ?? '';
if ($device_id === '') {
    header("Content-Type: text/plain");
    echo "ERROR: Missing device_id";
    exit;
}

// 1. Re-verify device ownership
$checkStmt = $conn->prepare("SELECT 1 FROM device_status WHERE device_id = ? AND user_id = ? LIMIT 1");
$checkStmt->bind_param("si", $device_id, $userId);
$checkStmt->execute();
if (!$checkStmt->fetch()) {
    $checkStmt->close();
    header("Content-Type: text/plain");
    echo "ERROR: Device not found or unauthorized";
    exit;
}
$checkStmt->close();

// 2. Extract payload
$payload = [];

// Priority 1: JSON body
$jsonBody = file_get_contents('php://input');
if (!empty($jsonBody)) {
    $decoded = json_decode($jsonBody, true);
    if (is_array($decoded)) {
        $payload = array_merge($payload, $decoded);
    }
}

// Priority 2: Query parameters (excluding auth/routing params)
$excludedParams = ['uid', 'device_id', 'user_id'];
foreach ($_GET as $key => $value) {
    if (!in_array($key, $excludedParams)) {
        $payload[$key] = $value;
    }
}

if (empty($payload)) {
    header("Content-Type: text/plain");
    echo "ERROR: Empty payload";
    exit;
}

// 3. Enforce Data Caps based on User Tier
$config = include 'iot_config.php';
$tierStmt = $conn->prepare("SELECT user_type FROM iot_users WHERE id = ? LIMIT 1");
$tierStmt->bind_param("i", $userId);
$tierStmt->execute();
$tierStmt->bind_result($userType);
$tierStmt->fetch();
$tierStmt->close();

$limits = $config['telemetry'];
$maxRecords = ($userType === 'premium') ? $limits['max_records_premium'] : $limits['max_records_free'];

// Count existing records
$countStmt = $conn->prepare("SELECT COUNT(*) FROM device_telemetry WHERE user_id = ? AND device_id = ?");
$countStmt->bind_param("is", $userId, $device_id);
$countStmt->execute();
$countStmt->bind_result($currentCount);
$countStmt->fetch();
$countStmt->close();

if ($currentCount >= $maxRecords) {
    // Delete oldest to make room (rolling window)
    $overflow = ($currentCount - $maxRecords) + 1;
    // MariaDB/MySQL DELETE joined with ORDER BY requires specific syntax or a subquery
    // For simplicity and compatibility:
    $deleteIdsStmt = $conn->prepare("DELETE FROM device_telemetry WHERE user_id = ? AND device_id = ? ORDER BY recorded_at ASC LIMIT ?");
    $deleteIdsStmt->bind_param("isi", $userId, $device_id, $overflow);
    $deleteIdsStmt->execute();
    $deleteIdsStmt->close();
}

// 4. Log Telemetry
$jsonPayload = json_encode($payload);
$logStmt = $conn->prepare("INSERT INTO device_telemetry (user_id, device_id, payload) VALUES (?, ?, ?)");
$logStmt->bind_param("iss", $userId, $device_id, $jsonPayload);
$logStmt->execute();
$logStmt->close();

// 5. Update Live State (Option B: Sync incoming data to dashboard)
// Fetch current state
$selectStmt = $conn->prepare("SELECT state_json FROM device_status WHERE device_id = ? AND user_id = ?");
$selectStmt->bind_param("si", $device_id, $userId);
$selectStmt->execute();
$selectStmt->bind_result($currentJson);
$currentTypedState = [];
if ($selectStmt->fetch()) {
    $currentTypedState = normalize_typed_state(json_decode($currentJson, true));
}
$selectStmt->close();

// Merge payloads
foreach ($payload as $key => $val) {
    // Infer types for new telemetry keys if they don't exist
    $type = isset($currentTypedState[$key]) ? $currentTypedState[$key]['type'] : infer_type_from_value($val);
    
    // Cast appropriately
    $isValid = true;
    $castedValue = cast_value_by_type($val, $type, $isValid);
    
    if ($isValid) {
        $currentTypedState[$key] = [
            'value' => $castedValue,
            'type' => $type,
            'source' => 'telemetry'
        ];
    }
}

$updatedJson = json_encode($currentTypedState);
$updateStatusStmt = $conn->prepare("UPDATE device_status SET state_json = ?, last_seen = CURRENT_TIMESTAMP WHERE device_id = ? AND user_id = ?");
$updateStatusStmt->bind_param("ssi", $updatedJson, $device_id, $userId);
$updateStatusStmt->execute();
$updateStatusStmt->close();

header("Content-Type: text/plain");
echo "OK";

$conn->close();
?>
