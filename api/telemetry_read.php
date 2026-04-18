<?php
include 'db_config.php';
require_once 'auth_helpers.php';

// Endpoint to fetch historical telemetry data
// Supports: uid (UUID or session), device_id, limit, offset

$userId = resolve_read_user_id($conn);

$device_id = $_GET['device_id'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($device_id === '') {
    echo json_encode(["error" => "Missing device_id"]);
    exit;
}

// 1. Fetch telemetry records
// We order by recorded_at DESC to get newest first
$stmt = $conn->prepare("SELECT payload, recorded_at FROM device_telemetry WHERE user_id = ? AND device_id = ? ORDER BY recorded_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("isii", $userId, $device_id, $limit, $offset);
$stmt->execute();
$stmt->bind_result($payloadJson, $timestamp);

$results = [];
while ($stmt->fetch()) {
    $results[] = [
        "data" => json_decode($payloadJson, true),
        "timestamp" => $timestamp
    ];
}
$stmt->close();

echo json_encode([
    "device_id" => $device_id,
    "count" => count($results),
    "records" => $results
]);

$conn->close();
?>
