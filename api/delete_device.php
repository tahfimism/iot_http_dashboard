<?php
include 'db_config.php';
require_once 'auth_helpers.php';

$userId = require_api_auth_user_id();

$device_id = trim($_GET['device_id'] ?? '');

if ($device_id === '') {
    echo json_encode(["error" => "Missing device_id"]);
    exit;
}

$deleteStmt = $conn->prepare("DELETE FROM device_status WHERE device_id = ? AND user_id = ?");
$deleteStmt->bind_param("si", $device_id, $userId);
$deleteStmt->execute();

if ($deleteStmt->affected_rows > 0) {
    echo json_encode([
        "status" => "deleted",
        "device_id" => $device_id
    ]);
} else {
    echo json_encode(["error" => "Device not found"]);
}

$deleteStmt->close();
$conn->close();
?>