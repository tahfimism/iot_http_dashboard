<?php
include 'db_config.php';
require_once 'auth_helpers.php';

// Endpoint to fetch historical telemetry data
// Supports: uid (UUID or session), device_id, limit, offset

$userId = resolve_read_user_id($conn);

$device_id = $_GET['device_id'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

if ($device_id === '') {
    echo json_encode(["error" => "Missing device_id"]);
    exit;
}

// 1. Build Query with filters
$query = "SELECT payload, recorded_at FROM device_telemetry WHERE user_id = ? AND device_id = ?";
$params = [$userId, $device_id];
$types = "is";

if ($from !== '') {
    $query .= " AND recorded_at >= ?";
    $params[] = $from;
    $types .= "s";
}
if ($to !== '') {
    $query .= " AND recorded_at <= ?";
    $params[] = $to;
    $types .= "s";
}

$query .= " ORDER BY recorded_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
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
