<?php
include 'db_config.php';
require_once 'auth_helpers.php';

// Endpoint to delete telemetry data for a specific device
// Requires session auth (manual action from dashboard)

$userId = require_api_auth_user_id();
$device_id = $_GET['device_id'] ?? '';

if ($device_id === '') {
    echo json_encode(["error" => "Missing device_id"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM device_telemetry WHERE user_id = ? AND device_id = ?");
$stmt->bind_param("is", $userId, $device_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Telemetry history cleared"]);
} else {
    echo json_encode(["error" => "Failed to clear history"]);
}

$stmt->close();
$conn->close();
?>
